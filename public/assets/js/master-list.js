/* TheFarmConcept — ตัวช่วยที่หน้ารายการในเมนู "พื้นฐาน" ใช้ร่วมกันทั้งหมด

   ทั้งห้าหน้าต้องการสามอย่างเหมือนกัน จึงรวมไว้ที่นี่แทนการเขียนซ้ำในทุกหน้า:
     1. แถบนับจำนวนแยกตามสถานะ มุมซ้ายบนของตาราง กดเพื่อกรองได้
     2. จำนวนแถวต่อหน้าที่พอดีกับความสูงจอ ไม่ใช่เลข 10 ตายตัว
     3. ปุ่มค้นหาที่อยู่ใต้ปุ่มหลัก ไม่ใช่แถวเดียวกัน */
window.TFC = window.TFC || {};

(function () {
  /* คำสั่งลบมาตรฐานของข้อมูลพื้นฐาน — แสดงให้กดได้ทุกแถว
     แถวที่ถูกใช้งานแล้วจะแจ้งเหตุผลในโมดัล และเซิร์ฟเวอร์ตรวจซ้ำอีกชั้น */
  window.TFC.masterDeleteAction = function (opts) {
    opts = opts || {};

    return {
      key: opts.key,
      label: opts.label,
      icon: 'delete',
      modal: opts.modal,
      perm: opts.perm,
      danger: true
    };
  };

  /* ตั้งค่าโมดัลยืนยันก่อนเปิด — คืน true เฉพาะรายการที่ส่ง DELETE ได้ */
  window.TFC.prepareMasterDelete = function (opts) {
    opts = opts || {};
    var usage = Math.max(0, Number(opts.usageCount) || 0);
    var blocked = usage > 0;
    var confirm = document.getElementById(opts.confirmId);
    var cancel = document.querySelector('#' + opts.modalId + ' [data-close-modal]');
    var message = document.getElementById(opts.messageId);
    var title = document.querySelector('#' + opts.modalId + ' .modal-title');
    var footer = document.querySelector('#' + opts.modalId + ' .modal-footer');

    /* จำหัวข้อยืนยันเดิมไว้ เพื่อคืนค่าเมื่อผู้ใช้เปิดรายการที่ยังลบได้ต่อจากรายการที่ถูกใช้งาน */
    if (title && !title.getAttribute('data-delete-title')) {
      title.setAttribute('data-delete-title', title.textContent.trim());
    }

    if (title) {
      title.textContent = blocked ? 'ไม่สามารถลบได้' : title.getAttribute('data-delete-title');
    }

    if (message) {
      message.textContent = blocked
        ? 'มีรายการถูกนำไปใช้งานแล้ว ไม่สามารถลบได้ แนะนำให้กำหนดสถานะเป็น “ปิดใช้งาน” แทน'
        : opts.confirmMessage;
    }
    if (confirm) {
      confirm.hidden = blocked;
      confirm.style.display = blocked ? 'none' : '';
    }
    if (cancel) cancel.textContent = blocked ? 'รับทราบ' : 'ยกเลิก';
    if (footer) footer.style.justifyContent = blocked ? 'center' : '';

    return !blocked;
  };

  /* ---------- 1. แถบนับจำนวนแยกตามสถานะ ----------
     opts.buckets = [{ key, label, match(row) }]  ·  key '' = ทั้งหมด
     opts.onPick(key) ถูกเรียกเมื่อผู้ใช้กดเปลี่ยนตัวกรอง */
  window.TFC.renderStatusCounts = function (target, rows, opts) {
    var el = typeof target === 'string' ? document.getElementById(target) : target;
    if (!el) return;

    opts = opts || {};
    var buckets = opts.buckets || [];
    var active = opts.active || '';

    /* ใช้คลาส .status-pill ชุดเดียวกับหน้ารายการกิจกรรม เพื่อให้หน้าตาตรงกันทั้งระบบ */
    el.innerHTML = buckets.map(function (b) {
      var count = b.key === '' ? rows.length : rows.filter(b.match).length;
      var on = b.key === active;

      return '<button type="button" class="status-pill' + (on ? ' is-active' : '') + '"' +
        ' data-count-key="' + window.TFC.escapeHtml(b.key) + '" aria-pressed="' + on + '">' +
        window.TFC.escapeHtml(b.label) +
        '<span class="status-pill-count">' + count.toLocaleString('th-TH') + '</span>' +
        '</button>';
    }).join('');

    if (el.dataset.bound === '1' || !opts.onPick) return;

    /* ผูก listener ครั้งเดียว เพราะ innerHTML ถูกเขียนทับทุกครั้งที่วาดตารางใหม่ */
    el.dataset.bound = '1';
    el.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-count-key]');
      if (btn) opts.onPick(btn.getAttribute('data-count-key'));
    });
  };

  /* ---------- 2. จำนวนแถวที่พอดีกับจอ ----------
     คิดจากพื้นที่ที่เหลือใต้ตาราง ไม่ใช่ความสูงจอทั้งหมด เพราะหัวหน้า แถบเครื่องมือ
     และแถบแบ่งหน้ากินที่ไปแล้วส่วนหนึ่ง — ต่างกันทุกหน้า จึงวัดจาก DOM จริง */
  window.TFC.fitPageSize = function (tableBodyId, rowHeight) {
    var body = document.getElementById(tableBodyId);
    if (!body) return 10;

    var top = body.getBoundingClientRect().top;

    /* เผื่อที่ให้แถบแบ่งหน้าและระยะขอบล่าง */
    var available = window.innerHeight - top - 96;
    var fits = Math.floor(available / (rowHeight || 52));

    /* ไม่ต่ำกว่า 8 แถว ไม่งั้นจอเตี้ยหรือหน้าต่างที่ย่อไว้จะเหลือแถวเดียวสองแถว */
    return Math.max(8, fits);
  };

  /* ---------- 3. ตัวเลือกจำนวนแถวที่มีค่าพอดีจอรวมอยู่ด้วย ----------
     ถ้าไม่ใส่เข้าไป dropdown จะไม่มี option ที่ตรงกับค่าที่ใช้อยู่ แล้วจะเด้งกลับเป็น 10 */
  window.TFC.pageSizeOptions = function (current) {
    var base = [10, 20, 50, 100];
    if (base.indexOf(current) === -1) base.push(current);

    return base.sort(function (a, b) { return a - b; });
  };
})();
