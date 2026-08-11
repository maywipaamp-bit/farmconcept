<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\SatisfactionResponse;
use App\Models\SurveyResponse;
use App\Services\MenuService;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /* evl_answers.response_type เก็บเป็นคำสั้น ไม่ใช่ชื่อคลาสเต็ม
           ย้าย namespace ของ Model ได้โดยข้อมูลเดิมไม่พัง
           enforceMorphMap ทำให้ลืมลงทะเบียนชนิดใหม่แล้วพังทันทีตอนพัฒนา ไม่ใช่ตอน production */
        Relation::enforceMorphMap([
            'satisfaction' => SatisfactionResponse::class,
            'survey' => SurveyResponse::class,
            'activity' => Activity::class,
        ]);

        /* ปิด lazy loading นอก production — เจอ N+1 ตั้งแต่ตอนพัฒนา ไม่ใช่ตอนขึ้นจริง
           ตรงกับข้อกำหนดเรื่องประสิทธิภาพในข้อ 4.5 ของมาตรฐานโปรเจกต์ */
        Model::preventLazyLoading(! $this->app->isProduction());

        /* artisan serve ส่งต่อ environment ให้ลูกเฉพาะรายชื่อที่กำหนดไว้เท่านั้น
           TMP/TEMP ไม่อยู่ในรายชื่อ กระบวนการลูกจึงไม่รู้จักโฟลเดอร์ชั่วคราวของผู้ใช้
           แล้วถอยไปใช้ C:\WINDOWS ซึ่งเขียนไม่ได้ — ผลคือ "unable to create a temporary file"
           ทุกครั้งที่อัปโหลดไฟล์ ทั้งที่โค้ดและสิทธิ์ถูกต้องหมด
           เติมสองตัวนี้เข้าไปแทนการแก้ php.ini เพราะเป็นเรื่องของ dev server ไม่ใช่ของเครื่อง */
        if (! $this->app->isProduction()) {
            ServeCommand::$passthroughVariables[] = 'TMP';
            ServeCommand::$passthroughVariables[] = 'TEMP';
        }

        /* บริบทฝั่งหน้าจอประกอบที่เดียว ใช้ร่วมกันทั้งหน้า Blade และหน้า static เดิม
           eager load ไว้ครบ ไม่งั้นการวนเช็คสิทธิ์ทีละเมนูจะยิง query ต่อเมนู */
        View::composer('partials.client-context', function (ViewContract $view): void {
            $user = auth()->user()?->loadMissing('roles.menuPermissions');

            if ($user !== null) {
                $view->with('clientContext', app(MenuService::class)->clientContextFor($user));
            }
        });
    }
}
