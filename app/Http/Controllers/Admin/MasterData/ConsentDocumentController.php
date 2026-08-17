<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Models\ConsentDocument;
use App\Models\Option;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConsentDocumentController extends MasterDataController
{
    /** ประเภทติดตัวระบบ — เอกสารที่มีอยู่อ้างรหัสชุดนี้ ลบไม่ได้ */
    private const TYPES = [
        'terms' => 'เงื่อนไขการเข้าร่วมและการใช้งาน',
        'pdpa' => 'นโยบายและความยินยอม PDPA',
        'cohort_data' => 'ยินยอมเก็บข้อมูลกลุ่มตัวอย่าง',
    ];

    /** กลุ่มใน mst_options ที่เก็บประเภทที่ผู้ดูแลเพิ่มเองภายหลัง */
    private const TYPE_OPTION_GROUP = 'consent_type';

    /**
     * ประเภททั้งหมดที่เลือกได้ = ติดตัวระบบ + ที่ผู้ดูแลเพิ่มเอง
     *
     * แยกสองชั้นแทนการย้ายของเดิมลงตาราง เพราะเอกสารที่มีอยู่อ้างรหัส terms/pdpa/cohort_data ไว้แล้ว
     * ถ้าย้ายแล้วมีใครลบแถวทิ้ง เอกสารเดิมจะชี้ไปประเภทที่ไม่มีอยู่
     *
     * @return array<string, string>
     */
    private function allTypes(): array
    {
        return self::TYPES + Option::group(self::TYPE_OPTION_GROUP)
            ->active()
            ->orderBy('sort_order')
            ->pluck('label', 'code')
            ->all();
    }

    /**
     * เพิ่มประเภทเอกสารใหม่จากดรอปดาวน์ในฟอร์ม
     *
     * ตอบกลับเป็น { value, label } ตามที่ smart-select.js คาดไว้
     * เพื่อให้เลือกรายการที่เพิ่งเพิ่มให้อัตโนมัติโดยไม่ต้องโหลดหน้าใหม่
     */
    public function storeType(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:160'],
        ], [
            'label.required' => 'กรุณากรอกชื่อประเภทเอกสาร',
        ]);

        $label = trim($data['label']);

        /* ชื่อซ้ำกับประเภทที่มีอยู่ = ไม่สร้างใหม่ คืนตัวเดิมไปให้เลือกแทน
           ป้องกันประเภทชื่อเหมือนกันสองอันที่แยกไม่ออกด้วยตาเปล่า */
        $existing = collect($this->allTypes())->search(
            fn (string $name) => mb_strtolower($name) === mb_strtolower($label)
        );

        if ($existing !== false) {
            return response()->json(['value' => $existing, 'label' => $this->allTypes()[$existing]]);
        }

        $option = Option::create([
            'option_group' => self::TYPE_OPTION_GROUP,
            /* รหัสเป็นเลขลำดับ ไม่แปลงจากชื่อไทย เพราะ slug ภาษาไทยได้สตริงว่าง */
            'code' => 'custom_'.(Option::group(self::TYPE_OPTION_GROUP)->max('id') + 1),
            'label' => $label,
            'sort_order' => (int) Option::group(self::TYPE_OPTION_GROUP)->max('sort_order') + 1,
            'is_active' => true,
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json(['value' => $option->code, 'label' => $option->label], 201);
    }

    protected function model(): string { return ConsentDocument::class; }
    protected function view(): string { return 'admin.master.consent-documents'; }
    protected function label(): string { return 'เอกสารความยินยอม'; }
    protected function codePrefix(): string { return 'CNS'; }

    protected function query()
    {
        return ConsentDocument::query()->with('updatedBy:id,name')->withCount('consents')->orderByDesc('is_active')->orderBy('consent_type')->orderByDesc('id');
    }

    protected function viewData(): array
    {
        return ['consentTypes' => $this->allTypes()];
    }

    protected function rules(?Model $current): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_keys($this->allTypes()))],
            'title' => ['required', 'string', 'max:160'],
            'version' => ['required', 'string', 'max:20', Rule::unique('mst_consent_documents', 'version')
                ->where(fn ($query) => $query->where('consent_type', request('type')))->ignore($current?->id)],
            'content' => ['required', 'string', 'max:20000'],
            'effectiveDate' => ['nullable', 'date'],
            'required' => ['required', 'boolean'],
            'active' => [
                'required', 'boolean',
                function (string $attribute, mixed $value, \Closure $fail) use ($current): void {
                    if (! $value) return;
                    $exists = ConsentDocument::query()->where('consent_type', request('type'))->where('is_active', true)
                        ->when($current, fn ($query) => $query->whereKeyNot($current->id))->exists();
                    if ($exists) $fail('ประเภทนี้มีเอกสารที่เปิดใช้งานอยู่แล้ว กรุณาปิดฉบับเดิมก่อน');
                },
            ],
        ];
    }

    protected function attributes(): array
    {
        return ['type' => 'ประเภท', 'title' => 'ชื่อเอกสาร', 'version' => 'เวอร์ชัน', 'content' => 'รายละเอียด', 'effectiveDate' => 'วันที่เริ่มใช้', 'required' => 'บังคับยอมรับ', 'active' => 'สถานะ'];
    }

    protected function columns(array $data): array
    {
        return [
            'consent_type' => $data['type'],
            'title' => $data['title'],
            'version' => $data['version'],
            'content' => $data['content'],
            'effective_date' => $data['effectiveDate'] ?: null,
            'is_required' => $data['required'],
            'is_active' => $data['active'],
            'active_slot' => $data['active'] ? 1 : null,
            'updated_by' => auth()->id(),
        ];
    }

    protected function blockedFromDelete(Model $record): ?string
    {
        $count = $record->consents()->count();
        return $count > 0 ? 'ลบไม่ได้ เพราะเอกสารนี้มีการยอมรับแล้ว '.$count.' รายการ' : null;
    }

    protected function toRow(Model $record): array
    {
        $usage = isset($record->consents_count) ? $record->consents_count : $record->consents()->count();
        return [
            'id' => $record->code,
            'type' => $record->consent_type,
            'typeLabel' => $this->allTypes()[$record->consent_type] ?? $record->consent_type,
            'title' => $record->title,
            'version' => $record->version,
            'content' => $record->content,
            'effectiveDate' => optional($record->effective_date)->toDateString(),
            'required' => $record->is_required,
            'active' => $record->is_active,
            'deleteUsageCount' => $usage,
            'updatedAt' => optional($record->updated_at)->toDateString(),
            'updatedBy' => $record->updatedBy?->name,
        ];
    }
}
