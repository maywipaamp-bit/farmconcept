/* TheFarmConcept — Follow-up Round Templates Service
   master data ของ "รอบติดตาม" แบบนับจากวันที่เข้ากลุ่มตัวอย่าง (offset เป็นจำนวนวัน)
   ตาราง: follow_up_round_templates (name, offset_days, is_active, sort_order)

   ไฟล์นี้คุม "ระยะห่างเป็นวัน" ที่ใช้คำนวณวันครบกำหนดรายคน
   ส่วนการ "เปิดรอบติดตามจริง" (เลือกช่วงวันครบกำหนดแล้วดึงคนที่ถึงกำหนด) อยู่ที่
   assets/js/round-data.js + admin/evaluations/rounds.html ซึ่งอ่านรอบรายคนผ่าน TFC.cohort อีกที

   ทำไมต้องมีชั้นนี้
   - ระบบกลุ่มตัวอย่างต้องอ่านรอบจากที่นี่ที่เดียว ห้ามเขียน 3/6/12 เดือนไว้ในโค้ด
     ไม่งั้นแก้ค่าในหน้าตั้งค่าแล้วระบบยังสร้างรอบตามเลขเดิมที่ฝังไว้
   - ตรรกะวันที่ต้องอยู่ชุดเดียวกับที่เซิร์ฟเวอร์ใช้ ตัวอย่างบนจอจะได้ตรงกับของจริง

   การใช้งาน
     var svc = TFC.followUpTemplateService;
     svc.load().then(function (templates) { ... });
     svc.save(templates).then(function (saved) { ... });
     svc.defaults();                          // ค่าตั้งต้น ใช้กับปุ่ม "คืนค่าเริ่มต้น"
     svc.materialize('2026-08-10');           // รอบของคนหนึ่งคน พร้อม offset ที่ snapshot แล้ว
*/
window.TFC = window.TFC || {};

