/* TheFarmConcept — หน้าประเมินกิจกรรม (admin/activities/responses)

   ผลแบบประเมินหลังกิจกรรมรายชุด — อ่านอย่างเดียว ไม่มีหน้ารายละเอียด
   ข้อมูลมาจาก MySQL ผ่าน ActivitySatisfactionService ทั้งหมด ไม่มี mock เหลืออยู่

   หน้าเปิดมาพร้อมข้อมูลชุดแรกที่เซิร์ฟเวอร์ใส่มาให้ใน TFC_RESPONSES แล้ว
   เฟรมแรกจึงเป็นตารางจริง ไม่ใช่โครงร่างที่ค่อยสลับเป็นข้อมูล (มาตรฐาน Motion ข้อ 1–2)
   โครงร่างใช้เฉพาะตอนเปลี่ยนกิจกรรม ซึ่งเป็นการโหลดชุดใหม่ทั้งแผง

   ข้อบังคับที่ห้ามหย่อน: ไม่มีชื่อ เบอร์โทร หรือรหัสลงทะเบียนมาถึงหน้านี้
   ตัวระบุเดียวที่มีคือ seq = ลำดับผู้ตอบภายในกิจกรรมนั้น ซึ่งเซิร์ฟเวอร์คิดจาก
   รายการเต็มก่อนกรอง ตัวเลขจึงไม่ขยับตามแท็บหรือหน้าที่เปิดอยู่ */
