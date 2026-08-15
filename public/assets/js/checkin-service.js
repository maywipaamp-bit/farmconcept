/* TheFarmConcept — Check-in Service
   ทุกการอ่านและเขียนสถานะเช็คอินของหน้า admin/activities/checkin.html ผ่านไฟล์นี้ที่เดียว
   หน้าจอไม่แตะ TFC_MOCK ตรง ๆ เลย เมื่อต่อ backend จริงให้แก้เฉพาะฟังก์ชันใน "ชั้นขนส่งข้อมูล"
   ด้านล่าง โดยที่หน้าจอไม่ต้องแก้อะไรเลย

   กฎสามข้อที่ไฟล์นี้รับผิดชอบ (มาจากข้อกำหนดของหน้าจอ)
   1. ข้อมูลแยกตามกิจกรรม — ทุก API รับ activityId เป็นพารามิเตอร์แรกเสมอ
      รายชื่อ ตัวเลขสรุป และ Walk-in ของแต่ละกิจกรรมไม่ปนกัน
   2. เวลาเช็คอินเป็น "เวลาของเซิร์ฟเวอร์" — ห้ามใช้ new Date() ของเครื่องผู้ใช้มาประทับเวลา
      เพราะนาฬิกาเครื่องหน้างานตั้งเองได้ ลำดับการเช็คอินจะเพี้ยนทันที
      ใน demo จึงนับต่อจากคนล่าสุด "ในรอบเดียวกัน" (ดู stampFor)
   3. การยกเลิกเช็คอินต้องบันทึก audit log ที่ฝั่งเซิร์ฟเวอร์ — ใคร ยกเลิกของใคร เมื่อไหร่
      undoCheckIn จึงคืน entry ของ audit log กลับมาให้หน้าจอยืนยันได้ว่าเขียนสำเร็จแล้ว

   การใช้งาน
     var svc = TFC.checkinService;
     svc.snapshot(activityId).then(function (snap) { ... });
     svc.checkIn(activityId, personId).then(function (person) { ... });
     svc.undoCheckIn(activityId, personId).then(function (res) { res.audit ... });
     svc.addWalkIn(activityId, { name, phone, roundKey, ageRange }).then(function (person) { ... });
     var stop = svc.subscribe(activityId, function (event) { ... });   // realtime
*/
window.TFC = window.TFC || {};

