@extends('layouts.admin')

@section('title', $activity->name)

@php
  /* วันที่แบบไทยย่อ ใช้ซ้ำหลายจุดในหน้านี้ — พ.ศ. และเดือนย่อแบบเดียวกับหน้าผู้ลงทะเบียน */
  $thMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
  $thDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
  $thaiDate = fn ($d, $withYear = true) => $d
      ? $d->day.' '.$thMonths[$d->month - 1].($withYear ? ' '.($d->year + 543) : '')
      : '—';

  $firstRound = $activity->rounds->first();
  $timeText = $firstRound
      ? substr((string) $firstRound->time_start, 0, 5).'–'.substr((string) $firstRound->time_end, 0, 5).' น.'
      : null;

  /* บรรทัดวันเวลาแบบย่อ "พ. 19 ส.ค. 69 · 13:00–16:00 น." — วันย่อ + พ.ศ. สองหลัก */
  $thDaysShort = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
  $dateLine = '—';
  if ($activity->start_date) {
      $d = $activity->start_date;
      $dateLine = $thDaysShort[$d->dayOfWeek].' '.$d->day.' '.$thMonths[$d->month - 1].' '.(($d->year + 543) % 100);
      if ($activity->end_date && ! $activity->end_date->isSameDay($d)) {
          $e = $activity->end_date;
          $dateLine .= ' – '.$e->day.' '.$thMonths[$e->month - 1].' '.(($e->year + 543) % 100);
      }
      if ($timeText) {
          $dateLine .= ' · '.$timeText;
      }
  }

  $locationText = $firstRound?->location ?: $activity->areas->pluck('name')->implode(' · ') ?: '—';
  $instructorText = $activity->instructors->pluck('name')->implode(', ') ?: '—';

  $feeText = $activity->has_fee
      ? number_format((float) $activity->fee).' บาท / ท่าน'
      : 'ไม่มีค่าใช้จ่าย';

  /* นับถอยหลังปิดรับสมัคร — เตือนเป็นสีส้มเฉพาะตอนยังเปิดรับอยู่ */
  $deadlineText = null;
  $deadlineOpen = false;
  if ($activity->requires_registration && $activity->registration_end_at) {
      if ($activity->registration_end_at->isFuture()) {
          $days = (int) now()->startOfDay()->diffInDays($activity->registration_end_at->copy()->startOfDay());
          $deadlineText = $days <= 0 ? 'วันนี้เป็นวันสุดท้ายของการรับสมัคร' : 'อีก '.$days.' วันจะปิดรับสมัคร';
          $deadlineOpen = true;
      } else {
          $deadlineText = 'ปิดรับสมัครแล้ว';
      }
  }
@endphp

