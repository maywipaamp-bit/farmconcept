<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Models\Area;
use App\Models\District;
use App\Models\Option;
use App\Models\PartnerOrg;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class AreaController extends MasterDataController
{
    protected function model(): string
    {
        return Area::class;
    }

    protected function view(): string
    {
        return 'admin.master.areas';
    }

    protected function label(): string
    {
        return 'พื้นที่ดำเนินงาน';
    }

    protected function codePrefix(): string
    {
        return 'AREA';
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
        return Area::query()
            ->with(['areaType:id,label', 'areaGroup:id,label', 'district:id,province,name', 'partnerOrgs:id,name',
                'updatedBy:id,name'])
            ->withCount(['activities', 'participants', 'users', 'registrations'])
            ->orderByDesc('id');
    }

    /**
     * ตัวเลือกทั้งหมดมาจากฐานข้อมูล ไม่ใช่รายการที่เขียนไว้ในไฟล์ JS
     *
     * ของเดิม dropdown จังหวัด/อำเภออ่านจาก mock-data.js ซึ่งเป็นคนละแหล่งกับ mst_districts
     * บังเอิญตรงกันอยู่ แต่ไม่มีอะไรบังคับให้ตรง วันไหนแก้ที่เดียวก็เพี้ยนทันที
     */
    protected function viewData(): array
    {
        return [
            'types' => Option::group('area_type')->active()->get(['id', 'label']),
            'groups' => Option::group('area_group')->active()->get(['id', 'label']),
            'statuses' => Option::group('area_status')->active()->pluck('label'),

            /* จังหวัด → รายชื่ออำเภอ ให้หน้าจอผูกสองช่องต่อกันได้โดยไม่ต้องยิงคำขอเพิ่ม */
            'districts' => District::orderBy('province')->orderBy('name')->get()
                ->groupBy('province')
                ->map(fn ($rows) => $rows->map(fn (District $d) => ['id' => $d->id, 'name' => $d->name])->values()),

            'partnerOrgs' => PartnerOrg::active()->orderBy('name')->pluck('name'),
        ];
    }

    protected function rules(?Model $current): array
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('mst_areas', 'name')->ignore($current?->id)],

            /* รับเป็น id ตรง ๆ ไม่ใช่ชื่อ — ชื่อซ้ำกันได้และเปลี่ยนได้ ทำให้จับคู่ผิดโดยไม่มีอะไรเตือน */
            'areaTypeId' => ['required', Rule::exists('mst_options', 'id')->where('option_group', 'area_type')],
            'areaGroupId' => ['required', Rule::exists('mst_options', 'id')->where('option_group', 'area_group')],
            'districtId' => ['required', 'exists:mst_districts,id'],

            'status' => ['required', 'string', Rule::in(Option::group('area_status')->active()->pluck('label')->all())],

            'startDate' => ['nullable', 'date'],

            /* สิ้นสุดต้องไม่มาก่อนเริ่ม และปล่อยว่างได้ = ยังดำเนินการอยู่ */
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],

            /* หน่วยงานร่วมส่งมาเป็นรายชื่อ พิมพ์ชื่อใหม่ได้ ระบบสร้างให้เอง */
            'partnerOrgs' => ['present', 'array', 'max:20'],
            'partnerOrgs.*' => ['required', 'string', 'max:200', 'distinct'],

            'coordinator' => ['required', 'string', 'max:120'],
            'coordinatorPhone' => ['required', 'string', 'max:30'],
            'coordinatorPosition' => ['nullable', 'string', 'max:120'],
            'mapUrl' => ['nullable', 'url', 'max:255'],
        ];
    }

    protected function attributes(): array
    {
        return [
            'name' => 'ชื่อพื้นที่ดำเนินงาน',
            'areaTypeId' => 'ประเภทพื้นที่',
            'areaGroupId' => 'กลุ่มพื้นที่',
            'districtId' => 'เขต/อำเภอ',
            'status' => 'สถานะ',
            'startDate' => 'วันที่เริ่มดำเนินการ',
            'endDate' => 'วันที่สิ้นสุด',
            'partnerOrgs' => 'หน่วยงานร่วม',
            'partnerOrgs.*' => 'ชื่อหน่วยงานร่วม',
            'coordinator' => 'ผู้ประสานงาน',
            'coordinatorPhone' => 'เบอร์โทรผู้ประสานงาน',
            'coordinatorPosition' => 'ตำแหน่งผู้ประสานงาน',
            'mapUrl' => 'ลิงก์ Google Map',
        ];
    }

    protected function columns(array $data): array
    {
        return [
            'name' => $data['name'],
            'area_type_id' => $data['areaTypeId'],
            'area_group_id' => $data['areaGroupId'],
            'district_id' => $data['districtId'],
            'status' => $data['status'],
            'start_date' => $data['startDate'] ?: null,
            'end_date' => $data['endDate'] ?: null,
            'coordinator_name' => $data['coordinator'],
            'coordinator_phone' => $data['coordinatorPhone'],
            'coordinator_position' => $data['coordinatorPosition'] ?: null,
            'map_url' => $data['mapUrl'] ?: null,
            'updated_by' => auth()->id(),
        ];
    }

    /**
     * หน่วยงานร่วม — จับคู่ด้วยชื่อ ไม่มีก็สร้างให้
     *
     * ช่องนี้ให้พิมพ์ชื่อเองได้ตามฟอร์มเดิม การสร้างอัตโนมัติจึงจำเป็น
     * แต่ตัดช่องว่างหัวท้ายก่อนเทียบเสมอ ไม่งั้น "สสส. " กับ "สสส." จะกลายเป็นคนละหน่วยงาน
     */
    protected function syncRelations(Model $record, array $data): void
    {
        $ids = collect($data['partnerOrgs'])
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->map(fn (string $name) => PartnerOrg::firstOrCreate(['name' => $name], ['is_active' => true])->id);

        $record->partnerOrgs()->sync($ids);
    }

    protected function toRow(Model $record): array
    {
        return [
            'id' => $record->code,
            'name' => $record->name,

            /* ส่งทั้ง id (ให้ฟอร์มเลือกกลับได้) และชื่อ (ให้ตารางแสดง) */
            'areaTypeId' => $record->area_type_id,
            'areaType' => $record->areaType?->label,
            'areaGroupId' => $record->area_group_id,
            'areaGroup' => $record->areaGroup?->label,
            'districtId' => $record->district_id,
            'district' => $record->district?->name,
            'province' => $record->district?->province,

            'startDate' => $record->start_date?->toDateString(),
            'endDate' => $record->end_date?->toDateString(),
            'partnerOrgs' => $record->partnerOrgs->pluck('name')->values(),
            'partnerOrg' => $record->partnerOrgs->pluck('name')->join(', '),
            'coordinator' => $record->coordinator_name,
            'coordinatorPhone' => $record->coordinator_phone,
            'coordinatorPosition' => $record->coordinator_position,
            'mapUrl' => $record->map_url,
            'status' => $record->status,
            'activityCount' => $record->activities_count,
            'deleteUsageCount' => $record->activities_count + $record->participants_count
                + $record->users_count + $record->registrations_count,

            /* ตารางแสดง "ชื่อคนแก้" กับ "วันที่ | เวลา" — ส่งเวลามาด้วย ไม่ใช่แค่วันที่
               ข้อมูลชุดแรกที่มาจาก seeder ไม่มีคนแก้ ให้เป็นค่าว่างแล้วหน้าจอแสดงขีดแทน */
            'updatedBy' => $record->updatedBy?->name,
            'updatedAt' => $record->updated_at?->toDateString(),
            'updatedTime' => $record->updated_at?->format('H.i'),
        ];
    }

    protected function blockedFromDelete(Model $record): ?string
    {
        $activities = $record->activities()->count();
        $participants = $record->participants()->count();
        $users = $record->users()->count();
        $registrations = $record->registrations()->count();

        if ($activities === 0 && $participants === 0 && $users === 0 && $registrations === 0) {
            return null;
        }

        $used = [];
        if ($activities) {
            $used[] = 'กิจกรรม '.$activities.' รายการ';
        }
        if ($participants) {
            $used[] = 'ผู้เข้าร่วม '.$participants.' คน';
        }
        if ($users) {
            $used[] = 'ผู้ใช้งาน '.$users.' คน';
        }
        if ($registrations) {
            $used[] = 'ใบลงทะเบียน '.$registrations.' รายการ';
        }

        return 'พื้นที่นี้ถูกใช้อยู่กับ'.implode(' และ ', $used).' ลบไม่ได้ '
            .'ถ้าไม่ต้องการให้เลือกได้อีก ให้เปลี่ยนสถานะเป็น "สิ้นสุดแล้ว" แทน';
    }
}
