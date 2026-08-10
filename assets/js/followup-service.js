/* TheFarmConcept — Follow-up Rounds Service
   ทุกการอ่านและเขียน "ตั้งค่ารอบติดตาม" ของหน้า admin/evaluations/rounds.html ผ่านไฟล์นี้ที่เดียว
   เมื่อต่อ backend จริงให้แก้เฉพาะฟังก์ชันในชั้นขนส่งข้อมูลด้านล่าง หน้าจอไม่ต้องแก้อะไรเลย

   ทำไมต้องมีชั้นนี้
   - การตั้งค่าชุดนี้ใช้ตัดสินใจ "ส่งแจ้งเตือนให้ใคร เมื่อไหร่" ซึ่งงานจริงรันที่ฝั่งเซิร์ฟเวอร์
     หน้าจอเป็นแค่ตัวแก้ค่า ไม่ใช่เจ้าของข้อมูล
   - วันเวลาที่คำนวณต้องอิงวันที่ของเซิร์ฟเวอร์ ไม่ใช่นาฬิกาเครื่องผู้ใช้
     เพราะสถานะรอบ (ยังไม่ถึง / เปิดอยู่ / ปิดแล้ว) เพี้ยนทันทีถ้าเครื่องตั้งวันผิด

   การใช้งาน
     var svc = TFC.followUpService;
     svc.load().then(function (settings) { ... });
     svc.save(settings).then(function (saved) { ... });
     svc.defaults();                       // ค่าตั้งต้น ใช้กับปุ่ม "คืนค่าเริ่มต้น"
     svc.serverToday();                    // วันที่ของเซิร์ฟเวอร์ (YYYY-MM-DD)
*/
window.TFC = window.TFC || {};

