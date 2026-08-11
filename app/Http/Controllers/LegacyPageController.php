<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * เสิร์ฟหน้า HTML เดิมที่ยังไม่ได้ย้ายเป็น Blade
 *
 * ไฟล์ถูกย้ายออกจาก public/ มาไว้ที่ resources/legacy/ เพื่อให้ผ่าน middleware ตรวจสิทธิ์ก่อนเสมอ
 * ถ้าปล่อยไว้ใน public/ เว็บเซิร์ฟเวอร์จะส่งไฟล์ให้ตรง ๆ โดยไม่ผ่าน Laravel เลย
 * แปลว่าใครก็เปิดหน้าหลังบ้านได้โดยไม่ต้องล็อกอิน
 *
 * URL ยังเหมือนเดิมทุกตัวอักษร path สัมพัทธ์ ../../assets/ ในไฟล์จึงยังชี้ถูก
 */
class LegacyPageController extends Controller
{
    private const ROOT = 'resources/legacy/admin';

    public function show(Request $request, string $path): Response
    {
        $file = $this->resolve($path);

        $html = $this->versionAssets($this->injectAuthContext(file_get_contents($file), $request));

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            /* หน้าหลังบ้านมีข้อมูลส่วนบุคคล ห้ามให้ proxy หรือเบราว์เซอร์เก็บไว้ */
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * ต่อ ?v=<เวลาที่แก้ไฟล์> ท้าย URL ของ CSS/JS ที่หน้าเดิมโหลดตรง ๆ
     *
     * ไฟล์เหล่านี้ชื่อคงที่และเสิร์ฟโดยไม่มี Cache-Control/ETag/Last-Modified เลย
     * เบราว์เซอร์จึงใช้ heuristic caching เก็บของเก่าไว้ แก้ CSS แล้วหน้าจอไม่เปลี่ยน
     * จนกว่าจะกด Ctrl+Shift+R — ซึ่งไม่ควรเป็นสิ่งที่ต้องจำ
     *
     * ใช้เวลาที่แก้ไฟล์เป็นเลขเวอร์ชัน แก้ไฟล์เมื่อไหร่ URL เปลี่ยนเอง
     * ไฟล์ที่ไม่มีอยู่จริงปล่อยผ่านไปตามเดิม ไม่ทำให้หน้าพัง
     */
    private function versionAssets(string $html): string
    {
        return preg_replace_callback(
            '#(href|src)="((?:\.\./)*assets/[^"?]+\.(?:css|js))"#',
            function (array $m): string {
                $relative = preg_replace('#^(\.\./)+#', '', $m[2]);
                $file = public_path($relative);

                if (! is_file($file)) {
                    return $m[0];
                }

                return $m[1] . '="' . $m[2] . '?v=' . filemtime($file) . '"';
            },
            $html
        );
    }

    /**
     * แปลง path จาก URL เป็นไฟล์จริง พร้อมกัน path traversal
     *
     * ตรวจสองชั้น: กรองอักขระที่ใช้ไต่ขึ้นไดเรกทอรีก่อน แล้วยืนยันอีกครั้งด้วย realpath
     * ว่าไฟล์ที่ได้อยู่ใต้โฟลเดอร์ที่อนุญาตจริง — ชั้นแรกกันเคสทั่วไป ชั้นสองกัน symlink
     */
    private function resolve(string $path): string
    {
        abort_unless(preg_match('#^[A-Za-z0-9._/-]+\.html$#', $path) === 1, 404);
        abort_if(str_contains($path, '..'), 404);

        $root = realpath(base_path(self::ROOT));
        $file = realpath(base_path(self::ROOT . '/' . $path));

        abort_unless($file !== false && $root !== false && str_starts_with($file, $root), 404);

        return $file;
    }

    /**
     * แทนสคริปต์ menu-config.js ด้วยบริบทจริงของผู้ใช้ที่ล็อกอินอยู่
     *
     * ตำแหน่งเดิมของ menu-config.js อยู่ระหว่าง mock-data.js กับ sidebar-render.js พอดี
     * ซึ่งเป็นตำแหน่งที่ต้องการอยู่แล้ว จึงแทนที่ตรงนั้นได้เลย
     * ถ้าหน้าไหนไม่มีสคริปต์นั้นแปลว่าไม่มีแถบเมนู ไม่ต้องฉีดอะไร
     */
    private function injectAuthContext(string $html, Request $request): string
    {
        $context = view('partials.client-context')->render();

        $html = preg_replace(
            '#<script src="[^"]*assets/js/menu-config\.js"></script>#',
            $context,
            $html,
            1
        );

        /* โหลดฟอนต์ตรงนี้ด้วย ไม่รอ @import ใน standard/tokens.css ที่เป็นคำขอต่อแถวสองชั้น
           URL ต้องตรงกับใน tokens.css เป๊ะ เบราว์เซอร์จะได้ยุบเป็นคำขอเดียว */
        $head = '<meta name="csrf-token" content="' . e(csrf_token()) . '">' . PHP_EOL
            . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600&amp;family=Anuphan:wght@200;300;400;500;600&amp;display=swap">' . PHP_EOL;

        return str_replace('</head>', $head . '</head>', $html);
    }
}
