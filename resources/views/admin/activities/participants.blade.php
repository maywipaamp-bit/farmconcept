@extends('layouts.admin')

@section('title', 'ลงทะเบียน · '.$activity->name)

@section('content')
  @include('admin.activities.partials.detail-header', ['activeTab' => 'participants'])

  @unless ($activity->requires_registration)
    @include('admin.activities.partials.detail-notice', [
      'message' => 'กิจกรรมนี้ไม่ได้กำหนดให้ลงทะเบียนล่วงหน้า',
      'detail' => 'รายชื่อด้านล่าง (ถ้ามี) มาจากการเพิ่มโดยเจ้าหน้าที่ เช่น Walk-in หน้างาน — เปิดรับลงทะเบียนได้ที่หน้าแก้ไขกิจกรรม',
    ])
  @endunless

  <div class="card aov-pt-card">
    <div class="aov-pt-toolbar">
      <h2 class="aov-section-title mb-0">รายชื่อผู้เข้าร่วม</h2>
      <div class="aov-pt-tools">
        @if ($registerQr)
          {{-- เปิด QR ให้ผู้เข้าร่วมสแกนลงทะเบียนเองหน้างาน --}}
          <button type="button" class="btn btn-outline" id="aov-pt-qr-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14h1M14 20h1M18 18h3v3h-3z"/></svg>
            เปิด QR Code
          </button>
        @endif
        {{-- เลือกคอลัมน์ที่จะแสดง — จำค่าไว้ต่อกิจกรรมใน localStorage --}}
        <div class="aov-pt-picker">
          <button type="button" class="btn btn-outline" id="aov-pt-cols-btn" aria-expanded="false">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9.5 4v16M15.5 4v16"/></svg>
            คอลัมน์
          </button>
          <div class="aov-pt-picker-panel" id="aov-pt-cols-panel" hidden>
            <div class="aov-pt-picker-title">เลือกคอลัมน์ที่แสดง</div>
            @foreach ($columns as $col)
              <label class="aov-pt-picker-item{{ $col['fixed'] ? ' is-fixed' : '' }}">
                <input type="checkbox" value="{{ $col['key'] }}" checked {{ $col['fixed'] ? 'disabled' : '' }}>
                <span>{{ $col['label'] }}</span>
              </label>
            @endforeach
          </div>
        </div>
        <button type="button" class="btn btn-outline" id="aov-pt-export" {{ $rows->isEmpty() ? 'disabled' : '' }}>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 4v11M8 11.5l4 4 4-4M5 19.5h14"/></svg>
          ส่งออก Excel
        </button>
        @if ($registerUrl)
          {{-- แอดมินลงทะเบียนแทนผู้เข้าร่วม — เปิดหน้าลงทะเบียนจริงในแท็บใหม่ --}}
          <a class="btn btn-primary" href="{{ $registerUrl }}" target="_blank" rel="noopener">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            ลงทะเบียน
          </a>
        @endif
      </div>
    </div>

    @if ($rows->isEmpty())
      <div class="state-placeholder">
        <span class="state-placeholder-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></svg></span>
        <div class="state-placeholder-title">ยังไม่มีผู้ลงทะเบียน</div>
        <div class="state-placeholder-desc">รายชื่อจะแสดงที่นี่เมื่อมีผู้ลงทะเบียนเข้าร่วมกิจกรรม</div>
      </div>
    @else
      <div class="aov-pt-scroll">
        <table class="aov-pt-table">
          <thead>
            <tr>
              <th class="aov-pt-num">#</th>
              @foreach ($columns as $col)
                <th data-col="{{ $col['key'] }}">{{ $col['label'] }}</th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach ($rows as $index => $row)
              <tr>
                <td class="aov-pt-num">{{ $index + 1 }}</td>
                @foreach ($columns as $col)
                  @if ($col['key'] === 'name')
                    <td data-col="name">
                      <div>{{ $row['name'] }}</div>
                      {{-- เบอร์นี้เคยลงทะเบียนกิจกรรมอื่นกับเรา — กดเพื่อดูว่ากิจกรรมไหนบ้าง --}}
                      @if ($row['prior'] > 0)
                        <button type="button" class="aov-pt-history" data-history-index="{{ $index }}">
                          เคยร่วมกิจกรรม {{ $row['prior'] }} ครั้ง
                        </button>
                      @endif
                    </td>
                  @elseif ($col['key'] === 'slip')
                    <td data-col="slip">
                      @if ($row['slip'])
                        {{-- เปิด popup ดูสลิปพร้อมปุ่มตัดสินสถานะ ไม่บอกสถานะซ้ำที่นี่ --}}
                        <button type="button" class="aov-pt-slip" data-slip-index="{{ $index }}">ดูสลิป</button>
                      @else
                        —
                      @endif
                    </td>
                  @elseif ($col['key'] === 'payment')
                    <td data-col="payment">
                      {{-- เปลี่ยนสถานะได้จากตาราง — ใช้ endpoint เดียวกับหน้าผู้ลงทะเบียน
                           สีของช่องสื่อสถานะปัจจุบัน · "รอตรวจสอบ" เป็นสถานะจากระบบ เลือกกลับเองไม่ได้ --}}
                      @php
                        $payClass = ['ชำระแล้ว' => 'is-paid', 'ยังไม่ชำระ' => 'is-unpaid', 'รอตรวจสอบ' => 'is-pending'][$row['payment']] ?? '';
                      @endphp
                      <select class="aov-pt-pay {{ $payClass }}" data-code="{{ $row['code'] }}" data-current="{{ $row['payment'] }}">
                        @foreach (array_values(array_unique(array_filter([$row['payment'], 'ชำระแล้ว', 'ยังไม่ชำระ']))) as $status)
                          <option value="{{ $status }}" @selected($status === $row['payment'])>{{ $status }}</option>
                        @endforeach
                      </select>
                    </td>
                  @else
                    <td data-col="{{ $col['key'] }}">{{ $row[$col['key']] !== '' ? $row[$col['key']] : '—' }}</td>
                  @endif
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="aov-pt-foot">ทั้งหมด {{ $rows->count() }} คน</div>
    @endif
  </div>
