<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\Registration;
use App\Models\SatisfactionResponse;
use App\Models\SurveyResponse;
use App\Services\MenuService;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
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
            'registration' => Registration::class,
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

        /*
         * @assetv('assets/js/app.js') — เหมือน asset() แต่ต่อ ?v=<เวลาที่แก้ไฟล์> ให้
         *
         * JS/CSS ที่ไม่ได้ผ่าน Vite ถูกเสิร์ฟด้วยชื่อคงที่และไม่มี Cache-Control/ETag/Last-Modified
         * เบราว์เซอร์จึงเก็บของเก่าไว้ แก้ไฟล์แล้วหน้าจอไม่เปลี่ยนจนกว่าจะ hard refresh
         * ผูกเลขเวอร์ชันกับเวลาที่แก้ไฟล์ URL จึงเปลี่ยนเองทุกครั้งที่แก้จริง
         */
        Blade::directive('assetv', function (string $expression): string {
            return "<?php echo \App\Providers\AppServiceProvider::versionedAsset({$expression}); ?>";
        });

        /*
         * @thaidate($date) — 2026-09-30 → 30 ก.ย. 2569
         *
         * format() ของ PHP ให้ชื่อเดือนภาษาอังกฤษเสมอ และปีเป็น ค.ศ.
         * หน้าจอฝั่งผู้เข้าร่วมเป็นภาษาไทยล้วน จะเขียนตารางเดือนซ้ำในทุก view ไม่ได้
         */
        Blade::directive('thaidate', function (string $expression): string {
            return "<?php echo \App\Providers\AppServiceProvider::thaiDate({$expression}); ?>";
        });

        /* บริบทฝั่งหน้าจอประกอบที่เดียว ใช้ร่วมกันทั้งหน้า Blade และหน้า static เดิม
           eager load ไว้ครบ ไม่งั้นการวนเช็คสิทธิ์ทีละเมนูจะยิง query ต่อเมนู */
        View::composer('partials.client-context', function (ViewContract $view): void {
            $user = auth()->user()?->loadMissing('roles.menuPermissions');

            if ($user !== null) {
                $view->with('clientContext', app(MenuService::class)->clientContextFor($user));
            }
        });
    }
    /**
     * URL ของไฟล์ static พร้อมเลขเวอร์ชันจากเวลาที่แก้ไฟล์
     *
     * ไฟล์ที่ไม่มีอยู่จริงคืน asset() ตามปกติ ไม่ให้หน้าพังเพราะพิมพ์ path ผิด
     */
    public static function versionedAsset(string $path): string
    {
        $file = public_path($path);

        return is_file($file) ? asset($path) . '?v=' . filemtime($file) : asset($path);
    }

    /** วันที่แบบไทยย่อ — 30 ก.ย. 2569 · คืนขีดกลางเมื่อไม่มีวันที่ ไม่ใช่วันที่ปลอม */
    public static function thaiDate(mixed $date, bool $withYear = true): string
    {
        if (blank($date)) {
            return '—';
        }

        $date = $date instanceof \DateTimeInterface ? Carbon::instance($date) : Carbon::parse($date);

        $months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return $date->day.' '.$months[$date->month - 1].($withYear ? ' '.($date->year + 543) : '');
    }
}
