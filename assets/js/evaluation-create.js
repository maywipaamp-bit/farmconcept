/* TheFarmConcept — หน้าสร้างแบบประเมิน (admin/evaluations/create.html)

   วิธีเรนเดอร์: รายการคำถามถูกสร้างใหม่เฉพาะตอนที่โครงสร้างเปลี่ยน (เพิ่ม/ลบ/สลับลำดับ/เปลี่ยนชนิด)
   ส่วนการพิมพ์ในช่องข้อความจะอัปเดตแค่ state + แผงขวา ไม่แตะ DOM ของรายการ
   ไม่งั้นช่องที่กำลังพิมพ์อยู่จะเสีย focus ทุกตัวอักษร */
(function () {
  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  var KIND_LABELS = {
    rating: 'ให้คะแนน 1–5',
    single: 'เลือก 1 ข้อ',
    multi: 'เลือกหลายข้อ',
    dropdown: 'เลือกจากรายการ',
    text: 'ข้อความ'
  };
  var KIND_ORDER = ['rating', 'single', 'multi', 'dropdown', 'text'];

  var STAGES = [
    { label: 'ตอนลงทะเบียน', hint: 'ผู้เข้าร่วมกรอกตอนจองที่นั่ง' },
    { label: 'หลังกิจกรรม', hint: 'ส่งให้ผู้เข้าร่วมหลังจบงาน' },
    { label: 'ติดตามสุขภาพ', hint: 'ไม่ผูกกับกิจกรรม ส่งตามรอบเวลา' }
  ];
  var STANDALONE = 'ติดตามสุขภาพ';

  var AUTOSAVE_MS = 60000;

  var state = {
    name: '',
    desc: '',
    stage: 'หลังกิจกรรม',
    nextId: 4,
    dirty: false,
    /* ตั้งต้นด้วยหัวข้อส่วน 1 ส่วนพร้อมคำถาม 2 ข้อในนั้น
       ให้เห็นตั้งแต่แรกว่าจัดคำถามเป็นตอนได้ และเลขข้อจะเป็น 1.1 / 1.2
       ตั้งชื่อส่วนไว้ให้ด้วย ไม่งั้นเช็กลิสต์ข้อ "ทุกคำถามและหัวข้อส่วนมีข้อความ" จะไม่ผ่านตั้งแต่เปิดหน้า */
    items: [
      { id: 1, title: 'ความคิดเห็นต่อกิจกรรม', kind: 'section', required: false, choices: [] },
      { id: 2, title: 'ความพึงพอใจโดยรวมต่อกิจกรรมนี้', kind: 'rating', required: true, choices: [] },
      { id: 3, title: 'จะแนะนำกิจกรรมนี้ให้คนอื่นหรือไม่', kind: 'single', required: true, choices: ['แนะนำ', 'ไม่แน่ใจ', 'ไม่แนะนำ'] }
    ]
  };

  function isStandalone() { return state.stage === STANDALONE; }
  function itemById(id) { return state.items.filter(function (i) { return String(i.id) === String(id); })[0]; }
  /* เลือกจากรายการใช้ชุดตัวเลือกเหมือนแบบเลือก 1 ข้อ ต่างแค่วิธีแสดงให้ผู้ตอบ */
  function hasChoices(kind) { return kind === 'single' || kind === 'multi' || kind === 'dropdown'; }

  /* วงกลม = เลือก 1 ข้อ · สี่เหลี่ยม = เลือกหลายข้อ · สี่เหลี่ยมเทา = เลือกจากรายการ */
  function markHtml(kind) {
    var cls = kind === 'multi' ? ' is-box' : (kind === 'dropdown' ? ' is-box is-muted' : '');
    return '<span class="ec-mark' + cls + '"></span>';
  }

  /* เลขข้อ: ส่วนได้ "ตอนที่ N" · คำถามในส่วนได้ "N.M" · คำถามนอกส่วนได้ "M" */
  function numbering() {
    var qn = 0, sn = 0;
    return state.items.map(function (q) {
      if (q.kind === 'section') {
        sn++; qn = 0;
        return { no: '', sectionNo: 'ตอนที่ ' + sn, inSection: true };
      }
      qn++;
      return { no: sn > 0 ? sn + '.' + qn : String(qn), sectionNo: '', inSection: sn > 0 };
    });
  }

  function questionCount() {
    return state.items.filter(function (i) { return i.kind !== 'section'; }).length;
  }

  function checklist() {
    var filled = state.items.filter(function (i) { return i.title.trim().length > 0; }).length;
    return [
      { label: 'ตั้งชื่อชุดแบบประเมิน', ok: state.name.trim().length > 0 },
      { label: 'เลือกว่าใช้ตอนไหน', ok: !!state.stage },
      { label: 'มีคำถามอย่างน้อย 1 ข้อ', ok: questionCount() > 0 },
      { label: 'ทุกคำถามและหัวข้อส่วนมีข้อความ', ok: state.items.length > 0 && filled === state.items.length },
      { label: 'คำถามแบบเลือกมีตัวเลือกครบ', ok: state.items.every(function (i) {
          return !hasChoices(i.kind) || i.choices.filter(function (c) { return c.trim(); }).length >= 2;
        }) }
    ];
  }

  /* ================= ส่วนที่ 1 ================= */
  function renderStages() {
    $('ec-stages').innerHTML = STAGES.map(function (s) {
      var on = state.stage === s.label;
      return '<button type="button" class="ec-pick' + (on ? ' is-on' : '') + '" data-stage="' + esc(s.label) + '">' +
        '<span class="ec-pick-title">' + esc(s.label) + '</span>' +
        '<span class="ec-pick-hint">' + esc(s.hint) + '</span>' +
        '</button>';
    }).join('');
  }

 — รายการคำถาม ================= */
  function renderItems() {
    var nums = numbering();

    $('ec-items').innerHTML = state.items.map(function (q, i) {
      var n = nums[i];
      return q.kind === 'section' ? sectionHtml(q, n) : questionHtml(q, n);
    }).join('');

    $('ec-summary').textContent = questionCount() + ' ข้อ · ' +
      state.items.filter(function (i2) { return i2.kind === 'section'; }).length + ' หัวข้อส่วน · บังคับตอบ ' +
      state.items.filter(function (i2) { return i2.required && i2.kind !== 'section'; }).length + ' ข้อ';
  }

  /* ที่จับลาก — แทนปุ่มลูกศรสองปุ่มเดิม ทำให้แถวเตี้ยลงและใช้ลากสลับลำดับได้
     ยังกดลูกศรขึ้น/ลงบนคีย์บอร์ดได้เมื่อโฟกัสอยู่ที่ตัวจับ สำหรับคนที่ลากไม่ได้ */
  function gripHtml(id) {
    return '<button type="button" class="ec-grip" data-grip="' + id + '"' +
      ' aria-label="ลากเพื่อสลับลำดับ หรือกดลูกศรขึ้น/ลง">' +
      '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">' +
      '<circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/>' +
      '<circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>' +
      '<circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg></button>';
  }

  function sectionHtml(q, n) {
    return '<div class="ec-row ec-row-section" data-row="' + q.id + '">' +
      '<div class="ec-row-lead">' + gripHtml(q.id) + '</div>' +
      '<div class="ec-row-body">' +
        '<div class="ec-section-line">' +
          '<span class="ec-section-badge">' + esc(n.sectionNo) + '</span>' +
          '<input type="text" class="input ec-section-input" value="' + esc(q.title) + '"' +
            ' placeholder="ชื่อหัวข้อส่วน เช่น ข้อมูลสุขภาพ" data-title="' + q.id + '">' +
          '<button type="button" class="ec-link-danger" data-remove="' + q.id + '">' +
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h14M9.5 7V5.5h5V7M7 7l.8 12h8.4L17 7"/></svg>' +
            'ลบหัวข้อ</button>' +
        '</div>' +
      '</div></div>';
  }

  function questionHtml(q, n) {
    return '<div class="ec-row' + (n.inSection ? ' is-nested' : '') + '" data-row="' + q.id + '">' +
      '<div class="ec-row-lead">' + gripHtml(q.id) + '<span class="ec-row-no">' + esc(n.no) + '</span></div>' +
      '<div class="ec-row-body">' +
        '<input type="text" class="input" value="' + esc(q.title) + '" placeholder="พิมพ์คำถาม" data-title="' + q.id + '">' +

        '<div class="ec-chips">' + KIND_ORDER.map(function (k) {
          return '<button type="button" class="ec-chip' + (q.kind === k ? ' is-on' : '') +
            '" data-kind="' + k + ':' + q.id + '">' + esc(KIND_LABELS[k]) + '</button>';
        }).join('') + '</div>' +

        (hasChoices(q.kind) ? choicesHtml(q) : '') +
        (q.kind === 'rating' ? ratingHtml() : '') +
        (q.kind === 'text' ? '<div class="ec-text-hint">ผู้ตอบจะพิมพ์คำตอบเองในช่องนี้</div>' : '') +

        '<div class="ec-row-foot">' +
          '<label class="ec-required">' +
            '<input type="checkbox" data-required="' + q.id + '"' + (q.required ? ' checked' : '') + '>' +
            '<span>' + (isStandalone() ? 'บังคับตอบ' : 'บังคับตอบ (ข้ามได้ถ้าไม่ติ๊ก)') + '</span>' +
          '</label>' +
          '<button type="button" class="ec-link-muted" data-remove="' + q.id + '">' +
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h14M9.5 7V5.5h5V7M7 7l.8 12h8.4L17 7"/></svg>' +
            'ลบคำถาม</button>' +
        '</div>' +
      '</div></div>';
  }

  function choicesHtml(q) {
    return '<div class="ec-choices">' +
      q.choices.map(function (c, ci) {
        return '<div class="ec-choice">' +
          markHtml(q.kind) +
          '<input type="text" class="input ec-choice-input" value="' + esc(c) + '" placeholder="ตัวเลือก"' +
            ' data-choice="' + q.id + ':' + ci + '">' +
          '<button type="button" class="ec-choice-remove" data-remove-choice="' + q.id + ':' + ci + '" aria-label="ลบตัวเลือก">' +
            '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button>' +
          '</div>';
      }).join('') +
      '<button type="button" class="ec-link-add" data-add-choice="' + q.id + '">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5.5v13M5.5 12h13"/></svg>' +
        'เพิ่มตัวเลือก</button>' +
      '</div>';
  }

  function ratingHtml() {
    return '<div class="ec-scale">' +
      [1, 2, 3, 4, 5].map(function (s) { return '<span class="ec-scale-box">' + s + '</span>'; }).join('') +
      '<span class="ec-scale-hint">1 = น้อยที่สุด · 5 = มากที่สุด</span>' +
      '</div>';
  }

  /* ================= แผงขวา ================= */
  function renderPreview() {
    var nameEl = $('ec-preview-name');
    nameEl.textContent = state.name.trim() || 'ชื่อชุดแบบประเมิน';
    nameEl.classList.toggle('is-empty', !state.name.trim());

    $('ec-preview-desc').textContent = state.desc.trim() ||
      (isStandalone() ? 'ส่งตามรอบเวลา ไม่ผูกกับกิจกรรม' : 'คำอธิบายจะแสดงตรงนี้');

    var nums = numbering();
    $('ec-preview-list').innerHTML = state.items.map(function (q, i) {
      var n = nums[i];
      if (q.kind === 'section') {
        return '<div class="ec-pv-section">' +
          '<span class="ec-pv-section-no">' + esc(n.sectionNo) + '</span>' +
          '<span class="ec-pv-section-title">' + esc(q.title.trim() || 'หัวข้อส่วน') + '</span>' +
          '</div>';
      }
      return '<div class="ec-pv-q' + (n.inSection ? ' is-nested' : '') + '">' +
        '<span class="ec-pv-title">' + esc(n.no + '. ' + (q.title.trim() || 'คำถามที่ยังไม่ได้พิมพ์') + (q.required ? ' *' : '')) + '</span>' +
        (q.kind === 'rating'
          ? '<div class="ec-scale">' + [1, 2, 3, 4, 5].map(function (s) { return '<span class="ec-scale-box">' + s + '</span>'; }).join('') + '</div>'
          : '') +
        (q.kind === 'dropdown'
          ? '<div class="ec-pv-select">' +
              '<span>' + esc((q.choices[0] || '').trim() || 'เลือกคำตอบ') + '</span>' +
              '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>' +
            '</div>'
          : '') +
        (hasChoices(q.kind) && q.kind !== 'dropdown'
          ? '<div class="ec-pv-choices">' + q.choices.map(function (c) {
              return '<span class="ec-pv-choice">' + markHtml(q.kind) + esc(c) + '</span>';
            }).join('') + '</div>'
          : '') +
        (q.kind === 'text' ? '<div class="ec-pv-text">พิมพ์คำตอบ…</div>' : '') +
        '</div>';
    }).join('');
  }

  function renderChecklist() {
    var list = checklist();
    var okCount = list.filter(function (d) { return d.ok; }).length;
    var ready = okCount === list.length;

    $('ec-checklist').innerHTML = list.map(function (d) {
      return '<span class="ec-check' + (d.ok ? ' is-ok' : '') + '">' +
        '<span class="ec-check-dot">' +
          '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>' +
        '</span>' + esc(d.label) + '</span>';
    }).join('');

    var badge = $('ec-done-badge');
    badge.textContent = okCount + '/' + list.length;
    badge.classList.toggle('is-ready', ready);

    $('ec-progress-text').textContent = ready
      ? 'พร้อมบันทึกและเปิดใช้งาน'
      : 'ยังเหลือ ' + (list.length - okCount) + ' ข้อที่ต้องแก้ก่อนเปิดใช้งาน';
    $('ec-save').disabled = !ready;
  }

  /* อัปเดตทุกอย่างที่คำนวณจาก state ยกเว้นรายการคำถาม (ซึ่งมี input ที่อาจกำลังพิมพ์อยู่) */
  function syncDerived() {
    renderPreview();
    renderChecklist();
    $('ec-summary').textContent = questionCount() + ' ข้อ · ' +
      state.items.filter(function (i) { return i.kind === 'section'; }).length + ' หัวข้อส่วน · บังคับตอบ ' +
      state.items.filter(function (i) { return i.required && i.kind !== 'section'; }).length + ' ข้อ';
  }

  function syncAll() {
    renderStages();
    renderItems();
    syncDerived();
  }

  function touch(structural) {
    state.dirty = true;
    if (structural) renderItems();
    syncDerived();
  }

  /* ================= เมนูเพิ่มคำถาม ================= */
  function renderAddMenu() {
    $('ec-add-menu').innerHTML = KIND_ORDER.map(function (k) {
      return '<button type="button" class="ec-add-item" role="menuitem" data-add="' + k + '">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5.5v13M5.5 12h13"/></svg>' +
        esc(KIND_LABELS[k]) + '</button>';
    }).join('');
  }

  function syncAddMenu() {
    $('ec-add-menu').hidden = !state.addOpen;
    $('ec-add-toggle').setAttribute('aria-expanded', String(state.addOpen));
    $('ec-add-toggle').classList.toggle('is-open', state.addOpen);
  }

  function addItem(kind) {
    state.items.push({
      id: state.nextId++,
      title: '',
      kind: kind,
      required: false,
      choices: hasChoices(kind) ? ['ตัวเลือกที่ 1', 'ตัวเลือกที่ 2'] : []
    });
    state.addOpen = false;
    syncAddMenu();
    touch(true);
  }

  function indexOfId(id) {
    return state.items.map(function (i) { return String(i.id); }).indexOf(String(id));
  }

  function move(id, dir) {
    var idx = indexOfId(id);
    var to = idx + dir;
    if (idx < 0 || to < 0 || to >= state.items.length) return;
    var tmp = state.items[idx];
    state.items[idx] = state.items[to];
    state.items[to] = tmp;
    touch(true);
    /* คืนโฟกัสให้ตัวจับของแถวเดิม จะได้กดลูกศรรัวๆ เลื่อนต่อได้ */
    var grip = document.querySelector('[data-grip="' + id + '"]');
    if (grip) grip.focus();
  }

  /* ---------- ลากสลับลำดับ ----------
     ตั้ง draggable ตอนกดที่ตัวจับเท่านั้น ไม่งั้นการลากเลือกข้อความในช่องกรอก
     จะกลายเป็นการลากทั้งแถวไปด้วย */
  var dragId = null;

  document.addEventListener('mousedown', function (e) {
    var grip = e.target.closest('[data-grip]');
    var row = grip && grip.closest('[data-row]');
    if (row) row.setAttribute('draggable', 'true');
  });

  document.addEventListener('mouseup', clearDraggable);

  function clearDraggable() {
    var rows = document.querySelectorAll('[data-row][draggable]');
    Array.prototype.forEach.call(rows, function (r) { r.removeAttribute('draggable'); });
  }

  document.addEventListener('dragstart', function (e) {
    var row = e.target.closest && e.target.closest('[data-row]');
    if (!row || !row.getAttribute('draggable')) return;
    dragId = row.getAttribute('data-row');
    row.classList.add('is-dragging');
    if (e.dataTransfer) {
      e.dataTransfer.effectAllowed = 'move';
      /* Firefox ไม่เริ่มลากถ้าไม่ได้ตั้งข้อมูลไว้ */
      e.dataTransfer.setData('text/plain', dragId);
    }
  });

  document.addEventListener('dragover', function (e) {
    if (dragId === null) return;
    var row = e.target.closest && e.target.closest('[data-row]');
    if (!row) return;
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    clearDropMarks();
    if (row.getAttribute('data-row') === dragId) return;
    var box = row.getBoundingClientRect();
    row.classList.add(e.clientY < box.top + box.height / 2 ? 'is-drop-before' : 'is-drop-after');
  });

  document.addEventListener('drop', function (e) {
    if (dragId === null) return;
    var row = e.target.closest && e.target.closest('[data-row]');
    if (!row) return;
    e.preventDefault();

    var targetId = row.getAttribute('data-row');
    var before = row.classList.contains('is-drop-before');
    endDrag();
    if (targetId === dragId) return;

    var from = indexOfId(dragId);
    var moved = state.items.splice(from, 1)[0];
    var to = indexOfId(targetId);
    state.items.splice(before ? to : to + 1, 0, moved);
    touch(true);
  });

  document.addEventListener('dragend', endDrag);

  function endDrag() {
    var dragging = document.querySelector('.is-dragging');
    if (dragging) dragging.classList.remove('is-dragging');
    clearDropMarks();
    clearDraggable();
    dragId = null;
  }

  function clearDropMarks() {
    var marked = document.querySelectorAll('.is-drop-before, .is-drop-after');
    Array.prototype.forEach.call(marked, function (r) {
      r.classList.remove('is-drop-before', 'is-drop-after');
    });
  }

  /* ================= เหตุการณ์ ================= */
  $('ec-name').addEventListener('input', function () { state.name = this.value; touch(false); });
  $('ec-desc').addEventListener('input', function () { state.desc = this.value; touch(false); });

  /* พิมพ์ในช่องคำถาม/ตัวเลือก -> อัปเดตแค่ state กับแผงขวา ไม่เรนเดอร์รายการใหม่ */
  document.addEventListener('input', function (e) {
    var title = e.target.closest('[data-title]');
    if (title) {
      var q = itemById(title.getAttribute('data-title'));
      if (q) { q.title = title.value; touch(false); }
      return;
    }
    var choice = e.target.closest('[data-choice]');
    if (choice) {
      var parts = choice.getAttribute('data-choice').split(':');
      var item = itemById(parts[0]);
      if (item) { item.choices[Number(parts[1])] = choice.value; touch(false); }
    }
  });

  document.addEventListener('change', function (e) {
    var req = e.target.closest('[data-required]');
    if (req) {
      var q = itemById(req.getAttribute('data-required'));
      if (q) { q.required = req.checked; touch(false); }
    }
  });

  document.addEventListener('click', function (e) {
    var t = e.target;

    var stage = t.closest('[data-stage]');
    if (stage) {
      state.stage = stage.getAttribute('data-stage');
      renderStages();
      /* ป้ายกำกับ "บังคับตอบ" เปลี่ยนตามช่วง จึงต้องวาดรายการใหม่ด้วย */
      return touch(true);
    }


    if (kind) {
      var kp = kind.getAttribute('data-kind').split(':');
      var kq = itemById(kp[1]);
      if (!kq) return;
      kq.kind = kp[0];
      if (hasChoices(kq.kind)) {
        if (!kq.choices.length) kq.choices = ['ตัวเลือกที่ 1', 'ตัวเลือกที่ 2'];
      } else {
        kq.choices = [];
      }
      return touch(true);
    }

    var rm = t.closest('[data-remove]');
    if (rm) {
      var id = rm.getAttribute('data-remove');
      state.items = state.items.filter(function (x) { return String(x.id) !== String(id); });
      return touch(true);
    }

    var addChoice = t.closest('[data-add-choice]');
    if (addChoice) {
      var aq = itemById(addChoice.getAttribute('data-add-choice'));
      if (aq) { aq.choices.push('ตัวเลือกใหม่'); touch(true); }
      return;
    }

    var rmChoice = t.closest('[data-remove-choice]');
    if (rmChoice) {
      var cp = rmChoice.getAttribute('data-remove-choice').split(':');
      var cq = itemById(cp[0]);
      if (cq) { cq.choices.splice(Number(cp[1]), 1); touch(true); }
      return;
    }

    if (t.closest('#ec-add-section')) {
      state.items.push({ id: state.nextId++, title: '', kind: 'section', required: false, choices: [] });
      return touch(true);
    }

    if (t.closest('#ec-add-toggle')) {
      state.addOpen = !state.addOpen;
      return syncAddMenu();
    }

    var add = t.closest('[data-add]');
    if (add) return addItem(add.getAttribute('data-add'));

    if (t.closest('#ec-save-draft')) return saveDraft(true);

    if (t.closest('#ec-save') && !$('ec-save').disabled) {
      state.dirty = false;
      if (window.TFC.showToast) window.TFC.showToast('บันทึกและเปิดใช้งานแบบประเมินเรียบร้อย', 'success');
      return;
    }

    if (state.addOpen && !t.closest('.ec-add-wrap')) {
      state.addOpen = false;
      syncAddMenu();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (dragId !== null) endDrag();
      if (state.addOpen) { state.addOpen = false; syncAddMenu(); }
      return;
    }
    /* คนที่ใช้คีย์บอร์ดหรือ screen reader ลากไม่ได้ จึงต้องมีทางเลื่อนลำดับด้วยลูกศร */
    var grip = e.target.closest && e.target.closest('[data-grip]');
    if (grip && (e.key === 'ArrowUp' || e.key === 'ArrowDown')) {
      e.preventDefault();
      move(grip.getAttribute('data-grip'), e.key === 'ArrowUp' ? -1 : 1);
    }
  });

  /* ---------- บันทึกร่าง ---------- */
  function saveDraft(manual) {
    state.dirty = false;
    var now = new Date();
    $('ec-autosave').hidden = false;
    $('ec-autosave-text').textContent = 'บันทึกร่างล่าสุด ' +
      String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ' น.';
    if (manual && window.TFC.showToast) window.TFC.showToast('บันทึกฉบับร่างเรียบร้อย', 'success');
  }

  setInterval(function () { if (state.dirty) saveDraft(false); }, AUTOSAVE_MS);

  window.addEventListener('beforeunload', function (e) {
    if (!state.dirty) return;
    e.preventDefault();
    e.returnValue = '';
  });

  $('ec-cancel').addEventListener('click', function (e) {
    if (!state.dirty) return;
    if (!window.confirm('ยังไม่ได้บันทึกการเปลี่ยนแปลง ต้องการออกจากหน้านี้หรือไม่')) e.preventDefault();
  });

  /* ---------- เริ่มต้น ---------- */
  renderAddMenu();
  syncAddMenu();
  syncAll();
})();  /* ================= ส่วนที่ 2บประเมิน (admin/evaluations/create.html)

   วิธีเรนเดอร์: รายการคำถามถูกสร้างใหม่เฉพาะตอนที่โครงสร้างเปลี่ยน (เพิ่ม/ลบ/สลับลำดับ/เปลี่ยนชนิด)
   ส่วนการพิมพ์ในช่องข้อความจะอัปเดตแค่ state + แผงขวา ไม่แตะ DOM ของรายการ
   ไม่งั้นช่องที่กำลังพิมพ์อยู่จะเสีย focus ทุกตัวอักษร */
