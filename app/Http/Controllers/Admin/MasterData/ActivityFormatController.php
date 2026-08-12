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
     * เรียงตามลำดับที่เพิ่มเข้ามา ไม่ใช่ตามเวลาที่แก้ล่าสุด
     *
     * ถ้าเรียงตามเวลาแก้ แถวที่เพิ่งบันทึกจะกระโดดไปบนสุด คนที่กำลังไล่แก้ทีละแถว
     * จะเสียตำแหน่งที่ค้างไว้ทุกครั้งแล้วต้องหาใหม่ว่าทำถึงไหน
     */
    protected function query()
    {
        return ActivityFormat::query()->withCount('activities')->orderBy('id');
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
            'updatedAt' => $record->updated_at?->toDateString(),
        ];
    }

    protected function blockedFromDelete(Model $record): ?string
    {
        $count = $record->activities()->count();

        return $count === 0
            ? null
            : 'หมวดหมู่นี้ถูกใช้อยู่กับกิจกรรม ' . $count . ' รายการ ลบไม่ได้ '
              . 'ถ้าไม่ต้องการให้เลือกได้อีก ให้เปลี่ยนสถานะเป็น "ไม่ใช้งาน" แทน';
    }
}
