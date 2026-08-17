<?php

use App\Http\Controllers\Admin\ActivityCheckinController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\ReportPeopleController;
use App\Http\Controllers\Admin\ActivityQrController;
use App\Http\Controllers\Admin\ActivityResponseController;
use App\Http\Controllers\Admin\CohortController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EvaluationController;
use App\Http\Controllers\Admin\EvaluationResponseController;
use App\Http\Controllers\Admin\MasterData;
use App\Http\Controllers\Admin\RegistrantController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TrackingRoundController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\LegacyPageController;
use App\Http\Controllers\PublicActivityController;
use App\Http\Controllers\PublicCheckinController;
use App\Http\Controllers\PublicLineLoginController;
use App\Http\Controllers\PublicPostSurveyController;
use App\Http\Controllers\PublicQrController;
use App\Http\Controllers\PublicRegistrationController;
use App\Http\Controllers\PublicTrackingRoundQrController;
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

Route::get('/', [PublicActivityController::class, 'page']);
Route::redirect('/home.html', '/');

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

/* หน้าประวัติความเป็นมา — เนื้อหาคงที่ ไม่ต้องมีคอนโทรลเลอร์ */
Route::view('/about', 'public.activities.about')->name('public.about');

/* หน้าช่องทางติดต่อ — แยกออกมาจากท้ายหน้าเกี่ยวกับเรา */
Route::view('/contact', 'public.activities.contact')->name('public.contact');

Route::get('/activities/{activity}', [PublicActivityController::class, 'show'])
    ->where('activity', '[A-Za-z0-9-]+')
    ->name('public.activities.show');

/* flow ลงทะเบียน 6 ขั้นบนมือถือ — ตรวจสิทธิ์ · กรอกข้อมูล · ชำระเงิน · สำเร็จ */
Route::get('/activities/{activity}/register', [PublicRegistrationController::class, 'page'])
    ->where('activity', '[A-Za-z0-9-]+')
    ->name('public.activities.register');

Route::post('/activities/{activity}/registration/check', [PublicRegistrationController::class, 'check'])
    ->where('activity', '[A-Za-z0-9-]+')
    ->middleware('throttle:30,1')
    ->name('public.activities.registration.check');

Route::post('/activities/{activity}/registration/payment', [PublicRegistrationController::class, 'payment'])
    ->where('activity', '[A-Za-z0-9-]+')
    ->middleware('throttle:10,1')
    ->name('public.activities.registration.payment');

Route::post('/activities/{activity}/registration', [PublicRegistrationController::class, 'store'])
    ->where('activity', '[A-Za-z0-9-]+')
    ->middleware('throttle:10,1')
    ->name('public.activities.registration.store');

Route::post('/activities/{activity}/checkin/lookup', [PublicCheckinController::class, 'lookup'])
    ->where('activity', '[A-Za-z0-9-]+')
    ->middleware('throttle:30,1')
    ->name('public.activities.checkin.lookup');

Route::post('/activities/{activity}/checkin', [PublicCheckinController::class, 'store'])
    ->where('activity', '[A-Za-z0-9-]+')
    ->middleware('throttle:15,1')
    ->name('public.activities.checkin.store');

/* หน้าแบบประเมินหลังกิจกรรม — แยกจากหน้ารายละเอียด มีหัวเว็บเหมือนแบบประเมินติดตาม */
Route::get('/activities/{activity}/survey', [PublicPostSurveyController::class, 'page'])
    ->where('activity', '[A-Za-z0-9-]+')
    ->name('public.activities.survey');

Route::post('/activities/{activity}/post-survey', [PublicPostSurveyController::class, 'store'])
    ->where('activity', '[A-Za-z0-9-]+')
    ->middleware('throttle:10,1')
    ->name('public.activities.post-survey.store');

/* เข้าสู่ระบบด้วย LINE ก่อนลงทะเบียน — callback ไม่มีรหัสกิจกรรมใน URL
   เพราะ LINE ให้ลงทะเบียน Callback URL ไว้ล่วงหน้าเป็นค่าตายตัว รหัสกิจกรรมจึงฝากไว้ใน session */
Route::get('/activities/{activity}/line/redirect', [PublicLineLoginController::class, 'redirect'])
    ->where('activity', '[A-Za-z0-9-]+')
    ->middleware('throttle:20,1')
    ->name('public.line.redirect');

