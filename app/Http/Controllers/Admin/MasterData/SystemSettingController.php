<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    private const TEXT_KEYS = [
        'organization_name', 'system_name', 'contact_address', 'contact_phone', 'contact_email',
        'website_url', 'facebook_url', 'instagram_url', 'line_url', 'youtube_url',
        'manual_url', 'privacy_policy_url', 'terms_url',
    ];

    public function index(): View
    {
        $settings = SystemSetting::query()->pluck('setting_value', 'setting_key')->all();
        $logoPath = $settings['organization_logo_path'] ?? null;

        return view('admin.master.system-settings', [
            'settings' => $settings,
            'logoUrl' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'organization_name' => ['required', 'string', 'max:160'],
            'system_name' => ['required', 'string', 'max:160'],
            'contact_address' => ['nullable', 'string', 'max:500'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:160'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'facebook_url' => ['nullable', 'url', 'max:500'],
            'instagram_url' => ['nullable', 'url', 'max:500'],
            'line_url' => ['nullable', 'url', 'max:500'],
            'youtube_url' => ['nullable', 'url', 'max:500'],
            'manual_url' => ['nullable', 'url', 'max:500'],
            'privacy_policy_url' => ['nullable', 'url', 'max:500'],
            'terms_url' => ['nullable', 'url', 'max:500'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ], [], ['organization_name' => 'ชื่อหน่วยงาน', 'system_name' => 'ชื่อระบบ', 'logo' => 'โลโก้']);

        DB::transaction(function () use ($request, $data): void {
            foreach (self::TEXT_KEYS as $key) {
                $this->set($key, $data[$key] ?? null, str_ends_with($key, '_url') ? 'url' : 'text');
            }

            $oldPath = SystemSetting::value('organization_logo_path');
            if ($request->boolean('remove_logo')) {
                if ($oldPath) Storage::disk('public')->delete($oldPath);
                $this->set('organization_logo_path', null, 'image');
            }

            if ($request->hasFile('logo')) {
                if ($oldPath) Storage::disk('public')->delete($oldPath);
                $this->set('organization_logo_path', $request->file('logo')->store('system-settings', 'public'), 'image');
            }

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'master.system_settings.updated',
                'detail' => 'แก้ไขตั้งค่าระบบ',
            ]);
        });

        return back()->with('success', 'บันทึกตั้งค่าระบบแล้ว');
    }

    private function set(string $key, ?string $value, string $type): void
    {
        SystemSetting::query()->updateOrCreate(['setting_key' => $key], [
            'setting_value' => $value,
            'setting_type' => $type,
            'updated_by' => auth()->id(),
        ]);
    }
}
