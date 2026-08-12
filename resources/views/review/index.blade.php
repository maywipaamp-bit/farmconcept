{{-- หน้าส่งงานให้ลูกค้าตรวจ — เปิดได้โดยไม่ต้องเข้าสู่ระบบ
     ไม่ได้ extends layouts.admin เพราะหน้านี้ไม่มีเมนูด้านข้างและผู้ใช้ไม่ได้ล็อกอิน --}}
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>ส่งงานให้ตรวจ | The Farm Concept</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600&display=swap">
  @vite('resources/css/app.css')
</head>
<body class="review-page">

<div class="review-shell">
  @if (! $round)
    <div class="review-empty">ยังไม่มีรอบส่งงานที่เปิดให้ตรวจ</div>
  @else
    <header class="review-head">
      <div class="review-head-text">
        <h1 class="review-title">ส่งงานพัฒนาระบบ</h1>
        <p class="review-meta">
          The Farm Concept &amp; Am | โดย แอม
          @if ($sentLabel) {{ $sentLabel }} @endif
          @if ($dueLabel) · ตรวจได้ถึง {{ $dueLabel }} @endif
        </p>
      </div>
      <div class="review-stats" id="review-stats"></div>
    </header>

    {{-- ข้อมูลโครงการ — ผู้ตรวจที่เปิดลิงก์เข้ามาต้องรู้ว่ากำลังดูงานของโครงการไหน
         คำอธิบายวิธีใช้อยู่ในการ์ดเดียวกัน ไม่ต้องมีแถบเหลืองอีกกล่องแยกต่างหาก --}}
    @if ($round->project_name || $round->project_start)
      <section class="review-project">
        <div class="review-project-fields">
          @if ($round->project_name)
            <div class="review-project-field">
              <div class="review-project-label">ชื่อโครงการ</div>
              <div class="review-project-value">{{ $round->project_name }}</div>
            </div>
          @endif

          @if ($round->project_start && $round->project_end)
            <div class="review-project-field">
              <div class="review-project-label">ระยะเวลาดำเนินโครงการ</div>
              <div class="review-project-value">
                {{ $round->project_start->format('d/m/Y') }} – {{ $round->project_end->format('d/m/Y') }}
                <span class="text-muted">(จำนวน {{ $round->projectDays() }} วัน)</span>
                {{-- นับถอยหลังคำนวณฝั่งหน้าจอ จะได้ตรงกับวันจริงของผู้ตรวจแม้เปิดหน้าค้างไว้ข้ามวัน --}}
                <span class="review-countdown" id="review-countdown" data-end="{{ $round->project_end->toDateString() }}"></span>
              </div>
            </div>
          @endif

          {{-- คำชี้แจงกับลิงก์ระบบอยู่ในบล็อกเดียวกัน เป็นชุดข้อมูลที่ผู้ตรวจต้องอ่านก่อนเริ่ม
               ถ้าแยกเป็นสองบล็อก ระยะห่างของ flex จะดันให้ดูเหมือนคนละเรื่อง --}}
          <div class="review-project-guide">
            <p class="review-project-note">
              <strong class="review-project-note-label">คำชี้แจง :</strong>
              สถานะ <strong>ตรวจได้</strong> เปิดดูและคอมเมนต์ได้เลยค่ะ ·
              <strong>รอพัฒนา</strong> ยังไม่ต้องตรวจ · คอมเมนต์ได้หลายครั้งต่อหน้า ระบบเก็บประวัติไว้ทั้งหมด
            </p>

            @if ($round->system_url)
              <div class="review-system">
                <span class="review-project-label">ลิงก์ระบบ</span>
                <a class="review-system-link" href="{{ $round->system_url }}" target="_blank" rel="noopener noreferrer">{{ $round->system_url }}</a>
                @if ($round->login_hint)
                  <span class="review-system-login">บัญชีทดลอง {{ $round->login_hint }}</span>
                @endif
              </div>
            @endif
          </div>
        </div>

        @if ($round->action_plan_url)
          <a class="btn btn-outline review-plan-btn" href="{{ $round->action_plan_url }}" target="_blank" rel="noopener noreferrer">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11H5a2 2 0 00-2 2v6a2 2 0 002 2h6a2 2 0 002-2v-4"/><path d="M15 3h6v6M10 14L21 3"/></svg>
            Action Plan
          </a>
        @endif
      </section>
    @endif

    {{-- ตัวกรองเดียวทางซ้าย · ช่องค้นหาขวา
         ชิปหลายปุ่มกินพื้นที่เต็มแถวโดยที่ผู้ตรวจใช้จริงทีละอันอยู่แล้ว --}}
    <div class="list-filter-bar">
      <label class="review-filter">
        <span class="review-filter-label">แสดง</span>
        <select class="select" id="review-filter" data-plain-select aria-label="กรองตามสถานะ"></select>
      </label>
      <div class="list-filter-tools">
        <input type="search" class="input list-search-input" id="review-search"
               placeholder="ค้นหาเมนู หรือฟังก์ชัน" aria-label="ค้นหาเมนู">
      </div>
    </div>

    <div class="table-wrapper">
      <div class="table-scroll">
        <table class="data-table is-header-filled is-dense">
          <thead>
            <tr>
              <th class="col-no">#</th>
              <th>เมนู</th>
              <th>หน้าจอ</th>
              <th class="cell-center">สถานะ</th>
              <th class="cell-center">วันครบกำหนด</th>
              <th>คอมเมนต์ล่าสุด</th>
              <th class="col-actions">จัดการ</th>
            </tr>
          </thead>
          <tbody id="review-table-body"></tbody>
          <tfoot><tr id="review-table-foot"></tr></tfoot>
        </table>
      </div>
    </div>
  @endif
