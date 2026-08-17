@extends('layouts.admin')

@section('title', 'Check-in · '.$activity->name)

@section('content')
  @include('admin.activities.partials.detail-header', ['activeTab' => 'checkins'])

  @unless ($activity->requires_checkin)
    @include('admin.activities.partials.detail-notice', [
      'message' => 'กิจกรรมนี้ไม่ได้กำหนดให้มีการ Check-in',
      'detail' => 'ผู้เข้าร่วมไม่ต้องยืนยันตัวตนหน้างาน ข้อมูลในตารางจึงอาจว่าง — เปิดใช้ Check-in ได้ที่หน้าแก้ไขกิจกรรม',
    ])
  @endunless

  <div class="card aov-pt-card">
    <div class="aov-pt-toolbar">
      <div>
        <h2 class="aov-section-title mb-0">การเช็คอิน</h2>
        <div class="aov-pt-toolbar-sub" id="aov-ck-summary">เช็คอินแล้ว {{ $activity->checked_in_count }} จาก {{ $activity->registrations_count }} คน</div>
      </div>
      <div class="aov-pt-tools">
        @if ($checkinQr)
          {{-- เปิด QR ให้ผู้เข้าร่วมสแกนยืนยันตัวตนหน้างานด้วยตนเอง --}}
          <button type="button" class="btn btn-outline" id="aov-ck-qr-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14h1M14 20h1M18 18h3v3h-3z"/></svg>
            เปิด QR Code
          </button>
        @endif
        <div class="aov-pt-picker">
          <button type="button" class="btn btn-outline" id="aov-ck-cols-btn" aria-expanded="false">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9.5 4v16M15.5 4v16"/></svg>
            คอลัมน์
          </button>
          <div class="aov-pt-picker-panel" id="aov-ck-cols-panel" hidden>
            <div class="aov-pt-picker-title">เลือกคอลัมน์ที่แสดง</div>
            @foreach ($columns as $col)
              <label class="aov-pt-picker-item{{ $col['fixed'] ? ' is-fixed' : '' }}">
                <input type="checkbox" value="{{ $col['key'] }}" checked {{ $col['fixed'] ? 'disabled' : '' }}>
                <span>{{ $col['label'] }}</span>
              </label>
            @endforeach
          </div>
        </div>
        <button type="button" class="btn btn-outline" id="aov-ck-export" {{ $rows->isEmpty() ? 'disabled' : '' }}>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 4v11M8 11.5l4 4 4-4M5 19.5h14"/></svg>
          ส่งออก Excel
        </button>
      </div>
    </div>

    @if ($rows->isEmpty())
      <div class="state-placeholder">
        <span class="state-placeholder-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 12.5l2.5 2.5L16 9.5"/></svg></span>
        <div class="state-placeholder-title">ยังไม่มีผู้ลงทะเบียน</div>
        <div class="state-placeholder-desc">ข้อมูลการเช็คอินจะแสดงที่นี่เมื่อมีผู้ลงทะเบียนเข้าร่วมกิจกรรม</div>
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
              <tr data-code="{{ $row['code'] }}">
                <td class="aov-pt-num">{{ $index + 1 }}</td>
                @foreach ($columns as $col)
                  @if ($col['key'] === 'name')
                    <td data-col="name">
                      <div>{{ $row['name'] }}</div>
                      @if ($row['walkIn'])
                        <div class="aov-pt-walkin">Walk-in</div>
                      @endif
                    </td>
                  @elseif ($col['key'] === 'status')
                    <td data-col="status">
                      {{-- เลือกสถานะได้สองทาง — เช็คอินแทน หรือยกเลิกเช็คอิน
                           endpoint เดียวกับหน้า Check-in หน้างาน บันทึก audit log ทั้งคู่ --}}
                      <select class="aov-pt-pay aov-ck-select {{ $row['checkedIn'] ? 'is-paid' : 'is-off' }}"
                              data-checkin-select data-current="{{ $row['checkedIn'] ? 'in' : 'out' }}">
                        <option value="out" @selected(! $row['checkedIn'])>ยังไม่เช็คอิน</option>
                        <option value="in" @selected($row['checkedIn'])>เช็คอินแล้ว</option>
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
@if ($checkinQr)
{{-- QR Check-in — เปิดจากปุ่มบนหัวตาราง ให้ผู้เข้าร่วมสแกนยืนยันตัวตนเองหน้างาน --}}
<div class="modal-overlay" id="aov-ck-qr-modal">
  <div class="modal modal-sm aov-qr-modal">
    <div class="modal-header">
      <h3 class="modal-title">QR Check-in หน้างาน</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิด">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body aov-qr-modal-body">
      <img src="{{ $checkinQr['imageUrl'] }}" alt="QR Check-in หน้างาน">
      <a class="aov-qr-modal-url" href="{{ $checkinQr['url'] }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://#', '', $checkinQr['url']) }}</a>
    </div>
    <div class="modal-footer">
      <a class="btn btn-outline" href="{{ $checkinQr['downloadUrl'] }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 4v11M8 11.5l4 4 4-4M5 19.5h14"/></svg>
        ดาวน์โหลด
      </a>
    </div>
  </div>
