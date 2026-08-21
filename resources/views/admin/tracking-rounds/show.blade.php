@extends('layouts.admin')

@section('title', $batch['name'])

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a> <span>/</span>
    <a href="{{ route('admin.tracking-rounds.index') }}">รอบติดตาม</a> <span>/</span>
    <span class="is-current">{{ $batch['name'] }}</span>
  </nav>

  <div class="fb-detail-header" id="rd-header"></div>

  <div class="fb-group-bar">
    <div class="cd-segmented" id="rd-groups"></div>
    <span class="fb-count" id="rd-count"></span>
  </div>

  <div id="rd-panel"></div>
@endsection

@section('modals')
{{-- คีย์คำตอบจากกระดาษ — สำหรับกลุ่มตัวอย่างที่ทำแบบประเมินในแอปเองไม่ได้
     คำถามวาดจากโครงชุดเดียวกับที่ผู้ตอบเห็น เจ้าหน้าที่จึงไล่คีย์ตามกระดาษได้ตรงบรรทัด --}}
<div class="modal-overlay" id="rd-answer-modal">
  <div class="modal rd-answer-modal" role="dialog" aria-modal="true" aria-labelledby="rd-answer-title">
    <div class="modal-header">
      <h3 class="modal-title" id="rd-answer-title">คีย์คำตอบจากกระดาษ</h3>
      <button type="button" class="modal-close" data-close-modal aria-label="ปิด">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p class="text-secondary small" id="rd-answer-who"></p>
      <p class="rd-answer-hint">ช่องที่มี <span class="form-required">*</span> จำเป็นต้องกรอก · ระบบจะบันทึกว่าคุณเป็นผู้คีย์แทน</p>
      <div id="rd-answer-body"></div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" data-close-modal>ยกเลิก</button>
      <button type="button" class="btn btn-primary" id="rd-answer-save">บันทึกคำตอบ</button>
    </div>
  </div>
</div>
@endsection

@push('page-script')
<script>
/* หน้ารายละเอียดรอบติดตาม

   แยกคนสองกลุ่มเสมอ:
   - แจ้งเตือนได้ (ผูก LINE) → ระบบส่งให้ ดูสถานะการส่ง/การตอบได้
   - แจ้งเตือนไม่ได้ → ระบบส่งไม่ได้ ต้องให้แอดมินติดตามนอกระบบและบันทึกผลไว้ */
