<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * LINE Login v2.1 (OpenID Connect) — เฉพาะส่วนที่หน้าลงทะเบียนสาธารณะใช้
 *
 * ใช้ id_token เป็นแหล่งข้อมูลผู้ใช้ ไม่เรียก /v2/profile เพิ่ม เพราะ id_token
 * มีทั้ง userId ชื่อ และรูปอยู่แล้ว และผ่านการตรวจลายเซ็นจาก LINE มาแล้วหนึ่งชั้น
 *
 * การตรวจ id_token ส่งไปให้ endpoint `/oauth2/v2.1/verify` ของ LINE ตรวจให้
 * แทนการถอด JWT เอง — ไม่ต้องดูแลการตรวจลายเซ็นและวันหมดอายุเองในโปรเจกต์นี้
 */
class LineLoginService
{
    private const AUTHORIZE_URL = 'https://access.line.me/oauth2/v2.1/authorize';

    private const TOKEN_URL = 'https://api.line.me/oauth2/v2.1/token';

    private const VERIFY_URL = 'https://api.line.me/oauth2/v2.1/verify';

    /**
     * บันทึกสาเหตุที่ LINE ปฏิเสธ เพื่อให้ตามปัญหาได้โดยไม่ต้องเดา
     *
     * เก็บเฉพาะรหัสและคำอธิบายที่ LINE ส่งกลับ ไม่บันทึก channel secret, code หรือ id_token
     * เพราะสามอย่างนั้นใช้สวมสิทธิ์ผู้ใช้ได้ถ้าไฟล์ log หลุด
     *
     * @param  array<string, mixed>|null  $body
     */
    private function logFailure(string $step, int $status, ?array $body): void
    {
        Log::warning('LINE Login: '.$step, [
            'status' => $status,
            'error' => $body['error'] ?? null,
            'error_description' => $body['error_description'] ?? null,
        ]);
    }

    /** ยังไม่ได้ตั้ง channel = ไม่ต้องแสดงปุ่ม LINE ให้ผู้ใช้กดแล้วเจอหน้า error */
    public function isConfigured(): bool
    {
        return filled(config('services.line.channel_id'))
            && filled(config('services.line.channel_secret'));
    }

    /** @return array{url: string, state: string, nonce: string} */
    public function authorizeRequest(string $redirectUri): array
    {
        $state = Str::random(40);
        $nonce = Str::random(40);

        /* ต้องเป็น PHP_QUERY_RFC3986 — ค่าเริ่มต้นของ http_build_query คือ RFC1738
           ซึ่งเข้ารหัสช่องว่างเป็น "+" ทำให้ LINE อ่าน scope เป็นชื่อเดียวว่า "openid+profile"
           แล้วตอบ 400 Bad Request กลับมา ส่วน RFC3986 เข้ารหัสเป็น %20 ตามที่ LINE กำหนด */
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.line.channel_id'),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => 'openid profile',
            'nonce' => $nonce,
        ], '', '&', PHP_QUERY_RFC3986);
        /* ไม่ส่ง prompt=consent — จะบังคับให้เห็นหน้ายินยอมทุกครั้งแม้เคยกดแล้ว
           ปล่อยตามค่าเริ่มต้นของ LINE คนที่เคยยินยอมแล้วจะเด้งกลับมาทันที */

        return [
            'url' => self::AUTHORIZE_URL.'?'.$query,
            'state' => $state,
            'nonce' => $nonce,
        ];
    }

    /**
     * แลก authorization code เป็นข้อมูลผู้ใช้
     *
     * @return array{userId: string, displayName: string, pictureUrl: ?string, email: ?string}
     */
    public function profileFromCode(string $code, string $redirectUri, string $nonce): array
    {
        $token = Http::asForm()
            ->timeout(10)
            ->post(self::TOKEN_URL, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'client_id' => config('services.line.channel_id'),
                'client_secret' => config('services.line.channel_secret'),
            ]);

        if ($token->failed() || ! $token->json('id_token')) {
            /* LINE บอกสาเหตุจริงมาใน error_description (เช่น redirect_uri ไม่ตรงกับที่ลงทะเบียน
               หรือ channel secret ผิด) แต่ข้อความนั้นเป็นภาษาอังกฤษเชิงเทคนิคและอาจมีข้อมูลภายใน
               จึงบันทึกลง log ให้ผู้ดูแลตามต่อได้ ส่วนผู้ใช้เห็นเฉพาะข้อความกลาง ๆ */
            $this->logFailure('แลก token ไม่สำเร็จ', $token->status(), $token->json());

            throw new RuntimeException('เชื่อมต่อบัญชี LINE ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        /* ตรวจว่า id_token ออกให้ channel นี้จริง และ nonce ตรงกับที่ส่งไปตอนเริ่ม
           nonce ที่ไม่ตรงแปลว่าคำตอบนี้ไม่ใช่ของคำขอที่เราเป็นคนเริ่ม จึงต้องปฏิเสธ */
        $verified = Http::asForm()
            ->timeout(10)
            ->post(self::VERIFY_URL, [
                'id_token' => $token->json('id_token'),
                'client_id' => config('services.line.channel_id'),
                'nonce' => $nonce,
            ]);

        if ($verified->failed() || ! $verified->json('sub')) {
            $this->logFailure('ตรวจ id_token ไม่ผ่าน', $verified->status(), $verified->json());

            throw new RuntimeException('ตรวจสอบข้อมูลบัญชี LINE ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        return [
            'userId' => (string) $verified->json('sub'),
            'displayName' => (string) ($verified->json('name') ?: 'ผู้ใช้ LINE'),
            'pictureUrl' => $verified->json('picture') ?: null,
            'email' => $verified->json('email') ?: null,
        ];
    }
}
