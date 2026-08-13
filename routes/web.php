<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\CohortController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MasterData;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\LegacyPageController;
use App\Http\Controllers\PublicActivityController;
use App\Http\Controllers\ReviewController;
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

/* หน้ากิจกรรมสาธารณะ — รายการและรายละเอียดอ่านจาก MySQL ผ่าน Laravel */
Route::get('/activities', [PublicActivityController::class, 'page'])
    ->name('public.activities');

Route::redirect('/activities.html', '/activities', 301);

Route::get('/activities/{activity}', [PublicActivityController::class, 'show'])
    ->where('activity', '[A-Za-z0-9-]+')
    ->name('public.activities.show');

/* ข้อมูลกิจกรรมสาธารณะ — คืนเฉพาะรายการที่เผยแพร่ */
Route::get('/api/public/activities', [PublicActivityController::class, 'index'])
    ->name('public.activities.data');

/*
 | หน้าส่งงานให้ลูกค้าตรวจ — อยู่นอก middleware auth โดยตั้งใจ
 | ลูกค้าเปิดลิงก์แล้วคอมเมนต์ได้เลย ไม่ต้องมีบัญชีในระบบ
 |
 | เฉพาะการแก้สถานะ/วันครบกำหนดที่ตรวจสิทธิ์เพิ่มในคอนโทรลเลอร์
 | เพราะเป็นข้อมูลของทีมพัฒนา ไม่ใช่ของผู้ตรวจ
 */
