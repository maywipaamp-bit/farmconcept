/* TheFarmConcept — ข้อมูลตัวอย่างของหน้าแดชบอร์ดหลัก (admin/dashboard.html)

   TODO: เชื่อม API จริง — ไฟล์นี้ทั้งไฟล์คือตัวแทนของ response จาก endpoint เดียว
   GET /dashboard/overview  ->  { generated_at, kpis, registration_trend,
                                  target_groups, action_items, upcoming_activities }

   กติกาที่ต้องรักษาไว้เวลาเปลี่ยนไปใช้ API จริง
   - ชื่อฟิลด์ในไฟล์นี้ตั้งเป็นภาษาอังกฤษให้ตรงกับที่ backend จะส่งมา
     ฝั่ง UI (dashboard-overview.js) อ่านจากชื่อเหล่านี้เท่านั้น จึงไม่ต้องแก้ UI ตอนสลับ
   - ตัวเลขสรุปทุกตัวมาจากเซิร์ฟเวอร์ ห้ามให้หน้าจอคำนวณเอง ยกเว้นสิ่งที่ derive ได้ตรง ๆ
     จากชุดข้อมูลเดียวกัน (เปอร์เซ็นต์ของ donut · เดือนที่มากที่สุด · % ที่นั่งเต็ม)
   - แก้จุดดึงข้อมูลที่ getDashboardOverview() ที่เดียว หน้า UI ไม่ต้องแก้
*/
window.TFC = window.TFC || {};