@section('content')
  @include('admin.activities.partials.detail-header', ['activeTab' => 'overview'])

  {{-- โครงกริด 2x2 แบบระบุชื่อพื้นที่ — แถวที่ 2 (รอบกิจกรรม + QR และลิงก์) ยืดสูงเท่ากันเสมอ
       ด้วยพฤติกรรมปกติของ CSS Grid (align-items: stretch) โดยไม่ต้องคำนวณความสูงเอง --}}
  <div class="aov-layout">

      {{-- การ์ดรายละเอียดกิจกรรม --}}
      <section class="card aov-detail-card aov-cell-detail">
        <div class="aov-detail-top">
          <div class="aov-cover{{ $activity->cover_image_path ? '' : ' is-empty' }}">
            @if ($activity->cover_image_path)
              <img src="{{ route('admin.activities.cover.show', $activity->code) }}" alt="รูปปก {{ $activity->name }}">
            @else
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            @endif
          </div>
          <div class="aov-detail-text">
            <h2 class="aov-detail-name">{{ $activity->name }}</h2>
            <p class="aov-desc">{{ $activity->description ?: 'ยังไม่มีคำอธิบายกิจกรรม' }}</p>

            <div class="aov-info">
              <div class="aov-info-line">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 9h18"/></svg>
                <span>{{ $dateLine }}</span>
              </div>
              <div class="aov-info-line">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1116 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>{{ $locationText }}</span>
              </div>
              <div class="aov-info-line">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></svg>
                <span>{{ $instructorText }}</span>
              </div>
              <div class="aov-info-line">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M9 4h5a4 4 0 010 8H9M9 4v16M9 4H7m2 8H7m9 8H7"/></svg>
                <span>{{ $feeText }}</span>
              </div>
              @if ($deadlineText)
                <div class="aov-info-line{{ $deadlineOpen ? ' is-deadline' : '' }}">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                  <span>{{ $deadlineText }}</span>
                </div>
              @endif
            </div>
          </div>
        </div>
      </section>

      {{-- รอบกิจกรรม พร้อมจำนวนผู้ลงทะเบียนต่อรอบ --}}
      <section class="card aov-rounds-card aov-cell-rounds">
        <h2 class="aov-section-title">รอบกิจกรรม</h2>
        @forelse ($activity->rounds as $index => $round)
          @php
            $cap = (int) $round->capacity;
            $reg = (int) $round->registrations_count;
            $pct = $cap > 0 ? min(100, round($reg / $cap * 100)) : 0;
            $full = $cap > 0 && $reg >= $cap;
          @endphp
          <div class="aov-round">
            <div class="aov-round-text">
              <div class="aov-round-name">รอบที่ {{ $index + 1 }} · {{ $thDays[$round->round_date->dayOfWeek] }} {{ $thaiDate($round->round_date, false) }}</div>
              <div class="aov-round-sub">{{ substr((string) $round->time_start, 0, 5) }}–{{ substr((string) $round->time_end, 0, 5) }} น.@if ($round->location) · {{ $round->location }}@endif</div>
            </div>
            <div class="aov-round-count">
              <span class="aov-round-num">{{ $reg }}@if ($cap > 0)/{{ $cap }}@endif</span>
              @if ($cap > 0)
                <span class="aov-round-bar" role="img" aria-label="ลงทะเบียนแล้ว {{ $pct }}%">
                  <span class="aov-round-bar-fill{{ $full ? ' is-full' : '' }}" style="width: {{ $pct }}%"></span>
                </span>
              @endif
            </div>
          </div>
        @empty
          <p class="aov-empty">ยังไม่มีรอบกิจกรรม</p>
        @endforelse
      </section>

      {{-- สรุปตัวเลข --}}
      <section class="card aov-summary-card aov-cell-summary">
        <h2 class="aov-section-title">สรุปตัวเลข</h2>
        <dl class="aov-summary">
          <div class="aov-summary-row">
            <dt>ลงทะเบียน</dt>
            <dd>{{ $activity->registrations_count }}@if ($activity->capacity > 0) / {{ $activity->capacity }}@endif</dd>
          </div>
          <div class="aov-summary-row">
            <dt>เช็คอินแล้ว</dt>
            <dd class="is-good">{{ $activity->checked_in_count }} คน</dd>
          </div>
          <div class="aov-summary-row">
            <dt>ตอบแบบประเมิน</dt>
            <dd>{{ $activity->responses_count }} คน</dd>
          </div>
          @if ($activity->has_fee)
            <div class="aov-summary-row">
              <dt>รายรับรวม</dt>
              <dd>{{ number_format($revenue) }} ฿</dd>
            </div>
            <div class="aov-summary-row">
              <dt>รอตรวจสอบสลิป</dt>
              <dd class="{{ $pendingSlips > 0 ? 'is-warn' : '' }}">{{ $pendingSlips }} ใบ</dd>
            </div>
          @endif
        </dl>
      </section>

      {{-- QR และลิงก์ --}}
      @if (count($qrCodes))
        <section class="card aov-qr-card aov-cell-qr">
          <h2 class="aov-section-title">QR และลิงก์</h2>
          @foreach ($qrCodes as $qr)
            <div class="aov-qr" data-qr-label="{{ $qr['label'] }}" data-qr-url="{{ $qr['url'] }}"
                 data-qr-image="{{ $qr['imageUrl'] }}" data-qr-download="{{ $qr['downloadUrl'] }}">
              {{-- กดที่รูปหรือชื่อเพื่อเปิดดู QR ขนาดเต็ม --}}
              <button type="button" class="aov-qr-open" data-qr-open title="เปิดดู QR ขนาดเต็ม">
                <span class="aov-qr-thumb">
                  <img src="{{ $qr['imageUrl'] }}" alt="QR {{ $qr['label'] }}" loading="lazy">
                </span>
                <span class="aov-qr-text">
                  <span class="aov-qr-label">{{ $qr['label'] }}</span>
                  <span class="aov-qr-url">{{ preg_replace('#^https?://#', '', $qr['url']) }}</span>
                </span>
              </button>
              <span class="aov-qr-actions">
                <button type="button" class="aov-qr-btn" data-qr-copy title="คัดลอกลิงก์" aria-label="คัดลอกลิงก์ {{ $qr['label'] }}">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                </button>
                <a class="aov-qr-btn" href="{{ $qr['downloadUrl'] }}" title="ดาวน์โหลด QR" aria-label="ดาวน์โหลด QR {{ $qr['label'] }}">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 4v11M8 11.5l4 4 4-4M5 19.5h14"/></svg>
                </a>
              </span>
            </div>
          @endforeach
        </section>
      @endif
  </div>
