<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /* ผู้ที่ล็อกอินอยู่แล้วเปิดหน้า /login ให้พากลับเข้าหลังบ้าน
           ไม่ใช่หน้าเว็บสาธารณะซึ่งเป็นค่าเริ่มต้นของ Laravel */
        $middleware->redirectUsersTo('/admin/activities/list');

        $middleware->alias([
            'menu' => \App\Http\Middleware\EnsureMenuAccess::class,
        ]);

        /* คนที่ถูกระงับสิทธิ์ระหว่างที่เปิดหน้าจอค้างไว้ ต้องถูกดีดออกในคำขอถัดไป
           ใส่ไว้ในกลุ่ม web เพื่อให้ครอบทุกหน้ารวมถึงหน้า static เดิม ไม่ใช่เฉพาะที่ประกาศเอง */
        $middleware->web(append: [
            \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /* ค่าเริ่มต้นของ Laravel จำกัดไว้เฉพาะ api/* ทำให้คำขอ fetch จากหน้าจอหลังบ้าน
           ได้หน้า error เป็น HTML กลับไป แล้วหน้าจอเอาข้อความเหตุผลไปแสดงไม่ได้
           เพิ่ม expectsJson() เข้ามาเพื่อให้คำขอที่ขอ JSON มา ได้ JSON กลับไปเสมอ */
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
