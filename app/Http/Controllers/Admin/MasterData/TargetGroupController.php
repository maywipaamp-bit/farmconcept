<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Models\TargetGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class TargetGroupController extends MasterDataController
{
    protected function model(): string
    {
        return TargetGroup::class;
    }

    protected function view(): string
    {
        return 'admin.master.target-groups';
    }

    protected function label(): string
    {
        return 'กลุ่มเป้าหมาย';
    }

    protected function codePrefix(): string
    {
        return 'TG';
    }

    /**
     * บันทึกแล้วต้องเห็นแถวนั้นบนสุดทันทีโดยไม่ต้องไปหา
     * จึงเรียงตามเวลาที่แก้ล่าสุด และใช้ id ตัดสินแถวที่แก้ในวินาทีเดียวกัน
     */
    protected function query()
    {
        /* หน้าจอแสดงจำนวนกิจกรรมที่ใช้กลุ่มนี้ — นับด้วย withCount ไม่งั้นจะยิง query ต่อแถว */
        return TargetGroup::query()->withCount('activities')->orderByDesc('updated_at')->orderByDesc('id');
    }

    protected function rules(?Model $current): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('mst_target_groups', 'name')->ignore($current?->id)],
            'targetCount' => ['required', 'integer', 'min:0', 'max:1000000'],
            'active' => ['required', 'boolean'],
        ];
    }

    protected function attributes(): array
    {
        return ['name' => 'ชื่อกลุ่มเป้าหมาย', 'targetCount' => 'จำนวนเป้าหมาย', 'active' => 'สถานะ'];
    }

    protected function columns(array $data): array
    {
        return [
            'name' => $data['name'],
            'target_count' => $data['targetCount'],
            'is_active' => $data['active'],
        ];
    }

    protected function toRow(Model $record): array
    {
        return [
            'id' => $record->code,
            'name' => $record->name,
            'ageRange' => $record->age_range,
            'targetCount' => $record->target_count,
            'activityCount' => $record->activities_count,
            'active' => $record->is_active,
            'updatedAt' => $record->updated_at?->toDateString(),
        ];
    }

    /**
     * กลุ่มเป้าหมายที่ยังมีกิจกรรมหรือผู้เข้าร่วมอ้างอิงอยู่ ลบไม่ได้
     *
     * ตารางเชื่อมไม่มี ON DELETE ที่กันไว้ ลบแล้วกิจกรรมจะเหลือกลุ่มเป้าหมายที่ชี้ไปหาความว่าง
     * และรายงานที่แยกตามกลุ่มจะนับหายไปเงียบ ๆ
     */
    protected function blockedFromDelete(Model $record): ?string
    {
        $activities = $record->activities()->count();
        $participants = $record->participants()->count();

        if ($activities === 0 && $participants === 0) {
            return null;
        }

        $used = [];
        if ($activities) $used[] = 'กิจกรรม ' . $activities . ' รายการ';
        if ($participants) $used[] = 'ผู้เข้าร่วม ' . $participants . ' คน';

        return 'กลุ่มเป้าหมายนี้ถูกใช้อยู่กับ' . implode(' และ ', $used)
            . ' ลบไม่ได้ ถ้าไม่ต้องการให้เลือกได้อีก ให้เปลี่ยนสถานะเป็น "ไม่ใช้งาน" แทน';
    }
}