(function () {
  var cfg = window.TFC_RESPONSES || {};
  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  var PAGE_SIZES = cfg.pageSizes || [10, 20, 50];

  /* แท็บช่วงคะแนน — key ต้องตรงกับ band() ใน ActivitySatisfactionService */
  var BANDS = [
    { key: 'all', label: 'ทั้งหมด' },
    { key: 'praise', label: 'ชื่นชม (4–5)' },
    { key: 'mid', label: 'ปานกลาง (3)' },
    { key: 'improve', label: 'ต้องปรับปรุง (1–2)' }
  ];

  var state = {
    activity: cfg.activity,
    activities: cfg.activities || [],
    summary: cfg.summary,
    band: 'all',
    keyword: '',
    page: 1,
    pageSize: PAGE_SIZES[0],
    pickerOpen: false,
    seq: 0            /* กันผลลัพธ์ที่มาช้ากว่าคำขอถัดไปเขียนทับของใหม่ */
  };

  function get(url, params) {
    var query = new URLSearchParams(params);
    return fetch(url + '?' + query.toString(), {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin'
    }).then(function (res) {
      if (!res.ok) throw new Error('responses api ' + res.status);
      return res.json();
    });
  }

  /* ---------- หัวหน้า + popover เลือกกิจกรรม ---------- */
  function activityMeta(item) {
    return item.startDateLabel + ' · ตอบแล้ว ' + item.responseCount + ' ชุด';
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

  /* ---------- แผงสรุป ----------
     ตัวเลขทุกตัวมาจากคำตอบชุดเดียวกับตาราง ไม่มีค่าสรุปที่เก็บแยกไว้ให้ขัดกันเอง */
  function kpiHtml(label, value, unit, note, tone) {
    return '<div class="ar-kpi">' +
      '<span class="ar-kpi-label">' + esc(label) + '</span>' +
      '<span class="ar-kpi-value' + (tone ? ' is-' + tone : '') + '">' + esc(value) +
        (unit ? '<span class="ar-kpi-unit">' + esc(unit) + '</span>' : '') + '</span>' +
      '<span class="ar-kpi-note">' + esc(note) + '</span>' +
      '</div>';
  }

  function barHtml(label, percent, value, low) {
    return '<div class="ar-bar">' +
      '<div class="ar-bar-head">' +
        '<span class="ar-bar-label">' + esc(label) + '</span>' +
        '<span class="ar-bar-value' + (low ? ' is-low' : '') + '">' + esc(value) + '</span>' +
      '</div>' +
      '<span class="ar-bar-track"><span class="ar-bar-fill' + (low ? ' is-low' : '') +
        '" style="width:' + Math.max(0, Math.min(100, percent)) + '%"></span></span>' +
      '</div>';
  }

  function renderSummary() {
    var s = state.summary;
    var host = $('ar-summary');
    if (!s) { host.innerHTML = ''; return; }

    var rateNote = s.attendedCount
      ? 'จากผู้เข้าร่วมจริง ' + s.attendedCount + ' คน' + (s.isRepresentative ? '' : ' · กลุ่มตัวอย่างยังน้อย')
      : 'ยังไม่มีข้อมูลผู้เข้าร่วมจริง';

    var kpis = kpiHtml('คะแนนเฉลี่ย', s.average === null ? '—' : s.average.toFixed(2), s.average === null ? '' : '/5',
        s.grade.label, s.grade.tone) +
      kpiHtml('จำนวนผู้ตอบ', String(s.responseCount), 'ชุด', rateNote) +
      kpiHtml('อัตราการตอบ', s.responseRate === null ? '—' : String(s.responseRate), s.responseRate === null ? '' : '%',
        s.responseRate === null ? 'กิจกรรมนี้ไม่มีการเช็คอิน' : 'ผู้ตอบ ÷ ผู้เข้าร่วมจริง') +
      kpiHtml('ให้คะแนน 4–5', String(s.highRatioPercent), '%', 'ความเห็นเพิ่มเติม ' + s.commentCount + ' ชุด');

    var distribution = s.distribution.map(function (d) {
      return barHtml(d.star + ' ดาว', d.percent, d.count + ' ชุด · ' + d.percent + '%', false);
    }).join('');

    var topics = s.topics.length
      ? s.topics.map(function (t) {
          return barHtml(t.label, t.average / 5 * 100, t.average.toFixed(1) + '/5', t.needsWork);
        }).join('')
      : '<p class="ar-panel-empty">ยังไม่มีคำตอบชนิดให้คะแนนในกิจกรรมนี้</p>';

    host.innerHTML =
      '<div class="ar-kpis">' + kpis + '</div>' +
      '<div class="ar-panels">' +
        '<section class="card ar-panel"><h2 class="ar-panel-title">การกระจายคะแนน</h2>' +
          (s.responseCount ? distribution : '<p class="ar-panel-empty">ยังไม่มีผู้ตอบแบบประเมิน</p>') +
        '</section>' +
        '<section class="card ar-panel"><h2 class="ar-panel-title">คะแนนรายข้อ</h2>' + topics + '</section>' +
      '</div>';
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

  /* โครงร่างระหว่างโหลดกิจกรรมใหม่ — จำนวนแถวเท่าที่จะแสดงจริง หน้าจึงไม่กระโดดตอนข้อมูลมา */
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

  /* "10 ส.ค. 69 · 12:45 น." — เวลามาจากฐานเป็น "YYYY-MM-DD HH:MM:SS" แปลงตอนแสดงผลเท่านั้น */
  function fmtDateTime(value) {
    var parts = String(value).split(/[T ]/);
    var date = window.TFC.formatThaiDate(parts[0]).replace(/(\d{2})(\d{2})$/, '$2');
    return date + ' · ' + (parts[1] || '').slice(0, 5) + ' น.';
  }

  function scoreLabel(row) {
    return row.score === null ? 'ไม่ให้คะแนน' : row.score + '/5';
  }

  function rowHtml(row) {
    return '<div class="ar-row">' +
      '<span class="ar-no">' + row.seq + '</span>' +
      '<span class="ar-who">ผู้ตอบ #' + row.seq + '</span>' +
      '<span class="ar-mid"><span class="ar-score is-' + row.band + '">' + esc(scoreLabel(row)) + '</span></span>' +
      '<span class="ar-cell">' + esc(row.round) + '</span>' +
      (row.comment
        ? '<span class="ar-comment" title="' + esc(row.comment) + '">' + esc(row.comment) + '</span>'
        : '<span class="ar-comment is-none">ไม่ได้ให้ความเห็น</span>') +
      '<span class="ar-cell ar-at">' + esc(fmtDateTime(row.submittedAt)) + '</span>' +
      '</div>';
  }

  /** วาดผลลัพธ์ชุดหนึ่งลงตาราง — ใช้ทั้งข้อมูลที่เซิร์ฟเวอร์ใส่มากับหน้าและที่โหลดมาทีหลัง */
  function paint(result) {
    var table = $('ar-table');
    table.classList.remove('is-refreshing');
    table.innerHTML = HEAD_HTML + (result.rows.length ? result.rows.map(rowHtml).join('') : EMPTY_HTML);
    $('ar-count').textContent = 'พบ ' + result.total + ' ชุด';

    var foot = $('ar-pagination');
    foot.hidden = result.total === 0;
    if (foot.hidden) return;

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

  /* โหลดข้อมูลใหม่โดยไม่ถอดของเดิมทิ้ง — จางลงแล้วค่อยสลับ ตามมาตรฐาน Motion */
  function render() {
    if (!state.activity) return Promise.resolve();
    $('ar-table').classList.add('is-refreshing');

    var seq = ++state.seq;
    return get(cfg.dataUrl, {
      id: state.activity.id,
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

      paint(result);
    }, function () {
      $('ar-table').classList.remove('is-refreshing');
      window.TFC.showToast('โหลดผลแบบประเมินไม่สำเร็จ กรุณาลองใหม่', 'danger');
    });
  }

  /* ---------- เหตุการณ์ ---------- */
  function closePicker() {
    if (!state.pickerOpen) return;
    state.pickerOpen = false;
    renderHead();
  }

  function selectActivity(id) {
    state.pickerOpen = false;
    if (!state.activity || id === state.activity.id) return renderHead();

    state.activity = { id: id, name: '', startDateLabel: '' };
    state.page = 1;                 /* เปลี่ยนกิจกรรมต้องกลับหน้า 1 */
    history.replaceState(null, '', cfg.pageUrl + '?id=' + encodeURIComponent(id));

    /* เปลี่ยนกิจกรรม = ข้อมูลทั้งแผงเป็นคนละชุด จึงใช้โครงร่างแทนการจางของเดิม */
    $('ar-table').innerHTML = skeletonHtml();

    return Promise.all([
      get(cfg.summaryUrl, { id: id }).then(function (data) {
        state.activity = data.activity;
        state.summary = data.summary;
        renderHead();
        renderSummary();
      }),
      render()
    ]);
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('#ar-picker-btn')) {
      state.pickerOpen = !state.pickerOpen;
      return renderHead();
    }

    var pick = e.target.closest('[data-activity]');
    if (pick) return selectActivity(pick.getAttribute('data-activity'));

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
    search: { placeholder: 'ลำดับผู้ตอบ หรือข้อความในความเห็น' },
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
    if (!state.activity) return;
    button.disabled = true;

    get(cfg.dataUrl, { id: state.activity.id, band: state.band, keyword: state.keyword })
      .then(function (result) {
        window.TFC.exportCsv(
          'ประเมินกิจกรรม-' + state.activity.id + '.csv',
          ['ลำดับ', 'ผู้ตอบ', 'คะแนนรวม', 'รอบที่เข้าร่วม', 'ความเห็นเพิ่มเติม', 'วันเวลาที่ตอบ'],
          result.rows.map(function (row) {
            return [row.seq, 'ผู้ตอบ #' + row.seq, scoreLabel(row), row.round,
              row.comment || 'ไม่ได้ให้ความเห็น', fmtDateTime(row.submittedAt)];
          })
        );
      }, function () {
        window.TFC.showToast('ส่งออกไม่สำเร็จ กรุณาลองใหม่', 'danger');
      })
      .then(function () { button.disabled = false; });
  });

  /* ---------- เริ่มทำงาน — ข้อมูลชุดแรกมากับหน้าแล้ว ไม่ต้องยิงคำขอซ้ำ ---------- */
  renderTabs();
  renderHead();
  renderSummary();

  if (state.activity) {
    paint(cfg.initial);
  } else {
    $('ar-table').innerHTML = HEAD_HTML +
      '<div class="ar-empty">' +
        '<span class="ar-empty-title">ยังไม่มีกิจกรรมที่ผูกแบบประเมินหลังกิจกรรมไว้</span>' +
        '<span class="ar-empty-hint">ผูกแบบประเมินชนิด “หลังกิจกรรม” กับกิจกรรมก่อน จึงจะเห็นผลตรงนี้</span>' +
      '</div>';
    $('ar-pagination').hidden = true;
  }
})();
