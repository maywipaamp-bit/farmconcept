<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ส่งข้อความหาผู้ใช้ผ่าน LINE Messaging API
 *
 * คนละ channel กับ LINE Login (LineLoginService) — Login ใช้ยืนยันตัวตน
 * ส่วนการ "ส่งข้อความหา" ต้องมี Messaging API channel และ channel access token ของตัวเอง
 * โปรเจกต์นี้ยังไม่ได้ตั้งค่าตัวหลัง ถ้าไม่มี token จะรายงานกลับว่าส่งไม่ได้
 * ไม่ใช่ทำเป็นว่าส่งสำเร็จ — ไม่งั้นแอดมินจะเชื่อว่าคนได้รับแจ้งเตือนแล้วทั้งที่ไม่ได้รับ
 */
class LinePushService
{
    private const PUSH_URL = 'https://api.line.me/v2/bot/message/push';

    public function isConfigured(): bool
    {
        return filled(config('services.line.messaging_token'));
    }

    /**
     * ส่งข้อความหาผู้ใช้หนึ่งคน
     *
     * คืน false เมื่อส่งไม่สำเร็จ ผู้เรียกเป็นคนตัดสินว่าจะบันทึกผลอย่างไร
     */
    public function pushText(string $lineUserId, string $message): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $response = Http::withToken(config('services.line.messaging_token'))
            ->asJson()
            ->post(self::PUSH_URL, [
                'to' => $lineUserId,
                'messages' => [['type' => 'text', 'text' => $message]],
            ]);

        if ($response->failed()) {
            /* ไม่บันทึก token และไม่บันทึกตัวข้อความ — ข้อความมีชื่อผู้รับอยู่ข้างใน */
            Log::warning('LINE push failed', [
                'status' => $response->status(),
                'message' => $response->json('message'),
            ]);

            return false;
        }

        return true;
    }
}
