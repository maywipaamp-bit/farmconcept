<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Models\ConsentDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConsentDocumentController extends MasterDataController
{
    private const TYPES = [
        'terms' => 'เงื่อนไขการเข้าร่วมและการใช้งาน',
        'pdpa' => 'นโยบายและความยินยอม PDPA',
        'cohort_data' => 'ยินยอมเก็บข้อมูลกลุ่มตัวอย่าง',
    ];

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
        return ['consentTypes' => self::TYPES];
    }

    protected function rules(?Model $current): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_keys(self::TYPES))],
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
            'typeLabel' => self::TYPES[$record->consent_type] ?? $record->consent_type,
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
