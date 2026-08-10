/* TheFarmConcept — หน้าตอบแบบประเมิน (admin/evaluations/responses.html)
   รายการคำตอบแบบประเมินสุขภาพที่กลุ่มตัวอย่างส่งเข้ามา — อ่านอย่างเดียว ไม่มีหน้ารายละเอียด

   การกรองและแบ่งหน้าเรียกผ่าน TFC.surveyResponses.query() ซึ่งมีหน้าตาเหมือน endpoint จริง
   ไฟล์นี้จึงไม่เคยถือรายการทั้งหมดไว้เอง และไม่ได้ตัดหน้าเองที่ frontend */
(function () {
  var S = window.TFC.surveyResponses;
  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  var PAGE_SIZES = [10, 20, 50];

  var state = { round: S.ALL, keyword: '', page: 1, pageSize: PAGE_SIZES[0] };

  /* ---------- แท็บรอบติดตาม ----------
     ชื่อรอบมาจาก master data ทั้งหมด ไม่มี 3/6/12 เดือนเขียนไว้ในไฟล์นี้ */
  function tabs() {
    return [S.ALL].concat(S.roundNames());
  }

  function renderTabs() {
    $('rl-tabs').innerHTML = tabs().map(function (name) {
      var on = state.round === name;
      return '<button type="button" role="tab" class="rl-tab' + (on ? ' is-on' : '') +
        '" aria-selected="' + on + '" data-round="' + esc(name) + '">' + esc(name) + '</button>';
    }).join('');
  }

  /* ---------- ตาราง ---------- */
  var EMPTY_HTML =
    '<div class="rl-empty">' +
      '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><line x1="16.2" y1="16.2" x2="21" y2="21"/></svg>' +
      '<span class="rl-empty-title">ไม่พบคำตอบที่ตรงกับเงื่อนไข</span>' +
      '<span class="rl-empty-hint">ลองเปลี่ยนรอบติดตาม หรือค้นหาด้วยชื่อ/รหัสบุคคลอื่น</span>' +
    '</div>';

  function rowHtml(row, index) {
    return '<div class="rl-row">' +
      '<span class="rl-no">' + index + '</span>' +
      '<div class="rl-person">' +
        '<span class="rl-name">' + esc(row.name) + '</span>' +
        '<span class="rl-pid">' + esc(row.pid) + '</span>' +
      '</div>' +
      '<span class="rl-cell">' + esc(row.round) + '</span>' +
      '<span class="rl-cell">' + esc(row.form) + '</span>' +
      '<span class="rl-cell rl-at">' + esc(S.fmtDateTime(row)) + '</span>' +
      '</div>';
  }

  var HEAD_HTML =
    '<div class="rl-row rl-th">' +
      '<span>#</span>' +
      '<span>ชื่อ / รหัส</span>' +
      '<span>รอบติดตาม</span>' +
      '<span>แบบประเมิน</span>' +
      '<span>วันเวลาที่ตอบ</span>' +
    '</div>';

  function render() {
    var result = S.query({
      round: state.round, keyword: state.keyword,
      page: state.page, pageSize: state.pageSize
    });

    /* หน้าที่ขอเกินจำนวนหน้าจริง (เช่นกรองแล้วผลลัพธ์หดลง) ให้ถอยมาหน้าสุดท้ายแล้วถามใหม่ */
    var pageCount = Math.max(1, Math.ceil(result.total / state.pageSize));
    if (state.page > pageCount) {
      state.page = pageCount;
      return render();
    }

    var start = (state.page - 1) * state.pageSize;
    $('rl-table').innerHTML = HEAD_HTML + (result.rows.length
      ? result.rows.map(function (row, i) { return rowHtml(row, start + i + 1); }).join('')
      : EMPTY_HTML);

    $('rl-count').textContent = 'พบ ' + result.total + ' ชุด';

    var foot = $('rl-pagination');
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
  }

  /* ---------- เหตุการณ์ ---------- */
  $('rl-tabs').addEventListener('click', function (e) {
    var tab = e.target.closest('[data-round]');
    if (!tab) return;
    var name = tab.getAttribute('data-round');
    if (name === state.round) return;
    state.round = name;
    state.page = 1;          /* เปลี่ยนเงื่อนไขต้องกลับหน้า 1 เสมอ */
    renderTabs();
    render();
  });

  /* แผงค้นหาใช้ component กลาง — ปุ่มแว่นขยายเปลี่ยนเป็นสีเขียวเองเมื่อมีคำค้นค้างอยู่ */
  window.TFC.searchPopover('rl-search', {
    triggerLabel: 'ค้นหาคำตอบแบบประเมิน',
    searchLabel: 'ค้นหา:',
    submitLabel: 'ดูผลลัพธ์',
    clearLabel: 'ล้างค่า',
    search: { placeholder: 'ชื่อ หรือรหัสบุคคล' },
    onSearch: function (values, done) {
      state.keyword = values.keyword;
      state.page = 1;
      render();
      done();
    },
    onClear: function () {
      state.keyword = '';
      state.page = 1;
      render();
    }
  });

  /* ส่งออกตามเงื่อนไขที่กรองอยู่ ไม่ใช่ทั้งตาราง — เรียก query() โดยไม่ส่งหน้า จึงได้ครบทุกหน้า */
  $('rl-export').addEventListener('click', function () {
    var all = S.query({ round: state.round, keyword: state.keyword });
    window.TFC.exportCsv(
      'ตอบแบบประเมิน.csv',
      ['ลำดับ', 'ชื่อ-นามสกุล', 'รหัสบุคคล', 'รอบติดตาม', 'แบบประเมิน', 'วันเวลาที่ตอบ'],
      all.rows.map(function (row, i) {
        return [i + 1, row.name, row.pid, row.round, row.form, S.fmtDateTime(row)];
      })
    );
  });

  renderTabs();
  render();
})();