@endsection

@section('modals')
@if ($registerQr)
{{-- QR ลงทะเบียน — เปิดจากปุ่มบนหัวตาราง ให้ผู้เข้าร่วมสแกนลงทะเบียนเองหน้างาน --}}
<div class="modal-overlay" id="aov-pt-qr-modal">
  <div class="modal modal-sm aov-qr-modal">
    <div class="modal-header">
      <h3 class="modal-title">QR ลงทะเบียนเข้าร่วม</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิด">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body aov-qr-modal-body">
      <img src="{{ $registerQr['imageUrl'] }}" alt="QR ลงทะเบียนเข้าร่วม">
      <a class="aov-qr-modal-url" href="{{ $registerQr['url'] }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://#', '', $registerQr['url']) }}</a>
    </div>
    <div class="modal-footer">
      <a class="btn btn-outline" href="{{ $registerQr['downloadUrl'] }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 4v11M8 11.5l4 4 4-4M5 19.5h14"/></svg>
        ดาวน์โหลด
      </a>
    </div>
  </div>
</div>
@endif

{{-- สลิปการชำระเงิน — ดูรูปแล้วตัดสินสถานะได้จากที่เดียวกัน --}}
<div class="modal-overlay" id="aov-pt-slip-modal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div>
        <h3 class="modal-title">สลิปการชำระเงิน</h3>
        <div class="aov-pt-history-sub" id="aov-pt-slip-sub"></div>
      </div>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิด">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body aov-pt-slip-body">
      <img id="aov-pt-slip-image" src="" alt="สลิปการชำระเงิน">
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline aov-pt-slip-reject" id="aov-pt-slip-reject">สลิปไม่ถูกต้อง</button>
      <button type="button" class="btn btn-primary" id="aov-pt-slip-confirm">ยืนยันการชำระเงิน</button>
    </div>
  </div>
</div>

{{-- ประวัติการเข้าร่วมกิจกรรมของเบอร์นี้ — เปิดจากบรรทัด "เคยร่วมกิจกรรม N ครั้ง" --}}
<div class="modal-overlay" id="aov-pt-history-modal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <div>
        <h3 class="modal-title">ประวัติการเข้าร่วมกิจกรรม</h3>
        <div class="aov-pt-history-sub" id="aov-pt-history-sub"></div>
      </div>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิด">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <table class="aov-pt-history-table">
        <thead>
          <tr><th class="aov-pt-num">#</th><th>ชื่อกิจกรรม</th><th>วันที่จัดกิจกรรม</th></tr>
        </thead>
        <tbody id="aov-pt-history-rows"></tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" data-close-modal>ปิด</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="@assetv('assets/js/activity-module.js')"></script>
@endpush