</div>

{{-- แผงคอมเมนต์ด้านข้าง — เปิดทับหน้าโดยไม่ต้องออกจากตาราง --}}
<div class="review-drawer" id="review-drawer" hidden>
  <div class="review-drawer-backdrop" data-close-drawer></div>
  <aside class="review-drawer-panel" role="dialog" aria-modal="true" aria-labelledby="review-drawer-screen">
    <div class="review-drawer-head">
      <div>
        <div class="review-drawer-menu" id="review-drawer-menu"></div>
        <h2 class="review-drawer-screen" id="review-drawer-screen"></h2>
        <div id="review-drawer-status"></div>
      </div>
      <button type="button" class="modal-close" data-close-drawer aria-label="ปิดแผงคอมเมนต์">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="review-drawer-body">
      <div class="review-thread-title" id="review-thread-title"></div>
      <div id="review-thread"></div>
    </div>

    <form class="review-drawer-foot" id="review-comment-form">
      @if (! $canManage)
        <input class="input mb-2" id="review-author" maxlength="120" placeholder="ชื่อของคุณ" required>
      @endif
      <textarea class="textarea" id="review-body" rows="3" maxlength="2000" required
                placeholder="เขียนคอมเมนต์เพิ่มเติม… ระบุจุดที่ต้องการให้แก้ให้ชัดเจน"></textarea>
      <div class="review-drawer-actions">
        <span class="caption text-muted">คอมเมนต์ได้หลายครั้ง · ทีมงานเห็นทันที</span>
        <button type="submit" class="btn btn-primary" id="review-send">ส่งคอมเมนต์</button>
      </div>
    </form>
  </aside>
</div>

{{-- ลำดับสำคัญ — แต่ละไฟล์เติมของลงใน window.TFC ที่ไฟล์ถัดไปใช้ต่อ
     mock-data (escapeHtml) → index-layout → master-list (ชิปนับจำนวน)
     → toast → activity-module (statusTextHTML) → smart-select → app (formatThaiDate) --}}
<script src="{{ asset('assets/js/mock-data.js') }}"></script>
<script src="{{ asset('assets/js/index-layout.js') }}"></script>
<script src="{{ asset('assets/js/master-list.js') }}"></script>
<script src="{{ asset('assets/js/toast.js') }}"></script>
<script src="{{ asset('assets/js/activity-module.js') }}"></script>
<script src="{{ asset('assets/js/smart-select.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>

