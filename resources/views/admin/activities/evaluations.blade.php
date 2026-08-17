@extends('layouts.admin')

@section('title', 'แบบประเมิน · '.$activity->name)

@section('content')
  @include('admin.activities.partials.detail-header', ['activeTab' => 'evaluations'])

  @unless ($activity->has_post_survey)
    @include('admin.activities.partials.detail-notice', [
      'message' => 'กิจกรรมนี้ไม่ได้เปิดแบบประเมินหลังกิจกรรม',
      'detail' => 'จึงไม่มีปุ่มทำแบบประเมินและ QR ให้สแกน — เปิดใช้แบบประเมินได้ที่หน้าแก้ไขกิจกรรม',
    ])
  @endunless

  <div class="card aov-pt-card">
    <div class="aov-pt-toolbar">
      <div>
        <h2 class="aov-section-title mb-0">คำตอบแบบประเมิน</h2>
        {{-- คำตอบเป็นนิรนามโดยออกแบบ — ระบุได้แค่ลำดับผู้ตอบ ไม่ผูกกับรายชื่อ --}}
        <div class="aov-pt-toolbar-sub">ตอบแล้ว {{ $activity->responses_count }} ชุด · คำตอบไม่ระบุตัวตนผู้ตอบ</div>
      </div>
      <div class="aov-pt-tools">
        @if ($surveyQr)
          {{-- เปิด QR ให้ผู้เข้าร่วมสแกนหน้างาน --}}
          <button type="button" class="btn btn-outline" id="aov-ev-qr-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14h1M14 20h1M18 18h3v3h-3z"/></svg>
            เปิด QR Code
          </button>
        @endif
        <div class="aov-pt-picker">
          <button type="button" class="btn btn-outline" id="aov-ev-cols-btn" aria-expanded="false">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9.5 4v16M15.5 4v16"/></svg>
            คอลัมน์
          </button>
          <div class="aov-pt-picker-panel" id="aov-ev-cols-panel" hidden>
            <div class="aov-pt-picker-title">เลือกคอลัมน์ที่แสดง</div>
            @foreach ($columns as $col)
              <label class="aov-pt-picker-item{{ $col['fixed'] ? ' is-fixed' : '' }}">
                <input type="checkbox" value="{{ $col['key'] }}" checked {{ $col['fixed'] ? 'disabled' : '' }}>
                <span>{{ $col['label'] }}</span>
              </label>
            @endforeach
          </div>
        </div>
        <button type="button" class="btn btn-outline" id="aov-ev-export" {{ $rows->isEmpty() ? 'disabled' : '' }}>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 4v11M8 11.5l4 4 4-4M5 19.5h14"/></svg>
          ส่งออก Excel
        </button>
        @if ($surveyUrl)
          {{-- แอดมินทำแบบประเมินแทนผู้เข้าร่วม — เปิดหน้าแบบประเมินจริงในแท็บใหม่ --}}
          <a class="btn btn-primary" href="{{ $surveyUrl }}" target="_blank" rel="noopener">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
            ทำแบบประเมิน
          </a>
        @endif
      </div>
    </div>

    @if ($rows->isEmpty())
      <div class="state-placeholder">
        <span class="state-placeholder-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 3.5h7l4 4v13H7zM14 3.5v4h4M10 12h5M10 15.5h5"/></svg></span>
        <div class="state-placeholder-title">ยังไม่มีคำตอบแบบประเมิน</div>
        <div class="state-placeholder-desc">คำตอบจะแสดงที่นี่เมื่อผู้เข้าร่วมตอบแบบประเมินหลังกิจกรรม</div>
      </div>
    @else
      <div class="aov-pt-scroll">
        <table class="aov-pt-table">
          <thead>
            <tr>
              <th class="aov-pt-num">#</th>
              @foreach ($columns as $col)
                <th data-col="{{ $col['key'] }}">
                  {{-- คำถามอาจยาว — จำกัดความกว้างหัวคอลัมน์ ข้อความเต็มอยู่ใน title --}}
                  <span class="aov-ev-th" title="{{ $col['label'] }}">{{ $col['label'] }}</span>
                </th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach ($rows as $index => $row)
              <tr>
                <td class="aov-pt-num">{{ $index + 1 }}</td>
                @foreach ($columns as $col)
                  @if ($col['key'] === 'average')
                    @php
                      $avg = $row['average'];
                      $grade = $row['grade'];
                      /* โทนป้ายอิงจากเกรดเดียวกับที่ใช้ทั่วระบบ — ไม่คิดช่วงคะแนนซ้ำที่นี่ */
                      $gradeClass = match ($grade['tone'] ?? null) {
                          'success' => 'is-in',
                          'warning' => 'is-mid',
                          'danger' => 'is-low',
                          default => '',
                      };
                    @endphp
                    <td data-col="average">
                      @if ($avg !== null)
                        {{-- ดาว + ตัวเลขอยู่บรรทัดเดียวกัน อ่านระดับได้เร็วแต่ยังเทียบค่าจริงได้ --}}
                        <div class="aov-ev-avg">
                          <span class="aov-ev-avg-stars" aria-hidden="true">
                            @for ($i = 1; $i <= 5; $i++)
                              <span class="aov-rp-star aov-rp-star--sm {{ $i <= round($avg) ? 'is-filled' : '' }}">★</span>
                            @endfor
                          </span>
                          <span class="aov-ev-avg-num">{{ number_format($avg, 1) }}</span>
                        </div>
                        <span class="aov-pt-status {{ $gradeClass }}">{{ $grade['label'] }}</span>
                      @else
                        —
                      @endif
                    </td>
                  @else
                    <td data-col="{{ $col['key'] }}">{{ ($row[$col['key']] ?? '') !== '' ? $row[$col['key']] : '—' }}</td>
                  @endif
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="aov-pt-foot">ทั้งหมด {{ $rows->count() }} ชุด</div>
    @endif
  </div>