(function () {
  var esc = window.TFC.escapeHtml;
  var $ = function (id) { return document.getElementById(id); };

  var KIND_LABELS = {
    rating: 'ให้คะแนน 1–5',
    single: 'เลือก 1 ข้อ',
    multi: 'เลือกหลายข้อ',
    dropdown: 'เลือกจากรายการ',
    text: 'ข้อความ'
  };
  var KIND_ORDER = ['rating', 'single', 'multi', 'dropdown', 'text'];

  var STAGES = [
    { label: 'ตอนลงทะเบียน', hint: 'ผู้เข้าร่วมกรอกตอนจองที่นั่ง' },
    { label: 'หลังกิจกรรม', hint: 'ส่งให้ผู้เข้าร่วมหลังจบงาน' },
    { label: 'ติดตามสุขภาพ', hint: 'ไม่ผูกกับกิจกรรม ส่งตามรอบเวลา' }
  ];
  var STANDALONE = 'ติดตามสุขภาพ';

  var AUTOSAVE_MS = 60000;

  var state = {
    name: '',
    desc: '',
    stage: 'หลังกิจกรรม',
    nextId: 4,
    dirty: false,
    /* ตั้งต้นด้วยหัวข้อส่วน 1 ส่วนพร้อมคำถาม 2 ข้อในนั้น
       ให้เห็นตั้งแต่แรกว่าจัดคำถามเป็นตอนได้ และเลขข้อจะเป็น 1.1 / 1.2
       ตั้งชื่อส่วนไว้ให้ด้วย ไม่งั้นเช็กลิสต์ข้อ "ทุกคำถามและหัวข้อส่วนมีข้อความ" จะไม่ผ่านตั้งแต่เปิดหน้า */
    items: [
      { id: 1, title: 'ความคิดเห็นต่อกิจกรรม', kind: 'section', required: false, choices: [] },
      { id: 2, title: 'ความพึงพอใจโดยรวมต่อกิจกรรมนี้', kind: 'rating', required: true, choices: [] },
      { id: 3, title: 'จะแนะนำกิจกรรมนี้ให้คนอื่นหรือไม่', kind: 'single', required: true, choices: ['แนะนำ', 'ไม่แน่ใจ', 'ไม่แนะนำ'] }
    ]
  };

  function isStandalone() { return state.stage === STANDALONE; }
  function itemById(id) { return state.items.filter(function (i) { return String(i.id) === String(id); })[0]; }
  /* เลือกจากรายการใช้ชุดตัวเลือกเหมือนแบบเลือก 1 ข้อ ต่างแค่วิธีแสดงให้ผู้ตอบ */
  function hasChoices(kind) { return kind === 'single' || kind === 'multi' || kind === 'dropdown'; }

  /* วงกลม = เลือก 1 ข้อ · สี่เหลี่ยม = เลือกหลายข้อ · สี่เหลี่ยมเทา = เลือกจากรายการ */
  function markHtml(kind) {
    var cls = kind === 'multi' ? ' is-box' : (kind === 'dropdown' ? ' is-box is-muted' : '');
    return '<span class="ec-mark' + cls + '"></span>';
  }

  /* เลขข้อ: ส่วนได้ "ตอนที่ N" · คำถามในส่วนได้ "N.M" · คำถามนอกส่วนได้ "M" */
  function numbering() {
    var qn = 0, sn = 0;
    return state.items.map(function (q) {
      if (q.kind === 'section') {
        sn++; qn = 0;
        return { no: '', sectionNo: 'ตอนที่ ' + sn, inSection: true };
      }
      qn++;
      return { no: sn > 0 ? sn + '.' + qn : String(qn), sectionNo: '', inSection: sn > 0 };
    });
  }

  function questionCount() {
    return state.items.filter(function (i) { return i.kind !== 'section'; }).length;
  }

  function checklist() {
    var filled = state.items.filter(function (i) { return i.title.trim().length > 0; }).length;
    return [
      { label: 'ตั้งชื่อชุดแบบประเมิน', ok: state.name.trim().length > 0 },
      { label: 'เลือกว่าใช้ตอนไหน', ok: !!state.stage },
      { label: 'มีคำถามอย่างน้อย 1 ข้อ', ok: questionCount() > 0 },
      { label: 'ทุกคำถามและหัวข้อส่วนมีข้อความ', ok: state.items.length > 0 && filled === state.items.length },
      { label: 'คำถามแบบเลือกมีตัวเลือกครบ', ok: state.items.every(function (i) {
          return !hasChoices(i.kind) || i.choices.filter(function (c) { return c.trim(); }).length >= 2;
        }) }
    ];
  }

  /* ================= ส่วนที่ 1 ================= */
  function renderStages() {
    $('ec-stages').innerHTML = STAGES.map(function (s) {
      var on = state.stage === s.label;
      return '<button type="button" class="ec-pick' + (on ? ' is-on' : '') + '" data-stage="' + esc(s.label) + '">' +
        '<span class="ec-pick-title">' + esc(s.label) + '</span>' +
        '<span class="ec-pick-hint">' + esc(s.hint) + '</span>' +
        '</button>';
    }).join('');
  }

 — รายการคำถาม ================= */
  function renderItems() {
    var nums = numbering();

    $('ec-items').innerHTML = state.items.map(function (q, i) {
      var n = nums[i];
      return q.kind === 'section' ? sectionHtml(q, n) : questionHtml(q, n);
    }).join('');

    $('ec-summary').textContent = questionCount() + ' ข้อ · ' +
      state.items.filter(function (i2) { return i2.kind === 'section'; }).length + ' หัวข้อส่วน · บังคับตอบ ' +
      state.items.filter(function (i2) { return i2.required && i2.kind !== 'section'; }).length + ' ข้อ';
  }

  /* ที่จับลาก — แทนปุ่มลูกศรสองปุ่มเดิม ทำให้แถวเตี้ยลงและใช้ลากสลับลำดับได้
     ยังกดลูกศรขึ้น/ลงบนคีย์บอร์ดได้เมื่อโฟกัสอยู่ที่ตัวจับ สำหรับคนที่ลากไม่ได้ */
  function gripHtml(id) {
    return '<button type="button" class="ec-grip" data-grip="' + id + '"' +
      ' aria-label="ลากเพื่อสลับลำดับ หรือกดลูกศรขึ้น/ลง">' +
      '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">' +
      '<circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/>' +
      '<circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>' +
      '<circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg></button>';
  }

  function sectionHtml(q, n) {
    return '<div class="ec-row ec-row-section" data-row="' + q.id + '">' +
      '<div class="ec-row-lead">' + gripHtml(q.id) + '</div>' +
      '<div class="ec-row-body">' +
        '<div class="ec-section-line">' +
          '<span class="ec-section-badge">' + esc(n.sectionNo) + '</span>' +
          '<input type="text" class="input ec-section-input" value="' + esc(q.title) + '"' +
            ' placeholder="ชื่อหัวข้อส่วน เช่น ข้อมูลสุขภาพ" data-title="' + q.id + '">' +
          '<button type="button" class="ec-link-danger" data-remove="' + q.id + '">' +
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h14M9.5 7V5.5h5V7M7 7l.8 12h8.4L17 7"/></svg>' +
            'ลบหัวข้อ</button>' +
        '</div>' +
      '</div></div>';
  }

  function questionHtml(q, n) {
    return '<div class="ec-row' + (n.inSection ? ' is-nested' : '') + '" data-row="' + q.id + '">' +
      '<div class="ec-row-lead">' + gripHtml(q.id) + '<span class="ec-row-no">' + esc(n.no) + '</span></div>' +
      '<div class="ec-row-body">' +
        '<input type="text" class="input" value="' + esc(q.title) + '" placeholder="พิมพ์คำถาม" data-title="' + q.id + '">' +

        '<div class="ec-chips">' + KIND_ORDER.map(function (k) {
          return '<button type="button" class="ec-chip' + (q.kind === k ? ' is-on' : '') +
            '" data-kind="' + k + ':' + q.id + '">' + esc(KIND_LABELS[k]) + '</button>';
        }).join('') + '</div>' +

        (hasChoices(q.kind) ? choicesHtml(q) : '') +
        (q.kind === 'rating' ? ratingHtml() : '') +
        (q.kind === 'text' ? '<div class="ec-text-hint">ผู้ตอบจะพิมพ์คำตอบเองในช่องนี้</div>' : '') +

        '<div class="ec-row-foot">' +
          '<label class="ec-required">' +
            '<input type="checkbox" data-required="' + q.id + '"' + (q.required ? ' checked' : '') + '>' +
            '<span>' + (isStandalone() ? 'บังคับตอบ' : 'บังคับตอบ (ข้ามได้ถ้าไม่ติ๊ก)') + '</span>' +
          '</label>' +
          '<button type="button" class="ec-link-muted" data-remove="' + q.id + '">' +
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h14M9.5 7V5.5h5V7M7 7l.8 12h8.4L17 7"/></svg>' +
            'ลบคำถาม</button>' +
        '</div>' +
      '</div></div>';
  }

  function choicesHtml(q) {
    return '<div class="ec-choices">' +
      q.choices.map(function (c, ci) {
        return '<div class="ec-choice">' +
          markHtml(q.kind) +
          '<input type="text" class="input ec-choice-input" value="' + esc(c) + '" placeholder="ตัวเลือก"' +
            ' data-choice="' + q.id + ':' + ci + '">' +
          '<button type="button" class="ec-choice-remove" data-remove-choice="' + q.id + ':' + ci + '" aria-label="ลบตัวเลือก">' +
            '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button>' +
          '</div>';
      }).join('') +
      '<button type="button" class="ec-link-add" data-add-choice="' + q.id + '">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5.5v13M5.5 12h13"/></svg>' +
        'เพิ่มตัวเลือก</button>' +
      '</div>';
  }

  function ratingHtml() {
    return '<div class="ec-scale">' +
      [1, 2, 3, 4, 5].map(function (s) { return '<span class="ec-scale-box">' + s + '</span>'; }).join('') +
      '<span class="ec-scale-hint">1 = น้อยที่สุด · 5 = มากที่สุด</span>' +
      '</div>';
  }

  /* ================= แผงขวา ================= */
  function renderPreview() {
    var nameEl = $('ec-preview-name');
    nameEl.textContent = state.name.trim() || 'ชื่อชุดแบบประเมิน';
    nameEl.classList.toggle('is-empty', !state.name.trim());

    $('ec-preview-desc').textContent = state.desc.trim() ||
      (isStandalone() ? 'ส่งตามรอบเวลา ไม่ผูกกับกิจกรรม' : 'คำอธิบายจะแสดงตรงนี้');

    var nums = numbering();
    $('ec-preview-list').innerHTML = state.items.map(function (q, i) {
      var n = nums[i];
      if (q.kind === 'section') {
        return '<div class="ec-pv-section">' +
          '<span class="ec-pv-section-no">' + esc(n.sectionNo) + '</span>' +
          '<span class="ec-pv-section-title">' + esc(q.title.trim() || 'หัวข้อส่วน') + '</span>' +
          '</div>';
      }
      return '<div class="ec-pv-q' + (n.inSection ? ' is-nested' : '') + '">' +
        '<span class="ec-pv-title">' + esc(n.no + '. ' + (q.title.trim() || 'คำถามที่ยังไม่ได้พิมพ์') + (q.required ? ' *' : '')) + '</span>' +
        (q.kind === 'rating'
          ? '<div class="ec-scale">' + [1, 2, 3, 4, 5].map(function (s) { return '<span class="ec-scale-box">' + s + '</span>'; }).join('') + '</div>'
          : '') +
        (q.kind === 'dropdown'
          ? '<div class="ec-pv-select">' +
              '<span>' + esc((q.choices[0] || '').trim() || 'เลือกคำตอบ') + '</span>' +
              '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>' +
            '</div>'
          : '') +
        (hasChoices(q.kind) && q.kind !== 'dropdown'
          ? '<div class="ec-pv-choices">' + q.choices.map(function (c) {
              return '<span class="ec-pv-choice">' + markHtml(q.kind) + esc(c) + '</span>';
            }).join('') + '</div>'
          : '') +
        (q.kind === 'text' ? '<div class="ec-pv-text">พิมพ์คำตอบ…</div>' : '') +
        '</div>';
    }).join('');
  }

  function renderChecklist() {
    var list = checklist();
    var okCount = list.filter(function (d) { return d.ok; }).length;
    var ready = okCount === list.length;

    $('ec-checklist').innerHTML = list.map(function (d) {
      return '<span class="ec-check' + (d.ok ? ' is-ok' : '') + '">' +
        '<span class="ec-check-dot">' +
          '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>' +
        '</span>' + esc(d.label) + '</span>';
    }).join('');

    var badge = $('ec-done-badge');
    badge.textContent = okCount + '/' + list.length;
    badge.classList.toggle('is-ready', ready);

    $('ec-progress-text').textContent = ready
      ? 'พร้อมบันทึกและเปิดใช้งาน'
      : 'ยังเหลือ ' + (list.length - okCount) + ' ข้อที่ต้องแก้ก่อนเปิดใช้งาน';
    $('ec-save').disabled = !ready;
  }

  /* อัปเดตทุกอย่างที่คำนวณจาก state ยกเว้นรายการคำถาม (ซึ่งมี input ที่อาจกำลังพิมพ์อยู่) */
  function syncDerived() {
    renderPreview();
    renderChecklist();
    $('ec-summary').textContent = questionCount() + ' ข้อ · ' +
      state.items.filter(function (i) { return i.kind === 'section'; }).length + ' หัวข้อส่วน · บังคับตอบ ' +
      state.items.filter(function (i) { return i.required && i.kind !== 'section'; }).length + ' ข้อ';
  }

  function syncAll() {
    renderStages();
    renderItems();
    syncDerived();
  }

  function touch(structural) {
    state.dirty = true;
    if (structural) renderItems();
    syncDerived();
  }

  /* ================= เมนูเพิ่มคำถาม ================= */
  function renderAddMenu() {
    $('ec-add-menu').innerHTML = KIND_ORDER.map(function (k) {
      return '<button type="button" class="ec-add-item" role="menuitem" data-add="' + k + '">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5.5v13M5.5 12h13"/></svg>' +
        esc(KIND_LABELS[k]) + '</button>';
    }).join('');
  }

  function syncAddMenu() {
    $('ec-add-menu').hidden = !state.addOpen;
    $('ec-add-toggle').setAttribute('aria-expanded', String(state.addOpen));
    $('ec-add-toggle').classList.toggle('is-open', state.addOpen);
  }

  function addItem(kind) {
    state.items.push({
      id: state.nextId++,
      title: '',
      kind: kind,
      required: false,
      choices: hasChoices(kind) ? ['ตัวเลือกที่ 1', 'ตัวเลือกที่ 2'] : []
    });
    state.addOpen = false;
    syncAddMenu();
    touch(true);
  }

  function indexOfId(id) {
    return state.items.map(function (i) { return String(i.id); }).indexOf(String(id));
  }

  function move(id, dir) {
    var idx = indexOfId(id);
    var to = idx + dir;
    if (idx < 0 || to < 0 || to >= state.items.length) return;
    var tmp = state.items[idx];
    state.items[idx] = state.items[to];
    state.items[to] = tmp;
    touch(true);
    /* คืนโฟกัสให้ตัวจับของแถวเดิม จะได้กดลูกศรรัวๆ เลื่อนต่อได้ */
    var grip = document.querySelector('[data-grip="' + id + '"]');
    if (grip) grip.focus();
  }

  /* ---------- ลากสลับลำดับ ----------
     ตั้ง draggable ตอนกดที่ตัวจับเท่านั้น ไม่งั้นการลากเลือกข้อความในช่องกรอก
     จะกลายเป็นการลากทั้งแถวไปด้วย */
  var dragId = null;

  document.addEventListener('mousedown', function (e) {
    var grip = e.target.closest('[data-grip]');
    var row = grip && grip.closest('[data-row]');
    if (row) row.setAttribute('draggable', 'true');
  });

  document.addEventListener('mouseup', clearDraggable);

  function clearDraggable() {
    var rows = document.querySelectorAll('[data-row][draggable]');
    Array.prototype.forEach.call(rows, function (r) { r.removeAttribute('draggable'); });
  }

  document.addEventListener('dragstart', function (e) {
    var row = e.target.closest && e.target.closest('[data-row]');
    if (!row || !row.getAttribute('draggable')) return;
    dragId = row.getAttribute('data-row');
    row.classList.add('is-dragging');
    if (e.dataTransfer) {
      e.dataTransfer.effectAllowed = 'move';
      /* Firefox ไม่เริ่มลากถ้าไม่ได้ตั้งข้อมูลไว้ */
      e.dataTransfer.setData('text/plain', dragId);
    }
  });

  document.addEventListener('dragover', function (e) {
    if (dragId === null) return;
    var row = e.target.closest && e.target.closest('[data-row]');
    if (!row) return;
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    clearDropMarks();
    if (row.getAttribute('data-row') === dragId) return;
    var box = row.getBoundingClientRect();
    row.classList.add(e.clientY < box.top + box.height / 2 ? 'is-drop-before' : 'is-drop-after');
  });

  document.addEventListener('drop', function (e) {
    if (dragId === null) return;
    var row = e.target.closest && e.target.closest('[data-row]');
    if (!row) return;
    e.preventDefault();

    var targetId = row.getAttribute('data-row');
    var before = row.classList.contains('is-drop-before');
    endDrag();
    if (targetId === dragId) return;

    var from = indexOfId(dragId);
    var moved = state.items.splice(from, 1)[0];
    var to = indexOfId(targetId);
    state.items.splice(before ? to : to + 1, 0, moved);
    touch(true);
  });

  document.addEventListener('dragend', endDrag);

  function endDrag() {
    var dragging = document.querySelector('.is-dragging');
    if (dragging) dragging.classList.remove('is-dragging');
    clearDropMarks();
    clearDraggable();
    dragId = null;
  }

  function clearDropMarks() {
    var marked = document.querySelectorAll('.is-drop-before, .is-drop-after');
    Array.prototype.forEach.call(marked, function (r) {
      r.classList.remove('is-drop-before', 'is-drop-after');
    });
  }

  /* ================= เหตุการณ์ ================= */
  $('ec-name').addEventListener('input', function () { state.name = this.value; touch(false); });
  $('ec-desc').addEventListener('input', function () { state.desc = this.value; touch(false); });

  /* พิมพ์ในช่องคำถาม/ตัวเลือก -> อัปเดตแค่ state กับแผงขวา ไม่เรนเดอร์รายการใหม่ */
  document.addEventListener('input', function (e) {
    var title = e.target.closest('[data-title]');
    if (title) {
      var q = itemById(title.getAttribute('data-title'));
      if (q) { q.title = title.value; touch(false); }
      return;
    }
    var choice = e.target.closest('[data-choice]');
    if (choice) {
      var parts = choice.getAttribute('data-choice').split(':');
      var item = itemById(parts[0]);
      if (item) { item.choices[Number(parts[1])] = choice.value; touch(false); }
    }
  });

  document.addEventListener('change', function (e) {
    var req = e.target.closest('[data-required]');
    if (req) {
      var q = itemById(req.getAttribute('data-required'));
      if (q) { q.required = req.checked; touch(false); }
    }
  });

  document.addEventListener('click', function (e) {
    var t = e.target;

    var stage = t.closest('[data-stage]');
    if (stage) {
      state.stage = stage.getAttribute('data-stage');
      renderStages();
      /* ป้ายกำกับ "บังคับตอบ" เปลี่ยนตามช่วง จึงต้องวาดรายการใหม่ด้วย */
      return touch(true);
    }

    var round = t.closest('[data-round]');
    if (round) {
      var r = round.getAttribute('data-round');
      var i = state.rounds.indexOf(r);
      if (i > -1) state.rounds.splice(i, 1); else state.rounds.push(r);
      renderRounds();
      state.dirty = true;
      return;
    }

    var kind = t.closest('[data-kind]');
    if (kind) {
      var kp = kind.getAttribute('data-kind').split(':');
      var kq = itemById(kp[1]);
      if (!kq) return;
      kq.kind = kp[0];
      if (hasChoices(kq.kind)) {
        if (!kq.choices.length) kq.choices = ['ตัวเลือกที่ 1', 'ตัวเลือกที่ 2'];
      } else {
        kq.choices = [];
      }
      return touch(true);
    }

    var rm = t.closest('[data-remove]');
    if (rm) {
      var id = rm.getAttribute('data-remove');
      state.items = state.items.filter(function (x) { return String(x.id) !== String(id); });
      return touch(true);
    }

    var addChoice = t.closest('[data-add-choice]');
    if (addChoice) {
      var aq = itemById(addChoice.getAttribute('data-add-choice'));
      if (aq) { aq.choices.push('ตัวเลือกใหม่'); touch(true); }
      return;
    }

    var rmChoice = t.closest('[data-remove-choice]');
    if (rmChoice) {
      var cp = rmChoice.getAttribute('data-remove-choice').split(':');
      var cq = itemById(cp[0]);
      if (cq) { cq.choices.splice(Number(cp[1]), 1); touch(true); }
      return;
    }

    if (t.closest('#ec-add-section')) {
      state.items.push({ id: state.nextId++, title: '', kind: 'section', required: false, choices: [] });
      return touch(true);
    }

    if (t.closest('#ec-add-toggle')) {
      state.addOpen = !state.addOpen;
      return syncAddMenu();
    }

    var add = t.closest('[data-add]');
    if (add) return addItem(add.getAttribute('data-add'));

    if (t.closest('#ec-save-draft')) return saveDraft(true);

    if (t.closest('#ec-save') && !$('ec-save').disabled) {
      state.dirty = false;
      if (window.TFC.showToast) window.TFC.showToast('บันทึกและเปิดใช้งานแบบประเมินเรียบร้อย', 'success');
      return;
    }

    if (state.addOpen && !t.closest('.ec-add-wrap')) {
      state.addOpen = false;
      syncAddMenu();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (dragId !== null) endDrag();
      if (state.addOpen) { state.addOpen = false; syncAddMenu(); }
      return;
    }
    /* คนที่ใช้คีย์บอร์ดหรือ screen reader ลากไม่ได้ จึงต้องมีทางเลื่อนลำดับด้วยลูกศร */
    var grip = e.target.closest && e.target.closest('[data-grip]');
    if (grip && (e.key === 'ArrowUp' || e.key === 'ArrowDown')) {
      e.preventDefault();
      move(grip.getAttribute('data-grip'), e.key === 'ArrowUp' ? -1 : 1);
    }
  });

  /* ---------- บันทึกร่าง ---------- */
  function saveDraft(manual) {
    state.dirty = false;
    var now = new Date();
    $('ec-autosave').hidden = false;
    $('ec-autosave-text').textContent = 'บันทึกร่างล่าสุด ' +
      String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ' น.';
    if (manual && window.TFC.showToast) window.TFC.showToast('บันทึกฉบับร่างเรียบร้อย', 'success');
  }

  setInterval(function () { if (state.dirty) saveDraft(false); }, AUTOSAVE_MS);

  window.addEventListener('beforeunload', function (e) {
    if (!state.dirty) return;
    e.preventDefault();
    e.returnValue = '';
  });

  $('ec-cancel').addEventListener('click', function (e) {
    if (!state.dirty) return;
    if (!window.confirm('ยังไม่ได้บันทึกการเปลี่ยนแปลง ต้องการออกจากหน้านี้หรือไม่')) e.preventDefault();
  });

  /* ---------- เริ่มต้น ---------- */
  renderAddMenu();
  syncAddMenu();
  syncAll();
})();
