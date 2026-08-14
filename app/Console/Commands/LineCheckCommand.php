<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * ตรวจการตั้งค่า LINE Login ก่อนไปลองกดจริงบนเบราว์เซอร์
 *
 * มีไว้เพราะหน้า authorize ของ LINE ตอบ "400 Bad Request" เหมือนกันหมดทุกสาเหตุ
 * และเนื้อหาข้อผิดพลาดถูกเรนเดอร์ด้วย JavaScript จึงอ่านจากฝั่งเซิร์ฟเวอร์ไม่ได้เลย
 * ทำให้แยกไม่ออกว่าเป็นเพราะ channel ผิดประเภท, secret ผิด หรือ Callback URL ไม่ตรง
 */
class LineCheckCommand extends Command
{
    protected $signature = 'line:check';

    protected $description = 'ตรวจว่าค่า LINE Login ใน .env ใช้งานได้จริงหรือไม่';

    public function handle(): int
    {
        $id = (string) config('services.line.channel_id');
        $secret = (string) config('services.line.channel_secret');

        $this->line('');
        $this->line('ตรวจการตั้งค่า LINE Login');
        $this->line(str_repeat('─', 46));

        if ($id === '' || $secret === '') {
            $this->error('✗ ยังไม่ได้ตั้ง LINE_LOGIN_CHANNEL_ID หรือ LINE_LOGIN_CHANNEL_SECRET ใน .env');

            return self::FAILURE;
        }

        $this->line('Channel ID       : '.$id.' ('.strlen($id).' หลัก)');
        $this->line('Channel secret   : ตั้งค่าแล้ว ('.strlen($secret).' ตัวอักษร)');

        $callback = rtrim((string) config('app.url'), '/').'/auth/line/callback';
        $this->line('Callback URL     : '.$callback);
        $this->line('  ↑ ต้องลงทะเบียนค่านี้แบบตรงตัวอักษรใน Console แท็บ LINE Login');
        $this->line('');

        $token = Http::asForm()
            ->timeout(10)
            ->post('https://api.line.me/v2/oauth/accessToken', [
                'grant_type' => 'client_credentials',
                'client_id' => $id,
                'client_secret' => $secret,
            ]);

        if ($token->json('error') === 'invalid_client') {
            $this->error('✗ Channel ID กับ Channel secret ไม่เข้าคู่กัน');
            $this->warn('  คัดลอกทั้งสองค่าจาก channel เดียวกัน แท็บ Basic settings');

            return self::FAILURE;
        }

        if ($token->failed()) {
            $this->warn('△ ตรวจไม่ได้ชัดเจน — LINE ตอบ: '.$token->status().' '.(string) $token->json('error_description'));

            return self::SUCCESS;
        }

        /* ตัวชี้ขาดว่าเป็น channel ชนิดไหน คือ "มีบอทผูกอยู่หรือไม่" ไม่ใช่ "ขอ token ได้หรือไม่"
           เพราะทั้งสองชนิดขอ channel access token ได้เหมือนกัน แต่มีเฉพาะ Messaging API
           ที่เรียก /v2/bot/info ได้สำเร็จ — Login channel จะโดนปฏิเสธด้วย 403 */
        $bot = Http::withToken((string) $token->json('access_token'))
            ->timeout(10)
            ->get('https://api.line.me/v2/bot/info');

        if ($bot->successful()) {
            $this->error('✗ ค่านี้เป็นของ Messaging API channel ไม่ใช่ LINE Login channel');
            $this->line('  ชื่อ channel ที่เจอ: '.$bot->json('displayName').' ('.$bot->json('basicId').')');
            $this->line('');
            $this->warn('  วิธีแก้: เปิด LINE Developers Console แล้วเลือก Provider เดิม');
            $this->warn('  สร้างหรือเปิด channel ที่เป็นชนิด "LINE Login" (คนละตัวกับ Messaging API)');
            $this->warn('  แล้วคัดลอก Channel ID / Channel secret ของตัวนั้นมาใส่แทน');

            return self::FAILURE;
        }

        $this->info('✓ เป็น LINE Login channel และ ID/secret เข้าคู่กันถูกต้อง');
        $this->line('');
        $this->line('  ตรวจอัตโนมัติได้แค่นี้ เพราะ LINE ไม่มี API ให้ยืนยัน Callback URL');
        $this->line('  ถ้ากดแล้วยังขึ้น 400 ให้ดูสองข้อนี้ใน Console แท็บ LINE Login:');
        $this->line('   1) Callback URL ตรงกับด้านบนเป๊ะ ๆ (ห้ามมี / ปิดท้าย)');
        $this->line('   2) channel เปิดใช้ประเภทแอปแบบ "Web app" ไว้แล้ว');

        return self::SUCCESS;
    }
}
