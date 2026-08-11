<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Models\Area;
use App\Models\Option;
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
     * บันทึกแล้วต้องเห็นแถวนั้นบนสุดทันทีโดยไม่ต้องไปหา
     * จึงเรียงตามเวลาที่แก้ล่าสุด และใช้ id ตัดสินแถวที่แก้ในวินาทีเดียวกัน
     */
    protected function query()
    {
        return Area::query()->withCount('activities')->orderByDesc('updated_at')->orderByDesc('id');
    }

    /**
     * ตัวเลือกชุดเดียวกับที่ใช้ตรวจตอนบันทึก
     *
     * ของเดิมหน้าจออ่านจาก mock-data.js ซึ่งเป็นคนละที่กับกฎฝั่งเซิร์ฟเวอร์
     * ถ้าปล่อยไว้ วันหนึ่งสองที่จะไม่ตรงกันแล้วบันทึกไม่ผ่านโดยไม่มีเหตุผลที่ผู้ใช้เห็นได้
     */
    protected function viewData(): array
    {
        return [
            'types' => self::optionLabels('area_type'),
            'groups' => self::optionLabels('area_group'),
            'statuses' => self::optionLabels('area_status'),
        ];
    }

    /**
     * ตัวเลือกของพื้นที่ — อยู่ที่ mst_options แยกด้วย option_group
     *
     * ตารางเดียวกับอาชีพและช่องทางที่รู้จักกิจกรรม (ปม F.3) ไม่แยกตารางต่อชุด
     * เพราะทุกชุดมีแค่ code/label/ลำดับ/เปิดปิด เหมือนกันหมด
     *
     * @return array<int, string>
     */
    private static function optionLabels(string $group): array
    {
        return Option::group($group)->active()->pluck('label')->all();
    }

    protected function rules(?Model $current): array
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('mst_areas', 'name')->ignore($current?->id)],
            'areaType' => ['nullable', 'string', Rule::in(self::optionLabels('area_type'))],
            'areaGroup' => ['required', 'string', Rule::in(self::optionLabels('area_group'))],
            'status' => ['required', 'string', Rule::in(self::optionLabels('area_status'))],

            'province' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],

            'startDate' => ['nullable', 'date'],

            /* สิ้นสุดต้องไม่มาก่อนเริ่ม และปล่อยว่างได้ = ยังดำเนินการอยู่ */
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],

            'partnerOrg' => ['nullable', 'string', 'max:200'],
            'coordinator' => ['required', 'string', 'max:150'],
            'coordinatorPhone' => ['required', 'string', 'max:30'],
            'coordinatorPosition' => ['nullable', 'string', 'max:150'],
            'mapUrl' => ['nullable', 'url', 'max:500'],
        ];
    }

    protected function attributes(): array
    {
        return [
            'name' => 'ชื่อพื้นที่ดำเนินงาน',
            'areaType' => 'ประเภทพื้นที่',
            'areaGroup' => 'กลุ่มพื้นที่',
            'status' => 'สถานะ',
            'province' => 'จังหวัด',
            'district' => 'เขต/อำเภอ',
            'startDate' => 'วันที่เริ่มดำเนินการ',
            'endDate' => 'วันที่สิ้นสุด',
            'partnerOrg' => 'หน่วยงานร่วม',
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
            'area_type' => $data['areaType'] ?: null,
            'area_group' => $data['areaGroup'],
            'status' => $data['status'],
            'province' => $data['province'] ?: null,
            'district' => $data['district'] ?: null,
            'start_date' => $data['startDate'] ?: null,
            'end_date' => $data['endDate'] ?: null,
            'partner_org' => $data['partnerOrg'] ?: null,
            'coordinator_name' => $data['coordinator'],
            'coordinator_phone' => $data['coordinatorPhone'],
            'coordinator_position' => $data['coordinatorPosition'] ?: null,
            'map_url' => $data['mapUrl'] ?: null,
            'updated_by' => auth()->id(),
        ];
    }

    protected function toRow(Model $record): array
    {
        return [
            'id' => $record->code,
            'name' => $record->name,
            'province' => $record->province,
            'district' => $record->district,
            'areaType' => $record->area_type,
            'areaGroup' => $record->area_group,
            'startDate' => $record->start_date?->toDateString(),
            'endDate' => $record->end_date?->toDateString(),
            'partnerOrg' => $record->partner_org,
            'coordinator' => $record->coordinator_name,
            'coordinatorPhone' => $record->coordinator_phone,
            'coordinatorPosition' => $record->coordinator_position,
            'mapUrl' => $record->map_url,
            'status' => $record->status,
            'activityCount' => $record->activities_count,
            'updatedAt' => $record->updated_at?->toDateString(),
        ];
    }

    protected function blockedFromDelete(Model $record): ?string
    {
        $count = $record->activities()->count();

        return $count === 0
            ? null
            : 'พื้นที่นี้ถูกใช้อยู่กับกิจกรรม ' . $count . ' รายการ ลบไม่ได้ '
              . 'ถ้าไม่ต้องการให้เลือกได้อีก ให้เปลี่ยนสถานะเป็น "สิ้นสุดแล้ว" แทน';
    }
}