Route::get('/auth/line/callback', [PublicLineLoginController::class, 'callback'])
    ->middleware('throttle:20,1')
    ->name('public.line.callback');

Route::post('/activities/{activity}/line/logout', [PublicLineLoginController::class, 'logout'])
    ->where('activity', '[A-Za-z0-9-]+')
    ->name('public.line.logout');

/* QR สาธารณะของกิจกรรม — token สุ่ม ไม่เปิดเผย activity id และ QR ที่ถูกปิดต้องได้หน้าอธิบายแทน 404 */
Route::get('/r/{token}', [PublicQrController::class, 'registration'])
    ->where('token', '[a-z0-9]{24}')
    ->name('public.qr.registration');
Route::get('/c/{token}', [PublicQrController::class, 'checkin'])
    ->where('token', '[a-z0-9]{24}')
    ->name('public.qr.checkin');
Route::get('/s/{token}', [PublicQrController::class, 'postSurvey'])
    ->where('token', '[a-z0-9]{24}')
    ->name('public.qr.post-survey');

/* QR ติดตามสุขภาพ — อันเดียวทั้งโครงการ ไม่ผูกกิจกรรม และไม่มีรหัสคนอยู่ใน URL
   ต้องยืนยันตัวตนก่อนเสมอ ระบบจึงแสดงเฉพาะรอบที่ถึงกำหนดของคนนั้น
   แยกเป็นหน้าจอละขั้นตอน แต่ละขั้นมี URL ของตัวเอง กดย้อนกลับแล้วไม่หลุดไปเริ่มใหม่ */
/* พาธอ่านออกและพูดต่อทางโทรศัพท์ได้ — QR ติดตามสุขภาพมีอันเดียวทั้งโครงการ
   จึงไม่ต้องมี token ใน URL ตัว token เดิมเป็นแค่รหัสของแถวใน act_qr_codes ไม่ใช่ความลับ
   (ตัวกันคนอื่นเข้าถึงข้อมูลคือการยืนยันตัวตน ไม่ใช่การเดา URL ไม่ถูก) */
Route::prefix('health')->group(function () {
    Route::get('/', [PublicTrackingRoundQrController::class, 'landing'])->name('public.tracking-round-qr');
    Route::post('/verify', [PublicTrackingRoundQrController::class, 'verify'])->name('public.tracking-round-qr.verify');

    Route::get('/choose', [PublicTrackingRoundQrController::class, 'choose'])->name('public.tracking-round-qr.choose');
    Route::post('/choose', [PublicTrackingRoundQrController::class, 'chooseSubmit'])->name('public.tracking-round-qr.choose.submit');

    Route::get('/register', [PublicTrackingRoundQrController::class, 'register'])->name('public.tracking-round-qr.register');
    Route::post('/register', [PublicTrackingRoundQrController::class, 'registerSubmit'])->name('public.tracking-round-qr.register.submit');

    Route::get('/home', [PublicTrackingRoundQrController::class, 'dashboard'])->name('public.tracking-round-qr.dashboard');
    Route::post('/notify', [PublicTrackingRoundQrController::class, 'toggleNotify'])->name('public.tracking-round-qr.notify');

    Route::get('/proxy', [PublicTrackingRoundQrController::class, 'proxy'])->name('public.tracking-round-qr.proxy');
    Route::post('/proxy', [PublicTrackingRoundQrController::class, 'proxySubmit'])->name('public.tracking-round-qr.proxy.submit');
    Route::post('/proxy/stop', [PublicTrackingRoundQrController::class, 'proxyStop'])->name('public.tracking-round-qr.proxy.stop');

    Route::get('/rounds', [PublicTrackingRoundQrController::class, 'roundList'])->name('public.tracking-round-qr.rounds');
    Route::get('/rounds/{round}/survey', [PublicTrackingRoundQrController::class, 'survey'])->name('public.tracking-round-qr.survey');
    Route::post('/rounds/{round}/survey', [PublicTrackingRoundQrController::class, 'surveySubmit'])->name('public.tracking-round-qr.survey.submit');
    Route::get('/rounds/{round}/done', [PublicTrackingRoundQrController::class, 'done'])->name('public.tracking-round-qr.done');

    Route::post('/sign-out', [PublicTrackingRoundQrController::class, 'signOut'])->name('public.tracking-round-qr.sign-out');

    Route::get('/line', [PublicLineLoginController::class, 'redirectHealth'])->name('public.tracking-round-qr.line');
    Route::get('/line/return', [PublicTrackingRoundQrController::class, 'lineReturn'])->name('public.tracking-round-qr.line-return');
})->where('round', '[0-9]+');