window.TFC.checkinService = (function () {
  /* ตั้งค่าปลายทาง backend ได้จากหน้าเว็บ (window.TFC_CONFIG) โดยไม่ต้องแก้ไฟล์นี้
     - checkinApiBase : ตั้งเมื่อมี REST API จริง ถ้าเว้นว่างจะทำงานบนข้อมูลตัวอย่างในเครื่อง
     - checkinSocketUrl : ตั้งเมื่อมี WebSocket ถ้าเว้นว่างจะถอยไปใช้ polling อัตโนมัติ
     - checkinPollMs : จังหวะ polling เมื่อไม่มี WebSocket */
  var cfg = window.TFC_CONFIG || {};
  var API_BASE = cfg.checkinApiBase || '';
  var SOCKET_URL = cfg.checkinSocketUrl || '';
  var POLL_MS = cfg.checkinPollMs || 4000;
  var LATENCY = 220;

  /* ระยะห่างระหว่างคนที่เช็คอินติดกันใน demo — คนต่อคิวสแกนห่างกันประมาณสองนาที */
  var STEP_MIN = 2;

  /* ---------------------------------------------------------------
     สถานะฝั่ง "เซิร์ฟเวอร์จำลอง" — แยกกล่องตาม activityId ทุกกล่อง
     โครงสร้าง: state[activityId] = { people: [...], rounds: [...] }
     --------------------------------------------------------------- */
  var state = {};
  var walkSeq = {};
  var listeners = [];

  function mock() { return window.TFC_MOCK || {}; }

  function activityOf(activityId) {
    return (mock().activities || []).filter(function (a) { return a.id === activityId; })[0];
  }

  function pad2(n) { return String(n).padStart(2, '0'); }
  function pad3(n) { return String(n).padStart(3, '0'); }

  function toMinutes(text) {
    var m = /(\d{1,2})[:.](\d{2})/.exec(text || '');
    return m ? Number(m[1]) * 60 + Number(m[2]) : 0;
  }

  function toClock(total) {
    total = Math.max(0, total);
    return pad2(Math.floor(total / 60) % 24) + ':' + pad2(total % 60);
  }

  /* รอบของกิจกรรม — มาจาก activitySessions ถ้ามีรอบเดียวไม่ต้องใส่เลขรอบให้รก */
  function buildRounds(activity) {
    var sessions = (mock().activitySessions || {})[activity.id] || [];
    if (!sessions.length) {
      return [{
        key: activity.id + '-S1', label: 'รอบเดียว',
        date: activity.startDate, time: activity.time || '',
        startMin: toMinutes(activity.time) || 540
      }];
    }
    return sessions.map(function (s, i) {
      return {
        key: activity.id + '-S' + (i + 1),
        label: sessions.length > 1 ? 'รอบ ' + (i + 1) : 'รอบเดียว',
        date: s.date,
        time: s.time || activity.time || '',
        startMin: toMinutes(s.time || activity.time) || 540
      };
    });
  }

  function roundOf(rounds, sessionDate) {
    return rounds.filter(function (r) { return r.date === sessionDate; })[0] || rounds[0];
  }

  /* แปลงข้อมูลลงทะเบียนดิบให้เป็นรูปที่หน้าจอเช็คอินใช้ — ทำครั้งเดียวต่อกิจกรรม
     เวลาเช็คอินของคนที่เช็คอินมาก่อนแล้วใช้ค่าที่มากับข้อมูล (ถือว่าเซิร์ฟเวอร์ประทับมาแล้ว)
     ไม่ได้คำนวณใหม่จากนาฬิกาเครื่อง */
  function boot(activityId) {
    if (state[activityId]) return state[activityId];

    var activity = activityOf(activityId);
    if (!activity) {
      state[activityId] = { people: [], rounds: [] };
      return state[activityId];
    }

    var rounds = buildRounds(activity);
    var suffix = activity.id.slice(-3);

    var people = ((mock().activityRegistrations || {})[activityId] || []).map(function (r, i) {
      var checkedIn = r.checkinStatus === 'เข้าร่วมแล้ว';
      /* Walk-in คือคนที่เจ้าหน้าที่บันทึกหน้างาน จึงต้องเช็คอินแล้วเสมอ
         "เดินมาหน้างานแต่ยังไม่มา" เป็นสถานะที่ขัดกันเอง */
      var walkIn = !!r.manualEntry && checkedIn;
      var round = roundOf(rounds, r.session);
      return {
        id: r.id,
        code: (walkIn ? 'WLK-' : 'REG-') + suffix + '-' + pad3(i + 1),
        name: r.name,
        phone: r.phone || '',
        ageRange: r.ageRange || '',
        roundKey: round.key,
        round: round.label,
        walkIn: walkIn,
        checkedInAt: checkedIn ? (r.checkedInAt || toClock(round.startMin)) : '',
        /* scan = ผู้เข้าร่วมสแกน QR เอง · staff = เจ้าหน้าที่กดให้ที่หน้าจอนี้ */
        source: walkIn ? 'staff' : 'scan'
      };
    });

    walkSeq[activityId] = people.filter(function (p) { return p.walkIn; }).length;
    state[activityId] = { people: people, rounds: rounds };
    return state[activityId];
  }

  function personOf(activityId, personId) {
    return boot(activityId).people.filter(function (p) { return p.id === personId; })[0];
  }

  /* ---------------------------------------------------------------
     เวลาของเซิร์ฟเวอร์
     stampFor: เวลาเช็คอินคนถัดไปของรอบนั้น = คนล่าสุดในรอบเดียวกัน + STEP_MIN
     ถ้ายังไม่มีใครเช็คอินเลย ให้เริ่มที่เวลาเริ่มรอบ
     --------------------------------------------------------------- */
  function stampFor(activityId, roundKey) {
    var box = boot(activityId);
    var round = box.rounds.filter(function (r) { return r.key === roundKey; })[0] || box.rounds[0];
    var base = round ? round.startMin : 540;

    var latest = box.people.reduce(function (max, p) {
      if (!p.checkedInAt || p.roundKey !== roundKey) return max;
      return Math.max(max, toMinutes(p.checkedInAt));
    }, -1);

    return toClock(latest < 0 ? base : latest + STEP_MIN);
  }

  /* "ตอนนี้" ของเซิร์ฟเวอร์ = เวลาเช็คอินล่าสุดของทั้งกิจกรรม (จุดที่สายเหตุการณ์เดินมาถึง)
     ถ้ายังไม่มีใครเช็คอินเลย ให้ใช้เวลาเริ่มรอบแรก — ไม่แตะนาฬิกาเครื่องผู้ใช้ */
  function serverNow(activityId) {
    var box = boot(activityId);
    var latest = box.people.reduce(function (max, p) {
      return p.checkedInAt ? Math.max(max, toMinutes(p.checkedInAt)) : max;
    }, -1);
    if (latest < 0) latest = box.rounds.length ? box.rounds[0].startMin : 540;
    return toClock(latest);
  }

  /* เวลาที่ใช้ประทับ audit log — เป็นเวลาของ "เหตุการณ์ในกิจกรรม" ไม่ใช่นาฬิกาเครื่อง
     ต่อ backend จริงแล้วให้ backend เป็นคนใส่ฟิลด์นี้ แล้วลบฟังก์ชันนี้ทิ้ง */
  function serverTimestamp(activityId, clock) {
    var activity = activityOf(activityId);
    var day = (activity && activity.startDate) || '';
    return day ? day + 'T' + clock : clock;
  }

  /* ---------------------------------------------------------------
     ชั้นขนส่งข้อมูล — จุดเดียวที่ต้องแก้เมื่อมี backend จริง
     ทุกฟังก์ชันคืน Promise เสมอ หน้าจอจึงไม่รู้ว่าข้อมูลมาจากไหน
     --------------------------------------------------------------- */
  function csrf() {
    var tag = document.querySelector('meta[name="csrf-token"]');
    return tag ? tag.getAttribute('content') : '';
  }

  /* ข้อความจาก validation ของ Laravel (422) เป็นข้อความที่เขียนให้คนอ่านอยู่แล้ว
     จึงส่งต่อขึ้นหน้าจอตรง ๆ — สถานะอื่นไม่ส่งต่อ เพราะอาจมีรายละเอียดภายในระบบติดมา */
  function fail(res, path) {
    return res.json().then(function (body) {
      var first = body && body.errors && Object.keys(body.errors)[0];
      var message = res.status === 422 ? ((first && body.errors[first][0]) || body.message) : '';
      var error = new Error(message || ('checkin api ' + res.status + ' ' + path));
      error.status = res.status;
      throw error;
    }, function () {
      throw new Error('checkin api ' + res.status + ' ' + path);
    });
  }

  function request(path, options) {
    return fetch(API_BASE + path, Object.assign({
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf()
      },
      credentials: 'same-origin'
    }, options || {})).then(function (res) {
      if (!res.ok) return fail(res, path);
      return res.json();
    });
  }

  function local(value) {
    return new Promise(function (resolve) {
      setTimeout(function () { resolve(value); }, LATENCY);
    });
  }

  /* GET /activities/:id/checkin */
  function snapshot(activityId) {
    if (API_BASE) return request('/activities/' + encodeURIComponent(activityId) + '/checkin');
    var box = boot(activityId);
    return local({
      activityId: activityId,
      rounds: box.rounds.slice(),
      people: box.people.map(function (p) { return Object.assign({}, p); })
    });
  }

  /* POST /activities/:id/checkin  { registrationId, source } */
  function checkIn(activityId, personId, opts) {
    opts = opts || {};
    if (API_BASE) {
      return request('/activities/' + encodeURIComponent(activityId) + '/checkin', {
        method: 'POST',
        body: JSON.stringify({ registrationId: personId, source: opts.source || 'staff' })
      });
    }

    var person = personOf(activityId, personId);
    if (!person) return Promise.reject(new Error('registration not found: ' + personId));
    if (person.checkedInAt) return local(Object.assign({}, person));

    person.checkedInAt = stampFor(activityId, person.roundKey);
    person.source = opts.source || 'staff';
    return local(Object.assign({}, person)).then(function (saved) {
      emit({ type: 'checkin', activityId: activityId, person: saved });
      return saved;
    });
  }

  /* DELETE /activities/:id/checkin/:registrationId
     เซิร์ฟเวอร์ต้องเขียน audit log ในทรานแซกชันเดียวกับการล้างเวลาเช็คอิน
     ถ้าเขียน log ไม่สำเร็จต้องไม่ยกเลิกเช็คอิน — หน้าจอถือว่าคำสั่งล้มเหลว */
  function undoCheckIn(activityId, personId, opts) {
    opts = opts || {};
    if (API_BASE) {
      return request('/activities/' + encodeURIComponent(activityId) + '/checkin/' + encodeURIComponent(personId), {
        method: 'DELETE',
        body: JSON.stringify({ reason: opts.reason || '' })
      });
    }

    var person = personOf(activityId, personId);
    if (!person) return Promise.reject(new Error('registration not found: ' + personId));

    var previous = person.checkedInAt;
    var actor = mock().currentUser || {};
    var entry = {
      id: 'AUD-' + pad3(auditStore().length + 1),
      action: 'checkin.undo',
      activityId: activityId,
      registrationId: personId,
      registrationName: person.name,
      /* previousCheckedInAt = เวลาที่เคยเช็คอิน · at = เวลาที่ "กดยกเลิก" คนละค่ากัน */
      previousCheckedInAt: previous,
      actorUsername: actor.username || '-',
      actorName: actor.name || '-',
      actorRole: actor.role || '-',
      at: serverTimestamp(activityId, serverNow(activityId)),
      reason: opts.reason || ''
    };

    /* ล้างเฉพาะเวลาเช็คอิน — source บอกว่า "คนนี้เข้าระบบมาทางไหน" ไม่ใช่สถานะ จึงคงไว้ */
    person.checkedInAt = '';
    auditStore().push(entry);

    return local({ registrationId: personId, audit: entry }).then(function (res) {
      emit({ type: 'undo', activityId: activityId, personId: personId, audit: entry });
      return res;
    });
  }

  /* POST /activities/:id/walk-ins  { name, phone, roundKey, ageRange, consent }
     เพิ่มแล้วเช็คอินให้ทันทีในคำสั่งเดียว — คนที่ยืนอยู่หน้างานถือว่ามาถึงแล้ว */
  function addWalkIn(activityId, payload) {
    payload = payload || {};
    if (API_BASE) {
      return request('/activities/' + encodeURIComponent(activityId) + '/walk-ins', {
        method: 'POST', body: JSON.stringify(payload)
      });
    }

    var box = boot(activityId);
    var round = box.rounds.filter(function (r) { return r.key === payload.roundKey; })[0] || box.rounds[0];
    var seq = (walkSeq[activityId] || 0) + 1;
    walkSeq[activityId] = seq;

    var person = {
      id: activityId + '-W' + pad3(seq),
      code: 'WLK-' + activityId.slice(-3) + '-' + pad3(seq),
      name: payload.name,
      phone: payload.phone || '',
      ageRange: payload.ageRange || '',
      roundKey: round ? round.key : '',
      round: round ? round.label : 'หน้างาน',
      walkIn: true,
      checkedInAt: stampFor(activityId, round ? round.key : ''),
      source: 'staff'
    };
    box.people.push(person);

    return local(Object.assign({}, person)).then(function (saved) {
      emit({ type: 'walkin', activityId: activityId, person: saved });
      return saved;
    });
  }

  /* GET /activities/:id/checkin/audit — ใช้ตอนทำหน้า "ประวัติการแก้ไขเช็คอิน" */
  function auditStore() {
    var m = mock();
    m.checkinAuditLog = m.checkinAuditLog || [];
    return m.checkinAuditLog;
  }

  function auditLog(activityId) {
    if (API_BASE) return request('/activities/' + encodeURIComponent(activityId) + '/checkin/audit');
    return local(auditStore().filter(function (e) { return e.activityId === activityId; }));
  }

  /* ---------------------------------------------------------------
     Realtime — ผู้เข้าร่วมสแกน QR เองแล้วหน้าจอนี้ต้องขึ้นเองโดยไม่ต้องรีเฟรช
     ลำดับการเลือกช่องทาง: WebSocket -> polling
     ทั้งสองทางส่ง event รูปเดียวกันเข้า handler หน้าจอจึงเขียนโค้ดชุดเดียว
     --------------------------------------------------------------- */
  function emit(event) {
    listeners.forEach(function (l) {
      if (l.activityId === event.activityId) l.handler(event);
    });
  }

  function openSocket(activityId, handler) {
    var socket = new WebSocket(SOCKET_URL + '?activityId=' + encodeURIComponent(activityId));
    socket.addEventListener('open', function () { handler({ type: 'status', activityId: activityId, online: true }); });
    socket.addEventListener('close', function () { handler({ type: 'status', activityId: activityId, online: false }); });
    socket.addEventListener('message', function (e) {
      try { handler(Object.assign({ activityId: activityId }, JSON.parse(e.data))); } catch (err) { /* ข้อความที่อ่านไม่ออกให้ข้าม */ }
    });
    return function () { socket.close(); };
  }

  /* Polling — ดึง snapshot มาเทียบกับรอบก่อนหน้า แล้วยิงเฉพาะส่วนที่ต่างจริง
     ไม่ส่ง snapshot ทั้งก้อนกลับไป เพราะหน้าจอจะ re-render ทั้งหน้าและกระพริบ */
  function startPolling(activityId, handler) {
    var known = null;
    var timer = null;
    var stopped = false;

    function tick() {
      snapshot(activityId).then(function (snap) {
        if (stopped) return;
        var next = {};
        snap.people.forEach(function (p) { next[p.id] = p; });

        if (known) {
          Object.keys(next).forEach(function (id) {
            var before = known[id];
            var after = next[id];
            if (!before) { handler({ type: 'walkin', activityId: activityId, person: after }); return; }
            if (!before.checkedInAt && after.checkedInAt) handler({ type: 'checkin', activityId: activityId, person: after });
            if (before.checkedInAt && !after.checkedInAt) handler({ type: 'undo', activityId: activityId, personId: id });
          });
        }
        known = next;
        handler({ type: 'status', activityId: activityId, online: true });
      })['catch'](function () {
        if (!stopped) handler({ type: 'status', activityId: activityId, online: false });
      });
    }

    tick();
    timer = setInterval(tick, POLL_MS);
    return function () { stopped = true; clearInterval(timer); };
  }

  function subscribe(activityId, handler) {
    /* ช่องทางในเครื่อง: ให้ผลของ checkIn/undo/addWalkIn ที่เกิดบนหน้าจอนี้เด้งกลับเข้า handler ทันที
       โดยไม่ต้องรอรอบ polling ถัดไป */
    var entry = { activityId: activityId, handler: handler };
    listeners.push(entry);

    var stopRemote = SOCKET_URL ? openSocket(activityId, handler) : startPolling(activityId, handler);

    return function () {
      var i = listeners.indexOf(entry);
      if (i > -1) listeners.splice(i, 1);
      stopRemote();
    };
  }

  /* เครื่องจำลอง "ผู้เข้าร่วมสแกน QR เอง" สำหรับต้นแบบที่ยังไม่มี backend
     เขียนสถานะเข้ากล่องข้อมูลตรง ๆ เหมือนคำขอที่มาจากมือถือผู้เข้าร่วม
     รอบ polling ถัดไปจึงเห็นความเปลี่ยนแปลงเอง — เป็นการพิสูจน์ว่าเส้นทาง realtime ทำงานจริง

     ปิดไว้เป็นค่าเริ่มต้น หน้าจอต้องเรียกเองเมื่อเปิดด้วย ?live=demo เท่านั้น
     เพราะถ้าเปิดตลอด คนในรายชื่อจะถูกเช็คอินเองเรื่อย ๆ โดยไม่มีใครกด
     ซึ่งบนหน้าจอแยกไม่ออกจากบั๊ก และทำให้ตัวเลขสรุปเชื่อถือไม่ได้ */
  function simulateScans(activityId, everyMs) {
    if (API_BASE || SOCKET_URL) return function () {};
    var timer = setInterval(function () {
      var box = boot(activityId);
      var waiting = box.people.filter(function (p) { return !p.checkedInAt; });
      if (!waiting.length) return;
      var next = waiting[0];
      next.checkedInAt = stampFor(activityId, next.roundKey);
      next.source = 'scan';
    }, everyMs || 20000);
    return function () { clearInterval(timer); };
  }

  return {
    snapshot: snapshot,
    checkIn: checkIn,
    undoCheckIn: undoCheckIn,
    addWalkIn: addWalkIn,
    auditLog: auditLog,
    subscribe: subscribe,
    simulateScans: simulateScans
  };
})();
