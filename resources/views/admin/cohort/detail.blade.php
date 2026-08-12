@extends('layouts.admin')

@section('title', 'รายละเอียดกลุ่มตัวอย่าง')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard">แดชบอร์ด</a> <span>/</span>
    <a href="{{ route('admin.cohort.index') }}">กลุ่มตัวอย่าง</a> <span>/</span>
    <span class="is-current" id="cd-crumb">{{ $member['name'] }} ({{ $member['pid'] }})</span>
  </nav>

  <div class="cd-header" id="cd-header">
    <div>
      <h1 class="co-title" style="margin:0;">{{ $member['name'] }}</h1>
      <span class="text-secondary small">{{ $member['pid'] }} · เข้ากลุ่มตัวอย่างเมื่อ {{ $member['entryDate'] }}</span>
    </div>
    <div class="flex gap-2">
      @if(!$member['stopped'])
        <button type="button" class="btn btn-outline" data-open-modal="cd-stop-modal">ยุติการติดตาม</button>
      @else
        <span class="badge badge-danger">ยุติการติดตามแล้ว</span>
      @endif
      <a href="{{ route('admin.cohort.index') }}" class="btn btn-outline">กลับหน้ารายการ</a>
    </div>
  </div>

  <div class="cd-grid2 mt-4">
    <section class="card cd-panel">
      <span class="cd-panel-title">ข้อมูลทั่วไป</span>
      <dl class="field-view-grid" id="cd-info">
        <div><dt>ชื่อ-นามสกุล</dt><dd>{{ $member['name'] }}</dd></div>
        <div><dt>รหัสบุคคล</dt><dd>{{ $member['pid'] }}</dd></div>
        <div><dt>เบอร์โทรศัพท์</dt><dd>{{ $member['phone'] }}</dd></div>
        <div><dt>เพศ</dt><dd>{{ $member['gender'] }}</dd></div>
        <div><dt>ช่วงอายุ</dt><dd>{{ $member['age'] }}</dd></div>
        <div><dt>อาชีพ</dt><dd>{{ $member['job'] }}</dd></div>
        <div><dt>พื้นที่ดำเนินงาน</dt><dd>{{ $member['area'] }}</dd></div>
        <div><dt>กลุ่มเป้าหมาย</dt><dd>{{ $member['target'] }}</dd></div>
        <div><dt>สถานะผูก LINE</dt><dd>{{ $member['line'] ? 'ผูกแล้ว' : 'ยังไม่ผูก' }}</dd></div>
        <div><dt>สถานะการติดตาม</dt><dd><span class="badge badge-info">{{ $member['status'] }}</span></dd></div>
      </dl>
    </section>

    <section class="card cd-panel">
      <span class="cd-panel-title">ไทม์ไลน์การติดตามผล</span>
      <div class="cd-timeline" id="cd-timeline">
        @foreach($member['rounds'] as $r)
          <div class="cd-timeline-item">
            <div class="cd-timeline-marker {{ $r['state'] === 'ตอบแล้ว' ? 'is-done' : ($r['state'] === 'เกินกำหนด' ? 'is-over' : 'is-due') }}"></div>
            <div class="cd-timeline-content">
              <div class="flex justify-between items-center">
                <strong>{{ $r['name'] }}</strong>
                <span class="badge {{ $r['state'] === 'ตอบแล้ว' ? 'badge-success' : ($r['state'] === 'เกินกำหนด' ? 'badge-danger' : 'badge-warning') }}">{{ $r['state'] }}</span>
              </div>
              <p class="small text-secondary mb-0">
                @if($r['answeredAt'])
                  ตอบเมื่อ: {{ $r['answeredAt'] }}
                @else
                  วันครบกำหนด: {{ $r['dueDate'] }}
                @endif
              </p>
            </div>
          </div>
        @endforeach
      </div>
    </section>
  </div>
@endsection

@section('modals')
@if(!$member['stopped'])
<div class="modal-overlay" id="cd-stop-modal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <h3 class="modal-title">ยืนยันยุติการติดตาม</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิด">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <form id="cd-stop-form">
      <div class="modal-body">
        <p class="text-secondary small mb-3">เมื่อยุติการติดตามแล้ว ผู้ใช้จะไม่ได้รับแจ้งเตือนหรือทำแบบประเมินในรอบถัดไปอีก</p>
        <div class="form-group mb-0">
          <label class="form-label" for="cd-stop-reason">เหตุผลที่ยุติการติดตาม<span class="form-required">*</span></label>
          <input class="input" id="cd-stop-reason" required placeholder="เช่น ย้ายออกจากพื้นที่ / ขอถอนตัว">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button>
        <button type="submit" class="btn btn-danger" id="cd-stop-submit">ยืนยันยุติการติดตาม</button>
      </div>
    </form>
  </div>
</div>
@endif
@endsection

@push('page-script')
<script>
(function () {
  var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  var stopForm = document.getElementById('cd-stop-form');

  if (stopForm) {
    stopForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var reason = document.getElementById('cd-stop-reason').value;
      var submitBtn = document.getElementById('cd-stop-submit');
      submitBtn.disabled = true;

      fetch('{{ route('admin.cohort.stop', $member['db_id']) }}', {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ reason: reason })
      })
      .then(function (res) { return res.json(); })
      .then(function (res) {
        submitBtn.disabled = false;
        if (!res.success) {
          if (window.TFC.showToast) window.TFC.showToast(res.message || 'เกิดข้อผิดพลาด', 'danger');
          return;
        }
        if (window.TFC.showToast) window.TFC.showToast(res.message, 'success');
        window.location.reload();
      })
      .catch(function () {
        submitBtn.disabled = false;
        if (window.TFC.showToast) window.TFC.showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'danger');
      });
    });
  }
})();
</script>
@endpush
