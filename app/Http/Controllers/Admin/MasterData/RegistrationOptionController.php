<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Option;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationOptionController extends Controller
{
    private const GROUPS = [
        'age_range' => ['label' => 'ช่วงอายุ', 'prefix' => 'AGE'],
        'occupation' => ['label' => 'อาชีพ', 'prefix' => 'OCC'],
        'source_channel' => ['label' => 'ช่องทางรับรู้ข่าวสารกิจกรรม', 'prefix' => 'SRC'],
        'interest' => ['label' => 'กิจกรรมอื่นที่สนใจ', 'prefix' => 'INT'],
        'cohort_source' => ['label' => 'แหล่งที่มาของกลุ่มตัวอย่าง', 'prefix' => 'COH'],
    ];

    public function index(Request $request): JsonResponse|View
    {
        $rows = Option::query()
            ->with('updatedBy:id,name')
            ->whereIn('option_group', array_keys(self::GROUPS))
            ->orderBy('option_group')->orderBy('sort_order')->orderBy('id')
            ->get()->map(fn (Option $option) => $this->toRow($option))->values();

        if ($request->expectsJson()) {
            return response()->json(['rows' => $rows]);
        }

        return view('admin.master.registration-options', [
            'groups' => self::GROUPS,
            'seedRows' => $rows,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $option = DB::transaction(function () use ($data): Option {
            $group = $data['group'];
            $option = Option::create([
                'option_group' => $group,
                'code' => $this->nextCode($group),
                'label' => $data['label'],
                'sort_order' => $data['sortOrder'],
                'is_active' => $data['active'],
                'updated_by' => auth()->id(),
            ]);
            $this->log($option, 'created', 'เพิ่ม');

            return $option;
        });

        return response()->json(['message' => 'เพิ่มตัวเลือกแล้ว', 'row' => $this->toRow($option->load('updatedBy:id,name'))], 201);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $option = $this->find($key);
        $data = $this->validated($request, $option);

        DB::transaction(function () use ($option, $data): void {
            $option->update([
                'label' => $data['label'],
                'sort_order' => $data['sortOrder'],
                'is_active' => $data['active'],
                'updated_by' => auth()->id(),
            ]);
            $this->log($option, 'updated', 'แก้ไข');
        });

        return response()->json(['message' => 'บันทึกตัวเลือกแล้ว', 'row' => $this->toRow($option->fresh('updatedBy:id,name'))]);
    }

    public function destroy(string $key): JsonResponse
    {
        $blocked = null;

        DB::transaction(function () use ($key, &$blocked): void {
            $option = $this->find($key, true);
            $usage = $this->usageCount($option);
            if ($usage > 0) {
                $blocked = 'ลบไม่ได้ เพราะตัวเลือกนี้ถูกนำไปใช้แล้ว '.$usage.' รายการ';
                return;
            }

            $this->log($option, 'deleted', 'ลบ');
            $option->delete();
        });

        return $blocked
            ? response()->json(['message' => $blocked], 403)
            : response()->json(['message' => 'ลบตัวเลือกแล้ว']);
    }

    private function validated(Request $request, ?Option $current = null): array
    {
        $group = $current?->option_group ?? $request->input('group');

        return $request->validate([
            'group' => [$current ? 'sometimes' : 'required', 'string', Rule::in(array_keys(self::GROUPS))],
            'label' => [
                'required', 'string', 'max:160',
                Rule::unique('mst_options', 'label')->where(fn ($query) => $query->where('option_group', $group))->ignore($current?->id),
            ],
            'sortOrder' => ['required', 'integer', 'min:0', 'max:999'],
            'active' => ['required', 'boolean'],
        ], [], [
            'group' => 'ประเภทข้อมูล', 'label' => 'ชื่อรายการ', 'sortOrder' => 'ลำดับ', 'active' => 'สถานะ',
        ]);
    }

    private function find(string $key, bool $lock = false): Option
    {
        [$group, $code] = array_pad(explode(':', urldecode($key), 2), 2, null);
        abort_unless(isset(self::GROUPS[$group]) && $code, 404);
        $query = Option::query()->where('option_group', $group)->where('code', $code);

        return ($lock ? $query->lockForUpdate() : $query)->firstOrFail();
    }

    private function nextCode(string $group): string
    {
        $prefix = self::GROUPS[$group]['prefix'].'-';
        $max = Option::query()->where('option_group', $group)->where('code', 'like', $prefix.'%')
            ->lockForUpdate()->pluck('code')
            ->map(fn (string $code) => (int) Str::afterLast($code, '-'))->max() ?? 0;

        return $prefix.str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

    private function usageCount(Option $option): int
    {
        return match ($option->option_group) {
            'occupation' => DB::table('ptp_participants')->where('occupation_id', $option->id)->count()
                + DB::table('act_registrations')->where('occupation_id', $option->id)->count(),
            'source_channel' => DB::table('ptp_participants')->where('source_channel_id', $option->id)->count()
                + DB::table('act_registrations')->where('source_channel_id', $option->id)->count(),
            'interest' => DB::table('act_registration_interests')->where('option_id', $option->id)->count(),
            'cohort_source' => DB::table('ptp_cohort_profiles')->where('source_type', $option->code)->count(),
            default => 0,
        };
    }

    private function toRow(Option $option): array
    {
        return [
            'id' => $option->option_group.':'.$option->code,
            'group' => $option->option_group,
            'code' => $option->code,
            'label' => $option->label,
            'sortOrder' => $option->sort_order,
            'active' => $option->is_active,
            'deleteUsageCount' => $this->usageCount($option),
            'updatedAt' => optional($option->updated_at)->toDateString(),
            'updatedBy' => $option->updatedBy?->name,
        ];
    }

    private function log(Option $option, string $action, string $verb): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'master.registration_option.'.$action,
            'detail' => $verb.self::GROUPS[$option->option_group]['label'].' '.$option->code.' — '.$option->label,
        ]);
    }
}
