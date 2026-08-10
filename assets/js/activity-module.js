/* TheFarmConcept — Activity module shared helpers
   ใช้ร่วมกันระหว่าง admin/activities/list.html, create.html (ทั้งสร้างและแก้ไข), detail.html
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

  /* '2026-08-08T12:00' -> '8 ส.ค. 69 | 12.00' (รูปแบบสั้นของคอลัมน์ "ปรับปรุงล่าสุด") */
  window.TFC.formatThaiShort = function (value) {
    if (!value) return '-';
    var parts = String(value).split('T');
    var short = window.TFC.formatThaiDate(parts[0]).replace(/\d{2}(\d{2})$/, '$1');
    if (!parts[1]) return short;
    return short + ' | ' + parts[1].slice(0, 5).replace(':', '.');
  };

  /* ส่งออกตารางที่กำลังแสดงอยู่เป็น CSV โดยอ่านจาก DOM ตรง ๆ (ข้ามคอลัมน์ปุ่มจัดการ)
     ใช้ได้กับทุกหน้ารายการโดยไม่ต้องเขียน mapping ข้อมูลซ้ำในแต่ละหน้า */
  window.TFC.exportTableCsv = function (tableSelector, filename) {
    var table = typeof tableSelector === 'string' ? document.querySelector(tableSelector) : tableSelector;
    if (!table) return;

    var heads = Array.prototype.slice.call(table.querySelectorAll('thead th'));
    var keepIndexes = [];
    var headers = [];
    heads.forEach(function (th, index) {
      if (th.classList.contains('col-actions') || !th.textContent.trim()) return;
      keepIndexes.push(index);
      headers.push(th.textContent.replace(/[↕↑↓]/g, '').trim());
    });

    var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'))
      .filter(function (tr) { return tr.children.length === heads.length; })
      .map(function (tr) {
        return keepIndexes.map(function (index) {
          return (tr.children[index].textContent || '').replace(/s+/g, ' ').trim();
        });
      });

    window.TFC.exportCsv(filename, headers, rows);
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

    /* ภาพปกกิจกรรมขนาดย่อ — ยังไม่มีไฟล์จริงในข้อมูลจำลอง จึงใช้กล่อง placeholder แทน */
    thumbHTML: function (activity) {
      if (activity && activity.coverImage) {
        return '<span class="thumb"><img src="' + window.TFC.escapeHtml(activity.coverImage) +
          '" alt="ภาพกิจกรรม ' + window.TFC.escapeHtml(activity.name) + '"></span>';
      }
      return '<span class="thumb is-empty" aria-label="ยังไม่มีภาพกิจกรรม">' +
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
        '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>' +
        '</svg></span>';
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

  /* Dropdown สถานะแบบมีสีสำหรับข้อมูลชุดอื่นที่ไม่ได้อยู่ใน master list ของโมดูลกิจกรรม
     (เช่น ใช้งาน/ไม่ใช้งาน ของข้อมูลพื้นฐาน) — ส่ง options มาพร้อมสี badge ได้เลย
     TFC.statusSelectHTML({ options: [{ value, badge }], value, rowId, ariaLabel }) */
  window.TFC.statusSelectHTML = function (opts) {
    opts = opts || {};
    var tones = {};
    var optionsHtml = (opts.options || []).map(function (item) {
      tones[item.value] = (item.badge || 'badge-neutral').replace('badge-', 'is-');
      return '<option value="' + window.TFC.escapeHtml(item.value) + '"' +
        (item.value === opts.value ? ' selected' : '') + '>' +
        window.TFC.escapeHtml(item.value) + '</option>';
    }).join('');

    return '<select class="select status-select ' + (tones[opts.value] || 'is-neutral') + '"' +
      ' data-status-select="custom" data-status-tones="' + window.TFC.escapeHtml(JSON.stringify(tones)) + '"' +
      (opts.rowId ? ' data-row-id="' + window.TFC.escapeHtml(opts.rowId) + '"' : '') +
      ' aria-label="' + window.TFC.escapeHtml(opts.ariaLabel || 'เปลี่ยนสถานะ') + '">' +
      optionsHtml + '</select>';
  };

  /* สถานะแบบข้อความอย่างเดียว (ไม่ให้แก้ในตาราง) — ตัวหนังสือสี พื้นหลังจาง
     TFC.statusTextHTML({ options: [{ value, badge }], value }) */
  window.TFC.statusTextHTML = function (opts) {
    opts = opts || {};
    var hit = (opts.options || []).filter(function (item) { return item.value === opts.value; })[0];
    var tone = ((hit && hit.badge) || 'badge-neutral').replace('badge-', 'is-');
    return '<span class="status-text ' + tone + '">' + window.TFC.escapeHtml(opts.value || '-') + '</span>';
  };

  /* เปลี่ยนสีตามค่าที่เลือกทันที + แจ้ง toast (mock: ยังไม่บันทึกลงฐานข้อมูลจริง) */
  document.addEventListener('change', function (e) {
    var select = e.target.closest('[data-status-select]');
    if (!select) return;

    var toneAttr = select.getAttribute('data-status-tones');
    var tone;
    if (toneAttr) {
      try { tone = JSON.parse(toneAttr)[select.value]; } catch (err) { tone = null; }
    } else {
      tone = toneOf(select.getAttribute('data-status-select'), select.value);
    }

    select.className = 'select status-select ' + (tone || 'is-neutral');
    if (window.TFC.showToast) window.TFC.showToast('เปลี่ยนสถานะเป็น "' + select.value + '" แล้ว', 'success');
  });
})();
