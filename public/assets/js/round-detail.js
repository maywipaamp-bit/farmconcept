/* TheFarmConcept — หน้ารายละเอียดรอบติดตาม (admin/evaluations/round-detail.html)

   แยกคนสองกลุ่มเสมอ:
   - แจ้งเตือนได้ (ผูก LINE) → ระบบส่งให้ ดูสถานะการส่ง/การตอบได้
   - แจ้งเตือนไม่ได้ → ระบบส่งไม่ได้ ต้องให้แอดมินติดตามนอกระบบและบันทึกผลไว้ */
(function () {
  var R = window.TFC.rounds;
  var C = window.TFC.cohort;
  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  var params = new URLSearchParams(location.search);
  var batch = R.byId(params.get('id')) || R.batches()[0];

  var GROUPS = ['แจ้งเตือนได้ (LINE)', 'แจ้งเตือนไม่ได้ (ติดตามนอกระบบ)'];
  var state = { group: GROUPS[0], editPid: null, lineCopied: false };

  var STATE_CLASS = {
    'กำลังดำเนินการ': 'is-active', 'รอเริ่ม': 'is-waiting',
    'เสร็จสิ้น': 'is-done', 'ยกเลิกแล้ว': 'is-cancelled'
  };
  var MEMBER_CLASS = {
    'ตอบแล้ว': 'is-done', 'รอติดตาม': 'is-due',
    'เกินกำหนด': 'is-over', 'ยังไม่ถึงกำหนด': 'is-idle'
  };

  function notifiable() { return R.membersOf(batch.id).filter(function (p) { return p.line; }); }
  function unreachable() { return R.membersOf(batch.id).filter(function (p) { return !p.line; }); }

  function renderHeader() {
    $('rd-crumb').textContent = batch.name;
    document.title = batch.name + ' | TheFarmConcept';

    $('rd-header').innerHTML =
      '<div class="fb-detail-main">' +
        '<a class="cd-back" href="rounds.html" aria-label="ย้อนกลับไปรายการรอบติดตาม">' +
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 6l-6 6 6 6"/></svg>' +
        '</a>' +
        '<div class="fb-detail-text">' +
          '<div class="fb-title-line">' +
            '<h1 class="fb-title">' + esc(batch.name) + '</h1>' +
            '<span class="fb-state ' + STATE_CLASS[batch.state] + '">' + esc(batch.state) + '</span>' +
          '</div>' +
          '<div class="cd-meta">' +
            '<span>ครบกำหนด ' + esc(C.fmt(batch.from)) + ' – ' + esc(C.fmt(batch.to)) + '</span>' +
            '<span>' + esc(batch.form) + '</span>' +
          '</div>' +
        '</div>' +
      '</div>';
  }

  function renderGroups() {
    var n = notifiable().length, u = unreachable().length;
    $('rd-groups').innerHTML = GROUPS.map(function (g, i) {
      var on = state.group === g;
      return '<button type="button" class="cd-seg' + (on ? ' is-on' : '') + '" data-group="' + esc(g) + '">' +
        esc(g) + ' · ' + (i === 0 ? n : u) + '</button>';
    }).join('');
  }

  /* ---------- กลุ่มที่แจ้งเตือนได้ ---------- */
  function renderNotifiable() {
    var list = notifiable();
    $('rd-count').textContent = 'ส่งแจ้งเตือนได้ ' + list.length + ' คน · ตอบแล้ว ' +
      list.filter(function (p) { return p.answered; }).length + ' คน';

    var rows = list.map(function (p) {
      return '<div class="fb-tr">' +
        '<div class="fb-name-cell">' +
          '<a class="fb-name" href="../cohort/detail.html?id=' + esc((C.byPid(p.pid) || {}).id || '') + '">' + esc(p.name) + '</a>' +
          '<span class="fb-pid">' + esc(p.pid) + '</span>' +
        '</div>' +
        '<div class="fb-cell fb-nums">' + esc(p.phone) + '</div>' +
        '<div class="fb-cell">' + esc(p.area) + '</div>' +
        '<div class="fb-cell">ติดตาม ' + esc(p.round) + '</div>' +
        '<div class="fb-cell fb-nums">' + esc(C.fmt(p.due)) + '</div>' +
        '<div><span class="cd-badge is-good">ส่งแล้ว</span></div>' +
        '<div><span class="cd-badge ' + MEMBER_CLASS[p.state] + '">' + esc(p.state) + '</span></div>' +
        '</div>';
    }).join('');

    return '<div class="card fb-table-card">' +
      '<div class="fb-table-scroll"><div class="fb-table fb-member-table">' +
        '<div class="fb-tr fb-th">' +
          '<div>ชื่อ / รหัส</div><div>เบอร์โทร</div><div>พื้นที่</div>' +
          '<div>รอบที่ติดตาม</div><div>ครบกำหนด</div><div>การส่ง</div><div>สถานะ</div>' +
        '</div>' + (rows || emptyHtml('ไม่มีคนที่แจ้งเตือนได้ในรอบนี้')) +
      '</div></div></div>';
  }

  /* ---------- กลุ่มที่แจ้งเตือนไม่ได้ ---------- */
  function renderUnreachable() {
    var list = unreachable();
    var logged = list.filter(function (p) { return R.offlineLog(batch.id, p.pid); }).length;
    $('rd-count').textContent = 'ต้องติดตามเอง ' + list.length + ' คน · บันทึกผลแล้ว ' + logged + ' คน';

    var rows = list.map(function (p) {
      var log = R.offlineLog(batch.id, p.pid);
      var editing = state.editPid === p.pid;

      return '<div class="fb-tr">' +
        '<div class="fb-name-cell">' +
          '<a class="fb-name" href="../cohort/detail.html?id=' + esc((C.byPid(p.pid) || {}).id || '') + '">' + esc(p.name) + '</a>' +
          '<span class="fb-pid">' + esc(p.pid) + '</span>' +
        '</div>' +
        '<div class="fb-cell fb-nums">' + esc(p.phone) + '</div>' +
        '<div class="fb-cell">ติดตาม ' + esc(p.round) + '</div>' +
        '<div class="fb-cell fb-nums">' + esc(C.fmt(p.due)) + '</div>' +
        '<div><span class="cd-badge ' + MEMBER_CLASS[p.state] + '">' + esc(p.state) + '</span></div>' +
        '<div class="fb-offline">' +
          (editing
            ? '<select class="select fb-offline-kind" data-kind="' + esc(p.pid) + '">' +
                R.NOTE_KINDS.map(function (k) { return '<option value="' + esc(k) + '">' + esc(k) + '</option>'; }).join('') +
              '</select>' +
              '<input type="text" class="input fb-offline-note" data-note="' + esc(p.pid) +
                '" placeholder="ผลการติดตาม" value="' + esc(log ? log.note : '') + '">' +
              '<button type="button" class="btn btn-primary btn-sm" data-save="' + esc(p.pid) + '">บันทึก</button>' +
              '<button type="button" class="btn btn-outline btn-sm" data-cancel-edit="1">ยกเลิก</button>'
            : (log
                ? '<span class="fb-offline-done">' + esc(log.kind) + ' · ' + esc(log.note) + '</span>' +
                  '<span class="fb-offline-by">' + esc(C.fmt(log.at)) + ' ' + esc(log.time) + ' · ' + esc(log.by) + '</span>' +
                  '<button type="button" class="cd-mini-btn" data-edit="' + esc(p.pid) + '">แก้ไข</button>'
                : '<button type="button" class="cd-mini-btn" data-edit="' + esc(p.pid) + '">บันทึกผลติดตาม</button>')) +
        '</div>' +
        '</div>';
    }).join('');

    /* ทางแก้ระยะยาวของกลุ่มนี้คือให้เขาผูก LINE ไม่ใช่ให้แอดมินโทรตามทุกรอบ
       ลิงก์จึงต้องอยู่ตรงนี้ ไม่ใช่ให้ไปหาเองในหน้าอื่น */
    return '<div class="fb-hint fb-hint-stack">' +
      '<span>คนกลุ่มนี้ยังไม่ผูก LINE ระบบจึงส่งแจ้งเตือนให้ไม่ได้ — ชวนให้ผูก LINE ด้วยลิงก์ข้างล่าง หรือติดตามเองแล้วบันทึกผลไว้ที่นี่</span>' +
      '<div class="copy-link">' +
        '<code class="copy-link-url">' + esc(C.LINE_LINK_URL) + '</code>' +
        '<button type="button" class="copy-link-btn" id="rd-line-copy">' +
          '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h8"/></svg>' +
          (state.lineCopied ? 'คัดลอกแล้ว' : 'คัดลอกลิงก์ผูก LINE') +
        '</button>' +
      '</div>' +
      '</div>' +
      '<div class="card fb-table-card">' +
      '<div class="fb-table-scroll"><div class="fb-table fb-offline-table">' +
        '<div class="fb-tr fb-th">' +
          '<div>ชื่อ / รหัส</div><div>เบอร์โทร</div><div>รอบที่ติดตาม</div>' +
          '<div>ครบกำหนด</div><div>สถานะ</div><div>ผลการติดตามนอกระบบ</div>' +
        '</div>' + (rows || emptyHtml('ทุกคนในรอบนี้ผูก LINE แล้ว')) +
      '</div></div></div>';
  }

  function emptyHtml(text) {
    return '<div class="fb-empty"><span class="fb-empty-title">' + esc(text) + '</span></div>';
  }

  function renderPanel() {
    $('rd-panel').innerHTML = state.group === GROUPS[0] ? renderNotifiable() : renderUnreachable();
  }

  function render() {
    renderHeader();
    renderGroups();
    renderPanel();
  }

  document.addEventListener('click', function (e) {
    var t = e.target;

    var g = t.closest('[data-group]');
    if (g) { state.group = g.getAttribute('data-group'); state.editPid = null; renderGroups(); return renderPanel(); }

    if (t.closest('#rd-line-copy')) {
      /* clipboard ล้มเหลวได้จริง (ไม่ใช่ https หรือผู้ใช้ไม่อนุญาต) จึงรอผลก่อนค่อยบอกว่าสำเร็จ */
      var ok = function () { state.lineCopied = true; renderPanel(); };
      var no = function () {
        if (window.TFC.showToast) window.TFC.showToast('คัดลอกไม่สำเร็จ — ลิงก์คือ ' + C.LINE_LINK_URL, 'warning');
      };
      if (!navigator.clipboard) return no();
      navigator.clipboard.writeText('https://' + C.LINE_LINK_URL).then(ok, no);
      return;
    }

    var ed = t.closest('[data-edit]');
    if (ed) { state.editPid = ed.getAttribute('data-edit'); return renderPanel(); }

    if (t.closest('[data-cancel-edit]')) { state.editPid = null; return renderPanel(); }

    var save = t.closest('[data-save]');
    if (save) {
      var pid = save.getAttribute('data-save');
      var kind = document.querySelector('[data-kind="' + pid + '"]').value;
      var note = document.querySelector('[data-note="' + pid + '"]').value.trim();
      if (!note) return;
      R.saveOffline(batch.id, pid, kind, note);
      state.editPid = null;
      renderPanel();
      if (window.TFC.showToast) window.TFC.showToast('บันทึกผลการติดตามแล้ว', 'success');
      return;
    }
  });

  render();
})();