window.TFC.followUpService = (function () {
  /* ตั้งค่าปลายทาง backend ได้จากหน้าเว็บโดยไม่ต้องแก้ไฟล์นี้ */
  var cfg = window.TFC_CONFIG || {};
  var API_BASE = cfg.followUpApiBase || '';
  var LATENCY = 260;

  /* วันที่อ้างอิงของระบบตัวอย่าง — ชุดข้อมูลใน mock-data.js อยู่ในช่วงปี 2026
     ถ้าใช้ new Date() ของเครื่อง สถานะรอบจะกลายเป็น "ปิดรอบแล้ว" ทั้งหมดทันทีที่เวลาจริงเลยไป
     ต่อ backend จริงแล้วให้ลบค่านี้ทิ้ง แล้วอ่านวันที่จาก response ของเซิร์ฟเวอร์แทน */
  var SERVER_TODAY = '2026-08-10';

  function mock() { return window.TFC_MOCK || {}; }

  function serverToday() {
    return SERVER_TODAY;
  }

  /* แบบประเมินที่เลือกได้ — ดึงจากชุดข้อมูลกลาง ไม่ได้พิมพ์รายชื่อซ้ำไว้ที่นี่
     เพิ่ม/ลบแบบประเมินในหน้า "แบบประเมิน" แล้วตัวเลือกที่นี่ตามทันที */
  function formOptions() {
    return (mock().evaluationForms || []).map(function (f) {
      return { id: f.id, name: f.name };
    });
  }

  function defaults() {
    var forms = formOptions();
    /* เลือกแบบติดตามผลเป็นค่าตั้งต้นถ้ามี เพราะหน้านี้คือรอบติดตาม ไม่ใช่แบบประเมินหลังกิจกรรม */
    var followUpForm = (forms.filter(function (f) { return f.name.indexOf('ติดตาม') > -1; })[0] || forms[0] || {}).id || '';

    return {
      rounds: [
        { id: 'FR-1', name: 'ค่าตั้งต้น (Baseline)', from: '2026-05-08', to: '2026-05-22', formId: followUpForm, enabled: true },
        { id: 'FR-2', name: 'ติดตาม 3 เดือน',        from: '2026-08-06', to: '2026-08-20', formId: followUpForm, enabled: true },
        { id: 'FR-3', name: 'ติดตาม 6 เดือน',        from: '2026-11-05', to: '2026-11-19', formId: followUpForm, enabled: true },
        { id: 'FR-4', name: 'ติดตาม 12 เดือน',       from: '2027-05-07', to: '2027-05-28', formId: followUpForm, enabled: true }
      ],
      line: {
        enabled: true,
        leadDays: 3,
        sendTime: '09:00',
        message: 'สวัสดีคุณ {ชื่อ}\nถึงเวลาทำแบบประเมิน{รอบ}แล้ว\nกรุณาตอบภายในวันที่ {วันครบกำหนด}'
      }
    };
  }

  /* ---------------------------------------------------------------
     ชั้นขนส่งข้อมูล — จุดเดียวที่ต้องแก้เมื่อมี backend จริง
     --------------------------------------------------------------- */
  function request(path, options) {
    return fetch(API_BASE + path, Object.assign({
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin'
    }, options || {})).then(function (res) {
      if (!res.ok) throw new Error('follow-up api ' + res.status + ' ' + path);
      return res.json();
    });
  }

  function local(value) {
    return new Promise(function (resolve) {
      setTimeout(function () { resolve(JSON.parse(JSON.stringify(value))); }, LATENCY);
    });
  }

  /* ที่เก็บของ "เซิร์ฟเวอร์จำลอง" — อยู่ในหน่วยความจำ รีเฟรชแล้วกลับเป็นค่าตั้งต้น
     ตรงนี้คือจุดที่ backend จริงจะเข้ามาแทน */
  var store = null;

  /* GET /follow-up/settings */
  function load() {
    if (API_BASE) return request('/follow-up/settings');
    if (!store) store = defaults();
    return local(store);
  }

  /* PUT /follow-up/settings */
  function save(settings) {
    if (API_BASE) {
      return request('/follow-up/settings', { method: 'PUT', body: JSON.stringify(settings) });
    }
    store = JSON.parse(JSON.stringify(settings));
    return local(store);
  }

  /* ---------------------------------------------------------------
     ตรรกะวันที่ — รวมไว้ที่นี่เพราะหน้าจอกับเซิร์ฟเวอร์ต้องคิดเหมือนกัน
     ถ้าปล่อยให้หน้าจอคำนวณเอง ตัวอย่างที่แสดงจะไม่ตรงกับที่ระบบส่งจริง
     --------------------------------------------------------------- */

  /* บวก/ลบวันจากวันที่ ISO โดยไม่ผ่าน timezone ของเครื่อง
     new Date('2026-08-20') ตีความเป็น UTC แล้ว toISOString กลับมาอาจเลื่อนไปหนึ่งวัน
     ในเขตเวลาไทย (UTC+7) จึงคำนวณด้วยเลขวันตรง ๆ แทน */
  function shiftDays(iso, days) {
    if (!iso) return '';
    var p = iso.split('-');
    if (p.length !== 3) return '';
    var d = new Date(Date.UTC(Number(p[0]), Number(p[1]) - 1, Number(p[2])));
    d.setUTCDate(d.getUTCDate() + days);
    return d.toISOString().slice(0, 10);
  }

  function daysBetween(from, to) {
    if (!from || !to) return 0;
    var a = new Date(from + 'T00:00:00Z').getTime();
    var b = new Date(to + 'T00:00:00Z').getTime();
    return Math.round((b - a) / 86400000);
  }

  /* สถานะของรอบเทียบกับวันที่ของเซิร์ฟเวอร์ */
  function roundStatus(round) {
    if (!round.enabled) return 'ปิดใช้งาน';
    if (!round.from || !round.to) return 'ยังไม่กำหนดวันที่';
    var today = serverToday();
    if (round.to < round.from) return 'ช่วงวันที่ไม่ถูกต้อง';
    if (today < round.from) return 'ยังไม่ถึงรอบ';
    if (today > round.to) return 'ปิดรอบแล้ว';
    return 'เปิดอยู่';
  }

  /* วันเวลาที่จะส่งแจ้งเตือนของรอบนั้น — ก่อนวันปิดรับตามจำนวนวันที่ตั้งไว้ */
  function notifyDate(round, leadDays) {
    if (!round.to) return '';
    return shiftDays(round.to, -Math.max(0, leadDays || 0));
  }

  return {
    load: load,
    save: save,
    defaults: defaults,
    formOptions: formOptions,
    serverToday: serverToday,
    shiftDays: shiftDays,
    daysBetween: daysBetween,
    roundStatus: roundStatus,
    notifyDate: notifyDate
  };
})();
