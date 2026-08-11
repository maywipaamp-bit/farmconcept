<?php

namespace App\Http\Requests;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** จำนวนครั้งที่กรอกผิดได้ก่อนถูกหน่วงเวลา — ต้นแบบเขียนไว้ 5 ครั้ง */
    private const MAX_ATTEMPTS = 5;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:60'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'กรุณากรอกชื่อผู้ใช้งาน',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
        ];
    }

    /**
     * ยืนยันตัวตน
     *
     * ข้อความผิดพลาดเหมือนกันทุกกรณี ทั้งชื่อผู้ใช้ไม่มีอยู่จริง รหัสผ่านผิด และบัญชีถูกระงับ
     * เพื่อไม่ให้ผู้ไม่หวังดีไล่เดาได้ว่าบัญชีไหนมีอยู่ในระบบ
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginInput = $this->string('username')->trim()->value();
        $loginField = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $loginField => $loginInput,
            'password' => $this->string('password')->value(),
            'status' => 'ใช้งานอยู่',
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => 'กรอกผิดครบ ' . self::MAX_ATTEMPTS . ' ครั้ง กรุณารออีก ' . ceil($seconds / 60) . ' นาทีแล้วลองใหม่',
        ]);
    }

    /** นับแยกตามชื่อผู้ใช้ + IP เพื่อไม่ให้คนหนึ่งล็อกบัญชีของคนอื่นได้ด้วยการกรอกผิดรัว ๆ */
    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('username')->value()) . '|' . $this->ip());
    }
}
