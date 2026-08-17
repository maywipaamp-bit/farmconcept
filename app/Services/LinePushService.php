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
        return $this->push($lineUserId, [['type' => 'text', 'text' => $message]]);
    }

    /**
     * ส่งการ์ดชวนทำแบบประเมิน — หัวเรื่อง เนื้อความของแอดมิน รอบ/วันครบกำหนด และปุ่มกดใหญ่
     *
     * เป็น Flex Message เพราะข้อความ text ล้วนทำปุ่มไม่ได้ และ buttons template
     * จำกัดเนื้อความ 160 ตัวอักษรซึ่งข้อความไทยของโครงการยาวเกินแทบทุกครั้ง
     * โครงเลียนแบบการ์ดสำรวจของ LINE เอง: ภาพจำ (อีโมจิ) → หัวเรื่อง → เนื้อความ →
     * ข้อมูลรอบแบบโครงสร้าง → คำชี้แจงสั้น → ปุ่มเดียวเต็มความกว้าง
     * สีอ้างชุดแบรนด์ของระบบ (AGENTS.md): เขียวหลัก #81C060 · เขียวเข้ม #2F6D45
     */
    public function pushSurveyInvite(string $lineUserId, string $message, string $roundName, string $dueDate, string $url): bool
    {
        return $this->push($lineUserId, [[
            'type' => 'flex',
            /* altText คือบรรทัดที่เด้งบนแถบแจ้งเตือนของมือถือ — LINE จำกัด 400 ตัวอักษร */
            'altText' => mb_substr($message, 0, 400),
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'paddingAll' => '20px',
                    'contents' => [
                        ['type' => 'text', 'text' => '💚', 'size' => 'xxl', 'align' => 'center'],
                        ['type' => 'text', 'text' => 'แบบประเมินสุขภาวะ', 'weight' => 'bold', 'size' => 'lg',
                            'align' => 'center', 'color' => '#2F6D45', 'margin' => 'md'],
                        ['type' => 'text', 'text' => $message, 'wrap' => true, 'size' => 'sm',
                            'color' => '#6B7280', 'align' => 'center', 'margin' => 'lg'],
                        ['type' => 'separator', 'margin' => 'xl'],
                        ['type' => 'text', 'text' => 'รอบติดตาม '.$roundName, 'size' => 'xs',
                            'color' => '#9CA3AF', 'align' => 'center', 'margin' => 'xl'],
                        ['type' => 'text', 'text' => 'ตอบได้ถึงวันที่ '.$dueDate, 'weight' => 'bold', 'size' => 'md',
                            'color' => '#2F6D45', 'align' => 'center', 'margin' => 'sm'],
                    ],
                ],
                'footer' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'paddingAll' => '12px',
                    'contents' => [[
                        'type' => 'button',
                        'style' => 'primary',
                        'color' => '#81C060',
                        'height' => 'md',
                        'action' => ['type' => 'uri', 'label' => 'เริ่มทำแบบประเมิน', 'uri' => $url],
                    ]],
                ],
            ],
        ]]);
    }

    /** @param  array<int, array<string, mixed>>  $messages */
    private function push(string $lineUserId, array $messages): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $response = Http::withToken(config('services.line.messaging_token'))
            ->asJson()
            ->post(self::PUSH_URL, [
                'to' => $lineUserId,
                'messages' => $messages,
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
