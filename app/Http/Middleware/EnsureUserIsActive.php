<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ระงับสิทธิ์แล้วต้องออกจากระบบทันที ไม่ใช่รอให้ผู้ใช้กดออกเอง
 *
 * หน้าเข้าสู่ระบบกันคนที่ถูกระงับไม่ให้ล็อกอินใหม่อยู่แล้ว แต่ถ้าคนนั้นเปิดหน้าจอค้างไว้
 * session เดิมยังใช้งานได้ต่อจนกว่าจะหมดอายุ — ระงับสิทธิ์จึงไม่มีผลจริงในช่วงนั้น
 *
 * ตรวจทุกคำขอเพราะสถานะเปลี่ยนได้ตลอดเวลาโดยที่เจ้าตัวไม่รู้
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->status !== 'ระงับการใช้งาน') {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ';

        /* คำขอจากหน้าจอที่ยิงด้วย fetch ต้องได้ JSON กลับไป ไม่ใช่ HTML ของหน้าล็อกอิน
           ไม่งั้นหน้าจอจะขึ้น error ที่อ่านไม่รู้เรื่องแทนเหตุผลจริง */
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 401);
        }

        return redirect()->route('login')->withErrors(['username' => $message]);
    }
}
