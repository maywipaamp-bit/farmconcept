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

    /**
     * ปลายทางแจ้งเตือนแอดมินทั้งหมด
     *
     * รับได้หลายค่าคั่นด้วยจุลภาค เพราะบาง OA ถูกปิดไม่ให้เข้ากลุ่มแชต
     * (ตั้งค่าใน LINE Official Account Manager) ทีมงานจึงต้องรับทีละคนแทน
     *
     * @return array<int, string>
     */
    public function adminTargets(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        return collect(explode(',', (string) config('services.line.admin_notify_to')))
            ->map(fn (string $id) => trim($id))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** ตั้งปลายทางแจ้งเตือนแอดมินไว้หรือยัง — ไม่ตั้ง = ไม่ส่ง ไม่ใช่ความผิดพลาด */
    public function hasAdminTarget(): bool
    {
        return $this->adminTargets() !== [];
    }

    /**
     * แจ้งเตือนทีมงานว่ามีคนลงทะเบียนเข้ามาใหม่
     *
     * ปลายทางเป็นกลุ่ม LINE ของทีม หรือรายคนหลายคนก็ได้ (คั่นด้วยจุลภาค)
     * ไม่เก็บ LINE id ของพนักงานไว้ในฐานข้อมูล เพราะทีมงานเปลี่ยนคนบ่อย
     * และการเพิ่ม/ลดคนควรทำที่ .env จุดเดียว ไม่ต้องมีหน้าจอจัดการเพิ่ม
     *
     * คืน true เมื่อส่งถึงอย่างน้อยหนึ่งปลายทาง — คนหนึ่งรับไม่ได้
     * ไม่ควรทำให้ถือว่าทีมทั้งทีมไม่ได้รับแจ้ง
     *
     * @param  array<int, array{label: string, value: string}>  $rows
     */
    public function pushAdminAlert(string $title, string $headline, array $rows, string $url, string $buttonLabel): bool
    {
        $targets = $this->adminTargets();

        if ($targets === []) {
            return false;
        }

        $detail = array_map(fn (array $row) => [
            'type' => 'box',
            'layout' => 'baseline',
            'spacing' => 'sm',
            'margin' => 'md',
            'contents' => [
                ['type' => 'text', 'text' => $row['label'], 'size' => 'sm', 'color' => '#9CA3AF', 'flex' => 2],
                ['type' => 'text', 'text' => $row['value'], 'size' => 'sm', 'color' => '#374151', 'flex' => 5, 'wrap' => true],
            ],
        ], $rows);

        $message = [[
            'type' => 'flex',
            /* altText คือบรรทัดที่เด้งบนแถบแจ้งเตือน — ต้องอ่านรู้เรื่องโดยไม่ต้องเปิดแอป */
            'altText' => mb_substr($title.' · '.$headline, 0, 400),
            'contents' => [
                'type' => 'bubble',
                'body' => [
                    'type' => 'box',
                    'layout' => 'vertical',
                    'paddingAll' => '20px',
                    /* ไม่แสดง $headline ซ้ำในการ์ด — เนื้อความเดียวกันอยู่ในตารางด้านล่างครบแล้ว
                       (ชื่อผู้ลงทะเบียน + ชื่อกิจกรรม) พูดซ้ำสองรอบทำให้การ์ดยาวโดยไม่ได้ข้อมูลเพิ่ม
                       ตัว $headline ยังใช้ใน altText ด้านบน ซึ่งเป็นบรรทัดเดียวที่เด้งบนแถบแจ้งเตือน
                       และต้องอ่านรู้เรื่องโดยไม่ต้องเปิดแอป */
                    'contents' => array_merge([
                        ['type' => 'text', 'text' => $title, 'weight' => 'bold', 'size' => 'md', 'color' => '#2F6D45'],
                        ['type' => 'separator', 'margin' => 'lg'],
                    ], $detail),
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
                        'action' => ['type' => 'uri', 'label' => $buttonLabel, 'uri' => $url],
                    ]],
                ],
            ],
        ]];

        $sent = false;

        foreach ($targets as $target) {
            /* ส่งให้ครบทุกปลายทางเสมอ ไม่หยุดเมื่อเจอตัวที่ส่งไม่ได้
               คนหนึ่งบล็อกบอทไว้ ไม่ควรทำให้คนที่เหลือไม่ได้รับแจ้ง */
            $sent = $this->push($target, $message) || $sent;
        }

        return $sent;
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
