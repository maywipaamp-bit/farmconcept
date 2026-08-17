<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * รวม id ปลายทางที่ webhook เคยเห็น เพื่อเอาไปตั้ง LINE_ADMIN_NOTIFY_TO
 *
 * ทำไมต้องมี: LINE Developers Console แสดง user id ให้แค่เจ้าของ channel คนเดียว
 * คนอื่นในทีมต้องให้บอทบอก id ของตัวเองผ่าน webhook แล้วส่งต่อมาให้ผู้ดูแล
 * ซึ่งคนส่วนใหญ่คัดลอกข้อความยาว ๆ จากมือถือแล้วส่งต่อผิดบ่อย
 *
 * คำสั่งนี้อ่านจาก log ที่ LineWebhookController เขียนไว้ แล้วรวมให้พร้อมวางใน .env เลย
 * ไม่ได้เก็บลงฐานข้อมูล — เป็นแค่ขั้นตอนตอนตั้งค่าครั้งแรก ไม่ใช่ข้อมูลที่ระบบต้องใช้ต่อ
 */
class LineIdsCommand extends Command
{
    protected $signature = 'line:ids {--reset : ล้าง log แล้วเริ่มเก็บใหม่}';

    protected $description = 'แสดง LINE id ที่ webhook เคยเห็น สำหรับตั้งค่า LINE_ADMIN_NOTIFY_TO';

    public function handle(): int
    {
        $path = storage_path('logs/laravel.log');

        if ($this->option('reset')) {
            if (File::exists($path)) {
                File::put($path, '');
            }

            $this->info('ล้าง log แล้ว — ให้แต่ละคนทักบอทใหม่อีกครั้ง');

            return self::SUCCESS;
        }

        if (! File::exists($path)) {
            $this->warn('ยังไม่มีไฟล์ log — แปลว่า webhook ยังไม่เคยถูกเรียกเลย');
            $this->hint();

            return self::SUCCESS;
        }

        /* บรรทัดที่ LineWebhookController เขียนไว้หน้าตาเป็น JSON ต่อท้ายข้อความ
           อ่านด้วย regex เพราะรูปแบบ log ของ Laravel ไม่ได้เป็น JSON ทั้งบรรทัด */
        preg_match_all(
            '/LINE webhook source id.*?"kind":"([^"]+)".*?"id":"([^"]+)"/u',
            File::get($path),
            $matches,
            PREG_SET_ORDER,
        );

        $found = collect($matches)
            ->map(fn (array $m) => ['kind' => $m[1], 'id' => $m[2]])
            ->unique('id')
            ->values();

        if ($found->isEmpty()) {
            $this->warn('ยังไม่พบ id ใน log');
            $this->hint();

            return self::SUCCESS;
        }

        $this->line('');
        $this->table(['ชนิด', 'id'], $found->map(fn (array $r) => [$r['kind'], $r['id']])->all());

        $this->line('คัดลอกบรรทัดนี้ไปใส่ใน .env ได้เลย:');
        $this->line('');
        $this->info('LINE_ADMIN_NOTIFY_TO='.$found->pluck('id')->implode(','));
        $this->line('');
        $this->line('เอาเฉพาะคนที่ต้องการก็ได้ — ตัดตัวที่ไม่ต้องการออก แล้วเหลือจุลภาคคั่นระหว่างที่เหลือ');

        return self::SUCCESS;
    }

    private function hint(): void
    {
        $this->line('');
        $this->line('ขั้นตอน:');
        $this->line(' 1) ใส่ LINE_MESSAGING_CHANNEL_SECRET ใน .env (Channel secret ของ channel Messaging API)');
        $this->line(' 2) Console → แท็บ Messaging API → Webhook URL = '.rtrim((string) config('app.url'), '/').'/line/webhook');
        $this->line('    แล้วเปิดสวิตช์ Use webhook');
        $this->line(' 3) ที่ OA Manager → ตั้งค่าการตอบกลับ → "แชท" ต้องปิด ไม่งั้น webhook จะไม่ทำงาน');
        $this->line(' 4) ให้แต่ละคนสแกน QR เพิ่มบอทเป็นเพื่อน — บอทจะตอบ id ของคนนั้นกลับไปทันที');
        $this->line(' 5) กลับมารันคำสั่งนี้อีกครั้ง');
    }
}
