@extends('layouts.admin')

@section('title', 'ตั้งค่ารอบประเมิน')

@section('content')
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="/admin/dashboard.html">แดชบอร์ด</a> <span>/</span>
        <span>พื้นฐาน</span> <span>/</span>
        <span class="is-current">ตั้งค่ารอบประเมิน</span>
      </nav>
      {{-- ชื่อหน้า 28px/600 + ปุ่มหลัก "เพิ่มรอบ" มุมขวา --}}
      <div class="page-header" id="frt-page-header"></div>

      {{-- สองการ์ดวางคู่กันเพื่อกระชับพื้นที่ — จอแคบซ้อนกันเองด้วย grid --}}
      <div class="frt-grid">
      <!-- ---------- ตารางรอบ ---------- -->
      <section class="card frt-card" aria-labelledby="frt-table-title">
        <div class="frt-card-head">
          <span class="frt-card-title" id="frt-table-title">รอบประเมินสุขภาพกลุ่มตัวอย่าง</span>
        </div>

        {{-- wrapper เลื่อนแนวนอนได้ ตัวแถวมีความกว้างขั้นต่ำ กันคอลัมน์ถูกบีบจนอ่านไม่ออก --}}
        <div class="frt-scroll">
          <div class="frt-rows">
            <div class="frt-row frt-head">
              <span class="frt-th">#</span>
              <span class="frt-th">ชื่อรอบ</span>
              <span class="frt-th frt-th-center">จำนวนวัน</span>
              <span class="frt-th frt-th-center">ใช้งาน</span>
            </div>
            <div id="frt-rows"></div>
          </div>
        </div>

        <p class="frt-hint" id="frt-error" hidden></p>

        {{-- ปุ่มอยู่ในการ์ดเดียวกับตารางที่แก้ ไม่ใช่แถบลอยล่างจอ
             ผู้ใช้จึงเห็นสิ่งที่แก้กับปุ่มบันทึกอยู่ในสายตาเดียวกัน --}}
        <div class="frt-actions">
          <span class="frt-summary" id="frt-summary"></span>
          <div class="frt-actions-btns">
            <button type="button" class="btn btn-outline" id="frt-reset">คืนค่าเริ่มต้น</button>
            <button type="button" class="btn btn-primary" id="frt-save" disabled>บันทึกการตั้งค่า</button>
          </div>
        </div>
      </section>

      <!-- ---------- ทดลองคำนวณ ---------- -->
      <section class="card frt-card" aria-labelledby="frt-sim-title">
        <div class="frt-card-head">
          <span class="frt-card-title" id="frt-sim-title">ทดลองคำนวณ</span>
          <label class="frt-sim-field">
            <span class="frt-sim-label">วันที่เข้ากลุ่มตัวอย่าง</span>
            <input type="text" class="input frt-sim-date" id="frt-entry" data-picker="date" placeholder="เลือกวันที่">
          </label>
        </div>

        <p class="frt-hint">ระบบจะสร้างรอบเหล่านี้ให้ผู้เข้าร่วมแต่ละคนโดยนับจากวันที่เข้ากลุ่มตัวอย่างของคนนั้น</p>

        <div class="frt-scroll">
          <div class="frt-timeline" id="frt-timeline"></div>
        </div>
      </section>
      </div>
@endsection

@push('scripts')
<script>
/* บอก service ว่าต่อฐานข้อมูลจริงแล้ว — ต้องมาก่อนไฟล์ service ทำงาน เพราะอ่านค่านี้ตอนสร้างตัวเอง */
window.TFC_CONFIG = window.TFC_CONFIG || {};
window.TFC_CONFIG.followUpTemplateApiBase = @json(route('admin.master.follow-up-rounds.index'));
</script>
<script src="@assetv('assets/js/followup-template-service.js')"></script>
@endpush

