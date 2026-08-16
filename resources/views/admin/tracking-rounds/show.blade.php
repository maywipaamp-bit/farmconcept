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

@push('page-script')
<script>
/* หน้ารายละเอียดรอบติดตาม

   แยกคนสองกลุ่มเสมอ:
   - แจ้งเตือนได้ (ผูก LINE) → ระบบส่งให้ ดูสถานะการส่ง/การตอบได้
   - แจ้งเตือนไม่ได้ → ระบบส่งไม่ได้ ต้องให้แอดมินติดตามนอกระบบและบันทึกผลไว้ */
(function () {
  var batch = @json($batch);
  var NOTE_KINDS = @json(config('farmconcept.tracking_round.offline_kinds'));
  var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  var esc = window.TFC.escapeHtml;
  var fmt = window.TFC.formatThaiDate;
  var $ = function (id) { return document.getElementById(id); };

  var GROUPS = ['แจ้งเตือนได้ (LINE)', 'แจ้งเตือนไม่ได้ (ติดตามนอกระบบ)'];
  var state = { group: GROUPS[0], editId: null, notifying: false };

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
            '<span>ตอบแล้ว ' + batch.answered + '/' + batch.total + ' คน</span>' +
          '</div>' +
        '</div>' +
      '</div>' +
      '<div>' +
        '<button type="button" class="btn btn-outline" id="rd-notify"' +
          (batch.cancelled || batch.total === 0 || state.notifying ? ' disabled' : '') + '>' +
          (state.notifying ? 'กำลังส่ง…' : 'ส่งแจ้งเตือนอีกครั้ง') + '</button>' +
      '</div>';
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
        '</div>';
    }).join('');

    return '<div class="card fb-table-card">' +
      '<div class="fb-table-scroll"><div class="fb-table fb-member-table">' +
        '<div class="fb-tr fb-th">' +
          '<div>ชื่อ / รหัส</div><div>เบอร์โทร</div><div>พื้นที่</div>' +
          '<div>รอบที่ติดตาม</div><div>ครบกำหนด</div><div>การส่ง</div><div>สถานะ</div>' +
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
        '</div>' + (rows || emptyHtml('ทุกคนในรอบนี้ผูก LINE แล้ว')) +
      '</div></div></div>';
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

    if (t.closest('#rd-notify')) {
      if ($('rd-notify').disabled) return;
      state.notifying = true;
      renderHeader();
      api('{{ route('admin.tracking-rounds.send-notify', $batch['code']) }}', 'POST').then(function (res) {
        state.notifying = false;
        if (!res.ok) { renderHeader(); return window.TFC.showToast(res.body.message || 'ส่งแจ้งเตือนไม่สำเร็จ', 'danger'); }
        /* โหลดรอบใหม่ทั้งก้อนเพื่อให้สถานะการส่งรายคนตรงกับที่บันทึกจริง */
        return api('{{ route('admin.tracking-rounds.show', $batch['code']) }}', 'GET').then(function (fresh) {
          if (fresh.ok) batch = fresh.body.data;
          render();
          window.TFC.showToast(res.body.message, res.body.notify.sent > 0 ? 'success' : 'warning');
        });
      });
      return;
    }

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

  render();
})();
</script>
@endpush
