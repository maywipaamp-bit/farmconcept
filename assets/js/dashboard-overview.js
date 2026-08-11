/* TheFarmConcept — หน้าแดชบอร์ดหลัก (admin/dashboard.html)
   ไฟล์นี้ทำหน้าที่เดียวคือ "วาดหน้าจอจากข้อมูลที่ได้รับ" ไม่ถือข้อมูลเอง

   TODO: เชื่อม API จริง — จุดดึงข้อมูลอยู่ที่ TFC.getDashboardOverview() ใน dashboard-data.js
   ไฟล์นี้รับเป็น Promise อยู่แล้ว เปลี่ยนไปเรียก API จริงได้โดยไม่ต้องแก้อะไรตรงนี้

   สิ่งที่หน้าจอคำนวณเอง (derive จากชุดข้อมูลเดียวกัน จึงไม่มีทางขัดกับตัวเลขที่ส่งมา)
   - เดือนที่มีผู้เข้าร่วมมากที่สุด และความสูงของแท่ง
   - เปอร์เซ็นต์ของแต่ละกลุ่มเป้าหมาย (ปรับให้รวมได้ 100% พอดีเสมอ)
   - เปอร์เซ็นต์ที่นั่งที่ถูกจองของแต่ละกิจกรรม
   นอกนั้นแสดงตามที่เซิร์ฟเวอร์ส่งมาตรง ๆ */
