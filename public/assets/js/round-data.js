/* TheFarmConcept — ระบบรอบติดตาม: ชั้นข้อมูลและตรรกะที่ทั้ง 3 หน้าใช้ร่วมกัน
   (รายการรอบ · รายละเอียดรอบ · สร้างรอบ)

   คอนเซ็ปต์: แอดมินสร้างรอบเองโดยระบุ "ช่วงวันครบกำหนด" แล้วระบบดึงคนที่ครบกำหนดในช่วงนั้นมา
   แยกคนสองแบบเสมอ — แจ้งเตือนได้ (ผูก LINE) กับแจ้งเตือนไม่ได้ (แอดมินต้องติดตามนอกระบบ) */
(function () {
  var TFC = window.TFC = window.TFC || {};
  var C = TFC.cohort;

  var TODAY = C.TODAY;

  var STATES = ['กำลังดำเนินการ', 'รอเริ่ม', 'เสร็จสิ้น', 'ยกเลิกแล้ว'];

  var FORMS = ['แบบติดตามการเปลี่ยนแปลงสุขภาพ', 'แบบคัดกรองสุขภาพเบื้องต้น'];

  var BATCHES = [
    { id: 'b1', name: 'ติดตามกลุ่มตัวอย่าง ส.ค. 2569', from: '2026-08-01', to: '2026-08-31',
      form: FORMS[0], state: 'กำลังดำเนินการ' },
    { id: 'b2', name: 'ติดตามกลุ่มตัวอย่าง ก.ค. 2569', from: '2026-07-01', to: '2026-07-31',
      form: FORMS[0], state: 'เสร็จสิ้น' },
    { id: 'b3', name: 'ติดตามกลุ่มตัวอย่าง ก.ย. 2569', from: '2026-09-01', to: '2026-09-30',
      form: FORMS[0], state: 'รอเริ่ม' },
    { id: 'b4', name: 'ติดตามกลุ่มเปราะบาง Q3', from: '2026-08-05', to: '2026-08-20',
      form: FORMS[1], state: 'กำลังดำเนินการ' }
  ];

  var MEMBERS = {
    b1: [
      { pid: 'PID-0001', name: 'สมชาย ใจดี', phone: '081-234-5678', area: 'ชุมชนพูนทรัพย์', target: 'วัยทำงาน', round: '3 เดือน', due: '2026-08-12', line: true, answered: true },
      { pid: 'PID-0004', name: 'อารีย์ แสงทอง', phone: '084-567-8901', area: 'ชุมชนพูนทรัพย์', target: 'ผู้สูงอายุ', round: '12 เดือน', due: '2026-08-15', line: true, answered: false },
      { pid: 'PID-0006', name: 'กมลชนก อินทร์แก้ว', phone: '095-777-8899', area: 'ชุมชนตึกร้าง', target: 'วัยทำงาน', round: '6 เดือน', due: '2026-08-18', line: true, answered: false },
      { pid: 'PID-0007', name: 'สุริยา ทองใบ', phone: '080-222-3344', area: 'ชุมชนพูนทรัพย์', target: 'ผู้สูงอายุ', round: '12 เดือน', due: '2026-08-06', line: true, answered: false },
      { pid: 'PID-0003', name: 'ธนกฤต พงษ์ทอง', phone: '062-345-6789', area: 'ชุมชนตึกร้าง', target: 'เด็กและเยาวชน', round: '6 เดือน', due: '2026-08-09', line: false, answered: false },
      { pid: 'PID-0005', name: 'ณัฐพล บุญมี', phone: '091-234-5670', area: 'The Farm Concept', target: 'กลุ่มเปราะบาง', round: '3 เดือน', due: '2026-08-22', line: false, answered: false },
      { pid: 'PID-0009', name: 'ประเสริฐ มั่นคง', phone: '086-441-7788', area: 'ชุมชนตึกร้าง', target: 'ผู้สูงอายุ', round: '6 เดือน', due: '2026-08-27', line: false, answered: false }
    ],
    b2: [
      { pid: 'PID-0002', name: 'วิภาดา ศรีสุข', phone: '089-876-5432', area: 'The Farm Concept', target: 'วัยทำงาน', round: '3 เดือน', due: '2026-07-12', line: true, answered: true },
      { pid: 'PID-0008', name: 'สุดา พูลผล', phone: '092-330-4455', area: 'ชุมชนพูนทรัพย์', target: 'ผู้สูงอายุ', round: '6 เดือน', due: '2026-07-20', line: true, answered: true },
      { pid: 'PID-0010', name: 'บุญมี ทองคำ', phone: '081-556-9900', area: 'ชุมชนตึกร้าง', target: 'ผู้สูงอายุ', round: '6 เดือน', due: '2026-07-25', line: false, answered: true }
    ],
    b3: [
      { pid: 'PID-0011', name: 'จันทร์เพ็ญ ดีงาม', phone: '084-112-3344', area: 'ชุมชนพูนทรัพย์', target: 'ผู้สูงอายุ', round: '3 เดือน', due: '2026-09-05', line: true, answered: false },
      { pid: 'PID-0012', name: 'อนันต์ สุขสม', phone: '090-778-2211', area: 'The Farm Concept', target: 'วัยทำงาน', round: '6 เดือน', due: '2026-09-14', line: true, answered: false },
      { pid: 'PID-0013', name: 'ระวี แจ่มใส', phone: '063-909-3322', area: 'ชุมชนตึกร้าง', target: 'กลุ่มเปราะบาง', round: '12 เดือน', due: '2026-09-22', line: false, answered: false }
    ],
    b4: [
      { pid: 'PID-0005', name: 'ณัฐพล บุญมี', phone: '091-234-5670', area: 'The Farm Concept', target: 'กลุ่มเปราะบาง', round: '6 เดือน', due: '2026-08-08', line: false, answered: false },
      { pid: 'PID-0014', name: 'สมหญิง ใจงาม', phone: '087-220-6677', area: 'ชุมชนตึกร้าง', target: 'กลุ่มเปราะบาง', round: '6 เดือน', due: '2026-08-14', line: false, answered: false },
      { pid: 'PID-0015', name: 'ทวี ศรีสวัสดิ์', phone: '082-441-9988', area: 'ชุมชนพูนทรัพย์', target: 'ผู้สูงอายุ', round: '6 เดือน', due: '2026-08-19', line: true, answered: false }
    ]
  };

  /* คลังรายชื่อสำหรับหน้าสร้างรอบ — จำลองฝั่ง server
     ของจริงต้อง query ด้วย target + ช่วง due ที่ backend และแบ่งหน้าด้วย LIMIT/OFFSET
     ห้ามดึงทั้งหมดมากรองที่ frontend */
  var POOL = (function () {
    var out = [];
    Object.keys(MEMBERS).forEach(function (bid) {
      MEMBERS[bid].forEach(function (p) {
        if (!out.some(function (x) { return x.pid === p.pid; })) out.push(p);
      });
    });
    /* เติมให้มีจำนวนพอทดสอบการแบ่งหน้า */
    var extra = [
      { pid: 'PID-0016', name: 'พเยาว์ แซ่ตั้ง', phone: '085-330-1122', area: 'ชุมชนพูนทรัพย์', target: 'ผู้สูงอายุ', round: '3 เดือน', due: '2026-08-11', line: true },
      { pid: 'PID-0017', name: 'กิตติ ศรีนวล', phone: '083-771-4455', area: 'ชุมชนตึกร้าง', target: 'วัยทำงาน', round: '6 เดือน', due: '2026-08-13', line: true },
      { pid: 'PID-0018', name: 'ดวงใจ พันธ์ดี', phone: '089-224-6677', area: 'The Farm Concept', target: 'วัยทำงาน', round: '12 เดือน', due: '2026-08-16', line: false },
      { pid: 'PID-0019', name: 'ชัยวัฒน์ ก้องไกล', phone: '081-909-8877', area: 'ชุมชนพูนทรัพย์', target: 'เด็กและเยาวชน', round: '3 เดือน', due: '2026-08-20', line: true },
      { pid: 'PID-0020', name: 'มาลี สายทอง', phone: '094-118-2299', area: 'ชุมชนตึกร้าง', target: 'ผู้สูงอายุ', round: '6 เดือน', due: '2026-08-24', line: true },
      { pid: 'PID-0021', name: 'สุชาติ เรืองฤทธิ์', phone: '087-556-3311', area: 'The Farm Concept', target: 'กลุ่มเปราะบาง', round: '12 เดือน', due: '2026-08-28', line: false },
      { pid: 'PID-0022', name: 'อัมพร ทองเนื้อเก้า', phone: '062-880-7744', area: 'ชุมชนพูนทรัพย์', target: 'ผู้สูงอายุ', round: '3 เดือน', due: '2026-08-30', line: true }
    ];
    return out.concat(extra);
  })();

  /* สถานะรายคนของรอบ derive จากวันที่ครบกำหนด ห้าม hardcode
     ช่วงติดตามใช้เกณฑ์เดียวกับระบบกลุ่มตัวอย่าง: ครบกำหนด −7 ถึง +14 วัน */
  function memberState(p) {
    if (p.answered) return 'ตอบแล้ว';
    var from = C.addDays(p.due, -7);
    var to = C.addDays(p.due, 14);
    if (TODAY < from) return 'ยังไม่ถึงกำหนด';
    if (TODAY <= to) return 'รอติดตาม';
    return 'เกินกำหนด';
  }

  function membersOf(bid) {
    return (MEMBERS[bid] || []).map(function (p) {
      return Object.assign({}, p, { state: memberState(p) });
    });
  }

  /* ตัวเลขสรุปของแต่ละรอบ — คนที่ยังไม่ผูก LINE ไม่ถูกนับเป็นแจ้งเตือนได้ */
  function statsOf(bid) {
    var list = membersOf(bid);
    return {
      total: list.length,
      answered: list.filter(function (p) { return p.answered; }).length,
      notifiable: list.filter(function (p) { return p.line; }).length,
      unreachable: list.filter(function (p) { return !p.line; }).length
    };
  }

  function byId(id) { return BATCHES.filter(function (b) { return b.id === id; })[0]; }

  /* ค้นหารายชื่อสำหรับสร้างรอบ — จำลอง query ฝั่ง server
     คืนทั้ง total และเฉพาะหน้าที่ขอ เพื่อให้ frontend ไม่ต้องถือรายชื่อทั้งหมด */
  function queryPool(opts) {
    var targets = opts.targets || [];
    var matched = POOL.filter(function (p) {
      if (targets.length && targets.indexOf(p.target) < 0) return false;
      if (opts.from && p.due < opts.from) return false;
      if (opts.to && p.due > opts.to) return false;
      return true;
    }).sort(function (a, b) { return a.due.localeCompare(b.due); });

    var page = opts.page || 1;
    var size = opts.pageSize || 10;
    var start = (page - 1) * size;

    return {
      total: matched.length,
      /* ของจริงคือ LIMIT/OFFSET ที่ backend — ที่นี่ตัดหลังจากกรองเพื่อจำลองผลลัพธ์เดียวกัน */
      rows: matched.slice(start, start + size).map(function (p) {
        return Object.assign({}, p, { state: memberState(p) });
      }),
      allPids: matched.map(function (p) { return p.pid; }),
      notifiablePids: matched.filter(function (p) { return p.line; }).map(function (p) { return p.pid; })
    };
  }

  /* บันทึกรอบใหม่ + ส่งแจ้งเตือน
     คนที่ยังไม่ผูก LINE ไม่ถูกนับเป็นส่งสำเร็จ และถูกรายงานกลับให้แอดมินติดตามเอง
     ทุกคนที่ถูกส่งจะได้ log ไปโผล่ในแท็บ "ประวัติการติดตาม" ของหน้ากลุ่มตัวอย่าง */
  function createBatch(data, pids) {
    var id = 'b' + (BATCHES.length + 1);
    var picked = POOL.filter(function (p) { return pids.indexOf(p.pid) > -1; })
      .map(function (p) { return Object.assign({}, p, { answered: false }); });

    BATCHES.unshift({ id: id, name: data.name, from: data.from, to: data.to, form: data.form, state: data.state });
    MEMBERS[id] = picked;

    var sent = 0, failed = 0;
    picked.forEach(function (p) {
      var member = C.byPid(p.pid);
      var ok = !!p.line;
      if (ok) sent++; else failed++;
      if (member) C.logLine(member, { name: 'ติดตาม ' + p.round }, ok);
    });

    return { id: id, sent: sent, failed: failed };
  }

  function cancelBatch(id) {
    var b = byId(id);
    if (b) b.state = 'ยกเลิกแล้ว';
  }

  /* บันทึกผลติดตามนอกระบบของคนที่แจ้งเตือนไม่ได้ */
  var offline = {};

  function offlineLog(bid, pid) { return (offline[bid] || {})[pid] || null; }

  function saveOffline(bid, pid, kind, note) {
    offline[bid] = offline[bid] || {};
    var now = new Date();
    offline[bid][pid] = {
      kind: kind, note: note,
      at: C.iso(now),
      time: String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0'),
      by: (window.TFC_MOCK.currentUser && window.TFC_MOCK.currentUser.name) || 'ผู้ดูแลระบบ'
    };
    /* ให้ไปโผล่ในประวัติของคนนั้นด้วย จะได้ไม่ต้องคีย์ซ้ำสองที่ */
    var member = C.byPid(pid);
    if (member) C.addNote(member, { at: C.iso(now), time: offline[bid][pid].time, kind: kind, text: note, by: offline[bid][pid].by });
    return offline[bid][pid];
  }

  TFC.rounds = {
    TODAY: TODAY,
    STATES: STATES,
    FORMS: FORMS,
    batches: function () { return BATCHES; },
    byId: byId,
    membersOf: membersOf,
    statsOf: statsOf,
    memberState: memberState,
    queryPool: queryPool,
    createBatch: createBatch,
    cancelBatch: cancelBatch,
    offlineLog: offlineLog,
    saveOffline: saveOffline,
    TARGETS: C.TARGETS,
    NOTE_KINDS: ['โทรติดตาม', 'เยี่ยมบ้าน', 'ฝากผู้นำชุมชน', 'ส่ง SMS'],

    /* ข้อความแจ้งเตือนตั้งต้น — ตัวแปรถูกแทนค่าตอนพรีวิวและตอนส่งจริง */
    DEFAULT_MSG: 'สวัสดีคุณ {ชื่อ} 🌱\nถึงเวลาทำแบบประเมิน{รอบ}แล้ว\nกรุณาตอบภายในวันที่ {วันครบกำหนด}',
    fillMsg: function (tpl, p) {
      return tpl
        .replace(/\{ชื่อ\}/g, p.name)
        .replace(/\{รอบ\}/g, 'ติดตาม ' + p.round)
        .replace(/\{วันครบกำหนด\}/g, C.fmt(p.due));
    }
  };
})();
