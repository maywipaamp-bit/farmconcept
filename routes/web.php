<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\LegacyPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| เส้นทางของระบบ
|--------------------------------------------------------------------------
| จัดกลุ่มตาม Module ตามข้อ 4.3 ของมาตรฐานโปรเจกต์
|
| ทุกหน้าหลังบ้านอยู่ใต้ middleware `auth` รวมถึงหน้า HTML เดิมที่ยังไม่ได้ย้าย
| ซึ่งถูกย้ายออกจาก public/ มาเสิร์ฟผ่าน LegacyPageController เพื่อให้ผ่านการตรวจสิทธิ์ก่อนเสมอ
*/

Route::redirect('/', '/home.html');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

/* ลิงก์เก่าที่ยังชี้ /login.html — ส่งต่อไปหน้าใหม่แทนที่จะ 404 */
Route::redirect('/login.html', '/login');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::prefix('activities')->name('activities.')->group(function () {
            /*
             | ต้องเป็น /list ไม่ใช่ / เพราะ URL /admin/activities ถูกใช้เป็น prefix
             | ของหน้า static เดิมอยู่ เมื่อย้ายหน้าในโมดูลนี้ครบแล้วค่อยเปลี่ยนกลับ
             */
            Route::get('/list', [ActivityController::class, 'index'])
                ->middleware('menu:activities-list')
                ->name('index');
        });
    });

    /*
     | หน้า HTML เดิมที่ยังไม่ได้ย้าย — ต้องประกาศ "ท้ายสุด" ของกลุ่ม admin
     | ไม่งั้นจะดักเส้นทาง Blade ที่ประกาศไว้ข้างบนไปหมด
     |
     | ไม่ระบุ menu_key ให้ middleware เดาจาก path เอง ผ่าน config/menu.php
     | path ที่ไม่ได้ลงทะเบียนไว้จะได้ 404 ไม่ใช่ปล่อยผ่าน
     */
    Route::get('/admin/{path}', [LegacyPageController::class, 'show'])
        ->where('path', '.*\.html')
        ->middleware('menu')
        ->name('legacy');
});