/* QR ที่พิมพ์แจกไปแล้วชี้มาที่ /h/<token> — ส่งต่อไปพาธใหม่ ห้ามให้เจอ 404
   กระดาษที่แจกไปแล้วเรียกคืนไม่ได้ */
Route::get('/h/{token}', fn () => redirect()->route('public.tracking-round-qr'))
    ->where('token', '[a-z0-9]{24}');

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

        /*
         | รายงาน — หน้าที่มองข้อมูลข้ามกิจกรรม ไม่ผูกกับกิจกรรมใดกิจกรรมหนึ่ง
         | แยก prefix ออกจาก activities เพราะไม่ใช่การจัดการกิจกรรมรายตัว
         | และจะมีรายงานตัวอื่นมาอยู่ในหมวดนี้ต่อไป
         */
        Route::prefix('reports')->name('reports.')->group(function () {
            /* รายชื่อผู้เข้าร่วมทั้งหมด — รวมทุกกิจกรรมเป็นรายคน (ยึดเบอร์โทร/อีเมล) */
            Route::get('/people', [ReportPeopleController::class, 'index'])
                ->middleware('menu:reports-people')
                ->name('people');
        });

        /* พาธเดิมตอนหน้านี้ยังอยู่ใต้หมวดกิจกรรม — ส่งต่อไปที่ใหม่แทนที่จะ 404 */
        Route::redirect('/activities/people', '/admin/reports/people');

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

            /* หน้า Check-in หน้างาน — ต้องอยู่ก่อน /{activity}/... ด้วยเหตุผลเดียวกับ /list และ /create
               เมนู Check-in เป็นสิทธิ์คนละตัวกับเมนูจัดการกิจกรรม เจ้าหน้าที่หน้างานจึงเข้าได้
               โดยไม่ต้องให้สิทธิ์แก้ไขกิจกรรมตามไปด้วย */
            Route::redirect('/checkin.html', '/admin/activities/checkin');

            Route::get('/checkin', [ActivityCheckinController::class, 'index'])
                ->middleware('menu:activities-checkin')
                ->name('checkin');

            /* ชุด endpoint JSON ของหน้าเช็คอิน — รูป URL ต้องตรงกับ assets/js/checkin-service.js
               ที่ตั้ง checkinApiBase = '/admin' ไว้ (ดู checkin.blade.php)
               รับสองสิทธิ์ เพราะแท็บ Check-in ในหน้ารายละเอียดกิจกรรมก็เรียกชุดนี้เหมือนกัน
               คนที่จัดการกิจกรรมได้จึงเช็คอินแทนจากแท็บนั้นได้โดยไม่ต้องเปิดสิทธิ์เมนู Check-in เพิ่ม */
            Route::middleware('menu:activities-checkin|activities-list')->name('checkin.')->group(function () {
                Route::get('/{activity}/checkin', [ActivityCheckinController::class, 'snapshot'])->name('snapshot');
                Route::post('/{activity}/checkin', [ActivityCheckinController::class, 'store'])->name('store');
                Route::get('/{activity}/checkin/audit', [ActivityCheckinController::class, 'audit'])->name('audit');
                Route::delete('/{activity}/checkin/{registration}', [ActivityCheckinController::class, 'destroy'])
                    ->where('registration', '[A-Za-z0-9-]+')->name('destroy');
                Route::post('/{activity}/walk-ins', [ActivityCheckinController::class, 'walkIn'])->name('walk-ins');
            });

            Route::post('/', [ActivityController::class, 'store'])
                ->middleware('menu:activities-list')
                ->name('store');

            /* ผู้ลงทะเบียน — ย้ายจากหน้า static แล้ว
               ต้องมาก่อน /{activity} ไม่งั้น "registrants" จะถูกอ่านเป็นรหัสกิจกรรม
               ผูก {registration} ด้วย code ไม่ใช่ id ตัวเลข URL จึงไม่บอกลำดับข้อมูลในฐาน */
            Route::redirect('/registrants.html', '/admin/activities/registrants');

            /* รับสองสิทธิ์ด้วยเหตุผลเดียวกับชุด checkin — แท็บลงทะเบียนในหน้ารายละเอียดกิจกรรม
               เรียก endpoint ยืนยันการชำระเงินและเปิดดูสลิปจากชุดนี้ */
            Route::prefix('registrants')->name('registrants.')
                ->middleware('menu:activities-registrants|activities-list')->group(function () {
                    Route::get('/', [RegistrantController::class, 'index'])->name('index');
                    Route::patch('/{registration:code}/payment', [RegistrantController::class, 'updatePayment'])->name('payment');
                    Route::post('/{registration:code}/checkin', [RegistrantController::class, 'checkin'])->name('checkin');
                    Route::delete('/{registration:code}/checkin', [RegistrantController::class, 'undoCheckin'])->name('checkin.undo');
                    Route::get('/{registration:code}/slip/{slip}', [RegistrantController::class, 'slip'])->name('slip');
                });

            /* ประเมินกิจกรรม — ย้ายจากหน้า static แล้ว
               ต้องมาก่อน /{activity} ด้วยเหตุผลเดียวกับ registrants
               กิจกรรมที่ดูอยู่ส่งเป็น ?id=<code> ไม่ใช่ส่วนของ path จึงไม่ชนกับ {activity} */
            Route::redirect('/responses.html', '/admin/activities/responses');

            Route::prefix('responses')->name('responses.')
                ->middleware('menu:activities-responses')->group(function () {
                    Route::get('/', [ActivityResponseController::class, 'index'])->name('index');
                    Route::get('/data', [ActivityResponseController::class, 'data'])->name('data');
                    Route::get('/summary', [ActivityResponseController::class, 'summary'])->name('summary');
                });

            /* {activity} ผูกกับคอลัมน์ code ผ่าน Activity::getRouteKeyName()
               ต้องมาหลัง /list และ /create ไม่งั้นสองคำนี้จะถูกตีความเป็นรหัสกิจกรรม */

            /* หน้าภาพรวมกิจกรรม — ปลายทางของ "ดูรายละเอียด" ในหน้ารายการ */
            Route::get('/{activity}', [ActivityController::class, 'show'])
                ->middleware('menu:activities-list')
                ->name('show');

            /* แท็บผู้เข้าร่วมของหน้ารายละเอียด — ตารางรายชื่อพร้อมฟิลด์ตามแบบลงทะเบียน */
            Route::get('/{activity}/participants', [ActivityController::class, 'participants'])
                ->middleware('menu:activities-list')
                ->name('participants');

            /* แท็บ Check-in ของหน้ารายละเอียด — ตารางอ่านอย่างเดียว
               ใช้ /checkins (มี s) เพราะ /{activity}/checkin เป็น endpoint JSON ของหน้าเช็คอินหน้างาน */
            Route::get('/{activity}/checkins', [ActivityController::class, 'checkins'])
                ->middleware('menu:activities-list')
                ->name('checkins');

            /* แท็บแบบประเมินของหน้ารายละเอียด — คำตอบนิรนามพร้อมคะแนนรายข้อ */
            Route::get('/{activity}/evaluations', [ActivityController::class, 'evaluations'])
                ->middleware('menu:activities-list')
                ->name('evaluations');

            /* แท็บรายงานภาพรวมของหน้ารายละเอียด — กราฟสรุปจากข้อมูลชุดเดียวกับแท็บอื่น */
            Route::get('/{activity}/reports', [ActivityController::class, 'reports'])
                ->middleware('menu:activities-list')
                ->name('reports');

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

            /* ไม่ผูก middleware:menu เพราะหน้าที่ต้องใช้ QR มีทั้งเมนูจัดการกิจกรรมและเมนู Check-in
               สิทธิ์ตรวจที่ ActivityPolicy::viewQr ซึ่งรู้จักทั้งสองเมนู */
            Route::get('/{activity}/qr/{purpose}', [ActivityQrController::class, 'show'])
                ->where('purpose', 'public|checkin|post_survey')
                ->name('qr.show');

            Route::put('/{activity}', [ActivityController::class, 'update'])
                ->middleware('menu:activities-list')
                ->name('update');

            /* เปลี่ยนสถานะอย่างเดียวจากหน้ารายการ — endpoint เบา ไม่ผ่าน ActivityRequest
               เพราะฟอร์มเต็มบังคับกรอกฟิลด์อื่นครบด้วย ซึ่งไม่จำเป็นสำหรับแค่เปลี่ยนสถานะ */
            Route::patch('/{activity}/status', [ActivityController::class, 'updateStatus'])
                ->middleware('menu:activities-list')
                ->name('status');

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
                'payment-accounts' => [MasterData\PaymentAccountController::class, 'master-data-payment-accounts'],
                'consent-documents' => [MasterData\ConsentDocumentController::class, 'master-data-consents'],
                'programs' => [MasterData\ProgramController::class, 'master-data-programs'],
                'instructors' => [MasterData\InstructorController::class, 'master-data-instructors'],
                'areas' => [MasterData\AreaController::class, 'master-data-areas'],
            ];

            foreach ($tables as $slug => [$controller, $menuKey]) {
                Route::prefix($slug)->name($slug.'.')->middleware('menu:'.$menuKey)->group(function () use ($controller) {
                    /* URL เดียวทำสองหน้าที่ — เปิดจากเบราว์เซอร์ได้หน้าจอ ยิงด้วย Accept: application/json
                       ได้รายการข้อมูล ทำให้ลิงก์ในเมนูกับปลายทางที่หน้าจอเรียกเป็นที่เดียวกัน
                       ไม่ต้องจำสอง URL ต่อหนึ่งตาราง */
                    Route::get('/', [$controller, 'index'])->name('index');
                    Route::post('/', [$controller, 'store'])->name('store');
                    Route::put('/{code}', [$controller, 'update'])->name('update');
                    Route::delete('/{code}', [$controller, 'destroy'])->name('destroy');
                });
            }

            Route::prefix('registration-options')->name('registration-options.')
                ->middleware('menu:master-data-registration-options')->group(function () {
                    Route::get('/', [MasterData\RegistrationOptionController::class, 'index'])->name('index');
                    Route::post('/', [MasterData\RegistrationOptionController::class, 'store'])->name('store');
                    Route::put('/{key}', [MasterData\RegistrationOptionController::class, 'update'])->name('update');
                    Route::delete('/{key}', [MasterData\RegistrationOptionController::class, 'destroy'])->name('destroy');
                });

            /* รูปวิทยากรแยก endpoint เพราะ PHP อ่าน multipart จาก PUT ไม่ได้ — แบบเดียวกับรูปปกกิจกรรม */
            Route::middleware('menu:master-data-instructors')->group(function () {
                Route::post('/instructors/{code}/photo', [MasterData\InstructorController::class, 'uploadPhoto'])
                    ->name('instructors.photo.store');

                Route::delete('/instructors/{code}/photo', [MasterData\InstructorController::class, 'deletePhoto'])
                    ->name('instructors.photo.destroy');
            });

            Route::middleware('menu:master-data-payment-accounts')->group(function () {
                Route::post('/payment-accounts/{code}/qr-code', [MasterData\PaymentAccountController::class, 'uploadQrCode'])
                    ->name('payment-accounts.qr-code.store');

                Route::delete('/payment-accounts/{code}/qr-code', [MasterData\PaymentAccountController::class, 'deleteQrCode'])
                    ->name('payment-accounts.qr-code.destroy');
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

            /* สองเส้นนี้ต้องอยู่ก่อน /{cohortProfile} ไม่งั้น "lookups" จะถูกอ่านเป็นรหัสกลุ่มตัวอย่าง */
            Route::get('/lookups', [CohortController::class, 'lookups'])->name('lookups');
            Route::post('/upload-consent', [CohortController::class, 'uploadConsent'])->name('upload-consent');

            Route::get('/{cohortProfile}', [CohortController::class, 'show'])->name('show');
            Route::put('/{cohortProfile}', [CohortController::class, 'update'])->name('update');
            Route::delete('/{cohortProfile}', [CohortController::class, 'destroy'])->name('destroy');
            Route::patch('/{cohortProfile}/stop', [CohortController::class, 'stopFollowUp'])->name('stop');
        });

        Route::redirect('/evaluations/rounds.html', '/admin/tracking-rounds');
        Route::redirect('/evaluations/round-create.html', '/admin/tracking-rounds/create');
        Route::redirect('/evaluations/round-detail.html', '/admin/tracking-rounds');

        Route::prefix('tracking-rounds')->name('tracking-rounds.')->middleware('menu:evaluations-rounds')->group(function () {
            Route::get('/', [TrackingRoundController::class, 'index'])->name('index');
            Route::post('/', [TrackingRoundController::class, 'store'])->name('store');

            /* สองเส้นนี้ต้องอยู่ก่อน /{trackingRound} ไม่งั้นจะถูกอ่านเป็นรหัสรอบ */
            Route::get('/create', [TrackingRoundController::class, 'create'])->name('create');
            Route::get('/eligible-members', [TrackingRoundController::class, 'eligibleMembers'])->name('eligible-members');

            Route::get('/{trackingRound}', [TrackingRoundController::class, 'show'])
                ->where('trackingRound', '[A-Za-z0-9-]+')->name('show');
            Route::post('/{trackingRound}/send-notify', [TrackingRoundController::class, 'sendNotify'])
                ->where('trackingRound', '[A-Za-z0-9-]+')->name('send-notify');
            Route::patch('/{trackingRound}/cancel', [TrackingRoundController::class, 'cancel'])
                ->where('trackingRound', '[A-Za-z0-9-]+')->name('cancel');
            Route::post('/{trackingRound}/members/{member}/notify', [TrackingRoundController::class, 'sendNotifyMember'])
                ->where('trackingRound', '[A-Za-z0-9-]+')->name('members.notify');
            Route::post('/{trackingRound}/members/{member}/offline-log', [TrackingRoundController::class, 'offlineLog'])
                ->where('trackingRound', '[A-Za-z0-9-]+')->name('members.offline-log');
        });

        /* ลิงก์ Legacy ส่งต่อไป clean URL เท่านั้น ไม่ใช้ .html เป็น URL หลัก */
        Route::get('/evaluations/list.html', fn () => redirect()->route('admin.evaluations.index', status: 301))
            ->middleware('menu:evaluations')
            ->name('evaluations.legacy-list');
        Route::get('/evaluations/create.html', [EvaluationController::class, 'legacyCreateRedirect'])
            ->middleware('menu:evaluations')
            ->name('evaluations.legacy-create');

        /* ต้องอยู่ก่อนกลุ่ม evaluations ด้านล่าง ไม่งั้น "responses" จะถูกอ่านเป็นรหัสแบบประเมิน
           และใช้สิทธิ์เมนูคนละตัวกัน (ตอบแบบประเมิน ≠ จัดการแบบประเมิน) */
        Route::redirect('/evaluations/responses.html', '/admin/evaluations/responses');

        Route::prefix('evaluations/responses')->name('evaluations.responses.')
            ->middleware('menu:evaluations-responses')->group(function () {
                Route::get('/', [EvaluationResponseController::class, 'index'])->name('index');
                Route::get('/{id}', [EvaluationResponseController::class, 'show'])
                    ->where('id', '[0-9]+')->name('show');
            });

        Route::prefix('evaluations')->name('evaluations.')->middleware('menu:evaluations')->group(function () {
            Route::get('/', [EvaluationController::class, 'index'])->name('index');
            Route::get('/data', [EvaluationController::class, 'data'])->name('data');
            Route::get('/create', [EvaluationController::class, 'create'])->name('create');
            Route::post('/', [EvaluationController::class, 'store'])->name('store');
            Route::get('/{form}/edit', [EvaluationController::class, 'edit'])->where('form', '[A-Za-z0-9-]+')->name('edit');
            Route::get('/{form}', [EvaluationController::class, 'show'])->where('form', '[A-Za-z0-9-]+')->name('show');
            Route::put('/{form}', [EvaluationController::class, 'update'])->where('form', '[A-Za-z0-9-]+')->name('update');
            Route::post('/{form}/duplicate', [EvaluationController::class, 'duplicate'])->where('form', '[A-Za-z0-9-]+')->name('duplicate');
            Route::patch('/{form}/status', [EvaluationController::class, 'changeStatus'])->where('form', '[A-Za-z0-9-]+')->name('status');
            Route::delete('/{form}', [EvaluationController::class, 'destroy'])->where('form', '[A-Za-z0-9-]+')->name('destroy');
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