<script>
(function () {
  var items = @json($items);
  var STATUSES = @json($statuses);
  var canManage = @json($canManage);
  var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  if (!document.getElementById('review-table-body')) return;

  /* ลำดับคือลำดับการทำงานจริง
     ยังไม่เริ่ม → ส่งให้ตรวจ → ผู้ตรวจดูแล้ว → ทีมกำลังแก้ตามที่คอมเมนต์ → จบ */
  var STATUS_OPTIONS = [
    { value: 'รอพัฒนา', badge: 'badge-neutral' },
    { value: 'ตรวจได้', badge: 'badge-success' },
    { value: 'ตรวจแล้ว', badge: 'badge-info' },
    { value: 'ระหว่างแก้งาน', badge: 'badge-warning' },
    { value: 'เสร็จสิ้น', badge: 'badge-primary' }
  ];

  /* คลาสสีของช่องสถานะในตาราง — ใช้ชื่อเดียวกับ badge แต่ตัดคำนำหน้าออก */
  var STATUS_TONE = {
    'รอพัฒนา': 'is-neutral',
    'ตรวจได้': 'is-success',
    'ตรวจแล้ว': 'is-info',
    'ระหว่างแก้งาน': 'is-warning',
    'เสร็จสิ้น': 'is-primary'
  };

  /* เปิดหน้ามาเจอ "ตรวจได้" ก่อน เพราะเป็นคิวที่ผู้ตรวจต้องดูจริง
     ที่เหลือเป็นของที่ยังทำไม่เสร็จหรือปิดไปแล้ว ไม่ใช่สิ่งที่ต้องเห็นก่อน */
  var state = { statusKey: 'ตรวจได้' };

  /* ---------- ตัวนับคอมเมนต์ที่ยังไม่ได้อ่าน ----------
     หน้านี้ไม่มีระบบล็อกอิน จึงจำ "อ่านถึงคอมเมนต์ไหนแล้ว" ไว้ในเครื่องของผู้ตรวจเอง
     เปิดแผงคอมเมนต์ของหน้าจอไหน = อ่านหน้านั้นแล้ว จุดแจ้งเตือนจึงหายไป */
  var SEEN_KEY = 'tfc-review-seen';

  function seenMap() {
    try { return JSON.parse(localStorage.getItem(SEEN_KEY) || '{}'); } catch (e) { return {}; }
  }

  function markSeen(item) {
    if (!item.lastCommentId) return;

    var map = seenMap();
    map[item.id] = item.lastCommentId;
    try { localStorage.setItem(SEEN_KEY, JSON.stringify(map)); } catch (e) { /* โหมดส่วนตัวเขียนไม่ได้ ไม่ใช่เรื่องคอขาดบาดตาย */ }
  }

  function unreadCount(item) {
    if (!item.commentCount) return 0;

    var seen = seenMap()[item.id] || 0;
    return item.lastCommentId > seen ? item.commentCount : 0;
  }

  /* ---------- ตาราง ---------- */
  /* match ใช้ทั้งนับจำนวนในชิปและกรองแถว — ป้อนเฉพาะแถวที่เป็นหน้าจอจริงเข้าไปนับ
     หัวข้อหมวดไม่ใช่หน้าจอ จึงไม่ถูกนับ แต่ยังโผล่เป็นหัวข้อคั่นเสมอ (จัดการตอนกรองแถว) */
  var BUCKETS = [{ key: '', label: 'ทั้งหมด' }].concat(STATUSES.map(function (s) {
    return { key: s, label: s, match: function (r) { return r.status === s; } };
  })).concat([
    { key: '__unread', label: 'คอมเมนต์ใหม่', match: function (r) { return unreadCount(r) > 0; } }
  ]);

  function thaiDate(iso) {
    return iso ? window.TFC.formatThaiDate(iso).replace(/\d{2}(\d{2})$/, '$1') : '-';
  }

  /* การ์ดกดได้ = ตัวกรองอีกทางหนึ่ง กดซ้ำเพื่อยกเลิก
     ตัวเลขที่เห็นกับสิ่งที่ได้ตอนกดจึงเป็นชุดเดียวกัน ไม่ต้องเดา */
  function statCard(key, value, label) {
    return '<button type="button" class="review-stat' + (state.statusKey === key ? ' is-active' : '') + '"' +
      ' data-stat-filter="' + key + '" aria-pressed="' + (state.statusKey === key) + '">' +
      '<span class="review-stat-value">' + value + '</span>' +
      '<span class="review-stat-label">' + label + '</span></button>';
  }

  function render() {
    var keyword = (document.getElementById('review-search').value || '').trim().toLowerCase();
    var bucket = BUCKETS.filter(function (b) { return b.key === state.statusKey; })[0];

    var rows = items.filter(function (r) {
      var hit = !keyword || (r.menuLabel + ' ' + r.screen).toLowerCase().indexOf(keyword) !== -1;
      if (!hit) return false;

      /* หัวข้อหมวดผ่านตัวกรองสถานะเสมอ เพราะไม่มีสถานะของตัวเอง */
      return r.isGroup || !bucket || !bucket.match || bucket.match(r);
    });

    /* หัวข้อหมวดที่ไม่เหลือเมนูย่อยอยู่ใต้มันแล้ว ไม่ต้องแสดง — ไม่งั้นจะเห็นหัวข้อลอย ๆ ไม่มีเนื้อหา */
    rows = rows.filter(function (r, idx) {
      if (!r.isGroup) return true;
      var next = rows[idx + 1];
      return next && !next.isGroup;
    });

    /* "ตรวจได้" นับเฉพาะที่สถานะ "แจ้งทดสอบ" — คือของที่รอผู้ตรวจอยู่จริง
       ระหว่างแก้งานกับเสร็จสิ้นเปิดดูได้เหมือนกัน แต่ไม่ใช่คิวที่ต้องตรวจแล้ว

       "คอมเมนต์" นับเฉพาะที่ยังไม่ได้เปิดอ่าน จึงเป็นตัวเลขที่บอกว่ายังเหลืองานต้องดูเท่าไร
       ถ้านับทั้งหมด ตัวเลขจะโตขึ้นเรื่อย ๆ แล้วไม่มีความหมายภายในไม่กี่วัน */
    var screens = items.filter(function (r) { return !r.isGroup; });

    document.getElementById('review-stats').innerHTML =
      statCard('ตรวจได้', screens.filter(function (r) { return r.status === 'ตรวจได้'; }).length, 'ตรวจได้') +
      statCard('รอพัฒนา', screens.filter(function (r) { return r.status === 'รอพัฒนา'; }).length, 'รอพัฒนา') +
      statCard('__unread', screens.reduce(function (n, r) { return n + unreadCount(r); }, 0), 'คอมเมนต์ใหม่');

    /* ตัวกรองเดียว — ตัวเลขในวงเล็บบอกจำนวนของแต่ละสถานะโดยไม่ต้องเลือกดูก่อน */
    document.getElementById('review-filter').innerHTML = BUCKETS.map(function (b) {
      var n = b.key === '' ? screens.length : screens.filter(b.match).length;

      return '<option value="' + window.TFC.escapeHtml(b.key) + '"' + (b.key === state.statusKey ? ' selected' : '') + '>' +
        window.TFC.escapeHtml(b.label) + ' (' + n + ')</option>';
    }).join('');

    var seq = 0;

    document.getElementById('review-table-body').innerHTML = rows.map(function (r) {
      var unread = unreadCount(r);
      var last = r.lastComment;

      /* หมวดที่มีเมนูย่อยเป็นหัวข้อคั่น ไม่ใช่หน้าจอ จึงไม่นับลำดับและไม่มีช่องให้แก้ */
      if (r.isGroup) {
        return '<tr class="review-group" data-item="' + r.id + '">' +
          '<td class="col-no"></td>' +
          '<td class="review-menu"><span class="review-menu-group">' + window.TFC.escapeHtml(r.menuLabel) + '</span></td>' +
          '<td class="text-secondary">' + window.TFC.escapeHtml(r.screen) + '</td>' +
          '<td colspan="4"></td></tr>';
      }

      return '<tr data-item="' + r.id + '">' +
        '<td class="col-no nowrap">' + (++seq) + '</td>' +

        /* ชื่อเมนูกดแล้วไปหน้านั้นเลย — ผู้ตรวจเปิดดูของจริงได้จากคอลัมน์แรกโดยไม่ต้องไล่หาปุ่ม
           เมนูย่อยมีเส้นนำและย่อหน้า เห็นได้ทันทีว่าอยู่ใต้หมวดไหน */
        '<td class="review-menu' + (r.level > 1 ? ' is-child' : ' is-main') + '">' +
        (r.level > 1 ? '<span class="review-menu-branch" aria-hidden="true">└</span>' : '') +
        (r.url
          ? '<a class="review-menu-link" href="' + window.TFC.escapeHtml(r.url) + '" target="_blank" rel="noopener">' +
            window.TFC.escapeHtml(r.menuLabel) + '</a>'
          : '<span class="review-menu-leaf">' + window.TFC.escapeHtml(r.menuLabel) + '</span>') +
        '</td>' +

        '<td class="review-screen">' + window.TFC.escapeHtml(r.screen) + '</td>' +

        '<td class="cell-center nowrap">' + statusCell(r) + '</td>' +
        '<td class="cell-center nowrap">' + dueCell(r) + '</td>' +

        '<td class="review-last">' + (last
          ? '<div class="review-last-body">' + window.TFC.escapeHtml(last.body) + '</div>' +
            '<div class="caption text-secondary">' + window.TFC.escapeHtml(last.author) + ' · ' +
            thaiDate(last.date) + ' · ' + window.TFC.escapeHtml(last.time) + '</div>'
          : '<span class="text-muted">-</span>') + '</td>' +

        '<td class="review-actions">' +
        (r.open && r.url
          ? '<a class="btn btn-outline btn-sm" href="' + window.TFC.escapeHtml(r.url) + '" target="_blank" rel="noopener">เปิดดู</a>'
          : '<span class="btn btn-outline btn-sm is-disabled" aria-disabled="true">ยังไม่พร้อม</span>') +
        '<button type="button" class="btn btn-outline btn-sm review-comment-btn" data-open-comments="' + r.id + '"' +
        ' aria-label="คอมเมนต์ของ ' + window.TFC.escapeHtml(r.menuLabel) + '">' +
        '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.4 8.4 0 01-9 8.4 8.4 8.4 0 01-3.8-.9L3 21l1.9-5.2A8.4 8.4 0 0121 11.5z"/></svg>' +
        (unread ? '<span class="review-unread">' + unread + '</span>' : '') +
        '</button>' +
        '</td></tr>';
    }).join('') ||
      '<tr class="table-empty-row"><td colspan="7"><div class="table-empty">ไม่พบเมนูตามเงื่อนไขที่เลือก</div></td></tr>';

    /* นับเฉพาะหน้าจอจริง ไม่นับหัวข้อหมวด */
    var shown = rows.filter(function (r) { return !r.isGroup; }).length;

    document.getElementById('review-table-foot').innerHTML =
      '<td colspan="7"><div class="pagination-info"><span class="pagination-summary">' +
      'แสดง ' + shown + ' จาก ' + screens.length + ' เมนู</span></div></td>';
  }

  /* สถานะกับวันครบกำหนดแก้ได้จากหน้านี้เลย ทั้งทีมงานและผู้ตรวจ
     เปลี่ยนแล้วบันทึกทันที ไม่มีปุ่มยืนยัน — ตารางนี้ใช้ร่วมกันระหว่างรอบส่งงาน */
  function statusCell(r) {
    /* data-plain-select = ใช้ dropdown ของเบราว์เซอร์ ไม่แปลงเป็น combobox ของระบบ
       ตารางนี้มี 17 แถว ถ้าทุกแถวมีแผงค้นหาในตัวเองจะหนักและกินที่เกินความจำเป็น
       ตัวเลือกมีแค่ 4 ค่า ไม่มีอะไรต้องค้นหา */
    return '<select class="select review-status ' + (STATUS_TONE[r.status] || 'is-neutral') + '"' +
      ' data-plain-select data-status-for="' + r.id + '"' +
      ' aria-label="สถานะของ ' + window.TFC.escapeHtml(r.menuLabel) + '">' +
      STATUSES.map(function (s) {
        return '<option value="' + window.TFC.escapeHtml(s) + '"' + (s === r.status ? ' selected' : '') + '>' +
          window.TFC.escapeHtml(s) + '</option>';
      }).join('') + '</select>';
  }

  /* จำนวนวันจนถึงวันครบกำหนด — บวกคือยังเหลือ ลบคือเลยมาแล้ว
     เทียบเฉพาะวัน ตัดเวลาออก ไม่งั้นบ่ายวันครบกำหนดจะกลายเป็น "เลยกำหนด" */
  function daysUntil(iso) {
    if (!iso) return null;

    var p = iso.split('-');
    var target = new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]));
    var now = new Date();

    return Math.round((target - new Date(now.getFullYear(), now.getMonth(), now.getDate())) / 86400000);
  }

  /* บรรทัดใต้ช่องวันที่ — ปกติเป็นสีเทาบอกว่าเหลืออีกกี่วัน
     เปลี่ยนเป็นสีแดงเมื่อเหลือไม่เกิน 2 วัน และหายไปเมื่อเลยกำหนดมาเกิน 5 วัน
     ของที่เลยกำหนดมานานแล้วเตือนไปก็ไม่ช่วยอะไร มีแต่ทำให้ทั้งตารางเป็นสีแดง */
  function dueWarning(r) {
    var days = daysUntil(r.dueDate);
    if (days === null || days < -5) return '';

    var text = days > 0 ? 'อีก ' + days + ' วัน'
      : days === 0 ? 'ครบกำหนดวันนี้'
      : 'เลยกำหนด ' + Math.abs(days) + ' วัน';

    return '<span class="review-due-note' + (days <= 2 ? ' is-urgent' : '') + '">' + text + '</span>';
  }

  /* วันที่กับจำนวนวันที่เหลืออยู่บรรทัดเดียวกัน แถวจึงเตี้ยลงและตารางกวาดตาได้เร็วขึ้น */
  function dueCell(r) {
    return '<span class="review-due-cell">' +
      '<input type="date" class="input review-due" data-due-for="' + r.id + '" value="' + (r.dueDate || '') + '"' +
      ' aria-label="วันครบกำหนดของ ' + window.TFC.escapeHtml(r.menuLabel) + '">' + dueWarning(r) + '</span>';
  }

  function itemOf(id) {
    return items.filter(function (r) { return String(r.id) === String(id); })[0];
  }

  function replaceItem(row) {
    var idx = items.findIndex(function (r) { return r.id === row.id; });
    if (idx !== -1) items[idx] = row;
  }

  /* การ์ดด้านบนกดแล้วกรองเหมือนกัน — กดซ้ำที่การ์ดเดิมเพื่อกลับไปแสดงทั้งหมด */
  document.getElementById('review-stats').addEventListener('click', function (e) {
    var card = e.target.closest('[data-stat-filter]');
    if (!card) return;

    var key = card.getAttribute('data-stat-filter');
    state.statusKey = key === state.statusKey ? '' : key;
    render();
  });

  document.getElementById('review-filter').addEventListener('change', function () {
    state.statusKey = this.value;
    render();
  });

  /* ค้นหาแบบพิมพ์แล้วกรองเลย — หน่วง 200ms กันวาดตารางใหม่ทุกตัวอักษร */
  var searchTimer = null;
  ['input', 'search'].forEach(function (evt) {
    document.getElementById('review-search').addEventListener(evt, function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(render, 200);
    });
  });

  /* ---------- บันทึกสถานะ / วันครบกำหนด ---------- */
  document.getElementById('review-table-body').addEventListener('change', function (e) {
    var select = e.target.closest('[data-status-for]');
    var due = e.target.closest('[data-due-for]');
    if (!select && !due) return;

    var control = select || due;
    var id = control.getAttribute(select ? 'data-status-for' : 'data-due-for');
    var tr = control.closest('tr');
    if (!tr || tr.getAttribute('data-saving') === 'true') return;

    var savedItem = itemOf(id);
    var previousStatus = savedItem ? savedItem.status : null;
    tr.setAttribute('data-saving', 'true');
    Array.prototype.forEach.call(tr.querySelectorAll('.review-status, .review-due'), function (field) {
      field.disabled = true;
    });

    /* อ่านค่าจากช่องจริงในแถวทั้งสองช่อง ไม่ใช่จากข้อมูลที่จำไว้
       ถ้าอ่านจากข้อมูลที่จำไว้ การแก้สองช่องติดกันเร็ว ๆ จะส่งค่าเก่าของอีกช่องตามไปด้วย
       แล้วคำขอที่มาทีหลังจะเขียนทับสิ่งที่คำขอแรกเพิ่งบันทึกไป */
    var body = {
      status: tr.querySelector('.review-status').value,
      dueDate: tr.querySelector('.review-due').value
    };

    fetch('{{ url('/review/items') }}/' + id, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
      credentials: 'same-origin',
      body: JSON.stringify(body)
    })
      .then(function (res) {
        return res.json().catch(function () { return {}; }).then(function (payload) {
          if (!res.ok || !payload.success) {
            var detail = payload.message || (payload.errors ? Object.values(payload.errors).flat().join(' ') : 'บันทึกไม่สำเร็จ');
            throw new Error(detail);
          }

          return payload;
        });
      })
      .then(function (res) {
        replaceItem(res.data);

        /* ถ้าเปลี่ยนสถานะจากตัวกรองสถานะเดิม ให้ตามไปยังสถานะใหม่
           รายการจึงไม่หายจากจอทันทีจนดูเหมือนเลือกแล้วไม่บันทึก */
        if (select && state.statusKey === previousStatus && res.data.status !== previousStatus) {
          state.statusKey = res.data.status;
        }

        render();
        window.TFC.showToast(res.message, 'success');
      })
      .catch(function (error) {
        render();
        window.TFC.showToast(error.message || 'เชื่อมต่อเซิร์ฟเวอร์ไม่สำเร็จ', 'danger');
      });
  });

  /* ---------- แผงคอมเมนต์ ---------- */
  var drawer = document.getElementById('review-drawer');
  var openId = null;

  function closeDrawer() {
    drawer.hidden = true;
    openId = null;
    document.body.classList.remove('is-drawer-open');
  }

  drawer.addEventListener('click', function (e) {
    if (e.target.closest('[data-close-drawer]')) closeDrawer();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !drawer.hidden) closeDrawer();
  });

  document.getElementById('review-table-body').addEventListener('click', function (e) {
    var btn = e.target.closest('[data-open-comments]');
    if (!btn) return;

    openComments(btn.getAttribute('data-open-comments'));
  });

  function openComments(id) {
    var row = itemOf(id);
    if (!row) return;

    openId = id;
    document.getElementById('review-drawer-menu').textContent = row.menuLabel;
    document.getElementById('review-drawer-screen').textContent = row.screen;
    document.getElementById('review-drawer-status').innerHTML =
      window.TFC.statusTextHTML({ options: STATUS_OPTIONS, value: row.status });

    document.getElementById('review-thread').innerHTML = '<div class="review-thread-loading">กำลังโหลดคอมเมนต์…</div>';
    document.getElementById('review-thread-title').textContent = 'ประวัติคอมเมนต์';

    drawer.hidden = false;
    document.body.classList.add('is-drawer-open');

    /* เปิดอ่านแล้ว = เคลียร์จุดแจ้งเตือนของหน้าจอนั้น */
    markSeen(row);
    render();

    fetch('{{ url('/review/items') }}/' + id + '/comments', { headers: { 'Accept': 'application/json' } })
      .then(function (res) { return res.json(); })
      .then(function (res) { renderThread(res.data || []); })
      .catch(function () {
        document.getElementById('review-thread').innerHTML =
          '<div class="review-thread-loading">โหลดคอมเมนต์ไม่สำเร็จ</div>';
      });
  }

  function renderThread(list) {
    document.getElementById('review-thread-title').textContent = 'ประวัติคอมเมนต์ (' + list.length + ')';

    document.getElementById('review-thread').innerHTML = list.length
      ? list.map(function (c) {
          return '<div class="review-msg is-' + c.side + '">' +
            '<div class="review-msg-head">' +
            '<span class="review-msg-author">' + window.TFC.escapeHtml(c.author) + '</span>' +
            '<span class="review-msg-side">' + (c.side === 'team' ? 'ทีมงาน' : 'ลูกค้า') + '</span>' +
            '<span class="review-msg-time">' + thaiDate(c.date) + ' · ' + window.TFC.escapeHtml(c.time) + '</span>' +
            '</div>' +
            '<div class="review-msg-body">' + window.TFC.escapeHtml(c.body) + '</div>' +
            (c.resolved ? '<div class="review-msg-resolved">แก้ไขแล้ว</div>' : '') +
            '</div>';
        }).join('')
      : '<div class="review-thread-loading">ยังไม่มีคอมเมนต์ — เขียนความเห็นแรกได้เลย</div>';
  }

  document.getElementById('review-comment-form').addEventListener('submit', function (e) {
    e.preventDefault();
    if (!openId) return;

    var button = document.getElementById('review-send');
    var author = document.getElementById('review-author');
    var body = document.getElementById('review-body');

    button.disabled = true;
    button.textContent = 'กำลังส่ง…';

    fetch('{{ url('/review/items') }}/' + openId + '/comments', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify({ name: author ? author.value : null, body: body.value })
    })
      .then(function (res) { return res.json(); })
      .then(function (res) {
        if (!res.success) {
          var msg = res.message || (res.errors ? Object.values(res.errors).flat().join(' ') : 'ส่งไม่สำเร็จ');
          return window.TFC.showToast(msg, 'danger');
        }

        body.value = '';
        replaceItem(res.item);

        /* ไม่ทำเครื่องหมายว่าอ่านแล้วตรงนี้ตั้งใจ — คอมเมนต์ที่เพิ่งส่งต้องขึ้นจุดแจ้งเตือนด้วย
           แม้จะเป็นของตัวเอง จุดจะหายก็ต่อเมื่อกดปุ่มคอมเมนต์เข้าไปดูอีกครั้ง */
        render();

        var id = openId;
        return fetch('{{ url('/review/items') }}/' + id + '/comments', { headers: { 'Accept': 'application/json' } })
          .then(function (r) { return r.json(); })
          .then(function (r) {
            renderThread(r.data || []);
            window.TFC.showToast(res.message, 'success');
          });
      })
      .catch(function () { window.TFC.showToast('เชื่อมต่อเซิร์ฟเวอร์ไม่สำเร็จ', 'danger'); })
      .finally(function () {
        button.disabled = false;
        button.textContent = 'ส่งคอมเมนต์';
      });
  });

  /* ---------- นับถอยหลังวันสิ้นสุดโครงการ ----------
     คิดจากนาฬิกาของผู้ตรวจ ไม่ใช่ของเซิร์ฟเวอร์ เพราะคนละโซนเวลาแล้วตัวเลขจะคลาดกันหนึ่งวัน
     ตรวจซ้ำทุกนาที เผื่อเปิดหน้าค้างไว้ข้ามเที่ยงคืน */
  var countdownEl = document.getElementById('review-countdown');

  function renderCountdown() {
    if (!countdownEl) return;

    var parts = countdownEl.getAttribute('data-end').split('-');
    var end = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    var now = new Date();

    /* เทียบเฉพาะวัน ตัดเวลาออกทั้งสองฝั่ง ไม่งั้นบ่ายวันสุดท้ายจะกลายเป็น "เลยกำหนด" */
    var days = Math.round((end - new Date(now.getFullYear(), now.getMonth(), now.getDate())) / 86400000);

    if (days > 0) {
      countdownEl.className = 'review-countdown';
      countdownEl.textContent = 'เหลืออีก ' + days + ' วัน';
    } else if (days === 0) {
      countdownEl.className = 'review-countdown is-urgent';
      countdownEl.textContent = 'วันสุดท้ายวันนี้';
    } else {
      countdownEl.className = 'review-countdown is-over';
      countdownEl.textContent = 'เลยกำหนดมาแล้ว ' + Math.abs(days) + ' วัน';
    }
  }

  renderCountdown();
  setInterval(renderCountdown, 60000);

  render();
})();
</script>
</body>
</html>