@endsection

@section('modals')
@if ($surveyQr)
{{-- QR แบบประเมิน — เปิดจากปุ่มบนหัวตาราง ให้ผู้เข้าร่วมสแกนหน้างาน --}}
<div class="modal-overlay" id="aov-ev-qr-modal">
  <div class="modal modal-sm aov-qr-modal">
    <div class="modal-header">
      <h3 class="modal-title">QR แบบประเมินหลังกิจกรรม</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิด">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body aov-qr-modal-body">
      <img src="{{ $surveyQr['imageUrl'] }}" alt="QR แบบประเมินหลังกิจกรรม">
      <a class="aov-qr-modal-url" href="{{ $surveyQr['url'] }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://#', '', $surveyQr['url']) }}</a>
    </div>
    <div class="modal-footer">
      <a class="btn btn-outline" href="{{ $surveyQr['downloadUrl'] }}">
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
  var storageKey = 'tfc-aov-evaluations-cols-' + @json($activity->code);

  var panel = document.getElementById('aov-ev-cols-panel');
  var button = document.getElementById('aov-ev-cols-btn');

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

  document.getElementById('aov-ev-export').addEventListener('click', function () {
    var visible = columns.filter(function (col) {
      return col.fixed || hidden.indexOf(col.key) === -1;
    });
    window.TFC.exportCsv(
      'แบบประเมิน-' + @json($activity->code) + '.csv',
      ['#'].concat(visible.map(function (col) { return col.label; })),
      rows.map(function (row, i) {
        return [i + 1].concat(visible.map(function (col) {
          var value = row[col.key];
          return value === null || value === undefined ? '' : value;
        }));
      })
    );
  });

  var qrBtn = document.getElementById('aov-ev-qr-btn');
  if (qrBtn) {
    qrBtn.addEventListener('click', function () { window.TFC.openModal('aov-ev-qr-modal'); });
  }

  applyColumns();
})();
</script>
@endpush