</div>
@endif
@endsection

@push('scripts')
<script src="@assetv('assets/js/activity-module.js')"></script>
@endpush

@push('page-script')
<script type="module">
(function () {
  var columns = @json($columns);
  var rows = @json($rows);
  var storageKey = 'tfc-aov-checkins-cols-' + @json($activity->code);
  var checkinUrl = @json(route('admin.activities.checkin.store', $activity->code));
  var actorName = @json(auth()->user()->name ?? '');
  var checkedInCount = @json($activity->checked_in_count);
  var totalCount = @json($activity->registrations_count);

  var qrBtn = document.getElementById('aov-ck-qr-btn');
  if (qrBtn) {
    qrBtn.addEventListener('click', function () { window.TFC.openModal('aov-ck-qr-modal'); });
  }

  var panel = document.getElementById('aov-ck-cols-panel');
  var button = document.getElementById('aov-ck-cols-btn');

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

  document.getElementById('aov-ck-export').addEventListener('click', function () {
    var visible = columns.filter(function (col) {
      return col.fixed || hidden.indexOf(col.key) === -1;
    });
    window.TFC.exportCsv(
      'เช็คอิน-' + @json($activity->code) + '.csv',
      ['#'].concat(visible.map(function (col) { return col.label; })),
      rows.map(function (row, i) {
        return [i + 1].concat(visible.map(function (col) { return row[col.key] || ''; }));
      })
    );
  });

  /* ---------- เลือกสถานะเช็คอินโดยแอดมิน ----------
     เช็คอินแทนหรือยกเลิกก็ได้ — endpoint เดียวกับหน้า Check-in หน้างาน
     (บันทึก audit log ทั้งสองทาง) สำเร็จแล้วอัปเดตแถวตรงนั้นเลย ไม่ reload ทั้งหน้า */
  var csrf = document.querySelector('meta[name="csrf-token"]');
  var TH_MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

  function thaiDateTime(iso) {
    var d = iso ? new Date(iso) : new Date();
    var hh = String(d.getHours()).padStart(2, '0');
    var mm = String(d.getMinutes()).padStart(2, '0');
    return d.getDate() + ' ' + TH_MONTHS[d.getMonth()] + ' ' + (d.getFullYear() + 543) + ' · ' + hh + ':' + mm + ' น.';
  }

  function setCell(tr, key, text) {
    var cell = tr.querySelector('[data-col="' + key + '"]');
    if (cell) cell.textContent = text;
  }

  function requestJson(url, method, body) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : '',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-HTTP-Method-Override': method
      },
      body: JSON.stringify(body || {})
    }).then(function (res) {
      return res.json().catch(function () { return {}; }).then(function (payload) {
        if (!res.ok) throw new Error(payload.message || 'ทำรายการไม่สำเร็จ กรุณาลองใหม่');
        return payload;
      });
    });
  }

  function updateSummary(delta) {
    checkedInCount += delta;
    document.getElementById('aov-ck-summary').textContent =
      'เช็คอินแล้ว ' + checkedInCount + ' จาก ' + totalCount + ' คน';
  }

  document.querySelectorAll('[data-checkin-select]').forEach(function (select) {
    select.addEventListener('change', function () {
      var tr = select.closest('tr');
      var code = tr.getAttribute('data-code');
      var previous = select.getAttribute('data-current');
      var target = select.value;
      var row = rows.find(function (r) { return r.code === code; });
      select.disabled = true;

      var request = target === 'in'
        ? requestJson(checkinUrl, 'POST', { registrationId: code, source: 'staff' })
        : requestJson(checkinUrl + '/' + encodeURIComponent(code), 'DELETE', {});

      request
        .then(function (payload) {
          var checkedIn = target === 'in';
          var timeText = checkedIn ? thaiDateTime(payload.checkedInAtIso) : '';

          select.setAttribute('data-current', target);
          select.classList.toggle('is-paid', checkedIn);
          select.classList.toggle('is-off', ! checkedIn);
          setCell(tr, 'checked_in_at', timeText || '—');
          setCell(tr, 'method', checkedIn ? 'เจ้าหน้าที่' : '—');
          setCell(tr, 'performed_by', checkedIn ? actorName : '—');

          if (row) {
            row.checkedIn = checkedIn;
            row.status = checkedIn ? 'เช็คอินแล้ว' : 'ยังไม่เช็คอิน';
            row.checked_in_at = timeText;
            row.method = checkedIn ? 'เจ้าหน้าที่' : '';
            row.performed_by = checkedIn ? actorName : '';
          }

          updateSummary(checkedIn ? 1 : -1);
          window.TFC.showToast(
            (checkedIn ? 'เช็คอิน ' : 'ยกเลิกเช็คอิน ') + (row ? row.name : code) + ' แล้ว',
            'success'
          );
        })
        .catch(function (err) {
          select.value = previous;
          window.TFC.showToast(err.message, 'danger');
        })
        .finally(function () { select.disabled = false; });
    });
  });

  applyColumns();
})();
</script>
@endpush