(function () {
  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  var MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

  /* ---------- ตัวช่วย ---------- */
  function thaiDateTime(iso) {
    var p = String(iso || '').split('T');
    var d = (p[0] || '').split('-');
    if (d.length !== 3) return '';
    var time = (p[1] || '').slice(0, 5);
    return Number(d[2]) + ' ' + MONTHS[Number(d[1]) - 1] + ' ' + (Number(d[0]) + 543) +
      (time ? ' เวลา ' + time + ' น.' : '');
  }

  function num(n) { return Number(n || 0).toLocaleString('th-TH'); }

  function emptyBox(title, text) {
    return '<div class="dash-empty">' +
      '<span class="dash-empty-title">' + esc(title) + '</span>' +
      '<span class="dash-empty-text">' + esc(text) + '</span>' +
      '</div>';
  }

  /* ---------- A. หัวหน้า ----------
     บรรทัด "ข้อมูล ณ ..." ถูกถอดออกจากหน้าแล้ว ตามกติกาที่ว่าใต้ชื่อหน้าไม่มีคำอธิบาย
     เขียนต่อเมื่อยังมีที่ให้เขียน จะได้ใส่กลับได้ทันทีถ้าเอาบรรทัดนั้นคืนมา */
  function renderHead(data) {
    var updated = $('dash-updated');
    if (updated) updated.textContent = 'ข้อมูล ณ ' + thaiDateTime(data.generated_at);
  }

  /* ---------- B. KPI 4 ใบ ---------- */
  /* บรรทัดเปรียบเทียบสื่อด้วยสี: เขียว = ดีขึ้น · ส้ม = ลดลง · เทา = เท่าเดิม */
  function changeLine(pct) {
    var n = Number(pct);
    if (!isFinite(n) || n === 0) return { cls: 'is-flat', text: 'เท่าเดือนก่อน' };
    if (n > 0) return { cls: 'is-up', text: '+' + n + '% จากเดือนก่อน' };
    return { cls: 'is-down', text: '−' + Math.abs(n) + '% จากเดือนก่อน' };
  }

  function renderKpis(list) {
    if (!list.length) {
      $('dash-kpis').innerHTML = emptyBox('ยังไม่มีตัวเลขสรุป', 'ระบบจะสรุปให้อัตโนมัติเมื่อเริ่มมีข้อมูล');
      return;
    }
    $('dash-kpis').innerHTML = list.map(function (k) {
      var c = changeLine(k.change_pct);
      return '<a class="dash-kpi" href="' + esc(k.target_path) + '">' +
        '<span class="dash-kpi-top">' +
          '<span class="dash-kpi-label">' + esc(k.label) + '</span>' +
          '<svg class="dash-kpi-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>' +
        '</span>' +
        '<span class="dash-kpi-value">' + num(k.value) +
          '<span class="dash-kpi-unit">' + esc(k.unit) + '</span>' +
        '</span>' +
        '<span class="dash-kpi-change ' + c.cls + '">' + esc(c.text) + '</span>' +
        '</a>';
    }).join('');
  }

  /* ---------- C1. กราฟแท่งรายเดือน ---------- */
  function renderTrend(list) {
    var box = $('dash-trend'), note = $('dash-trend-note');

    if (!list.length) {
      box.innerHTML = emptyBox('ยังไม่มีข้อมูลผู้เข้าร่วม', 'กราฟจะขึ้นเมื่อมีผู้เข้าร่วมกิจกรรมแล้ว');
      note.textContent = '';
      return;
    }

    /* เดือนที่มากที่สุดหาเอง ไม่ได้ทำเครื่องหมายไว้ในข้อมูล ตัวเลขกับสีจึงตรงกันเสมอ */
    var top = list.reduce(function (a, b) { return b.registered > a.registered ? b : a; }, list[0]);
    var max = top.registered || 1;

    note.textContent = 'ย้อนหลัง ' + list.length + ' เดือน · เดือนที่มากที่สุด ' +
      top.label + ' ' + num(top.registered) + ' คน';

    box.innerHTML = list.map(function (m) {
      var isTop = m.month === top.month;
      /* ขั้นต่ำ 4% กันแท่งที่ค่าน้อยมากหายไปจนดูเหมือนไม่มีข้อมูล */
      var h = Math.max(4, Math.round((m.registered / max) * 100));
      return '<div class="dash-bar' + (isTop ? ' is-top' : '') + '"' +
        ' title="' + esc(m.label + ' · ผู้เข้าร่วม ' + num(m.registered) + ' คน') + '">' +
        '<span class="dash-bar-value">' + num(m.registered) + '</span>' +
        '<span class="dash-bar-track"><span class="dash-bar-fill" style="height:' + h + '%"></span></span>' +
        '<span class="dash-bar-label">' + esc(m.label) + '</span>' +
        '</div>';
    }).join('');
  }

  /* ---------- C2. Donut กลุ่มเป้าหมาย ---------- */
  /* ปัดเศษแล้วยกส่วนต่างไปให้กลุ่มที่ใหญ่ที่สุด ผลรวมจึงเป็น 100% พอดีทุกครั้ง
     ถ้าปล่อยให้ปัดตรง ๆ ผลรวมจะเป็น 99% หรือ 101% แล้ววงกับ legend ไม่ตรงกัน */
  function withPercent(groups) {
    var total = groups.reduce(function (s, g) { return s + (g.count || 0); }, 0);
    if (!total) return { total: 0, rows: [] };

    var rows = groups.map(function (g) {
      return { key: g.key, label: g.label, count: g.count || 0, pct: Math.round((g.count || 0) / total * 100) };
    });
    var drift = 100 - rows.reduce(function (s, r) { return s + r.pct; }, 0);
    if (drift) {
      var biggest = rows.reduce(function (a, b) { return b.count > a.count ? b : a; }, rows[0]);
      biggest.pct += drift;
    }
    return { total: total, rows: rows };
  }

  function renderGroups(groups) {
    var box = $('dash-groups');
    var data = withPercent(groups);

    if (!data.rows.length) {
      box.innerHTML = emptyBox('ยังไม่มีข้อมูลกลุ่มเป้าหมาย', 'สัดส่วนจะขึ้นเมื่อมีผู้เข้าร่วมที่ระบุกลุ่มแล้ว');
      return;
    }

    /* สร้าง conic-gradient จาก % ที่แสดงจริง วงกับ legend จึงตรงกันเป๊ะ */
    var at = 0;
    var stops = data.rows.map(function (r, i) {
      var from = at, to = at + r.pct;
      at = to;
      return 'var(--dash-slice-' + ((i % 4) + 1) + ') ' + from + '% ' + to + '%';
    }).join(', ');

    box.innerHTML =
      '<div class="dash-donut-wrap">' +
        '<div class="dash-donut" style="background: conic-gradient(' + stops + ')" role="img"' +
          ' aria-label="สัดส่วนผู้เข้าร่วมตามกลุ่มเป้าหมาย รวม ' + num(data.total) + ' คน">' +
          '<span class="dash-donut-hole"><span class="dash-donut-total">100 %</span></span>' +
        '</div>' +
        '<div class="dash-legend">' +
          data.rows.map(function (r, i) {
            return '<span class="dash-legend-row">' +
              '<span class="dash-legend-dot" style="background: var(--dash-slice-' + ((i % 4) + 1) + ')"></span>' +
              '<span class="dash-legend-label">' + esc(r.label) + '</span>' +
              '<span class="dash-legend-pct">' + r.pct + '%</span>' +
              '</span>';
          }).join('') +
        '</div>' +
      '</div>';
  }

  /* ---------- D1. สิ่งที่ต้องติดตาม ---------- */
  function renderActions(list) {
    var box = $('dash-actions'), badge = $('dash-actions-count');

    var urgent = list.filter(function (a) { return a.severity === 'urgent'; }).length;
    badge.textContent = list.length ? num(list.length) + ' รายการ' : '—';
    badge.classList.toggle('is-urgent', urgent > 0);
    badge.hidden = !list.length;

    if (!list.length) {
      box.innerHTML = emptyBox('ไม่มีสิ่งที่ต้องติดตาม', 'ทุกอย่างอยู่ในกำหนดแล้ว');
      return;
    }

    box.innerHTML = list.map(function (a) {
      return '<a class="dash-action" href="' + esc(a.target_path) + '">' +
        '<span class="dash-dot is-' + esc(a.severity) + '" aria-hidden="true"></span>' +
        '<span class="dash-action-text">' +
          '<span class="dash-action-label">' + esc(a.label) + '</span>' +
          '<span class="dash-action-detail">' + esc(a.detail) + '</span>' +
        '</span>' +
        '<svg class="dash-action-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>' +
        '</a>';
    }).join('');
  }

  /* ---------- D2. กิจกรรมที่จะจัดขึ้นเร็ว ๆ นี้ ---------- */
  var SEAT_ALERT_PCT = 90;   /* เต็มหรือใกล้เต็ม ต้องเห็นก่อนที่จะรับเพิ่มไม่ได้ */

  function renderUpcoming(list) {
    var box = $('dash-upcoming');

    if (!list.length) {
      box.innerHTML = emptyBox('ยังไม่มีกิจกรรมที่จะจัด', 'สร้างกิจกรรมใหม่เพื่อเริ่มเปิดรับสมัคร');
      return;
    }

    box.innerHTML = list.map(function (a) {
      var d = String(a.start_date || '').split('-');
      var day = d.length === 3 ? Number(d[2]) : '—';
      var mon = d.length === 3 ? MONTHS[Number(d[1]) - 1] : '';
      var cap = a.capacity || 0;
      var pct = cap ? Math.min(100, Math.round((a.registered || 0) / cap * 100)) : 0;
      var tight = cap > 0 && pct >= SEAT_ALERT_PCT;

      return '<div class="dash-up' + (tight ? ' is-tight' : '') + '">' +
        '<span class="dash-up-date"><span class="dash-up-day">' + day + '</span>' +
          '<span class="dash-up-mon">' + esc(mon) + '</span></span>' +
        '<span class="dash-up-text">' +
          '<span class="dash-up-name">' + esc(a.name) + '</span>' +
          '<span class="dash-up-area">' + esc(a.area) + '</span>' +
        '</span>' +
        '<span class="dash-up-seat">' +
          '<span class="dash-up-count">' + num(a.registered) + '/' + num(cap) + '</span>' +
          '<span class="dash-up-bar"><span style="width:' + pct + '%"></span></span>' +
        '</span>' +
        '</div>';
    }).join('');
  }

  /* ---------- เริ่มทำงาน ---------- */
  function render(data) {
    renderHead(data);
    renderKpis(data.kpis || []);
    renderTrend(data.registration_trend || []);
    renderGroups(data.target_groups || []);
    renderActions(data.action_items || []);
    renderUpcoming(data.upcoming_activities || []);
  }

  /* TODO: เชื่อม API จริง — จุดเรียกใช้ข้อมูลของหน้านี้อยู่บรรทัดถัดไปที่เดียว */
  window.TFC.getDashboardOverview().then(render)['catch'](function () {
    if (window.TFC.showToast) window.TFC.showToast('โหลดข้อมูลแดชบอร์ดไม่สำเร็จ', 'danger');
  });
})();
