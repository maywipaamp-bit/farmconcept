{{-- แถบแจ้งเตือนของแท็บในหน้ารายละเอียด — ใช้เมื่อกิจกรรมไม่ได้เปิดฟีเจอร์ของแท็บนั้น
     ต้องส่ง $message (และ $detail ถ้ามีบรรทัดขยายความ) --}}
<div class="alert alert-info aov-tab-notice" role="status">
  <span class="alert-icon">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M12 11.5V16"/></svg>
  </span>
  <div>
    <div class="alert-title">{{ $message }}</div>
    @if (! empty($detail))
      <div>{{ $detail }}</div>
    @endif
  </div>
</div>