(function () {

  /* วันที่อ้างอิงของระบบตัวอย่าง — ชุดข้อมูลทั้งระบบอยู่ในช่วงปี 2026
     ถ้าใช้นาฬิกาเครื่องผู้ใช้ ตัวเลข "เดือนนี้" กับกิจกรรมที่จะถึงจะไม่สอดคล้องกัน */
  var GENERATED_AT = '2026-08-10T09:15:00';

  /* ---------- ข้อมูลชุดปกติ ---------- */
  var dashboardOverview = {
    generated_at: GENERATED_AT,

    /* change_pct: บวก = ดีขึ้น · ลบ = ลดลง · 0 = เท่าเดือนก่อน
       target_path เป็นที่อยู่ของหน้าที่คลิกแล้วไปดูรายละเอียด */
    kpis: [
      { key: 'participants',     label: 'ผู้เข้าร่วมทั้งหมด',        value: 1426, unit: 'คน',      change_pct: 12,  target_path: 'activities/registrants.html' },
      { key: 'open_activities',  label: 'กิจกรรมที่เปิดรับสมัคร',    value: 8,    unit: 'กิจกรรม', change_pct: 0,   target_path: 'activities/list.html' },
      { key: 'evaluations',      label: 'แบบประเมินที่ตอบเดือนนี้',  value: 342,  unit: 'ชุด',     change_pct: -6,  target_path: 'evaluations/list.html' },
      { key: 'cohort_following', label: 'กลุ่มตัวอย่างที่ติดตามผล',  value: 120,  unit: 'คน',      change_pct: 4,   target_path: 'cohort/list.html' }
    ],

    /* ย้อนหลัง 12 เดือน เรียงจากเก่าไปใหม่ — UI หาเดือนที่มากที่สุดเอง ไม่ต้องส่ง flag มา */
    registration_trend: [
      { month: '2025-09', label: 'ก.ย.', registered: 96 },
      { month: '2025-10', label: 'ต.ค.', registered: 112 },
      { month: '2025-11', label: 'พ.ย.', registered: 104 },
      { month: '2025-12', label: 'ธ.ค.', registered: 88 },
      { month: '2026-01', label: 'ม.ค.', registered: 121 },
      { month: '2026-02', label: 'ก.พ.', registered: 134 },
      { month: '2026-03', label: 'มี.ค.', registered: 127 },
      { month: '2026-04', label: 'เม.ย.', registered: 96 },
      { month: '2026-05', label: 'พ.ค.', registered: 152 },
      { month: '2026-06', label: 'มิ.ย.', registered: 165 },
      { month: '2026-07', label: 'ก.ค.', registered: 188 },
      { month: '2026-08', label: 'ส.ค.', registered: 143 }
    ],

    /* ส่งมาเป็นจำนวนคน ไม่ใช่เปอร์เซ็นต์ — UI คำนวณ % เอง สัดส่วนจึงตรงกับตัวเลขเสมอ */
    target_groups: [
      { key: 'youth',      label: 'เด็กและเยาวชน',  count: 96 },
      { key: 'working',    label: 'วัยทำงาน',       count: 168 },
      { key: 'elder',      label: 'ผู้สูงอายุ',      count: 120 },
      { key: 'vulnerable', label: 'กลุ่มเปราะบาง',  count: 96 }
    ],

    /* severity มี 3 ระดับตามสีเชิงความหมายของระบบ: urgent(ส้ม) · attention(เขียว) · info(เทา) */
    action_items: [
      { key: 'followup_overdue', label: 'ติดตามผล 3 เดือนเกินกำหนด', detail: 'ผู้สูงอายุ 12 คน · เกินมาแล้ว 5 วัน', severity: 'urgent',    target_path: 'cohort/list.html' },
      { key: 'checkin_pending',  label: 'ยังไม่ปิดรอบเช็คอิน',       detail: 'Workshop อาหารสุขภาพจากสวน รอบที่ 2', severity: 'urgent',    target_path: 'activities/checkin.html' },
      { key: 'eval_waiting',     label: 'แบบประเมินรอผู้เข้าร่วมตอบ', detail: '34 คน ยังไม่ตอบแบบประเมินหลังกิจกรรม', severity: 'attention', target_path: 'evaluations/list.html' },
      { key: 'activity_draft',   label: 'กิจกรรมค้างเป็นฉบับร่าง',   detail: '2 กิจกรรม ยังไม่ได้เผยแพร่',           severity: 'attention', target_path: 'activities/list.html' },
      { key: 'form_draft',       label: 'แบบประเมินฉบับร่าง',        detail: '1 ชุด รอเปิดใช้งาน',                  severity: 'info',      target_path: 'evaluations/list.html' }
    ],

    /* เรียงตามวันที่จัดจากใกล้ไปไกล — UI ไม่เรียงซ้ำ */
    upcoming_activities: [
      { id: 'ACT-2026-021', name: 'ปลูกผักปลอดสารสำหรับครอบครัว', start_date: '2026-08-14', registered: 38, capacity: 40, area: 'The Farm Concept' },
      { id: 'ACT-2026-022', name: 'อบรมแกนนำสุขภาพชุมชน',         start_date: '2026-08-18', registered: 25, capacity: 25, area: 'ชุมชนพูนทรัพย์' },
      { id: 'ACT-2026-023', name: 'ตลาดนัดผักปลอดสารประจำเดือน',  start_date: '2026-08-22', registered: 42, capacity: 80, area: 'The Farm Concept' },
      { id: 'ACT-2026-024', name: 'ดูแลสุขภาพผู้สูงอายุ',          start_date: '2026-08-27', registered: 18, capacity: 30, area: 'ชุมชนตึกร้าง' },
      { id: 'ACT-2026-025', name: 'ทำปุ๋ยหมักจากเศษอาหาร',        start_date: '2026-09-03', registered: 9,  capacity: 35, area: 'ชุมชนพูนทรัพย์' }
    ]
  };

  /* ---------- ข้อมูลชุดว่าง ----------
     ใช้ตอนระบบยังไม่มีข้อมูล (เพิ่งติดตั้ง / พื้นที่ใหม่) และใช้ทดสอบ Empty State
     โครงเหมือนกันทุกฟิลด์ ต่างแค่ array ว่าง — UI จึงไม่ต้องเช็ก null ที่ไหนเลย */
  var emptyDashboardOverview = {
    generated_at: GENERATED_AT,
    kpis: [],
    registration_trend: [],
    target_groups: [],
    action_items: [],
    upcoming_activities: []
  };

  /* ---------- จุดดึงข้อมูล ----------
     TODO: เชื่อม API จริง — เปลี่ยนเป็น
       return fetch(API_BASE + '/dashboard/overview').then(function (r) { return r.json(); });
     แล้วหน้า UI ไม่ต้องแก้ เพราะรับเป็น Promise อยู่แล้ว

     mode: 'normal' (ค่าตั้งต้น) | 'empty' — mode ใช้เฉพาะตอนทดสอบ ไม่มีปุ่มสลับใน UI */
  function getDashboardOverview(mode) {
    var data = mode === 'empty' ? emptyDashboardOverview : dashboardOverview;
    return Promise.resolve(JSON.parse(JSON.stringify(data)));
  }

  window.TFC.dashboardOverview = dashboardOverview;
  window.TFC.emptyDashboardOverview = emptyDashboardOverview;
  window.TFC.getDashboardOverview = getDashboardOverview;
})();
