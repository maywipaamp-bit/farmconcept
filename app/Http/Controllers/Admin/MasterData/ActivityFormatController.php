<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Models\ActivityFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ActivityFormatController extends MasterDataController
{
    protected function model(): string
    {
        return ActivityFormat::class;
    }

    protected function view(): string
    {
        return 'admin.master.activity-formats';
    }

    protected function label(): string
    {
        return 'หมวดหมู่กิจกรรม';
    }

    protected function codePrefix(): string
    {
        return 'FMT';
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
        return ActivityFormat::query()->with('updatedBy:id,name')->withCount('activities')->orderByDesc('id');
    }

    protected function rules(?Model $current): array
    {
        return [
            'name' => ['required', 'string', 'max:60', Rule::unique('mst_activity_formats', 'name')->ignore($current?->id)],

            /* ไอคอนต้องเป็นชื่อที่ activityCategoryIcons รู้จัก ไม่งั้นชิปหมวดหมู่ในฟอร์มกิจกรรมจะว่าง */
            'icon' => ['required', 'string', Rule::in(config('farmconcept.category_icons'))],
            'active' => ['required', 'boolean'],
        ];
    }

    protected function attributes(): array
    {
        return ['name' => 'ชื่อหมวดหมู่', 'icon' => 'ไอคอน', 'active' => 'สถานะ'];
    }

    protected function columns(array $data): array
    {
        return [
            'name' => $data['name'],
            'icon' => $data['icon'],
            'is_active' => $data['active'],
            'updated_by' => auth()->id(),
        ];
    }

    protected function toRow(Model $record): array
    {
        return [
            'id' => $record->code,
            'name' => $record->name,
            'icon' => $record->icon,
            'activityCount' => $record->activities_count,
            'active' => $record->is_active,
            /* ตารางแสดง "ชื่อคนแก้" กับ "วันที่ | เวลา" — แถวที่มีอยู่ก่อนระบบเก็บข้อมูลนี้
               จะไม่มีชื่อ หน้าจอแสดงขีดแทนจนกว่าจะมีคนแก้ครั้งถัดไป */
            'updatedBy' => $record->updatedBy?->name,
            'updatedAt' => $record->updated_at?->toDateString(),
            'updatedTime' => $record->updated_at?->format('H.i'),
        ];
    }

    protected function blockedFromDelete(Model $record): ?string
    {
        $count = $record->activities()->count();

        return $count === 0
            ? null
            : 'หมวดหมู่นี้ถูกใช้อยู่กับกิจกรรม '.$count.' รายการ ลบไม่ได้ '
              .'ถ้าไม่ต้องการให้เลือกได้อีก ให้เปลี่ยนสถานะเป็น "ไม่ใช้งาน" แทน';
    }
}
