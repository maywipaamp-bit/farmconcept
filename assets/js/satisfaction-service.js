/* TheFarmConcept — Satisfaction Service
   ทุกการอ่านข้อมูลความพึงพอใจผ่านไฟล์นี้ที่เดียว — ตอนนี้ผู้ใช้คือ
   admin/activities/responses.html (หน้าภาพรวมสถิติ satisfaction.html ถูกถอดออกแล้ว)
   หน้าจอไม่แตะ TFC_MOCK ตรง ๆ เลย เมื่อต่อ backend จริงให้แก้เฉพาะฟังก์ชันใน
   "ชั้นขนส่งข้อมูล" ด้านล่าง โดยที่หน้าจอไม่ต้องแก้อะไรเลย

   กฎห้าข้อที่ไฟล์นี้รับผิดชอบ (มาจากข้อกำหนดของหน้าจอ)
   1. คำตอบรายคนคือแหล่งข้อมูลเดียว — คะแนนเฉลี่ย คะแนนรายหัวข้อ การกระจายดาว
      จำนวนผู้ตอบ และความเห็น derive จากชุดเดียวกันทั้งหมด ไม่มีการเก็บค่าสรุปแยกไว้
      ตัวเลขในการ์ดกับตารางจึงขัดกันเองไม่ได้
   2. อัตราการตอบ = ผู้ตอบ ÷ ผู้เข้าร่วมจริง (checkin) ไม่ใช่ผู้ลงทะเบียน
   3. ทุก API รับ activityId เป็นพารามิเตอร์แรกเสมอ ข้อมูลแต่ละกิจกรรมไม่ปนกัน
   4. ไม่คืนข้อมูลที่ระบุตัวตนผู้ตอบ แม้แต่ในตารางรายคน — ไม่มี user_id ชื่อ หรือเบอร์โทร
      ออกจากฟังก์ชันใดในไฟล์นี้ (ดู toPublicRow)
   5. การแบ่งหน้าทำที่ฝั่งเซิร์ฟเวอร์ — responses() รับ limit/offset และคืนเฉพาะแถวของหน้านั้น
      พร้อม total ห้ามให้หน้าจอดึงทั้งหมดมาตัดเอง

   การใช้งาน
     var svc = TFC.satisfactionService;
     svc.activities().then(function (list) { ... });                 // สำหรับ popover เปลี่ยนกิจกรรม
     svc.summary(activityId).then(function (s) { ... });             // การ์ดสรุป + รายหัวข้อ + การกระจาย
     svc.responses(activityId, { limit, offset }).then(function (p) { p.rows / p.total });
     svc.responseList(activityId, { band, keyword, limit, offset })        // ตารางหน้า "ตอบประเมินหลังกิจกรรม"
        .then(function (p) { p.rows / p.total });                          // band: all | praise | mid | improve
     svc.comments(activityId, { filter }).then(function (list) { ... });   // filter: all | praise | improve
*/
window.TFC = window.TFC || {};

