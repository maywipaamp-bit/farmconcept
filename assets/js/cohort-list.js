/* TheFarmConcept — หน้ารายการกลุ่มตัวอย่าง (admin/cohort/list.html)
   ตรรกะวันที่และสถานะทั้งหมดอยู่ใน cohort-data.js ไฟล์นี้ทำหน้าที่แสดงผลอย่างเดียว */
(function () {
  var C = window.TFC.cohort;
  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  var TABS = ['ทั้งหมด', 'ต้องติดตามรอบนี้', 'เกินกำหนด', 'ติดตามครบ', 'หลุดการติดตาม'];
  var PAGE_SIZES = [10, 20, 50];

  var state = {
    tab: 'ทั้งหมด', q: '', round: 'ทุกรอบ', area: 'ทุกพื้นที่',
    searchOpen: false, menu: null, page: 1, pageSize: PAGE_SIZES[0]
  };

  /* รวมข้อมูลที่ derive แล้วของทุกคนไว้ชุดเดียว ใช้ทั้งการ์ดสรุปและตาราง */
  function infoAll() {
    return C.members().map(function (m) {
      return { m: m, sc: C.schedule(m), next: C.nextRound(m), status: C.personStatus(m) };
    });
  }

  function filtered(info) {
    var q = state.q.trim().toLowerCase();
    return info.filter(function (i) {
      if (state.tab === 'ต้องติดตามรอบนี้' && !i.sc.some(function (r) { return r.state === 'รอติดตาม'; })) return false;
      if (state.tab === 'เกินกำหนด' && !i.sc.some(function (r) { return r.state === 'เกินกำหนด'; })) return false;
      if (state.tab === 'ติดตามครบ' && i.status !== 'ติดตามครบ') return false;
      if (state.tab === 'หลุดการติดตาม' && i.status !== 'หลุดการติดตาม') return false;
      if (state.round !== 'ทุกรอบ' && (!i.next || i.next.name !== state.round)) return false;
      if (state.area !== 'ทุกพื้นที่' && i.m.area !== state.area) return false;
      if (q && (i.m.name + ' ' + i.m.pid + ' ' + i.m.phone).toLowerCase().indexOf(q) < 0) return false;
      return true;
    });
  }

  function hasFilter() {
    return !!state.q.trim() || state.round !== 'ทุกรอบ' || state.area !== 'ทุกพื้นที่';
  }

  /* ---------- การ์ดสรุป ---------- */
  function renderStats() {
    var info = infoAll();
    var dueNow = info.filter(function (i) { return i.sc.some(function (r) { return r.state === 'รอติดตาม'; }); }).length;
    var overdue = info.filter(function (i) { return i.sc.some(function (r) { return r.state === 'เกินกำหนด'; }); }).length;
    var running = info.filter(function (i) { return i.status === 'กำลังติดตาม'; }).length;

    var cards = [
      { label: 'กลุ่มตัวอย่างทั้งหมด', value: info.length, hint: 'เป้าหมาย ' + C.TARGET + ' คน', warn: false },
      { label: 'กำลังติดตาม', value: running, hint: Math.round((running / (info.length || 1)) * 100) + '% ของกลุ่ม', warn: false },
      /* เหลืองเฉพาะเมื่อมีงานค้างจริง ไม่งั้นเป็นเทาเหมือนใบอื่น */
      { label: 'ต้องติดตามช่วงนี้', value: dueNow, hint: dueNow > 0 ? 'อยู่ในช่วงติดตาม' : 'ไม่มีรายการ', warn: dueNow > 0 },
      { label: 'เกินกำหนด', value: overdue, hint: overdue > 0 ? 'พ้นช่วงติดตามแล้ว' : 'ไม่มีรายการ', warn: overdue > 0 }
    ];

    $('co-stats').innerHTML = cards.map(function (c) {
      return '<div class="card co-stat">' +
        '<span class="co-stat-label">' + esc(c.label) + '</span>' +
        '<span class="co-stat-value' + (c.warn ? ' is-warn' : '') + '">' + c.value + ' <span class="co-stat-unit">คน</span></span>' +
        '<span class="co-stat-hint">' + esc(c.hint) + '</span>' +
        '</div>';
    }).join('');
  }

  /* ---------- แท็บและตัวกรอง ---------- */
  function renderTabs() {
    $('co-tabs').innerHTML = TABS.map(function (t) {
      var on = state.tab === t;
      return '<button type="button" class="co-tab' + (on ? ' is-on' : '') + '" role="tab" aria-selected="' + on +
        '" data-tab="' + esc(t) + '">' + esc(t) + '</button>';
    }).join('');
  }

  function fillFilters() {
    $('co-round').innerHTML = ['ทุกรอบ'].concat(C.ROUNDS.map(function (r) { return r.name; }))
      .map(function (v) { return '<option value="' + esc(v) + '">' + esc(v) + '</option>'; }).join('');
    $('co-area').innerHTML = ['ทุกพื้นที่'].concat(C.AREAS)
      .map(function (v) { return '<option value="' + esc(v) + '">' + esc(v) + '</option>'; }).join('');
  }

  function syncSearch() {
    $('co-search-btn').classList.toggle('is-on', state.searchOpen || hasFilter());
    $('co-search-btn').setAttribute('aria-expanded', String(state.searchOpen));
    $('co-search-panel').hidden = !state.searchOpen;
  }

  /* ---------- ไอคอนสถานะรายรอบ ---------- */
  var ROUND_ICONS = {
    'ตอบแล้ว': { cls: 'is-done', path: '<path d="M5 12.5l4.5 4.5L19 7.5"/>', w: 2 },
    'รอติดตาม': { cls: 'is-due', path: '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.6V12l3 1.8"/>', w: 1.9 },
    'เกินกำหนด': { cls: 'is-over', path: '<path d="M6.5 6.5l11 11M17.5 6.5l-11 11"/>', w: 2 },
    'ยังไม่ถึงกำหนด': { cls: 'is-idle', path: '<path d="M6 12h12"/>', w: 1.8 },
    'ยุติการติดตาม': { cls: 'is-idle', path: '<path d="M6 12h12"/>', w: 1.8 }
  };

  function roundIcon(r) {
    var ic = ROUND_ICONS[r.state] || ROUND_ICONS['ยังไม่ถึงกำหนด'];
    var title = r.name + ' · ' + r.state +
      (r.at ? ' · ตอบเมื่อ ' + C.fmt(r.at) : ' · ครบกำหนด ' + C.fmt(r.due));
    return '<span class="co-round-icon ' + ic.cls + '" title="' + esc(title) + '">' +
      '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' + ic.w +
      '" stroke-linecap="round" stroke-linejoin="round">' + ic.path + '</svg></span>';
  }

  function renderLegend() {
    var items = [
      { state: 'ตอบแล้ว', label: 'ตอบแล้ว' },
      { state: 'รอติดตาม', label: 'รอติดตาม' },
      { state: 'เกินกำหนด', label: 'เกินกำหนด' },
      { state: 'ยังไม่ถึงกำหนด', label: 'ยังไม่ถึงกำหนด' }
    ];
    $('co-legend').innerHTML = items.map(function (i) {
      var ic = ROUND_ICONS[i.state];
      return '<span class="co-legend-item">' +
        '<span class="co-round-icon ' + ic.cls + '">' +
        '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' + ic.w +
        '" stroke-linecap="round" stroke-linejoin="round">' + ic.path + '</svg></span>' +
        esc(i.label) + '</span>';
    }).join('');
  }

  /* ---------- ตาราง ---------- */
  var STATUS_CLASS = {
    'กำลังติดตาม': 'is-running', 'ติดตามครบ': 'is-done',
    'หลุดการติดตาม': 'is-lost', 'ยุติการติดตาม': 'is-stopped'
  };

  function rowHtml(i) {
    var m = i.m;
    var due = C.dueText(i.next);
    var open = state.menu === m.id;
    var isStopped = !!C.stopped[m.id];

    return '<div class="co-tr" data-row="' + esc(m.id) + '">' +
      '<div class="co-name-cell">' +
        '<a class="co-name" href="detail.html?id=' + esc(m.id) + '">' + esc(m.name) + '</a>' +
        '<span class="co-pid">' + esc(m.pid) + '</span>' +
      '</div>' +
      '<div class="co-area-cell">' +
        '<span class="co-area">' + esc(m.area) + '</span>' +
        '<span class="co-line ' + (m.line ? 'is-linked' : 'is-unlinked') + '">' +
          (m.line ? 'ผูก LINE แล้ว' : 'ยังไม่ผูก LINE') + '</span>' +
      '</div>' +
      '<div class="co-cell">' + esc(m.target) + '</div>' +
      i.sc.map(function (r) { return '<div class="co-round-cell">' + roundIcon(r) + '</div>'; }).join('') +
      '<div class="co-next">' +
        '<span class="co-next-name">' + esc(i.next ? i.next.name : '—') + '</span>' +
        '<span class="co-next-due ' + due.tone + '">' + esc(due.text) + '</span>' +
      '</div>' +
      '<div><span class="co-status ' + STATUS_CLASS[i.status] + '">' + esc(i.status) + '</span></div>' +
      '<div class="co-actions">' +
        '<button type="button" class="co-more' + (open ? ' is-on' : '') + '" data-menu="' + esc(m.id) +
          '" aria-haspopup="menu" aria-expanded="' + open + '" aria-label="เมนูจัดการ ' + esc(m.name) + '">' +
          '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/></svg>' +
        '</button>' +
        (open
          ? '<div class="co-menu" role="menu">' +
              '<a class="co-menu-item" role="menuitem" href="detail.html?id=' + esc(m.id) + '">ดูรายละเอียด</a>' +
              '<button type="button" class="co-menu-item" role="menuitem" data-notify="' + esc(m.id) + '"' +
                (m.line ? '' : ' disabled title="ส่งไม่ได้ — ยังไม่ผูก LINE"') + '>ส่งแจ้งเตือน LINE</button>' +
              '<button type="button" class="co-menu-item' + (isStopped ? '' : ' is-danger') + '" role="menuitem" data-stop="' + esc(m.id) + '">' +
                (isStopped ? 'กลับมาติดตามต่อ' : 'ยุติการติดตาม') + '</button>' +
            '</div>'
          : '') +
      '</div>' +
      '</div>';
  }

  var EMPTY_HTML = '<div class="co-empty">' +
    '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="16.2" y1="16.2" x2="21" y2="21"/></svg>' +
    '<span class="co-empty-title">ไม่พบกลุ่มตัวอย่างที่ตรงกับเงื่อนไข</span></div>';

  function render() {
    var info = infoAll();
    var list = filtered(info);
    var pageCount = Math.max(1, Math.ceil(list.length / state.pageSize));
    if (state.page > pageCount) state.page = pageCount;
    var start = (state.page - 1) * state.pageSize;
    var pageRows = list.slice(start, start + state.pageSize);

    $('co-rows').innerHTML = pageRows.length ? pageRows.map(rowHtml).join('') : EMPTY_HTML;
    $('co-foot').innerHTML = '<span class="co-foot-text">แสดง ' + list.length + ' จาก ' + info.length +
      ' คน</span><div id="co-pagination"></div>';

    window.TFC.renderPagination('co-pagination', {
      page: state.page, pageSize: state.pageSize, total: list.length, pageSizeOptions: PAGE_SIZES,
      onChange: function (p) { state.page = p; render(); },
      onPageSizeChange: function (s) { state.pageSize = s; state.page = 1; render(); }
    });

    flipMenu();
    renderStats();
    syncSearch();
  }

  /* เมนูของแถวท้ายๆ ถูกกรอบตารางที่มี overflow ตัด จึงพลิกขึ้นบนเมื่อที่ไม่พอ */
  function flipMenu() {
    var menu = document.querySelector('.co-menu');
    var scroller = document.querySelector('.co-table-scroll');
    if (!menu || !scroller) return;
    menu.classList.remove('is-up');
    if (menu.getBoundingClientRect().bottom > scroller.getBoundingClientRect().bottom) menu.classList.add('is-up');
  }

  /* ================= ฟอร์มเพิ่มกลุ่มตัวอย่าง ================= */
  var form = {
    pid: '', name: '', phone: '',
    gender: C.GENDERS[0], age: C.AGES[1], job: C.JOBS[0],
    area: C.AREAS[1], target: C.TARGETS[1], source: C.SOURCES[0],
    base: '', status: C.PERSON_STATUSES[0],
    /* เก็บเป็น id ของรอบ ไม่ใช่จำนวนเดือน — ชุดรอบมาจาก master data จึงเปลี่ยนได้ตลอด */
    rounds: C.ROUNDS.map(function (r) { return r.id; }), dueEdit: {}, editing: null,
    consent: false, linkCopied: false
  };

  function selectHtml(id, options, value) {
    return '<select class="select" id="' + id + '">' + options.map(function (o) {
      return '<option value="' + esc(o) + '"' + (o === value ? ' selected' : '') + '>' + esc(o) + '</option>';
    }).join('') + '</select>';
  }

  function fieldHtml(label, req, control) {
    return '<label class="co-field">' +
      '<span class="co-field-label">' + esc(label) + (req ? '<span class="form-required">*</span>' : '') + '</span>' +
      control + '</label>';
  }

  function renderForm() {
    $('co-form-grid').innerHTML =
      /* แถว 1 */
      '<label class="co-field">' +
        '<span class="co-field-label">รหัสบุคคล</span>' +
        '<span class="co-pid-input">' +
          '<input type="text" class="input" id="co-f-pid" value="' + esc(form.pid) + '" placeholder="กดปุ่มเพื่อรันเลข" disabled>' +
          '<button type="button" class="co-pid-btn" id="co-gen-pid">รันเลข</button>' +
        '</span></label>' +
      fieldHtml('ชื่อ–นามสกุล', true, '<input type="text" class="input" id="co-f-name" value="' + esc(form.name) + '" placeholder="ชื่อ นามสกุล">') +
      fieldHtml('เบอร์โทร', true, '<input type="tel" class="input" id="co-f-phone" value="' + esc(form.phone) + '" placeholder="08x-xxx-xxxx">') +
      /* แถว 2 */
      fieldHtml('เพศ', true, selectHtml('co-f-gender', C.GENDERS, form.gender)) +
      fieldHtml('ช่วงอายุ', false, selectHtml('co-f-age', C.AGES, form.age)) +
      fieldHtml('อาชีพ', false, selectHtml('co-f-job', C.JOBS, form.job)) +
      /* แถว 3 */
      fieldHtml('พื้นที่', true, selectHtml('co-f-area', C.AREAS, form.area)) +
      fieldHtml('กลุ่มเป้าหมาย', true, selectHtml('co-f-target', C.TARGETS, form.target)) +
      fieldHtml('แหล่งที่มา', true, selectHtml('co-f-source', C.SOURCES, form.source)) +
      /* แถว 4 */
      fieldHtml('วันที่เข้ากลุ่มตัวอย่าง', true, '<input type="date" class="input" id="co-f-base" value="' + esc(form.base) + '" lang="th-TH">') +
      fieldHtml('สถานะ', false, selectHtml('co-f-status', C.PERSON_STATUSES, form.status)) +
      '<label class="co-field">' +
        '<span class="co-field-label">ลิงก์แบบประเมิน</span>' +
        '<button type="button" class="co-copy-btn' + (form.linkCopied ? ' is-done' : '') + '" id="co-copy-link"' +
          (form.pid ? '' : ' disabled') + '>' +
          (form.linkCopied ? 'คัดลอกลิงก์แล้ว' : 'คัดลอกลิงก์แบบประเมิน') + '</button>' +
      '</label>';
  }

  function renderRoundChips() {
    $('co-round-chips').innerHTML = C.ROUNDS.map(function (r) {
      var on = form.rounds.indexOf(r.id) > -1;
      return '<button type="button" class="co-chip' + (on ? ' is-on' : '') + '" data-round-chip="' + esc(r.id) + '">' +
        '<span class="co-chip-mark">' +
          '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>' +
        '</span>' + esc(r.name) + '</button>';
    }).join('');
  }

  /* ตารางย่อยแสดงวันครบกำหนดที่คำนวณจากวันที่เข้ากลุ่ม แก้ทับได้รายรอบ
     เรียงตามจำนวนวัน ไม่ใช่ตามลำดับที่กด เพื่อให้อ่านเป็นไทม์ไลน์เสมอ */
  function renderDueTable() {
    var rounds = C.ROUNDS.filter(function (r) { return form.rounds.indexOf(r.id) > -1; })
      .slice().sort(function (a, b) { return a.offsetDays - b.offsetDays; });

    if (!rounds.length || !form.base) {
      $('co-due-table').innerHTML = '';
      return;
    }

    $('co-due-table').innerHTML =
      '<div class="co-due-row co-due-head"><div>รอบ</div><div>ครบกำหนดติดตาม</div><div></div></div>' +
      rounds.map(function (r) {
        var due = form.dueEdit[r.id] || C.addDays(form.base, r.offsetDays);
        var editing = form.editing === r.id;
        return '<div class="co-due-row">' +
          '<div class="co-due-name">' + esc(r.name) + '</div>' +
          '<div>' + (editing
            ? '<input type="date" class="input co-due-input" value="' + esc(due) + '" data-due-input="' + esc(r.id) + '" lang="th-TH">'
            : '<span class="co-due-date">' + esc(C.fmt(due)) + '</span>') + '</div>' +
          '<div class="co-due-action">' +
            '<button type="button" class="co-icon-btn" data-due-edit="' + esc(r.id) + '" aria-label="' +
              (editing ? 'บันทึกวันที่' : 'แก้วันครบกำหนด') + '">' +
              (editing
                ? '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>'
                : '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4l10-10-4-4L4 16v4Z"/><path d="M13.5 6.5l4 4"/></svg>') +
            '</button>' +
          '</div></div>';
      }).join('');
  }

  function formValid() {
    return !!(form.pid && form.name.trim() && form.phone.trim() && form.base &&
      form.rounds.length && form.consent);
  }

  function syncForm() {
    $('co-consent-file').hidden = !form.consent;
    $('co-save').disabled = !formValid();
  }

  function renderAddModal() {
    renderForm();
    renderRoundChips();
    renderDueTable();
    syncForm();
  }

  /* ================= เหตุการณ์ ================= */
  document.addEventListener('click', function (e) {
    var t = e.target;

    var tab = t.closest('[data-tab]');
    if (tab) { state.tab = tab.getAttribute('data-tab'); state.page = 1; state.menu = null; renderTabs(); return render(); }

    if (t.closest('#co-search-btn')) { state.searchOpen = !state.searchOpen; return syncSearch(); }

    if (t.closest('#co-clear')) {
      state.q = ''; state.round = 'ทุกรอบ'; state.area = 'ทุกพื้นที่';
      $('co-q').value = ''; $('co-round').value = 'ทุกรอบ'; $('co-area').value = 'ทุกพื้นที่';
      state.page = 1;
      return render();
    }

    if (t.closest('#co-apply')) {
      state.q = $('co-q').value;
      state.round = $('co-round').value;
      state.area = $('co-area').value;
      state.page = 1;
      state.searchOpen = false;
      return render();
    }

    var menu = t.closest('[data-menu]');
    if (menu) {
      var mid = menu.getAttribute('data-menu');
      state.menu = state.menu === mid ? null : mid;
      return render();
    }

    var notify = t.closest('[data-notify]');
    if (notify) {
      if (notify.disabled) return;
      var nm = C.byId(notify.getAttribute('data-notify'));
      state.menu = null;
      render();
      if (nm) location.href = 'detail.html?id=' + encodeURIComponent(nm.id) + '&notify=1';
      return;
    }

    var stop = t.closest('[data-stop]');
    if (stop) {
      var sid = stop.getAttribute('data-stop');
      if (C.stopped[sid]) delete C.stopped[sid]; else C.stopped[sid] = true;
      state.menu = null;
      render();
      var sm = C.byId(sid);
      if (sm && window.TFC.showToast) {
        window.TFC.showToast(C.stopped[sid] ? 'ยุติการติดตาม ' + sm.name : 'กลับมาติดตาม ' + sm.name + ' ต่อ', 'info');
      }
      return;
    }

    /* ---- ฟอร์ม ---- */
    if (t.closest('#co-gen-pid')) {
      var max = C.members().reduce(function (n, m) { return Math.max(n, Number(m.pid.slice(4))); }, 0);
      form.pid = 'PID-' + String(max + 1).padStart(4, '0');
      form.linkCopied = false;
      return renderAddModal();
    }

    if (t.closest('#co-copy-link')) {
      if (!form.pid) return;
      var url = 'https://' + C.surveyLink({ pid: form.pid }, 'r0');
      if (navigator.clipboard) navigator.clipboard.writeText(url);
      form.linkCopied = true;
      return renderAddModal();
    }

    var chip = t.closest('[data-round-chip]');
    if (chip) {
      var rid = chip.getAttribute('data-round-chip');
      var i = form.rounds.indexOf(rid);
      if (i > -1) form.rounds.splice(i, 1); else form.rounds.push(rid);
      return renderAddModal();
    }

    var dueBtn = t.closest('[data-due-edit]');
    if (dueBtn) {
      var rn = dueBtn.getAttribute('data-due-edit');
      if (form.editing === rn) {
        var input = document.querySelector('[data-due-input="' + rn + '"]');
        if (input && input.value) form.dueEdit[rn] = input.value;
        form.editing = null;
      } else {
        form.editing = rn;
      }
      return renderDueTable();
    }

    /* clipboard ล้มเหลวได้จริง (ไม่ใช่ https หรือผู้ใช้ไม่อนุญาต) จึงรอผลก่อนค่อยบอกว่าสำเร็จ */
    var savedCopy = t.closest('#co-saved-copy') || t.closest('#co-bind-copy');
    if (savedCopy) {
      var link = (savedCopy.id === 'co-bind-copy' ? $('co-bind-link') : $('co-saved-link')).textContent;
      var copyFail = function () {
        if (window.TFC.showToast) window.TFC.showToast('คัดลอกไม่สำเร็จ — ลิงก์คือ ' + link, 'warning');
      };
      if (!navigator.clipboard) return copyFail();
      navigator.clipboard.writeText('https://' + link)
        .then(function () { savedCopy.textContent = 'คัดลอกแล้ว'; }, copyFail);
      return;
    }

    if (state.menu && !t.closest('.co-actions')) { state.menu = null; render(); }
    if (state.searchOpen && !t.closest('.co-search')) { state.searchOpen = false; syncSearch(); }
  });

  document.addEventListener('input', function (e) {
    var id = e.target.id;
    if (id === 'co-f-name') form.name = e.target.value;
    else if (id === 'co-f-phone') form.phone = e.target.value;
    else if (id === 'co-f-base') { form.base = e.target.value; renderDueTable(); }
    else return;
    syncForm();
  });

  document.addEventListener('change', function (e) {
    var id = e.target.id;
    var map = { 'co-f-gender': 'gender', 'co-f-age': 'age', 'co-f-job': 'job',
      'co-f-area': 'area', 'co-f-target': 'target', 'co-f-source': 'source', 'co-f-status': 'status' };
    if (map[id]) { form[map[id]] = e.target.value; return; }
    if (id === 'co-consent') { form.consent = e.target.checked; syncForm(); }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (state.menu) { state.menu = null; render(); }
    if (state.searchOpen) { state.searchOpen = false; syncSearch(); }
  });

  $('co-add-form').addEventListener('submit', function (e) {
    e.preventDefault();
    if (!formValid()) return;

    /* รอบที่ติ๊กไว้ถูกแปลงเป็นวันครบกำหนดรายคนทันที ไม่ได้อ้างรอบกลาง
       snapshot จำนวนวันลงระเบียนของคนนี้เลย ถ้าแอดมินไปแก้ค่าในหน้าตั้งค่าทีหลัง
       กำหนดของคนนี้ต้องไม่ขยับตาม — ของจริงคือแถวในตาราง follow_up_rounds ของแต่ละคน */
    var member = {
      id: 'm' + (C.members().length + 1), pid: form.pid, name: form.name.trim(), phone: form.phone.trim(),
      gender: form.gender, age: form.age, job: form.job, source: form.source,
      area: form.area, target: form.target, line: false, consent: true,
      base: form.base, ans: {},
      rounds: C.ROUNDS.filter(function (r) { return form.rounds.indexOf(r.id) > -1; })
        .map(function (r) {
          /* วันที่ที่แอดมินแก้ทับรายรอบ ต้องเก็บเป็น offset ของคนนี้ ไม่ใช่ทิ้งไป */
          var due = form.dueEdit[r.id];
          return {
            id: r.id, templateId: r.templateId, name: r.name, short: r.short,
            offsetDays: due ? C.daysBetween(form.base, due) : r.offsetDays
          };
        })
    };
    C.members().push(member);

    window.TFC.closeModal('co-add-modal');
    $('co-saved-name').textContent = member.name + ' · ' + member.pid;
    $('co-saved-link').textContent = C.surveyLink(member, 'r0');
    $('co-saved-copy').textContent = 'คัดลอกลิงก์';
    $('co-bind-link').textContent = C.LINE_LINK_URL;
    $('co-bind-copy').textContent = 'คัดลอกลิงก์';
    window.TFC.openModal('co-saved-modal');

    form.pid = ''; form.name = ''; form.phone = ''; form.base = '';
    form.consent = false; form.linkCopied = false; form.dueEdit = {};
    $('co-consent').checked = false;
    renderAddModal();
    render();
  });

  $('co-export').addEventListener('click', function () {
    var rows = filtered(infoAll()).map(function (i) {
      return [i.m.pid, i.m.name, i.m.phone, i.m.area, i.m.target, i.m.source,
        C.fmt(i.m.base), i.next ? i.next.name : '', i.next ? C.fmt(i.next.due) : '', i.status];
    });
    window.TFC.exportCsv('กลุ่มตัวอย่าง.csv',
      ['รหัสบุคคล', 'ชื่อ-นามสกุล', 'เบอร์โทร', 'พื้นที่', 'กลุ่มเป้าหมาย', 'แหล่งที่มา',
       'วันที่เข้ากลุ่ม', 'รอบถัดไป', 'ครบกำหนด', 'สถานะ'], rows);
  });

  /* ---------- เริ่มต้น ---------- */
  fillFilters();
  renderTabs();
  renderLegend();
  renderAddModal();
  render();
})();
