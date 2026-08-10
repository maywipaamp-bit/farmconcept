/* TheFarmConcept — หน้าตอบประเมินหลังกิจกรรม (admin/activities/responses.html)
   รายการคำตอบแบบประเมินหลังกิจกรรมรายชุด — อ่านอย่างเดียว ไม่มีหน้ารายละเอียด

   คอนเซ็ปต์เดียวกับหน้า "ตอบแบบประเมิน" ฝั่งประเมินสุขภาพ ต่างกันที่แหล่งข้อมูล
   ทุกการอ่านผ่าน TFC.satisfactionService ซึ่งรับประกันว่าไม่มี user_id/ชื่อ/เบอร์โทร
   ออกมาถึงหน้าจอเลย — ตัวระบุเดียวที่มีคือลำดับผู้ตอบภายในกิจกรรมนั้น */
(function () {
  var svc = window.TFC.satisfactionService;
  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  var PAGE_SIZES = [10, 20, 50];

  /* แท็บช่วงคะแนน — key ต้องตรงกับ bandOf() ใน satisfaction-service.js */
  var BANDS = [
    { key: 'all', label: 'ทั้งหมด' },
    { key: 'praise', label: 'ชื่นชม (4–5)' },
    { key: 'mid', label: 'ปานกลาง (3)' },
    { key: 'improve', label: 'ต้องปรับปรุง (1–2)' }
  ];

  var state = {
    activity: null,
    activities: [],
    band: 'all',
    keyword: '',
    page: 1,
    pageSize: PAGE_SIZES[0],
    pickerOpen: false,
    loaded: false,
    seq: 0            /* กันผลลัพธ์ที่มาช้ากว่าคำขอถัดไปเขียนทับของใหม่ */
  };

  /* ---------- หัวหน้า + popover เลือกกิจกรรม ---------- */
  function activityMeta(a) {
    return window.TFC.formatThaiDate(a.startDate) + ' · ตอบแล้ว ' + a.responseCount + ' ชุด';
  }

  function renderHead() {
    var a = state.activity;
    if (!a) return;
    $('ar-act-name').textContent = a.name;
    $('ar-act-code').textContent = a.id;

    $('ar-picker-btn').setAttribute('aria-expanded', String(state.pickerOpen));
    var panel = $('ar-picker-panel');
    panel.hidden = !state.pickerOpen;
    panel.innerHTML = '<span class="act-picker-title">เลือกกิจกรรม</span>' +
      state.activities.map(function (item) {
        return '<button type="button" class="act-picker-item' + (item.id === a.id ? ' is-active' : '') +
          '" role="option" aria-selected="' + (item.id === a.id) + '" data-activity="' + esc(item.id) + '">' +
          '<span class="act-picker-item-name">' + esc(item.name) + '</span>' +
          '<span class="act-picker-item-meta">' + esc(activityMeta(item)) + '</span>' +
          '</button>';
      }).join('');
  }

  function renderTabs() {
    $('ar-tabs').innerHTML = BANDS.map(function (b) {
      var on = state.band === b.key;
      return '<button type="button" role="tab" class="ar-tab' + (on ? ' is-on' : '') +
        '" aria-selected="' + on + '" data-band="' + esc(b.key) + '">' + esc(b.label) + '</button>';
    }).join('');
  }

  /* ---------- ตาราง ---------- */
  var HEAD_HTML =
    '<div class="ar-row ar-th">' +
      '<span>#</span>' +
      '<span>ผู้ตอบ</span>' +
      '<span class="ar-mid">คะแนนรวม</span>' +
      '<span>รอบที่เข้าร่วม</span>' +
      '<span>ความเห็นเพิ่มเติม</span>' +
      '<span>วันเวลาที่ตอบ</span>' +
    '</div>';

  var EMPTY_HTML =
    '<div class="ar-empty">' +
      '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><line x1="16.2" y1="16.2" x2="21" y2="21"/></svg>' +
      '<span class="ar-empty-title">ไม่พบคำตอบที่ตรงกับเงื่อนไข</span>' +
      '<span class="ar-empty-hint">ลองเปลี่ยนช่วงคะแนน หรือค้นด้วยคำอื่นในความเห็น</span>' +
    '</div>';

  /* โครงร่างระหว่างโหลดครั้งแรก — จำนวนแถวเท่าที่จะแสดงจริง หน้าจึงไม่กระโดดตอนข้อมูลมา */
  function skeletonHtml() {
    var row = '<div class="ar-row ar-skeleton-row">' +
      '<span class="skeleton skeleton--text-sm"></span>' +
      '<span class="skeleton skeleton--text"></span>' +
      '<span class="skeleton skeleton--text"></span>' +
      '<span class="skeleton skeleton--text"></span>' +
      '<span class="skeleton skeleton--text"></span>' +
      '<span class="skeleton skeleton--text"></span>' +
      '</div>';
    return HEAD_HTML + new Array(state.pageSize + 1).join(row);
  }

  /* "10 ส.ค. 69 · 12:45 น." — วันที่เก็บเป็น ISO แปลงตอนแสดงผลเท่านั้น */
  function fmtDateTime(iso) {
    var parts = String(iso).split('T');
    var date = window.TFC.formatThaiDate(parts[0]).replace(/(\d{2})(\d{2})$/, '$2');
    return date + ' · ' + (parts[1] || '').slice(0, 5) + ' น.';
  }

  function rowHtml(row) {
    return '<div class="ar-row">' +
      '<span class="ar-no">' + row.seq + '</span>' +
      '<span class="ar-who">ผู้ตอบ #' + row.seq + '</span>' +
      '<span class="ar-mid"><span class="ar-score is-' + row.band + '">' + row.score + '/5</span></span>' +
      '<span class="ar-cell">' + esc(row.round) + '</span>' +
      (row.comment
        ? '<span class="ar-comment" title="' + esc(row.comment) + '">' + esc(row.comment) + '</span>'
        : '<span class="ar-comment is-none">ไม่ได้ให้ความเห็น</span>') +
      '<span class="ar-cell ar-at">' + esc(fmtDateTime(row.submittedAt)) + '</span>' +
      '</div>';
  }

  /* โหลดข้อมูลใหม่โดยไม่ถอดของเดิมทิ้ง — จางลงแล้วค่อยสลับ ตามมาตรฐาน Motion */
  function render() {
    var table = $('ar-table');
    if (!state.loaded) table.innerHTML = skeletonHtml();
    else table.classList.add('is-refreshing');

    var seq = ++state.seq;
    return svc.responseList(state.activity.id, {
      band: state.band,
      keyword: state.keyword,
      limit: state.pageSize,
      offset: (state.page - 1) * state.pageSize
    }).then(function (result) {
      if (seq !== state.seq) return;      /* มีคำขอใหม่แซงไปแล้ว ทิ้งผลชุดนี้ */

      var pageCount = Math.max(1, Math.ceil(result.total / state.pageSize));
      if (state.page > pageCount) {
        state.page = pageCount;
        return render();
      }

      table.classList.remove('is-refreshing');
      table.innerHTML = HEAD_HTML + (result.rows.length ? result.rows.map(rowHtml).join('') : EMPTY_HTML);
      $('ar-count').textContent = 'พบ ' + result.total + ' ชุด';
      state.loaded = true;

      var foot = $('ar-pagination');
      foot.hidden = result.total === 0;
      if (!foot.hidden) {
        window.TFC.renderPagination(foot, {
          page: state.page,
          pageSize: state.pageSize,
          total: result.total,
          pageSizeOptions: PAGE_SIZES,
          unit: 'ชุด',
          edges: false,
          sizeWithInfo: true,
          onChange: function (next) { state.page = next; render(); },
          onPageSizeChange: function (size) { state.pageSize = size; state.page = 1; render(); }
        });
      }
    });
  }

  /* ---------- เหตุการณ์ ---------- */
  function closePicker() {
    if (!state.pickerOpen) return;
    state.pickerOpen = false;
    renderHead();
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('#ar-picker-btn')) {
      state.pickerOpen = !state.pickerOpen;
      return renderHead();
    }

    var pick = e.target.closest('[data-activity]');
    if (pick) {
      var id = pick.getAttribute('data-activity');
      state.pickerOpen = false;
      if (id === state.activity.id) return renderHead();
      state.activity = state.activities.filter(function (a) { return a.id === id; })[0];
      state.page = 1;                 /* เปลี่ยนกิจกรรมต้องกลับหน้า 1 */
      history.replaceState(null, '', 'responses.html?id=' + encodeURIComponent(id));
      renderHead();
      return render();
    }

    var tab = e.target.closest('[data-band]');
    if (tab) {
      var band = tab.getAttribute('data-band');
      if (band === state.band) return;
      state.band = band;
      state.page = 1;
      renderTabs();
      return render();
    }

    if (!e.target.closest('.act-picker')) closePicker();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape' || !state.pickerOpen) return;
    closePicker();
    $('ar-picker-btn').focus();
  });

  window.TFC.searchPopover('ar-search', {
    triggerLabel: 'ค้นหาคำตอบ',
    searchLabel: 'ค้นหา:',
    submitLabel: 'ดูผลลัพธ์',
    clearLabel: 'ล้างค่า',
    search: { placeholder: 'รหัสผู้ตอบ หรือข้อความในความเห็น' },
    onSearch: function (values, done) {
      state.keyword = values.keyword;
      state.page = 1;
      render().then(done, done);
    },
    onClear: function () {
      state.keyword = '';
      state.page = 1;
      render();
    }
  });

  /* ส่งออกตามเงื่อนไขที่กรองอยู่ (ไม่ส่ง limit = ได้ครบทุกหน้า)
     คอลัมน์ที่ส่งออกต้องไม่มีอะไรที่ย้อนกลับไปหาตัวคนได้ — มีแค่ลำดับผู้ตอบ */
  $('ar-export').addEventListener('click', function () {
    var button = this;
    button.disabled = true;
    svc.responseList(state.activity.id, { band: state.band, keyword: state.keyword })
      .then(function (result) {
        window.TFC.exportCsv(
          'ตอบประเมินหลังกิจกรรม-' + state.activity.id + '.csv',
          ['ลำดับ', 'ผู้ตอบ', 'คะแนนรวม', 'รอบที่เข้าร่วม', 'ความเห็นเพิ่มเติม', 'วันเวลาที่ตอบ'],
          result.rows.map(function (row) {
            return [row.seq, 'ผู้ตอบ #' + row.seq, row.score + '/5', row.round,
              row.comment || 'ไม่ได้ให้ความเห็น', fmtDateTime(row.submittedAt)];
          })
        );
      })
      .then(function () { button.disabled = false; }, function () { button.disabled = false; });
  });

  /* ---------- เริ่มทำงาน ---------- */
  renderTabs();
  $('ar-table').innerHTML = skeletonHtml();

  svc.activities().then(function (list) {
    /* กิจกรรมที่ยังไม่มีใครตอบ เปิดมาแล้วเจอตารางว่าง จึงเรียงกิจกรรมที่มีคำตอบขึ้นก่อน */
    state.activities = list.slice().sort(function (a, b) { return b.responseCount - a.responseCount; });

    var wanted = new URLSearchParams(location.search).get('id');
    state.activity = state.activities.filter(function (a) { return a.id === wanted; })[0] || state.activities[0];

    renderHead();
    return render();
  });
})();