(function () {
  var batch = @json($batch);
  var NOTE_KINDS = @json(config('farmconcept.tracking_round.offline_kinds'));
  var QUESTIONS = @json($formQuestions);
  /* ป้ายคะแนนชุดเดียวกับที่ผู้ตอบเห็น ไม่งั้นคนคีย์ตีความคะแนนคนละแบบกับคนตอบ */
  var RATING = @json(array_values(config('farmconcept.tracking_round.rating_labels')));
  var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  var esc = window.TFC.escapeHtml;
  var fmt = window.TFC.formatThaiDate;
  var $ = function (id) { return document.getElementById(id); };

  var GROUPS = ['แจ้งเตือนได้ (LINE)', 'แจ้งเตือนไม่ได้ (ติดตามนอกระบบ)'];
  var state = { group: GROUPS[0], editId: null, answerFor: null, saving: false };

  var STATE_CLASS = {
    'กำลังดำเนินการ': 'is-active', 'รอเริ่ม': 'is-waiting',
    'เสร็จสิ้น': 'is-done', 'ยกเลิกแล้ว': 'is-cancelled'
  };
  var MEMBER_CLASS = {
    'ตอบแล้ว': 'is-done', 'รอติดตาม': 'is-due',
    'เกินกำหนด': 'is-over', 'ยังไม่ถึงกำหนด': 'is-idle'
  };

  function notifiable() { return batch.members.filter(function (p) { return p.line; }); }
  function unreachable() { return batch.members.filter(function (p) { return !p.line; }); }

  function renderHeader() {
    $('rd-header').innerHTML =
      '<div class="fb-detail-main">' +
        '<a class="cd-back" href="{{ route('admin.tracking-rounds.index') }}" aria-label="ย้อนกลับไปรายการรอบติดตาม">' +
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 6l-6 6 6 6"/></svg>' +
        '</a>' +
        '<div class="fb-detail-text">' +
          '<div class="fb-title-line">' +
            '<h1 class="fb-title">' + esc(batch.name) + '</h1>' +
            '<span class="fb-state ' + STATE_CLASS[batch.state] + '">' + esc(batch.state) + '</span>' +
          '</div>' +
          '<div class="cd-meta">' +
            '<span>ครบกำหนด ' + esc(fmt(batch.from)) + ' – ' + esc(fmt(batch.to)) + '</span>' +
            '<span>' + esc(batch.form) + '</span>' +
            (batch.answerDue ? '<span>ตอบได้ถึง ' + esc(fmt(batch.answerDue)) + '</span>' : '') +
            '<span>ตอบแล้ว ' + batch.answered + '/' + batch.total + ' คน</span>' +
          '</div>' +
        '</div>' +
      '</div>' +
      /* ปุ่มส่งทั้งรอบถูกตัดออก — ส่งซ้ำทั้งก้อนยิงถึงคนที่ตอบไปแล้วด้วย เป็นการรบกวนที่ไม่มีเหตุผล
         ตอนนี้ส่งเป็นรายคนได้จากตารางด้านล่าง ซึ่งเห็นสถานะของแต่ละคนก่อนกด */
      '';
  }

  function renderGroups() {
    var n = notifiable().length, u = unreachable().length;
    $('rd-groups').innerHTML = GROUPS.map(function (g, i) {
      var on = state.group === g;
      return '<button type="button" class="cd-seg' + (on ? ' is-on' : '') + '" data-group="' + esc(g) + '">' +
        esc(g) + ' · ' + (i === 0 ? n : u) + '</button>';
    }).join('');
  }

  function emptyHtml(text) {
    return '<div class="fb-empty"><span class="fb-empty-title">' + esc(text) + '</span></div>';
  }

  function personCell(p) {
    /* รหัสใต้ชื่อเป็นรหัสกลุ่มตัวอย่าง (CHT-xxxx) — รหัสเดียวกับที่ใช้ในรายงานวิจัย
       ชื่อยังแสดงเพราะหน้านี้เป็นงานปฏิบัติการ (โทรตาม/แจ้งเตือน) และอาจเป็นนามแฝงอยู่แล้ว */
    return '<div class="fb-name-cell">' +
      '<a class="fb-name" href="{{ url('admin/cohort') }}/' + p.cohortId + '">' + esc(p.name) + '</a>' +
      '<span class="fb-pid">' + esc(p.cohortCode || p.pid) + '</span>' +
      '</div>';
  }

  /* ปุ่มคีย์คำตอบจากกระดาษ — มีเมื่อยังไม่ตอบ และแบบประเมินมีคำถามให้คีย์จริง
     ไม่มีคำถามเลยแปลว่าตั้งค่าฝั่งแบบประเมินยังไม่เสร็จ เปิด popup ไปก็เจอกล่องเปล่า */
  function keyInButton(p) {
    if (batch.cancelled || answerable().length === 0) return '';

    return '<button type="button" class="cd-mini-btn" data-key-in="' + esc(p.memberId) + '">คีย์คำตอบ</button>';
  }

  /* หัวข้อคั่นไม่ใช่คำถาม ไม่มีคำตอบให้คีย์ */
  function answerable() {
    return QUESTIONS.filter(function (q) { return q.type !== 'section'; });
  }

  /* ---------- กลุ่มที่แจ้งเตือนได้ ---------- */
  function renderNotifiable() {
    var list = notifiable();
    $('rd-count').textContent = 'ส่งแจ้งเตือนได้ ' + list.length + ' คน · ตอบแล้ว ' +
      list.filter(function (p) { return p.answered; }).length + ' คน';

    var rows = list.map(function (p) {
      /* สถานะการส่งอ่านจากผลจริงที่บันทึกไว้ ไม่ใช่เขียนว่า "ส่งแล้ว" ทุกแถว
         ถ้าส่งไม่ผ่านต้องเห็นตรงนี้ ไม่งั้นแอดมินเชื่อว่าเขาได้รับแล้ว */
      var sent = p.notifyResult === 'ส่งสำเร็จ';
      var notYet = !p.notifyResult;

      return '<div class="fb-tr">' +
        personCell(p) +
        '<div class="fb-cell fb-nums">' + esc(p.phone) + '</div>' +
        '<div class="fb-cell">' + esc(p.area) + '</div>' +
        '<div class="fb-cell">' + esc(p.round) + '</div>' +
        '<div class="fb-cell fb-nums">' + esc(fmt(p.due)) + '</div>' +
        '<div><span class="cd-badge ' + (sent ? 'is-good' : (notYet ? 'is-idle' : 'is-warn')) + '">' +
          esc(notYet ? 'ยังไม่ส่ง' : p.notifyResult) + '</span></div>' +
        '<div><span class="cd-badge ' + MEMBER_CLASS[p.state] + '">' + esc(p.state) + '</span></div>' +
        /* ส่งทีละคนโดยเห็นสถานะของคนนั้นอยู่ตรงหน้า — คนที่ตอบไปแล้วไม่ต้องส่งซ้ำให้รบกวน
           ปุ่มจึงปิดไว้ ต่างจากการส่งทั้งรอบที่ยิงถึงทุกคนพร้อมกัน */
        '<div class="text-center rd-row-actions">' +
          (p.state === 'ตอบแล้ว'
            ? '<span class="text-muted">—</span>'
            : '<button type="button" class="btn btn-outline btn-sm" data-notify-member="' + esc(p.memberId) + '"' +
                (batch.cancelled ? ' disabled' : '') + '>' + (sent ? 'ส่งอีกครั้ง' : 'ส่งแจ้งเตือน') + '</button>' +
              keyInButton(p)) +
        '</div>' +
        '</div>';
    }).join('');

    return '<div class="card fb-table-card">' +
      '<div class="fb-table-scroll"><div class="fb-table fb-member-table">' +
        '<div class="fb-tr fb-th">' +
          '<div>ชื่อ / รหัส</div><div>เบอร์โทร</div><div>พื้นที่</div>' +
          '<div>รอบที่ติดตาม</div><div>ครบกำหนด</div><div>การส่ง</div><div>สถานะ</div>' +
          '<div class="text-center">แจ้งเตือน</div>' +
        '</div>' + (rows || emptyHtml('ไม่มีคนที่แจ้งเตือนได้ในรอบนี้')) +
      '</div></div></div>';
  }

  /* ---------- กลุ่มที่แจ้งเตือนไม่ได้ ---------- */
  function renderUnreachable() {
    var list = unreachable();
    var logged = list.filter(function (p) { return p.offlineNote; }).length;
    $('rd-count').textContent = 'ต้องติดตามเอง ' + list.length + ' คน · บันทึกผลแล้ว ' + logged + ' คน';

    var rows = list.map(function (p) {
      var editing = state.editId === p.memberId;

      return '<div class="fb-tr">' +
        personCell(p) +
        '<div class="fb-cell fb-nums">' + esc(p.phone) + '</div>' +
        '<div class="fb-cell">' + esc(p.round) + '</div>' +
        '<div class="fb-cell fb-nums">' + esc(fmt(p.due)) + '</div>' +
        '<div><span class="cd-badge ' + MEMBER_CLASS[p.state] + '">' + esc(p.state) + '</span></div>' +
        '<div class="fb-offline">' +
          (editing
            ? '<select class="select fb-offline-kind" data-kind="' + p.memberId + '">' +
                NOTE_KINDS.map(function (k) {
                  return '<option value="' + esc(k) + '"' + (p.offlineKind === k ? ' selected' : '') + '>' + esc(k) + '</option>';
                }).join('') +
              '</select>' +
              '<input type="text" class="input fb-offline-note" data-note="' + p.memberId +
                '" placeholder="ผลการติดตาม" value="' + esc(p.offlineNote || '') + '">' +
              '<button type="button" class="btn btn-primary btn-sm" data-save="' + p.memberId + '">บันทึก</button>' +
              '<button type="button" class="btn btn-outline btn-sm" data-cancel-edit="1">ยกเลิก</button>'
            : (p.offlineNote
                ? '<span class="fb-offline-done">' + esc(p.offlineKind) + ' · ' + esc(p.offlineNote) + '</span>' +
                  '<span class="fb-offline-by">' + esc(fmt((p.offlineAt || '').slice(0, 10))) + ' · ' + esc(p.offlineBy || '') + '</span>' +
                  '<button type="button" class="cd-mini-btn" data-edit="' + p.memberId + '">แก้ไข</button>'
                : '<button type="button" class="cd-mini-btn" data-edit="' + p.memberId + '">บันทึกผลติดตาม</button>')) +
        '</div>' +
        '<div class="text-center">' + (p.state === 'ตอบแล้ว' ? '<span class="text-muted">—</span>' : keyInButton(p)) + '</div>' +
        '</div>';
    }).join('');

    /* ทางแก้ระยะยาวของกลุ่มนี้คือให้เขาผูก LINE ไม่ใช่ให้แอดมินโทรตามทุกรอบ */
    return '<div class="fb-hint fb-hint-stack">' +
      '<span>คนกลุ่มนี้ยังไม่ผูก LINE ระบบจึงส่งแจ้งเตือนให้ไม่ได้ — ชวนให้ผูก LINE หรือติดตามเองแล้วบันทึกผลไว้ที่นี่</span>' +
      '</div>' +
      '<div class="card fb-table-card">' +
      '<div class="fb-table-scroll"><div class="fb-table fb-offline-table">' +
        '<div class="fb-tr fb-th">' +
          '<div>ชื่อ / รหัส</div><div>เบอร์โทร</div><div>รอบที่ติดตาม</div>' +
          '<div>ครบกำหนด</div><div>สถานะ</div><div>ผลการติดตามนอกระบบ</div>' +
          '<div class="text-center">คำตอบ</div>' +
        '</div>' + (rows || emptyHtml('ทุกคนในรอบนี้ผูก LINE แล้ว')) +
      '</div></div></div>';
  }

  /* ---------- คีย์คำตอบจากกระดาษ ----------
     วาดคำถามชุดเดียวกับที่ผู้ตอบเห็น เรียงตามลำดับเดิม เจ้าหน้าที่จึงไล่คีย์ตามกระดาษได้ตรงบรรทัด
     ไม่แบ่งทีละข้อแบบฝั่งผู้ตอบ — คนคีย์มีคำตอบครบอยู่ในมือแล้ว กดผ่านทีละข้อมีแต่ช้าลง */
  function answerFieldHtml(q, n) {
    var name = 'answer_' + q.id;
    var star = q.required ? '<span class="form-required">*</span>' : '';
    var head = '<span class="co-field-label">ข้อ ' + n + ': ' + esc(q.text) + star + '</span>';
    var body;

    if (q.type === 'rating') {
      body = '<select class="select" data-answer="' + q.id + '"><option value="">— ไม่ระบุ —</option>' +
        RATING.map(function (label, i) {
          return '<option value="' + (i + 1) + '">' + esc((i + 1) + ' · ' + label) + '</option>';
        }).join('') + '</select>';
    } else if (q.type === 'paragraph') {
      body = '<textarea class="input" rows="3" maxlength="5000" data-answer="' + q.id + '"></textarea>';
    } else if (q.type === 'text') {
      body = '<input type="text" class="input" maxlength="5000" data-answer="' + q.id + '">';
    } else if (q.type === 'consent') {
      body = '<label class="rd-answer-consent"><input type="checkbox" data-answer="' + q.id + '" value="1">' +
        '<span>ผู้ตอบยอมรับตามที่ระบุในเอกสาร</span></label>';
    } else if (q.type === 'multi' || q.type === 'chips') {
      body = '<div class="rd-answer-options">' + q.options.map(function (o) {
        return '<label class="rd-answer-option"><input type="checkbox" data-answer-multi="' + q.id +
          '" value="' + o.id + '"><span>' + esc(o.label) + '</span></label>';
      }).join('') + '</div>';
    } else {
      body = '<div class="rd-answer-options">' + q.options.map(function (o) {
        return '<label class="rd-answer-option"><input type="radio" name="' + name +
          '" data-answer="' + q.id + '" value="' + o.id + '"><span>' + esc(o.label) + '</span></label>';
      }).join('') + '</div>';
    }

    return '<div class="co-field rd-answer-field">' + head + body + '</div>';
  }

  function openAnswerModal(memberId) {
    var p = batch.members.find(function (m) { return m.memberId === Number(memberId); });
    if (!p) return;

    state.answerFor = Number(memberId);

    $('rd-answer-who').textContent = p.name + ' · ' + (p.cohortCode || p.pid) + ' · รอบ ' + p.round;

    var n = 0;
    $('rd-answer-body').innerHTML = QUESTIONS.map(function (q) {
      if (q.type === 'section') return '<p class="rd-answer-section">' + esc(q.text) + '</p>';
      n += 1;
      return answerFieldHtml(q, n);
    }).join('');

    window.TFC.openModal('rd-answer-modal');
  }

  /* เก็บค่าจาก popup — ส่งเฉพาะข้อที่คีย์ไว้จริง ข้อที่เว้นว่างไม่ต้องส่งไปให้ตัวตรวจ
     ฝั่งเซิร์ฟเวอร์จะเป็นคนบอกเองว่าข้อบังคับตอบข้อไหนขาด */
  function collectAnswers() {
    var out = {};

    $('rd-answer-body').querySelectorAll('[data-answer]').forEach(function (el) {
      var id = el.getAttribute('data-answer');
      if (el.type === 'radio') { if (el.checked) out['answer_' + id] = el.value; return; }
      if (el.type === 'checkbox') { if (el.checked) out['answer_' + id] = '1'; return; }
      if (el.value.trim() !== '') out['answer_' + id] = el.value.trim();
    });

    $('rd-answer-body').querySelectorAll('[data-answer-multi]:checked').forEach(function (el) {
      var key = 'answer_' + el.getAttribute('data-answer-multi');
      (out[key] = out[key] || []).push(el.value);
    });

    return out;
  }

  function renderPanel() {
    $('rd-panel').innerHTML = state.group === GROUPS[0] ? renderNotifiable() : renderUnreachable();
  }

  function render() {
    renderHeader();
    renderGroups();
    renderPanel();
  }

  function api(url, method, body) {
    return fetch(url, {
      method: method,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: body ? JSON.stringify(body) : undefined
    }).then(function (res) { return res.json().then(function (b) { return { ok: res.ok, body: b }; }); });
  }

  document.addEventListener('click', function (e) {
    var t = e.target;

    var g = t.closest('[data-group]');
    if (g) { state.group = g.getAttribute('data-group'); state.editId = null; renderGroups(); return renderPanel(); }

    var one = t.closest('[data-notify-member]');
    if (one) {
      if (one.disabled) return;
      one.disabled = true;
      one.textContent = 'กำลังส่ง…';

      api('{{ url('admin/tracking-rounds') }}/{{ $batch['code'] }}/members/'
        + encodeURIComponent(one.getAttribute('data-notify-member')) + '/notify', 'POST').then(function (res) {
        if (res.ok && res.body.data) batch = res.body.data;
        render();
        window.TFC.showToast(res.body.message || 'ส่งแจ้งเตือนไม่สำเร็จ',
          res.ok && res.body.success ? 'success' : 'warning');
      });
      return;
    }

    var keyIn = t.closest('[data-key-in]');
    if (keyIn) return openAnswerModal(keyIn.getAttribute('data-key-in'));

    var ed = t.closest('[data-edit]');
    if (ed) { state.editId = Number(ed.getAttribute('data-edit')); return renderPanel(); }

    if (t.closest('[data-cancel-edit]')) { state.editId = null; return renderPanel(); }

    var save = t.closest('[data-save]');
    if (save) {
      var id = save.getAttribute('data-save');
      var kind = document.querySelector('[data-kind="' + id + '"]').value;
      var note = document.querySelector('[data-note="' + id + '"]').value.trim();
      if (!note) return window.TFC.showToast('กรุณากรอกผลการติดตาม', 'warning');

      save.disabled = true;
      save.textContent = 'กำลังบันทึก…';

      api('{{ url('admin/tracking-rounds/'.$batch['code'].'/members') }}/' + id + '/offline-log', 'POST',
        { kind: kind, note: note }).then(function (res) {
        if (!res.ok) {
          save.disabled = false;
          save.textContent = 'บันทึก';
          return window.TFC.showToast(res.body.message || 'บันทึกไม่สำเร็จ', 'danger');
        }
        var i = batch.members.findIndex(function (m) { return m.memberId === Number(id); });
        if (i > -1) batch.members[i] = res.body.data;
        state.editId = null;
        renderPanel();
        window.TFC.showToast('บันทึกผลการติดตามแล้ว', 'success');
      });
      return;
    }
  });

  /* บันทึกคำตอบที่คีย์ — ปุ่มเข้าสถานะรอทันทีและกดซ้ำไม่ได้
     คีย์ซ้ำสองครั้งคือคำตอบสองใบของคนเดียวในรอบเดียว ซึ่งฝั่งเซิร์ฟเวอร์กันไว้แล้ว
     แต่ผู้ใช้ควรเห็นตั้งแต่ปุ่มว่ากำลังทำงานอยู่ ไม่ใช่กดแล้วเงียบ */
  $('rd-answer-save').addEventListener('click', function () {
    if (state.saving || !state.answerFor) return;

    var button = this;
    var id = state.answerFor;

    state.saving = true;
    button.disabled = true;
    button.textContent = 'กำลังบันทึก…';

    api('{{ url('admin/tracking-rounds/'.$batch['code'].'/members') }}/' + id + '/record-answers',
      'POST', collectAnswers()).then(function (res) {
      state.saving = false;
      button.disabled = false;
      button.textContent = 'บันทึกคำตอบ';

      if (!res.ok) {
        /* ข้อความของข้อบังคับตอบอยู่ใน errors ไม่ใช่ message — ต้องหยิบมาแสดง
           ไม่งั้นผู้คีย์เห็นแค่ "บันทึกไม่สำเร็จ" โดยไม่รู้ว่าขาดข้อไหน */
        var detail = res.body && res.body.errors
          ? res.body.errors[Object.keys(res.body.errors)[0]][0]
          : (res.body && res.body.message);

        return window.TFC.showToast(detail || 'บันทึกคำตอบไม่สำเร็จ', 'danger');
      }

      var i = batch.members.findIndex(function (m) { return m.memberId === id; });
      if (i > -1) batch.members[i] = res.body.data;

      state.answerFor = null;
      window.TFC.closeModal('rd-answer-modal');
      render();
      window.TFC.showToast('บันทึกคำตอบแล้ว', 'success');
    });
  });

  render();
})();
</script>
@endpush
