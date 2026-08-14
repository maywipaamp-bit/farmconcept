<?php

namespace App\Http\Controllers;

use App\Models\FollowUpRound;
use App\Models\Participant;
use App\Models\QrCode;
use App\Services\TrackingRoundService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

/**
 * QR ทำแบบประเมินติดตามสุขภาพ — QR เดียวทั้งโครงการ
 *
 * URL ไม่มีรหัสคน รหัสรอบ หรือรหัสแบบประเมินอยู่ข้างใน จึงพิมพ์ครั้งเดียวใช้ได้ตลอด
 * และ QR ที่หลุดถึงคนนอกก็ใช้ไม่ได้ เพราะต้องยืนยันตัวตนก่อนเสมอ
 * ห้ามทำ QR แยกรายคนเด็ดขาด — นั่นเท่ากับแจกกุญแจของคนนั้นให้ใครก็ได้ที่เห็นกระดาษ
 *
 * แบบประเมินที่ผู้ตอบได้เห็นมาจาก "รอบของเขา" ไม่ได้มาจาก QR
 */
class PublicTrackingRoundQrController extends Controller
{
    public function __construct(private readonly TrackingRoundService $rounds) {}

    /** หน้าที่ผู้ใช้เห็นหลังสแกน — ฟอร์มยืนยันตัวตน */
    public function landing(string $token): View|Response
    {
        $qr = QrCode::where('token', $token)->where('purpose', 'health')->firstOrFail();

        if (! $qr->is_active || ($qr->expires_at && $qr->expires_at->isPast())) {
            return response()->view('public.qr-unavailable', ['activity' => null], 410);
        }

        $qr->increment('scan_count');

        return view('public.tracking-round-qr', ['token' => $qr->token]);
    }

    /**
     * ยืนยันตัวตนแล้วคืนเฉพาะรอบที่ถึงกำหนดของคนนั้น
     *
     * ยืนยันด้วย เบอร์โทร + รหัสบุคคลบนใบยินยอม — สองอย่างคู่กัน
     * ถ้าให้กรอกแค่เบอร์แล้วเข้าได้เลย ใครก็ตอบแทนคนอื่นได้ด้วยการเดาเบอร์
     * (เหตุผลเดียวกับที่ ptp_verification_codes ถูกออกแบบไว้ตั้งแต่ต้น)
     *
     * จำกัดจำนวนครั้งต่อ IP เพราะรหัสบุคคลเป็นเลขเรียง เดาทีละใบได้ถ้าไม่จำกัด
     */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:30'],
            'person_code' => ['required', 'string', 'max:30'],
        ], [
            'phone.required' => 'กรุณากรอกเบอร์โทรศัพท์',
            'person_code.required' => 'กรุณากรอกรหัสบุคคลบนใบยินยอม',
        ]);

        $qr = QrCode::where('token', $data['token'])->where('purpose', 'health')->first();

        if ($qr === null || ! $qr->is_active) {
            return response()->json(['message' => 'ลิงก์นี้ใช้งานไม่ได้แล้ว'], 410);
        }

        $key = 'tracking-round-verify:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'message' => 'ลองหลายครั้งเกินไป กรุณารอสักครู่แล้วลองใหม่',
            ], 429);
        }

        RateLimiter::hit($key, 600);

        $participant = Participant::with('cohortProfile')
            ->where('person_code', trim($data['person_code']))
            ->get()
            /* เทียบเบอร์แบบตัวเลขล้วน ข้อมูลเก่าเก็บทั้งแบบมีขีดและไม่มี */
            ->first(fn (Participant $p) => $this->digits($p->phone) === $this->digits($data['phone']));

        if ($participant === null) {
            return response()->json([
                'message' => 'ไม่พบข้อมูล — ตรวจเบอร์โทรและรหัสบุคคลบนใบยินยอมอีกครั้ง',
            ], 422);
        }

        $due = $this->rounds->dueRoundsFor($participant);

        RateLimiter::clear($key);

        return response()->json([
            'success' => true,
            'participant' => [
                'personCode' => $participant->person_code,
                'name' => $participant->name,
            ],
            /* ชื่อรอบมาจากใบติดตามของคนนั้น ซึ่ง snapshot มาจากหน้าตั้งค่ารอบประเมิน
               ไม่มี "3 เดือน / 6 เดือน" เขียนตายไว้ที่ไหนในเส้นทางนี้ */
            'rounds' => $due->map(fn (FollowUpRound $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'dueDate' => $r->due_date?->toDateString(),
                'state' => $r->state(),
            ])->values(),
            'message' => $due->isEmpty()
                ? 'ตอนนี้ยังไม่มีรอบที่ถึงกำหนดของคุณ'
                : 'พบ '.$due->count().' รอบที่ถึงกำหนด',
        ]);
    }

    /**
     * บันทึกว่าตอบรอบนี้แล้ว
     *
     * ตัวหน้าจอกรอกคำตอบยังไม่ได้ทำในเฟสนี้ — เส้นนี้มีไว้ให้การ sync
     * (ตอบแล้ว → รอบติดตามรายคนเป็น "ตอบแล้ว" → สมาชิกในรอบเป็น "ตอบแล้ว")
     * เป็นเส้นทางจริงที่เรียกได้และทดสอบได้ ไม่ใช่ตรรกะที่ไม่มีใครเรียก
     */
    public function submit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'phone' => ['required', 'string', 'max:30'],
            'person_code' => ['required', 'string', 'max:30'],
            'round_id' => ['required', 'integer', 'exists:ptp_follow_up_rounds,id'],
        ]);

        $verified = $this->verify($request);

        if ($verified->getStatusCode() !== 200) {
            return $verified;
        }

        $participant = Participant::where('person_code', trim($data['person_code']))->firstOrFail();

        /* ต้องเป็นรอบของคนที่เพิ่งยืนยันตัวตน และต้องเป็นรอบที่เปิดให้ตอบอยู่จริง
           ไม่งั้นคนหนึ่งยืนยันตัวเองแล้วส่ง round_id ของคนอื่นเข้ามาปิดรอบให้เขาได้ */
        $round = $this->rounds->dueRoundsFor($participant)->firstWhere('id', $data['round_id']);

        if ($round === null) {
            return response()->json(['message' => 'รอบนี้ไม่ได้เปิดให้ตอบอยู่'], 422);
        }

        $this->rounds->recordResponse($round);

        return response()->json([
            'success' => true,
            'message' => 'บันทึกคำตอบแล้ว ขอบคุณครับ',
            'round' => ['id' => $round->id, 'name' => $round->name],
        ]);
    }

    private function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value);
    }
}
