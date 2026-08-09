/* TheFarmConcept — หน้ารายละเอียดกิจกรรม (admin/activities/detail.html)

   หน้านี้เป็น read-only ทั้งหมด ไม่มี input field แม้แต่ช่องเดียว
   การแก้ไขทำที่ edit.html ผ่านปุ่ม "แก้ไขกิจกรรม" เท่านั้น

   ทุกตัวเลขในหน้านี้คำนวณสดจากชุดข้อมูลของระบบ ไม่มีค่าที่พิมพ์ทับไว้:
   - ผู้ลงทะเบียน   -> TFC_MOCK.activityRegistrations[id]  (เพศ/อายุ/อาชีพ/ช่องทาง/ความสนใจ/รอบ/registeredAtISO)
   - การชำระเงิน    -> registration.paymentStatus + activity.fee
   - เช็คอิน        -> registration.checkinStatus + checkedInAt + manualEntry (self/staff)
   - แบบประเมิน     -> TFC_MOCK.activityEvaluations[id] (topicScores/average/feedback/answeredAt ไม่มี user id)
   ยกเว้นขั้น "เปิดหน้ากิจกรรม" ของ funnel ที่ระบบยังไม่ได้เก็บ page view — ดูหมายเหตุที่ renderReport() */
(function () {
  var mock = window.TFC_MOCK;
  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  /* ---------- เลือกกิจกรรม ---------- */
  var params = new URLSearchParams(location.search);
  var activities = mock.activities || [];
  var activity = activities.filter(function (a) { return a.id === params.get('id'); })[0] || activities[0];

  var regs = (mock.activityRegistrations[activity.id] || []).map(function (r, i) {
    return {
      raw: r,
      code: (r.manualEntry ? 'WLK-' : 'REG-') + activity.id.slice(-3) + '-' + String(i + 1).padStart(3, '0'),
      name: r.name,
      phone: r.phone,
      email: r.email,
      gender: r.gender,
      ageRange: r.ageRange,
      occupation: r.occupation,
      sourceChannel: r.sourceChannel,
      interests: r.interests || [],
      session: r.session,
      registeredAt: r.registeredAtISO || '',
      payment: activity.hasFee ? r.paymentStatus : 'ยกเว้น',
      amount: activity.hasFee ? (activity.fee || 0) : 0,
      checkedIn: r.checkinStatus === 'เข้าร่วมแล้ว',
      checkedInAt: r.checkedInAt || '',
      bySelf: !r.manualEntry
    };
  });

  var evals = mock.activityEvaluations[activity.id] || [];
  var sessions = mock.activitySessions[activity.id] || [];

  var state = { tab: 'ภาพรวม', peopleView: 'ตาราง', checkView: 'ตาราง', showAdvice: false };

  /* ================= ตัวช่วยคำนวณ ================= */
  function countBy(list, keyFn) {
    var map = {};
    list.forEach(function (item) {
      var keys = keyFn(item);
      (Array.isArray(keys) ? keys : [keys]).forEach(function (k) {
        if (k === undefined || k === null || k === '') return;
        map[k] = (map[k] || 0) + 1;
      });
    });
    return map;
  }

  function toRows(map) {
    return Object.keys(map)
      .map(function (k) { return { label: k, n: map[k] }; })
      .sort(function (a, b) { return b.n - a.n; });
  }

  function maxOf(rows) {
    return rows.reduce(function (m, r) { return Math.max(m, r.n); }, 0);
  }

  function pct(part, total) { return total ? (part / total) * 100 : 0; }

  function roundName(reg) {
    if (!sessions.length) return 'รอบเดียว';
    var i = sessions.map(function (s) { return s.date; }).indexOf(reg.session);
    return 'รอบ ' + (i < 0 ? 1 : i + 1);
  }

  function baht(n) { return Number(n || 0).toLocaleString('th-TH') + ' ฿'; }

  /* ---------- ตัวเลขหลักที่ใช้ซ้ำหลายแท็บ ---------- */
  var totalCap = sessions.length
    ? sessions.reduce(function (n, s) { return n + (s.capacity || 0); }, 0)
    : (activity.capacity || 0);
  var checkedIn = regs.filter(function (r) { return r.checkedIn; });
  var paid = regs.filter(function (r) { return r.payment === 'ชำระแล้ว'; });
  var waitingSlip = regs.filter(function (r) { return r.payment === 'รอตรวจสอบ'; });
  var walkIn = regs.filter(function (r) { return !r.bySelf && r.checkedIn; });
  var revenue = paid.reduce(function (n, r) { return n + r.amount; }, 0);
  var avgScore = evals.length
    ? Math.round((evals.reduce(function (n, e) { return n + e.average; }, 0) / evals.length) * 10) / 10
    : 0;

  /* ================= ชิ้นส่วน UI ที่ใช้ซ้ำ ================= */
  function barRow(label, valueText, percent, tone) {
    return '<div class="ad-bar-row">' +
      '<div class="ad-bar-head"><span class="ad-bar-label">' + esc(label) + '</span>' +
      '<span class="ad-bar-value">' + esc(valueText) + '</span></div>' +
      '<div class="ad-bar"><span class="' + (tone || '') + '" style="width: ' + percent.toFixed(1) + '%"></span></div>' +
      '</div>';
  }

  /* แสดงแค่ 5 อันดับแรกให้การ์ดสูงเท่ากันทุกใบและอ่านง่าย
     ที่เหลือบอกจำนวนไว้ท้ายการ์ด ไม่ตัดทิ้งเงียบๆ */
  var TOP_N = 5;

  function barListCard(title, rows, unit, subtitle) {
    var max = maxOf(rows);
    var shown = rows.slice(0, TOP_N);
    var restCount = rows.length - shown.length;
    var restSum = rows.slice(TOP_N).reduce(function (n, r) { return n + r.n; }, 0);

    return '<div class="card ad-chart-card">' +
      '<div class="ad-card-head"><span class="ad-card-title">' + esc(title) + '</span>' +
      (subtitle ? '<span class="ad-card-sub">' + esc(subtitle) + '</span>' : '') + '</div>' +
      '<div class="ad-bars">' +
      (shown.length
        ? shown.map(function (r) {
            return barRow(r.label, r.n + ' ' + unit, pct(r.n, max), r.n === max ? 'is-peak' : '');
          }).join('')
        : '<span class="ad-empty-line">ยังไม่มีข้อมูล</span>') +
      '</div>' +
      (restCount > 0
        ? '<span class="ad-rest-line">อีก ' + restCount + ' รายการ รวม ' + restSum + ' ' + unit + '</span>'
        : '') +
      '</div>';
  }

  function vBarCard(title, rows, subtitle) {
    var max = maxOf(rows);
    return '<div class="card ad-chart-card">' +
      '<div class="ad-card-head"><span class="ad-card-title">' + esc(title) + '</span>' +
      (subtitle ? '<span class="ad-card-sub">' + esc(subtitle) + '</span>' : '') + '</div>' +
      '<div class="ad-vbars-wrap"><div class="ad-vbars">' +
      rows.map(function (r) {
        return '<div class="ad-vbar-col" title="' + esc(r.label + ' · ' + r.n) + '">' +
          '<span class="ad-vbar-n">' + r.n + '</span>' +
          '<div class="ad-vbar-track"><span class="ad-vbar' + (r.n === max && max > 0 ? ' is-peak' : '') +
          '" style="height: ' + Math.max(pct(r.n, max), r.n > 0 ? 6 : 2).toFixed(1) + '%"></span></div>' +
          '<span class="ad-vbar-label">' + esc(r.label) + '</span>' +
          '</div>';
      }).join('') +
      '</div></div></div>';
  }

  function statCard(label, value, hint, tone) {
    return '<div class="card ad-stat">' +
      '<span class="ad-stat-label">' + esc(label) + '</span>' +
      '<span class="ad-stat-value' + (tone ? ' ' + tone : '') + '">' + esc(value) + '</span>' +
      (hint ? '<span class="ad-stat-hint">' + esc(hint) + '</span>' : '') +
      '</div>';
  }

  function qrSvg(seed, n) {
    n = n || 17;
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

  var QR_LINKS = [
    { label: 'ลงทะเบียนเข้าร่วม', url: 'farmconcept.th/r/' + activity.id.slice(-4) },
    { label: 'เช็คอินหน้างาน', url: 'farmconcept.th/c/' + activity.id.slice(-4) },
    { label: 'แบบประเมินหลังกิจกรรม', url: 'farmconcept.th/s/' + activity.id.slice(-4) }
  ];

  /* ================= หัวหน้า ================= */
  function renderHeader() {
    var badge = (mock.activityStatuses || []).filter(function (s) { return s.value === activity.status; })[0];
    var updated = activity.updatedAt || activity.publishStart || activity.startDate;

    $('ad-header').innerHTML =
      '<div class="ad-header-main">' +
        '<a class="ad-back" href="list.html" aria-label="ย้อนกลับไปรายการกิจกรรม">' +
          '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>' +
        '</a>' +
        '<div class="ad-header-text">' +
          '<div class="ad-title-line">' +
            '<h1 class="ad-title">' + esc(activity.name) + '</h1>' +
            '<span class="badge ' + (badge ? badge.badge : 'badge-neutral') + '">' + esc(activity.status) + '</span>' +
          '</div>' +
          '<div class="ad-sub-line">' +
            '<span class="ad-code">' + esc(activity.id) + '</span>' +
            '<span class="ad-updated">ปรับปรุงล่าสุด ' + esc(window.TFC.formatThaiDate(updated)) +
              ' โดย ' + esc((mock.currentUser && mock.currentUser.name) || 'ผู้ดูแลระบบ') + '</span>' +
          '</div>' +
        '</div>' +
      '</div>' +
      '<div class="ad-header-actions">' +
        '<button type="button" class="btn btn-outline" id="ad-export">' +
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 4v11M8 11.5l4 4 4-4M5 19.5h14"/></svg>' +
          'ส่งออกรายงาน</button>' +
        '<a class="btn btn-primary" href="edit.html?id=' + encodeURIComponent(activity.id) + '">' +
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4l10-10-4-4L4 16v4Z"/><path d="M13.5 6.5l4 4"/></svg>' +
          'แก้ไขกิจกรรม</a>' +
      '</div>';
  }

  /* ================= แท็บ ================= */
  function renderTabs() {
    var defs = [
      { label: 'ภาพรวม', count: null },
      { label: 'ผู้เข้าร่วม', count: regs.length },
      { label: 'Check-in', count: checkedIn.length },
      { label: 'แบบประเมิน', count: evals.length },
      { label: 'รายงาน', count: null }
    ];
    $('ad-tabs').innerHTML = defs.map(function (t) {
      var on = state.tab === t.label;
      return '<button type="button" class="ad-tab' + (on ? ' is-on' : '') + '" role="tab" aria-selected="' + on +
        '" data-tab="' + esc(t.label) + '">' + esc(t.label) +
        (t.count !== null ? '<span class="ad-tab-count">' + t.count + '</span>' : '') + '</button>';
    }).join('');
  }

  /* ================= 1) ภาพรวม ================= */
  function renderOverview() {
    var tags = [activity.type, activity.format].filter(Boolean);
    var instructors = (activity.instructorList || [activity.instructor]).filter(Boolean).join(', ');

    var facts = [
      { label: 'วันที่จัด', value: window.TFC.formatThaiDate(activity.startDate), d: 'M4 6.5h16v13H4zM8 3.5v4M16 3.5v4M4 11h16' },
      { label: 'เวลา', value: (activity.time || '-') + ' น.', d: 'M12 20.5a8.5 8.5 0 1 0 0-17 8.5 8.5 0 0 0 0 17ZM12 7.5V12l3 1.8' },
      { label: 'สถานที่', value: activity.area || '-', d: 'M12 21s6.5-6 6.5-10.5a6.5 6.5 0 1 0-13 0C5.5 15 12 21 12 21Z' },
      { label: 'วิทยากร', value: instructors || '-', d: 'M12 11.6a3.4 3.4 0 1 0 0-6.8 3.4 3.4 0 0 0 0 6.8ZM5.5 20c0-3.3 2.9-5.8 6.5-5.8s6.5 2.5 6.5 5.8' },
      { label: 'หลักสูตร', value: activity.course ? activity.course + ' (' + activity.program + ')' : '-', d: 'M5 4.5h9a2.5 2.5 0 0 1 2.5 2.5v12.5H7.5A2.5 2.5 0 0 1 5 17V4.5ZM16.5 7H19v12.5H7.5' },
      { label: 'ค่าเข้าร่วม', value: activity.hasFee ? baht(activity.fee) + ' · โอนผ่านธนาคาร' : 'ไม่มีค่าใช้จ่าย', d: 'M3.5 7.5h17v9h-17zM3.5 11h17M6.5 14h3' }
    ];

    /* รอบกิจกรรม: จำนวนที่นั่งนับจากผู้ลงทะเบียนจริงของรอบนั้น ไม่ใช่ตัวเลขที่เขียนไว้ */
    var roundCards = sessions.map(function (s, i) {
      var n = regs.filter(function (r) { return r.session === s.date; }).length;
      var cap = s.capacity || 0;
      var full = cap > 0 && n >= cap;
      return '<div class="ad-round">' +
        '<div class="ad-round-head">' +
          '<span class="ad-round-name">รอบที่ ' + (i + 1) + ' · ' + esc(window.TFC.formatThaiDate(s.date)) + '</span>' +
          '<span class="ad-round-seats">' + n + '/' + cap + ' ที่นั่ง</span>' +
        '</div>' +
        '<span class="ad-round-when">' + esc(s.time || '-') + ' น. · ' + esc(s.location || '-') + '</span>' +
        '<div class="ad-bar"><span class="' + (full ? 'is-full' : 'is-peak') + '" style="width: ' + pct(n, cap).toFixed(1) + '%"></span></div>' +
        '</div>';
    }).join('');

    var regFields = ['รอบที่สมัคร', 'ชื่อ–นามสกุล', 'เพศ', 'ช่วงอายุ', 'เบอร์โทรศัพท์', 'อีเมล', 'อาชีพ', 'ช่องทางที่รู้จัก', 'กิจกรรมที่สนใจ']
      .concat(activity.hasFee ? ['สลิปการโอนเงิน'] : []);

    var kpis = [
      { label: 'ลงทะเบียน', value: regs.length + ' / ' + totalCap, tone: '' },
      { label: 'เช็คอินแล้ว', value: checkedIn.length + ' คน', tone: 'is-success' },
      { label: 'ตอบแบบประเมิน', value: evals.length + ' คน', tone: '' },
      { label: 'รายรับรวม', value: baht(revenue), tone: '' },
      { label: 'รอตรวจสอบสลิป', value: waitingSlip.length + ' ใบ', tone: waitingSlip.length ? 'is-warning' : '' }
    ];

    return '<div class="ad-layout">' +
      '<div class="ad-main">' +

        '<div class="card ad-hero">' +
          '<div class="ad-hero-top">' +
            '<div class="ad-cover">' +
              '<span class="ad-cover-badge">' + esc(activity.format || activity.type || 'กิจกรรม') + '</span>' +
              '<span class="ad-cover-text">' + esc(activity.name) + '</span>' +
            '</div>' +
            '<div class="ad-hero-text">' +
              '<div class="ad-tags">' + tags.map(function (t) { return '<span class="ad-tag">' + esc(t) + '</span>'; }).join('') + '</div>' +
              '<span class="ad-card-title">รายละเอียดกิจกรรม</span>' +
              '<p class="ad-desc">' + esc(activity.description || 'กิจกรรม' + activity.name + ' จัดโดย ' + (activity.organizer || 'The Farm Concept') + ' เปิดรับผู้เข้าร่วม ' + totalCap + ' คน') + '</p>' +
            '</div>' +
          '</div>' +
          '<div class="ad-facts">' +
            facts.map(function (f) {
              return '<div class="ad-fact">' +
                '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="' + f.d + '"/></svg>' +
                '<span class="ad-fact-text">' +
                  '<span class="ad-fact-label">' + esc(f.label) + '</span>' +
                  '<span class="ad-fact-value">' + esc(f.value) + '</span>' +
                '</span></div>';
            }).join('') +
          '</div>' +
        '</div>' +

        '<div class="card ad-chart-card">' +
          '<span class="ad-card-title">รอบกิจกรรม</span>' +
          '<div class="ad-rounds">' + (roundCards || '<span class="ad-empty-line">ยังไม่ได้กำหนดรอบ</span>') + '</div>' +
        '</div>' +

        '<div class="card ad-chart-card">' +
          '<span class="ad-card-title">ข้อมูลที่เก็บตอนลงทะเบียน</span>' +
          '<div class="ad-fields">' + regFields.map(function (f) {
            return '<span class="ad-field-chip">' + esc(f) + '</span>';
          }).join('') + '</div>' +
        '</div>' +

      '</div>' +
      '<aside class="ad-side">' +
        '<div class="card ad-panel">' +
          '<span class="ad-card-title">สรุปตัวเลข</span>' +
          '<div class="ad-kpis">' + kpis.map(function (k) {
            return '<div class="ad-kpi"><span class="ad-kpi-label">' + esc(k.label) + '</span>' +
              '<span class="ad-kpi-value' + (k.tone ? ' ' + k.tone : '') + '">' + esc(k.value) + '</span></div>';
          }).join('') + '</div>' +
        '</div>' +
        '<div class="card ad-panel">' +
          '<span class="ad-card-title">QR และลิงก์</span>' +
          '<div class="ad-qr-list">' + QR_LINKS.map(function (q) {
            return '<div class="ad-qr-item">' +
              '<span class="ad-qr-thumb">' + qrSvg(q.url) + '</span>' +
              '<span class="ad-qr-text">' +
                '<span class="ad-qr-label">' + esc(q.label) + '</span>' +
                '<span class="ad-qr-url">' + esc(q.url) + '</span>' +
              '</span>' +
              '<button type="button" class="ad-qr-btn" data-qr="' + esc(q.label) + '" aria-label="ดาวน์โหลด QR ' + esc(q.label) + '">' +
                '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M12 4v11M8 11.5l4 4 4-4M5 19.5h14"/></svg>' +
              '</button></div>';
          }).join('') + '</div>' +
        '</div>' +
      '</aside>' +
    '</div>';
  }

  /* ================= 2) ผู้เข้าร่วม ================= */
  function segmented(id, current, views) {
    return '<div class="ad-segmented" id="' + id + '">' + views.map(function (v) {
      return '<button type="button" class="ad-seg' + (current === v ? ' is-on' : '') + '" data-view="' + id + ':' + esc(v) + '">' + esc(v) + '</button>';
    }).join('') + '</div>';
  }

  function payBadge(status) {
    var map = { 'ชำระแล้ว': 'badge-success', 'รอตรวจสอบ': 'badge-warning', 'ยกเว้น': 'badge-neutral', 'ยังไม่ชำระ': 'badge-danger', 'ปฏิเสธ': 'badge-danger' };
    return '<span class="badge ' + (map[status] || 'badge-neutral') + '">' + esc(status) + '</span>';
  }

  function renderPeople() {
    if (state.peopleView === 'รายงาน') return renderPeopleHead() + renderPeopleReport();
    return renderPeopleHead() +
      '<div class="card ad-table-card">' +
        '<div class="ad-table-scroll"><div class="ad-table ad-people-table">' +
          '<div class="ad-tr ad-th">' +
            '<div>ผู้เข้าร่วม</div><div>รอบที่สมัคร</div><div>อายุ</div><div>อาชีพ</div>' +
            '<div>ติดต่อ</div><div>ชำระเงิน</div><div>Check-in</div>' +
          '</div>' +
          regs.map(function (r) {
            return '<div class="ad-tr">' +
              '<div class="ad-person">' +
                '<span class="ad-avatar' + (r.checkedIn ? ' is-done' : '') + '">' + esc(r.name.charAt(0)) + '</span>' +
                '<span class="ad-person-text">' +
                  '<span class="ad-person-name">' + esc(r.name) + '</span>' +
                  '<span class="ad-person-code">' + esc(r.code) + '</span>' +
                '</span></div>' +
              '<div class="ad-cell-plain">' + esc(roundName(r)) + '</div>' +
              '<div class="ad-cell-plain">' + esc(r.ageRange) + '</div>' +
              '<div class="ad-cell-plain">' + esc(r.occupation) + '</div>' +
              '<div class="ad-contact"><span>' + esc(r.phone) + '</span><span class="ad-contact-mail">' + esc(r.email) + '</span></div>' +
              '<div>' + payBadge(r.payment) + '</div>' +
              '<div>' + (r.checkedIn
                ? '<span class="badge badge-success">เช็คอินแล้ว</span>'
                : '<span class="ad-muted-cell">ยังไม่มา</span>') + '</div>' +
              '</div>';
          }).join('') +
        '</div></div>' +
      '</div>';
  }

  function renderPeopleHead() {
    return '<div class="ad-view-bar">' +
      '<span class="ad-view-title">ผู้ลงทะเบียน ' + regs.length + ' คน</span>' +
      segmented('peopleView', state.peopleView, ['ตาราง', 'รายงาน']) +
      '</div>';
  }

  /* ช่วงเวลาที่คนลงทะเบียน — คิดจาก registeredAtISO ของแต่ละคน ไม่ใช่ค่าที่ตั้งไว้ */
  function regTimeSlots() {
    var slots = ['06:00–09:00', '09:00–12:00', '12:00–15:00', '15:00–18:00', '18:00–21:00', '21:00–24:00'];
    var counts = slots.map(function () { return 0; });
    regs.forEach(function (r) {
      if (!r.registeredAt) return;
      var h = Number(r.registeredAt.slice(11, 13));
      var i = Math.floor((h - 6) / 3);
      if (i >= 0 && i < slots.length) counts[i]++;
    });
    return slots.map(function (s, i) { return { label: s, n: counts[i] }; });
  }

  function regWeekdays() {
    var names = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    var counts = [0, 0, 0, 0, 0, 0, 0];
    regs.forEach(function (r) {
      if (!r.registeredAt) return;
      counts[new Date(r.registeredAt.slice(0, 10)).getDay()]++;
    });
    /* เริ่มที่วันจันทร์ตามที่คนไทยอ่านปฏิทิน */
    return [1, 2, 3, 4, 5, 6, 0].map(function (d) { return { label: names[d], n: counts[d] }; });
  }

  function renderPeopleReport() {
    var repeatCount = 0;
    var otherIds = Object.keys(mock.activityRegistrations).filter(function (k) { return k !== activity.id; });
    regs.forEach(function (r) {
      var seen = otherIds.some(function (k) {
        return (mock.activityRegistrations[k] || []).some(function (x) { return x.name === r.name; });
      });
      if (seen) repeatCount++;
    });

    var kpis =
      statCard('ลงทะเบียนทั้งหมด', regs.length + ' คน', 'จากที่รับได้ ' + totalCap + ' ที่นั่ง', '') +
      statCard('ชำระเงินแล้ว', paid.length + ' คน', 'รวม ' + baht(revenue), 'is-success') +
      statCard('รอตรวจสอบสลิป', waitingSlip.length + ' คน', waitingSlip.length ? 'ต้องยืนยันก่อนวันงาน' : 'ไม่มีรายการค้าง', waitingSlip.length ? 'is-warning' : '') +
      statCard('มาซ้ำจากกิจกรรมก่อน', repeatCount + ' คน', 'คิดเป็น ' + Math.round(pct(repeatCount, regs.length)) + '% ของผู้ลงทะเบียน', '');

    var byRound = sessions.map(function (s, i) {
      var n = regs.filter(function (r) { return r.session === s.date; }).length;
      return { label: 'รอบที่ ' + (i + 1) + ' · ' + window.TFC.formatThaiDate(s.date), n: n, cap: s.capacity || 0 };
    });

    return '<div class="ad-stats-row">' + kpis + '</div>' +
      '<div class="ad-grid2">' + genderCard() + barListCard('ช่วงอายุ', ageRows(), 'คน') + '</div>' +
      '<div class="ad-grid2">' +
        barListCard('ช่วงเวลาที่คนลงทะเบียน', regTimeSlots(), 'คน', 'คิดจากเวลาที่กดยืนยันจริง') +
        vBarCard('วันในสัปดาห์ที่ลงทะเบียน', regWeekdays()) +
      '</div>' +
      '<div class="ad-grid2">' +
        barListCard('กิจกรรมที่สนใจเพิ่มเติม', toRows(countBy(regs, function (r) { return r.interests; })), 'คน') +
        barListCard('รู้จักกิจกรรมจากช่องทางใด', channelRows(), 'คน') +
      '</div>' +
      '<div class="ad-grid2">' +
        barListCard('อาชีพ', toRows(countBy(regs, function (r) { return r.occupation; })), 'คน') +
        '<div class="card ad-chart-card"><span class="ad-card-title">แยกตามรอบที่สมัคร</span><div class="ad-bars">' +
        (byRound.length
          ? byRound.map(function (r) {
              return barRow(r.label, r.n + ' / ' + r.cap + ' ที่นั่ง', pct(r.n, r.cap), r.cap > 0 && r.n >= r.cap ? 'is-full' : 'is-peak');
            }).join('')
          : '<span class="ad-empty-line">ยังไม่ได้กำหนดรอบ</span>') +
        '</div></div>' +
      '</div>';
  }

  function ageRows() { return toRows(countBy(regs, function (r) { return r.ageRange; })); }
  function channelRows() { return toRows(countBy(regs, function (r) { return r.sourceChannel; })); }

  /* โดนัทเพศ — สร้าง conic-gradient จากสัดส่วนจริง */
  function genderCard() {
    var rows = toRows(countBy(regs, function (r) { return r.gender; }));
    var total = regs.length;
    var swatches = ['#16A34A', '#86EFAC', '#D1D5DB', '#EEF1EE'];
    var deg = 0;
    var stops = rows.map(function (r, i) {
      var from = deg;
      deg += (r.n / (total || 1)) * 360;
      return swatches[i % swatches.length] + ' ' + from.toFixed(1) + 'deg ' + deg.toFixed(1) + 'deg';
    }).join(', ');

    return '<div class="card ad-chart-card">' +
      '<span class="ad-card-title">เพศ</span>' +
      '<div class="ad-donut-wrap">' +
        '<div class="ad-donut" style="background: conic-gradient(' + (stops || '#EEF1EE 0deg 360deg') + ')">' +
          '<span class="ad-donut-hole"><span class="ad-donut-n">' + total + '</span><span class="ad-donut-unit">คน</span></span>' +
        '</div>' +
        '<div class="ad-legend">' + rows.map(function (r, i) {
          return '<span class="ad-legend-row">' +
            '<span class="ad-legend-dot" style="background: ' + swatches[i % swatches.length] + '"></span>' +
            '<span class="ad-legend-label">' + esc(r.label) + '</span>' +
            '<span class="ad-legend-value">' + r.n + ' คน · ' + Math.round(pct(r.n, total)) + '%</span>' +
            '</span>';
        }).join('') + '</div>' +
      '</div></div>';
  }

  /* ================= 3) Check-in ================= */
  function renderCheckin() {
    var head = '<div class="ad-view-bar">' +
      '<span class="ad-view-title">เช็คอินแล้ว ' + checkedIn.length + ' จาก ' + regs.length + ' คน</span>' +
      segmented('checkView', state.checkView, ['ตาราง', 'รายงาน']) +
      '</div>';

    if (state.checkView === 'รายงาน') return head + renderCheckinReport();

    return head +
      '<div class="card ad-table-card">' +
        '<div class="ad-table-scroll"><div class="ad-table ad-check-table">' +
          '<div class="ad-tr ad-th"><div>ชื่อ</div><div>รหัส</div><div>รอบ</div><div>เวลาเช็คอิน</div><div>ช่องทาง</div></div>' +
          regs.map(function (r) {
            return '<div class="ad-tr">' +
              '<div class="ad-person">' +
                '<span class="ad-avatar' + (r.checkedIn ? ' is-done' : '') + '">' + esc(r.name.charAt(0)) + '</span>' +
                '<span class="ad-person-name">' + esc(r.name) + '</span>' +
              '</div>' +
              '<div class="ad-cell-plain ad-nums">' + esc(r.code) + '</div>' +
              '<div class="ad-cell-plain">' + esc(roundName(r)) + '</div>' +
              '<div class="' + (r.checkedIn ? 'ad-time-done' : 'ad-muted-cell') + '">' +
                (r.checkedIn ? esc(r.checkedInAt) + ' น.' : 'ยังไม่มา') + '</div>' +
              '<div class="ad-cell-plain">' + (r.checkedIn ? (r.bySelf ? 'สแกนเอง' : 'เจ้าหน้าที่บันทึก') : '—') + '</div>' +
              '</div>';
          }).join('') +
        '</div></div>' +
      '</div>';
  }

  function renderCheckinReport() {
    var noShow = regs.length - checkedIn.length;
    var mins = checkedIn.map(function (r) {
      return Number(r.checkedInAt.slice(0, 2)) * 60 + Number(r.checkedInAt.slice(3, 5));
    });
    var avgMin = mins.length ? Math.round(mins.reduce(function (a, b) { return a + b; }, 0) / mins.length) : 0;
    var avgText = mins.length
      ? String(Math.floor(avgMin / 60)).padStart(2, '0') + ':' + String(avgMin % 60).padStart(2, '0')
      : '—';

    /* กราฟช่วงเวลาเช็คอิน — แบ่งเป็นช่วง 15 นาทีจากเวลาที่เช็คอินจริง */
    var buckets = {};
    checkedIn.forEach(function (r) {
      var m = Number(r.checkedInAt.slice(0, 2)) * 60 + Number(r.checkedInAt.slice(3, 5));
      var b = Math.floor(m / 15) * 15;
      buckets[b] = (buckets[b] || 0) + 1;
    });
    var bars = Object.keys(buckets).map(Number).sort(function (a, b) { return a - b; }).map(function (b) {
      return { label: String(Math.floor(b / 60)).padStart(2, '0') + ':' + String(b % 60).padStart(2, '0'), n: buckets[b] };
    });

    var recent = checkedIn.slice().sort(function (a, b) { return b.checkedInAt.localeCompare(a.checkedInAt); }).slice(0, 5);

    return '<div class="ad-stats-row">' +
        statCard('เช็คอินแล้ว', checkedIn.length + ' / ' + regs.length, 'คิดเป็น ' + Math.round(pct(checkedIn.length, regs.length)) + '% ของผู้ลงทะเบียน', 'is-success') +
        statCard('ไม่มาตามนัด', noShow + ' คน', 'ลงทะเบียนแล้วแต่ไม่เช็คอิน', noShow ? 'is-warning' : '') +
        statCard('Walk-in', walkIn.length + ' คน', 'เพิ่มหน้างานโดยเจ้าหน้าที่', '') +
        statCard('เช็คอินเฉลี่ย', avgText, 'กิจกรรมเริ่ม ' + ((activity.time || '09:00').split('-')[0].trim()) + ' น.', '') +
      '</div>' +
      '<div class="ad-grid2">' +
        vBarCard('ช่วงเวลาที่คนเช็คอิน', bars, 'แบ่งเป็นช่วงละ 15 นาที') +
        '<div class="card ad-chart-card"><span class="ad-card-title">เช็คอินล่าสุด</span><div class="ad-recent">' +
        (recent.length ? recent.map(function (r) {
          return '<div class="ad-recent-row">' +
            '<span class="ad-avatar is-done">' + esc(r.name.charAt(0)) + '</span>' +
            '<span class="ad-recent-text"><span class="ad-recent-name">' + esc(r.name) + '</span>' +
            '<span class="ad-recent-by">' + (r.bySelf ? 'สแกนเอง' : 'เจ้าหน้าที่บันทึก') + '</span></span>' +
            '<span class="ad-recent-at">' + esc(r.checkedInAt) + ' น.</span></div>';
        }).join('') : '<span class="ad-empty-line">ยังไม่มีผู้เช็คอิน</span>') +
        '</div></div>' +
      '</div>';
  }

  /* ================= 4) แบบประเมิน ================= */
  function renderSurvey() {
    /* กระจายดาว — ปัดคะแนนเฉลี่ยรายคนเป็นจำนวนเต็ม */
    var dist = [5, 4, 3, 2, 1].map(function (star) {
      return { label: star + ' ดาว', n: evals.filter(function (e) { return Math.round(e.average) === star; }).length };
    });

    var topics = (mock.evaluationTopics || []).map(function (t) {
      var scores = evals.map(function (e) { return (e.topicScores || {})[t.key]; }).filter(function (v) { return typeof v === 'number'; });
      var avg = scores.length ? scores.reduce(function (a, b) { return a + b; }, 0) / scores.length : 0;
      return { label: t.label, score: Math.round(avg * 10) / 10 };
    }).sort(function (a, b) { return b.score - a.score; });

    /* ความเห็นไม่ระบุตัวตน — แสดงเป็น "ผู้ตอบ #n" เท่านั้น ไม่ดึงชื่อจากชุดข้อมูล */
    var comments = evals.filter(function (e) { return e.feedback; }).slice(0, 6);

    var maxD = maxOf(dist);
    var rate = pct(evals.length, checkedIn.length);

    return '<div class="ad-layout">' +
      '<div class="ad-main">' +
        '<div class="card ad-chart-card">' +
          '<span class="ad-card-title">คะแนนความพึงพอใจ</span>' +
          '<div class="ad-score-row">' +
            '<div class="ad-score-big">' +
              '<span class="ad-score-num">' + (avgScore ? avgScore.toFixed(1) : '—') + '</span>' +
              '<span class="ad-score-of">จาก 5.0</span>' +
              '<span class="ad-score-count">' + evals.length + ' คนตอบ</span>' +
            '</div>' +
            '<div class="ad-bars ad-score-dist">' + dist.map(function (d) {
              return barRow(d.label, d.n + ' คน', pct(d.n, maxD), d.n === maxD && maxD > 0 ? 'is-peak' : '');
            }).join('') + '</div>' +
          '</div>' +
        '</div>' +

        '<div class="card ad-chart-card">' +
          '<div class="ad-card-head"><span class="ad-card-title">ความเห็นจากผู้เข้าร่วม</span>' +
          '<span class="ad-card-sub">ไม่ระบุตัวตน</span></div>' +
          '<div class="ad-comments">' + (comments.length ? comments.map(function (c, i) {
            var low = c.average < 4;
            return '<div class="ad-comment">' +
              '<div class="ad-comment-head">' +
                '<span class="ad-comment-who">ผู้ตอบ #' + (i + 1) + ' · ' + esc(window.TFC.formatThaiDate(c.answeredAt.slice(0, 10))) + '</span>' +
                '<span class="ad-comment-score' + (low ? ' is-low' : '') + '">' + c.average.toFixed(1) + '</span>' +
              '</div>' +
              '<p class="ad-comment-text">' + esc(c.feedback) + '</p>' +
              '</div>';
          }).join('') : '<span class="ad-empty-line">ยังไม่มีความเห็น</span>') + '</div>' +
        '</div>' +
      '</div>' +

      '<aside class="ad-side">' +
        '<div class="card ad-panel">' +
          '<span class="ad-card-title">คะแนนรายหัวข้อ</span>' +
          '<div class="ad-bars">' + topics.map(function (t) {
            var low = t.score < 4;
            return '<div class="ad-bar-row">' +
              '<div class="ad-bar-head"><span class="ad-bar-label">' + esc(t.label) + '</span>' +
              '<span class="ad-bar-value' + (low ? ' is-low' : '') + '">' + t.score.toFixed(1) + '</span></div>' +
              '<div class="ad-bar"><span class="' + (low ? 'is-full' : 'is-peak') + '" style="width: ' + pct(t.score, 5).toFixed(1) + '%"></span></div>' +
              '</div>';
          }).join('') + '</div>' +
        '</div>' +
        '<div class="card ad-panel">' +
          '<span class="ad-card-title">อัตราการตอบ</span>' +
          '<div class="ad-rate"><span class="ad-rate-num">' + Math.round(rate) + '%</span>' +
          '<span class="ad-rate-hint">' + evals.length + ' จากผู้เข้าร่วม ' + checkedIn.length + ' คน</span></div>' +
          '<div class="ad-bar"><span class="is-peak" style="width: ' + rate.toFixed(1) + '%"></span></div>' +
        '</div>' +
      '</aside>' +
    '</div>';
  }

  /* ================= 5) รายงาน ================= */
  function successParts() {
    return [
      { label: 'ที่นั่งที่ขายได้', value: regs.length + '/' + totalCap, pct: pct(regs.length, totalCap), w: 0.25 },
      { label: 'เข้าร่วมจริง', value: checkedIn.length + '/' + regs.length, pct: pct(checkedIn.length, regs.length), w: 0.30 },
      { label: 'ความพึงพอใจ', value: (avgScore ? avgScore.toFixed(1) : '0.0') + '/5', pct: pct(avgScore, 5), w: 0.30 },
      { label: 'ตอบแบบประเมิน', value: evals.length + '/' + checkedIn.length, pct: pct(evals.length, checkedIn.length), w: 0.15 }
    ];
  }

  function dailyRegs() {
    var map = {};
    regs.forEach(function (r) {
      if (!r.registeredAt) return;
      var d = r.registeredAt.slice(0, 10);
      map[d] = (map[d] || 0) + 1;
    });
    var keys = Object.keys(map).sort();
    return keys.map(function (k) {
      var d = new Date(k);
      return { label: d.getDate() + ' ' + ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'][d.getMonth()], n: map[k], iso: k };
    });
  }

  function renderReport() {
    var parts = successParts();
    /* สูตรคะแนนความสำเร็จ ตามที่กำหนด — ไม่มีค่าใดถูกพิมพ์ทับไว้ */
    var score = Math.round(parts.reduce(function (s, p) { return s + p.pct * p.w; }, 0));
    var grade = score >= 85 ? 'ดีมาก' : (score >= 70 ? 'ดี' : 'ต้องปรับปรุง');

    var daily = dailyRegs();
    var peak = daily.reduce(function (m, d) { return d.n > (m ? m.n : 0) ? d : m; }, null);
    var rangeText = daily.length
      ? daily[0].label + ' – ' + daily[daily.length - 1].label + ' · ' + daily.length + ' วันก่อนกิจกรรม'
      : 'ยังไม่มีข้อมูล';

    return '<div class="ad-report">' +

      '<div class="card ad-chart-card">' +
        '<div class="ad-card-head">' +
          '<span class="ad-card-title">วิเคราะห์ผลกิจกรรม</span>' +
          '<button type="button" class="btn btn-outline btn-sm" id="ad-advice-toggle">' +
            (state.showAdvice ? 'ซ่อนข้อเสนอแนะ' : 'ดูข้อเสนอแนะและแนวทางโฆษณา') + '</button>' +
        '</div>' +
        '<div class="ad-success">' +
          '<div class="ad-success-score">' +
            '<span class="ad-success-num">' + score + '</span>' +
            '<span class="ad-success-of">/ 100</span>' +
            '<span class="ad-success-grade' + (score >= 85 ? ' is-good' : (score >= 70 ? ' is-ok' : ' is-warn')) + '">' + grade + '</span>' +
          '</div>' +
          '<div class="ad-bars ad-success-parts">' + parts.map(function (p) {
            var tone = p.pct >= 85 ? 'is-strong' : (p.pct >= 70 ? 'is-peak' : 'is-full');
            return '<div class="ad-bar-row">' +
              '<div class="ad-bar-head">' +
                '<span class="ad-bar-label">' + esc(p.label) + '<span class="ad-weight">น้ำหนัก ' + Math.round(p.w * 100) + '%</span></span>' +
                '<span class="ad-bar-value">' + esc(p.value) + '</span>' +
              '</div>' +
              '<div class="ad-bar"><span class="' + tone + '" style="width: ' + p.pct.toFixed(1) + '%"></span></div>' +
              '</div>';
          }).join('') + '</div>' +
        '</div>' +
        (state.showAdvice ? adviceHtml() : '') +
      '</div>' +

      funnelHtml() +
      vBarCard('ยอดลงทะเบียนรายวัน', daily, rangeText + (peak ? ' · สูงสุด ' + peak.label + ' ' + peak.n + ' คน' : '')) +
      '<div class="ad-grid2">' + genderCard() + barListCard('ช่วงอายุ', ageRows(), 'คน') + '</div>' +
      '<div class="ad-grid2">' +
        barListCard('ช่องทางที่รู้จักกิจกรรม', channelRows(), 'คน') +
        barListCard('กิจกรรมที่สนใจเพิ่มเติม', toRows(countBy(regs, function (r) { return r.interests; })), 'คน') +
      '</div>' +
    '</div>';
  }

  /* funnel — ขั้นแรกต้องใช้ page view ของหน้าลงทะเบียน ซึ่งระบบยังไม่ได้เก็บ
     จึงแสดงว่ายังไม่มีข้อมูลแทนการใส่ตัวเลขสมมติ และคิด % ของขั้นถัดไปจากผู้ลงทะเบียนแทน */
  function funnelHtml() {
    var steps = [
      { label: 'เปิดหน้ากิจกรรม', value: null, hint: 'ระบบยังไม่ได้เก็บ page view ของหน้าลงทะเบียน' },
      { label: 'ลงทะเบียน', value: regs.length, hint: 'จากที่รับได้ ' + totalCap + ' ที่นั่ง', base: regs.length },
      { label: 'เข้าร่วมจริง', value: checkedIn.length, hint: Math.round(pct(checkedIn.length, regs.length)) + '% ของผู้ลงทะเบียน', base: regs.length },
      { label: 'ตอบแบบประเมิน', value: evals.length, hint: Math.round(pct(evals.length, checkedIn.length)) + '% ของผู้เข้าร่วม', base: regs.length }
    ];

    return '<div class="card ad-chart-card">' +
      '<div class="ad-card-head"><span class="ad-card-title">เส้นทางผู้เข้าร่วม</span>' +
      '<span class="ad-card-sub">คิดจากผู้ลงทะเบียนเป็นฐาน 100%</span></div>' +
      '<div class="ad-funnel">' + steps.map(function (s) {
        if (s.value === null) {
          return '<div class="ad-funnel-step is-missing">' +
            '<div class="ad-bar-head"><span class="ad-bar-label">' + esc(s.label) + '</span>' +
            '<span class="ad-bar-value is-missing">ยังไม่มีข้อมูล</span></div>' +
            '<div class="ad-bar"><span class="is-missing" style="width: 100%"></span></div>' +
            '<span class="ad-funnel-hint">' + esc(s.hint) + '</span></div>';
        }
        return '<div class="ad-funnel-step">' +
          '<div class="ad-bar-head"><span class="ad-bar-label">' + esc(s.label) + '</span>' +
          '<span class="ad-bar-value">' + s.value + ' คน</span></div>' +
          '<div class="ad-bar"><span class="is-peak" style="width: ' + pct(s.value, s.base).toFixed(1) + '%"></span></div>' +
          '<span class="ad-funnel-hint">' + esc(s.hint) + '</span></div>';
      }).join('') + '</div></div>';
  }

  /* ข้อเสนอแนะ — ข้อความคงที่ แต่ตัวเลขที่อ้างถึงคำนวณจากข้อมูลจริงทั้งหมด */
  function adviceHtml() {
    var topics = (mock.evaluationTopics || []).map(function (t) {
      var scores = evals.map(function (e) { return (e.topicScores || {})[t.key]; }).filter(function (v) { return typeof v === 'number'; });
      return { label: t.label, score: scores.length ? scores.reduce(function (a, b) { return a + b; }, 0) / scores.length : 0 };
    }).sort(function (a, b) { return a.score - b.score; });
    var worst = topics[0] || { label: '-', score: 0 };

    var channels = channelRows();
    var topChannel = channels[0] || { label: '-', n: 0 };
    var interests = toRows(countBy(regs, function (r) { return r.interests; }));
    var topInterest = interests[0] || { label: '-', n: 0 };
    /* ข้อเสนอแนะต้องใช้ชื่อช่วงอายุเต็ม และเรียงตามจำนวน ไม่ใช่ลำดับอายุ */
    var ages = toRows(countBy(regs, function (r) { return r.ageRange; }));
    var topAge = ages[0] || { label: '-', n: 0 };
    var genders = toRows(countBy(regs, function (r) { return r.gender; }));
    var topGender = genders[0] || { label: '-', n: 0 };
    var times = regTimeSlots().slice().sort(function (a, b) { return b.n - a.n; });
    var topTime = times[0] || { label: '-', n: 0 };
    var days = regWeekdays().slice().sort(function (a, b) { return b.n - a.n; });
    var topDay = days[0] || { label: '-', n: 0 };

    var emptiest = sessions.map(function (s, i) {
      var n = regs.filter(function (r) { return r.session === s.date; }).length;
      return { label: 'รอบที่ ' + (i + 1), n: n, cap: s.capacity || 0, left: (s.capacity || 0) - n };
    }).sort(function (a, b) { return b.left - a.left; })[0];

    var insights = [
      { tone: 'warn', metric: worst.label + ' ' + worst.score.toFixed(1) + '/5', title: 'หัวข้อที่คะแนนต่ำสุด', text: 'ทบทวนจุดนี้ก่อนจัดรุ่นถัดไป' },
      emptiest && emptiest.left > 0
        ? { tone: 'warn', metric: emptiest.label + ' เหลือ ' + emptiest.left + ' ที่', title: 'รอบที่ยังไม่เต็ม', text: 'ย้ายเวลาให้เหมาะกับกลุ่มเป้าหมาย หรือรวมรอบ' }
        : { tone: 'good', metric: 'ทุกรอบเต็ม', title: 'ที่นั่งขายหมด', text: 'พิจารณาเพิ่มรอบหรือเพิ่มที่นั่งในรุ่นถัดไป' },
      waitingSlip.length
        ? { tone: 'warn', metric: waitingSlip.length + ' ใบ · ' + baht(waitingSlip.length * (activity.fee || 0)), title: 'สลิปยังไม่ยืนยัน', text: 'ยืนยันให้ครบก่อนวันงาน ยอดจึงเข้ารายรับ' }
        : { tone: 'good', metric: 'ตรวจครบแล้ว', title: 'ไม่มีสลิปค้าง', text: 'ยอดรายรับเป็นตัวเลขสุดท้ายแล้ว' },
      { tone: 'good', metric: topChannel.label + ' ' + Math.round(pct(topChannel.n, regs.length)) + '%', title: 'ช่องทางที่ได้ผลสุด', text: 'รุ่นหน้าลงโพสต์ช่องทางนี้ก่อน 14 วัน' },
      { tone: 'good', metric: topInterest.label + ' ' + Math.round(pct(topInterest.n, regs.length)) + '%', title: 'หัวข้อที่อยากได้ต่อ', text: 'ตั้งกิจกรรมถัดไปให้ตรงกับความสนใจนี้' },
      { tone: 'info', metric: topTime.label + ' = ' + topTime.n + ' คน', title: 'ช่วงเวลาที่คนสมัครมากสุด', text: 'ปล่อยโพสต์และเปิดรับสมัครในช่วงนี้' }
    ];

    var targets = [
      { label: 'แพลตฟอร์มหลัก', value: topChannel.label, hint: topChannel.n + '/' + regs.length + ' คน (' + Math.round(pct(topChannel.n, regs.length)) + '%)' + (channels[1] ? ' · รองมา ' + channels[1].label + ' ' + channels[1].n + ' คน' : '') },
      { label: 'อายุที่ยิง', value: topAge.label, hint: 'กลุ่มใหญ่สุด ' + topAge.n + ' คน' + (ages[1] ? ' · รวมกับ ' + ages[1].label + ' = ' + Math.round(pct(topAge.n + ages[1].n, regs.length)) + '% ของทั้งหมด' : '') },
      { label: 'เพศ', value: topGender.label + ' ' + Math.round(pct(topGender.n, regs.length)) + '%', hint: 'ตั้งน้ำหนักตามสัดส่วนที่ลงทะเบียนจริง' },
      { label: 'พื้นที่', value: activity.area || '-', hint: 'ยิงรัศมีรอบพื้นที่จัดกิจกรรม' },
      { label: 'ความสนใจที่ใช้ targeting', value: interests.slice(0, 2).map(function (i) { return i.label; }).join(' / ') || '-', hint: interests.slice(0, 2).map(function (i) { return Math.round(pct(i.n, regs.length)) + '%'; }).join(' และ ') + ' เลือกไว้ในแบบลงทะเบียน' },
      { label: 'ช่วงเวลายิงโฆษณา', value: topTime.label + ' และวัน' + topDay.label, hint: 'ช่วงที่คนลงทะเบียนจริงมากสุด ' + topTime.n + ' คน และ ' + topDay.n + ' คน' }
    ];

    var actions = [
      { tone: 'good', metric: channels.slice(0, 2).map(function (c) { return c.label + ' ' + Math.round(pct(c.n, regs.length)) + '%'; }).join(' · '), title: 'แบ่งงบตามสัดส่วนจริง', text: 'ทุ่มช่องทางอันดับ 1 เป็นหลัก อันดับ 2 เสริม' },
      { tone: 'good', metric: 'ผู้เข้าร่วมเดิม ' + checkedIn.length + ' คน', title: 'ทำ referral', text: 'ให้ผู้เข้าร่วมเดิมแชร์โพสต์แลกสิทธิ์จองก่อน' },
      { tone: 'info', metric: emptiest && emptiest.left > 0 ? emptiest.label + ' เหลือ ' + emptiest.left + ' ที่' : 'ทุกรอบเต็ม', title: 'ยิงเจาะรอบที่ยังว่าง', text: 'แยกชุดโฆษณาเฉพาะรอบนั้น 7 วันสุดท้าย' },
      { tone: 'info', metric: 'คะแนน ' + (avgScore ? avgScore.toFixed(1) : '-') + '/5', title: 'ครีเอทีฟที่ควรใช้', text: 'ใช้ประโยคจากรีวิวคะแนนสูงเป็นแคปชัน' }
    ];

    return '<div class="ad-advice">' +
      '<div class="ad-advice-block">' +
        '<span class="ad-card-title">ข้อเสนอแนะ</span>' +
        '<div class="ad-insights">' + insights.map(function (i, n) {
          return '<div class="ad-insight is-' + i.tone + '">' +
            '<span class="ad-insight-no">' + (n + 1) + '</span>' +
            '<span class="ad-insight-metric">' + esc(i.metric) + '</span>' +
            '<span class="ad-insight-title">' + esc(i.title) + '</span>' +
            '<span class="ad-insight-text">' + esc(i.text) + '</span>' +
            '</div>';
        }).join('') + '</div>' +
      '</div>' +
      '<div class="ad-advice-block">' +
        '<span class="ad-card-title">แนวทางยิงโฆษณาโซเชียล</span>' +
        '<div class="ad-targets">' + targets.map(function (t) {
          return '<div class="ad-target">' +
            '<span class="ad-target-label">' + esc(t.label) + '</span>' +
            '<span class="ad-target-value">' + esc(t.value) + '</span>' +
            '<span class="ad-target-hint">' + esc(t.hint) + '</span>' +
            '</div>';
        }).join('') + '</div>' +
        '<div class="ad-insights ad-actions">' + actions.map(function (i) {
          return '<div class="ad-insight is-' + i.tone + '">' +
            '<span class="ad-insight-metric">' + esc(i.metric) + '</span>' +
            '<span class="ad-insight-title">' + esc(i.title) + '</span>' +
            '<span class="ad-insight-text">' + esc(i.text) + '</span>' +
            '</div>';
        }).join('') + '</div>' +
      '</div>' +
    '</div>';
  }

  /* ================= เรนเดอร์ + เหตุการณ์ ================= */
  function render() {
    renderTabs();
    var html = state.tab === 'ภาพรวม' ? renderOverview()
      : state.tab === 'ผู้เข้าร่วม' ? renderPeople()
      : state.tab === 'Check-in' ? renderCheckin()
      : state.tab === 'แบบประเมิน' ? renderSurvey()
      : renderReport();
    $('ad-panel').innerHTML = html;
  }

  document.addEventListener('click', function (e) {
    var tab = e.target.closest('[data-tab]');
    if (tab) { state.tab = tab.getAttribute('data-tab'); return render(); }

    var view = e.target.closest('[data-view]');
    if (view) {
      var parts = view.getAttribute('data-view').split(':');
      state[parts[0]] = parts[1];
      return render();
    }

    if (e.target.closest('#ad-advice-toggle')) { state.showAdvice = !state.showAdvice; return render(); }

    if (e.target.closest('#ad-export')) {
      var rows = regs.map(function (r, i) {
        return [i + 1, r.code, r.name, r.phone, r.email, r.gender, r.ageRange, r.occupation,
          r.sourceChannel, roundName(r), r.payment, r.checkedIn ? r.checkedInAt : ''];
      });
      window.TFC.exportCsv('รายงาน-' + activity.id + '.csv',
        ['#', 'รหัส', 'ชื่อ-นามสกุล', 'เบอร์โทร', 'อีเมล', 'เพศ', 'ช่วงอายุ', 'อาชีพ',
         'ช่องทางที่รู้จัก', 'รอบ', 'การชำระเงิน', 'เวลาเช็คอิน'], rows);
      return;
    }

    var qr = e.target.closest('[data-qr]');
    if (qr && window.TFC.showToast) window.TFC.showToast('ดาวน์โหลด QR ' + qr.getAttribute('data-qr') + ' เรียบร้อย', 'success');
  });

  renderHeader();
  render();
})();
