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

    /** ส่งผู้ใช้ไปหน้ายินยอมของ LINE — จำรหัสกิจกรรมไว้เพื่อพากลับมาที่เดิม */
    public function redirect(Request $request, string $activity): RedirectResponse
    {
        $target = route('public.activities.register', $activity);

        if (! $this->line->isConfigured()) {
            return redirect()->away($target);
        }

        $authorize = $this->line->authorizeRequest(route('public.line.callback'));

        $request->session()->put('line_oauth', [
            'state' => $authorize['state'],
            'nonce' => $authorize['nonce'],
            'activity' => $activity,
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
        $activity = $oauth['activity'] ?? null;
        $fallback = $activity
            ? route('public.activities.register', $activity)
            : route('public.activities');

        /* ผู้ใช้กดยกเลิกที่หน้า LINE — กลับมาหน้าเดิมเงียบ ๆ ไม่ต้องขึ้น error ให้ตกใจ */
        if ($request->query('error')) {
            return redirect()->away($fallback);
        }

        $code = (string) $request->query('code');
        $state = (string) $request->query('state');

        if (! $activity || ! $code || ! $state || ! hash_equals((string) ($oauth['state'] ?? ''), $state)) {
            return redirect()->away($fallback)->with('lineError', 'การเชื่อมต่อ LINE หมดอายุ กรุณาลองใหม่อีกครั้ง');
        }

        /* กิจกรรมต้องยังเปิดอยู่จริง ไม่งั้นพากลับไปหน้าที่ redirect ต่อเองอยู่แล้ว */
        if (! Activity::forPublicListing()->where('code', $activity)->exists()) {
            return redirect()->away(route('public.activities'));
        }

        try {
            $profile = $this->line->profileFromCode($code, route('public.line.callback'), (string) $oauth['nonce']);
        } catch (RuntimeException $e) {
            return redirect()->away($fallback)->with('lineError', $e->getMessage());
        }

        $request->session()->put(self::SESSION_KEY, $profile);

        return redirect()->away($fallback);
    }

    /** ออกจากบัญชี LINE เฉพาะฝั่งเว็บนี้ — ไม่แตะ session ของแอป LINE */
    public function logout(Request $request, string $activity): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->away(route('public.activities.register', $activity));
    }
}
