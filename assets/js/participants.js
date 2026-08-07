/* TheFarmConcept — Participant module helpers (โมดูล "ผู้เข้าร่วมทั้งหมด")
   ใช้ร่วมกัน 3 หน้าจอ: pages/participants/list.html, form.html, detail.html
   เพื่อไม่ให้ badge map / การนับสถิติ / การอ่าน Master Data ถูกเขียนซ้ำในแต่ละหน้า

   ไฟล์นี้เป็น helper เฉพาะโมดูล — ไม่แตะ Component กลาง (index-layout.js, action-menu.js, data-service.js)
   โหลดหลัง data-service.js และ mock-data.js เสมอ

   ทุกฟังก์ชันอ่าน Master Data จาก window.TFC_MOCK ชุดเดียวกับโมดูล "พื้นฐาน":
     targetGroups → กลุ่มเป้าหมาย, areas → พื้นที่ดำเนินการ, sampleFollowUpRounds → รอบติดตามกลุ่มตัวอย่าง
   จึงไม่มีการ hardcode รายการเหล่านี้ในหน้าจอใด ๆ ของโมดูลนี้ */
window.TFC = window.TFC || {};

(function () {
  var mock = function () { return window.TFC_MOCK || {}; };

  var TYPE_BADGE = { sample: 'badge-primary', general: 'badge-neutral' };
  var TARGET_GROUP_BADGE = {
    'กลุ่มเด็กและเยาวชน': 'badge-info',
    'กลุ่มวัยทำงาน': 'badge-primary',
    'กลุ่มผู้สูงอายุ': 'badge-warning'
  };
  var PROJECT_STATUS_BADGE = {
    'เข้าร่วม': 'badge-success',
    'ถอนตัว': 'badge-warning',
    'ติดตามไม่ได้': 'badge-neutral',
    'เสียชีวิต': 'badge-danger'
  };
  var CONSENT_BADGE = {
    'ยินยอม': 'badge-success',
    'ไม่ยินยอม': 'badge-danger',
    'รอยืนยัน': 'badge-warning',
    'ขอถอนความยินยอม': 'badge-neutral'
  };
  var PURCHASE_BADGE = {
    'สำเร็จ': 'badge-success',
    'รอดำเนินการ': 'badge-warning',
    'ยกเลิก': 'badge-danger'
  };

  function escape(value) {
    return window.TFC.escapeHtml(value == null ? '' : value);
  }

  function badge(map, value, fallbackLabel) {
    var text = value || fallbackLabel || '-';
    return '<span class="badge ' + (map[value] || 'badge-neutral') + '">' + escape(text) + '</span>';
  }

  window.TFC.participants = {
    /* ---------- Data access (ผ่าน Data Service กลาง — พร้อมสลับเป็น API จริงในอนาคต) ---------- */
    service: function () {
      return window.TFC.dataService('participants');
    },

    all: function () {
      return mock().participants || [];
    },

    find: function (id) {
      return this.all().filter(function (p) { return p.id === id; })[0] || null;
    },

    /* อ่าน id จาก query string (?id=PTP-0001) — ถ้าไม่ระบุใช้รายการแรกเพื่อให้เปิดหน้าตรง ๆ ได้ในเฟส Prototype */
    idFromQuery: function () {
      var id = new URLSearchParams(window.location.search).get('id');
      if (id) return id;
      var first = this.all()[0];
      return first ? first.id : '';
    },

    /* ---------- Master Data ร่วมกับโมดูล "พื้นฐาน" ---------- */
    targetGroupNames: function () {
      return (mock().targetGroups || []).map(function (g) { return g.name; });
    },

    areaNames: function () {
      return (mock().areas || []).map(function (a) { return a.name; });
    },

    dataSources: function () {
      return mock().activityDataSources || [];
    },

    /* รอบติดตามกลุ่มตัวอย่าง — ชุดเดียวกับเมนู "รอบติดตามกลุ่มตัวอย่าง" ในโมดูลพื้นฐาน
       แก้ไข/เพิ่ม/ลบรอบที่หน้านั้น แล้วฟอร์มและแท็บประเมินสุขภาพจะสะท้อนตามทันที */
    rounds: function () {
      return mock().sampleFollowUpRounds || [];
    },

    roundName: function (roundId) {
      var round = this.rounds().filter(function (r) { return r.id === roundId; })[0];
      return round ? round.name : (roundId || '-');
    },

    /* ---------- ประวัติย่อยรายบุคคล ---------- */
    activityHistory: function (participantId) {
      return (mock().participantActivityHistory || {})[participantId] || [];
    },

    healthEvaluations: function (participantId) {
      return (mock().participantHealthEvaluations || {})[participantId] || [];
    },

    purchases: function (participantId) {
      return (mock().participantPurchases || {})[participantId] || [];
    },

    /* ตัวเลขทั้ง 4 คอลัมน์ในหน้า Index คำนวณจากประวัติจริง ไม่เก็บเป็น field ซ้ำในตัว participant */
    counts: function (participantId) {
      var activities = this.activityHistory(participantId);
      return {
        activities: activities.length,
        evaluations: activities.filter(function (a) { return a.evaluated; }).length,
        healthEvaluations: this.healthEvaluations(participantId).length,
        purchases: this.purchases(participantId).length
      };
    },

    /* ---------- Label / Badge ---------- */
    typeLabel: function (type) {
      var item = (mock().participantTypes || []).filter(function (t) { return t.value === type; })[0];
      return item ? item.label : '-';
    },

    typeBadge: function (type) {
      return '<span class="badge ' + (TYPE_BADGE[type] || 'badge-neutral') + '">' + escape(this.typeLabel(type)) + '</span>';
    },

    targetGroupBadge: function (name) { return badge(TARGET_GROUP_BADGE, name); },
    projectStatusBadge: function (value) { return badge(PROJECT_STATUS_BADGE, value); },
    consentStatusBadge: function (value) { return badge(CONSENT_BADGE, value); },
    purchaseStatusBadge: function (value) { return badge(PURCHASE_BADGE, value); },

    /* ---------- Form helpers ---------- */
    optionsHtml: function (list, selected, placeholder) {
      var head = placeholder ? '<option value="">' + escape(placeholder) + '</option>' : '';
      return head + (list || []).map(function (item) {
        var value = typeof item === 'object' ? item.value : item;
        var label = typeof item === 'object' ? item.label : item;
        var isSelected = selected != null && String(selected) === String(value) ? ' selected' : '';
        return '<option value="' + escape(value) + '"' + isSelected + '>' + escape(label) + '</option>';
      }).join('');
    },

    /* รหัสบุคคลรันออโต้: TFC-<ปี พ.ศ. 2 หลัก>-<running 4 หลัก> — เลือกเลขถัดจากรหัสที่มีอยู่แล้ว */
    nextPersonCode: function () {
      var yearSuffix = String(new Date().getFullYear() + 543).slice(-2);
      var prefix = 'TFC-' + yearSuffix + '-';
      var maxRunning = this.all().reduce(function (max, p) {
        var code = p.personCode || '';
        if (code.indexOf(prefix) !== 0) return max;
        var running = parseInt(code.slice(prefix.length), 10);
        return isNaN(running) ? max : Math.max(max, running);
      }, 0);
      return prefix + String(maxRunning + 1).padStart(4, '0');
    },

    /* Validate "ห้ามซ้ำ" — ยกเว้นตัวเองตอนแก้ไข */
    isPersonCodeTaken: function (code, exceptId) {
      var target = String(code || '').trim().toLowerCase();
      if (!target) return false;
      return this.all().some(function (p) {
        return p.id !== exceptId && String(p.personCode || '').trim().toLowerCase() === target;
      });
    },

    /* id ของรายการย่อย (ประวัติกิจกรรม/ประเมิน/สั่งซื้อ) ในเฟส Mock */
    nextChildId: function (prefix) {
      return prefix + '-' + Date.now().toString(36).toUpperCase();
    }
  };
})();