window.TFC.satisfactionService = (function () {
  /* ตั้งปลายทาง backend ได้จากหน้าเว็บผ่าน window.TFC_CONFIG.satisfactionApiBase
     เว้นว่าง = ทำงานบนข้อมูลตัวอย่างในเครื่อง */
  var cfg = window.TFC_CONFIG || {};
  var API_BASE = cfg.satisfactionApiBase || '';
  var LATENCY = 220;

  /* เกณฑ์แปลคะแนนเฉลี่ยเป็นคำ — เรียงจากสูงไปต่ำ ตัวแรกที่ผ่านคือคำตอบ */
  var GRADES = [
    { min: 4.5, label: 'ดีมาก', tone: 'success' },
    { min: 4.0, label: 'ดี', tone: 'success' },
    { min: 3.5, label: 'ปานกลาง', tone: 'warning' },
    { min: 0, label: 'ต้องปรับปรุง', tone: 'danger' }
  ];

  /* อัตราการตอบต่ำกว่านี้ถือว่ากลุ่มตัวอย่างน้อยเกินกว่าจะเป็นตัวแทนได้ */
  var REPRESENTATIVE_MIN = 70;

  /* คะแนนรายหัวข้อต่ำกว่านี้ถือว่าเป็นจุดที่ต้องปรับปรุง (แถบเปลี่ยนเป็นสีเหลือง) */
  var TOPIC_WARN_BELOW = 4.0;

  function mock() { return window.TFC_MOCK || {}; }

  function topics() { return mock().satisfactionTopics || []; }

  function activityOf(activityId) {
    return (mock().activities || []).filter(function (a) { return a.id === activityId; })[0] || null;
  }

  /* คำตอบของกิจกรรมเดียว เรียงจากล่าสุดไปเก่าสุด — ลำดับนี้คือลำดับ "ผู้ตอบ #N" ที่หน้าจอแสดง */
  function rowsOf(activityId) {
    return (mock().satisfactionResponses || [])
      .filter(function (r) { return r.activityId === activityId; })
      .slice()
      .sort(function (a, b) { return a.submittedAt < b.submittedAt ? 1 : -1; });
  }

  /* ผู้เข้าร่วมจริง = คนที่เช็คอินแล้ว ไม่ใช่ยอดลงทะเบียน */
  function checkedInCount(activityId) {
    var regs = (mock().activityRegistrations || {})[activityId] || [];
    return regs.filter(function (r) { return r.checkinStatus === 'เข้าร่วมแล้ว'; }).length;
  }

  function round1(n) { return Math.round(n * 10) / 10; }
  function round2(n) { return Math.round(n * 100) / 100; }

  function mean(values) {
    if (!values.length) return 0;
    var sum = values.reduce(function (a, b) { return a + b; }, 0);
    return sum / values.length;
  }

  /* คะแนนของผู้ตอบหนึ่งคน = ค่าเฉลี่ยของทุกหัวข้อที่เขาให้ */
  function rowAverage(row) { return mean(row.scores || []); }

  function gradeOf(average) {
    for (var i = 0; i < GRADES.length; i++) {
      if (average >= GRADES[i].min) return { label: GRADES[i].label, tone: GRADES[i].tone };
    }
    return { label: GRADES[GRADES.length - 1].label, tone: 'danger' };
  }

  /* แถวที่ปลอดภัยพอจะส่งออกจากเซิร์ฟเวอร์ — เหลือเฉพาะคะแนน เวลา และลำดับที่ใช้แสดงผล
     ไม่มี id ของคำตอบติดไปด้วย เพราะ id เดียวกันข้ามหน้าจอใช้ไล่หาตัวคนได้ */
  function toPublicRow(row, index) {
    return {
      seq: index + 1,
      scores: (row.scores || []).slice(),
      average: round1(rowAverage(row)),
      submittedAt: row.submittedAt
    };
  }

  /* รอบของกิจกรรมที่คำตอบชุดนั้นผูกอยู่
     ของจริงคือ survey_responses.activity_round_id ที่ join กับ activity_rounds
     ต้นแบบยังไม่มีคอลัมน์นั้น จึงจับคู่จากวันที่ส่งคำตอบกับวันที่ของรอบ
     (แบบประเมินหลังกิจกรรมถูกส่งหลังจบรอบที่เข้า จึงใช้รอบล่าสุดที่ไม่เกินวันที่ส่ง)
     เกณฑ์ตั้งชื่อรอบเหมือน checkin-service: มีรอบเดียวไม่ต้องใส่เลขรอบให้รก */
  function roundLabelOf(activityId, submittedAt) {
    var sessions = (mock().activitySessions || {})[activityId] || [];
    if (sessions.length < 2) return 'รอบเดียว';

    var day = String(submittedAt || '').slice(0, 10);
    var index = 0;
    sessions.forEach(function (s, i) { if (s.date <= day) index = i; });
    return 'รอบ ' + (index + 1);
  }

  /* ช่วงคะแนนที่ใช้เป็นแท็บกรอง — ตัดสินจากคะแนนรวมที่ปัดเป็นจำนวนเต็มแล้ว
     ต้องเป็นชุดเดียวกับที่หน้าจอใช้เลือกสี pill ไม่งั้นแท็บกับสีจะไม่ตรงกัน */
  function bandOf(score) {
    if (score >= 4) return 'praise';
    if (score === 3) return 'mid';
    return 'improve';
  }

  /* GET /activities/:id/satisfaction/response-list?band=&keyword=&limit=&offset=
     ตารางรายคำตอบของหน้า "ตอบประเมินหลังกิจกรรม"

     ข้อบังคับที่ห้ามหย่อน: ไม่มี user_id ชื่อ หรือเบอร์โทรออกจากฟังก์ชันนี้
     สิ่งเดียวที่ระบุ "คนตอบ" ได้คือ seq ซึ่งเป็นลำดับภายในกิจกรรมนั้นเท่านั้น
     และ seq ถูกกำหนดจากรายการเต็มก่อนกรอง ตัวเลขจึงไม่ขยับตามตัวกรองหรือหน้าที่เปิดอยู่

     ไม่ส่ง limit = ขอทั้งชุดที่ตรงเงื่อนไข (ใช้ตอนส่งออก Excel) */
  function responseList(activityId, opts) {
    opts = opts || {};
    var band = opts.band || 'all';
    var keyword = String(opts.keyword || '').trim().toLowerCase();

    if (API_BASE) {
      return request('/activities/' + encodeURIComponent(activityId) +
        '/satisfaction/response-list?band=' + encodeURIComponent(band) +
        '&keyword=' + encodeURIComponent(keyword) +
        (opts.limit ? '&limit=' + opts.limit + '&offset=' + (opts.offset || 0) : ''));
    }

    var matched = rowsOf(activityId).map(function (row, i) {
      var pub = toPublicRow(row, i);
      pub.score = Math.round(pub.average);          /* แสดงเป็น N/5 */
      pub.band = bandOf(pub.score);
      pub.round = roundLabelOf(activityId, row.submittedAt);
      pub.comment = row.comment || '';
      delete pub.scores;                            /* หน้านี้ไม่ใช้คะแนนรายหัวข้อ */
      return pub;
    }).filter(function (row) {
      if (band !== 'all' && row.band !== band) return false;
      if (!keyword) return true;
      return ('ผู้ตอบ #' + row.seq).toLowerCase().indexOf(keyword) > -1 ||
        row.comment.toLowerCase().indexOf(keyword) > -1;
    });

    if (!opts.limit) return local({ total: matched.length, rows: matched });

    var offset = opts.offset || 0;
    return local({
      total: matched.length,
      limit: opts.limit,
      offset: offset,
      /* ของจริงคือ LIMIT/OFFSET ที่ server — ตัดหลังกรองเพื่อจำลองผลลัพธ์ชุดเดียวกัน */
      rows: matched.slice(offset, offset + opts.limit)
    });
  }

  /* ---------------------------------------------------------------
     ชั้นขนส่งข้อมูล — จุดเดียวที่ต้องแก้เมื่อมี backend จริง
     ทุกฟังก์ชันคืน Promise เสมอ หน้าจอจึงไม่รู้ว่าข้อมูลมาจากไหน
     --------------------------------------------------------------- */
  function request(path) {
    return fetch(API_BASE + path, {
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin'
    }).then(function (res) {
      if (!res.ok) throw new Error('satisfaction api ' + res.status + ' ' + path);
      return res.json();
    });
  }

  function local(value) {
    return new Promise(function (resolve) {
      setTimeout(function () { resolve(value); }, LATENCY);
    });
  }

  /* GET /satisfaction/activities — รายการกิจกรรมสำหรับ popover "เปลี่ยนกิจกรรม"
     แต่ละตัวเลือกต้องบอกจำนวนผู้ตอบจริง ไม่ใช่ยอดลงทะเบียน */
  function activities() {
    if (API_BASE) return request('/satisfaction/activities');
    return local((mock().activities || []).map(function (a) {
      return {
        id: a.id,
        name: a.name,
        startDate: a.startDate,
        endDate: a.endDate,
        time: a.time,
        responseCount: rowsOf(a.id).length
      };
    }));
  }

  /* GET /activities/:id/satisfaction/summary
     ทุกค่าคำนวณสดจากคำตอบรายคนของกิจกรรมนั้น */
  function summary(activityId) {
    if (API_BASE) return request('/activities/' + encodeURIComponent(activityId) + '/satisfaction/summary');

    var rows = rowsOf(activityId);
    var list = topics();
    var activity = activityOf(activityId);
    var attended = checkedInCount(activityId);

    var perRowAverages = rows.map(rowAverage);
    var average = mean(perRowAverages);

    var byTopic = list.map(function (topic, i) {
      var scores = rows.map(function (r) { return (r.scores || [])[i]; })
        .filter(function (v) { return typeof v === 'number'; });
      var value = round1(mean(scores));
      return {
        key: topic.key,
        label: topic.label,
        short: topic.short,
        average: value,
        needsWork: rows.length > 0 && value < TOPIC_WARN_BELOW
      };
    });

    /* การกระจายดาว: ปัดคะแนนเฉลี่ยของแต่ละคนเป็นจำนวนเต็ม 1–5 */
    var distribution = [5, 4, 3, 2, 1].map(function (star) {
      var count = perRowAverages.filter(function (v) { return Math.round(v) === star; }).length;
      return {
        star: star,
        count: count,
        percent: rows.length ? Math.round((count / rows.length) * 100) : 0
      };
    });

    var highCount = perRowAverages.filter(function (v) { return Math.round(v) >= 4; }).length;
    var responseRate = attended ? Math.round((rows.length / attended) * 100) : 0;

    return local({
      activityId: activityId,
      activityName: activity ? activity.name : '',
      activityCode: activity ? activity.id : '',
      average: round2(average),
      /* ยังไม่มีใครตอบ = ยังตัดเกรดไม่ได้ ไม่ใช่ "ต้องปรับปรุง" (คะแนน 0 มาจากการหารศูนย์) */
      grade: rows.length ? gradeOf(average) : { label: 'ยังไม่มีข้อมูล', tone: 'neutral' },
      responseCount: rows.length,
      attendedCount: attended,
      responseRate: responseRate,
      isRepresentative: responseRate >= REPRESENTATIVE_MIN,
      highRatioPercent: rows.length ? Math.round((highCount / rows.length) * 100) : 0,
      topics: byTopic,
      distribution: distribution
    });
  }

  /* GET /activities/:id/satisfaction/responses?limit=&offset=
     แบ่งหน้าที่ฝั่งเซิร์ฟเวอร์ — คืนเฉพาะแถวของหน้านั้นกับ total */
  function responses(activityId, opts) {
    opts = opts || {};
    var limit = opts.limit || 10;
    var offset = opts.offset || 0;

    if (API_BASE) {
      return request('/activities/' + encodeURIComponent(activityId) +
        '/satisfaction/responses?limit=' + limit + '&offset=' + offset);
    }

    var all = rowsOf(activityId);
    return local({
      total: all.length,
      limit: limit,
      offset: offset,
      rows: all.slice(offset, offset + limit).map(function (row, i) {
        return toPublicRow(row, offset + i);
      })
    });
  }

  /* GET /activities/:id/satisfaction/comments?filter=
     filter: all (ทั้งหมด) · praise (เฉลี่ย >= 4) · improve (เฉลี่ย <= 3) */
  function comments(activityId, opts) {
    opts = opts || {};
    var filter = opts.filter || 'all';

    if (API_BASE) {
      return request('/activities/' + encodeURIComponent(activityId) +
        '/satisfaction/comments?filter=' + encodeURIComponent(filter));
    }

    var withText = rowsOf(activityId)
      .map(function (row, i) {
        var pub = toPublicRow(row, i);
        pub.comment = row.comment || '';
        return pub;
      })
      .filter(function (row) { return !!row.comment; });

    var counts = {
      all: withText.length,
      praise: withText.filter(function (r) { return r.average >= 4; }).length,
      improve: withText.filter(function (r) { return r.average <= 3; }).length
    };

    var rows = withText.filter(function (r) {
      if (filter === 'praise') return r.average >= 4;
      if (filter === 'improve') return r.average <= 3;
      return true;
    });

    return local({ rows: rows, counts: counts });
  }

  return {
    activities: activities,
    summary: summary,
    responses: responses,
    responseList: responseList,
    comments: comments,
    topics: topics
  };
})();