window.TFC.followUpTemplateService = (function () {
  /* ตั้งค่าปลายทาง backend ได้จากหน้าเว็บโดยไม่ต้องแก้ไฟล์นี้ */
  var cfg = window.TFC_CONFIG || {};
  var API_BASE = cfg.followUpTemplateApiBase || '';
  var LATENCY = 260;

  /* วันที่อ้างอิงของระบบตัวอย่าง — ต้องตรงกับ TODAY ใน assets/js/cohort-data.js
     ชุดข้อมูลตัวอย่างอยู่ในช่วงปี 2026 ถ้าใช้นาฬิกาเครื่องผู้ใช้ สถานะรอบจะเพี้ยนทั้งหมด */
  var SERVER_TODAY = cfg.followUpToday || '2026-08-10';

  /* ตัวหารสำหรับแปลงจำนวนวันเป็น "≈ กี่เดือน / กี่ปี"
     ประกาศเป็นค่าคงที่เพราะถูกใช้ทั้งตอนแสดงผลและตอนตรวจค่า — ห้ามพิมพ์เลขซ้ำในสองที่ */
  var DAYS_PER_MONTH = 30;
  var DAYS_PER_YEAR = 365;

  function serverToday() { return SERVER_TODAY; }

  /* ค่าตั้งต้นของระบบ — ก่อนเข้าร่วม / 3 / 6 / 12 เดือน
     นี่คือ "ที่เดียว" ที่เลขชุดนี้ถูกเขียนไว้ ส่วนอื่นของระบบต้องอ่านผ่าน load() เท่านั้น */
  function defaults() {
    return [
      { id: 'FRT-1', name: 'ก่อนเข้าร่วม', offsetDays: 0,   isActive: true, sortOrder: 1 },
      { id: 'FRT-2', name: '3 เดือน',      offsetDays: 90,  isActive: true, sortOrder: 2 },
      { id: 'FRT-3', name: '6 เดือน',      offsetDays: 180, isActive: true, sortOrder: 3 },
      { id: 'FRT-4', name: '12 เดือน',     offsetDays: 365, isActive: true, sortOrder: 4 }
    ];
  }

  /* ---------------------------------------------------------------
     จำนวน record ที่ถูกสร้างจากแต่ละรอบ
     ใช้ตอบคำถามเดียว: ลบรอบนี้ได้ไหม — ลบได้เฉพาะรอบที่ยังไม่เคยสร้าง record
     ตัวเลขจริงมาจากเซิร์ฟเวอร์ (COUNT ของ follow_up_rounds ที่อ้าง template นี้)
     และ **เซิร์ฟเวอร์ต้องตรวจซ้ำตอน DELETE เสมอ** — ปุ่มที่ปิดไว้บนจอกันได้แค่การกดพลาด
     --------------------------------------------------------------- */
  var USAGE = cfg.followUpUsage || { 'FRT-1': 128, 'FRT-2': 96, 'FRT-3': 41, 'FRT-4': 0 };

  function usageCount(id) { return USAGE[id] || 0; }

  /* รอบที่เพิ่งเพิ่มบนจอยังไม่มี id จากเซิร์ฟเวอร์ จึงยังไม่มีทางมี record — ลบได้เสมอ */
  function canDelete(tpl) { return usageCount(tpl && tpl.id) === 0; }

  /* ---------------------------------------------------------------
     ตรรกะวันที่ — ต้องคิดเหมือนกับที่เซิร์ฟเวอร์คิด
     --------------------------------------------------------------- */

  /* บวกวันจากวันที่ ISO โดยไม่ผ่าน timezone ของเครื่อง
     new Date('2026-08-10') ถูกตีความเป็น UTC แล้ว toISOString กลับมาอาจเลื่อนไปหนึ่งวัน
     ในเขตเวลาไทย (UTC+7) จึงคำนวณด้วยเลขวันตรง ๆ */
  function shiftDays(iso, days) {
    if (!iso) return '';
    var p = String(iso).split('-');
    if (p.length !== 3) return '';
    var d = new Date(Date.UTC(Number(p[0]), Number(p[1]) - 1, Number(p[2])));
    if (isNaN(d.getTime())) return '';
    d.setUTCDate(d.getUTCDate() + (Number(days) || 0));
    return d.toISOString().slice(0, 10);
  }

  /* วันครบกำหนดของรอบหนึ่ง = วันที่เข้ากลุ่มตัวอย่าง + offset */
  function dueDate(entryIso, offsetDays) {
    return shiftDays(entryIso, offsetDays);
  }

  /* คำอธิบายใต้วันที่ — คำนวณจากจำนวนวันเสมอ ห้ามผูกกับชื่อรอบ
     เพราะผู้ใช้ตั้งชื่อรอบเป็นอะไรก็ได้ ชื่อไม่ใช่แหล่งความจริงของระยะเวลา
     ต่ำกว่าหนึ่งเดือนบอกเป็นวันตรง ๆ — ปัดเป็น "≈ 0 เดือน" แล้วไม่มีความหมาย */
  function approxLabel(offsetDays) {
    var n = Number(offsetDays);
    if (!isFinite(n) || n < 0) return '';
    if (n === 0) return 'วันเดียวกับที่เข้ากลุ่ม';
    if (n % DAYS_PER_YEAR === 0) return '≈ ' + (n / DAYS_PER_YEAR) + ' ปี';
    if (n % DAYS_PER_MONTH === 0) return '≈ ' + (n / DAYS_PER_MONTH) + ' เดือน';
    if (n < DAYS_PER_MONTH) return 'หลังเข้ากลุ่ม ' + n + ' วัน';
    return '≈ ' + Math.round(n / DAYS_PER_MONTH) + ' เดือน';
  }

  /* ---------------------------------------------------------------
     จุดเชื่อมกับระบบกลุ่มตัวอย่าง
     --------------------------------------------------------------- */

  /* รอบที่ยังเปิดใช้งาน เรียงตาม sort_order — ระบบกลุ่มตัวอย่างเรียกตัวนี้
     รอบที่ปิดใช้งานจะไม่ถูกสร้างให้คนใหม่ แต่ของคนเก่าที่สร้างไปแล้วยังอยู่ครบ
     เพราะรอบของแต่ละคนเป็น record ของตัวเอง ไม่ได้อ่าน template สดทุกครั้ง */
  function activeTemplates(templates) {
    return (templates || []).filter(function (t) { return t.isActive; })
      .slice()
      .sort(function (a, b) { return (a.sortOrder || 0) - (b.sortOrder || 0); });
  }

  /* สร้างรอบของ "คนหนึ่งคน" จากวันที่เข้ากลุ่มตัวอย่าง
     คืน offsetDays ติดไปด้วยเพื่อให้ผู้เรียก snapshot ลง record ของคนนั้น —
     ถ้าเก็บแค่ templateId ไว้แล้วภายหลังมีคนแก้ offset ของ template
     วันครบกำหนดของคนที่สร้างไปแล้วจะขยับตามทั้งกระดาน ซึ่งผิด */
  function materialize(entryIso, templates) {
    return activeTemplates(templates).map(function (t) {
      return {
        templateId: t.id,
        name: t.name,
        offsetDays: t.offsetDays,   /* snapshot — ห้ามอ่านสดจาก template ตอนแสดงผลภายหลัง */
        dueDate: dueDate(entryIso, t.offsetDays)
      };
    });
  }

  /* ---------------------------------------------------------------
     ตรวจความถูกต้องก่อนบันทึก — หน้าจอกับเซิร์ฟเวอร์ใช้กติกาชุดเดียวกัน
     --------------------------------------------------------------- */
  function isWholeNonNegative(v) {
    if (v === '' || v === null || v === undefined) return false;
    var n = Number(v);
    return isFinite(n) && n >= 0 && Math.floor(n) === n;
  }

  function validate(templates) {
    var list = templates || [];
    var blank = list.filter(function (t) {
      return !String(t.name || '').trim() || !isWholeNonNegative(t.offsetDays);
    }).length;

    /* จำนวนวันซ้ำกันไม่ได้ ไม่งั้นคนหนึ่งคนจะได้รอบครบกำหนดวันเดียวกันสองรอบ
       ตรวจทุกแถวรวมที่ปิดใช้งานด้วย เพราะแถวที่ปิดไว้อาจถูกเปิดกลับมาเมื่อไหร่ก็ได้ */
    var seen = {}, dup = [];
    list.forEach(function (t) {
      if (!isWholeNonNegative(t.offsetDays)) return;
      var key = String(Number(t.offsetDays));
      if (seen[key]) { if (dup.indexOf(key) < 0) dup.push(key); }
      seen[key] = true;
    });

    var activeCount = list.filter(function (t) { return t.isActive; }).length;

    return {
      blankCount: blank,
      duplicateDays: dup.map(Number).sort(function (a, b) { return a - b; }),
      activeCount: activeCount,
      ok: blank === 0 && dup.length === 0 && activeCount > 0
    };
  }

  /* ---------------------------------------------------------------
     ชั้นขนส่งข้อมูล — จุดเดียวที่ต้องแก้เมื่อมี backend จริง
     --------------------------------------------------------------- */
  function request(path, options) {
    var tag = document.querySelector('meta[name="csrf-token"]');

    options = Object.assign({}, options || {});

    var headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': tag ? tag.getAttribute('content') : ''
    };

    /* IIS บนเซิร์ฟเวอร์ปลายทางดัก PUT / PATCH / DELETE ไว้ตั้งแต่ก่อนถึง PHP (WebDAVModule)
       ส่งเป็น POST แล้วบอกเมธอดจริงผ่านหัวข้อนี้ Laravel อ่านให้เองตั้งแต่ชั้น Request

       ใส่ _method ไว้ในตัว JSON ใช้ไม่ได้ — Laravel อ่าน _method จากฟอร์มหรือ query เท่านั้น
       ไม่ได้อ่านจาก body ที่เป็น JSON */
    var method = (options.method || 'GET').toUpperCase();
    if (method === 'PUT' || method === 'PATCH' || method === 'DELETE') {
      headers['X-HTTP-Method-Override'] = method;
      options.method = 'POST';
      if (!options.body) options.body = '{}';
    }

    options.headers = Object.assign(headers, options.headers || {});
    options.credentials = options.credentials || 'same-origin';

    return fetch(API_BASE + path, options).then(function (res) {
      return res.json().catch(function () { return {}; }).then(function (data) {
        if (res.ok) return data;

        /* ข้อความจากเซิร์ฟเวอร์เป็นภาษาไทยและบอกเหตุผลจริง เช่น ลบรอบที่มีคนใช้อยู่ไม่ได้
           โยนต่อไปให้หน้าจอแสดง ดีกว่าโยนรหัสสถานะที่ผู้ใช้อ่านไม่รู้เรื่อง */
        var msg = data.errors
          ? Object.keys(data.errors).map(function (k) { return data.errors[k][0]; }).join(' · ')
          : (data.message || 'ทำรายการไม่สำเร็จ');

        throw new Error(msg);
      });
    });
  }

  function local(value) {
    return new Promise(function (resolve) {
      setTimeout(function () { resolve(JSON.parse(JSON.stringify(value))); }, LATENCY);
    });
  }

  /* ที่เก็บของ "เซิร์ฟเวอร์จำลอง" — อยู่ในหน่วยความจำ รีเฟรชแล้วกลับเป็นค่าตั้งต้น */
  var store = null;

  /* GET /follow-up/round-templates */
  function load() {
    if (!API_BASE) return local(cached());

    return request('').then(function (data) {
      /* จำค่าที่ได้จากเซิร์ฟเวอร์ไว้ ผู้เรียกแบบ synchronous (ระบบกลุ่มตัวอย่าง)
         จะได้ค่าจริง ไม่ใช่ค่าตั้งต้นที่เขียนไว้ในไฟล์นี้ */
      store = data.rows || [];
      if (data.today) SERVER_TODAY = data.today;
      if (data.usage) USAGE = data.usage;
      return JSON.parse(JSON.stringify(store));
    });
  }

  /* ค่าปัจจุบันแบบ synchronous — สำหรับผู้เรียกที่ต้องใช้ทันทีตอนสคริปต์ถูกโหลด
     (ระบบกลุ่มตัวอย่างประกอบตารางรอบตั้งแต่ไฟล์โหลด รอ Promise ไม่ได้)
     ต่อ backend จริงแล้วให้เรียก load() ตอนบูตหน้า จากนั้น cached() จะคืนค่าที่ได้จากเซิร์ฟเวอร์ */
  function cached() {
    if (!store) store = defaults();
    return JSON.parse(JSON.stringify(store));
  }

  /* PUT /follow-up/round-templates (ส่งผ่าน POST + method override เพื่อรองรับ IIS)
     sort_order คิดจากลำดับแถวบนจอ ผู้ใช้ไม่ต้องกรอกเลขลำดับเอง */
  function save(templates) {
    var payload = (templates || []).map(function (t, i) {
      /* แถวที่เพิ่งกด "เพิ่มรอบ" ยังไม่มีรหัสจริง หน้าจอตั้ง id ชั่วคราวไว้ให้ตัวเองอ้างอิงเท่านั้น
         ต้องส่ง null ไป ไม่งั้นเซิร์ฟเวอร์จะหารหัสนั้นไม่เจอแล้วตอบ 404 */
      var isNew = /^FRT-NEW-/.test(String(t.id || ''));

      return {
        id: isNew ? null : t.id,
        name: String(t.name || '').trim(),
        offsetDays: Number(t.offsetDays),
        isActive: !!t.isActive,
        sortOrder: i + 1
      };
    });
    if (API_BASE) {
      return request('', {
        method: 'PUT',
        body: JSON.stringify({ rows: payload })
      }).then(function (data) {
        store = data.rows || [];
        if (data.usage) USAGE = data.usage;
        return JSON.parse(JSON.stringify(store));
      });
    }
    store = JSON.parse(JSON.stringify(payload));
    return local(store);
  }

  return {
    load: load,
    save: save,
    cached: cached,
    defaults: defaults,
    serverToday: serverToday,
    shiftDays: shiftDays,
    dueDate: dueDate,
    approxLabel: approxLabel,
    activeTemplates: activeTemplates,
    materialize: materialize,
    validate: validate,
    usageCount: usageCount,
    canDelete: canDelete
  };
})();
