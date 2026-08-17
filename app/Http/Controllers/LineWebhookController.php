<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * รับ webhook จาก LINE เพื่อ "หา id ปลายทาง" สำหรับตั้งค่า LINE_ADMIN_NOTIFY_TO
 *
 * ทำไมต้องมี: LINE Developers Console แสดงให้แค่ user id ของเจ้าของ channel เท่านั้น
 * ส่วน group id ของกลุ่มทีมงานไม่มีที่ไหนให้ดู — ได้จาก webhook event ทางเดียว
 *
 * วิธีใช้: ตั้ง Webhook URL ใน Console เป็น {APP_URL}/line/webhook แล้วเปิด Use webhook
 * จากนั้นพิมพ์อะไรก็ได้ในกลุ่มที่เชิญบอทเข้าไป บอทจะตอบ id กลับมาในแชตเลย
 * (และบันทึกไว้ใน log ด้วย เผื่อข้อความในแชตหาย)
 *
 * ไม่เก็บอะไรลงฐานข้อมูล และไม่ทำอย่างอื่นนอกจากตอบ id กลับ
 */
class LineWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /* ตรวจลายเซ็นก่อนเสมอ — endpoint นี้เปิดสาธารณะและไม่มี CSRF
           ถ้าไม่ตรวจ ใครก็ยิงเข้ามาให้บอทตอบข้อความได้ */
        if (! $this->hasValidSignature($request)) {
            return response('invalid signature', 401);
        }

        foreach ($request->input('events', []) as $event) {
            $source = $event['source'] ?? [];
            $id = $source['groupId'] ?? $source['roomId'] ?? $source['userId'] ?? null;

            if (! $id) {
                continue;
            }

            $kind = match (true) {
                isset($source['groupId']) => 'กลุ่ม',
                isset($source['roomId']) => 'ห้องแชต',
                default => 'ผู้ใช้รายคน',
            };

            Log::info('LINE webhook source id', ['kind' => $kind, 'id' => $id]);

            /* ตอบกลับได้เฉพาะ event ที่มี replyToken — ครอบทั้ง "เพิ่มเพื่อน" (follow),
               "บอทถูกเชิญเข้ากลุ่ม" (join) และ "มีคนพิมพ์ข้อความ" (message)
               คนที่จะรับแจ้งเตือนจึงได้ id ของตัวเองทันทีที่กดเพิ่มเพื่อน ไม่ต้องพิมพ์อะไรเลย */
            if (! empty($event['replyToken'])) {
                $this->reply(
                    $event['replyToken'],
                    "รหัสสำหรับรับแจ้งเตือนของ{$kind}นี้คือ\n\n{$id}\n\n"
                    ."กดค้างที่ข้อความนี้เพื่อคัดลอก แล้วส่งให้ผู้ดูแลระบบนำไปตั้งค่า",
                );
            }
        }

        /* LINE ต้องได้ 200 เสมอ ไม่งั้นจะถือว่า endpoint เสียแล้วปิดการส่งให้เอง */
        return response('ok', 200);
    }

    private function hasValidSignature(Request $request): bool
    {
        $secret = (string) config('services.line.messaging_channel_secret');
        $signature = (string) $request->header('X-Line-Signature');

        if ($secret === '' || $signature === '') {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        return hash_equals($expected, $signature);
    }

    private function reply(string $replyToken, string $text): void
    {
        $token = (string) config('services.line.messaging_token');

        if ($token === '') {
            return;
        }

        Http::withToken($token)->asJson()->post('https://api.line.me/v2/bot/message/reply', [
            'replyToken' => $replyToken,
            'messages' => [['type' => 'text', 'text' => $text]],
        ]);
    }
}
