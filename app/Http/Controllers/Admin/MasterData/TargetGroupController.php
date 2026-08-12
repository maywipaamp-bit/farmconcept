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
     * เรียงจากรายการที่เพิ่มล่าสุดลงมา — ของใหม่อยู่บนสุดเสมอ
     *
     * ใช้ id ไม่ใช่ updated_at เพราะสองอย่างนี้ให้ผลต่างกันตอนแก้ไข
     * id คงที่ตลอดอายุของแถว แก้ข้อมูลแล้วลำดับจึงไม่ขยับ คนที่ไล่แก้ทีละแถวไม่เสียตำแหน่ง
     * ส่วน updated_at จะดีดแถวที่เพิ่งบันทึกขึ้นบนสุดทุกครั้ง
     */
    protected function query()
    {
        /* หน้าจอแสดงจำนวนกิจกรรมที่ใช้กลุ่มนี้ — นับด้วย withCount ไม่งั้นจะยิง query ต่อแถว */
        return TargetGroup::query()
            ->with('updatedBy:id,name')
            ->withCount(['activities', 'participants', 'registrations'])
            ->orderByDesc('id');
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
            'updated_by' => auth()->id(),
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
            'deleteUsageCount' => $record->activities_count + $record->participants_count + $record->registrations_count,
            'active' => $record->is_active,
            /* ตารางแสดง "ชื่อคนแก้" กับ "วันที่ | เวลา" — แถวที่มีอยู่ก่อนระบบเก็บข้อมูลนี้
               จะไม่มีชื่อ หน้าจอแสดงขีดแทนจนกว่าจะมีคนแก้ครั้งถัดไป */
            'updatedBy' => $record->updatedBy?->name,
            'updatedAt' => $record->updated_at?->toDateString(),
            'updatedTime' => $record->updated_at?->format('H.i'),
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
        $registrations = $record->registrations()->count();

        if ($activities === 0 && $participants === 0 && $registrations === 0) {
            return null;
        }

        $used = [];
        if ($activities) {
            $used[] = 'กิจกรรม '.$activities.' รายการ';
        }
        if ($participants) {
            $used[] = 'ผู้เข้าร่วม '.$participants.' คน';
        }
        if ($registrations) {
            $used[] = 'ใบลงทะเบียน '.$registrations.' รายการ';
        }

        return 'กลุ่มเป้าหมายนี้ถูกใช้อยู่กับ'.implode(' และ ', $used)
            .' ลบไม่ได้ ถ้าไม่ต้องการให้เลือกได้อีก ให้เปลี่ยนสถานะเป็น "ไม่ใช้งาน" แทน';
    }
}
