<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Models\Activity;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\InstructorExpertise;
use App\Models\Program;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

            /* ใช้เป็นรายการแนะนำในช่อง Tag ความเชี่ยวชาญ
               ผู้ใช้ยังพิมพ์ค่าใหม่ได้ แต่ข้อมูลเดิมจะเลือกซ้ำได้โดยไม่สะกดต่างกัน */
            'expertiseOptions' => InstructorExpertise::query()
                ->select('name')
                ->distinct()
                ->orderBy('name')
                ->pluck('name')
                ->values(),

            'photoMaxBytes' => self::photoMaxBytes(),
        ];
    }

    protected function rules(?Model $current): array
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('mst_instructors', 'name')->ignore($current?->id)],
            /* ไม่บังคับ — วิทยากรบางคนติดต่อผ่านหน่วยงานต้นสังกัด ไม่ได้ให้เบอร์ส่วนตัวไว้
               บังคับกรอกทำให้เจ้าหน้าที่ต้องใส่เลขมั่ว ๆ ลงไปเพื่อให้บันทึกผ่าน */
            'phone' => ['nullable', 'string', 'max:30'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'active' => ['required', 'boolean'],

            'searchTags' => ['present', 'array', 'max:20'],
            'searchTags.*' => ['required', 'string', 'max:100', 'distinct'],

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
            'searchTags' => 'คำค้นหา (Tag)',
            'searchTags.*' => 'คำค้นหา (Tag)',
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
            'phone' => $data['phone'] ?? null,
            'bio' => $data['bio'] ?? null,
            'search_tags' => $data['searchTags'] ?: null,
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
        $photoUrl = '';
        if ($record->photo_path) {
            $path = $record->photo_path;
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                $photoUrl = $path;
            } elseif (str_starts_with($path, '/storage/')) {
                $photoUrl = $path;
            } elseif (str_starts_with($path, 'storage/')) {
                $photoUrl = '/'.$path;
            } else {
                $photoUrl = Storage::url($path);
            }
        }

        return [
            'id' => $record->code,
            'name' => $record->name,
            'phone' => $record->phone,
            'photo' => $photoUrl,
            'expertise' => $record->expertise,
            'expertiseList' => $record->expertises->pluck('name')->values(),
            'courses' => $record->courses->pluck('name')->values(),
            'searchTags' => $record->search_tags ?? [],
            'bio' => $record->bio,
            'activityCount' => $record->activities_count,
            'deleteUsageCount' => $record->activities_count,
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
            ['photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::PHOTO_MAX_KB]],
            [],
            ['photo' => 'รูปวิทยากร']
        );

        $file = $request->file('photo');
        if (! $file || ! $file->isValid()) {
            return response()->json([
                'message' => 'ไฟล์รูปภาพไม่ถูกต้อง หรือขนาดใหญ่เกินเพดานที่ PHP บนเซิร์ฟเวอร์รองรับ (upload_max_filesize)',
                'errors' => ['photo' => ['ไม่สามารถอ่านไฟล์รูปภาพได้ กรุณาตรวจสอบขนาดไฟล์หรือลองใหม่อีกครั้ง']],
            ], 422);
        }

        /* ลบไฟล์เดิมก่อน ไม่งั้นเปลี่ยนรูปหลายรอบจะเหลือไฟล์กำพร้าสะสมไปเรื่อย ๆ */
        $this->deletePhotoFile($instructor);

        $path = $this->storePhotoFile($file);

        if (! $path) {
            return response()->json([
                'message' => 'ไม่สามารถบันทึกไฟล์รูปภาพลงในดิสก์ได้',
                'errors' => ['photo' => ['เกิดข้อผิดพลาดในการบันทึกรูปภาพ']],
            ], 500);
        }

        $instructor->forceFill(['photo_path' => $path])->save();

        return response()->json(['message' => 'อัปโหลดรูปวิทยากรแล้ว', 'url' => Storage::url($path)]);
    }

    /**
     * บันทึกไฟล์รูปวิทยากรลงในดิสก์ public อย่างปลอดภัย
     *
     * บน Windows IIS / PHP บางสภาพแวดล้อม getRealPath() อาจคืนค่าว่างหรือ false
     * ส่งผลให้ store() เรียก fopen("") แล้วเกิด ValueError: Path must not be empty
     * ฟังก์ชันนี้จึงอ่านไฟล์ผ่าน getPathname() หรือ file_get_contents เป็น fallback ก่อน
     */
    private function storePhotoFile(UploadedFile $file): ?string
    {
        if (! $file->isValid()) {
            return null;
        }

        $ext = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'png';
        $path = 'instructor-photos/'.Str::random(40).'.'.strtolower($ext);

        $tmpPath = $file->getRealPath() ?: $file->getPathname();

        if (! empty($tmpPath) && file_exists($tmpPath)) {
            $contents = @file_get_contents($tmpPath);
            if ($contents !== false) {
                Storage::disk('public')->put($path, $contents);

                return $path;
            }
        }

        return $file->store('instructor-photos', 'public');
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
        if (! $instructor->photo_path) {
            return;
        }

        $path = $instructor->photo_path;
        if (str_starts_with($path, '/storage/')) {
            $path = substr($path, 9);
        } elseif (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }

        if (str_starts_with($path, 'instructor-photos/') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
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
