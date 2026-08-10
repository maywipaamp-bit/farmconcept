/* TheFarmConcept — หน้ารายการรอบติดตาม (admin/evaluations/rounds.html) */
(function () {
  var R = window.TFC.rounds;
  var C = window.TFC.cohort;
  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  var TABS = ['ทั้งหมด'].concat(R.STATES);
  var PAGE_SIZES = [10, 20, 50];

  var state = {
    tab: 'ทั้งหมด', q: '', form: 'ทุกแบบประเมิน',
    searchOpen: false, menu: null, page: 1, pageSize: PAGE_SIZES[0]
  };

  function filtered() {
    var q = state.q.trim().toLowerCase();
    return R.batches().filter(function (b) {
      if (state.tab !== 'ทั้งหมด' && b.state !== state.tab) return false;
      if (state.form !== 'ทุกแบบประเมิน' && b.form !== state.form) return false;
      if (q && b.name.toLowerCase().indexOf(q) < 0) return false;
      return true;
    });
  }

  function hasFilter() { return !!state.q.trim() || state.form !== 'ทุกแบบประเมิน'; }

  function renderTabs() {
    $('fb-tabs').innerHTML = TABS.map(function (t) {
      var on = state.tab === t;
      return '<button type="button" class="fb-tab' + (on ? ' is-on' : '') + '" role="tab" aria-selected="' + on +
        '" data-tab="' + esc(t) + '">' + esc(t) + '</button>';
    }).join('');
  }

  function fillFilters() {
    $('fb-form').innerHTML = ['ทุกแบบประเมิน'].concat(R.FORMS)
      .map(function (v) { return '<option value="' + esc(v) + '">' + esc(v) + '</option>'; }).join('');
  }

  function syncSearch() {
    $('fb-search-btn').classList.toggle('is-on', state.searchOpen || hasFilter());
    $('fb-search-btn').setAttribute('aria-expanded', String(state.searchOpen));
    $('fb-search-panel').hidden = !state.searchOpen;
  }

  var STATE_CLASS = {
    'กำลังดำเนินการ': 'is-active', 'รอเริ่ม': 'is-waiting',
    'เสร็จสิ้น': 'is-done', 'ยกเลิกแล้ว': 'is-cancelled'
  };

  function rowHtml(b, index) {
    var s = R.statsOf(b.id);
    var open = state.menu === b.id;
    var pct = s.total ? Math.round((s.answered / s.total) * 100) : 0;

    return '<div class="fb-tr is-clickable" data-open="' + esc(b.id) + '">' +
      '<div class="fb-cell fb-nums fb-no">' + index + '</div>' +
      '<div class="fb-name-cell"><span class="fb-name">' + esc(b.name) + '</span></div>' +
      '<div class="fb-cell fb-nums">' + esc(C.fmt(b.from)) + ' – ' + esc(C.fmt(b.to)) + '</div>' +
      '<div class="fb-cell">' + esc(b.form) + '</div>' +
      '<div class="fb-cell fb-nums text-center">' + s.total + '</div>' +
      '<div class="fb-progress-cell">' +
        '<span class="fb-progress-text">' + s.answered + '/' + s.total + '</span>' +
        '<div class="fb-progress"><span style="width: ' + pct + '%"></span></div>' +
      '</div>' +
      '<div class="fb-cell fb-nums text-center">' + s.notifiable + '</div>' +
      /* คนที่แจ้งเตือนไม่ได้ต้องเด่นพอให้แอดมินเห็นว่ามีงานต้องตามเอง */
      '<div class="fb-cell fb-nums text-center' + (s.unreachable > 0 ? ' is-warn' : '') + '">' + s.unreachable + '</div>' +
      '<div><span class="fb-state ' + STATE_CLASS[b.state] + '">' + esc(b.state) + '</span></div>' +
      '<div class="fb-actions">' +
        '<button type="button" class="fb-more' + (open ? ' is-on' : '') + '" data-menu="' + esc(b.id) +
          '" aria-haspopup="menu" aria-expanded="' + open + '" aria-label="เมนูจัดการ ' + esc(b.name) + '">' +
          '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/></svg>' +
        '</button>' +
        (open
          ? '<div class="fb-menu" role="menu">' +
              '<a class="fb-menu-item" role="menuitem" href="round-create.html?id=' + esc(b.id) + '">แก้ไขรอบติดตาม</a>' +
              '<button type="button" class="fb-menu-item is-danger" role="menuitem" data-cancel="' + esc(b.id) + '"' +
                (b.state === 'ยกเลิกแล้ว' ? ' disabled' : '') + '>ยกเลิกรอบติดตาม</button>' +
            '</div>'
          : '') +
      '</div>' +
      '</div>';
  }

  var EMPTY_HTML = '<div class="fb-empty">' +
    '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg>' +
    '<span class="fb-empty-title">ไม่พบรอบติดตามที่ตรงกับเงื่อนไข</span></div>';

  function render() {
    var list = filtered();
    var all = R.batches().length;
    var pageCount = Math.max(1, Math.ceil(list.length / state.pageSize));
    if (state.page > pageCount) state.page = pageCount;
    var start = (state.page - 1) * state.pageSize;
    var pageRows = list.slice(start, start + state.pageSize);

    $('fb-rows').innerHTML = pageRows.length
      ? pageRows.map(function (b, i) { return rowHtml(b, start + i + 1); }).join('')
      : EMPTY_HTML;

    $('fb-count').textContent = 'แสดง ' + list.length + ' จาก ' + all + ' รอบ';
    $('fb-foot').innerHTML = '<div id="fb-pagination"></div>';

    window.TFC.renderPagination('fb-pagination', {
      page: state.page, pageSize: state.pageSize, total: list.length, pageSizeOptions: PAGE_SIZES,
      onChange: function (p) { state.page = p; render(); },
      onPageSizeChange: function (s) { state.pageSize = s; state.page = 1; render(); }
    });

    flipMenu();
    syncSearch();
  }

  function flipMenu() {
    var menu = document.querySelector('.fb-menu');
    var scroller = document.querySelector('.fb-table-scroll');
    if (!menu || !scroller) return;
    menu.classList.remove('is-up');
    if (menu.getBoundingClientRect().bottom > scroller.getBoundingClientRect().bottom) menu.classList.add('is-up');
  }

  /* ---------- เหตุการณ์ ---------- */
  document.addEventListener('click', function (e) {
    var t = e.target;

    var tab = t.closest('[data-tab]');
    if (tab) { state.tab = tab.getAttribute('data-tab'); state.page = 1; state.menu = null; renderTabs(); return render(); }

    if (t.closest('#fb-search-btn')) { state.searchOpen = !state.searchOpen; return syncSearch(); }

    if (t.closest('#fb-clear')) {
      state.q = ''; state.form = 'ทุกแบบประเมิน';
      $('fb-q').value = ''; $('fb-form').value = 'ทุกแบบประเมิน';
      state.page = 1;
      return render();
    }

    if (t.closest('#fb-apply')) {
      state.q = $('fb-q').value;
      state.form = $('fb-form').value;
      state.page = 1;
      state.searchOpen = false;
      return render();
    }

    var menu = t.closest('[data-menu]');
    if (menu) {
      e.stopPropagation();
      var mid = menu.getAttribute('data-menu');
      state.menu = state.menu === mid ? null : mid;
      return render();
    }

    var cancel = t.closest('[data-cancel]');
    if (cancel) {
      e.stopPropagation();
      if (cancel.disabled) return;
      R.cancelBatch(cancel.getAttribute('data-cancel'));
      state.menu = null;
      render();
      if (window.TFC.showToast) window.TFC.showToast('ยกเลิกรอบติดตามแล้ว', 'info');
      return;
    }

    /* คลิกที่แถว (นอกเมนู) เข้าหน้ารายละเอียด */
    var row = t.closest('[data-open]');
    if (row && !t.closest('.fb-actions')) {
      location.href = 'round-detail.html?id=' + encodeURIComponent(row.getAttribute('data-open'));
      return;
    }

    if (state.menu && !t.closest('.fb-actions')) { state.menu = null; render(); }
    if (state.searchOpen && !t.closest('.fb-search')) { state.searchOpen = false; syncSearch(); }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (state.menu) { state.menu = null; render(); }
    if (state.searchOpen) { state.searchOpen = false; syncSearch(); }
  });

  /* ---------- QR ถาวรของระบบติดตามสุขภาพ ----------
     URL คงที่ตัวเดียว ไม่มี pid / รหัสรอบ / รหัสแบบประเมินอยู่ข้างใน
     จึงพิมพ์ครั้งเดียวใช้ได้ตลอด และหลุดถึงคนนอกก็เข้าไม่ได้เพราะต้องยืนยันตัวตนก่อน
     ห้ามเอาลิงก์เฉพาะบุคคล (C.surveyLink) มาทำ QR แจก — นั่นเท่ากับแจกกุญแจของคนนั้น */
  var SURVEY_URL = 'farmconcept.th/h';

  /* ลายตัวอย่างเท่านั้น สแกนไม่ติด — ของจริงต้องให้ backend สร้างจาก SURVEY_URL
     ใช้ seed คงที่เพื่อให้ลายไม่เปลี่ยนทุกครั้งที่เปิดหน้า */
  function qrSvg(seed, n) {
    n = n || 21;
    var cells = '', h = 0, i;
    for (i = 0; i < seed.length; i++) h = (h * 131 + seed.charCodeAt(i)) >>> 0;
    for (i = 0; i < n * n; i++) {
      h = (h * 1103515245 + 12345) >>> 0;
      if ((h >>> 16) % 100 < 46) cells += '<rect x="' + (i % n) + '" y="' + Math.floor(i / n) + '" width="1" height="1"/>';
    }
    [[0, 0], [n - 5, 0], [0, n - 5]].forEach(function (p) {
      cells += '<rect x="' + p[0] + '" y="' + p[1] + '" width="5" height="5" fill="none" stroke="currentColor" stroke-width="1"/>' +
        '<rect x="' + (p[0] + 2) + '" y="' + (p[1] + 2) + '" width="1" height="1"/>';
    });
    return '<svg viewBox="0 0 ' + n + ' ' + n + '" fill="currentColor" aria-hidden="true">' + cells + '</svg>';
  }

  function initQr() {
    var svg = qrSvg(SURVEY_URL);
    $('fb-qr').innerHTML = svg;
    $('fb-qr-large').innerHTML = svg;

    var copyText = $('fb-qr-copy-text');
    var copyTimer = null;

    /* clipboard ล้มเหลวได้จริง (ไม่ใช่ https หรือผู้ใช้ไม่อนุญาต)
       จึงต้องรอผลก่อนค่อยขึ้น "คัดลอกแล้ว" ไม่งั้นปุ่มจะบอกว่าสำเร็จทั้งที่ไม่ได้คัดลอก */
    $('fb-qr-copy').addEventListener('click', function () {
      var done = function () {
        copyText.textContent = 'คัดลอกแล้ว';
        clearTimeout(copyTimer);
        copyTimer = setTimeout(function () { copyText.textContent = 'คัดลอกลิงก์'; }, 2000);
        if (window.TFC.showToast) window.TFC.showToast('คัดลอกลิงก์ ' + SURVEY_URL + ' แล้ว', 'success');
      };
      var fail = function () {
        if (window.TFC.showToast) window.TFC.showToast('คัดลอกไม่สำเร็จ — ลิงก์คือ ' + SURVEY_URL, 'warning');
      };
      if (!navigator.clipboard) return fail();
      navigator.clipboard.writeText('https://' + SURVEY_URL).then(done, fail);
    });

    $('fb-qr-open').addEventListener('click', function () { window.TFC.openModal('fb-qr-modal'); });

    $('fb-qr-download').addEventListener('click', function () {
      var el = $('fb-qr').querySelector('svg');
      if (!el) return;
      var blob = new Blob([el.outerHTML], { type: 'image/svg+xml' });
      var url = URL.createObjectURL(blob);
      var link = document.createElement('a');
      link.href = url;
      link.download = 'health-survey-qr.svg';
      link.click();
      URL.revokeObjectURL(url);
      if (window.TFC.showToast) window.TFC.showToast('ดาวน์โหลด QR แล้ว', 'success');
    });

    /* พิมพ์เฉพาะแผ่น QR — .is-printing ทำให้ @media print ซ่อนส่วนอื่นทั้งหมด */
    $('fb-qr-print').addEventListener('click', function () {
      document.body.classList.add('is-printing');
      window.print();
      document.body.classList.remove('is-printing');
    });
  }

  fillFilters();
  renderTabs();
  render();
  initQr();
})();
