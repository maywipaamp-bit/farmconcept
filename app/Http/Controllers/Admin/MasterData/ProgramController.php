<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Models\Course;
use App\Models\Program;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ProgramController extends MasterDataController
{
    protected function model(): string
    {
        return Program::class;
    }

    protected function view(): string
    {
        return 'admin.master.programs';
    }

    protected function label(): string
    {
        return 'โปรแกรมการเรียนรู้';
    }

    protected function codePrefix(): string
    {
        return 'PROG';
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
        return Program::query()
            ->with([
                'courses:id,program_id,name,sort_order',
                'updatedBy:id,name',
            ])
            ->withCount([
                'activities',
                'courses as used_courses_count' => fn ($query) => $query
                    ->where(fn ($course) => $course->has('activities')->orHas('instructors')),
            ])
            ->orderByDesc('id');
    }

    protected function rules(?Model $current): array
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('mst_programs', 'name')->ignore($current?->id)],
            'active' => ['required', 'boolean'],

            /* หลักสูตรส่งมาเป็นรายชื่อตามลำดับที่เห็นบนหน้าจอ — ลำดับคือความหมาย ไม่ใช่แค่การจัดเรียง */
            'courses' => ['present', 'array', 'max:50'],
            'courses.*' => ['required', 'string', 'max:150', 'distinct'],
        ];
    }

    protected function attributes(): array
    {
        return ['name' => 'ชื่อโปรแกรม', 'active' => 'สถานะ', 'courses' => 'หลักสูตร', 'courses.*' => 'ชื่อหลักสูตร'];
    }

    protected function columns(array $data): array
    {
        return ['name' => $data['name'], 'is_active' => $data['active'], 'updated_by' => auth()->id()];
    }

    /**
     * หลักสูตรแก้พร้อมโปรแกรมในโมดัลเดียว จึงต้องเทียบของเดิมกับของใหม่เอง
     *
     * จับคู่ด้วย "ชื่อ" ไม่ใช่ลำดับ เพราะหลักสูตรที่ถูกอ้างอิงจากกิจกรรมและวิทยากรอยู่แล้ว
     * ต้องคง id เดิมไว้ ถ้าลบทิ้งแล้วสร้างใหม่ทุกครั้ง กิจกรรมที่ผูกไว้จะขาดทันที
     */
    protected function syncRelations(Model $record, array $data): void
    {
        $wanted = array_values($data['courses']);
        $existing = $record->courses()->get()->keyBy('name');

        foreach ($wanted as $order => $name) {
            $course = $existing->get($name);

            if ($course) {
                $course->update(['sort_order' => $order + 1]);

                continue;
            }

            $record->courses()->create(['name' => $name, 'sort_order' => $order + 1]);
        }

        /* หลักสูตรที่ถูกเอาออกจากรายการ — ลบได้เฉพาะที่ยังไม่มีใครใช้
           ที่ถูกใช้อยู่ปล่อยไว้ตามเดิม ไม่งั้นกิจกรรมเก่าจะชี้ไปหาความว่าง */
        $removed = $existing->reject(fn (Course $c) => in_array($c->name, $wanted, true));

        foreach ($removed as $course) {
            if ($course->activities()->exists() || $course->instructors()->exists()) {
                continue;
            }

            $course->delete();
        }
    }

    protected function toRow(Model $record): array
    {
        return [
            'id' => $record->code,
            'name' => $record->name,
            'category' => $record->category,
            'activityCount' => $record->activities_count,
            'deleteUsageCount' => $record->activities_count + $record->used_courses_count,
            'active' => $record->is_active,
            'courses' => $record->courses->map(fn (Course $c) => ['order' => $c->sort_order, 'name' => $c->name])->values(),
            /* ตารางแสดง "ชื่อคนแก้" กับ "วันที่ | เวลา" — แถวที่มีอยู่ก่อนระบบเก็บข้อมูลนี้
               จะไม่มีชื่อ หน้าจอแสดงขีดแทนจนกว่าจะมีคนแก้ครั้งถัดไป */
            'updatedBy' => $record->updatedBy?->name,
            'updatedAt' => $record->updated_at?->toDateString(),
            'updatedTime' => $record->updated_at?->format('H.i'),
        ];
    }

    protected function blockedFromDelete(Model $record): ?string
    {
        $activities = $record->activities()->count();

        if ($activities > 0) {
            return 'โปรแกรมนี้ถูกใช้อยู่กับกิจกรรม '.$activities.' รายการ ลบไม่ได้ '
                .'ถ้าไม่ต้องการให้เลือกได้อีก ให้เปลี่ยนสถานะเป็น "ไม่ใช้งาน" แทน';
        }

        /* หลักสูตรในโปรแกรมอาจถูกกิจกรรมอ้างอิงอยู่แม้ตัวโปรแกรมจะไม่ถูกอ้าง */
        $usedCourses = $record->courses()
            ->where(fn ($q) => $q->has('activities')->orHas('instructors'))
            ->count();

        return $usedCourses === 0
            ? null
            : 'โปรแกรมนี้มีหลักสูตรที่ถูกใช้อยู่ '.$usedCourses.' หลักสูตร ลบไม่ได้ '
              .'ให้ย้ายหรือลบหลักสูตรเหล่านั้นก่อน';
    }
}
