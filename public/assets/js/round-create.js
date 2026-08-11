/* TheFarmConcept — หน้าสร้างรอบติดตาม (admin/evaluations/round-create.html)

   การค้นหารายชื่อและการแบ่งหน้าจำลองการทำงานฝั่ง server:
   ทุกครั้งที่เปลี่ยนหน้าจะเรียก queryPool ใหม่พร้อม page/pageSize
   frontend ไม่เคยถือรายชื่อทั้งหมด — ถือแค่ "รายการที่ติ๊กไว้" เป็นชุด pid
   ตัวเลือกจึงคงอยู่เมื่อเปลี่ยนหน้า */
(function () {
  var R = window.TFC.rounds;
  var C = window.TFC.cohort;
  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  var PAGE_SIZES = [10, 20, 50];

  var form = {
    name: '', formName: R.FORMS[0], from: '', to: '',
    targets: [], msg: R.DEFAULT_MSG
  };

  var search = {
    done: false, page: 1, pageSize: PAGE_SIZES[0],
    total: 0, rows: [], allPids: [], notifiablePids: []
  };

  /* เก็บเฉพาะ pid ที่ติ๊กไว้ ไม่ได้เก็บทั้งแถว ข้อมูลจึงไม่หายตอนเปลี่ยนหน้า */
  var picked = {};

  function pickedCount() { return Object.keys(picked).filter(function (k) { return picked[k]; }).length; }

  /* ---------- ฟอร์มด้านบน ---------- */
  function renderForm() {
    function field(label, req, control) {
      return '<label class="co-field">' +
        '<span class="co-field-label">' + esc(label) + (req ? '<span class="form-required">*</span>' : '') + '</span>' +
        control + '</label>';
    }

    $('rc-form').innerHTML =
      field('ชื่อรอบติดตาม', true, '<input type="text" class="input" id="rc-name" value="' + esc(form.name) +
        '" placeholder="เช่น ติดตามกลุ่มตัวอย่าง ก.ย. 2569">') +
      field('แบบประเมินที่ใช้', true, '<select class="select" id="rc-form-name">' + R.FORMS.map(function (f) {
        return '<option value="' + esc(f) + '"' + (form.formName === f ? ' selected' : '') + '>' + esc(f) + '</option>';
      }).join('') + '</select>') +
      field('ครบกำหนดตั้งแต่', true, '<input type="date" class="input" id="rc-from" value="' + esc(form.from) + '" lang="th-TH">') +
      field('ถึงวันที่', true, '<input type="date" class="input" id="rc-to" value="' + esc(form.to) + '" lang="th-TH">');
  }

  function renderTargets() {
    $('rc-targets').innerHTML = R.TARGETS.map(function (t) {
      var on = form.targets.indexOf(t) > -1;
      return '<button type="button" class="co-chip' + (on ? ' is-on' : '') + '" data-target="' + esc(t) + '">' +
        '<span class="co-chip-mark">' +
          '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>' +
        '</span>' + esc(t) + '</button>';
    }).join('');
  }

  /* ---------- ค้นหา / ผลลัพธ์ ---------- */
  function runSearch(page) {
    var res = R.queryPool({
      targets: form.targets, from: form.from, to: form.to,
      page: page || 1, pageSize: search.pageSize
    });
    search.done = true;
    search.page = page || 1;
    search.total = res.total;
    search.rows = res.rows;
    search.allPids = res.allPids;
    search.notifiablePids = res.notifiablePids;
  }

  var MEMBER_CLASS = {
    'ตอบแล้ว': 'is-done', 'รอติดตาม': 'is-due',
    'เกินกำหนด': 'is-over', 'ยังไม่ถึงกำหนด': 'is-idle'
  };

  function renderResult() {
    if (!search.done) {
      $('rc-result').innerHTML =
        '<div class="fb-guide">' +
          '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="16.2" y1="16.2" x2="21" y2="21"/></svg>' +
          '<span class="fb-guide-title">เลือกกลุ่มเป้าหมายและช่วงวันครบกำหนด แล้วกด “ค้นหารายชื่อ”</span>' +
          '<span class="fb-guide-text">ระบบจะดึงเฉพาะคนที่ครบกำหนดติดตามในช่วงที่ระบุ</span>' +
        '</div>';
      return;
    }

    /* เลือกทั้งหมด = ทุกคนที่แจ้งเตือนได้ในผลค้นหา ไม่ใช่แค่หน้านี้ */
    var allPicked = search.notifiablePids.length > 0 &&
      search.notifiablePids.every(function (pid) { return picked[pid]; });

    var start = (search.page - 1) * search.pageSize;

    var rows = search.rows.map(function (p, i) {
      var on = !!picked[p.pid];
      return '<div class="fb-tr">' +
        '<div class="fb-check-cell">' +
          '<input type="checkbox" data-pick="' + esc(p.pid) + '"' + (on ? ' checked' : '') +
            (p.line ? '' : ' disabled') + ' aria-label="เลือก ' + esc(p.name) + '">' +
        '</div>' +
        '<div class="fb-cell fb-nums fb-no">' + (start + i + 1) + '</div>' +
        '<div class="fb-name-cell">' +
          '<span class="fb-name">' + esc(p.name) + '</span>' +
          '<span class="fb-pid">' + esc(p.pid) + '</span>' +
        '</div>' +
        '<div class="fb-cell fb-nums">' + esc(p.phone) + '</div>' +
        '<div class="fb-cell">' + esc(p.target) + '</div>' +
        '<div class="fb-cell">ติดตาม ' + esc(p.round) + '</div>' +
        '<div class="fb-cell fb-nums">' + esc(C.fmt(p.due)) + '</div>' +
        '<div><span class="cd-badge ' + MEMBER_CLASS[p.state] + '">' + esc(p.state) + '</span></div>' +
        '<div>' + (p.line
          ? '<span class="cd-badge is-good">LINE</span>'
          : '<span class="cd-badge is-warn">ยังไม่ผูก LINE</span>') + '</div>' +
        '</div>';
    }).join('');

    var pageCount = Math.max(1, Math.ceil(search.total / search.pageSize));

    $('rc-result').innerHTML =
      '<div class="fb-result-bar">' +
        '<span class="fb-result-count">เลือกแล้ว ' + pickedCount() + ' จาก ' + search.total + ' คน</span>' +
        '<div class="fb-result-actions">' +
          '<button type="button" class="cd-mini-btn" id="rc-toggle-all">' +
            (allPicked ? 'ยกเลิกทั้งหมด' : 'เลือกทั้งหมด') + '</button>' +
          '<button type="button" class="cd-mini-btn" id="rc-export">ส่งออก Excel</button>' +
        '</div>' +
      '</div>' +

      '<div class="card fb-table-card">' +
        '<div class="fb-table-scroll"><div class="fb-table fb-pick-table">' +
          '<div class="fb-tr fb-th">' +
            '<div></div><div>#</div><div>ชื่อ / รหัส</div><div>เบอร์โทร</div><div>กลุ่มเป้าหมาย</div>' +
            '<div>รอบที่ติดตาม</div><div>ครบกำหนด</div><div>สถานะติดตาม</div><div>ช่องทางแจ้งเตือน</div>' +
          '</div>' +
          (rows || '<div class="fb-empty"><span class="fb-empty-title">ไม่พบคนที่ครบกำหนดในช่วงที่ระบุ</span></div>') +
        '</div></div>' +

        '<div class="fb-pager">' +
          '<span class="fb-pager-text">แสดง ' + (search.total ? start + 1 : 0) + '–' +
            (start + search.rows.length) + ' จาก ' + search.total + ' คน</span>' +
          '<div class="fb-pager-controls">' +
            '<select class="select fb-pager-size" id="rc-page-size">' + PAGE_SIZES.map(function (s) {
              return '<option value="' + s + '"' + (search.pageSize === s ? ' selected' : '') + '>' + s + ' รายการ</option>';
            }).join('') + '</select>' +
            '<button type="button" class="fb-pager-btn" data-page="' + (search.page - 1) + '"' +
              (search.page <= 1 ? ' disabled' : '') + ' aria-label="หน้าก่อนหน้า">' +
              '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 6l-6 6 6 6"/></svg></button>' +
            Array.from({ length: pageCount }, function (_, i) {
              return '<button type="button" class="fb-pager-num' + (search.page === i + 1 ? ' is-on' : '') +
                '" data-page="' + (i + 1) + '">' + (i + 1) + '</button>';
            }).join('') +
            '<button type="button" class="fb-pager-btn" data-page="' + (search.page + 1) + '"' +
              (search.page >= pageCount ? ' disabled' : '') + ' aria-label="หน้าถัดไป">' +
              '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 6l6 6-6 6"/></svg></button>' +
          '</div>' +
        '</div>' +
      '</div>';
  }

  /* ---------- ข้อความแจ้งเตือน ---------- */
  function sampleMember() {
    return search.rows[0] || { name: 'สมชาย ใจดี', round: '3 เดือน', due: '2026-08-12' };
  }

  function renderMsg() {
    $('rc-msg').value = form.msg;
    $('rc-bubble').textContent = R.fillMsg(form.msg, sampleMember());
  }

  /* ---------- แถบล่าง ---------- */
  function renderBottom() {
    var n = pickedCount();
    var unreachable = search.done ? search.allPids.length - search.notifiablePids.length : 0;

    $('rc-summary').textContent = !search.done
      ? 'ยังไม่ได้ค้นหารายชื่อ'
      : 'จะส่งแจ้งเตือน ' + n + ' คน' + (unreachable > 0 ? ' · อีก ' + unreachable + ' คนต้องติดตามเอง' : '');

    /* ต้องมีชื่อรอบ + ค้นหาแล้ว + มีคนถูกเลือก จึงจะกดสร้างได้ */
    $('rc-submit').disabled = !(form.name.trim() && search.done && n > 0);
  }

  function renderAll() {
    renderResult();
    renderMsg();
    renderBottom();
  }

  /* ---------- เหตุการณ์ ---------- */
  document.addEventListener('click', function (e) {
    var t = e.target;

    var tg = t.closest('[data-target]');
    if (tg) {
      var v = tg.getAttribute('data-target');
      var i = form.targets.indexOf(v);
      if (i > -1) form.targets.splice(i, 1); else form.targets.push(v);
      return renderTargets();
    }

    if (t.closest('#rc-search')) {
      runSearch(1);
      return renderAll();
    }

    if (t.closest('#rc-toggle-all')) {
      var allPicked = search.notifiablePids.length > 0 &&
        search.notifiablePids.every(function (pid) { return picked[pid]; });
      search.notifiablePids.forEach(function (pid) { picked[pid] = !allPicked; });
      return renderAll();
    }

    if (t.closest('#rc-export')) {
      var rows = search.rows.map(function (p, i) {
        return [i + 1, p.pid, p.name, p.phone, p.target, 'ติดตาม ' + p.round,
          C.fmt(p.due), p.state, p.line ? 'LINE' : 'ยังไม่ผูก LINE'];
      });
      window.TFC.exportCsv('รายชื่อรอบติดตาม.csv',
        ['#', 'รหัสบุคคล', 'ชื่อ-นามสกุล', 'เบอร์โทร', 'กลุ่มเป้าหมาย', 'รอบที่ติดตาม',
         'ครบกำหนด', 'สถานะติดตาม', 'ช่องทางแจ้งเตือน'], rows);
      return;
    }

    var pg = t.closest('[data-page]');
    if (pg) {
      if (pg.disabled) return;
      var p = Number(pg.getAttribute('data-page'));
      if (p < 1) return;
      runSearch(p);
      return renderAll();
    }

    if (t.closest('#rc-msg-reset')) { form.msg = R.DEFAULT_MSG; return renderMsg(); }

    if (t.closest('#rc-draft')) {
      if (window.TFC.showToast) window.TFC.showToast('บันทึกรอบติดตามเป็นฉบับร่างแล้ว', 'success');
      return;
    }

    if (t.closest('#rc-submit')) {
      if ($('rc-submit').disabled) return;
      var pids = Object.keys(picked).filter(function (k) { return picked[k]; });
      var out = R.createBatch({
        name: form.name.trim(), from: form.from, to: form.to,
        form: form.formName, state: 'กำลังดำเนินการ'
      }, pids);

      if (window.TFC.showToast) {
        window.TFC.showToast('สร้างรอบติดตามแล้ว · ส่งสำเร็จ ' + out.sent + ' คน' +
          (out.failed ? ' · ส่งไม่ได้ ' + out.failed + ' คน' : ''), 'success');
      }
      setTimeout(function () { location.href = 'round-detail.html?id=' + encodeURIComponent(out.id); }, 600);
      return;
    }
  });

  document.addEventListener('change', function (e) {
    var pick = e.target.closest('[data-pick]');
    if (pick) {
      picked[pick.getAttribute('data-pick')] = pick.checked;
      return renderAll();
    }
    if (e.target.id === 'rc-page-size') {
      search.pageSize = Number(e.target.value);
      runSearch(1);
      return renderAll();
    }
    if (e.target.id === 'rc-form-name') form.formName = e.target.value;
  });

  document.addEventListener('input', function (e) {
    var id = e.target.id;
    if (id === 'rc-name') { form.name = e.target.value; return renderBottom(); }
    if (id === 'rc-from') { form.from = e.target.value; return; }
    if (id === 'rc-to') { form.to = e.target.value; return; }
    if (id === 'rc-msg') { form.msg = e.target.value; $('rc-bubble').textContent = R.fillMsg(form.msg, sampleMember()); }
  });

  /* ---------- เริ่มต้น ---------- */
  renderForm();
  renderTargets();
  renderAll();
})();