@push('page-script')
{{-- ลำดับต้องตรงกับหน้าเดิม: app.js -> datetime-picker.js -> สคริปต์ของหน้า --}}
<script src="@assetv('assets/js/datetime-picker.js')"></script>
<script>
(function () {
  var esc = window.TFC.escapeHtml;
  /* อ่าน/เขียนผ่าน service ตัวเดียว หน้านี้ไม่ถือข้อมูลเองและไม่คำนวณวันที่เอง
     ตรรกะทั้งหมดอยู่ที่ followup-template-service.js เพื่อให้ระบบกลุ่มตัวอย่าง
     ที่เรียกใช้ค่าชุดเดียวกันได้ผลลัพธ์ตรงกับที่แสดงบนหน้านี้ */
  var svc = window.TFC.followUpTemplateService;

  var THAI_MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

  var state = { rows: [], saved: null, entry: svc.serverToday(), seq: 0 };

  function byId(id) { return document.getElementById(id); }
  function clone(v) { return JSON.parse(JSON.stringify(v)); }
  function toast(msg, tone) { if (window.TFC.showToast) window.TFC.showToast(msg, tone || 'success'); }

  /* วันที่ไทยแบบย่อ พ.ศ. สองหลัก — "10 ส.ค. 69" ตามข้อกำหนดของหน้านี้
     ไม่ใช้ TFC.formatThaiDate เพราะตัวนั้นคืนปีเต็ม (2569) และรับ ISO ว่างไม่ได้ */
  function thaiShort(iso) {
    if (!iso) return '—';
    var p = String(iso).split('-');
    if (p.length !== 3) return '—';
    var be = Number(p[0]) + 543;
    if (!isFinite(be)) return '—';
    return Number(p[2]) + ' ' + THAI_MONTHS[Number(p[1]) - 1] + ' ' + String(be).slice(-2);
  }

  function dirty() { return JSON.stringify(state.rows) !== JSON.stringify(state.saved); }

  /* ---------- 1. หัวหน้า ---------- */
  /* ไม่มีปุ่มเพิ่มรอบแล้ว — ชุดรอบเป็นค่าคงที่ของระบบ ปรับได้แต่ชื่อ จำนวนวัน และเปิด/ปิด */
  window.TFC.renderPageHeader('frt-page-header', { title: 'ตั้งค่ารอบประเมิน' });

  /* ---------- 2. ตาราง ---------- */
  function rowHtml(row, index) {
    var no = index + 1;

    return '<div class="frt-row frt-item' + (row.isActive ? '' : ' is-off') + '" data-id="' + esc(row.id) + '">' +
      '<span class="frt-no">' + no + '</span>' +

      '<input type="text" class="input frt-name" data-field="name" value="' + esc(row.name) + '"' +
        ' placeholder="ตั้งชื่อรอบ" aria-label="ชื่อรอบที่ ' + no + '">' +

      '<span class="frt-days">' +
        '<input type="number" class="input frt-days-input" data-field="offsetDays" value="' + esc(String(row.offsetDays)) + '"' +
          ' min="0" step="1" inputmode="numeric" aria-label="จำนวนวันของรอบที่ ' + no + '">' +
        '<span class="frt-unit">วัน</span>' +
      '</span>' +

      '<span class="frt-active">' +
        '<label class="switch switch-sm" title="เปิด/ปิดใช้งานรอบนี้">' +
          '<input type="checkbox" data-field="isActive"' + (row.isActive ? ' checked' : '') +
            ' aria-label="ใช้งานรอบที่ ' + no + '">' +
          '<span class="switch-track"></span>' +
        '</label>' +
      '</span>' +
      '</div>';
  }

  /* วาดตารางใหม่ทั้งชุด — เรียกเฉพาะตอนจำนวนแถวเปลี่ยน (เพิ่ม/ลบ/โหลด/คืนค่า)
     ระหว่างที่ผู้ใช้พิมพ์ในแถวให้ใช้ updateRow() แทน ไม่งั้นช่องที่โฟกัสอยู่จะหลุด
     (การแทน innerHTML ทิ้ง element ที่กำลังพิมพ์ ตัวอักษรถัดไปจะหายไปเฉย ๆ) */
  function renderRows() {
    byId('frt-rows').innerHTML = state.rows.map(rowHtml).join('');
  }

  /* เหลือแค่สถานะเปิด/ปิดของแถว — คอลัมน์ที่ต้องคำนวณถูกตัดออกแล้ว */
  function updateRow(rowEl, row) {
    rowEl.classList.toggle('is-off', !row.isActive);
  }

  function updateAllRows() {
    Array.prototype.forEach.call(byId('frt-rows').children, function (el) {
      var row = findRow(el.getAttribute('data-id'));
      if (row) updateRow(el, row);
    });
  }

  function findRow(id) {
    return state.rows.filter(function (r) { return r.id === id; })[0];
  }

  /* ---------- 3. ไทม์ไลน์ทดลองคำนวณ ---------- */
  function renderTimeline() {
    var active = svc.materialize(state.entry, withSortOrder());

    if (!active.length) {
      byId('frt-timeline').innerHTML =
        '<div class="frt-empty">' +
          '<span class="frt-empty-title">ยังไม่มีรอบที่เปิดใช้งาน</span>' +
          '<span class="frt-empty-text">เปิดสวิตช์ "ใช้งาน" อย่างน้อยหนึ่งรอบเพื่อดูตัวอย่าง</span>' +
        '</div>';
      return;
    }

    byId('frt-timeline').innerHTML = active.map(function (r) {
      return '<div class="frt-node">' +
        '<span class="frt-node-line" aria-hidden="true"></span>' +
        '<span class="frt-node-dot" aria-hidden="true"></span>' +
        '<span class="frt-node-name">' + esc(r.name || 'ไม่ได้ตั้งชื่อ') + '</span>' +
        '<span class="frt-node-offset">+' + esc(String(r.offsetDays)) + ' วัน</span>' +
        '<span class="frt-node-date">' + esc(thaiShort(r.dueDate)) + '</span>' +
        '</div>';
    }).join('');
  }

  /* sortOrder คิดจากลำดับแถวบนจอ ผู้ใช้ไม่ต้องกรอกเลขลำดับเอง */
  function withSortOrder() {
    return state.rows.map(function (r, i) {
      return { id: r.id, name: r.name, offsetDays: Number(r.offsetDays), isActive: r.isActive, sortOrder: i + 1 };
    });
  }

  /* ---------- 4. สรุปและปุ่มบันทึก ---------- */
  function renderSummary() {
    var check = svc.validate(withSortOrder());
    var note = byId('frt-error');

    if (check.blankCount) {
      byId('frt-summary').textContent = 'กรอกชื่อรอบและจำนวนวันให้ครบทุกแถว';
    } else if (check.duplicateDays.length) {
      /* จำนวนวันซ้ำไม่ได้ระบุไว้ในข้อความสรุปสามแบบ แต่ต้องกันไม่ให้บันทึก
         ไม่งั้นคนหนึ่งคนจะได้รอบครบกำหนดวันเดียวกันสองรอบ */
      byId('frt-summary').textContent = 'จำนวนวันซ้ำกัน (' + check.duplicateDays.join(', ') + ' วัน) — แก้ให้ไม่ซ้ำก่อนบันทึก';
    } else if (!check.activeCount) {
      byId('frt-summary').textContent = 'ต้องเปิดใช้งานอย่างน้อย 1 รอบ';
    } else {
      /* ทุกอย่างถูกต้องแล้วไม่ต้องบอกอะไร — ตารางข้างบนบอกครบอยู่แล้วว่ามีกี่รอบและรอบไหนเปิดอยู่
         บรรทัดนี้เหลือหน้าที่เดียวคือบอกสิ่งที่ต้องแก้ก่อนบันทึก */
      byId('frt-summary').textContent = '';
    }

    byId('frt-summary').classList.toggle('is-warn', !check.ok);

    if (check.duplicateDays.length) {
      note.textContent = 'จำนวนวันของแต่ละรอบต้องไม่ซ้ำกัน และต้องเป็นจำนวนเต็มตั้งแต่ 0 ขึ้นไป';
      note.hidden = false;
    } else {
      note.hidden = true;
    }

    byId('frt-save').disabled = !check.ok;
  }

  /* ทุกอย่างที่ขึ้นกับค่าในตาราง — เรียกหลังแก้ค่าทุกครั้ง */
  function renderDownstream() {
    renderTimeline();
    renderSummary();
  }

  function renderAll() {
    renderRows();
    renderDownstream();
  }

  /* ---------- 5. เหตุการณ์ ---------- */
  byId('frt-rows').addEventListener('input', onEdit);
  byId('frt-rows').addEventListener('change', onEdit);

  function onEdit(e) {
    var field = e.target.getAttribute('data-field');
    if (!field) return;
    var rowEl = e.target.closest('[data-id]');
    if (!rowEl) return;
    var row = findRow(rowEl.getAttribute('data-id'));
    if (!row) return;

    row[field] = field === 'isActive' ? e.target.checked : e.target.value;

    updateRow(rowEl, row);
    renderDownstream();
  }

  /* เปลี่ยนวันที่ตั้งต้น — ไทม์ไลน์ต้องขยับทันที */
  byId('frt-entry').addEventListener('input', function () {
    state.entry = this.getAttribute('data-iso') || '';
    updateAllRows();
    renderTimeline();
  });

  /* ---------- 6. บันทึก / คืนค่าเริ่มต้น ---------- */
  byId('frt-save').addEventListener('click', function () {
    var btn = this;
    btn.disabled = true;
    btn.textContent = 'กำลังบันทึก…';
    svc.save(withSortOrder()).then(function (saved) {
      state.rows = clone(saved);
      state.saved = clone(saved);
      renderAll();
      /* เตือนทุกครั้งที่บันทึก เพราะเป็นผลข้างเคียงที่มองไม่เห็นจากหน้าจอ:
         รอบของคนที่ถูกสร้างไปแล้ว snapshot ค่า offset ไว้ในระเบียนของตัวเอง จึงไม่ขยับตาม */
      toast('บันทึกแล้ว · มีผลกับผู้เข้าร่วมที่เข้ากลุ่มตัวอย่างหลังจากนี้เท่านั้น รอบของคนเดิมไม่เปลี่ยน', 'success');
    })['catch'](function (err) {
      toast(err.message || 'บันทึกไม่สำเร็จ กรุณาลองใหม่', 'danger');
    }).then(function () {
      btn.textContent = 'บันทึกการตั้งค่า';
      renderSummary();
    });
  });

  byId('frt-reset').addEventListener('click', function () {
    state.rows = svc.defaults();
    renderAll();
    toast('คืนค่าเริ่มต้นแล้ว · กดบันทึกการตั้งค่าเพื่อยืนยัน', 'info');
  });

  /* เตือนก่อนออกจากหน้าถ้ายังมีการแก้ที่ไม่ได้บันทึก */
  window.addEventListener('beforeunload', function (e) {
    if (!dirty()) return;
    e.preventDefault();
    e.returnValue = '';
  });

  /* ---------- เริ่มทำงาน ---------- */
  byId('frt-entry').setAttribute('data-iso', state.entry);

  svc.load().then(function (rows) {
    state.rows = rows;
    state.saved = clone(rows);

    /* วันอ้างอิงมาจากเซิร์ฟเวอร์ตอน load() จึงต้องตั้งช่อง "ทดลองคำนวณ" ใหม่หลังโหลดเสร็จ
       ค่าที่ตั้งไว้ก่อนหน้าเป็นค่าตั้งต้นที่เขียนอยู่ในไฟล์ service */
    state.entry = svc.serverToday();
    byId('frt-entry').setAttribute('data-iso', state.entry);

    renderAll();
  })['catch'](function (err) {
    toast(err.message || 'โหลดการตั้งค่าไม่สำเร็จ', 'danger');
  });
})();
</script>
@endpush