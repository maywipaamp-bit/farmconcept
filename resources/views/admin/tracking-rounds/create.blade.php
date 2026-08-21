@extends('layouts.admin')

@section('title', 'สร้างรอบติดตาม')

@section('main-class', 'fb-create-content')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a> <span>/</span>
    <a href="{{ route('admin.tracking-rounds.index') }}">รอบติดตาม</a> <span>/</span>
    <span class="is-current">สร้างรอบติดตาม</span>
  </nav>

  <div class="fb-detail-header">
    <div class="fb-detail-main">
      <a class="cd-back" href="{{ route('admin.tracking-rounds.index') }}" aria-label="ย้อนกลับไปรายการรอบติดตาม">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 6l-6 6 6 6"/></svg>
      </a>
      <h1 class="fb-title">สร้างรอบติดตาม</h1>
    </div>
  </div>

  <section class="card fb-create-card">
    <div class="fb-form-grid" id="rc-form"></div>

    <div class="fb-target-row">
      <span class="co-field-label">กลุ่มเป้าหมาย</span>
      <div class="co-chips" id="rc-targets"></div>
      <button type="button" class="btn btn-outline btn-sm" id="rc-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="16.2" y1="16.2" x2="21" y2="21"/></svg>
        ค้นหารายชื่อ</button>
    </div>

    <div id="rc-result"></div>

    <div class="fb-msg-row">
      <div class="fb-msg-col">
        <div class="cd-field-head">
          <span class="co-field-label">ข้อความแจ้งเตือน LINE</span>
          <button type="button" class="co-link" id="rc-msg-reset">คืนค่าเริ่มต้น</button>
        </div>
        <textarea class="input fb-msg" id="rc-msg" rows="4"></textarea>
        {{-- ตัวแปรเป็นปุ่มกดแทรก ไม่ใช่ข้อความให้พิมพ์ตาม — พิมพ์เองผิดวรรณยุกต์เดียวระบบก็ไม่แทนค่าให้ --}}
        <div class="fb-msg-hint">
          แตะเพื่อแทรกตัวแปร (ระบบแทนค่าให้รายคน):
          @foreach($placeholders as $placeholder)
            <button type="button" class="cd-mini-btn" data-insert="{{ $placeholder }}">{{ $placeholder }}</button>
          @endforeach
        </div>
      </div>
      <div class="fb-msg-col">
        <span class="co-field-label">ตัวอย่างที่ผู้รับเห็น</span>

        {{-- โชว์เฉพาะการ์ด LINE OA — แจ้งเตือนเด้งกับหน้าจอล็อกถูกตัดออก (คำสั่งทีม)
             ข้อความสองที่นั่นก็ชุดเดียวกับ altText ของการ์ดนี้อยู่แล้ว ไม่ได้เพิ่มข้อมูลใหม่ --}}
        <div class="cd-notif-previews">
          <div class="cd-notif">
            <span class="cd-notif-tag">LINE OA</span>
            {{-- โครงเดียวกับการ์ด Flex ที่ส่งจริง (LinePushService::pushSurveyInvite) — แก้ฝั่งนั้นต้องแก้ฝั่งนี้ตาม --}}
            <div class="cd-line-preview">
              <div class="cd-bubble">
                <span class="cd-bubble-hero" aria-hidden="true">💚</span>
                <span class="cd-bubble-title">แบบประเมินสุขภาวะ</span>
                <span class="cd-bubble-text is-center" id="rc-bubble"></span>
                <span class="cd-bubble-meta" id="rc-bubble-round"></span>
                <span class="cd-bubble-due" id="rc-bubble-due"></span>
                <span class="cd-bubble-btn">เริ่มทำแบบประเมิน</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection

@section('after-content')
  <div class="fb-bottombar">
    <div class="fb-bottombar-inner">
      <span class="fb-bottom-text" id="rc-summary"></span>
      <div class="fb-bottom-actions">
        <a class="btn btn-ghost" href="{{ route('admin.tracking-rounds.index') }}">ยกเลิก</a>
        <button type="button" class="btn btn-outline" id="rc-draft" disabled>บันทึกร่าง</button>
        <button type="button" class="btn btn-primary" id="rc-submit" disabled>สร้างรอบและส่งแจ้งเตือน</button>
      </div>
    </div>
  </div>
@endsection