Route::prefix('review')->name('review.')->group(function () {
    Route::get('/', [ReviewController::class, 'index'])->name('index');
    Route::get('/items/{item}/comments', [ReviewController::class, 'comments'])->name('comments');
    Route::post('/items/{item}/comments', [ReviewController::class, 'storeComment'])->name('comments.store');
    Route::put('/items/{item}', [ReviewController::class, 'updateItem'])->name('items.update');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    /* โปรไฟล์ของผู้ล็อกอินทุกบทบาท ไม่ผูกกับสิทธิ์เมนูจัดการผู้ใช้ */
    Route::post('/admin/profile', [UserController::class, 'updateProfile'])
        ->name('admin.profile.update');

    Route::prefix('admin')->name('admin.')->group(function () {
        /*
         | แดชบอร์ดภาพรวม — ย้ายเป็นหน้า Blade แล้ว
         | ลิงก์เดิมทั้งระบบยังชี้ /admin/dashboard.html อยู่ (หน้า static ที่ยังไม่ได้ย้าย
         | เขียนเป็น ../dashboard.html) จึงส่งต่อมาที่นี่แบบเดียวกับที่ /login.html ทำ
         | ต้องมาก่อน route ของหน้า static ไม่งั้น LegacyPageController จะดักไปก่อน
         */
        Route::redirect('/dashboard.html', '/admin/dashboard');

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('menu:dashboard')
            ->name('dashboard');

        /* เนื้อในของแดชบอร์ดตามช่วงเวลา — ใช้ตอนกดชิปตัวกรอง ไม่ต้องโหลดหน้าใหม่ */
        Route::get('/dashboard/fragment', [DashboardController::class, 'fragment'])
            ->middleware('menu:dashboard')
            ->name('dashboard.fragment');

        Route::prefix('activities')->name('activities.')->group(function () {
            /*
             | ต้องเป็น /list ไม่ใช่ / เพราะ URL /admin/activities ถูกใช้เป็น prefix
             | ของหน้า static เดิมอยู่ เมื่อย้ายหน้าในโมดูลนี้ครบแล้วค่อยเปลี่ยนกลับ
             */
            Route::get('/list', [ActivityController::class, 'index'])
                ->middleware('menu:activities-list')
                ->name('index');

            Route::get('/create', [ActivityController::class, 'create'])
                ->middleware('menu:activities-list')
                ->name('create');

            Route::post('/', [ActivityController::class, 'store'])
                ->middleware('menu:activities-list')
                ->name('store');

            /* {activity} ผูกกับคอลัมน์ code ผ่าน Activity::getRouteKeyName()
               ต้องมาหลัง /list และ /create ไม่งั้นสองคำนี้จะถูกตีความเป็นรหัสกิจกรรม */
            Route::get('/{activity}/edit', [ActivityController::class, 'edit'])
                ->middleware('menu:activities-list')
                ->name('edit');

            Route::get('/{activity}/cover', [ActivityController::class, 'showCover'])
                ->middleware('menu:activities-list')
                ->name('cover.show');

            /* รูปปกแยก endpoint เพราะ PHP อ่าน multipart จาก PUT ไม่ได้ */
            Route::post('/{activity}/cover', [ActivityController::class, 'uploadCover'])
                ->middleware('menu:activities-list')
                ->name('cover.store');

            Route::delete('/{activity}/cover', [ActivityController::class, 'deleteCover'])
                ->middleware('menu:activities-list')
                ->name('cover.destroy');

            Route::put('/{activity}', [ActivityController::class, 'update'])
                ->middleware('menu:activities-list')
                ->name('update');

            Route::delete('/{activity}', [ActivityController::class, 'destroy'])
                ->middleware('menu:activities-list')
                ->name('destroy');
        });

        /*
         | ข้อมูลพื้นฐาน — ทุกตารางในเมนู "พื้นฐาน" ใช้ชุด endpoint หน้าตาเดียวกัน
         | ประกาศรวมกันที่นี่ เพิ่มตารางใหม่คือเพิ่มหนึ่งบรรทัดในอาร์เรย์
         |
         | {code} คือรหัสข้อความ (TG-001) ไม่ใช่ id ตัวเลข — URL อ่านออกและไม่บอกลำดับข้อมูล
         | สิทธิ์แยกรายเมนู ไม่ใช่สิทธิ์รวมของกลุ่ม "พื้นฐาน" เพราะแต่ละหน้ามอบให้คนละคนได้
         */
        Route::prefix('master')->name('master.')->group(function () {
            $tables = [
                'target-groups' => [MasterData\TargetGroupController::class, 'master-data-target-groups'],
                'activity-formats' => [MasterData\ActivityFormatController::class, 'master-data-activity-formats'],
                'programs' => [MasterData\ProgramController::class, 'master-data-programs'],
                'instructors' => [MasterData\InstructorController::class, 'master-data-instructors'],
                'areas' => [MasterData\AreaController::class, 'master-data-areas'],
            ];

            foreach ($tables as $slug => [$controller, $menuKey]) {
                Route::prefix($slug)->name($slug . '.')->middleware('menu:' . $menuKey)->group(function () use ($controller) {
                    /* URL เดียวทำสองหน้าที่ — เปิดจากเบราว์เซอร์ได้หน้าจอ ยิงด้วย Accept: application/json
                       ได้รายการข้อมูล ทำให้ลิงก์ในเมนูกับปลายทางที่หน้าจอเรียกเป็นที่เดียวกัน
                       ไม่ต้องจำสอง URL ต่อหนึ่งตาราง */
                    Route::get('/', [$controller, 'index'])->name('index');
                    Route::post('/', [$controller, 'store'])->name('store');
                    Route::put('/{code}', [$controller, 'update'])->name('update');
                    Route::delete('/{code}', [$controller, 'destroy'])->name('destroy');
                });
            }

            /* รูปวิทยากรแยก endpoint เพราะ PHP อ่าน multipart จาก PUT ไม่ได้ — แบบเดียวกับรูปปกกิจกรรม */
            Route::middleware('menu:master-data-instructors')->group(function () {
                Route::post('/instructors/{code}/photo', [MasterData\InstructorController::class, 'uploadPhoto'])
                    ->name('instructors.photo.store');

                Route::delete('/instructors/{code}/photo', [MasterData\InstructorController::class, 'deletePhoto'])
                    ->name('instructors.photo.destroy');
            });

            /*
             | ตั้งค่ารอบประเมินไม่ได้แก้ทีละแถว แต่แก้ทั้งตารางแล้วกดบันทึกครั้งเดียว
             | จึงมีแค่ GET กับ PUT ก้อนเดียว ไม่มี POST/DELETE รายแถว
             */
            Route::prefix('follow-up-rounds')->name('follow-up-rounds.')
                ->middleware('menu:master-data-follow-up-rounds')
                ->group(function () {
                    Route::get('/', [MasterData\FollowUpRoundTemplateController::class, 'index'])->name('index');
                    Route::put('/', [MasterData\FollowUpRoundTemplateController::class, 'bulkSave'])->name('save');
                });
        });

        Route::redirect('/users/list.html', '/admin/users');

        Route::prefix('users')->name('users.')->middleware('menu:users-list')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        });

        Route::redirect('/users/roles.html', '/admin/users/roles');

        Route::prefix('users/roles')->name('users.roles.')->middleware('menu:users-roles')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::put('/{role}', [RoleController::class, 'update'])->name('update');
            Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
        });

        Route::redirect('/cohort/list.html', '/admin/cohort');
        Route::redirect('/cohort/detail.html', '/admin/cohort');

        Route::prefix('cohort')->name('cohort.')->middleware('menu:cohort')->group(function () {
            Route::get('/', [CohortController::class, 'index'])->name('index');
            Route::post('/', [CohortController::class, 'store'])->name('store');
            Route::get('/{cohortProfile}', [CohortController::class, 'show'])->name('show');
            Route::patch('/{cohortProfile}/stop', [CohortController::class, 'stopFollowUp'])->name('stop');
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
