@extends('layouts.admin')

@section('title', 'รายละเอียดกลุ่มตัวอย่าง')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard">แดชบอร์ด</a> <span>/</span>
    <a href="{{ route('admin.cohort.index') }}">กลุ่มตัวอย่าง</a> <span>/</span>
    {{-- อ้างด้วยรหัสบุคคล ไม่แสดงชื่อ (คำสั่งทีม) — กลุ่มตัวอย่างเป็นข้อมูลนิรนาม --}}
    <span class="is-current" id="cd-crumb">{{ $member['pid'] }}</span>
  </nav>

  <div class="cd-header" id="cd-header">
    <div>
      <h1 class="co-title" style="margin:0;">{{ $member['pid'] }}</h1>
      <span class="text-secondary small">เข้ากลุ่มตัวอย่างเมื่อ {{ $member['entryDate'] }}</span>
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

  {{-- ทุกอย่างของคนนี้อยู่ในแท็บชุดเดียว — เจ้าหน้าที่ที่โทรตามต้องสลับดูได้ในหน้าเดียว
       ไม่ใช่เปิดห้าหน้าแล้วจำข้ามหน้า --}}
  <section class="card cd-tabs-card mt-4">
    <div class="cd-tabs" role="tablist">
      <button type="button" class="cd-tab is-active" role="tab" data-tab="profile">ข้อมูลกลุ่มตัวอย่าง</button>
      <button type="button" class="cd-tab" role="tab" data-tab="activities">ประวัติกิจกรรม</button>
      <button type="button" class="cd-tab" role="tab" data-tab="health">แบบประเมินสุขภาพ</button>
      <button type="button" class="cd-tab" role="tab" data-tab="purchases">การซื้อสินค้า</button>
      <button type="button" class="cd-tab" role="tab" data-tab="notes">ประวัติการติดตาม</button>
    </div>

    <div class="cd-panel-body" data-panel="profile">
      <div class="cd-profile">
        <section>
          <h2 class="cd-panel-title">ข้อมูลทั่วไป</h2>
          <dl class="cd-facts">
            @foreach($tabs['info'] as [$label, $value])
              <div>
                <dt>{{ $label }}</dt>
                <dd>{{ $label === 'วันที่เข้ากลุ่มตัวอย่าง' ? \App\Providers\AppServiceProvider::thaiDate($value) : ($value ?: '—') }}</dd>
              </div>
            @endforeach
          </dl>
        </section>

        <section>
          <div class="cd-profile-head">
            <h2 class="cd-panel-title">ไทม์ไลน์การติดตาม</h2>
            {{-- บอกวันฐานไว้ด้วย ทุกวันครบกำหนดในไทม์ไลน์นับต่อจากวันนี้ทั้งหมด
                 ถ้าวันไหนดูผิด จะได้รู้ว่าต้องไปแก้ที่วันเข้ากลุ่ม ไม่ใช่ไล่แก้ทีละรอบ --}}
            <span class="cd-profile-note">คำนวณจากวันที่เข้ากลุ่ม @thaidate($tabs['entryDate'])</span>
          </div>

          @if($tabs['timeline']->isEmpty())
            <p class="cd-empty">ยังไม่มีรอบติดตามสำหรับคนนี้</p>
          @else
            <ol class="cd-tl">
              @foreach($tabs['timeline'] as $r)
                @php($done = $r['state'] === 'ตอบแล้ว')
                <li class="cd-tl-item {{ $done ? 'is-done' : ($r['state'] === 'เกินกำหนด' ? 'is-over' : '') }}">
                  <span class="cd-tl-dot" aria-hidden="true">@if($done)✓@endif</span>
                  <div class="cd-tl-body">
                    <p class="cd-tl-head">
                      <strong>{{ $r['name'] }}</strong>
                      <span class="cd-pill {{ $done ? 'is-ok' : ($r['state'] === 'เกินกำหนด' ? 'is-over' : 'is-wait') }}">{{ $r['state'] }}</span>
                    </p>
                    <p class="cd-tl-line">
                      ครบกำหนด @thaidate($r['due'])
                      @if($r['hasWindow'])
                        · ช่วงติดตาม @thaidate($r['from']) – @thaidate($r['to'])
                      @endif
                    </p>
                    <p class="cd-tl-line">
                      @if($r['answeredAt'])ตอบเมื่อ @thaidate($r['answeredAt'])@else ยังไม่มีคำตอบ @endif
                    </p>
                  </div>
                </li>
              @endforeach
            </ol>
          @endif
        </section>
      </div>
    </div>

    <div class="cd-panel-body" data-panel="activities" hidden>
      @if($tabs['activities']->isEmpty())
        <p class="cd-empty">ยังไม่มีประวัติการเข้าร่วมกิจกรรม</p>
      @else
        <table class="cd-data-table">
          <colgroup><col style="width:110px"><col><col style="width:24%"><col style="width:18%"><col style="width:110px"></colgroup>
          <thead><tr><th>วันที่</th><th>กิจกรรม</th><th>โปรแกรม / หลักสูตร</th><th>สถานที่</th><th>สถานะ</th></tr></thead>
          <tbody>
            @foreach($tabs['activities'] as $row)
              <tr>
                <td class="cd-nowrap">@thaidate($row['date'])</td>
                <td>{{ $row['name'] }}</td>
                <td class="text-secondary">{{ $row['program'] }}</td>
                <td class="text-secondary">{{ $row['venue'] }}</td>
                <td><span class="cd-pill {{ $row['joined'] ? 'is-ok' : '' }}">{{ $row['status'] }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>

    <div class="cd-panel-body" data-panel="health" hidden>
      <p class="cd-note">ผลสุขภาพทั้งหมดดึงจากแบบประเมินที่ผู้ตอบส่งเข้ามา ไม่กรอกคะแนนในโปรไฟล์</p>
      @if(count($member['rounds']) === 0)
        <p class="cd-empty">ยังไม่มีรอบติดตามสำหรับคนนี้</p>
      @else
        <table class="cd-data-table">
          <colgroup><col><col style="width:150px"><col style="width:140px"><col style="width:150px"></colgroup>
          <thead><tr><th>รอบติดตาม</th><th>ครบกำหนด</th><th>สถานะ</th><th>ตอบเมื่อ</th></tr></thead>
          <tbody>
            @foreach($member['rounds'] as $r)
              <tr>
                <td>
                  <span class="cd-dot {{ $r['state'] === 'ตอบแล้ว' ? 'is-done' : '' }}"></span>
                  {{ $r['name'] }}
                </td>
                <td class="cd-nowrap text-secondary">@thaidate($r['dueDate'])</td>
                <td><span class="cd-pill {{ $r['state'] === 'ตอบแล้ว' ? 'is-ok' : ($r['state'] === 'เกินกำหนด' ? 'is-over' : 'is-wait') }}">{{ $r['state'] }}</span></td>
                <td class="cd-nowrap">@if($r['answeredAt'])@thaidate($r['answeredAt'])@else—@endif</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>

    <div class="cd-panel-body" data-panel="purchases" hidden>
      @if($tabs['purchases']->isEmpty())
        <p class="cd-empty">ยังไม่มีการซื้อสินค้า</p>
      @else
        <p class="cd-note">รวม {{ number_format($tabs['purchaseTotal']) }} บาท · {{ $tabs['purchases']->count() }} รายการ</p>
        <table class="cd-data-table">
          <colgroup><col style="width:110px"><col><col style="width:18%"><col style="width:110px"><col style="width:110px"><col style="width:100px"><col style="width:130px"></colgroup>
          <thead><tr><th>วันที่ซื้อ</th><th>รายการสินค้า</th><th>ร้านที่ซื้อ</th><th>ช่องทางซื้อ</th><th>สถานะ</th><th class="cd-right">ยอดเงิน</th><th>บันทึกโดย</th></tr></thead>
          <tbody>
            @foreach($tabs['purchases'] as $row)
              <tr>
                <td class="cd-nowrap">@thaidate($row['date'])</td>
                <td>{{ $row['items'] }}</td>
                <td class="text-secondary">{{ $row['store'] }}</td>
                <td class="text-secondary">{{ $row['channel'] }}</td>
                <td><span class="cd-pill {{ $row['paid'] ? 'is-ok' : 'is-wait' }}">{{ $row['status'] }}</span></td>
                <td class="cd-right cd-num">{{ number_format($row['amount']) }}</td>
                <td class="text-secondary">{{ $row['by'] }}</td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr>
              <td colspan="4">รวมทั้งหมด</td>
              <td class="text-secondary">{{ $tabs['purchases']->count() }} รายการ</td>
              <td class="cd-right cd-num"><strong>{{ number_format($tabs['purchaseTotal']) }} ฿</strong></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      @endif
    </div>

    <div class="cd-panel-body" data-panel="notes" hidden>
      @if($tabs['notes']->isEmpty())
        <p class="cd-empty">ยังไม่มีประวัติการติดตาม</p>
      @else
        <table class="cd-data-table">
          <colgroup><col style="width:120px"><col style="width:130px"><col style="width:130px"><col><col style="width:140px"></colgroup>
          <thead><tr><th>วันที่ / เวลา</th><th>ที่มา</th><th>ประเภท</th><th>รายละเอียด</th><th>ดำเนินการโดย</th></tr></thead>
          <tbody>
            @foreach($tabs['notes'] as $row)
              <tr>
                <td class="cd-nowrap">
                  @thaidate($row['at'])
                  <span class="cd-sub">{{ $row['at']?->format('H:i') }} น.</span>
                </td>
                <td><span class="cd-pill">{{ $row['source'] }}</span></td>
                <td class="text-secondary">{{ $row['kind'] }}</td>
                <td>{{ $row['body'] }}</td>
                <td class="text-secondary">{{ $row['by'] }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>
  </section>
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

  /* สลับแท็บ — ทุกแผงอยู่ใน DOM แล้ว ที่นี่แค่ซ่อน/แสดง ไม่ยิงเซิร์ฟเวอร์ซ้ำ
     ข้อมูลทั้งสี่แท็บของคนหนึ่งคนรวมกันไม่กี่สิบแถว โหลดทีเดียวเร็วกว่ารอ request ต่อแท็บ */
  var tabs = Array.prototype.slice.call(document.querySelectorAll('.cd-tab'));

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var name = tab.getAttribute('data-tab');

      tabs.forEach(function (t) { t.classList.toggle('is-active', t === tab); });
      document.querySelectorAll('[data-panel]').forEach(function (panel) {
        panel.hidden = panel.getAttribute('data-panel') !== name;
      });
    });
  });

  if (stopForm) {
    stopForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var reason = document.getElementById('cd-stop-reason').value;
      var submitBtn = document.getElementById('cd-stop-submit');
      submitBtn.disabled = true;

      fetch('{{ route('admin.cohort.stop', $member['db_id']) }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-HTTP-Method-Override': 'PATCH'
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
