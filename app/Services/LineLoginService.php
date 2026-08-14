<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
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

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.line.channel_id'),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => 'openid profile',
            'nonce' => $nonce,
        ]);
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
            throw new RuntimeException('แลกข้อมูลบัญชี LINE ไม่สำเร็จ');
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
            throw new RuntimeException('ตรวจสอบข้อมูลบัญชี LINE ไม่สำเร็จ');
        }

        return [
            'userId' => (string) $verified->json('sub'),
            'displayName' => (string) ($verified->json('name') ?: 'ผู้ใช้ LINE'),
            'pictureUrl' => $verified->json('picture') ?: null,
            'email' => $verified->json('email') ?: null,
        ];
    }
}
