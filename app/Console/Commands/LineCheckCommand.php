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
    protected $signature = 'line:check {--notify-test : ส่งข้อความทดสอบไปยังปลายทางแจ้งเตือนทีมงานจริง}';

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

        return $this->checkMessaging();
    }

    /**
     * ฝั่งส่งแจ้งเตือน (Messaging API) — คนละ channel กับ Login
     *
     * token ตรวจกับ /v2/bot/info ได้ตรง ๆ เพราะมีเฉพาะ Messaging API channel ที่เรียกสำเร็จ
     * ถ้าไม่ตั้ง ระบบรอบติดตามจะบันทึกทุกคนเป็น "ส่งไม่สำเร็จ" — ต้องเห็นจากคำสั่งนี้ก่อนไปงมในหน้าจอ
     */
    private function checkMessaging(): int
    {
        $token = (string) config('services.line.messaging_token');

        $this->line('');
        $this->line('ตรวจการตั้งค่า LINE Messaging API (แจ้งเตือนรอบติดตาม)');
        $this->line(str_repeat('─', 46));

        if ($token === '') {
            $this->warn('△ ยังไม่ได้ตั้ง LINE_MESSAGING_CHANNEL_ACCESS_TOKEN ใน .env');
            $this->line('  ระบบจะบันทึกการแจ้งเตือน LINE ทุกคนเป็น "ส่งไม่สำเร็จ"');
            $this->line('  วิธีตั้ง: LINE Developers Console → channel ชนิด Messaging API');
            $this->line('  → แท็บ Messaging API → Issue channel access token (long-lived) แล้วคัดลอกมาใส่');

            return self::SUCCESS;
        }

        $bot = Http::withToken($token)->timeout(10)->get('https://api.line.me/v2/bot/info');

        if ($bot->failed()) {
            $this->error('✗ token ใช้ไม่ได้ — LINE ตอบ: '.$bot->status().' '.(string) $bot->json('message'));
            $this->warn('  ออก token ใหม่จากแท็บ Messaging API ของ channel แล้วใส่แทนของเดิม');

            return self::FAILURE;
        }

        $this->info('✓ Messaging API พร้อมส่ง — บอท: '.$bot->json('displayName').' ('.$bot->json('basicId').')');
        $this->line('  ผู้รับต้องเป็นเพื่อนกับบอทนี้ด้วย ไม่งั้น LINE จะปฏิเสธการส่งรายคน');

        return $this->checkAdminTarget();
    }

    /**
     * ปลายทางแจ้งเตือนทีมงาน (เช่น มีผู้ลงทะเบียนใหม่)
     *
     * ไม่มี API ให้ตรวจว่า group id ถูกต้องหรือไม่โดยไม่ส่งข้อความจริง
     * จึงตรวจได้แค่ว่าตั้งค่าไว้แล้วและรูปแบบเข้าเค้า — ที่เหลือต้องลองส่งด้วย --notify-test
     */
    private function checkAdminTarget(): int
    {
        $target = (string) config('services.line.admin_notify_to');

        $this->line('');
        $this->line('ตรวจปลายทางแจ้งเตือนทีมงาน (ผู้ลงทะเบียนใหม่)');
        $this->line(str_repeat('─', 46));

        if ($target === '') {
            $this->warn('△ ยังไม่ได้ตั้ง LINE_ADMIN_NOTIFY_TO ใน .env — ระบบจะไม่ส่งแจ้งเตือนให้ทีมงาน');
            $this->line('  ใส่ group id ของกลุ่ม LINE ทีมงานที่เชิญบอทนี้เข้าไปแล้ว (หรือ user id ของคนเดียว)');
            $this->line('  group id อยู่ใน webhook event ที่ LINE ส่งมาตอนมีข้อความในกลุ่ม (source.groupId)');

            return self::SUCCESS;
        }

        /* C = กลุ่ม · R = ห้องแชต · U = ผู้ใช้รายคน — LINE ใช้ตัวอักษรแรกแยกชนิดปลายทาง */
        $kind = match (substr($target, 0, 1)) {
            'C' => 'กลุ่ม',
            'R' => 'ห้องแชต',
            'U' => 'ผู้ใช้รายคน',
            default => null,
        };

        if ($kind === null) {
            $this->error('✗ ค่า LINE_ADMIN_NOTIFY_TO ไม่เข้าเค้า id ของ LINE (ต้องขึ้นต้นด้วย C, R หรือ U)');

            return self::FAILURE;
        }

        $this->info('✓ ตั้งปลายทางแล้ว — ชนิด: '.$kind);
        $this->line('  ลองส่งจริงได้ด้วย: php artisan line:check --notify-test');

        if (! $this->option('notify-test')) {
            return self::SUCCESS;
        }

        $sent = app(\App\Services\LinePushService::class)->pushAdminAlert(
            'ทดสอบการแจ้งเตือน',
            'ข้อความนี้ส่งจากคำสั่ง line:check เพื่อตรวจว่าปลายทางถูกต้อง',
            [['label' => 'สถานะ', 'value' => 'ถ้าเห็นข้อความนี้แปลว่าตั้งค่าถูกแล้ว']],
            rtrim((string) config('app.url'), '/').'/admin/dashboard',
            'เปิดระบบหลังบ้าน',
        );

        $sent
            ? $this->info('✓ ส่งข้อความทดสอบแล้ว — ไปดูในกลุ่ม/แชตปลายทาง')
            : $this->error('✗ ส่งไม่สำเร็จ — ดูรายละเอียดใน storage/logs/laravel.log');

        return $sent ? self::SUCCESS : self::FAILURE;
    }
}
