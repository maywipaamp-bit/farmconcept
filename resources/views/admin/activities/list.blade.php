@extends('layouts.admin')

@section('title', 'รายการกิจกรรม')

{{-- ตารางยืดเต็มจอ แถบแบ่งหน้าจึงติดขอบล่างเสมอ ข้อมูลล้นก็เลื่อนเฉพาะส่วนแถว --}}
@section('main-class', 'is-fill')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard.html">แดชบอร์ด</a> <span>/</span> <span class="is-current">จัดการกิจกรรม</span>
  </nav>
  <div class="page-header" id="activity-page-header"></div>

  {{-- โครงมาตรฐานของหน้ารายการ: pill สถานะซ้าย · ช่องค้นหา + ปุ่มตัวกรองขวา --}}
  <div class="list-filter-bar">
    <div class="status-pills" id="activity-status-pills"></div>
    <div class="list-filter-tools">
      {{-- ค้นหาพิมพ์แล้วกรองเลย ไม่ต้องกดปุ่ม จึงไม่มีปุ่มค้นหาข้างช่อง --}}
      <input type="search" class="input list-search-input" id="activity-search"
             placeholder="ค้นหากิจกรรม ผู้รับผิดชอบ สถานที่" aria-label="ค้นหากิจกรรม">
      <div id="activity-search-popover"></div>
    </div>
  </div>

  {{-- แถบแบ่งหน้าอยู่ในกรอบเดียวกับรายการ ให้อ่านเป็นบรรทัดท้ายของตาราง --}}
  <div class="table-wrapper mb-4">
    <div class="table-scroll">
      <div class="activity-list" id="activity-list"></div>
    </div>
    <div id="activity-pagination"></div>
  </div>
@endsection

@section('modals')
<div class="modal-overlay" id="activity-delete-modal">
  <div class="modal modal-sm">
    <div class="modal-body text-center">
      <span class="modal-confirm-icon is-danger mx-auto">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
      </span>
      <h3 class="modal-title mb-3">ยืนยันการลบกิจกรรม</h3>
      <p class="text-secondary" id="activity-delete-message"></p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" data-close-modal>ยกเลิก</button>
      {{-- ไม่มี data-close-modal เพราะต้องรอผลจากเซิร์ฟเวอร์ก่อนจึงปิด
           ถ้าปิดทันทีที่กด ผู้ใช้จะไม่เห็นว่าลบไม่สำเร็จ --}}
      <button class="btn btn-danger" id="activity-delete-confirm">ลบกิจกรรม</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="@assetv('assets/js/activity-module.js')"></script>
@endpush

