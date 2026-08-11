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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
