/* TheFarmConcept — ระบบกลุ่มตัวอย่าง: ชั้นข้อมูลและตรรกะที่หน้ารายการกับหน้ารายละเอียดใช้ร่วมกัน

   หลักการที่ต้องรักษาไว้:
   - 1 คน = 1 profile และมีได้กลุ่มเดียว จึงไม่มีตัวกรอง "เปลี่ยนกลุ่ม" ที่ไหนเลย
   - วันครบกำหนดของแต่ละรอบคำนวณจาก "วันที่เข้ากลุ่มตัวอย่าง" ของคนนั้น
     ไม่มีรอบกลางที่ทุกคนใช้ร่วมกัน คนที่เข้าคนละวันจึงครบกำหนดคนละวัน
   - ช่วงติดตาม = วันครบกำหนด −7 ถึง +14 วัน
   - สถานะทุกอย่าง derive จากวันที่ ไม่มีสถานะที่พิมพ์ทับไว้
   - วันที่เก็บเป็น ISO (YYYY-MM-DD) แสดงผลเป็น พ.ศ. ย่อ ผ่าน TFC.cohort.fmt() */
(function () {
  var TFC = window.TFC = window.TFC || {};

  /* วันอ้างอิงของต้นแบบ — ตอนต่อ backend ให้เปลี่ยนเป็นวันจริงของ server
     ตรึงไว้เพื่อให้สถานะที่ derive ออกมาคงที่ทุกครั้งที่เปิดดู */
  var TODAY = '2026-08-10';
  var TARGET = 120;
  var MONTHS = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

  function pad(n) { return n < 10 ? '0' + n : String(n); }
  function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }

  function addMonths(s, n) {
    var p = s.split('-').map(Number);
    return iso(new Date(p[0], p[1] - 1 + n, p[2]));
  }

  function addDays(s, n) {
    var p = s.split('-').map(Number);
    return iso(new Date(p[0], p[1] - 1, p[2] + n));
  }

  function fmt(s) {
    if (!s) return '—';
    var p = s.split('-').map(Number);
    return p[2] + ' ' + MONTHS[p[1] - 1] + ' ' + String((p[0] + 543) % 100);
  }

  /* จำนวนวันระหว่างสองวันที่ — ใช้แปลงวันครบกำหนดที่แอดมินแก้ทับ กลับเป็น offset ของคนนั้น */
  function daysBetween(from, to) {
    var a = from.split('-').map(Number), b = to.split('-').map(Number);
    return Math.round((new Date(b[0], b[1] - 1, b[2]) - new Date(a[0], a[1] - 1, a[2])) / 86400000);
  }

  /* จำนวนวันจากวันนี้ถึงวันที่กำหนด (บวก = อนาคต) */
  function daysFromToday(s) {
    var a = TODAY.split('-').map(Number), b = s.split('-').map(Number);
    return Math.round((new Date(b[0], b[1] - 1, b[2]) - new Date(a[0], a[1] - 1, a[2])) / 86400000);
  }

  /* รอบติดตามมาจาก master data (พื้นฐาน > ตั้งค่ารอบติดตาม) ไม่ฝัง 3/6/12 ไว้ที่นี่
     แอดมินเพิ่ม/ลบ/แก้จำนวนวันในหน้านั้นแล้วระบบกลุ่มตัวอย่างต้องตามทันที
     id ยังเป็น r0..rN ตามลำดับเหมือนเดิม เพราะคีย์ใน MEMBERS[].ans อ้างอิงชุดนี้อยู่ */
  var TPL = TFC.followUpTemplateService;

  function buildRounds(templates) {
    return TPL.activeTemplates(templates).map(function (t, i) {
      return { id: 'r' + i, templateId: t.id, name: t.name, short: t.name, offsetDays: t.offsetDays };
    });
  }

  var ROUNDS = buildRounds(TPL.cached());

  var MEMBERS = [
    { id: 'm1', pid: 'PID-0001', name: 'สมชาย ใจดี', phone: '081-234-5678', gender: 'ชาย', age: '36–45 ปี',
      job: 'ค้าขาย / ธุรกิจส่วนตัว', source: 'สมัครสมาชิก', area: 'ชุมชนพูนทรัพย์', target: 'วัยทำงาน',
      line: true, consent: true, base: '2025-08-05', ans: { r0: '2025-08-05', r1: '2025-11-07', r2: '2026-02-08' } },
    { id: 'm2', pid: 'PID-0002', name: 'วิภาดา ศรีสุข', phone: '089-876-5432', gender: 'หญิง', age: '26–35 ปี',
      job: 'พนักงานบริษัท', source: 'ลูกค้าประจำ (คาเฟ่/สวน/กิจกรรม)', area: 'The Farm Concept', target: 'วัยทำงาน',
      line: true, consent: true, base: '2026-05-10', ans: { r0: '2026-05-10' } },
    { id: 'm3', pid: 'PID-0003', name: 'ธนกฤต พงษ์ทอง', phone: '062-345-6789', gender: 'ชาย', age: 'ต่ำกว่า 25 ปี',
      job: 'นักเรียน / นักศึกษา', source: 'ผู้เข้าร่วมกรอกข้อมูลเอง', area: 'ชุมชนตึกร้าง', target: 'เด็กและเยาวชน',
      line: false, consent: true, base: '2025-06-01', ans: { r0: '2025-06-01', r1: '2025-09-03' } },
    { id: 'm4', pid: 'PID-0004', name: 'อารีย์ แสงทอง', phone: '084-567-8901', gender: 'หญิง', age: '60 ปีขึ้นไป',
      job: 'เกษียณ / ไม่ได้ทำงาน', source: 'สมัครเข้าร่วมโครงการวิจัย', area: 'ชุมชนพูนทรัพย์', target: 'ผู้สูงอายุ',
      line: true, consent: true, base: '2025-07-20', ans: { r0: '2025-07-20', r1: '2025-10-22', r2: '2026-01-24', r3: '2026-07-22' } },
    { id: 'm5', pid: 'PID-0005', name: 'ณัฐพล บุญมี', phone: '091-234-5670', gender: 'ชาย', age: '46–60 ปี',
      job: 'รับจ้างทั่วไป', source: 'เจ้าหน้าที่นำเข้าข้อมูล', area: 'The Farm Concept', target: 'กลุ่มเปราะบาง',
      line: false, consent: true, base: '2026-07-15', ans: { r0: '2026-07-15' } },
    { id: 'm6', pid: 'PID-0006', name: 'กมลชนก อินทร์แก้ว', phone: '095-777-8899', gender: 'หญิง', age: '26–35 ปี',
      job: 'พนักงานบริษัท', source: 'สมัครสมาชิก', area: 'ชุมชนตึกร้าง', target: 'วัยทำงาน',
      line: true, consent: true, base: '2026-02-01', ans: { r0: '2026-02-01', r1: '2026-05-04' } },
    { id: 'm7', pid: 'PID-0007', name: 'สุริยา ทองใบ', phone: '080-222-3344', gender: 'ชาย', age: '60 ปีขึ้นไป',
      job: 'เกษียณ / ไม่ได้ทำงาน', source: 'ลูกค้าประจำ (คาเฟ่/สวน/กิจกรรม)', area: 'ชุมชนพูนทรัพย์', target: 'ผู้สูงอายุ',
      line: true, consent: true, base: '2025-09-10', ans: { r0: '2025-09-10', r1: '2025-12-12', r2: '2026-03-14' } }
  ];

  /* ระเบียนที่มีอยู่แล้วถูกสร้างตอนที่ระบบยังใช้ค่าตั้งต้น จึง snapshot ค่านั้นติดตัวไว้
     ทำให้เห็นพฤติกรรมจริง: แก้จำนวนวันในหน้าตั้งค่าแล้วคนกลุ่มนี้ไม่ขยับ มีผลเฉพาะคนที่เพิ่มใหม่ */
  MEMBERS.forEach(function (m) { m.rounds = buildRounds(TPL.defaults()); });

  /* คนที่ถูกยุติการติดตาม เก็บแยกเพราะเป็นสถานะที่แอดมินกดเอง ไม่ได้ derive จากวันที่ */
  var stopped = {};

  /* รอบของคนหนึ่งคน — ใช้ snapshot ที่ติดมากับระเบียนของคนนั้น ไม่อ่าน template สด
     ถ้าอ่านสด พอแอดมินแก้จำนวนวันในหน้าตั้งค่า วันครบกำหนดของคนเก่าจะขยับตามทั้งกระดาน
     ซึ่งผิด เพราะบางคนตอบแบบประเมินไปแล้วตามกำหนดเดิม
     ของจริงคือคอลัมน์ offset_days ในตารางรอบของแต่ละคน ไม่ใช่ join กลับไปที่ template */
  function roundsOf(m) {
    return m.rounds || buildRounds(TPL.cached());
  }

  /* ตารางรอบของคนหนึ่งคน — ทุกรอบคำนวณจาก base ของคนนั้น */
  function schedule(m) {
    var isStopped = !!stopped[m.id];
    return roundsOf(m).map(function (r) {
      var due = addDays(m.base, r.offsetDays);
      /* รอบที่ offset 0 ทำในวันที่เข้ากลุ่มเลย ไม่มีช่วงให้รอ */
      var from = r.offsetDays === 0 ? m.base : addDays(due, -7);
      var to = r.offsetDays === 0 ? m.base : addDays(due, 14);
      var at = m.ans[r.id] || null;
      var state;
      if (at) state = 'ตอบแล้ว';
      else if (isStopped) state = 'ยุติการติดตาม';
      else if (TODAY < from) state = 'ยังไม่ถึงกำหนด';
      else if (TODAY <= to) state = 'รอติดตาม';
      else state = 'เกินกำหนด';
      return { id: r.id, name: r.name, short: r.short, due: due, from: from, to: to, at: at, state: state };
    });
  }

  /* สถานะรายคน derive จากสถานะรายรอบทั้งหมด */
  function personStatus(m) {
    if (stopped[m.id]) return 'ยุติการติดตาม';
    var sc = schedule(m);
    if (sc.every(function (r) { return r.state === 'ตอบแล้ว'; })) return 'ติดตามครบ';
    if (sc.filter(function (r) { return r.state === 'เกินกำหนด'; }).length >= 2) return 'หลุดการติดตาม';
    return 'กำลังติดตาม';
  }

  /* ลำดับการเลือกรอบถัดไป: รอบที่เปิดอยู่และยังไม่ตอบ → รอบที่ยังไม่ถึง → รอบที่เลยกำหนดล่าสุด */
  function nextRound(m) {
    var sc = schedule(m);
    var open = sc.filter(function (r) { return r.state === 'รอติดตาม'; })[0];
    if (open) return open;
    var soon = sc.filter(function (r) { return r.state === 'ยังไม่ถึงกำหนด'; })[0];
    if (soon) return soon;
    var over = sc.filter(function (r) { return r.state === 'เกินกำหนด'; });
    return over.length ? over[over.length - 1] : null;
  }

  /* ข้อความกำหนดของรอบถัดไป — บอกว่าต้องทำอะไรภายในเมื่อไหร่ */
  function dueText(round) {
    if (!round) return { text: '—', tone: '' };
    if (round.state === 'รอติดตาม') return { text: 'ภายใน ' + fmt(round.to), tone: 'is-due' };
    if (round.state === 'ยังไม่ถึงกำหนด') return { text: 'เปิด ' + fmt(round.from), tone: '' };
    if (round.state === 'เกินกำหนด') return { text: 'เลยกำหนด ' + fmt(round.to), tone: 'is-over' };
    return { text: fmt(round.due), tone: '' };
  }

  /* ลิงก์แบบประเมินเป็น token เฉพาะบุคคล+รอบ หมดอายุเมื่อปิดรอบ
     ต้นแบบสร้าง token จาก pid+round ตอนต่อ backend ให้ขอ token จาก server ที่ผูกวันหมดอายุไว้ */
  function surveyLink(m, roundId) {
    return 'farmconcept.th/h/' + m.pid.replace('PID-', '').toLowerCase() + (roundId ? '-' + roundId : '');
  }

  /* ลิงก์ผูก LINE — คงที่ตัวเดียวทั้งระบบ ไม่มี pid อยู่ใน URL
     เหตุผลที่ไม่ทำเป็นลิงก์เฉพาะคน: ผูกผิดคนอันตรายกว่าดูข้อมูลผิดคน
     เพราะ LINE ที่ผูกผิดจะรับแจ้งเตือนของคนนั้นไปตลอดโดยไม่มีใครรู้ว่าผิด

     ขั้นตอนที่ backend ต้องทำ:
     1. LINE Login เอา userId
     2. ยืนยันตัวตน — เบอร์โทร + รหัสเข้าใช้บนใบยินยอม
     3. ผูก userId เข้ากับ profile นั้น
     เงื่อนไขบังคับ: ถ้า LINE บัญชีนั้นผูกกับคนอื่นอยู่แล้วต้องปฏิเสธ ห้ามผูกซ้อน
     และการผูกทุกครั้งต้องบันทึก log ให้เห็นในแท็บ "ประวัติการติดตาม" */
  var LINE_LINK_URL = 'farmconcept.th/l';

  /* บันทึกการส่ง LINE — คนที่ยังไม่ผูก LINE ต้องไม่นับเป็นส่งสำเร็จ
     log ที่บันทึกที่นี่จะไปโผล่ในแท็บ "ประวัติการติดตาม" ของหน้ากลุ่มตัวอย่างด้วย
     ตอนต่อ backend ให้เปลี่ยนเป็น POST /line-logs แล้วให้ notesOf() ดึงจาก API เดียวกัน */
  function logLine(m, round, ok) {
    var now = new Date();
    var entry = {
      id: m.id + '-sys' + (sysSeq++),
      src: 'ระบบแจ้งเตือน',
      at: iso(now),
      time: String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0'),
      kind: 'แจ้งเตือน LINE',
      text: 'ส่งแจ้งเตือน' + (round ? round.name : 'รอบติดตาม') + ' · ' +
        (ok ? 'ส่งสำเร็จ' : 'ส่งไม่สำเร็จ (ยังไม่ผูก LINE)'),
      by: 'ระบบอัตโนมัติ'
    };
    sysNotes[m.id] = sysNotes[m.id] || [];
    sysNotes[m.id].unshift(entry);

    console.info('[line-log]', {
      pid: m.pid, name: m.name, round: round ? round.name : '-',
      result: ok ? 'ส่งสำเร็จ' : 'ส่งไม่สำเร็จ (ยังไม่ผูก LINE)',
      at: now.toISOString()
    });
    return entry;
  }

  var sysNotes = {}, sysSeq = 1;

  /* ---------- ข้อมูลของหน้ารายละเอียด ----------
     ทุกชุดผูกกับ base ของคนนั้น จึงเปลี่ยนตามคนที่เปิดดู ไม่ใช่ชุดตัวอย่างชุดเดียว */

  /* 1 คนเข้าได้หลายกิจกรรม/โปรแกรม/หลักสูตร */
  function activitiesOf(m) {
    return [
      { at: m.base, name: 'ตรวจสุขภาพเบื้องต้นและให้คำปรึกษา', program: 'โปรแกรมสุขภาพชุมชน', place: 'The Farm Concept', status: 'เข้าร่วม' },
      { at: addMonths(m.base, 1), name: 'ปลูกผักปลอดสารสำหรับครอบครัว', program: 'โปรแกรมเกษตรในเมือง · ปลูกผักในพื้นที่จำกัด', place: m.area, status: 'เข้าร่วม' },
      { at: addMonths(m.base, 3), name: 'อบรมแกนนำสุขภาพชุมชน', program: 'โปรแกรมพัฒนาแกนนำ · แกนนำสุขภาพชุมชน', place: m.area, status: m.line ? 'เข้าร่วม' : 'ไม่มา' },
      { at: addMonths(m.base, 5), name: 'ตลาดนัดผักปลอดสารประจำเดือน', program: 'โปรแกรมเกษตรในเมือง', place: m.area, status: 'เข้าร่วม' }
    ];
  }

  /* การซื้อสินค้า — แก้ไข/ลบด้วย overrides map ที่อ้าง id เดิม ไม่ใช่ลบแล้วเพิ่มใหม่
     เพื่อให้ประวัติยังชี้ไปที่รายการเดิมได้หลังแก้ */
  var orderExtra = {}, orderEdited = {}, orderRemoved = {}, orderSeq = 1;

  function ordersOf(m) {
    var seed = parseInt(m.pid.slice(-2), 10) || 1;
    var base = [
      { id: m.id + '-o1', at: addMonths(m.base, 2), item: 'ชุดผักสลัดปลอดสาร 1 กก.', shop: 'ตลาดนัดชุมชนพูนทรัพย์', channel: 'หน้าร้าน', amount: 180 + seed * 5, status: 'ชำระแล้ว', by: 'อรุณี ทองสุข' },
      { id: m.id + '-o2', at: addMonths(m.base, 4), item: 'ไข่ไก่อารมณ์ดี 30 ฟอง', shop: 'The Farm Concept Shop', channel: 'LINE OA', amount: 220 + seed * 3, status: 'ชำระแล้ว', by: 'วีระ ศรีสมบัติ' },
      { id: m.id + '-o3', at: addMonths(m.base, 6), item: 'ข้าวกล้องหอมมะลิ 5 กก.', shop: 'The Farm Concept Shop', channel: 'หน้าร้าน', amount: 320 + seed * 4, status: 'รอชำระ', by: 'สุนิสา แก้วมณี' }
    ];
    var removed = orderRemoved[m.id] || {};
    var edits = orderEdited[m.id] || {};
    return (orderExtra[m.id] || []).concat(base)
      .filter(function (o) { return !removed[o.id]; })
      .map(function (o) { return edits[o.id] || o; })
      .sort(function (a, b) { return b.at.localeCompare(a.at); });
  }

  /* บันทึกติดตาม — แยกสองแหล่ง: ระบบแจ้งเตือน (log อัตโนมัติ แก้/ลบไม่ได้) กับแอดมินคีย์เอง */
  var noteExtra = {}, noteEdited = {}, noteRemoved = {}, noteSeq = 1;

  function notesOf(m) {
    var base = [
      { id: m.id + '-s1', src: 'ระบบแจ้งเตือน', at: addDays(addMonths(m.base, 3), -3), time: '09:00', kind: 'แจ้งเตือน LINE',
        text: 'ระบบส่งแจ้งเตือนรอบติดตาม 3 เดือน ล่วงหน้า 3 วัน · ส่งสำเร็จ', by: 'ระบบอัตโนมัติ' },
      { id: m.id + '-b1', src: 'แอดมินติดตาม', at: addMonths(m.base, 3), time: '14:25', kind: 'โทรติดตาม',
        text: 'โทรแจ้งรอบติดตาม 3 เดือน ผู้เข้าร่วมรับสาย ยืนยันจะทำแบบประเมินภายในสัปดาห์นี้', by: 'สุนิสา แก้วมณี' },
      { id: m.id + '-s2', src: 'ระบบแจ้งเตือน', at: addDays(addMonths(m.base, 6), -3), time: '09:00', kind: 'แจ้งเตือน LINE',
        text: 'ระบบส่งแจ้งเตือนรอบติดตาม 6 เดือน ล่วงหน้า 3 วัน · ' + (m.line ? 'ส่งสำเร็จ' : 'ส่งไม่สำเร็จ (ยังไม่ผูก LINE)'), by: 'ระบบอัตโนมัติ' },
      { id: m.id + '-b2', src: 'แอดมินติดตาม', at: addMonths(m.base, 6), time: '10:40', kind: 'เยี่ยมบ้าน',
        text: 'ลงเยี่ยมบ้าน พบว่าปลูกผักต่อเนื่องในกระถางหลังบ้าน แต่ยังกินผักไม่ถึงเป้าวันละ 4 ทัพพี', by: 'วีระ ศรีสมบัติ' }
    ];
    var removed = noteRemoved[m.id] || {};
    var edits = noteEdited[m.id] || {};
    /* รวม log ที่ระบบเพิ่งส่งจริงจากหน้ารอบติดตามเข้ามาด้วย */
    return (noteExtra[m.id] || []).concat(sysNotes[m.id] || []).concat(base)
      .filter(function (n) { return !removed[n.id]; })
      .map(function (n) { return edits[n.id] || n; })
      .sort(function (a, b) { return (b.at + b.time).localeCompare(a.at + a.time); });
  }

  TFC.cohort = {
    TODAY: TODAY,
    TARGET: TARGET,
    ROUNDS: ROUNDS,
    /* สำหรับหน้าเพิ่มคนใหม่ — ต้อง snapshot ชุดนี้ลงระเบียนของคนนั้นตอนสร้าง */
    buildRounds: buildRounds,
    members: function () { return MEMBERS; },
    byId: function (id) { return MEMBERS.filter(function (m) { return m.id === id; })[0]; },
    byPid: function (pid) { return MEMBERS.filter(function (m) { return m.pid === pid; })[0]; },
    stopped: stopped,
    fmt: fmt,
    iso: iso,
    addMonths: addMonths,
    addDays: addDays,
    daysBetween: daysBetween,
    daysFromToday: daysFromToday,
    schedule: schedule,
    personStatus: personStatus,
    nextRound: nextRound,
    dueText: dueText,
    surveyLink: surveyLink,
    LINE_LINK_URL: LINE_LINK_URL,
    logLine: logLine,

    activitiesOf: activitiesOf,
    ordersOf: ordersOf,
    notesOf: notesOf,

    /* ---- แก้ไข/ลบรายการซื้อ (ใช้ id เดิมเสมอ) ---- */
    addOrder: function (m, o) {
      orderExtra[m.id] = orderExtra[m.id] || [];
      o.id = m.id + '-x' + (orderSeq++);
      orderExtra[m.id].unshift(o);
      return o.id;
    },
    editOrder: function (m, id, o) {
      orderEdited[m.id] = orderEdited[m.id] || {};
      o.id = id;
      orderEdited[m.id][id] = o;
    },
    removeOrder: function (m, id) {
      orderRemoved[m.id] = orderRemoved[m.id] || {};
      orderRemoved[m.id][id] = true;
    },

    /* ---- แก้ไข/ลบบันทึกติดตาม ---- */
    addNote: function (m, n) {
      noteExtra[m.id] = noteExtra[m.id] || [];
      n.id = m.id + '-n' + (noteSeq++);
      n.src = 'แอดมินติดตาม';
      noteExtra[m.id].unshift(n);
      return n.id;
    },
    editNote: function (m, id, n) {
      noteEdited[m.id] = noteEdited[m.id] || {};
      n.id = id;
      noteEdited[m.id][id] = n;
    },
    removeNote: function (m, id) {
      noteRemoved[m.id] = noteRemoved[m.id] || {};
      noteRemoved[m.id][id] = true;
    },

    AREAS: ['The Farm Concept', 'ชุมชนพูนทรัพย์', 'ชุมชนตึกร้าง'],
    TARGETS: ['เด็กและเยาวชน', 'วัยทำงาน', 'ผู้สูงอายุ', 'กลุ่มเปราะบาง'],
    /* 5 ค่านี้เป็น dataset ที่กำหนดมา ห้ามเพิ่มลดเอง */
    SOURCES: ['สมัครสมาชิก', 'สมัครเข้าร่วมโครงการวิจัย', 'ลูกค้าประจำ (คาเฟ่/สวน/กิจกรรม)',
              'เจ้าหน้าที่นำเข้าข้อมูล', 'ผู้เข้าร่วมกรอกข้อมูลเอง'],
    GENDERS: ['หญิง', 'ชาย', 'ไม่ระบุ'],
    AGES: ['ต่ำกว่า 25 ปี', '26–35 ปี', '36–45 ปี', '46–60 ปี', '60 ปีขึ้นไป'],
    JOBS: ['พนักงานบริษัท', 'นักเรียน / นักศึกษา', 'ค้าขาย / ธุรกิจส่วนตัว', 'รับจ้างทั่วไป', 'เกษียณ / ไม่ได้ทำงาน'],
    PERSON_STATUSES: ['กำลังติดตาม', 'รอเริ่มติดตาม', 'ยุติการติดตาม'],
    SHOPS: ['The Farm Concept Shop', 'ตลาดนัดชุมชนพูนทรัพย์', 'ร้านค้าชุมชนตึกร้าง'],
    CHANNELS: ['หน้าร้าน', 'LINE OA', 'ออกบูธกิจกรรม', 'สั่งล่วงหน้า'],
    NOTE_KINDS: ['โทรติดตาม', 'เยี่ยมบ้าน', 'ส่งข้อความ LINE', 'พบที่กิจกรรม']
  };
})();
