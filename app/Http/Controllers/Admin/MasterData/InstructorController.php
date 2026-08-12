<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Models\Activity;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\Program;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InstructorController extends MasterDataController
{
    /** เพดานรูปวิทยากรตามกฎของแอป — ใช้ทั้งตอน validate และตอนบอกหน้าจอ */
    private const PHOTO_MAX_KB = 5120;

    protected function model(): string
    {
        return Instructor::class;
    }

    protected function view(): string
    {
        return 'admin.master.instructors';
    }

    protected function label(): string
    {
        return 'วิทยากร';
    }

    protected function codePrefix(): string
    {
        return 'INS';
    }

    /**
     * เรียงจากรายการที่เพิ่มล่าสุดลงมา — ของใหม่อยู่บนสุดเสมอ
     *
     * ใช้ id ไม่ใช่ updated_at เพราะสองอย่างนี้ให้ผลต่างกันตอนแก้ไข
     * id คงที่ตลอดอายุของแถว แก้ข้อมูลแล้วลำดับจึงไม่ขยับ คนที่ไล่แก้ทีละแถวไม่เสียตำแหน่ง
     * ส่วน updated_at จะดีดแถวที่เพิ่งบันทึกขึ้นบนสุดทุกครั้ง
     */
    protected function query()
    {
        return Instructor::query()
            ->with([
                'expertises:id,instructor_id,name',
                'courses:id,name',
                'updatedBy:id,name',

                /* ประวัติการเป็นวิทยากรส่งไปกับแถวเลย จึงต้องโหลดพร้อมกันทั้งชุด
                   ไม่งั้นจะยิง query ต่อวิทยากรหนึ่งคน แล้วต่ออีกครั้งเพื่อนับผู้ลงทะเบียน */
                'activities' => fn ($query) => $query->withCount('registrations')->orderByDesc('start_date'),
            ])
            ->withCount('activities')
            ->orderByDesc('id');
    }

    /**
     * หลักสูตรที่เลือกได้ จัดกลุ่มตามโปรแกรม
     *
     * ของเดิมหน้าจออ่านจาก mock.programs ซึ่งเป็นคนละชุดกับฐานข้อมูล
     */
    protected function viewData(): array
    {
        return [
            'programs' => Program::with('courses:id,program_id,name,sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (Program $p) => ['name' => $p->name, 'courses' => $p->courses->pluck('name')->values()]),

            'photoMaxBytes' => self::photoMaxBytes(),
        ];
    }

    protected function rules(?Model $current): array
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('mst_instructors', 'name')->ignore($current?->id)],
            'phone' => ['required', 'string', 'max:30'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'active' => ['required', 'boolean'],

            'expertiseList' => ['present', 'array', 'max:20'],
            'expertiseList.*' => ['required', 'string', 'max:100', 'distinct'],

            /* หลักสูตรเลือกจากที่มีอยู่จริงเท่านั้น — หน้าจอส่งมาเป็นชื่อ ไม่ใช่ id */
            'courses' => ['present', 'array', 'max:50'],
            'courses.*' => ['required', 'string', 'distinct', Rule::exists('mst_courses', 'name')],
        ];
    }

    protected function attributes(): array
    {
        return [
            'name' => 'ชื่อวิทยากร',
            'phone' => 'เบอร์โทร',
            'bio' => 'รายละเอียด',
            'active' => 'สถานะ',
            'expertiseList' => 'ความเชี่ยวชาญ',
            'expertiseList.*' => 'ความเชี่ยวชาญ',
            'courses' => 'หลักสูตรที่สอน',
            'courses.*' => 'หลักสูตรที่สอน',
        ];
    }

    protected function columns(array $data): array
    {
        return [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'bio' => $data['bio'] ?? null,
            'is_active' => $data['active'],
            'updated_by' => auth()->id(),

            /* คอลัมน์ expertise เป็นข้อความสรุปที่หน้าอื่นแสดงเป็นบรรทัดเดียว
               ส่วนรายการแยกอยู่ที่ mst_instructor_expertises — เขียนทั้งสองที่ให้ตรงกันเสมอ */
            'expertise' => implode(' · ', $data['expertiseList']) ?: null,
        ];
    }

    /**
     * ความเชี่ยวชาญเขียนทับได้ทั้งชุด เพราะไม่มีใครอ้างอิงถึงมัน
     * หลักสูตรที่สอนเป็นตารางเชื่อม จึงใช้ sync ตามปกติ
     */
    protected function syncRelations(Model $record, array $data): void
    {
        $record->expertises()->delete();

        foreach ($data['expertiseList'] as $name) {
            $record->expertises()->create(['name' => $name]);
        }

        $record->courses()->sync(Course::whereIn('name', $data['courses'])->pluck('id'));
    }

    protected function toRow(Model $record): array
    {
        return [
            'id' => $record->code,
            'name' => $record->name,
            'phone' => $record->phone,
            'photo' => $record->photo_path ? Storage::url($record->photo_path) : '',
            'expertise' => $record->expertise,
            'expertiseList' => $record->expertises->pluck('name')->values(),
            'courses' => $record->courses->pluck('name')->values(),
            'bio' => $record->bio,
            'activityCount' => $record->activities_count,
            'active' => $record->is_active,
            /* ตารางแสดง "ชื่อคนแก้" กับ "วันที่ | เวลา" — แถวที่มีอยู่ก่อนระบบเก็บข้อมูลนี้
               จะไม่มีชื่อ หน้าจอแสดงขีดแทนจนกว่าจะมีคนแก้ครั้งถัดไป */
            'updatedBy' => $record->updatedBy?->name,
            'updatedAt' => $record->updated_at?->toDateString(),
            'updatedTime' => $record->updated_at?->format('H.i'),

            /* ประวัติมาจากกิจกรรมจริงในฐาน ไม่ใช่ข้อมูลที่กรอกเอง
               ส่งมากับแถวเลย เพราะโมดัลต้องแสดงทันทีที่เปิด ไม่ควรรอคำขออีกรอบ */
            'history' => $record->activities
                ->map(fn (Activity $a) => [
                    'name' => $a->name,
                    'startDate' => $a->start_date?->toDateString(),
                    'status' => $a->status,
                    'registered' => $a->registrations_count,
                ])
                ->values(),
        ];
    }

    protected function blockedFromDelete(Model $record): ?string
    {
        $count = $record->activities()->count();

        return $count === 0
            ? null
            : 'วิทยากรท่านนี้อยู่ในกิจกรรม '.$count.' รายการ ลบไม่ได้ '
              .'ถ้าไม่ต้องการให้เลือกได้อีก ให้เปลี่ยนสถานะเป็น "ไม่ใช้งาน" แทน';
    }

    /**
     * รูปวิทยากร — แยก endpoint เหมือนรูปปกกิจกรรม
     *
     * PHP อ่าน multipart จาก PUT ไม่ได้ตรง ๆ และการอัปทันทีทำให้เห็นตัวอย่างได้เลย
     * เก็บบน disk `public` เพราะรูปวิทยากรแสดงบนหน้ากิจกรรมที่ผู้เข้าร่วมเห็น
     */
    public function uploadPhoto(Request $request, string $code): JsonResponse
    {
        $instructor = $this->find($code);

        $request->validate(
            ['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::PHOTO_MAX_KB]],
            [],
            ['photo' => 'รูปวิทยากร']
        );

        /* ลบไฟล์เดิมก่อน ไม่งั้นเปลี่ยนรูปหลายรอบจะเหลือไฟล์กำพร้าสะสมไปเรื่อย ๆ */
        $this->deletePhotoFile($instructor);

        $path = $request->file('photo')->store('instructor-photos', 'public');
        $instructor->forceFill(['photo_path' => $path])->save();

        return response()->json(['message' => 'อัปโหลดรูปวิทยากรแล้ว', 'url' => Storage::url($path)]);
    }

    public function deletePhoto(string $code): JsonResponse
    {
        $instructor = $this->find($code);

        $this->deletePhotoFile($instructor);
        $instructor->forceFill(['photo_path' => null])->save();

        return response()->json(['message' => 'ลบรูปวิทยากรแล้ว']);
    }

    /**
     * ลบไฟล์รูปเดิมออกจาก disk
     *
     * ตรวจ path ซ้ำก่อนลบแม้ค่าจะมาจากฐานข้อมูลของเราเอง — ถ้าวันหนึ่งมีทางเขียนคอลัมน์นี้
     * จากข้อมูลภายนอกได้ การลบไฟล์นอกโฟลเดอร์ที่ตั้งใจจะกลายเป็นช่องโหว่ทันที
     */
    private function deletePhotoFile(Instructor $instructor): void
    {
        if (! $instructor->photo_path || ! str_starts_with($instructor->photo_path, 'instructor-photos/')) {
            return;
        }

        Storage::disk('public')->delete($instructor->photo_path);
    }

    /** เพดานขนาดรูปที่อัปได้จริงบนเครื่องนี้ — ค่าที่เล็กที่สุดระหว่างกฎแอปกับ php.ini */
    public static function photoMaxBytes(): int
    {
        $toBytes = function (string $value): int {
            $unit = strtolower(substr(trim($value), -1));
            $number = (int) $value;

            return match ($unit) {
                'g' => $number * 1024 ** 3,
                'm' => $number * 1024 ** 2,
                'k' => $number * 1024,
                default => $number,
            };
        };

        return min(
            self::PHOTO_MAX_KB * 1024,
            $toBytes(ini_get('upload_max_filesize') ?: '2M'),
            $toBytes(ini_get('post_max_size') ?: '8M'),
        );
    }
}
