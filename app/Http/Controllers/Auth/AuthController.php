<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        /* สร้าง session id ใหม่หลังล็อกอินสำเร็จ กัน session fixation */
        $request->session()->regenerate();

        $request->user()->forceFill(['last_login_at' => now()])->save();

        /* intended() พากลับไปหน้าที่ตั้งใจเปิดก่อนถูกเด้งมาล็อกอิน
           ผู้ใช้จึงไม่ต้องไล่คลิกเมนูกลับไปเองหลังล็อกอิน

           ปลายทางเริ่มต้นเป็นแดชบอร์ดตามที่ handoff ของหน้าเข้าสู่ระบบระบุไว้
           (เดิมเป็นรายการกิจกรรม เพราะตอนนั้นแดชบอร์ดยังเป็นหน้า static ที่ยังไม่ได้ย้าย) */
        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
