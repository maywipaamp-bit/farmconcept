@extends('layouts.admin')

@section('title', 'ตั้งค่าระบบ')

@push('styles')
<link rel="stylesheet" href="@assetv('assets/css/registration-master.css')">
@endpush

@section('content')
@php $value = fn (string $key, string $default = '') => old($key, $settings[$key] ?? $default); @endphp
<div class="registration-master-page system-settings-page">
  <nav class="breadcrumb" aria-label="Breadcrumb"><a href="/admin/dashboard">แดชบอร์ด</a><span>/</span><span>พื้นฐาน</span><span>/</span><span class="is-current">ตั้งค่าระบบ</span></nav>
  <div class="page-header" id="system-settings-header"></div>

  @if ($errors->any())<div class="settings-alert is-error">{{ $errors->first() }}</div>@endif
  <form method="POST" action="{{ route('admin.master.system-settings.update') }}" enctype="multipart/form-data" id="system-settings-form">
    @csrf
    <div class="settings-layout">
      <nav class="settings-section-nav" aria-label="ส่วนการตั้งค่า"><a href="#settings-identity">ข้อมูลองค์กร</a><a href="#settings-contact">ข้อมูลติดต่อ</a><a href="#settings-social">โซเชียล</a><a href="#settings-links">ลิงก์ระบบ</a></nav>
      <div class="settings-sections">
        <section class="settings-card" id="settings-identity">
          <div class="settings-card-heading"><h2>ข้อมูลองค์กรและระบบ</h2><p>ใช้เป็นข้อมูลกลางสำหรับชื่อระบบและภาพตราสัญลักษณ์</p></div>
          <div class="settings-form-grid">
            <div>
              <div class="form-group"><label class="form-label" for="organization-name">ชื่อหน่วยงาน<span class="form-required">*</span></label><input class="input" id="organization-name" name="organization_name" maxlength="160" required value="{{ $value('organization_name', 'The Farm Concept') }}"></div>
              <div class="form-group"><label class="form-label" for="system-name">ชื่อระบบ<span class="form-required">*</span></label><input class="input" id="system-name" name="system_name" maxlength="160" required value="{{ $value('system_name', 'ระบบติดตามและประเมินผลการเปลี่ยนแปลงสุขภาพ') }}"></div>
            </div>
            <div class="settings-logo-field">
              <label class="form-label" for="settings-logo">Logo</label>
              <label class="settings-logo-preview" for="settings-logo" id="settings-logo-preview">
                @if ($logoUrl)<img src="{{ $logoUrl }}" alt="โลโก้หน่วยงาน">@else<span>เลือกภาพโลโก้</span>@endif
              </label>
              <input type="file" id="settings-logo" name="logo" accept="image/jpeg,image/png,image/webp" hidden>
              <div class="settings-logo-actions"><label class="btn btn-outline btn-sm" for="settings-logo">เลือกภาพ</label>@if ($logoUrl)<label class="settings-remove-logo"><input type="checkbox" name="remove_logo" value="1"> ลบโลโก้เดิม</label>@endif</div>
              <p class="caption text-secondary">JPG, PNG, WEBP ไม่เกิน 2 MB</p>
            </div>
          </div>
        </section>

        <section class="settings-card" id="settings-contact">
          <div class="settings-card-heading"><h2>ข้อมูลติดต่อ</h2><p>ข้อมูลสำหรับแสดงให้ผู้เข้าร่วมติดต่อหน่วยงาน</p></div>
          <div class="settings-form-grid two-columns">
            <div class="form-group full"><label class="form-label" for="contact-address">ที่อยู่</label><textarea class="textarea" id="contact-address" name="contact_address" maxlength="500" rows="3">{{ $value('contact_address') }}</textarea></div>
            <div class="form-group"><label class="form-label" for="contact-phone">โทรศัพท์</label><input class="input" id="contact-phone" name="contact_phone" maxlength="50" value="{{ $value('contact_phone') }}"></div>
            <div class="form-group"><label class="form-label" for="contact-email">อีเมล</label><input class="input" id="contact-email" name="contact_email" type="email" maxlength="160" value="{{ $value('contact_email') }}"></div>
            <div class="form-group full"><label class="form-label" for="website-url">เว็บไซต์</label><input class="input" id="website-url" name="website_url" type="url" maxlength="500" placeholder="https://" value="{{ $value('website_url') }}"></div>
          </div>
        </section>

        <section class="settings-card" id="settings-social">
          <div class="settings-card-heading"><h2>ข้อมูลโซเชียล</h2><p>กรอกเป็นลิงก์เต็ม หากไม่มีสามารถเว้นว่างได้</p></div>
          <div class="settings-form-grid two-columns">
            @foreach (['facebook_url' => 'Facebook', 'instagram_url' => 'Instagram', 'line_url' => 'LINE', 'youtube_url' => 'YouTube'] as $key => $label)
              <div class="form-group"><label class="form-label" for="{{ str_replace('_', '-', $key) }}">{{ $label }}</label><input class="input" id="{{ str_replace('_', '-', $key) }}" name="{{ $key }}" type="url" maxlength="500" placeholder="https://" value="{{ $value($key) }}"></div>
            @endforeach
          </div>
        </section>

        <section class="settings-card" id="settings-links">
          <div class="settings-card-heading"><h2>ลิงก์เอกสารและคู่มือ</h2><p>ลิงก์กลางสำหรับคู่มือ เงื่อนไข และนโยบายความเป็นส่วนตัว</p></div>
          <div class="form-group"><label class="form-label" for="manual-url">ลิงก์คู่มือระบบ</label><input class="input" id="manual-url" name="manual_url" type="url" maxlength="500" placeholder="https://" value="{{ $value('manual_url') }}"></div>
          <div class="settings-form-grid two-columns">
            <div class="form-group"><label class="form-label" for="privacy-policy-url">ลิงก์นโยบายความเป็นส่วนตัว</label><input class="input" id="privacy-policy-url" name="privacy_policy_url" type="url" maxlength="500" placeholder="https://" value="{{ $value('privacy_policy_url') }}"></div>
            <div class="form-group"><label class="form-label" for="terms-url">ลิงก์เงื่อนไขการใช้งาน</label><input class="input" id="terms-url" name="terms_url" type="url" maxlength="500" placeholder="https://" value="{{ $value('terms_url') }}"></div>
          </div>
        </section>
      </div>
    </div>
    <div class="settings-save-bar"><span class="text-secondary">การตั้งค่านี้จัดเก็บเป็นข้อมูลกลางของระบบ</span><button type="submit" class="btn btn-primary" id="settings-save">บันทึกการตั้งค่า</button></div>
  </form>
</div>
@endsection

@push('page-script')
<script>
(function () {
  window.TFC.renderPageHeader('system-settings-header', { title: 'ตั้งค่าระบบ', description: 'จัดการชื่อหน่วยงาน โลโก้ ข้อมูลติดต่อ โซเชียล และลิงก์เอกสารกลาง' });
  var input = document.getElementById('settings-logo'); var preview = document.getElementById('settings-logo-preview');
  input.addEventListener('change', function () { var file = this.files && this.files[0]; if (!file) return; if (file.size > 2 * 1024 * 1024) { this.value = ''; window.TFC.showToast('ไฟล์โลโก้มีขนาดเกิน 2 MB', 'danger'); return; } var url = URL.createObjectURL(file); preview.innerHTML = '<img src="' + url + '" alt="ตัวอย่างโลโก้">'; });
  document.getElementById('system-settings-form').addEventListener('submit', function () { var button = document.getElementById('settings-save'); button.disabled = true; button.textContent = 'กำลังบันทึก…'; });
  @if (session('success')) window.TFC.showToast(@json(session('success')), 'success'); @endif
})();
</script>
@endpush
