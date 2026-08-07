/* TheFarmConcept — Activity module shared helpers
   ใช้ร่วมกันระหว่าง pages/activities/list.html, create.html, edit.html, detail.html
   เพื่อไม่ให้เขียน logic ซ้ำในแต่ละหน้า (กฎ Reuse ใน AGENTS.md)

   โหลดหลัง mock-data.js (ต้องใช้ TFC.escapeHtml / TFC.badgeClassOf) และหลัง app.js ไม่ได้จำเป็น
   เพราะเรียก TFC.formatThaiDate ตอน runtime เท่านั้น

   API
   ---
   TFC.activity.currentId()                        -> อ่าน ?id= จาก URL (fallback: กิจกรรมแรกใน mock)
   TFC.activity.get(id)                            -> object กิจกรรม
   TFC.activity.schedules(activity)                -> [{ date, timeStart, timeEnd, capacity, registered }]
   TFC.activity.dateLabel(activity)                -> '10 ส.ค. 2569' หรือ '24 ส.ค. 2569 – 7 ก.ย. 2569'
   TFC.activity.statusSelectHTML(kind, value, opts)-> <select> สถานะแบบมีสี (kind: activity|payment|checkin)
   TFC.activity.feeLabel(activity)                 -> 'ไม่มีค่าใช้จ่าย' | '200 บาท'
   TFC.countBy(rows, keyFn)                        -> { key: จำนวน } เรียงตามลำดับที่พบ
   TFC.exportCsv(filename, headers, rows)          -> ดาวน์โหลดไฟล์ CSV (UTF-8 BOM เปิดด้วย Excel ได้) */
window.TFC = window.TFC || {};

(function () {
  var STATUS_LISTS = {
    activity: 'activityStatuses',
    payment: 'paymentStatuses',
    checkin: 'checkinStatuses'
  };

  function listOf(kind) {
    return (window.TFC_MOCK && window.TFC_MOCK[STATUS_LISTS[kind]]) || [];
  }

  /* badge-success -> is-success (คลาสสีของ .status-select ใน components.css) */
  function toneOf(kind, value) {
    return window.TFC.badgeClassOf(listOf(kind), value).replace('badge-', 'is-');
  }

  window.TFC.countBy = function (rows, keyFn) {
    var result = {};
    (rows || []).forEach(function (row) {
      var keys = keyFn(row);
      (Array.isArray(keys) ? keys : [keys]).forEach(function (key) {
        if (key == null || key === '') return;
        result[key] = (result[key] || 0) + 1;
      });
    });
    return result;
  };

  window.TFC.exportCsv = function (filename, headers, rows) {
    var escapeCell = function (cell) {
      var text = cell == null ? '' : String(cell);
      return '"' + text.replace(/"/g, '""') + '"';
    };
    var csv = [headers].concat(rows).map(function (row) {
      return row.map(escapeCell).join(',');
    }).join('\r\n');

    /* BOM เพื่อให้ Excel อ่านภาษาไทยถูกต้อง */
    var blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    if (window.TFC.showToast) window.TFC.showToast('ส่งออกไฟล์ ' + filename + ' เรียบร้อย', 'success');
  };

  window.TFC.activity = {
    currentId: function () {
      var id = new URLSearchParams(window.location.search).get('id');
      var activities = (window.TFC_MOCK && window.TFC_MOCK.activities) || [];
      var exists = activities.some(function (a) { return a.id === id; });
      return exists ? id : (activities[0] && activities[0].id);
    },

    get: function (id) {
      return ((window.TFC_MOCK && window.TFC_MOCK.activities) || []).filter(function (a) {
        return a.id === id;
      })[0];
    },

    /* รอบกิจกรรม: อ่านจาก activitySessions แล้วแตก '09:00 - 12:00' เป็นเวลาเริ่ม/สิ้นสุด */
    schedules: function (activity) {
      if (!activity) return [];
      var sessions = (window.TFC_MOCK.activitySessions || {})[activity.id] || [];
      return sessions.map(function (session) {
        var parts = String(session.time || '').split('-');
        return {
          date: session.date,
          timeStart: (parts[0] || '').trim(),
          timeEnd: (parts[1] || '').trim(),
          capacity: session.capacity,
          registered: session.registered,
          location: session.location
        };
      });
    },

    dateLabel: function (activity) {
      if (!activity || !activity.startDate) return '-';
      var start = window.TFC.formatThaiDate(activity.startDate);
      if (!activity.endDate || activity.endDate === activity.startDate) return start;
      return start + ' – ' + window.TFC.formatThaiDate(activity.endDate);
    },

    feeLabel: function (activity) {
      if (!activity || !activity.hasFee || !activity.fee) return 'ไม่มีค่าใช้จ่าย';
      return Number(activity.fee).toLocaleString('th-TH') + ' บาท';
    },

    /* Dropdown สถานะแบบมีสี (inline edit) — ใช้ทั้งตาราง Index และตารางผู้ลงทะเบียน */
    statusSelectHTML: function (kind, value, opts) {
      opts = opts || {};
      var options = listOf(kind).map(function (item) {
        var selected = item.value === value ? ' selected' : '';
        return '<option value="' + window.TFC.escapeHtml(item.value) + '"' + selected + '>' +
          window.TFC.escapeHtml(item.value) + '</option>';
      }).join('');

      return '<select class="select status-select ' + toneOf(kind, value) + '"' +
        ' data-status-select="' + kind + '"' +
        (opts.rowId ? ' data-row-id="' + window.TFC.escapeHtml(opts.rowId) + '"' : '') +
        ' aria-label="' + window.TFC.escapeHtml(opts.ariaLabel || 'เปลี่ยนสถานะ') + '">' +
        options + '</select>';
    }
  };

  /* เปลี่ยนสีตามค่าที่เลือกทันที + แจ้ง toast (mock: ยังไม่บันทึกลงฐานข้อมูลจริง) */
  document.addEventListener('change', function (e) {
    var select = e.target.closest('[data-status-select]');
    if (!select) return;
    var kind = select.getAttribute('data-status-select');
    select.className = 'select status-select ' + toneOf(kind, select.value);
    if (window.TFC.showToast) window.TFC.showToast('เปลี่ยนสถานะเป็น "' + select.value + '" แล้ว', 'success');
  });
})();
