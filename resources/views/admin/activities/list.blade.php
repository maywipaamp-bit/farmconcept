@extends('layouts.admin')

@section('title', 'รายการกิจกรรม')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="/admin/dashboard.html">แดชบอร์ด</a> <span>/</span> <span class="is-current">จัดการกิจกรรม</span>
  </nav>
  <div class="page-header" id="activity-page-header"></div>

  <div class="list-filter-bar">
    <div class="status-pills" id="activity-status-pills"></div>
    <div id="activity-search-popover"></div>
  </div>

  <div class="activity-list" id="activity-list"></div>

  <div id="activity-pagination"></div>
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
<script src="{{ asset('assets/js/activity-module.js') }}"></script>
@endpush

@push('page-script')
{{-- type="module" เพื่อให้ทำงานหลัง bundle ของ Vite ซึ่งถูก defer — ดูเหตุผลใน layouts/admin.blade.php --}}
<script type="module">
(function () {
  var mock = window.TFC_MOCK;

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
  var state = { status: '', search: '', type: '', instructor: '', area: '', sort: 'updatedAt', page: 1, pageSize: PAGE_SIZES[0] };
  var deleteTargetId = null;

  window.TFC.renderPageHeader('activity-page-header', {
    title: 'จัดการกิจกรรม',
    actions: [
      {
        label: 'เพิ่มกิจกรรม',
        href: '/admin/activities/create.html',
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

    return rows.sort(function (a, b) {
      if (state.sort === 'registered') return b.registered - a.registered;
      if (state.sort === 'updatedAt') return String(b.updatedAt || '').localeCompare(String(a.updatedAt || ''));
      if (state.sort === 'startDate') return String(a.startDate).localeCompare(String(b.startDate));
      return String(a.name).localeCompare(String(b.name), 'th');
    });
  }

  window.TFC.searchPopover('activity-search-popover', {
    search: { id: 'activity-search', placeholder: 'ค้นหากิจกรรม ผู้รับผิดชอบ สถานที่' },
    filters: [
      { id: 'type', label: 'ประเภท', placeholder: 'ทั้งหมด', options: optionsFrom(function (a) { return a.type; }) },
      { id: 'instructor', label: 'วิทยากร', placeholder: 'ทั้งหมด', options: optionsFrom(responsible) },
      { id: 'area', label: 'สถานที่จัด', placeholder: 'ทั้งหมด', options: optionsFrom(areasOf) },
      {
        id: 'sort',
        label: 'เรียงลำดับ',
        placeholder: 'แก้ไขล่าสุด',
        options: [
          { value: 'startDate', label: 'วันที่จัดกิจกรรม' },
          { value: 'name', label: 'ชื่อกิจกรรม' },
          { value: 'registered', label: 'จำนวนผู้ลงทะเบียน' }
        ]
      }
    ],
    onSearch: function (values, done) {
      state.search = values.keyword;
      state.type = values.filters.type || '';
      state.instructor = values.filters.instructor || '';
      state.area = values.filters.area || '';
      state.sort = values.filters.sort || 'updatedAt';
      state.page = 1;
      render();
      done();
    },
    onClear: function () {
      state.search = '';
      state.type = '';
      state.instructor = '';
      state.area = '';
      state.sort = 'updatedAt';
      state.page = 1;
      render();
    }
  });

  /* ---------- มุมมองตาราง ---------- */
  var TABLE_COLS = ['รหัส', 'ชื่อกิจกรรม', 'วันและเวลา', 'สถานที่', 'ผู้ลงทะเบียน', 'เข้าร่วม', 'แบบประเมิน', 'สถานะ', ''];

  function rowHtml(activity) {
    var schedules = window.TFC.activity.schedules(activity);
    var timeText = schedules.length && schedules[0].timeStart
      ? schedules[0].timeStart + '–' + schedules[0].timeEnd + ' น.'
      : '';
    var cap = activity.capacity || 0;
    var reg = activity.registered || 0;
    var percent = cap ? Math.min(100, Math.round((reg / cap) * 100)) : 0;
    var tone = window.TFC.badgeClassOf(mock.activityStatuses, activity.status).replace('badge-', 'is-');
    var dash = '<span class="grid-dash">—</span>';

    return '<div class="grid-row" data-id="' + activity.id + '">' +
      '<div class="grid-code">' + window.TFC.escapeHtml(activity.id) + '</div>' +

      '<div><a class="grid-name" href="/admin/activities/create.html?id=' + activity.id + '">' + window.TFC.escapeHtml(activity.name) + '</a>' +
      (responsible(activity).length ? '<div class="grid-sub">' + window.TFC.escapeHtml(responsible(activity).join(' · ')) + '</div>' : '') + '</div>' +

      '<div><div class="grid-strong">' + window.TFC.escapeHtml(window.TFC.activity.dateLabel(activity)) + '</div>' +
      (timeText ? '<div class="grid-sub">' + window.TFC.escapeHtml(timeText) + '</div>' : '') + '</div>' +

      '<div class="grid-soft">' + window.TFC.escapeHtml(areasOf(activity).join(' · ') || '-') + '</div>' +

      '<div><div class="grid-strong">' + reg + '/' + cap + '</div>' +
      '<div class="grid-bar"><span class="' + tone + '" style="width:' + percent + '%"></span></div></div>' +

      '<div class="grid-num">' + dash + '</div>' +
      '<div class="grid-num">' + dash + '</div>' +
      '<div>' + window.TFC.statusTextHTML({ options: mock.activityStatuses, value: activity.status }) + '</div>' +
      '<div class="grid-actions">' + window.TFC.actionMenuTrigger(menuItems(activity)) + '</div>' +
      '</div>';
  }

  /* ลบได้เฉพาะกิจกรรมที่ยังเป็นฉบับร่าง — เผยแพร่แล้วมีคนลงทะเบียน การลบทิ้งจะทำให้ข้อมูลผู้เข้าร่วมหายด้วย */
  function canDelete(activity) {
    return activity.status === 'ฉบับร่าง';
  }

  function menuItems(activity) {
    var items = [
      { key: 'act-view-' + activity.id, label: 'ดูรายละเอียด', icon: 'view', href: '/admin/activities/detail.html?id=' + activity.id },
      { key: 'act-edit-' + activity.id, label: 'แก้ไข', icon: 'edit', href: '/admin/activities/create.html?id=' + activity.id, perm: 'activities' }
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
        return '<div>' + window.TFC.escapeHtml(c) + '</div>';
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
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
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