@push('page-script')
{{-- สคริปต์ธรรมดา ไม่ใช่ module — ต้องทำงานตอน parse ต่อจากสคริปต์กลางที่โหลดไว้ก่อนหน้า --}}
<script>
(function () {
  var mock = window.TFC_MOCK;

  try {
    var savedMessage = sessionStorage.getItem('tfc-activity-success');
    if (savedMessage) {
      sessionStorage.removeItem('tfc-activity-success');
      window.TFC.showToast(savedMessage, 'success');
    }
  } catch (e) { /* หน้า Index ยังทำงานได้แม้เบราว์เซอร์ปิด sessionStorage */ }

  /* ---------------------------------------------------------------------
     สะพานชั่วคราวระหว่างย้ายหน้าจอ
     ตรรกะการกรอง/เรียง/แบ่งหน้าด้านล่างคงของเดิมทุกบรรทัด เปลี่ยนเฉพาะ "แหล่งข้อมูล"
     จาก mock-data.js เป็นข้อมูลจริงจากฐานข้อมูล เพื่อให้เห็นว่า UI ไม่เปลี่ยนพฤติกรรม
     เมื่อเขียนหน้านี้เป็น Blade เต็มตัวแล้ว บล็อกนี้จะถูกถอดออกทั้งก้อน
     --------------------------------------------------------------------- */
  mock.activities = @json($activities);
  mock.activitySessions = @json($sessions);

  var activities = mock.activities;
  var PAGE_SIZES = [10, 20, 50];
  /* ค่าเริ่มต้น: กรองเฉพาะกิจกรรมที่ "เปิดรับสมัคร" และเรียงตามลำดับที่แสดงบนหน้าเว็บ (น้อยไปมาก)
     เพราะนี่คือมุมมองที่แอดมินต้องใช้บ่อยที่สุด — ดูว่ากิจกรรมที่กำลังเปิดรับอยู่เรียงลำดับถูกไหม */
  var state = { status: 'เปิดรับสมัคร', search: '', type: '', instructor: '', area: '', sort: 'publicRank', page: 1, pageSize: PAGE_SIZES[0] };
  var deleteTargetId = null;

  window.TFC.renderPageHeader('activity-page-header', {
    title: 'จัดการกิจกรรม',
    actions: [
      {
        label: 'เพิ่มกิจกรรม',
        href: '{{ route('admin.activities.create') }}',
        icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>'
      }
    ]
  });

  /* ---------- ชิปกรองตามสถานะ พร้อมจำนวน ---------- */
  function statusesInUse() {
    return (mock.activityStatuses || []).filter(function (item) {
      return activities.some(function (a) { return a.status === item.value; });
    });
  }

  function renderPills() {
    var pills = [{ value: '', label: 'ทั้งหมด', count: activities.length }].concat(
      statusesInUse().map(function (item) {
        return {
          value: item.value,
          label: item.value,
          count: activities.filter(function (a) { return a.status === item.value; }).length
        };
      }));

    document.getElementById('activity-status-pills').innerHTML = pills.map(function (pill) {
      return '<button type="button" class="status-pill' + (state.status === pill.value ? ' is-active' : '') +
        '" data-status="' + window.TFC.escapeHtml(pill.value) + '">' +
        window.TFC.escapeHtml(pill.label) +
        '<span class="status-pill-count">' + pill.count + '</span></button>';
    }).join('');
  }

  document.getElementById('activity-status-pills').addEventListener('click', function (e) {
    var pill = e.target.closest('[data-status]');
    if (!pill) return;
    state.status = pill.getAttribute('data-status');
    state.page = 1;
    renderPills();
    render();
  });

  /* ---------- ค้นหา / เรียงลำดับ ---------- */
  function responsible(activity) {
    return (activity.instructorList && activity.instructorList.length ? activity.instructorList : [activity.instructor]).filter(Boolean);
  }

  function areasOf(activity) {
    return (activity.areaList && activity.areaList.length ? activity.areaList : [activity.area]).filter(Boolean);
  }

  /* ตัวเลือกของแต่ละตัวกรองมาจากข้อมูลจริงที่มีอยู่ ไม่ hardcode รายชื่อไว้ */
  function optionsFrom(pick) {
    var seen = {};
    activities.forEach(function (a) {
      [].concat(pick(a)).filter(Boolean).forEach(function (v) { seen[v] = true; });
    });
    return Object.keys(seen).sort(function (a, b) { return a.localeCompare(b, 'th'); })
      .map(function (v) { return { value: v, label: v }; });
  }

  function filteredRows() {
    var term = state.search.trim().toLowerCase();
    var rows = activities.filter(function (a) {
      if (state.status && a.status !== state.status) return false;
      if (state.type && a.type !== state.type) return false;
      if (state.instructor && responsible(a).indexOf(state.instructor) === -1) return false;
      if (state.area && areasOf(a).indexOf(state.area) === -1) return false;
      if (!term) return true;
      return [a.name, a.id].concat(responsible(a), areasOf(a)).join(' ').toLowerCase().indexOf(term) !== -1;
    });

    /* ไม่เลือกการเรียง = ใช้ลำดับที่เซิร์ฟเวอร์ส่งมา (ใหม่สุดอยู่บน แก้แล้วไม่ขยับ)
       เดิมค่าเริ่มต้นเรียงตาม updatedAt ซึ่งดีดแถวที่เพิ่งบันทึกขึ้นบนสุดทุกครั้ง */
    if (!state.sort) return rows;

    return rows.sort(function (a, b) {
      if (state.sort === 'registered') return b.registered - a.registered;
      if (state.sort === 'startDate') return String(a.startDate).localeCompare(String(b.startDate));
      /* ลำดับที่แสดงบนหน้าเว็บ น้อยไปมาก — กิจกรรมที่ไม่ได้เผยแพร่ (ไม่มีลำดับ) ให้ไปอยู่ท้ายสุด */
      if (state.sort === 'publicRank') return (a.publicRank || Infinity) - (b.publicRank || Infinity);
      return String(a.name).localeCompare(String(b.name), 'th');
    });
  }

  /* ช่องค้นหาย้ายออกมาอยู่นอกแผงแล้ว ปุ่มนี้จึงเหลือแค่ตัวกรอง และเปลี่ยนไอคอนเป็นกรวยให้ตรงกับหน้าที่ */
  window.TFC.searchPopover('activity-search-popover', {
    search: false,
    icon: 'filter',
    filters: [
      { id: 'type', label: 'ประเภท', placeholder: 'ทั้งหมด', options: optionsFrom(function (a) { return a.type; }) },
      { id: 'instructor', label: 'วิทยากร', placeholder: 'ทั้งหมด', options: optionsFrom(responsible) },
      { id: 'area', label: 'สถานที่จัด', placeholder: 'ทั้งหมด', options: optionsFrom(areasOf) },
      {
        id: 'sort',
        label: 'เรียงลำดับ',
        placeholder: 'เพิ่มล่าสุด',
        options: [
          { value: 'publicRank', label: 'ลำดับที่แสดงบนเว็บ' },
          { value: 'startDate', label: 'วันที่จัดกิจกรรม' },
          { value: 'name', label: 'ชื่อกิจกรรม' },
          { value: 'registered', label: 'จำนวนผู้ลงทะเบียน' }
        ]
      }
    ],
    onSearch: function (values, done) {
      state.type = values.filters.type || '';
      state.instructor = values.filters.instructor || '';
      state.area = values.filters.area || '';
      /* ไม่เลือกอะไรในแผงตัวกรอง = กลับไปที่ค่าเริ่มต้นของหน้านี้ (เรียงตามลำดับที่แสดงบนเว็บ) ไม่ใช่เลิกเรียงไปเลย */
      state.sort = values.filters.sort || 'publicRank';
      state.page = 1;
      render();
      done();
    },
    onClear: function () {
      state.type = '';
      state.instructor = '';
      state.area = '';
      state.sort = 'publicRank';
      state.page = 1;
      render();
    }
  });

  /* ค้นหาแบบพิมพ์แล้วกรองเลย — หน่วง 200ms กันวาดรายการใหม่ทุกตัวอักษร
     ปุ่มกากบาทของ input[type=search] ยิง 'search' ไม่ใช่ 'input' จึงต้องดักทั้งสองอีเวนต์ */
  var searchTimer = null;
  ['input', 'search'].forEach(function (evt) {
    document.getElementById('activity-search').addEventListener(evt, function () {
      var value = this.value;
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        state.search = value;
        state.page = 1;
        render();
      }, 200);
    });
  });

  /* ---------- มุมมองตาราง ---------- */
  /* หกคอลัมน์ อ่านซ้ายไปขวาเป็นลำดับคำถาม: กิจกรรมอะไร · เมื่อไหร่ · คนมาแค่ไหน · สถานะ · ขึ้นเว็บหรือยัง
     สามตัวเลขของกิจกรรมเดียวกัน (ลงทะเบียน/เช็คอิน/ประเมิน) อยู่ช่องเดียว เพราะต้องอ่านเทียบกัน
     ไม่ใช่แยกช่องแล้วให้สายตาวิ่งข้ามคอลัมน์
     ตัดคอลัมน์ #, สถานที่ และปรับปรุงล่าสุดออก — ไม่ใช่สิ่งที่ต้องเห็นทุกแถว หาได้จากหน้ารายละเอียด
     จำนวนช่องต้องตรงกับ grid-template ใน components.css เสมอ */
  var TABLE_COLS = [
    { label: 'กิจกรรม' },
    { label: 'วันและเวลา' },
    { label: 'ลงทะเบียน · เช็คอิน · ประเมิน' },
    { label: 'สถานะ' },
    { label: 'หน้าเว็บ' },
    { label: '' }
  ];

  /* ป้ายประเภทหน้าชื่อ — รายการนี้ปนทั้งกิจกรรม อีเวนท์ และข่าวสาร
     ซึ่งมีคอลัมน์ที่ใช้ได้ไม่เท่ากัน (ข่าวสารไม่มีคนลงทะเบียน) ต้องแยกออกจากกันตั้งแต่แรกเห็น */
  var TYPE_TONE = { 'ข่าวสาร': 'is-news', 'อีเว้นท์': 'is-event' };

  function typeChip(activity) {
    if (!activity.type) return '';
    return '<span class="grid-type ' + (TYPE_TONE[activity.type] || 'is-activity') + '">' +
      window.TFC.escapeHtml(activity.type) + '</span>';
  }

  /* สามตัวเลขในช่องเดียว — ตัวแรกเน้น เพราะเป็นตัวที่บอกว่ากิจกรรมนี้ไปรอดหรือไม่ อีกสองตัวเป็นข้อมูลประกอบ
     กิจกรรมที่ไม่เปิดรับลงทะเบียน (ข่าวสาร/อีเวนท์บางรายการ) ไม่มีตัวหาร จึงขึ้น — แทน 0/0
     ตัดแถบกราฟความคืบหน้าออกแล้ว — ตัวเลขสามตัวพอสื่อความหมายอยู่แล้ว ไม่ต้องมีกราฟแนวนอนกินที่เพิ่ม */
  function metricsCell(activity) {
    var cap = activity.capacity || 0;
    var reg = activity.registered || 0;
    var regText = cap > 0 ? (reg + '/' + cap) : (reg ? String(reg) : '—');

    return '<div class="fb-progress-cell">' +
      '<span class="fb-progress-text">' +
        '<span class="grid-metric-main">' + window.TFC.escapeHtml(regText) + '</span>' +
        '<span class="grid-metric-sub"> · เช็คอิน ' + (activity.checkedIn || 0) +
        ' · ประเมิน ' + (activity.responses || 0) + '</span>' +
      '</span>' +
      '</div>';
  }

  /* คอลัมน์ "หน้าเว็บ" — ตอบสองคำถาม: กิจกรรมนี้โผล่บนหน้าเว็บสาธารณะไหม
     และถ้าโผล่ อยู่ลำดับที่เท่าไหร่ (เลขเดียวกับที่ผู้เข้าชมเห็นจริง ไม่ใช่ค่าดิบในฐาน)
     ไม่โผล่ก็บอกเหตุผลเลย แอดมินจะได้รู้ว่าต้องแก้อะไร ไม่ต้องไล่เดาเอง */
  function publicCell(activity) {
    if (activity.publicRank) {
      return '<div title="กำลังแสดงบนหน้าเว็บสาธารณะ เป็นลำดับที่ ' + activity.publicRank + '">' +
        '<div class="grid-web-on">เผยแพร่</div>' +
        '<div class="grid-sub">ลำดับที่ ' + activity.publicRank + '</div>' +
        '</div>';
    }
    var reason = activity.publicHiddenReason || 'ไม่แสดงบนเว็บ';
    return '<div class="grid-soft" title="' + window.TFC.escapeHtml(reason) + '">ไม่แสดงบนเว็บ</div>';
  }

  function truncate(str, maxLen) {
    maxLen = maxLen || 25;
    if (!str) return '';
    return str.length > maxLen ? str.slice(0, maxLen) + '...' : str;
  }

  function rowHtml(activity) {
    var schedules = window.TFC.activity.schedules(activity);
    var timeText = schedules.length && schedules[0].timeStart
      ? schedules[0].timeStart + '–' + schedules[0].timeEnd + ' น.'
      : '';
    var respText = responsible(activity).join(' · ');

    return '<div class="grid-row" data-id="' + activity.id + '">' +

      /* อีเวนท์กับกิจกรรมอยู่ปนกันในรายการเดียว จึงต้องบอกให้เห็นว่าแถวนี้อยู่ในอีเวนท์ไหน */
      '<div>' +
      (activity.parentEventName ? '<div class="grid-parent">อีเวนท์ · ' + window.TFC.escapeHtml(activity.parentEventName) + '</div>' : '') +
      '<div class="grid-name-line">' + typeChip(activity) +
        '<a class="grid-name" href="/admin/activities/' + activity.id + '/edit" title="' + window.TFC.escapeHtml(activity.name) + '">' + window.TFC.escapeHtml(truncate(activity.name, 30)) + '</a>' +
      '</div>' +
      '<div class="grid-sub" title="' + window.TFC.escapeHtml(respText) + '">' + (respText ? window.TFC.escapeHtml(truncate(respText, 30)) : '—') + '</div></div>' +

      '<div><div class="grid-strong">' + window.TFC.escapeHtml(window.TFC.activity.dateLabel(activity)) + '</div>' +
      (timeText ? '<div class="grid-sub">' + window.TFC.escapeHtml(timeText) + '</div>' : '') + '</div>' +

      '<div>' + metricsCell(activity) + '</div>' +

      '<div>' + window.TFC.statusTextHTML({ options: mock.activityStatuses, value: activity.status }) + '</div>' +

      '<div>' + publicCell(activity) + '</div>' +

      '<div class="grid-actions">' + window.TFC.actionMenuTrigger(menuItems(activity)) + '</div>' +
      '</div>';
  }

  /* ลบได้เฉพาะกิจกรรมที่ยังเป็นฉบับร่าง — เผยแพร่แล้วมีคนลงทะเบียน การลบทิ้งจะทำให้ข้อมูลผู้เข้าร่วมหายด้วย */
  function canDelete(activity) {
    return activity.status === 'ฉบับร่าง';
  }

  function menuItems(activity) {
    var items = [
      { key: 'act-view-' + activity.id, label: 'ดูรายละเอียด', icon: 'view', href: '/admin/activities/' + activity.id + '/edit' },
      { key: 'act-edit-' + activity.id, label: 'แก้ไข', icon: 'edit', href: '/admin/activities/' + activity.id + '/edit', perm: 'activities' },
      { key: 'act-registrants-' + activity.id, label: 'ผู้ลงทะเบียน', icon: 'users', href: '/admin/activities/registrants?id=' + activity.id },
      { key: 'act-checkin-' + activity.id, label: 'Check-in', icon: 'checkin', href: '/admin/activities/checkin?id=' + activity.id },
      { key: 'act-eval-' + activity.id, label: 'ประเมินกิจกรรม', icon: 'star', href: '/admin/activities/responses?id=' + activity.id }
    ];
    if (canDelete(activity)) {
      items.push({ key: 'act-delete-' + activity.id, label: 'ลบกิจกรรม', icon: 'delete',
                   modal: 'activity-delete-modal', perm: 'activities', danger: true });
    }
    return items;
  }

  function tableHtml(rows) {
    return '<div class="grid-table">' +
      '<div class="grid-head">' + TABLE_COLS.map(function (c) {
        return '<div' + (c.center ? ' class="grid-center"' : '') + '>' + window.TFC.escapeHtml(c.label) + '</div>';
      }).join('') + '</div>' +
      rows.map(rowHtml).join('') +
      '</div>';
  }

  var EMPTY_HTML = '<div class="state-placeholder">' +
    '<span class="state-placeholder-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg></span>' +
    '<div class="state-placeholder-title">ไม่พบกิจกรรมที่ตรงกับเงื่อนไข</div>' +
    '<div class="state-placeholder-desc">ลองเปลี่ยนคำค้นหา หรือล้างตัวกรองที่เลือกไว้</div></div>';

  var listEl = document.getElementById('activity-list');

  function render() {
    var rows = filteredRows();
    var pageCount = Math.max(1, Math.ceil(rows.length / state.pageSize));
    if (state.page > pageCount) state.page = pageCount;
    var start = (state.page - 1) * state.pageSize;

    var pageRows = rows.slice(start, start + state.pageSize);
    listEl.innerHTML = rows.length ? tableHtml(pageRows) : EMPTY_HTML;

    window.TFC.renderPagination('activity-pagination', {
      page: state.page,
      pageSize: state.pageSize,
      total: rows.length,
      pageSizeOptions: PAGE_SIZES,
      footer: true,
      onChange: function (page) { state.page = page; render(); },
      onPageSizeChange: function (size) { state.pageSize = size; state.page = 1; render(); }
    });
  }

  /* ---------- ลบกิจกรรม ---------- 
     เปิดกล่องยืนยันจากเมนู ⋮ แล้วเตรียมข้อความตามสถานะของแถวนั้น
     เกณฑ์ตรงกับ ActivityPolicy ฝั่งเซิร์ฟเวอร์ ไม่งั้นจะมีปุ่มให้กดแล้วค่อยบอกว่าลบไม่ได้ */
  listEl.addEventListener('click', function (e) {
    var trigger = e.target.closest('[data-action-menu]');
    if (!trigger) return;
    var row = trigger.closest('[data-id]');
    deleteTargetId = row ? row.getAttribute('data-id') : null;

    var activity = window.TFC.activity.get(deleteTargetId);
    var messageEl = document.getElementById('activity-delete-message');
    if (!activity) return;

    if (!canDelete(activity)) {
      messageEl.innerHTML = 'กิจกรรม "' + window.TFC.escapeHtml(activity.name) + '" เผยแพร่ไปแล้ว จึงลบไม่ได้' +
        '<br>หากต้องการยุติกิจกรรม ให้เปลี่ยนสถานะเป็น "ยกเลิก" แทน';
      confirmBtn.disabled = true;
    } else {
      messageEl.textContent = 'ต้องการลบกิจกรรม "' + activity.name + '" ใช่หรือไม่ การลบนี้ไม่สามารถย้อนกลับได้';
      confirmBtn.disabled = false;
    }
  });

  /* ลบจริงที่เซิร์ฟเวอร์ ไม่ใช่ลบแค่ในหน่วยความจำ
     ไม่ใช้ optimistic update เพราะเซิร์ฟเวอร์ตรวจสิทธิ์และสถานะซ้ำอีกชั้น คำขออาจถูกปฏิเสธได้จริง
     จึงต้องรอผลก่อนแล้วค่อยเอาแถวออก */
  var confirmBtn = document.getElementById('activity-delete-confirm');

  confirmBtn.addEventListener('click', function () {
    var index = activities.findIndex(function (a) { return a.id === deleteTargetId; });
    if (index === -1 || confirmBtn.disabled) return;

    var target = activities[index];
    setDeleting(true);

    fetch('/admin/activities/' + encodeURIComponent(target.id), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
        'X-HTTP-Method-Override': 'DELETE'
      }
    })
      .then(function (res) {
        return res.json().catch(function () { return {}; }).then(function (body) {
          if (!res.ok) throw new Error(body.message || 'ลบไม่สำเร็จ กรุณาลองใหม่');
          return body;
        });
      })
      .then(function (body) {
        activities.splice(index, 1);
        window.TFC.closeModal('activity-delete-modal');
        renderPills();
        render();
        if (window.TFC.showToast) window.TFC.showToast(body.message || 'ลบกิจกรรมแล้ว', 'success');
      })
      .catch(function (err) {
        if (window.TFC.showToast) window.TFC.showToast(err.message, 'danger');
      })
      .finally(function () { setDeleting(false); });
  });

  /* ปุ่มเข้าสถานะรอทันทีที่กด กันกดซ้ำระหว่างรอเซิร์ฟเวอร์ */
  function setDeleting(on) {
    confirmBtn.disabled = on;
    confirmBtn.textContent = on ? 'กำลังลบ…' : 'ลบกิจกรรม';
  }

  renderPills();
  render();
})();
</script>
@endpush
