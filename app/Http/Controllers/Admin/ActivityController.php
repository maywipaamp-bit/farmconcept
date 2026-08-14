<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityRequest;
use App\Models\Activity;
use App\Models\ActivityFormat;
use App\Models\Area;
use App\Models\Course;
use App\Models\Form;
use App\Models\Instructor;
use App\Models\Program;
use App\Models\TargetGroup;
use App\Services\ActivityService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ActivityController extends Controller
{
    /** รหัสสมมติของกิจกรรมที่ยังไม่ได้บันทึก — ฟอร์มใช้เป็นคีย์ชั่วคราวเท่านั้น ไม่เคยลงฐาน */
    private const NEW_CODE = 'NEW';

    /** เพดานรูปปกตามกฎของแอป — ใช้ทั้งตอน validate และตอนบอกหน้าจอ */
    private const COVER_MAX_KB = 5120;

    use AuthorizesRequests;

    /**
     * รายการกิจกรรม
     *
     * ยังส่งข้อมูลทั้งชุดให้หน้าจอกรอง/แบ่งหน้าเองแบบเดิม เพราะเป็นหน้านำร่อง
     * ที่ตั้งใจเปลี่ยนเฉพาะ "แหล่งข้อมูล" ไม่แตะพฤติกรรมของ UI
     * ขั้นถัดไปคือย้าย filter/sort/paginate ไปทำที่ฝั่งเซิร์ฟเวอร์ตามข้อ 4.5
     */
    public function index(): View
    {
        /* forList() ใส่ eager load + withCount ให้แล้ว จึงไม่เกิด N+1 ตอนวาดคอลัมน์
           โปรแกรม/พื้นที่/วิทยากร และไม่ต้องนับผู้ลงทะเบียนทีละแถว */
        $activities = Activity::forList()
            ->with([
                'rounds:id,activity_id,round_date,time_start,time_end,location,capacity',
                'parentEvent:id,code,name',
                'updatedBy:id,name',
            ])
            /* ใหม่สุดอยู่บน และแก้ข้อมูลแล้วลำดับไม่ขยับ — ใช้ id ไม่ใช่ updated_at
               ซึ่งจะดีดแถวที่เพิ่งบันทึกขึ้นบนสุดทุกครั้งจนคนที่ไล่แก้ทีละแถวเสียตำแหน่ง */
            ->orderByDesc('id')
            ->get();

        /* ลำดับจริงบนหน้าเว็บสาธารณะ (id => ลำดับที่ 1,2,3,…) — เรียงด้วยเงื่อนไขเดียวกับ
           PublicActivityController เป๊ะ เพื่อให้เลขที่แอดมินเห็นตรงกับที่ผู้เข้าชมเห็นจริง */
        $publicRanks = Activity::published()
            ->where('visibility', 'สาธารณะ')
            ->where('public_sort_order', '>', 0)
            ->orderBy('public_sort_order')
            ->orderBy('start_date')
            ->pluck('id')
            ->flip()
            ->map(fn (int $index) => $index + 1);

        return view('admin.activities.list', [
            'activities' => $activities->map(fn (Activity $a) => $this->toListRow($a, $publicRanks->get($a->id))),
            'sessions' => $activities->mapWithKeys(fn (Activity $a) => [$a->code => $this->toSessions($a)]),
        ]);
    }

    /**
     * ฟอร์มแก้ไขกิจกรรม
     *
     * ฟอร์มเดิมอ้างอิงทุกอย่างด้วย "ชื่อ" ไม่ใช่ id จึงต้องส่งตารางแปลงชื่อ→id ไปด้วย
     * และส่งค่าปัจจุบันทั้งชุดไป เพื่อให้ฟิลด์ที่ฟอร์มไม่ได้คุม (visibility / organizer /
     * data_source) คงค่าเดิมไว้ตอนบันทึก ไม่ถูกล้างเป็นค่าว่าง
     */
    public function edit(Activity $activity): View
    {
        $this->authorize('update', $activity);

        $activity->load(['areas', 'instructors', 'targetGroups', 'rounds', 'program', 'course', 'format', 'parentEvent', 'forms', 'qrCodes']);

        return $this->formView($activity);
    }

    /**
     * ฟอร์มสร้างกิจกรรมใหม่
     *
     * ใช้ Blade ตัวเดียวกับหน้าแก้ไข เพราะฟิลด์เหมือนกันทุกช่อง
     * ต่างกันแค่ปลายทางที่ส่งข้อมูลไป ถ้าแยกไฟล์จะต้องแก้สองที่ทุกครั้งที่เพิ่มฟิลด์
     *
     * กิจกรรมยังไม่มีตัวตนในฐาน จึงสร้าง instance เปล่าที่ยังไม่บันทึกขึ้นมาแทน
     * แล้วผูก relation ว่างไว้เอง ไม่ปล่อยให้ Eloquent ไปยิง query ด้วยคีย์ null
     */
    public function create(): View
    {
        $this->authorize('create', Activity::class);

        $activity = new Activity([
            'code' => self::NEW_CODE,
            'status' => Activity::STATUS_DRAFT,
            'type' => Activity::TYPE_ACTIVITY,
            'visibility' => 'สาธารณะ',
            'capacity' => 0,
            'fee' => 0,
        ]);

        foreach (['areas', 'instructors', 'targetGroups', 'rounds', 'forms', 'qrCodes'] as $many) {
            $activity->setRelation($many, collect());
        }

        foreach (['program', 'course', 'format', 'parentEvent'] as $one) {
            $activity->setRelation($one, null);
        }

        $activity->registrations_count = 0;

        return $this->formView($activity);
    }

    /**
     * บันทึกกิจกรรมใหม่
     *
     * ใช้ ActivityRequest ตัวเดียวกับการแก้ไข — ฉบับร่างตรวจน้อย เผยแพร่ตรวจครบ
     * ตอบ URL ของหน้าแก้ไขกลับไปด้วย เพื่อให้หน้าจอเปลี่ยนโหมดต่อได้โดยไม่ต้องโหลดใหม่
     */
    public function store(ActivityRequest $request, ActivityService $service): JsonResponse
    {
        $this->authorize('create', Activity::class);

        $activity = $service->create($request->validated(), $request->user());

        return response()->json([
            'message' => 'สร้าง'.($activity->isEvent() ? 'อีเวนท์' : 'กิจกรรม').' "'.$activity->name.'" แล้ว',
            'code' => $activity->code,
            'redirect' => route('admin.activities.index'),
            'activity' => $this->toListRow($activity->load(['program', 'format', 'areas', 'instructors'])->loadCount('registrations')),
        ], 201);
    }

    /**
     * ข้อมูลทั้งหมดที่ฟอร์มต้องใช้ — ใช้ร่วมกันทั้งโหมดสร้างและโหมดแก้ไข
     *
     * ฟอร์มเดิมอ้างอิงทุกอย่างด้วย "ชื่อ" ไม่ใช่ id จึงต้องส่งตารางแปลงชื่อ→id ไปด้วย
     * และส่งค่าปัจจุบันทั้งชุดไป เพื่อให้ฟิลด์ที่ฟอร์มไม่ได้คุม (visibility / organizer /
     * data_source) คงค่าเดิมไว้ตอนบันทึก ไม่ถูกล้างเป็นค่าว่าง
     */
    private function formView(Activity $activity): View
    {
        $evaluationForms = Form::active()
            ->whereIn('type', [Form::TYPE_REGISTRATION, Form::TYPE_POST_ACTIVITY])
            ->with(['questions:id,form_id,question_type,text,is_required,sort_order'])
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(fn (Form $form) => [
                'id' => (string) $form->id,
                'code' => $form->code,
                'name' => $form->name,
                'type' => $form->type,
                'status' => $form->status,
                'questions' => $form->questions->map(fn ($question) => [
                    'text' => $question->text,
                    'type' => $question->question_type,
                    'required' => $question->is_required,
                ])->values()->all(),
            ])->values();

        return view('admin.activities.form', [
            'activity' => $activity,
            'isCreate' => ! $activity->exists,
            'activities' => [$this->toFormRow($activity)],
            'sessions' => [$activity->code => $this->toSessions($activity)],
            'areas' => Area::orderBy('name')->get(['id', 'name']),
            'targetGroups' => TargetGroup::active()->get(['id', 'name']),

            /* ต้องส่ง icon ไปด้วย ไม่งั้นชิปหมวดหมู่จะไม่มีไอคอน
               (catIconSvg ใช้ค่านี้ไปหา path ใน activityCategoryIcons) */
            'formats' => ActivityFormat::active()->get(['id', 'name', 'icon'])
                ->map(fn (ActivityFormat $f) => ['name' => $f->name, 'icon' => $f->icon, 'active' => true]),

            /* โปรแกรม → หลักสูตร → วิทยากรที่สอนหลักสูตรนั้น
               ของเดิมเขียนไว้ตายตัวในไฟล์ JS และเป็นคนละชุดกับฐานข้อมูลทั้งหมด */
            'catalog' => $this->programCatalog(),

            /* อีเวนท์ที่เลือกเป็นแม่ได้ — ไม่รวมตัวเอง กันชี้หาตัวเอง */
            'events' => Activity::selectableEvents($activity->id)->get(['id', 'name']),
            'evaluationForms' => $evaluationForms,
            'qrCodes' => $this->qrPayload($activity),

            /* สองชุดนี้ยังไม่มีตารางในฐาน — docs/activity-module.md บรรทัด 84 ระบุว่า
               ประเภทกิจกรรมไม่ต้องมีหน้าจอจัดการ จึงเก็บเป็น config ไปก่อน
               ถ้าภายหลังทำเป็นตาราง ให้เปลี่ยนมาอ่านจากตารางที่นี่จุดเดียว */
            'types' => config('farmconcept.activity_types'),
            'venueModes' => config('farmconcept.venue_modes'),
            'lookup' => $this->lookupTables(),
            'current' => $this->currentPayload($activity),
            'coverMaxBytes' => $this->coverMaxBytes(),
        ]);
    }

    /**
     * เพดานขนาดรูปปกที่อัปได้จริงบนเครื่องนี้ (ไบต์)
     *
     * กฎของแอปคือ 5MB แต่ PHP ตัดไฟล์ทิ้งตั้งแต่ก่อนถึง Laravel ถ้าเกิน upload_max_filesize
     * หรือ post_max_size — ผลคือ Laravel เห็นเป็น "ไม่ได้แนบไฟล์มา" แล้วฟ้องผิดเรื่อง
     * ส่งค่าที่เล็กที่สุดในสามค่าไปให้หน้าจอ เพื่อบอกผู้ใช้ตรง ๆ ก่อนจะส่งไฟล์ที่ไม่มีทางผ่าน
     */
    private function coverMaxBytes(): int
    {
        $toBytes = function (string $value): int {
            $value = trim($value);
            $unit = strtolower(substr($value, -1));
            $number = (int) $value;

            return match ($unit) {
                'g' => $number * 1024 ** 3,
                'm' => $number * 1024 ** 2,
                'k' => $number * 1024,
                default => $number,
            };
        };

        return min(
            self::COVER_MAX_KB * 1024,
            $toBytes(ini_get('upload_max_filesize') ?: '2M'),
            $toBytes(ini_get('post_max_size') ?: '8M'),
        );
    }

    /**
     * โปรแกรม → หลักสูตร → วิทยากร ในรูปแบบที่ฟอร์มใช้เลือกแบบต่อเนื่อง
     *
     * วิทยากรของแต่ละหลักสูตรมาจาก mst_instructor_course ไม่ใช่รายชื่อวิทยากรทั้งหมด
     * เพื่อให้เลือกได้เฉพาะคนที่สอนหลักสูตรนั้นจริง
     *
     * @return array<int, array<string, mixed>>
     */
    private function programCatalog(): array
    {
        return Program::active()
            ->with(['courses.instructors:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Program $program) => [
                'program' => $program->name,
                'courses' => $program->courses->map(fn (Course $course) => [
                    'name' => $course->name,
                    /* ส่ง id มากับชื่อเลย ไม่ให้หน้าจอต้องนำชื่อวิทยากรไปค้นหา id อีกรอบ
                       ชื่อแก้ไขได้และอาจมีช่องว่างต่างกัน แต่ id เป็นตัวอ้างอิงจริงของฐานข้อมูล */
                    'teachers' => $course->instructors
                        ->map(fn (Instructor $instructor) => [
                            'id' => $instructor->id,
                            'name' => $instructor->name,
                        ])
                        ->values()
                        ->all(),
                ])->all(),
            ])
            ->all();
    }

    /**
     * ตารางแปลงชื่อ → id ของทุก master data ที่ฟอร์มใช้
     *
     * @return array<string, mixed>
     */
    private function lookupTables(): array
    {
        return [
            'areas' => Area::pluck('id', 'name'),
            'targetGroups' => TargetGroup::pluck('id', 'name'),
            'instructors' => Instructor::pluck('id', 'name'),
            'formats' => ActivityFormat::pluck('id', 'name'),
            'events' => Activity::where('type', Activity::TYPE_EVENT)->pluck('id', 'name'),
            'courses' => Course::get(['id', 'name', 'program_id'])
                ->keyBy('name')
                ->map(fn (Course $c) => ['id' => $c->id, 'program_id' => $c->program_id]),
        ];
    }

    /**
     * ค่าปัจจุบันในรูปแบบเดียวกับที่ ActivityRequest รับ
     *
     * @return array<string, mixed>
     */
    private function currentPayload(Activity $activity): array
    {
        $registrationForm = $activity->forms->first(fn (Form $form) => $form->pivot->slot === 'registration');
        $postSurveyForm = $activity->forms->first(fn (Form $form) => $form->pivot->slot === 'post_survey');

        return [
            'name' => $activity->name,
            'description' => $activity->description,
            'type' => $activity->type,
            'participant_type' => $activity->participant_type,
            'parent_event_id' => $activity->parent_event_id,
            'status' => $activity->status,
            'visibility' => $activity->visibility,
            'program_id' => $activity->program_id,
            'course_id' => $activity->course_id,
            'format_id' => $activity->format_id,
            'venue_mode' => $activity->venue_mode,
            'data_source' => $activity->data_source,
            'organizer' => $activity->organizer,
            'capacity' => $activity->capacity,
            'has_fee' => $activity->has_fee,
            'fee' => (float) $activity->fee,
            'requires_registration' => $activity->requires_registration,
            'requires_checkin' => $activity->requires_checkin,
            'has_post_survey' => $activity->has_post_survey,
            'registration_form_id' => $registrationForm?->id,
            'post_survey_form_id' => $postSurveyForm?->id,
            'is_published' => $activity->is_published,
            'is_featured' => $activity->is_featured,
            'start_date' => $activity->start_date?->toDateString(),
            'end_date' => $activity->end_date?->toDateString(),
            'checkin_start_at' => $activity->checkin_start_at?->format('Y-m-d H:i:s'),
            'checkin_end_at' => $activity->checkin_end_at?->format('Y-m-d H:i:s'),
            'survey_start_at' => $activity->survey_start_at?->format('Y-m-d H:i:s'),
            'survey_end_at' => $activity->survey_end_at?->format('Y-m-d H:i:s'),
            'publish_start_at' => $activity->publish_start_at?->format('Y-m-d H:i:s'),
            'publish_end_at' => $activity->publish_end_at?->format('Y-m-d H:i:s'),
            'registration_start_at' => $activity->registration_start_at?->format('Y-m-d H:i:s'),
            'registration_end_at' => $activity->registration_end_at?->format('Y-m-d H:i:s'),
            'public_sort_order' => $activity->public_sort_order,
            'area_ids' => $activity->areas->pluck('id'),
            'instructor_ids' => $activity->instructors->pluck('id'),
            'target_group_ids' => $activity->targetGroups->pluck('id'),
        ];
    }

    /** รูปแบบที่ activity-create.js คาดไว้ตอนเติมค่าลงฟอร์ม (โหมดแก้ไข) */
    private function toFormRow(Activity $activity): array
    {
        return $this->toListRow($activity) + [
            'description' => $activity->description,
            'parentEventName' => $activity->parentEvent?->name,
            'course' => $activity->course?->name,
            'targetGroups' => $activity->targetGroups->pluck('name')->all(),
            'isPublished' => $activity->is_published,
            'isFeatured' => $activity->is_featured,
            'tags' => $activity->format ? [$activity->format->name] : [],
            'area' => $activity->areas->first()?->name,
            'evaluationFormIds' => $activity->forms->pluck('id')->map(fn ($id) => (string) $id)->all(),
            'coverImage' => $activity->cover_image_path,
            'coverImageUrl' => $activity->cover_image_path
                ? route('admin.activities.cover.show', $activity->code)
                : null,

            /* สวิตช์จริงจากฐานข้อมูล — ฟอร์มเดิมต้องอนุมานเอาเองเพราะข้อมูลจำลองไม่มีสามฟิลด์นี้ */
            'joinFlags' => [
                'reg' => $activity->requires_registration,
                'chk' => $activity->requires_checkin,
                'survey' => $activity->has_post_survey,
            ],

            /* ช่วงเวลา — ฟอร์มผ่าเป็นช่องวันกับช่องเวลาเอง */
            'registrationStart' => $activity->registration_start_at?->format('Y-m-d H:i'),
            'registrationEnd' => $activity->registration_end_at?->format('Y-m-d H:i'),
            'checkinStart' => $activity->checkin_start_at?->format('Y-m-d H:i'),
            'checkinEnd' => $activity->checkin_end_at?->format('Y-m-d H:i'),
            'surveyStart' => $activity->survey_start_at?->format('Y-m-d H:i'),
            'surveyEnd' => $activity->survey_end_at?->format('Y-m-d H:i'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function qrPayload(Activity $activity): array
    {
        if (! $activity->exists) {
            return [];
        }

        $labels = [
            'public' => $activity->requires_registration ? 'ลงทะเบียนเข้าร่วม' : 'หน้ากิจกรรม',
            'checkin' => 'Check-in หน้างาน',
            'post_survey' => 'แบบประเมินหลังกิจกรรม',
        ];

        return $activity->qrCodes
            ->whereIn('purpose', array_keys($labels))
            ->sortBy(fn ($qr) => array_search($qr->purpose, array_keys($labels), true))
            ->map(fn ($qr) => [
                'purpose' => $qr->purpose,
                'label' => $labels[$qr->purpose],
                'url' => url($qr->target_url),
                'imageUrl' => route('admin.activities.qr.show', [$activity->code, $qr->purpose]),
                'downloadUrl' => route('admin.activities.qr.show', [$activity->code, $qr->purpose, 'download' => 1]),
                'active' => $qr->is_active,
            ])
            ->values()
            ->all();
    }

    /**
     * บันทึกการแก้ไขกิจกรรม
     *
     * การตรวจข้อมูลอยู่ที่ ActivityRequest ทั้งหมด (ฉบับร่างตรวจน้อย เผยแพร่ตรวจครบ)
     * ตัวงานอยู่ที่ Service เพราะต้องเขียน 6 ตารางใน transaction เดียว
     */
    public function update(ActivityRequest $request, Activity $activity, ActivityService $service): JsonResponse
    {
        $this->authorize('update', $activity);

        $updated = $service->update($activity, $request->validated(), $request->user());

        return response()->json([
            'message' => 'บันทึกกิจกรรม "'.$updated->name.'" แล้ว',
            'redirect' => route('admin.activities.index'),
            'activity' => $this->toListRow($updated->load(['program', 'format', 'areas', 'instructors'])->loadCount('registrations')),
        ]);
    }

    /** แสดงรูปผ่าน Laravel เพื่อให้ใช้งานบน IIS ได้โดยไม่ขึ้นกับ public/storage symlink */
    public function showCover(Activity $activity)
    {
        $this->authorize('update', $activity);

        abort_unless(
            $activity->cover_image_path
                && Storage::disk('public')->exists($activity->cover_image_path),
            404
        );

        return Storage::disk('public')->response(
            $activity->cover_image_path,
            null,
            ['Cache-Control' => 'private, max-age=300']
        );
    }

    /**
     * อัปโหลดรูปปกกิจกรรม
     *
     * แยกเป็น endpoint ของตัวเองแทนการส่งไฟล์มากับ PUT ที่บันทึกฟอร์ม
     * เพราะ PHP อ่าน multipart จาก PUT ไม่ได้ตรง ๆ และการอัปทันทีทำให้เห็นรูปพรีวิวได้เลย
     *
     * เก็บบน disk `public` เพราะรูปปกเป็นของสาธารณะ ผู้เข้าร่วมเห็นบนหน้ากิจกรรม
     * ต่างจากสลิปการชำระเงินที่ต้องเก็บนอก public แล้วเสิร์ฟผ่าน route ที่ตรวจสิทธิ์
     */
    public function uploadCover(Request $request, Activity $activity): JsonResponse
    {
        $this->authorize('update', $activity);

        $request->validate(
            ['cover' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::COVER_MAX_KB]],
            [],
            ['cover' => 'รูปภาพปก']
        );

        $file = $request->file('cover');
        if (! $file || ! $file->isValid()) {
            return response()->json([
                'message' => 'ไฟล์รูปภาพไม่ถูกต้อง หรือขนาดใหญ่เกินเพดานที่ PHP บนเซิร์ฟเวอร์รองรับ (upload_max_filesize)',
                'errors' => ['cover' => ['ไม่สามารถอ่านไฟล์รูปภาพได้ กรุณาตรวจสอบขนาดไฟล์หรือลองใหม่อีกครั้ง']],
            ], 422);
        }

        /* ลบไฟล์เดิมก่อน ไม่งั้นเปลี่ยนรูปหลายรอบจะเหลือไฟล์กำพร้าสะสมไปเรื่อย ๆ */
        $this->deleteCoverFile($activity);

        $path = $this->storeCoverFile($file);

        if (! $path) {
            return response()->json([
                'message' => 'ไม่สามารถบันทึกไฟล์รูปภาพลงในดิสก์ได้',
                'errors' => ['cover' => ['เกิดข้อผิดพลาดในการบันทึกรูปภาพ']],
            ], 500);
        }

        $activity->forceFill(['cover_image_path' => $path])->save();

        return response()->json([
            'message' => 'อัปโหลดรูปปกแล้ว',
            'path' => $path,
            'url' => route('admin.activities.cover.show', $activity->code),
            'label' => $file->getClientOriginalName()
                .' · '.round($file->getSize() / 1048576, 1).'MB',
        ]);
    }

    private function storeCoverFile(UploadedFile $file): ?string
    {
        if (! $file->isValid()) {
            return null;
        }

        $ext = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'png';
        $path = 'activity-covers/'.Str::random(40).'.'.strtolower($ext);

        $tmpPath = $file->getRealPath() ?: $file->getPathname();

        if (! empty($tmpPath) && file_exists($tmpPath)) {
            $contents = @file_get_contents($tmpPath);
            if ($contents !== false) {
                Storage::disk('public')->put($path, $contents);

                return $path;
            }
        }

        return $file->store('activity-covers', 'public');
    }

    /** ลบรูปปก — ลบทั้งไฟล์และค่าในฐาน ไม่ปล่อยให้ไฟล์ค้าง */
    public function deleteCover(Activity $activity): JsonResponse
    {
        $this->authorize('update', $activity);

        $this->deleteCoverFile($activity);
        $activity->forceFill(['cover_image_path' => null])->save();

        return response()->json(['message' => 'ลบรูปปกแล้ว']);
    }

    private function deleteCoverFile(Activity $activity): void
    {
        if ($activity->cover_image_path) {
            Storage::disk('public')->delete($activity->cover_image_path);
        }
    }

    /**
     * ลบกิจกรรม
     *
     * ตรวจสิทธิ์ผ่าน ActivityPolicy — ล้มเหลวจะได้ 403 พร้อมเหตุผลที่แสดงให้ผู้ใช้อ่านได้
     * ตัวงานจริงอยู่ที่ Service เพราะมีสองขั้นตอนที่ต้องสำเร็จพร้อมกัน (ลบ + บันทึก log)
     */
    public function destroy(Request $request, Activity $activity, ActivityService $service): JsonResponse
    {
        $this->authorize('delete', $activity);

        $service->delete($activity, $request->user());

        return response()->json([
            'message' => 'ลบกิจกรรม "'.$activity->name.'" แล้ว',
        ]);
    }

    /**
     * แปลงเป็นรูปแบบที่สคริปต์หน้าจอเดิมอ่านได้
     *
     * ชื่อคีย์เป็น camelCase ตามที่ assets/js/activity-module.js คาดไว้ ไม่ใช่ snake_case ของฐานข้อมูล
     * เป็นสะพานชั่วคราวระหว่างการย้าย — เมื่อเขียน UI ใหม่เป็น Blade เต็มตัวแล้วชั้นนี้จะถูกถอดออก
     *
     * @return array<string, mixed>
     */
    private function toListRow(Activity $activity, ?int $publicRank = null): array
    {
        return [
            'id' => $activity->code,
            /* สถานะบนหน้าเว็บสาธารณะ — publicRank มีค่าเฉพาะตอนหน้ารายการส่งมาให้
               (คำนวณจากรายการทั้งชุด) ส่วนเหตุผลที่ไม่แสดงอ่านได้จากตัวกิจกรรมเอง */
            'publicRank' => $publicRank,
            'publicSortOrder' => (int) $activity->public_sort_order,
            'publicHiddenReason' => $publicRank === null ? $this->publicHiddenReason($activity) : null,
            'name' => $activity->name,
            'type' => $activity->type,
            'status' => $activity->status,
            'capacity' => $activity->capacity,
            'registered' => $activity->registrations_count,
            'startDate' => $activity->start_date?->toDateString(),
            'endDate' => $activity->end_date?->toDateString(),
            'hasFee' => $activity->has_fee,
            'fee' => (float) $activity->fee,
            'areaList' => $activity->areas->pluck('name')->all(),
            'instructorList' => $activity->instructors->pluck('name')->all(),
            'program' => $activity->program?->name,
            'format' => $activity->format?->name,
            'parentEventName' => $activity->parentEvent?->name,

            /* ตารางแสดง "ชื่อคนแก้" กับ "วันที่ | เวลา" เหมือนหน้าอื่นในระบบ
               updatedAt ยังเป็น ISO เต็มเพราะตัวเรียงลำดับฝั่งหน้าจอใช้เทียบข้อความ */
            'updatedBy' => $activity->updatedBy?->name,
            'updatedAt' => $activity->updated_at?->toIso8601String(),
            'updatedDate' => $activity->updated_at?->toDateString(),
            'updatedTime' => $activity->updated_at?->format('H.i'),
        ];
    }

    /**
     * เหตุผลที่กิจกรรมไม่แสดงบนหน้าเว็บสาธารณะ — เรียงตามเงื่อนไขของ scopeForPublicListing
     *
     * ไล่เช็คเงื่อนไขเดียวกับที่หน้าเว็บใช้กรองทีละข้อ แล้วบอกข้อแรกที่ไม่ผ่าน
     * เพื่อให้แอดมินรู้ว่าต้องแก้อะไรกิจกรรมถึงจะโผล่ ไม่ใช่แค่รู้ว่า "ไม่แสดง"
     */
    private function publicHiddenReason(Activity $activity): string
    {
        $now = now();

        return match (true) {
            ! $activity->is_published => 'ยังไม่ได้เผยแพร่',
            $activity->visibility !== 'สาธารณะ' => 'การมองเห็นไม่ใช่สาธารณะ',
            $activity->publish_start_at && $activity->publish_start_at->gt($now) => 'ยังไม่ถึงช่วงเผยแพร่',
            $activity->publish_end_at && $activity->publish_end_at->lt($now) => 'พ้นช่วงเผยแพร่แล้ว',
            (int) $activity->public_sort_order <= 0 => 'ยังไม่กำหนดลำดับแสดง',
            default => 'ไม่เข้าเงื่อนไขการแสดง',
        };
    }

    /**
     * รอบกิจกรรมในรูปแบบที่ TFC.activity.schedules() อ่านได้ — คีย์ time เป็น "09:00 - 12:00"
     *
     * @return array<int, array<string, mixed>>
     */
    private function toSessions(Activity $activity): array
    {
        return $activity->rounds->map(fn ($round) => [
            'date' => $round->round_date->toDateString(),
            'time' => substr((string) $round->time_start, 0, 5).' - '.substr((string) $round->time_end, 0, 5),
            'location' => $round->location,
            'capacity' => $round->capacity,
        ])->all();
    }
}