@push('page-script')
<script type="module">
(function () {
  var columns = @json($columns);
  var rows = @json($rows);
  var storageKey = 'tfc-aov-participants-cols-' + @json($activity->code);
  var paymentUrl = @json(route('admin.activities.registrants.payment', ['registration' => '__CODE__']));

  var qrBtn = document.getElementById('aov-pt-qr-btn');
  if (qrBtn) {
    qrBtn.addEventListener('click', function () { window.TFC.openModal('aov-pt-qr-modal'); });
  }

  var panel = document.getElementById('aov-pt-cols-panel');
  var button = document.getElementById('aov-pt-cols-btn');

  /* คอลัมน์ที่ผู้ใช้ซ่อนไว้ — เก็บเป็นรายการ key ต่อกิจกรรม เปิดหน้าใหม่แล้วค่าคงเดิม */
  var hidden = [];
  try { hidden = JSON.parse(localStorage.getItem(storageKey) || '[]'); } catch (e) {}

  function applyColumns() {
    columns.forEach(function (col) {
      var off = hidden.indexOf(col.key) !== -1 && !col.fixed;
      document.querySelectorAll('[data-col="' + col.key + '"]').forEach(function (cell) {
        cell.hidden = off;
      });
    });
    panel.querySelectorAll('input[type=checkbox]').forEach(function (box) {
      if (!box.disabled) box.checked = hidden.indexOf(box.value) === -1;
    });
  }

  button.addEventListener('click', function (e) {
    e.stopPropagation();
    var open = panel.hidden;
    panel.hidden = !open;
    button.setAttribute('aria-expanded', String(open));
  });

  panel.addEventListener('click', function (e) { e.stopPropagation(); });

  document.addEventListener('click', function () {
    panel.hidden = true;
    button.setAttribute('aria-expanded', 'false');
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { panel.hidden = true; button.setAttribute('aria-expanded', 'false'); }
  });

  panel.addEventListener('change', function (e) {
    var box = e.target;
    if (box.type !== 'checkbox' || box.disabled) return;
    if (box.checked) {
      hidden = hidden.filter(function (k) { return k !== box.value; });
    } else if (hidden.indexOf(box.value) === -1) {
      hidden.push(box.value);
    }
    try { localStorage.setItem(storageKey, JSON.stringify(hidden)); } catch (e2) {}
    applyColumns();
  });

  /* ส่งออกเฉพาะคอลัมน์ที่กำลังแสดง — ไฟล์ CSV มี BOM เปิดด้วย Excel ได้ (TFC.exportCsv) */
  document.getElementById('aov-pt-export').addEventListener('click', function () {
    var visible = columns.filter(function (col) {
      return col.fixed || hidden.indexOf(col.key) === -1;
    });
    /* คอลัมน์ "เคยร่วมกิจกรรม" ไม่อยู่ในตัวเลือกคอลัมน์ (เป็นบรรทัดย่อใต้ชื่อ)
       แต่ในไฟล์ส่งออกแยกเป็นคอลัมน์ของตัวเองต่อจากชื่อ ให้เอาไปกรองใน Excel ได้ */
    var headers = ['#'];
    visible.forEach(function (col) {
      headers.push(col.label);
      if (col.key === 'name') headers.push('เคยร่วมกิจกรรมอื่น (ครั้ง)');
    });
    window.TFC.exportCsv(
      'ลงทะเบียน-' + @json($activity->code) + '.csv',
      headers,
      rows.map(function (row, i) {
        var line = [i + 1];
        visible.forEach(function (col) {
          if (col.key === 'slip') {
            line.push(row.slip ? (row.slip.status || 'มีสลิป') : '');
          } else {
            line.push(row[col.key] || '');
          }
          if (col.key === 'name') line.push(row.prior || 0);
        });
        return line;
      })
    );
  });

  /* เปลี่ยนสถานะชำระเงิน — endpoint เดียวกับหน้าผู้ลงทะเบียน (ปิดผลสลิปที่ค้างให้พร้อมกัน)
     ใช้ร่วมกันทั้ง dropdown ในตารางและปุ่มใน popup สลิป */
  var csrf = document.querySelector('meta[name="csrf-token"]');
  var PAY_CLASS = { 'ชำระแล้ว': 'is-paid', 'ยังไม่ชำระ': 'is-unpaid', 'รอตรวจสอบ': 'is-pending' };

  function savePayment(code, status) {
    return fetch(paymentUrl.replace('__CODE__', encodeURIComponent(code)), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : '',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-HTTP-Method-Override': 'PATCH'
      },
      body: JSON.stringify({ status: status })
    }).then(function (res) {
      return res.json().catch(function () { return {}; }).then(function (payload) {
        if (!res.ok) throw new Error(payload.message || 'เปลี่ยนสถานะไม่สำเร็จ กรุณาลองใหม่');
        return payload;
      });
    });
  }

  /* สถานะใหม่สะท้อนทั้งค่าและสีของช่องในตาราง แถวเดียวกันเสมอไม่ว่าสั่งจากไหน */
  function applyPayment(code, status) {
    var select = document.querySelector('.aov-pt-pay[data-code="' + code + '"]');
    if (select) {
      select.value = status;
      select.setAttribute('data-current', status);
      Object.keys(PAY_CLASS).forEach(function (key) { select.classList.remove(PAY_CLASS[key]); });
      if (PAY_CLASS[status]) select.classList.add(PAY_CLASS[status]);
    }
    var row = rows.find(function (r) { return r.code === code; });
    if (row) row.payment = status;
  }

  document.querySelectorAll('.aov-pt-pay').forEach(function (select) {
    select.addEventListener('change', function () {
      var previous = select.getAttribute('data-current');
      var status = select.value;
      var code = select.getAttribute('data-code');
      select.disabled = true;

      savePayment(code, status)
        .then(function (payload) {
          applyPayment(code, status);
          window.TFC.showToast(payload.message || 'บันทึกสถานะชำระเงินแล้ว', 'success');
        })
        .catch(function (err) {
          select.value = previous;
          window.TFC.showToast(err.message, 'danger');
        })
        .finally(function () { select.disabled = false; });
    });
  });

  /* popup สลิป — ดูรูปแล้วตัดสินได้เลย: ยืนยัน = ชำระแล้ว · ไม่ถูกต้อง = ยังไม่ชำระ */
  var slipCode = null;
  var slipConfirm = document.getElementById('aov-pt-slip-confirm');
  var slipReject = document.getElementById('aov-pt-slip-reject');

  document.querySelectorAll('[data-slip-index]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var row = rows[parseInt(btn.getAttribute('data-slip-index'), 10)];
      if (!row || !row.slip) return;
      slipCode = row.code;
      document.getElementById('aov-pt-slip-sub').textContent =
        row.name + ' · ' + row.phone + ' · สถานะสลิป: ' + (row.slip.status || '—');
      document.getElementById('aov-pt-slip-image').src = row.slip.url;
      window.TFC.openModal('aov-pt-slip-modal');
    });
  });

  function decideSlip(status, button, pendingLabel) {
    if (!slipCode) return;
    var code = slipCode;
    var confirmLabel = slipConfirm.textContent;
    var rejectLabel = slipReject.textContent;
    slipConfirm.disabled = slipReject.disabled = true;
    button.textContent = pendingLabel;

    savePayment(code, status)
      .then(function (payload) {
        applyPayment(code, status);
        var row = rows.find(function (r) { return r.code === code; });
        if (row && row.slip) row.slip.status = status === 'ชำระแล้ว' ? 'ชำระแล้ว' : 'ปฏิเสธ';
        window.TFC.closeModal('aov-pt-slip-modal');
        window.TFC.showToast(payload.message || 'บันทึกสถานะชำระเงินแล้ว', 'success');
      })
      .catch(function (err) {
        window.TFC.showToast(err.message, 'danger');
      })
      .finally(function () {
        slipConfirm.disabled = slipReject.disabled = false;
        slipConfirm.textContent = confirmLabel;
        slipReject.textContent = rejectLabel;
      });
  }

  slipConfirm.addEventListener('click', function () { decideSlip('ชำระแล้ว', slipConfirm, 'กำลังบันทึก…'); });
  slipReject.addEventListener('click', function () { decideSlip('ยังไม่ชำระ', slipReject, 'กำลังบันทึก…'); });

  /* popup ประวัติ — ข้อมูลอยู่ใน rows แล้ว (priorList) ไม่ต้องยิงคำขอเพิ่ม */
  document.querySelectorAll('[data-history-index]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var row = rows[parseInt(btn.getAttribute('data-history-index'), 10)];
      if (!row || !row.priorList || !row.priorList.length) return;

      document.getElementById('aov-pt-history-sub').textContent =
        row.name + ' · ' + row.phone + ' · เคยร่วมกิจกรรมกับเรา ' + row.prior + ' ครั้ง';

      document.getElementById('aov-pt-history-rows').innerHTML = row.priorList.map(function (item, i) {
        return '<tr><td class="aov-pt-num">' + (i + 1) + '</td>' +
          '<td>' + window.TFC.escapeHtml(item.name) + '</td>' +
          '<td>' + window.TFC.escapeHtml(item.date) + '</td></tr>';
      }).join('');

      window.TFC.openModal('aov-pt-history-modal');
    });
  });

  applyColumns();
})();
</script>
@endpush