@endsection

@section('modals')
{{-- QR ขนาดเต็ม — เปิดจากรายการ QR และลิงก์ --}}
<div class="modal-overlay" id="aov-qr-modal">
  <div class="modal modal-sm aov-qr-modal">
    <div class="modal-header">
      <h3 class="modal-title" id="aov-qr-modal-title">QR</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิด">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body aov-qr-modal-body">
      <img id="aov-qr-modal-image" src="" alt="QR ขนาดเต็ม">
      <a id="aov-qr-modal-url" class="aov-qr-modal-url" href="#" target="_blank" rel="noopener"></a>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" id="aov-qr-modal-copy">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
        คัดลอกลิงก์
      </button>
      <a class="btn btn-outline" id="aov-qr-modal-download" href="#">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 4v11M8 11.5l4 4 4-4M5 19.5h14"/></svg>
        ดาวน์โหลด
      </a>
    </div>
  </div>
</div>
@endsection

@push('page-script')
<script type="module">
(function () {
  var modalUrl = '';

  /* คัดลอกลิงก์ — เครื่องหน้างานบางเครื่องเปิดผ่าน http ธรรมดาซึ่งไม่มี clipboard API
     และ API นี้ยังปฏิเสธได้แม้มีอยู่ (เช่นหน้าต่างไม่ได้โฟกัส) จึงถอยไป execCommand เสมอเมื่อพลาด */
  function copyFallback(text) {
    return new Promise(function (resolve, reject) {
      var input = document.createElement('textarea');
      input.value = text;
      input.style.position = 'fixed';
      input.style.opacity = '0';
      document.body.appendChild(input);
      input.select();
      try { document.execCommand('copy') ? resolve() : reject(new Error('execCommand')); }
      catch (e) { reject(e); }
      finally { input.remove(); }
    });
  }

  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text).catch(function () { return copyFallback(text); });
    }
    return copyFallback(text);
  }

  function copyWithToast(url) {
    copyText(url).then(function () {
      window.TFC.showToast('คัดลอกลิงก์แล้ว', 'success');
    }).catch(function () {
      window.TFC.showToast('คัดลอกไม่สำเร็จ กรุณาคัดลอกจากช่องลิงก์เอง', 'danger');
    });
  }

  document.querySelectorAll('.aov-qr').forEach(function (item) {
    var label = item.getAttribute('data-qr-label');
    var url = item.getAttribute('data-qr-url');

    item.querySelector('[data-qr-copy]').addEventListener('click', function () {
      copyWithToast(url);
    });

    item.querySelector('[data-qr-open]').addEventListener('click', function () {
      modalUrl = url;
      document.getElementById('aov-qr-modal-title').textContent = label;
      document.getElementById('aov-qr-modal-image').src = item.getAttribute('data-qr-image');
      var link = document.getElementById('aov-qr-modal-url');
      link.href = url;
      link.textContent = url.replace(/^https?:\/\//, '');
      document.getElementById('aov-qr-modal-download').href = item.getAttribute('data-qr-download');
      window.TFC.openModal('aov-qr-modal');
    });
  });

  document.getElementById('aov-qr-modal-copy').addEventListener('click', function () {
    copyWithToast(modalUrl);
  });
})();
</script>
@endpush
