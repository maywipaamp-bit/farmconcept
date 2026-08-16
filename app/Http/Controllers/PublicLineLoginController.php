<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Services\LineLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * เข้าสู่ระบบด้วย LINE สำหรับหน้าลงทะเบียนกิจกรรมของบุคคลทั่วไป
 *
 * ไม่ได้ล็อกอินเข้าระบบหลังบ้าน — เก็บโปรไฟล์ไว้ใน session ของผู้เข้าชมเท่านั้น
 * เพื่อเติมชื่อให้ฟอร์มและผูกบัญชี LINE กับการจอง ผู้ใช้ยังกรอกแก้ได้ทุกช่อง
 */
class PublicLineLoginController extends Controller
{
    /** คีย์ session ที่หน้าลงทะเบียนอ่านโปรไฟล์ LINE — ใช้ร่วมกับ PublicRegistrationController */
    public const SESSION_KEY = 'line_profile';

    public function __construct(private readonly LineLoginService $line)
    {
    }

    /**
     * Callback URL ที่ส่งให้ LINE — ยึดจาก APP_URL เสมอ ไม่ใช่ host ของคำขอ
     *
     * LINE เทียบ redirect_uri แบบตรงตัวอักษรกับที่ลงทะเบียนไว้ใน Console
     * ถ้าปล่อยให้สร้างจาก host ของคำขอ ค่าจะเปลี่ยนไปตามทางที่ผู้ใช้เข้ามา
     * (localhost:8000 · 127.0.0.1 · www. กับไม่มี www.) แล้วต้องไล่ลงทะเบียนให้ครบทุกแบบ
     * ตกหล่นแบบใดแบบหนึ่งจะกลายเป็น 400 invalid_request เฉพาะผู้ใช้บางกลุ่ม ซึ่งตามยาก
     *
     * ใช้ชื่อ route เป็นต้นทางของ path เพื่อไม่ให้ URL ไปเขียนซ้ำไว้สองที่
     */
    private function callbackUrl(): string
    {
        return rtrim((string) config('app.url'), '/').route('public.line.callback', absolute: false);
    }

    /** ส่งผู้ใช้ไปหน้ายินยอมของ LINE — จำรหัสกิจกรรมไว้เพื่อพากลับมาที่เดิม */
    public function redirect(Request $request, string $activity): RedirectResponse
    {
        $target = route('public.activities.register', $activity);

        if (! $this->line->isConfigured()) {
            return redirect()->away($target);
        }

        $authorize = $this->line->authorizeRequest($this->callbackUrl());

        $request->session()->put('line_oauth', [
            'state' => $authorize['state'],
            'nonce' => $authorize['nonce'],
            'activity' => $activity,
        ]);

        return redirect()->away($authorize['url']);
    }

    /**
     * เข้าด้วย LINE จากหน้า QR ติดตามสุขภาพ
     *
     * ใช้ callback เส้นเดิมร่วมกัน เพราะ LINE ให้ลงทะเบียน Callback URL ไว้ล่วงหน้าได้ชุดเดียว
     * แยกปลายทางด้วย intent ที่ฝากไว้ใน session ตั้งแต่ตอนกดปุ่ม ไม่ใช่ทาง URL
     */
    public function redirectHealth(Request $request): RedirectResponse
    {
        $target = route('public.tracking-round-qr');

        if (! $this->line->isConfigured()) {
            return redirect()->away($target);
        }

        $authorize = $this->line->authorizeRequest($this->callbackUrl());

        $request->session()->put('line_oauth', [
            'state' => $authorize['state'],
            'nonce' => $authorize['nonce'],
            'intent' => 'health',
        ]);

        return redirect()->away($authorize['url']);
    }

    /**
     * ปลายทางที่ LINE ส่งกลับ — ต้องตรงกับ Callback URL ที่ตั้งไว้ใน LINE Developers Console
     *
     * เส้นทางนี้ไม่รับรหัสกิจกรรมทาง URL เพราะ LINE ให้ลงทะเบียน callback ไว้ล่วงหน้าเป็นค่าตายตัว
     * รหัสกิจกรรมจึงถูกฝากไว้ใน session ตั้งแต่ตอนกดปุ่ม
     */
    public function callback(Request $request): RedirectResponse
    {
        $oauth = $request->session()->pull('line_oauth');
        $isHealth = ($oauth['intent'] ?? null) === 'health';
        $activity = $oauth['activity'] ?? null;

        $fallback = match (true) {
            $isHealth => route('public.tracking-round-qr'),
            (bool) $activity => route('public.activities.register', $activity),
            default => route('public.activities'),
        };

        /* ผู้ใช้กดยกเลิกที่หน้า LINE — กลับมาหน้าเดิมเงียบ ๆ ไม่ต้องขึ้น error ให้ตกใจ */
        if ($request->query('error')) {
            return redirect()->away($fallback);
        }

        $code = (string) $request->query('code');
        $state = (string) $request->query('state');

        if ((! $activity && ! $isHealth) || ! $code || ! $state
            || ! hash_equals((string) ($oauth['state'] ?? ''), $state)) {
            return redirect()->away($fallback)->with('lineError', 'การเชื่อมต่อ LINE หมดอายุ กรุณาลองใหม่อีกครั้ง');
        }

        /* กิจกรรมต้องยังเปิดอยู่จริง ไม่งั้นพากลับไปหน้าที่ redirect ต่อเองอยู่แล้ว */
        if (! $isHealth && ! Activity::forPublicListing()->where('code', $activity)->exists()) {
            return redirect()->away(route('public.activities'));
        }

        try {
            /* ต้องเป็นค่าเดียวกับตอนขอ code เป๊ะ ๆ ไม่งั้น LINE ปฏิเสธการแลก token */
            $profile = $this->line->profileFromCode($code, $this->callbackUrl(), (string) $oauth['nonce']);
        } catch (RuntimeException $e) {
            return redirect()->away($fallback)->with('lineError', $e->getMessage());
        }

        $request->session()->put(self::SESSION_KEY, $profile);

        /* ฝั่งติดตามสุขภาพ: บัญชี LINE ที่เคยผูกไว้แล้วคือการยืนยันตัวตนในตัว
           เข้าได้เลยโดยไม่ต้องกรอกเบอร์ ส่วนคนที่ยังไม่เคยผูกจะถูกพาไปกรอกเบอร์หรือลงทะเบียนต่อ */
        if ($isHealth) {
            return redirect()->away(route('public.tracking-round-qr.line-return'));
        }

        return redirect()->away($fallback);
    }

    /** ออกจากบัญชี LINE เฉพาะฝั่งเว็บนี้ — ไม่แตะ session ของแอป LINE */
    public function logout(Request $request, string $activity): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->away(route('public.activities.register', $activity));
    }
}
