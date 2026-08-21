<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * เสิร์ฟคู่มือการใช้งานจาก docs/user-manual/
 *
 * ไม่ย้ายไฟล์ไป public/ ด้วยสองเหตุผล
 *   1. คู่มือมีภาพหน้าจอหลังบ้าน ต้องผ่านการล็อกอินก่อนเสมอ — เหมือนหน้า legacy ใน LegacyPageController
 *   2. คู่มืออยู่คู่กับ docs/ ตัวอื่น ๆ ของโปรเจกต์ เปิดจากดิสก์ตอนแก้ก็ยังต้องได้
 *
 * ไฟล์ HTML อ้าง CSS ของระบบด้วยพาธสัมพัทธ์ ../../public/assets/... เพื่อให้ดับเบิลคลิกเปิดจากดิสก์ได้
 * ตอนเสิร์ฟผ่าน URL พาธนั้นชี้ผิด จึงเขียนทับเป็น /assets/... ให้ตอนส่งออก — ไม่ต้องแก้ไฟล์ต้นทาง
 */
class ManualController extends Controller
{
    private const ROOT = 'docs/user-manual';

    /** ชนิดไฟล์ที่ยอมให้ดึงได้ — คู่มือมีแค่เท่านี้ ไม่เปิดกว้างเผื่ออนาคต */
    private const TYPES = [
        'html' => 'text/html; charset=UTF-8',
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
    ];

    public function show(string $path = 'index.html'): Response|BinaryFileResponse
    {
        /* /manual และ /manual/ ให้เท่ากับหน้าแรก */
        $path = trim($path, '/') ?: 'index.html';

        $file = $this->resolve($path);
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        /* ภาพและไฟล์สแตติกส่งเป็นไฟล์ตรง ๆ ให้เบราว์เซอร์แคชได้ ไม่ต้องอ่านเข้าหน่วยความจำ */
        if ($ext !== 'html') {
            return response()->file($file, [
                'Content-Type' => self::TYPES[$ext],
                'Cache-Control' => 'private, max-age=300',
            ]);
        }

        return response($this->rewriteAssetPaths(file_get_contents($file)))
            ->header('Content-Type', self::TYPES['html'])
            /* คู่มือมีภาพหน้าจอที่มีข้อมูลผู้เข้าร่วม ห้ามให้ proxy เก็บไว้ */
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * แปลง path จาก URL เป็นไฟล์จริง พร้อมกัน path traversal
     *
     * ตรวจสองชั้นเหมือน LegacyPageController — กรองอักขระก่อน แล้วยืนยันด้วย realpath ว่าอยู่ใต้ ROOT จริง
     */
    private function resolve(string $path): string
    {
        abort_unless(preg_match('#^[A-Za-z0-9._/-]+\.(html|css|js|png|jpg|svg)$#', $path) === 1, 404);
        abort_if(str_contains($path, '..'), 404);

        $root = realpath(base_path(self::ROOT));
        $file = realpath(base_path(self::ROOT . '/' . $path));

        abort_unless($file !== false && $root !== false && str_starts_with($file, $root), 404);

        return $file;
    }

    /**
     * ../../public/assets/... -> /assets/...
     *
     * เขียนทับตอนส่งออกเท่านั้น ไฟล์ในโฟลเดอร์ยังเปิดจากดิสก์ได้เหมือนเดิม
     * ต่อ ?v=<เวลาแก้ไฟล์> ให้ด้วย เบราว์เซอร์จะได้ไม่ค้าง CSS เก่าหลังแก้มาตรฐาน
     */
    private function rewriteAssetPaths(string $html): string
    {
        return preg_replace_callback(
            '#href="(?:\.\./)+public/(assets/[^"?]+\.css)"#',
            function (array $m): string {
                $file = public_path($m[1]);
                $version = is_file($file) ? '?v=' . filemtime($file) : '';

                return 'href="/' . $m[1] . $version . '"';
            },
            $html
        );
    }
}