@push('page-script')
<script>
/* หน้าสร้างรอบติดตาม

   การค้นหารายชื่อและการแบ่งหน้าทำที่ฝั่งเซิร์ฟเวอร์ทั้งหมด
   ทุกครั้งที่เปลี่ยนหน้าจะยิง /admin/tracking-rounds/eligible-members ใหม่พร้อม page/page_size
   หน้าจอไม่เคยถือรายชื่อทั้งหมด — ถือแค่ "รายการที่ติ๊กไว้" เป็นชุด id
   ตัวเลือกจึงคงอยู่เมื่อเปลี่ยนหน้า */
(function () {
  var FORMS = @json($forms);
  var TARGETS = @json($targetGroups);
  var DEFAULT_MSG = @json($defaultMessage);
  var TODAY = @json($today);
  var MONTH_START = @json($monthStart);
  var MONTH_END = @json($monthEnd);
  var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  var esc = window.TFC.escapeHtml;
  var fmt = window.TFC.formatThaiDate;
  var $ = function (id) { return document.getElementById(id); };

  var PAGE_SIZES = [10, 20, 50];

  var form = {
    name: '', formId: FORMS.length ? FORMS[0].value : '',
    from: MONTH_START, to: MONTH_END, answerDue: '', targets: [], msg: DEFAULT_MSG
  };

  var search = {
    done: false, loading: false, page: 1, pageSize: PAGE_SIZES[0],
    total: 0, rows: [], allIds: [], notifiableIds: []
  };

  /* เก็บเฉพาะ id ที่ติ๊กไว้ ไม่ได้เก็บทั้งแถว ข้อมูลจึงไม่หายตอนเปลี่ยนหน้า */
  var picked = {};
  var saving = false;

  function pickedIds() { return Object.keys(picked).filter(function (k) { return picked[k]; }).map(Number); }
  function pickedCount() { return pickedIds().length; }

  /* ---------- ฟอร์มด้านบน ---------- */
  function renderForm() {
    function field(label, req, control, hint) {
      /* คำอธิบายยาวเป็นไอคอนให้เมาส์ชี้ดู (title ของเบราว์เซอร์) — ไม่กินที่ใต้ฟิลด์ */
      var hintIcon = hint
        ? '<span class="co-hint-icon" title="' + esc(hint) + '" aria-label="' + esc(hint) + '" tabindex="0">' +
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><circle cx="12" cy="7.6" r="0.4" fill="currentColor"/></svg>' +
          '</span>'
        : '';

      return '<label class="co-field">' +
        '<span class="co-field-label">' + esc(label) + (req ? '<span class="form-required">*</span>' : '') + hintIcon + '</span>' +
        control + '</label>';
    }

    $('rc-form').innerHTML =
      field('ชื่อรอบติดตาม', true, '<input type="text" class="input" id="rc-name" value="' + esc(form.name) +
        '" placeholder="เช่น ติดตามกลุ่มตัวอย่าง ก.ย. 2569">') +
      /* แบบประเมินล็อกไว้ชนิดติดตามสุขภาพเท่านั้น รายการมาจากฐาน ไม่ได้เขียนตายที่นี่ */
      field('แบบประเมินที่ใช้', true, '<select class="select" id="rc-form-id">' + FORMS.map(function (f) {
        return '<option value="' + esc(String(f.value)) + '"' +
          (String(f.value) === String(form.formId) ? ' selected' : '') + '>' + esc(f.label) + '</option>';
      }).join('') + '</select>') +
      field('ครบกำหนดตั้งแต่', true, '<input type="date" class="input" id="rc-from" value="' + esc(form.from) + '" lang="th-TH">') +
      field('ถึงวันที่', true, '<input type="date" class="input" id="rc-to" value="' + esc(form.to) + '" lang="th-TH">') +
      /* เส้นตายการตอบของทั้งรอบ — คนละอย่างกับสองช่องบน ซึ่งเป็นช่วงที่ใช้กรองว่าใครเข้ารอบนี้
         เว้นว่างได้ ระบบจะใช้วันครบกำหนดของใบรายคนแทน */
      field('วันสุดท้ายที่ตอบได้', false,
        '<input type="date" class="input" id="rc-answer-due" value="' + esc(form.answerDue) + '" lang="th-TH" min="' + esc(form.to) + '">',
        'เว้นว่างได้ — ไม่กำหนดจะใช้วันครบกำหนดของแต่ละคน');
  }

  function renderTargets() {
    $('rc-targets').innerHTML = TARGETS.map(function (t) {
      var on = form.targets.indexOf(t.value) > -1;
      return '<button type="button" class="co-chip' + (on ? ' is-on' : '') + '" data-target="' + esc(String(t.value)) + '"' +
        ' role="checkbox" aria-checked="' + on + '">' +
        '<span class="co-chip-mark">' +
          '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>' +
        '</span>' + esc(t.label) + '</button>';
    }).join('');
  }

  /* ---------- ค้นหา / ผลลัพธ์ ---------- */
  function runSearch(page) {
    if (!form.from || !form.to) {
      window.TFC.showToast('กรุณาระบุช่วงวันครบกำหนดก่อนค้นหา', 'warning');
      return;
    }

    var params = new URLSearchParams();
    params.set('from', form.from);
    params.set('to', form.to);
    params.set('page', page || 1);
    params.set('page_size', search.pageSize);
    form.targets.forEach(function (id) { params.append('target_group_ids[]', id); });

    search.loading = true;
    renderResult();

    fetch('{{ route('admin.tracking-rounds.eligible-members') }}?' + params.toString(), {
      headers: { 'Accept': 'application/json' }
    })
      .then(function (res) { return res.json().then(function (b) { return { ok: res.ok, body: b }; }); })
      .then(function (res) {
        search.loading = false;

        if (!res.ok) {
          window.TFC.showToast(firstError(res.body) || 'ค้นหารายชื่อไม่สำเร็จ', 'danger');
          return renderAll();
        }

        search.done = true;
        search.page = res.body.page;
        search.total = res.body.total;
        search.rows = res.body.rows;
        search.allIds = res.body.allIds;
        search.notifiableIds = res.body.notifiableIds;
        renderAll();
      })
      .catch(function () {
        search.loading = false;
        window.TFC.showToast('เชื่อมต่อเซิร์ฟเวอร์ไม่สำเร็จ', 'danger');
        renderAll();
      });
  }

  function firstError(body) {
    if (body && body.errors) {
      var keys = Object.keys(body.errors);
      if (keys.length) return body.errors[keys[0]][0];
    }
    return body && body.message;
  }

  var MEMBER_CLASS = {
    'ตอบแล้ว': 'is-done', 'รอติดตาม': 'is-due',
    'เกินกำหนด': 'is-over', 'ยังไม่ถึงกำหนด': 'is-idle'
  };

  function renderResult() {
    if (!search.done && !search.loading) {
      $('rc-result').innerHTML =
        '<div class="fb-guide">' +
          '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="16.2" y1="16.2" x2="21" y2="21"/></svg>' +
          '<span class="fb-guide-title">เลือกกลุ่มเป้าหมายและช่วงวันครบกำหนด แล้วกด “ค้นหารายชื่อ”</span>' +
          '<span class="fb-guide-text">ระบบจะดึงเฉพาะคนที่ครบกำหนดติดตามในช่วงที่ระบุ และยังไม่ได้ตอบ</span>' +
        '</div>';
      return;
    }

    /* กำลังโหลดแล้วเคยมีผลลัพธ์อยู่ก่อน — คงตารางเดิมไว้ทั้งอัน ไม่ถอดออกแล้วค่อยใส่กลับ
       การ return spinner แทนตารางคือสาเหตุหลักของอาการจอกระพริบ */
    if (search.loading && $('rc-result').querySelector('.fb-table-card')) return;

    /* เลือกทั้งหมด = ทุกคนในผลค้นหา ไม่ใช่แค่หน้านี้ และไม่ใช่เฉพาะคนที่มี LINE
       คนที่ยังไม่ผูก LINE ก็เป็นสมาชิกของรอบได้ ระบบแค่ส่งแจ้งเตือนให้ไม่ได้
       ถ้าตัดเขาออกตั้งแต่ตอนเลือก ตัวเลข "ต้องติดตามเอง" ในหน้ารายละเอียดจะเป็นศูนย์เสมอ */
    var allPicked = search.allIds.length > 0 &&
      search.allIds.every(function (id) { return picked[id]; });

    var start = (search.page - 1) * search.pageSize;

    var rows = search.rows.map(function (p, i) {
      var on = !!picked[p.id];
      return '<div class="fb-tr">' +
        '<div class="fb-check-cell">' +
          '<input type="checkbox" data-pick="' + p.id + '"' + (on ? ' checked' : '') +
            ' aria-label="เลือก ' + esc(p.name) + '">' +
        '</div>' +
        '<div class="fb-cell fb-nums fb-no">' + (start + i + 1) + '</div>' +
        '<div class="fb-name-cell">' +
          '<span class="fb-name">' + esc(p.name) + '</span>' +
          '<span class="fb-pid">' + esc(p.cohortCode || p.pid) + '</span>' +
        '</div>' +
        '<div class="fb-cell fb-nums">' + esc(p.phone) + '</div>' +
        '<div class="fb-cell">' + esc(p.target) + '</div>' +
        /* ชื่อรอบมาจากใบติดตามของคนนั้น ไม่ได้ map จากจำนวนเดือนที่หน้าจอ */
        '<div class="fb-cell">' + esc(p.round) + '</div>' +
        '<div class="fb-cell fb-nums">' + esc(fmt(p.due)) + '</div>' +
        '<div><span class="cd-badge ' + MEMBER_CLASS[p.state] + '">' + esc(p.state) + '</span></div>' +
        '<div>' + (p.line
          ? '<span class="cd-badge is-good">LINE</span>'
          : '<span class="cd-badge is-warn">ยังไม่ผูก LINE</span>') + '</div>' +
        '</div>';
    }).join('');

    var pageCount = Math.max(1, Math.ceil(search.total / search.pageSize));

    $('rc-result').innerHTML =
      '<div class="fb-result-bar">' +
        '<span class="fb-result-count">เลือกแล้ว ' + pickedCount() + ' จาก ' + search.total + ' คน</span>' +
        '<div class="fb-result-actions">' +
          '<button type="button" class="cd-mini-btn" id="rc-toggle-all">' +
            (allPicked ? 'ยกเลิกทั้งหมด' : 'เลือกทั้งหมด') + '</button>' +
          '<button type="button" class="cd-mini-btn" id="rc-export">ส่งออก Excel</button>' +
        '</div>' +
      '</div>' +

      '<div class="card fb-table-card">' +
        '<div class="fb-table-scroll"><div class="fb-table fb-pick-table">' +
          '<div class="fb-tr fb-th">' +
            '<div></div><div>#</div><div>ชื่อ / รหัส</div><div>เบอร์โทร</div><div>กลุ่มเป้าหมาย</div>' +
            '<div>รอบที่ติดตาม</div><div>ครบกำหนด</div><div>สถานะติดตาม</div><div>ช่องทางแจ้งเตือน</div>' +
          '</div>' +
          (rows || '<div class="fb-empty"><span class="fb-empty-title">ไม่พบคนที่ครบกำหนดในช่วงที่ระบุ</span></div>') +
        '</div></div>' +

        '<div class="fb-pager">' +
          '<span class="fb-pager-text">แสดง ' + (search.total ? start + 1 : 0) + '–' +
            (start + search.rows.length) + ' จาก ' + search.total + ' คน</span>' +
          '<div class="fb-pager-controls">' +
            '<select class="select fb-pager-size" id="rc-page-size">' + PAGE_SIZES.map(function (s) {
              return '<option value="' + s + '"' + (search.pageSize === s ? ' selected' : '') + '>' + s + ' รายการ</option>';
            }).join('') + '</select>' +
            '<button type="button" class="fb-pager-btn" data-page="' + (search.page - 1) + '"' +
              (search.page <= 1 ? ' disabled' : '') + ' aria-label="หน้าก่อนหน้า">' +
              '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 6l-6 6 6 6"/></svg></button>' +
            Array.from({ length: pageCount }, function (_, i) {
              return '<button type="button" class="fb-pager-num' + (search.page === i + 1 ? ' is-on' : '') +
                '" data-page="' + (i + 1) + '">' + (i + 1) + '</button>';
            }).join('') +
            '<button type="button" class="fb-pager-btn" data-page="' + (search.page + 1) + '"' +
              (search.page >= pageCount ? ' disabled' : '') + ' aria-label="หน้าถัดไป">' +
              '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 6l6 6-6 6"/></svg></button>' +
          '</div>' +
        '</div>' +
      '</div>';
  }

  /* ---------- ข้อความแจ้งเตือน ---------- */
  function sampleMember() {
    var p = search.rows[0] || { name: 'สมชาย ใจดี', round: 'ติดตาม 3 เดือน', due: TODAY };

    /* วันสุดท้ายที่ตอบได้ของรอบมาก่อนวันครบกำหนดของใบรายคน — กติกาเดียวกับ RoundBatch::answerDueFor()
       แก้ฝั่งใดฝั่งหนึ่งแล้วพรีวิวจะไม่ตรงกับข้อความที่ส่งจริง */
    return { name: p.name, round: p.round, due: form.answerDue || p.due };
  }

  /* แทนค่าชุดเดียวกับฝั่งเซิร์ฟเวอร์ (TrackingRoundService::fillTemplate)
     เพิ่มตัวแปรใหม่ต้องแก้ทั้งสองที่ ไม่งั้นพรีวิวกับข้อความที่ส่งจริงจะไม่ตรงกัน */
  function fillMsg(tpl, p) {
    return tpl
      .replace(/\{ชื่อ\}/g, p.name)
      .replace(/\{รอบ\}/g, p.round)
      .replace(/\{วันครบกำหนด\}/g, fmt(p.due))
      .replace(/\{ลิงก์\}/g, @json(rtrim((string) config('app.url'), '/').'/health'));
  }

  function renderMsg() {
    if ($('rc-msg').value !== form.msg) $('rc-msg').value = form.msg;
    var p = sampleMember();
    $('rc-bubble').textContent = fillMsg(form.msg, p);
    /* รอบกับวันครบกำหนดบนการ์ดมาจากข้อมูลรายคน ไม่ใช่จากข้อความ — พรีวิวใช้คนแรกในผลค้นหา */
    $('rc-bubble-round').textContent = 'รอบติดตาม ' + p.round;
    $('rc-bubble-due').textContent = 'ตอบได้ถึงวันที่ ' + fmt(p.due);
  }

  /* ---------- แถบล่าง ---------- */
  function renderBottom() {
    var ids = pickedIds();
    var n = ids.filter(function (id) { return search.notifiableIds.indexOf(id) > -1; }).length;
    var unreachable = ids.length - n;

    $('rc-summary').textContent = !search.done
      ? 'ยังไม่ได้ค้นหารายชื่อ'
      : 'จะส่งแจ้งเตือน ' + n + ' คน' + (unreachable > 0 ? ' · อีก ' + unreachable + ' คนต้องติดตามเอง' : '');

    /* ต้องมีชื่อรอบ + ค้นหาแล้ว + มีคนถูกเลือก จึงจะกดสร้างได้ */
    var ready = !!(form.name.trim() && form.formId && search.done && ids.length > 0 && !saving);
    $('rc-submit').disabled = !ready;
    $('rc-draft').disabled = !ready;
    $('rc-submit').textContent = saving ? 'กำลังบันทึก…' : 'สร้างรอบและส่งแจ้งเตือน';
  }

  function renderAll() {
    renderResult();
    renderMsg();
    renderBottom();
  }

  /* ---------- บันทึก ---------- */
  function save(notify) {
    if (saving) return;
    saving = true;
    renderBottom();

    fetch('{{ route('admin.tracking-rounds.store') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({
        name: form.name.trim(),
        form_id: form.formId,
        due_from: form.from,
        due_to: form.to,
        /* ส่ง null เมื่อไม่ได้กำหนด — ส่งสตริงว่างไป validation จะตีเป็นค่าที่กรอกผิดรูปแบบ */
        answer_due_date: form.answerDue || null,
        target_group_ids: form.targets,
        follow_up_round_ids: pickedIds(),
        notification_template: form.msg,
        notify: notify
      })
    })
      .then(function (res) { return res.json().then(function (b) { return { ok: res.ok, body: b }; }); })
      .then(function (res) {
        saving = false;

        if (!res.ok || !res.body.success) {
          renderBottom();
          window.TFC.showToast(firstError(res.body) || 'บันทึกไม่สำเร็จ', 'danger');
          return;
        }

        window.TFC.showToast(res.body.message, 'success');
        setTimeout(function () { location.href = res.body.redirect; }, 600);
      })
      .catch(function () {
        saving = false;
        renderBottom();
        window.TFC.showToast('เชื่อมต่อเซิร์ฟเวอร์ไม่สำเร็จ', 'danger');
      });
  }

  /* ---------- เหตุการณ์ ---------- */
  document.addEventListener('click', function (e) {
    var t = e.target;

    var tg = t.closest('[data-target]');
    if (tg) {
      var v = Number(tg.getAttribute('data-target'));
      var i = form.targets.indexOf(v);
      if (i > -1) form.targets.splice(i, 1); else form.targets.push(v);
      return renderTargets();
    }

    if (t.closest('#rc-search')) return runSearch(1);

    if (t.closest('#rc-toggle-all')) {
      var allPicked = search.allIds.length > 0 &&
        search.allIds.every(function (id) { return picked[id]; });
      search.allIds.forEach(function (id) { picked[id] = !allPicked; });
      return renderAll();
    }

    if (t.closest('#rc-export')) {
      /* ไฟล์ที่หลุดออกนอกระบบอ้างคนด้วยรหัสกลุ่มตัวอย่างเท่านั้น ไม่มีชื่อ-เบอร์
         งานโทรตามให้เปิดดูในหน้าจอซึ่งจำกัดสิทธิ์อยู่แล้ว ไม่ใช่จากไฟล์ที่ส่งต่อกันได้ */
      var rows = search.rows.map(function (p, i) {
        return [i + 1, p.cohortCode || p.pid, p.target, p.round,
          fmt(p.due), p.state, p.line ? 'LINE' : 'ยังไม่ผูก LINE'];
      });
      window.TFC.exportCsv('รายชื่อรอบติดตาม.csv',
        ['#', 'รหัสกลุ่มตัวอย่าง', 'กลุ่มเป้าหมาย', 'รอบที่ติดตาม',
         'ครบกำหนด', 'สถานะติดตาม', 'ช่องทางแจ้งเตือน'], rows);
      return;
    }

    var pg = t.closest('[data-page]');
    if (pg) {
      if (pg.disabled) return;
      var p = Number(pg.getAttribute('data-page'));
      if (p < 1) return;
      return runSearch(p);
    }

    /* ปุ่มแทรกตัวแปร — แทรกตรงตำแหน่งเคอร์เซอร์ในกล่องข้อความ แล้วอัปเดตพรีวิวทันที */
    var insertBtn = t.closest('[data-insert]');
    if (insertBtn) {
      var ta = $('rc-msg');
      var token = insertBtn.getAttribute('data-insert');
      var start = ta.selectionStart == null ? ta.value.length : ta.selectionStart;
      var end = ta.selectionEnd == null ? ta.value.length : ta.selectionEnd;
      ta.value = ta.value.slice(0, start) + token + ta.value.slice(end);
      form.msg = ta.value;
      ta.focus();
      ta.setSelectionRange(start + token.length, start + token.length);
      renderMsg();
      return;
    }

    if (t.closest('#rc-msg-reset')) { form.msg = DEFAULT_MSG; return renderMsg(); }
    if (t.closest('#rc-draft')) { if (!$('rc-draft').disabled) save(false); return; }
    if (t.closest('#rc-submit')) { if (!$('rc-submit').disabled) save(true); return; }
  });

  document.addEventListener('change', function (e) {
    var pick = e.target.closest('[data-pick]');
    if (pick) {
      picked[pick.getAttribute('data-pick')] = pick.checked;
      return renderAll();
    }
    if (e.target.id === 'rc-page-size') {
      search.pageSize = Number(e.target.value);
      return runSearch(1);
    }
    if (e.target.id === 'rc-form-id') form.formId = e.target.value;
  });

  document.addEventListener('input', function (e) {
    var id = e.target.id;
    if (id === 'rc-name') { form.name = e.target.value; return renderBottom(); }
    if (id === 'rc-from') { form.from = e.target.value; return; }
    if (id === 'rc-to') { form.to = e.target.value; $('rc-answer-due').min = form.to; return; }
    if (id === 'rc-answer-due') { form.answerDue = e.target.value; renderMsg(); return; }
    if (id === 'rc-msg') { form.msg = e.target.value; renderMsg(); }
  });

  /* ---------- เริ่มต้น ---------- */
  renderForm();
  renderTargets();
  renderAll();
})();
</script>
@endpush
