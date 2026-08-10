/* TheFarmConcept — ตอบแบบประเมิน: ชั้นข้อมูลของคำตอบแบบประเมินสุขภาพ
   ใช้โดย assets/js/response-list.js (admin/evaluations/responses.html)

   หน้าจอนี้อ่านอย่างเดียว ไม่มี setter ของคำตอบในไฟล์นี้เลย

   โครงข้อมูลจริงที่จำลองอยู่:
     survey_responses            (person_id, round_id, form_id, submitted_at)
       join cohort_members       -> ชื่อ + รหัสบุคคล
       join follow_up_rounds     -> ชื่อรอบของคนนั้น (snapshot ต่อคน ไม่ใช่ template สด)
     ชื่อรอบสำหรับ "แท็บกรอง" มาจาก follow_up_round_templates ผ่าน followUpTemplateService
     จึงไม่มีเลข 3/6/12 เดือนเขียนไว้ในหน้าจอ

   query() ถูกออกแบบให้หน้าตาเหมือน endpoint จริง คือรับเงื่อนไข + หน้าที่ขอ
   แล้วคืน { total, rows } — หน้าจอไม่เคยถือรายการทั้งหมดไว้เอง
   ตอนต่อ backend ให้แทนไส้ในของ query() ด้วย
     GET /survey-responses?round=&keyword=&page=&pageSize=
   ซึ่งฝั่ง server ทำ WHERE + ORDER BY submitted_at DESC + LIMIT/OFFSET */
(function () {
  var TFC = window.TFC = window.TFC || {};
  var C = TFC.cohort;
  var TPL = TFC.followUpTemplateService;

  var ALL = 'ทั้งหมด';

  /* แบบประเมินที่ใช้กับงานติดตามสุขภาพ — อ่านจากชุดเดียวกับที่หน้ารอบติดตามใช้
     รอบแรก (วันที่เข้ากลุ่ม) เป็นการคัดกรอง ส่วนรอบถัด ๆ ไปเป็นแบบติดตามการเปลี่ยนแปลง */
  function formOf(roundIndex) {
    var forms = (TFC.rounds && TFC.rounds.FORMS) || [];
    return roundIndex === 0 ? (forms[1] || forms[0] || '-') : (forms[0] || '-');
  }

  /* เวลาที่ตอบ — ของจริงคือส่วนเวลาของคอลัมน์ submitted_at
     ต้นแบบยังเก็บแค่วันที่ จึงสร้างเวลาแบบกำหนดผลได้จากรหัสบุคคล+รอบ
     (เปิดหน้าซ้ำกี่ครั้งก็ได้เวลาเดิม ห้ามใช้ Math.random ที่นี่) */
  function timeOf(pid, roundIndex) {
    var seed = Number(String(pid).replace(/\D/g, '')) || 1;
    var t = (Math.imul(seed, 73856093) ^ Math.imul(roundIndex + 1, 19349663)) >>> 0;
    t = Math.imul(t ^ (t >>> 13), 1274126177) >>> 0;
    var minutes = 8 * 60 + (t % (10 * 60));            /* 08:00–17:59 */
    return pad(Math.floor(minutes / 60)) + ':' + pad(minutes % 60);
  }

  function pad(n) { return n < 10 ? '0' + n : String(n); }

  /* ชื่อรอบที่ใช้เป็นแท็บ — มาจาก master data ไม่ใช่ค่าที่เขียนไว้ในหน้าจอ */
  function roundNames() {
    return TPL.activeTemplates(TPL.cached()).map(function (t) { return t.name; });
  }

  /* ทุกแถวของ survey_responses หลัง join แล้ว
     สร้างใหม่ทุกครั้งที่เรียกเพื่อให้สะท้อนข้อมูลกลุ่มตัวอย่างล่าสุดเสมอ */
  function allRows() {
    var out = [];
    C.members().forEach(function (m) {
      C.schedule(m).forEach(function (r, i) {
        if (!r.at) return;                 /* ยังไม่ตอบ = ไม่มีแถวใน survey_responses */
        out.push({
          id: m.id + '-' + r.id,
          personId: m.id,
          pid: m.pid,
          name: m.name,
          round: r.name,
          form: formOf(i),
          at: r.at,                        /* ISO — แปลงเป็นวันที่ไทยตอนแสดงผลเท่านั้น */
          time: timeOf(m.pid, i)
        });
      });
    });
    return out;
  }

  /* เรียงใหม่ → เก่า ด้วย ISO + เวลา จึงเทียบเป็นข้อความได้ตรง ๆ
     รหัสบุคคลเป็นตัวตัดสินสุดท้าย ลำดับจะได้คงที่เมื่อวันเวลาซ้ำกันพอดี */
  function newestFirst(a, b) {
    var d = (b.at + b.time).localeCompare(a.at + a.time);
    return d !== 0 ? d : a.pid.localeCompare(b.pid);
  }

  /* opts = { round, keyword, page, pageSize }
     ไม่ส่ง page/pageSize = ขอทั้งชุดที่ตรงเงื่อนไข (ใช้ตอนส่งออก Excel) */
  function query(opts) {
    opts = opts || {};
    var round = opts.round || ALL;
    var q = String(opts.keyword || '').trim().toLowerCase();

    var matched = allRows().filter(function (row) {
      if (round !== ALL && row.round !== round) return false;
      if (q && (row.name + ' ' + row.pid).toLowerCase().indexOf(q) < 0) return false;
      return true;
    }).sort(newestFirst);

    if (!opts.page || !opts.pageSize) return { total: matched.length, rows: matched };

    /* ของจริงคือ LIMIT/OFFSET ที่ server — ตัดหลังกรองเพื่อจำลองผลลัพธ์ชุดเดียวกัน */
    var start = (opts.page - 1) * opts.pageSize;
    return { total: matched.length, rows: matched.slice(start, start + opts.pageSize) };
  }

  /* "8 ส.ค. 69 · 09:30 น." — วันที่ไทย พ.ศ. ย่อ + เวลา 24 ชม. */
  function fmtDateTime(row) {
    return C.fmt(row.at) + ' · ' + row.time + ' น.';
  }

  TFC.surveyResponses = {
    ALL: ALL,
    roundNames: roundNames,
    query: query,
    fmtDateTime: fmtDateTime
  };
})();
