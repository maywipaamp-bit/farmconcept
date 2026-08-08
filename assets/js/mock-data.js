/* TheFarmConcept — Central Mock Data (ข้อมูลจำลองภาษาไทย ไม่มีการเชื่อมต่อฐานข้อมูลจริง) */
window.TFC_MOCK = {
  currentUser: {
    name: 'สุนิสา แก้วมณี',
    role: 'เจ้าหน้าที่โครงการ',
    roleCode: 'staff',
    initials: 'สม'
  },

  notifications: [
    { title: 'มีผู้ลงทะเบียนใหม่', detail: 'กิจกรรมปลูกผักปลอดสารสำหรับครอบครัว รอบที่ 2', time: '10 นาทีที่แล้ว', type: 'info' },
    { title: 'รอตรวจสอบสลิปการชำระเงิน', detail: 'Workshop อาหารสุขภาพจากสวน มีสลิปรอตรวจ 5 รายการ', time: '1 ชั่วโมงที่แล้ว', type: 'warning' },
    { title: 'ถึงกำหนดติดตามผล 3 เดือน', detail: 'ผู้เข้าร่วมกลุ่มผู้สูงอายุ 12 คน ครบกำหนดติดตามแล้ว', time: 'เมื่อวาน', type: 'danger' }
  ],

  /* หมายเหตุ: ฟิลด์ชุดเดิม (area/program/time/instructor/...) คงไว้ทั้งหมดเพื่อไม่ให้หน้าจออื่นที่อ่านอยู่พัง
     ฟิลด์ที่เพิ่มใหม่ (type/participantType/format/course/targetGroups/areaList/instructorList/hasFee/
     dataSource/coverImage/publishXxx/visibility/isFeatured/evaluationFormIds/checkinXxx) เป็นส่วนขยายของ
     Data Model โมดูลจัดการกิจกรรม — ดู docs/activity-module.md
     (เขียน publishXxx/checkinXxx แทน publish*, checkin* เพราะเครื่องหมาย * ติดกับ / จะปิดคอมเมนต์กลางคัน) */
  activities: [
    {
      id: 'ACT-2026-014',
      name: 'ปลูกผักปลอดสารสำหรับครอบครัว',
      area: 'ชุมชนพูนทรัพย์ เขตสายไหม',
      areaList: ['ชุมชนพูนทรัพย์ เขตสายไหม'],
      program: 'โครงการฟื้นฟูสุขภาวะชุมชน',
      course: 'หลักสูตรปลูกผักปลอดสารพิษ',
      type: 'กิจกรรม',
      participantType: 'กลุ่มตัวอย่าง',
      format: 'Workshop ลงมือปฏิบัติ',
      dataSource: 'ลงทะเบียนออนไลน์',
      targetGroups: ['กลุ่มวัยทำงาน'],
      startDate: '2026-08-10',
      endDate: '2026-08-10',
      time: '09:00 - 12:00',
      capacity: 40,
      registered: 32,
      status: 'เปิดรับสมัคร',
      hasFee: false,
      fee: 0,
      organizer: 'The Farm Concept ร่วมกับสำนักงานเขตสายไหม',
      instructor: 'อาจารย์สมพงษ์ ปลูกดี',
      instructorList: ['อาจารย์สมพงษ์ ปลูกดี'],
      coverImage: '',
      evaluationFormIds: ['EVL-001'],
      checkinStart: '2026-08-10T08:00',
      checkinEnd: '2026-08-10T18:00',
      isPublished: true,
      publishStart: '2026-07-20T09:00',
      publishEnd: '2026-08-09T23:59',
      visibility: 'สาธารณะ',
      isFeatured: true,
      tags: ['WORKSHOP', 'CRAFT'],
      updatedBy: 'ผู้ดูแลระบบ แอม',
      updatedAt: '2026-08-08T12:00',
      description: 'กิจกรรมเรียนรู้การปลูกผักปลอดสารพิษ เหมาะสำหรับครอบครัวที่ต้องการเริ่มต้นปลูกผักไว้รับประทานเอง ผู้เข้าร่วมจะได้ลงมือปฏิบัติจริงตั้งแต่การเตรียมดิน เพาะกล้า จนถึงการดูแลรักษา โดยวิทยากรผู้เชี่ยวชาญจาก The Farm Concept'
    },
    {
      id: 'ACT-2026-015',
      name: 'Workshop อาหารสุขภาพจากสวน',
      area: 'ศูนย์การเรียนรู้ The Farm Concept',
      areaList: ['ศูนย์การเรียนรู้ The Farm Concept'],
      program: 'โครงการฟื้นฟูสุขภาวะชุมชน',
      course: 'หลักสูตรโภชนาการเบื้องต้น',
      type: 'กิจกรรม',
      participantType: 'ทั่วไป',
      format: 'Workshop ลงมือปฏิบัติ',
      dataSource: 'ลงทะเบียนออนไลน์',
      targetGroups: ['กลุ่มวัยทำงาน', 'กลุ่มผู้สูงอายุ'],
      startDate: '2026-08-17',
      endDate: '2026-08-17',
      time: '09:00 - 15:00',
      capacity: 30,
      registered: 30,
      status: 'เต็มแล้ว',
      hasFee: true,
      fee: 200,
      organizer: 'The Farm Concept',
      instructor: 'คุณนภา ทำอาหารเพื่อสุขภาพ',
      instructorList: ['คุณนภา ทำอาหารเพื่อสุขภาพ'],
      coverImage: '',
      evaluationFormIds: ['EVL-002'],
      checkinStart: '2026-08-17T08:00',
      checkinEnd: '2026-08-17T18:00',
      isPublished: true,
      publishStart: '2026-07-25T09:00',
      publishEnd: '2026-08-16T23:59',
      visibility: 'สาธารณะ',
      isFeatured: false,
      tags: ['FOOD', 'WORKSHOP'],
      updatedBy: 'วีระ ศรีสมบัติ',
      updatedAt: '2026-08-06T09:35',
      description: 'เรียนรู้การนำผักและสมุนไพรจากสวนมาปรุงเป็นเมนูอาหารเพื่อสุขภาพ พร้อมความรู้ด้านโภชนาการที่เหมาะกับทุกวัย ลงมือทำจริงและได้ชิมเมนูที่ปรุงเองในวันงาน'
    },
    {
      id: 'ACT-2026-016',
      name: 'เรียนรู้การทำปุ๋ยหมัก',
      area: 'ชุมชนหนองแขม',
      areaList: ['ชุมชนหนองแขม'],
      program: 'โครงการเกษตรเพื่อสุขภาพ',
      course: 'หลักสูตรทำปุ๋ยหมักอินทรีย์',
      type: 'กิจกรรม',
      participantType: 'ทั่วไป',
      format: 'บรรยาย/อบรม',
      dataSource: 'ลงทะเบียนหน้างาน',
      targetGroups: ['กลุ่มวัยทำงาน'],
      startDate: '2026-08-24',
      endDate: '2026-09-07',
      time: '09:00 - 12:00',
      capacity: 25,
      registered: 9,
      status: 'เปิดรับสมัคร',
      hasFee: false,
      fee: 0,
      organizer: 'The Farm Concept ร่วมกับชุมชนหนองแขม',
      instructor: 'อาจารย์สมพงษ์ ปลูกดี',
      instructorList: ['อาจารย์สมพงษ์ ปลูกดี'],
      coverImage: '',
      evaluationFormIds: ['EVL-004'],
      checkinStart: '2026-08-24T08:00',
      checkinEnd: '2026-09-07T18:00',
      isPublished: true,
      publishStart: '2026-08-01T09:00',
      publishEnd: '2026-08-23T23:59',
      visibility: 'เฉพาะกลุ่มเป้าหมาย',
      isFeatured: false,
      tags: ['CRAFT', 'WORKSHOP'],
      updatedBy: 'ปิยะดา รุ่งเรือง',
      updatedAt: '2026-08-05T16:20',
      description: 'อบรมเชิงปฏิบัติการทำปุ๋ยหมักจากเศษอาหารและวัสดุเหลือใช้ในครัวเรือน ลดขยะ เพิ่มความอุดมสมบูรณ์ให้ดิน เหมาะสำหรับผู้เริ่มต้นปลูกผักที่บ้าน'
    },
    {
      id: 'ACT-2026-017',
      name: 'กิจกรรมฟื้นฟูสุขภาวะชุมชน',
      area: 'ชุมชนพูนทรัพย์ เขตสายไหม',
      areaList: ['ชุมชนพูนทรัพย์ เขตสายไหม', 'ชุมชนบางบัว'],
      program: 'โครงการฟื้นฟูสุขภาวะชุมชน',
      course: 'หลักสูตรออกกำลังกายเพื่อสุขภาพ',
      type: 'อีเวนท์',
      participantType: 'กลุ่มตัวอย่าง',
      format: 'ตลาดนัด/กิจกรรมเปิด',
      dataSource: 'นำเข้าจากไฟล์',
      targetGroups: ['กลุ่มผู้สูงอายุ', 'กลุ่มวัยทำงาน'],
      startDate: '2026-07-20',
      endDate: '2026-07-20',
      time: '09:00 - 16:00',
      capacity: 50,
      registered: 47,
      status: 'ดำเนินการเสร็จสิ้น',
      hasFee: false,
      fee: 0,
      organizer: 'The Farm Concept',
      instructor: 'ทีมงาน The Farm Concept',
      instructorList: ['ทีมงาน The Farm Concept'],
      coverImage: '',
      evaluationFormIds: ['EVL-003'],
      checkinStart: '2026-07-20T08:00',
      checkinEnd: '2026-07-20T18:00',
      isPublished: true,
      publishStart: '2026-06-25T09:00',
      publishEnd: '2026-07-19T23:59',
      visibility: 'สาธารณะ',
      isFeatured: false,
      tags: ['MIND', 'WORKSHOP'],
      updatedBy: 'สุนิสา แก้วมณี',
      updatedAt: '2026-07-21T10:05',
      description: 'กิจกรรมรวมฐานการเรียนรู้ด้านสุขภาวะ ทั้งการออกกำลังกาย โภชนาการ และการปลูกผักสวนครัว สำหรับทุกกลุ่มวัยในชุมชน'
    },
    {
      id: 'ACT-2026-018',
      name: 'ตลาดนัดผักปลอดสารประจำเดือน',
      area: 'ชุมชนบางบัว',
      areaList: ['ชุมชนบางบัว'],
      program: 'โครงการเกษตรเพื่อสุขภาพ',
      course: '',
      type: 'อีเวนท์',
      participantType: 'ทั่วไป',
      format: 'ตลาดนัด/กิจกรรมเปิด',
      dataSource: 'บันทึกโดยเจ้าหน้าที่',
      targetGroups: ['กลุ่มเด็กและเยาวชน', 'กลุ่มวัยทำงาน', 'กลุ่มผู้สูงอายุ'],
      startDate: '2026-09-05',
      endDate: '2026-09-05',
      time: '08:00 - 12:00',
      capacity: 60,
      registered: 4,
      status: 'ฉบับร่าง',
      hasFee: false,
      fee: 0,
      organizer: 'The Farm Concept ร่วมกับชุมชนบางบัว',
      instructor: 'ทีมเกษตรกรอินทรีย์',
      instructorList: ['ทีมเกษตรกรอินทรีย์'],
      coverImage: '',
      evaluationFormIds: [],
      checkinStart: '',
      checkinEnd: '',
      isPublished: false,
      publishStart: '',
      publishEnd: '',
      visibility: 'สาธารณะ',
      isFeatured: false,
      tags: ['FOOD'],
      updatedBy: 'อรุณี ทองสุข',
      updatedAt: '2026-08-07T14:48',
      description: 'ตลาดนัดจำหน่ายผักปลอดสารพิษจากเกษตรกรในเครือข่ายชุมชน พบปะพูดคุยแลกเปลี่ยนความรู้การปลูกผักกับเกษตรกรตัวจริง'
    }
  ],

  /* ---------- Master list ของโมดูลจัดการกิจกรรม ---------- */
  activityTypes: ['กิจกรรม', 'อีเวนท์'],
  activityParticipantTypes: ['กลุ่มตัวอย่าง', 'ทั่วไป'],
  activityDataSources: ['ลงทะเบียนออนไลน์', 'ลงทะเบียนหน้างาน', 'นำเข้าจากไฟล์', 'บันทึกโดยเจ้าหน้าที่'],
  activityVisibilityLevels: ['สาธารณะ', 'เฉพาะกลุ่มเป้าหมาย', 'เฉพาะผู้มีลิงก์'],

  /* สถานะกิจกรรม + สี badge — 4 ค่าแรกคือค่าที่ข้อมูลเดิมใช้อยู่ อีก 3 ค่าเพิ่มตามสเปกใหม่ (รอยืนยัน state machine) */
  activityStatuses: [
    { value: 'ฉบับร่าง', badge: 'badge-neutral' },
    { value: 'เปิดรับสมัคร', badge: 'badge-success' },
    { value: 'ปิดรับสมัคร', badge: 'badge-warning' },
    { value: 'เต็มแล้ว', badge: 'badge-info' },
    { value: 'กำลังดำเนินการ', badge: 'badge-primary' },
    { value: 'ดำเนินการเสร็จสิ้น', badge: 'badge-neutral' },
    { value: 'ยกเลิก', badge: 'badge-danger' }
  ],

  paymentStatuses: [
    { value: 'ชำระแล้ว', badge: 'badge-success' },
    { value: 'รอตรวจสอบ', badge: 'badge-warning' },
    { value: 'ยังไม่ชำระ', badge: 'badge-neutral' },
    { value: 'ปฏิเสธ', badge: 'badge-danger' }
  ],

  checkinStatuses: [
    { value: 'เข้าร่วมแล้ว', badge: 'badge-success' },
    { value: 'ยังไม่เข้าร่วม', badge: 'badge-neutral' },
    { value: 'ไม่ได้เข้าร่วม', badge: 'badge-danger' }
  ],

  /* ตัวเลือกข้อมูลผู้ลงทะเบียน (ใช้ทั้งฟอร์มบันทึกหลังบ้านและกราฟรายงาน) */
  registrationOptions: {
    genders: ['หญิง', 'ชาย', 'อื่นๆ'],
    ageRanges: ['ต่ำกว่า 18 ปี', '18-29 ปี', '30-44 ปี', '45-59 ปี', '60 ปีขึ้นไป'],
    occupations: ['รับราชการ', 'พนักงานบริษัท', 'ธุรกิจส่วนตัว', 'เกษตรกร', 'นักเรียน/นักศึกษา', 'แม่บ้าน', 'เกษียณอายุ'],
    sourceChannels: ['Facebook', 'LINE OA', 'เว็บไซต์', 'เพื่อนแนะนำ', 'ผู้นำชุมชน', 'สื่อสิ่งพิมพ์'],
    interests: ['ปลูกผักปลอดสาร', 'ทำปุ๋ยหมัก', 'อาหารเพื่อสุขภาพ', 'สมุนไพรพื้นบ้าน', 'ออกกำลังกาย', 'สุขภาพจิต', 'เกษตรอินทรีย์']
  },

  /* ระดับความพึงพอใจ: mapping จากคะแนนเฉลี่ย (ใช้ร่วมกันทั้งตารางและกราฟ) */
  satisfactionLevels: [
    { value: 'พึงพอใจมากที่สุด', min: 4.5, badge: 'badge-success' },
    { value: 'พึงพอใจมาก', min: 3.5, badge: 'badge-primary' },
    { value: 'พึงพอใจปานกลาง', min: 2.5, badge: 'badge-info' },
    { value: 'พึงพอใจน้อย', min: 1.5, badge: 'badge-warning' },
    { value: 'พึงพอใจน้อยที่สุด', min: 0, badge: 'badge-danger' }
  ],

  /* หัวข้อประเมิน (รายหัวข้อ) และการจัดกลุ่มเป็นด้าน (รายด้าน — ใช้กับ Radar Chart) */
  evaluationTopics: [
    { key: 'content', label: 'เนื้อหากิจกรรม', dimension: 'ด้านเนื้อหา' },
    { key: 'apply', label: 'การนำไปใช้ได้จริง', dimension: 'ด้านเนื้อหา' },
    { key: 'speaker', label: 'ความสามารถของวิทยากร', dimension: 'ด้านวิทยากร' },
    { key: 'material', label: 'สื่อและอุปกรณ์', dimension: 'ด้านสื่อและสถานที่' },
    { key: 'venue', label: 'สถานที่และสิ่งอำนวยความสะดวก', dimension: 'ด้านสื่อและสถานที่' },
    { key: 'service', label: 'การให้บริการของเจ้าหน้าที่', dimension: 'ด้านการให้บริการ' }
  ],

  areas: [
    {
      id: 'AREA-001', name: 'ชุมชนพูนทรัพย์ เขตสายไหม', province: 'กรุงเทพมหานคร', district: 'เขตสายไหม',
      areaType: 'ชุมชนเมือง', areaGroup: 'กลุ่มกรุงเทพและปริมณฑล',
      startDate: '2025-01-15', endDate: '', partnerOrg: 'สำนักงานเขตสายไหม',
      coordinator: 'อรุณี ทองสุข', coordinatorPhone: '081-111-2222', coordinatorPosition: 'ผู้ประสานงานชุมชน',
      mapUrl: 'https://maps.google.com/?q=ชุมชนพูนทรัพย์+เขตสายไหม', status: 'ดำเนินการอยู่',
      activityCount: 6, totalParticipants: 172, avgSatisfaction: 4.6,
      updatedAt: '2026-08-01', updatedBy: 'สุนิสา แก้วมณี'
    },
    {
      id: 'AREA-002', name: 'ศูนย์การเรียนรู้ The Farm Concept', province: 'ปทุมธานี', district: 'อำเภอเมืองปทุมธานี',
      areaType: 'ศูนย์การเรียนรู้', areaGroup: 'กลุ่มกรุงเทพและปริมณฑล',
      startDate: '2024-06-01', endDate: '', partnerOrg: '',
      coordinator: 'วีระ ศรีสมบัติ', coordinatorPhone: '082-222-3333', coordinatorPosition: 'หัวหน้าศูนย์การเรียนรู้',
      mapUrl: 'https://maps.google.com/?q=The+Farm+Concept+ปทุมธานี', status: 'ดำเนินการอยู่',
      activityCount: 4, totalParticipants: 98, avgSatisfaction: 4.7,
      updatedAt: '2026-07-28', updatedBy: 'สุนิสา แก้วมณี'
    },
    {
      id: 'AREA-003', name: 'ชุมชนหนองแขม', province: 'กรุงเทพมหานคร', district: 'เขตหนองแขม',
      areaType: 'ชุมชนเมือง', areaGroup: 'กลุ่มกรุงเทพและปริมณฑล',
      startDate: '2025-03-10', endDate: '', partnerOrg: 'สำนักงานเขตหนองแขม',
      coordinator: 'ปิยะดา รุ่งเรือง', coordinatorPhone: '083-333-4444', coordinatorPosition: 'ผู้ประสานงานชุมชน',
      mapUrl: 'https://maps.google.com/?q=ชุมชนหนองแขม', status: 'ดำเนินการอยู่',
      activityCount: 3, totalParticipants: 41, avgSatisfaction: 4.3,
      updatedAt: '2026-07-15', updatedBy: 'วีระ ศรีสมบัติ'
    },
    {
      id: 'AREA-004', name: 'ชุมชนบางบัว', province: 'กรุงเทพมหานคร', district: 'เขตบางเขน',
      areaType: 'ชุมชนเมือง', areaGroup: 'กลุ่มกรุงเทพและปริมณฑล',
      startDate: '2025-09-01', endDate: '', partnerOrg: '',
      coordinator: 'ธนากร ใจดี', coordinatorPhone: '084-444-5555', coordinatorPosition: 'ผู้ประสานงานชุมชน',
      mapUrl: 'https://maps.google.com/?q=ชุมชนบางบัว', status: 'ระงับชั่วคราว',
      activityCount: 2, totalParticipants: 26, avgSatisfaction: 4.1,
      updatedAt: '2026-06-30', updatedBy: 'สุนิสา แก้วมณี'
    }
  ],

  areaTypes: ['ชุมชนเมือง', 'ชุมชนชนบท', 'ศูนย์การเรียนรู้', 'สถานศึกษา', 'หน่วยงานราชการ', 'อื่นๆ'],
  areaGroups: ['กลุ่มกรุงเทพและปริมณฑล', 'กลุ่มภาคกลาง', 'กลุ่มภาคเหนือ', 'กลุ่มภาคตะวันออกเฉียงเหนือ', 'กลุ่มภาคใต้'],
  areaStatuses: ['ดำเนินการอยู่', 'ระงับชั่วคราว', 'สิ้นสุดแล้ว'],

  provinceDistricts: {
    'กรุงเทพมหานคร': ['เขตสายไหม', 'เขตหนองแขม', 'เขตบางเขน', 'เขตบางบัว', 'เขตดอนเมือง', 'เขตจตุจักร'],
    'ปทุมธานี': ['อำเภอเมืองปทุมธานี', 'อำเภอคลองหลวง', 'อำเภอลำลูกกา', 'อำเภอธัญบุรี'],
    'นนทบุรี': ['อำเภอเมืองนนทบุรี', 'อำเภอปากเกร็ด', 'อำเภอบางบัวทอง'],
    'สมุทรปราการ': ['อำเภอเมืองสมุทรปราการ', 'อำเภอบางพลี', 'อำเภอบางบ่อ']
  },

  targetGroups: [
    { id: 'TG-001', name: 'กลุ่มเด็กและเยาวชน', ageRange: '6-18 ปี', memberCount: 84, avgScoreChange: 0.6, active: true, updatedAt: '2026-07-20', updatedBy: 'สุนิสา แก้วมณี' },
    { id: 'TG-002', name: 'กลุ่มวัยทำงาน', ageRange: '19-59 ปี', memberCount: 156, avgScoreChange: 0.9, active: true, updatedAt: '2026-07-22', updatedBy: 'สุนิสา แก้วมณี' },
    { id: 'TG-003', name: 'กลุ่มผู้สูงอายุ', ageRange: '60 ปีขึ้นไป', memberCount: 97, avgScoreChange: 1.2, active: true, updatedAt: '2026-07-18', updatedBy: 'วีระ ศรีสมบัติ' }
  ],

  sampleGroups: [
    { name: 'กลุ่มตัวอย่างติดตามผล รุ่นที่ 1', activityName: 'กิจกรรมฟื้นฟูสุขภาวะชุมชน', targetGroupName: 'กลุ่มผู้สูงอายุ', sampleSize: 20, trackedCount: 12, avgScoreChange: 1.1 },
    { name: 'กลุ่มตัวอย่างติดตามผล รุ่นที่ 2', activityName: 'ปลูกผักปลอดสารสำหรับครอบครัว', targetGroupName: 'กลุ่มวัยทำงาน', sampleSize: 15, trackedCount: 3, avgScoreChange: 0.7 },
    { name: 'กลุ่มตัวอย่างติดตามผล รุ่นที่ 3', activityName: 'Workshop อาหารสุขภาพจากสวน', targetGroupName: 'กลุ่มเด็กและเยาวชน', sampleSize: 10, trackedCount: 10, avgScoreChange: 0.9 }
  ],

  scoreTrend: [
    { period: 'ก่อนเข้าร่วมกิจกรรม', score: 3.2 },
    { period: 'หลังเข้าร่วมกิจกรรมทันที', score: 4.5 },
    { period: 'ติดตามผล 3 เดือน', score: 4.1 },
    { period: 'ติดตามผล 6 เดือน', score: 4.3 },
    { period: 'ติดตามผล 12 เดือน', score: 4.4 }
  ],

  participantsSummary: [
    { name: 'สมชาย ใจงาม', area: 'ชุมชนพูนทรัพย์ เขตสายไหม', targetGroup: 'กลุ่มวัยทำงาน', activitiesJoined: 3, avgSatisfaction: 4.6, followUpStatus: 'ติดตามตามกำหนด' },
    { name: 'วิภาดา สายใจ', area: 'ชุมชนพูนทรัพย์ เขตสายไหม', targetGroup: 'กลุ่มวัยทำงาน', activitiesJoined: 2, avgSatisfaction: 4.2, followUpStatus: 'ติดตามตามกำหนด' },
    { name: 'อดิศักดิ์ พูลสวัสดิ์', area: 'ชุมชนพูนทรัพย์ เขตสายไหม', targetGroup: 'กลุ่มผู้สูงอายุ', activitiesJoined: 1, avgSatisfaction: 4.8, followUpStatus: 'เกินกำหนดติดตามผล' },
    { name: 'ประภาส ทองแท้', area: 'ศูนย์การเรียนรู้ The Farm Concept', targetGroup: 'กลุ่มเด็กและเยาวชน', activitiesJoined: 1, avgSatisfaction: 4.4, followUpStatus: 'ติดตามครบแล้ว' },
    { name: 'กัลยา รุ่งเจริญ', area: 'ชุมชนหนองแขม', targetGroup: 'กลุ่มวัยทำงาน', activitiesJoined: 1, avgSatisfaction: 0, followUpStatus: 'ยังไม่เข้าร่วมกิจกรรม' }
  ],

  users: [
    { id: 'USR-001', name: 'สุนิสา แก้วมณี', username: 'sunisa01', email: 'sunisa@thefarmconcept.org', role: 'เจ้าหน้าที่โครงการ', roles: ['เจ้าหน้าที่โครงการ'], area: 'ชุมชนพูนทรัพย์ เขตสายไหม', status: 'ใช้งานอยู่', lastLogin: '2026-08-03' },
    { id: 'USR-002', name: 'วีระ ศรีสมบัติ', username: 'weera02', email: 'weera@thefarmconcept.org', role: 'ผู้ดูแลโครงการ', roles: ['ผู้ดูแลโครงการ', 'เจ้าหน้าที่โครงการ'], area: 'ศูนย์การเรียนรู้ The Farm Concept', status: 'ใช้งานอยู่', lastLogin: '2026-08-02' },
    { id: 'USR-003', name: 'ปิยะดา รุ่งเรือง', username: 'piyada03', email: 'piyada@thefarmconcept.org', role: 'เจ้าหน้าที่โครงการ', roles: ['เจ้าหน้าที่โครงการ'], area: 'ชุมชนหนองแขม', status: 'ระงับการใช้งาน', lastLogin: '2026-07-20' },
    { id: 'USR-004', name: 'ธนากร ใจดี', username: 'thanakorn04', email: 'thanakorn@thefarmconcept.org', role: 'เจ้าหน้าที่โครงการ', roles: ['เจ้าหน้าที่โครงการ'], area: 'ชุมชนบางบัว', status: 'ใช้งานอยู่', lastLogin: '2026-08-01' },
    { id: 'USR-005', name: 'อรุณี ทองสุข', username: 'arunee05', email: 'arunee@thefarmconcept.org', role: 'ผู้ดูแลระบบสูงสุด', roles: ['ผู้ดูแลระบบสูงสุด'], area: 'ส่วนกลาง', status: 'ใช้งานอยู่', lastLogin: '2026-08-03' }
  ],

  roles: [
    {
      id: 'ROLE-001', name: 'ผู้ดูแลระบบสูงสุด', code: 'super_admin',
      description: 'จัดการโครงการ ผู้ใช้งาน และข้อมูลกลางทั้งหมดของระบบ',
      userCount: 1, active: true,
      permissions: { project: true, users: true, areas: true, master_data: true, activities: true, payments: true, evaluations: true, reports: true },
      /* Granular menu-level permissions — keys match assets/js/menu-config.js item keys. This is a
         separate, additive structure used only by the new Permission Matrix UI in the Role popup;
         it does NOT replace `permissions` above, which is what TFC.hasPermission() still checks for
         gating existing row actions (edit/delete buttons etc.) across the whole app. */
      menuPermissions: {
        'dashboard': true, 'all-participants': true,
        'activities': true, 'activities-list': true, 'activities-registrations': true, 'activities-payments': true, 'activities-checkin': true, 'activities-satisfaction': true,
        'health-change': true, 'health-change-followup': true, 'health-change-reminders': true,
        'evaluation-forms': true, 'evaluation-forms-manage': true,
        'reports': true, 'reports-activities': true, 'reports-areas': true, 'reports-participants': true, 'reports-target-groups': true, 'reports-samples': true, 'reports-individual': true,
        'master-data': true, 'master-data-areas': true, 'master-data-target-groups': true, 'master-data-programs': true, 'master-data-instructors': true, 'master-data-activity-formats': true, 'master-data-sample-rounds': true,
        'users': true, 'users-list': true, 'users-roles': true
      }
    },
    {
      id: 'ROLE-002', name: 'ผู้ดูแลโครงการ', code: 'project_admin',
      description: 'จัดการพื้นที่ กิจกรรม และรายงานภายในโครงการที่รับผิดชอบ',
      userCount: 2, active: true,
      permissions: { project: false, users: false, areas: true, master_data: true, activities: true, payments: true, evaluations: true, reports: true },
      menuPermissions: {
        'dashboard': true, 'all-participants': true,
        'activities': true, 'activities-list': true, 'activities-registrations': true, 'activities-payments': true, 'activities-checkin': true, 'activities-satisfaction': true,
        'health-change': true, 'health-change-followup': true, 'health-change-reminders': true,
        'evaluation-forms': true, 'evaluation-forms-manage': true,
        'reports': true, 'reports-activities': true, 'reports-areas': true, 'reports-participants': true, 'reports-target-groups': true, 'reports-samples': true, 'reports-individual': true,
        'master-data': true, 'master-data-areas': true, 'master-data-target-groups': true, 'master-data-programs': true, 'master-data-instructors': true, 'master-data-activity-formats': true, 'master-data-sample-rounds': true,
        'users': false, 'users-list': false, 'users-roles': false
      }
    },
    {
      id: 'ROLE-003', name: 'เจ้าหน้าที่โครงการ', code: 'staff',
      description: 'จัดการกิจกรรม ลงทะเบียน ตรวจสอบการชำระเงิน และติดตามผลในพื้นที่ที่รับผิดชอบ',
      userCount: 3, active: true,
      permissions: { project: false, users: false, areas: false, master_data: false, activities: true, payments: true, evaluations: true, reports: false },
      menuPermissions: {
        'dashboard': true, 'all-participants': true,
        'activities': true, 'activities-list': true, 'activities-registrations': true, 'activities-payments': true, 'activities-checkin': true, 'activities-satisfaction': true,
        'health-change': true, 'health-change-followup': true, 'health-change-reminders': true,
        'evaluation-forms': true, 'evaluation-forms-manage': true,
        'reports': false, 'reports-activities': false, 'reports-areas': false, 'reports-participants': false, 'reports-target-groups': false, 'reports-samples': false, 'reports-individual': false,
        'master-data': false, 'master-data-areas': false, 'master-data-target-groups': false, 'master-data-programs': false, 'master-data-instructors': false, 'master-data-activity-formats': false, 'master-data-sample-rounds': false,
        'users': false, 'users-list': false, 'users-roles': false
      }
    },
    {
      id: 'ROLE-004', name: 'ผู้เข้าร่วมกิจกรรม', code: 'participant',
      description: 'ลงทะเบียนกิจกรรม แนบหลักฐานการชำระเงิน และทำแบบประเมิน',
      userCount: 337, active: true,
      permissions: { project: false, users: false, areas: false, master_data: false, activities: false, payments: false, evaluations: false, reports: false },
      menuPermissions: {
        'dashboard': false, 'all-participants': false,
        'activities': false, 'activities-list': false, 'activities-registrations': false, 'activities-payments': false, 'activities-checkin': false, 'activities-satisfaction': false,
        'health-change': false, 'health-change-followup': false, 'health-change-reminders': false,
        'evaluation-forms': false, 'evaluation-forms-manage': false,
        'reports': false, 'reports-activities': false, 'reports-areas': false, 'reports-participants': false, 'reports-target-groups': false, 'reports-samples': false, 'reports-individual': false,
        'master-data': false, 'master-data-areas': false, 'master-data-target-groups': false, 'master-data-programs': false, 'master-data-instructors': false, 'master-data-activity-formats': false, 'master-data-sample-rounds': false,
        'users': false, 'users-list': false, 'users-roles': false
      }
    }
  ],

  permissionModules: [
    { key: 'project', label: 'จัดการโครงการ' },
    { key: 'users', label: 'จัดการผู้ใช้งาน' },
    { key: 'areas', label: 'จัดการพื้นที่ดำเนินงาน' },
    { key: 'master_data', label: 'จัดการข้อมูลพื้นฐาน' },
    { key: 'activities', label: 'จัดการกิจกรรม' },
    { key: 'payments', label: 'ตรวจสอบการชำระเงิน' },
    { key: 'evaluations', label: 'จัดการแบบประเมิน' },
    { key: 'reports', label: 'ดูรายงาน' }
  ],

  programs: [
    {
      id: 'PROG-001', name: 'โครงการฟื้นฟูสุขภาวะชุมชน', category: 'สุขภาวะชุมชน', activityCount: 3, status: 'ดำเนินการอยู่', active: true,
      courses: [
        { order: 1, name: 'หลักสูตรออกกำลังกายเพื่อสุขภาพ' },
        { order: 2, name: 'หลักสูตรโภชนาการเบื้องต้น' },
        { order: 3, name: 'หลักสูตรการจัดการความเครียด' }
      ],
      updatedAt: '2026-07-30', updatedBy: 'สุนิสา แก้วมณี'
    },
    {
      id: 'PROG-002', name: 'โครงการเกษตรเพื่อสุขภาพ', category: 'เกษตรและอาหาร', activityCount: 2, status: 'ดำเนินการอยู่', active: true,
      courses: [
        { order: 1, name: 'หลักสูตรปลูกผักปลอดสารพิษ' },
        { order: 2, name: 'หลักสูตรทำปุ๋ยหมักอินทรีย์' }
      ],
      updatedAt: '2026-07-25', updatedBy: 'วีระ ศรีสมบัติ'
    },
    {
      id: 'PROG-003', name: 'โครงการพัฒนาเยาวชนนักปลูกผัก', category: 'เยาวชน', activityCount: 0, status: 'ฉบับร่าง', active: false,
      courses: [
        { order: 1, name: 'หลักสูตรเกษตรกรน้อยรุ่นใหม่' }
      ],
      updatedAt: '2026-08-02', updatedBy: 'สุนิสา แก้วมณี'
    }
  ],

  instructors: [
    {
      id: 'INS-001', name: 'อาจารย์สมพงษ์ ปลูกดี', phone: '08x-xxx-1111', activityCount: 4, active: true,
      photo: '', expertise: 'เกษตรอินทรีย์และปุ๋ยหมัก',
      expertiseList: ['เกษตรอินทรีย์', 'การทำปุ๋ยหมัก', 'การปลูกผักปลอดสารพิษ'],
      bio: 'มีประสบการณ์ด้านเกษตรอินทรีย์กว่า 15 ปี เป็นวิทยากรประจำของ The Farm Concept',
      updatedAt: '2026-07-28', updatedBy: 'สุนิสา แก้วมณี'
    },
    {
      id: 'INS-002', name: 'คุณนภา ทำอาหารเพื่อสุขภาพ', phone: '08x-xxx-2222', activityCount: 2, active: true,
      photo: '', expertise: 'โภชนาการและอาหารสุขภาพจากวัตถุดิบในสวน',
      expertiseList: ['โภชนาการ', 'อาหารสุขภาพจากผักสวนครัว'],
      bio: 'นักโภชนาการที่เชี่ยวชาญการนำวัตถุดิบจากสวนมาปรุงเป็นเมนูเพื่อสุขภาพ',
      updatedAt: '2026-07-20', updatedBy: 'สุนิสา แก้วมณี'
    },
    {
      id: 'INS-003', name: 'อาจารย์ประสิทธิ์ สวนผักงาม', phone: '08x-xxx-3333', activityCount: 3, active: true,
      photo: '', expertise: 'การออกแบบสวนผักในเมือง',
      expertiseList: ['การออกแบบสวนผักในเมือง', 'สวนผักคอนโด'],
      bio: '',
      updatedAt: '2026-07-10', updatedBy: 'วีระ ศรีสมบัติ'
    }
  ],

  activityFormats: [
    { id: 'FMT-001', name: 'Workshop ลงมือปฏิบัติ', active: true, badgeColor: 'primary', updatedAt: '2026-07-15', updatedBy: 'สุนิสา แก้วมณี' },
    { id: 'FMT-002', name: 'บรรยาย/อบรม', active: true, badgeColor: 'info', updatedAt: '2026-07-15', updatedBy: 'สุนิสา แก้วมณี' },
    { id: 'FMT-003', name: 'ตลาดนัด/กิจกรรมเปิด', active: true, badgeColor: 'warning', updatedAt: '2026-07-10', updatedBy: 'วีระ ศรีสมบัติ' },
    { id: 'FMT-004', name: 'ทัศนศึกษา/ดูงาน', active: false, badgeColor: 'neutral', updatedAt: '2026-06-20', updatedBy: 'สุนิสา แก้วมณี' }
  ],

  activityFormatBadgeColors: [
    { value: 'primary', label: 'เขียว (Primary)' },
    { value: 'info', label: 'ฟ้า (Info)' },
    { value: 'warning', label: 'เหลือง (Warning)' },
    { value: 'danger', label: 'แดง (Danger)' },
    { value: 'neutral', label: 'เทา (Neutral)' }
  ],

  sampleFollowUpRounds: [
    { id: 'ROUND-001', name: 'ติดตามผล 3 เดือน', trackDays: 90, lineNotify: true, notifyDaysBefore: 7 },
    { id: 'ROUND-002', name: 'ติดตามผล 6 เดือน', trackDays: 180, lineNotify: true, notifyDaysBefore: 7 },
    { id: 'ROUND-003', name: 'ติดตามผล 12 เดือน', trackDays: 365, lineNotify: false, notifyDaysBefore: 14 }
  ],

  /* ==========================================================================
     โมดูล "ผู้เข้าร่วมทั้งหมด" (Participant Management)
     - targetGroups / areas / sampleFollowUpRounds ไม่ถูกสร้างซ้ำที่นี่ — หน้าจอในโมดูลนี้อ่านจาก
       key เดิมด้านบนโดยตรง (โมดูล "พื้นฐาน") เพื่อให้แก้ที่เมนูพื้นฐานแล้วสะท้อนผลทันที
     ========================================================================== */
  /* value = โค้ดที่ฟอร์มใช้ควบคุมการแสดง Section "แผนการติดตาม" — label ตรงกับ activityParticipantTypes
     ของโมดูลกิจกรรม (ที่นั่นเก็บเป็น label ล้วนเพราะใช้เป็นตัวกรองอย่างเดียว) */
  participantTypes: [
    { value: 'sample', label: 'กลุ่มตัวอย่าง' },
    { value: 'general', label: 'ผู้เข้าร่วมทั่วไป' }
  ],
  /* "แหล่งที่มาของข้อมูล" ใช้ activityDataSources ร่วมกับโมดูลกิจกรรม — ไม่สร้าง list ซ้ำ */
  participantContactChannels: ['โทรศัพท์', 'LINE', 'อีเมล', 'ผ่านผู้ดูแล'],
  participantGenders: ['ชาย', 'หญิง', 'ไม่ระบุ'],
  participantCaregiverRelations: ['บุตร/ธิดา', 'คู่สมรส', 'บิดา/มารดา', 'ญาติ', 'ผู้ดูแลอาสาสมัคร', 'อื่นๆ'],
  participantStatuses: ['ใช้งานอยู่', 'ระงับการใช้งาน'],
  participantConsentStatuses: ['ยินยอม', 'ไม่ยินยอม', 'รอยืนยัน', 'ขอถอนความยินยอม'],
  participantProjectStatuses: ['เข้าร่วม', 'ถอนตัว', 'ติดตามไม่ได้', 'เสียชีวิต'],
  participantPurchaseStatuses: ['รอดำเนินการ', 'สำเร็จ', 'ยกเลิก'],

  /* followUpPlan[].roundId อ้างอิง sampleFollowUpRounds[].id — เก็บเฉพาะวันที่กำหนดติดตามของแต่ละรอบ
     ไม่เก็บชื่อรอบซ้ำ เพื่อให้ชื่อ/จำนวนรอบมาจากเมนู "รอบติดตามกลุ่มตัวอย่าง" ที่เดียว */
  participants: [
    {
      id: 'PTP-0001', personCode: 'TFC-69-0001', type: 'sample', name: 'สมชาย ใจงาม',
      phone: '081-234-5671', email: 'somchai.j@example.com', gender: 'ชาย',
      targetGroup: 'กลุ่มวัยทำงาน', area: 'ชุมชนพูนทรัพย์ เขตสายไหม',
      source: 'ลงทะเบียนออนไลน์', contactChannel: 'LINE',
      hasCaregiver: false, caregiverName: '', caregiverRelation: '', caregiverPhone: '',
      status: 'ใช้งานอยู่',
      followUpPlan: [
        { roundId: 'ROUND-001', dueDate: '2026-11-10' },
        { roundId: 'ROUND-002', dueDate: '2027-02-10' },
        { roundId: 'ROUND-003', dueDate: '2027-08-10' }
      ],
      lineNotify: true,
      consentStatus: 'ยินยอม', consentDate: '2026-07-25', consentFileName: 'consent-somchai.pdf',
      consentNote: 'ลงนามในแบบยินยอมฉบับกระดาษ ณ วันลงทะเบียน',
      projectStatus: 'เข้าร่วม',
      updatedAt: '2026-08-03', updatedBy: 'สุนิสา แก้วมณี'
    },
    {
      id: 'PTP-0002', personCode: 'TFC-69-0002', type: 'sample', name: 'วิภาดา สายใจ',
      phone: '082-345-6782', email: 'wipada.s@example.com', gender: 'หญิง',
      targetGroup: 'กลุ่มวัยทำงาน', area: 'ชุมชนพูนทรัพย์ เขตสายไหม',
      source: 'บันทึกโดยเจ้าหน้าที่', contactChannel: 'โทรศัพท์',
      hasCaregiver: false, caregiverName: '', caregiverRelation: '', caregiverPhone: '',
      status: 'ใช้งานอยู่',
      followUpPlan: [
        { roundId: 'ROUND-001', dueDate: '2026-11-10' },
        { roundId: 'ROUND-002', dueDate: '2027-02-10' },
        { roundId: 'ROUND-003', dueDate: '' }
      ],
      lineNotify: false,
      consentStatus: 'ยินยอม', consentDate: '2026-07-26', consentFileName: '',
      consentNote: '',
      projectStatus: 'เข้าร่วม',
      updatedAt: '2026-08-01', updatedBy: 'สุนิสา แก้วมณี'
    },
    {
      id: 'PTP-0003', personCode: 'TFC-69-0003', type: 'sample', name: 'อดิศักดิ์ พูลสวัสดิ์',
      phone: '085-678-9015', email: '', gender: 'ชาย',
      targetGroup: 'กลุ่มผู้สูงอายุ', area: 'ชุมชนพูนทรัพย์ เขตสายไหม',
      source: 'ลงทะเบียนหน้างาน', contactChannel: 'ผ่านผู้ดูแล',
      hasCaregiver: true, caregiverName: 'สายฝน พูลสวัสดิ์', caregiverRelation: 'บุตร/ธิดา', caregiverPhone: '089-111-2233',
      status: 'ใช้งานอยู่',
      followUpPlan: [
        { roundId: 'ROUND-001', dueDate: '2026-10-20' },
        { roundId: 'ROUND-002', dueDate: '2027-01-20' },
        { roundId: 'ROUND-003', dueDate: '2027-07-20' }
      ],
      lineNotify: true,
      consentStatus: 'ยินยอม', consentDate: '2026-07-10', consentFileName: 'consent-adisak.pdf',
      consentNote: 'ผู้ดูแลเป็นผู้ลงนามแทน',
      projectStatus: 'เข้าร่วม',
      updatedAt: '2026-07-30', updatedBy: 'วีระ ศรีสมบัติ'
    },
    {
      id: 'PTP-0004', personCode: 'TFC-69-0004', type: 'general', name: 'ประภาส ทองแท้',
      phone: '083-456-7893', email: 'prapas.t@example.com', gender: 'ชาย',
      targetGroup: 'กลุ่มเด็กและเยาวชน', area: 'ศูนย์การเรียนรู้ The Farm Concept',
      source: 'ลงทะเบียนออนไลน์', contactChannel: 'อีเมล',
      hasCaregiver: true, caregiverName: 'มณีรัตน์ ทองแท้', caregiverRelation: 'บิดา/มารดา', caregiverPhone: '084-567-8904',
      status: 'ใช้งานอยู่',
      followUpPlan: [],
      lineNotify: false,
      consentStatus: 'ยินยอม', consentDate: '2026-07-28', consentFileName: '',
      consentNote: '',
      projectStatus: 'เข้าร่วม',
      updatedAt: '2026-07-28', updatedBy: 'สุนิสา แก้วมณี'
    },
    {
      id: 'PTP-0005', personCode: 'TFC-69-0005', type: 'general', name: 'กัลยา รุ่งเจริญ',
      phone: '086-789-0126', email: 'kanlaya.r@example.com', gender: 'หญิง',
      targetGroup: 'กลุ่มวัยทำงาน', area: 'ชุมชนหนองแขม',
      source: 'นำเข้าจากไฟล์', contactChannel: 'โทรศัพท์',
      hasCaregiver: false, caregiverName: '', caregiverRelation: '', caregiverPhone: '',
      status: 'ใช้งานอยู่',
      followUpPlan: [],
      lineNotify: false,
      consentStatus: 'รอยืนยัน', consentDate: '', consentFileName: '',
      consentNote: 'นำเข้าจากไฟล์ ยังไม่ได้เก็บแบบยินยอม',
      projectStatus: 'เข้าร่วม',
      updatedAt: '2026-08-01', updatedBy: 'ปิยะดา รุ่งเรือง'
    },
    {
      id: 'PTP-0006', personCode: 'TFC-69-0006', type: 'sample', name: 'พิมพ์ใจ เพียรทำ',
      phone: '087-222-3344', email: '', gender: 'หญิง',
      targetGroup: 'กลุ่มผู้สูงอายุ', area: 'ชุมชนบางบัว',
      source: 'ลงทะเบียนออนไลน์', contactChannel: 'LINE',
      hasCaregiver: false, caregiverName: '', caregiverRelation: '', caregiverPhone: '',
      status: 'ระงับการใช้งาน',
      followUpPlan: [
        { roundId: 'ROUND-001', dueDate: '2026-08-01' },
        { roundId: 'ROUND-002', dueDate: '' },
        { roundId: 'ROUND-003', dueDate: '' }
      ],
      lineNotify: true,
      consentStatus: 'ขอถอนความยินยอม', consentDate: '2026-05-12', consentFileName: 'consent-pimjai.pdf',
      consentNote: 'แจ้งขอถอนความยินยอมทางโทรศัพท์ เมื่อ 20 ก.ค. 2569',
      projectStatus: 'ติดตามไม่ได้',
      updatedAt: '2026-07-21', updatedBy: 'สุนิสา แก้วมณี'
    }
  ],

  /* ประวัติเข้ากิจกรรมรายบุคคล — key = participants[].id
     evaluated = ทำแบบประเมินความพึงพอใจของกิจกรรมนั้นแล้วหรือยัง (ใช้นับคอลัมน์ "จำนวนทำแบบประเมิน") */
  participantActivityHistory: {
    'PTP-0001': [
      { id: 'PAH-0001', activityName: 'กิจกรรมฟื้นฟูสุขภาวะชุมชน', location: 'ชุมชนพูนทรัพย์ เขตสายไหม', price: 0, joinDate: '2026-07-20', evaluated: true },
      { id: 'PAH-0002', activityName: 'ปลูกผักปลอดสารสำหรับครอบครัว', location: 'ชุมชนพูนทรัพย์ เขตสายไหม', price: 0, joinDate: '2026-08-10', evaluated: true },
      { id: 'PAH-0003', activityName: 'Workshop อาหารสุขภาพจากสวน', location: 'ศูนย์การเรียนรู้ The Farm Concept', price: 200, joinDate: '2026-08-17', evaluated: false }
    ],
    'PTP-0002': [
      { id: 'PAH-0004', activityName: 'ปลูกผักปลอดสารสำหรับครอบครัว', location: 'ชุมชนพูนทรัพย์ เขตสายไหม', price: 0, joinDate: '2026-08-10', evaluated: true },
      { id: 'PAH-0005', activityName: 'เรียนรู้การทำปุ๋ยหมัก', location: 'ชุมชนหนองแขม', price: 0, joinDate: '2026-08-24', evaluated: false }
    ],
    'PTP-0003': [
      { id: 'PAH-0006', activityName: 'กิจกรรมฟื้นฟูสุขภาวะชุมชน', location: 'ชุมชนพูนทรัพย์ เขตสายไหม', price: 0, joinDate: '2026-07-20', evaluated: true }
    ],
    'PTP-0004': [
      { id: 'PAH-0007', activityName: 'Workshop อาหารสุขภาพจากสวน', location: 'ศูนย์การเรียนรู้ The Farm Concept', price: 200, joinDate: '2026-08-17', evaluated: true }
    ],
    'PTP-0005': [],
    'PTP-0006': [
      { id: 'PAH-0008', activityName: 'กิจกรรมฟื้นฟูสุขภาวะชุมชน', location: 'ชุมชนพูนทรัพย์ เขตสายไหม', price: 0, joinDate: '2026-07-20', evaluated: false }
    ]
  },

  /* ประวัติประเมินการเปลี่ยนแปลงสุขภาพ — roundId อ้างอิง sampleFollowUpRounds[].id (ชุดเดียวกับแผนการติดตาม) */
  participantHealthEvaluations: {
    'PTP-0001': [
      { id: 'PHE-0001', roundId: 'ROUND-001', evaluatedAt: '2026-11-12', recordedBy: 'ผู้เข้าร่วมทำเอง' }
    ],
    'PTP-0002': [],
    'PTP-0003': [
      { id: 'PHE-0002', roundId: 'ROUND-001', evaluatedAt: '2026-10-22', recordedBy: 'สุนิสา แก้วมณี (ทำแทน)' },
      { id: 'PHE-0003', roundId: 'ROUND-002', evaluatedAt: '2027-01-25', recordedBy: 'สุนิสา แก้วมณี (ทำแทน)' }
    ],
    'PTP-0004': [],
    'PTP-0005': [],
    'PTP-0006': []
  },

  /* ประวัติการซื้อสินค้า — เฟสปัจจุบันกรอก Manual ทั้งหมด (ยังไม่มี Master Data สินค้า/ร้านค้า)
     โครงสร้าง field ตั้งใจแยก items[] ออกมาแล้ว เพื่อให้เฟสถัดไปเปลี่ยน items[].productName (string)
     เป็น items[].productId (reference) และ storeName เป็น storeId ได้โดยไม่ต้องรื้อ UI */
  participantPurchases: {
    'PTP-0001': [
      {
        id: 'PPO-0001',
        items: [
          { productName: 'เมล็ดพันธุ์ผักสลัด', quantity: 2 },
          { productName: 'ดินปลูกอินทรีย์ 5 กก.', quantity: 1 }
        ],
        storeName: 'ร้านค้าชุมชนพูนทรัพย์', orderDate: '2026-08-01', orderStatus: 'สำเร็จ'
      }
    ],
    'PTP-0002': [],
    'PTP-0003': [
      {
        id: 'PPO-0002',
        items: [{ productName: 'ชุดปลูกผักในกระถาง', quantity: 1 }],
        storeName: 'The Farm Concept Shop', orderDate: '2026-07-25', orderStatus: 'รอดำเนินการ'
      }
    ],
    'PTP-0004': [],
    'PTP-0005': [],
    'PTP-0006': []
  },

  registrations: [
    { id: 'REG-0001', activityId: 'ACT-2026-014', activityName: 'ปลูกผักปลอดสารสำหรับครอบครัว', name: 'สมชาย ใจงาม', phone: '081-234-5671', session: '10 ส.ค. 2569 · 09:00', paymentStatus: 'ชำระแล้ว', checkinStatus: 'ยังไม่เข้าร่วม', registeredAt: '2026-07-25' },
    { id: 'REG-0002', activityId: 'ACT-2026-014', activityName: 'ปลูกผักปลอดสารสำหรับครอบครัว', name: 'วิภาดา สายใจ', phone: '082-345-6782', session: '10 ส.ค. 2569 · 09:00', paymentStatus: 'รอตรวจสอบ', checkinStatus: 'ยังไม่เข้าร่วม', registeredAt: '2026-07-26' },
    { id: 'REG-0003', activityId: 'ACT-2026-015', activityName: 'Workshop อาหารสุขภาพจากสวน', name: 'ประภาส ทองแท้', phone: '083-456-7893', session: '17 ส.ค. 2569 · 09:00', paymentStatus: 'รอตรวจสอบ', checkinStatus: 'ยังไม่เข้าร่วม', registeredAt: '2026-07-28' },
    { id: 'REG-0004', activityId: 'ACT-2026-015', activityName: 'Workshop อาหารสุขภาพจากสวน', name: 'มณีรัตน์ ใจบุญ', phone: '084-567-8904', session: '17 ส.ค. 2569 · 09:00', paymentStatus: 'ชำระแล้ว', checkinStatus: 'เข้าร่วมแล้ว', registeredAt: '2026-07-20' },
    { id: 'REG-0005', activityId: 'ACT-2026-017', activityName: 'กิจกรรมฟื้นฟูสุขภาวะชุมชน', name: 'อดิศักดิ์ พูลสวัสดิ์', phone: '085-678-9015', session: '20 ก.ค. 2569 · 09:00', paymentStatus: 'ชำระแล้ว', checkinStatus: 'เข้าร่วมแล้ว', registeredAt: '2026-07-10' },
    { id: 'REG-0006', activityId: 'ACT-2026-016', activityName: 'เรียนรู้การทำปุ๋ยหมัก', name: 'กัลยา รุ่งเจริญ', phone: '086-789-0126', session: '24 ส.ค. 2569 · 09:00', paymentStatus: 'ปฏิเสธ', checkinStatus: 'ยังไม่เข้าร่วม', registeredAt: '2026-08-01' },
    { id: 'REG-0007', activityId: 'ACT-2026-014', activityName: 'ปลูกผักปลอดสารสำหรับครอบครัว', name: 'ธีรพงษ์ แสงทอง', phone: '087-890-1237', session: '10 ส.ค. 2569 · 09:00', paymentStatus: 'ชำระแล้ว', checkinStatus: 'ยังไม่เข้าร่วม', registeredAt: '2026-07-27' }
  ],

  evaluationForms: [
    { id: 'EVL-001', name: 'แบบประเมินหลังเข้าร่วม: ปลูกผักปลอดสารสำหรับครอบครัว', activityName: 'ปลูกผักปลอดสารสำหรับครอบครัว', type: 'หลังเข้าร่วมกิจกรรม', questionCount: 5, responseCount: 3, status: 'เผยแพร่แล้ว', createdAt: '2026-08-01' },
    { id: 'EVL-002', name: 'แบบประเมินหลังเข้าร่วม: Workshop อาหารสุขภาพจากสวน', activityName: 'Workshop อาหารสุขภาพจากสวน', type: 'หลังเข้าร่วมกิจกรรม', questionCount: 6, responseCount: 30, status: 'เผยแพร่แล้ว', createdAt: '2026-08-05' },
    { id: 'EVL-003', name: 'แบบติดตามผล 3 เดือน: กิจกรรมฟื้นฟูสุขภาวะชุมชน', activityName: 'กิจกรรมฟื้นฟูสุขภาวะชุมชน', type: 'ติดตามผล 3 เดือน', questionCount: 10, responseCount: 12, status: 'เผยแพร่แล้ว', createdAt: '2026-07-25' },
    { id: 'EVL-004', name: 'แบบประเมินหลังเข้าร่วม: เรียนรู้การทำปุ๋ยหมัก', activityName: 'เรียนรู้การทำปุ๋ยหมัก', type: 'หลังเข้าร่วมกิจกรรม', questionCount: 7, responseCount: 0, status: 'ฉบับร่าง', createdAt: '2026-08-02' }
  ],

  evaluationQuestions: {
    'EVL-001': [
      { order: 1, type: 'คะแนนความพึงพอใจ (1-5)', text: 'ความพึงพอใจโดยรวมต่อกิจกรรม', required: true },
      { order: 2, type: 'คะแนนความพึงพอใจ (1-5)', text: 'ความรู้ที่ได้รับสามารถนำไปใช้ได้จริง', required: true },
      { order: 3, type: 'ตัวเลือกเดียว', text: 'ท่านทราบข่าวกิจกรรมนี้จากช่องทางใด', required: false },
      { order: 4, type: 'ข้อความสั้น', text: 'สิ่งที่ประทับใจที่สุดในกิจกรรมนี้', required: false },
      { order: 5, type: 'ข้อความยาว', text: 'ข้อเสนอแนะเพิ่มเติม', required: false }
    ]
  },

  evaluationRespondents: {
    'EVL-001': [
      { name: 'สมชาย ใจงาม', phone: '081-234-5671', submittedAt: '2026-08-10', score: 4.6 },
      { name: 'วิภาดา สายใจ', phone: '082-345-6782', submittedAt: '2026-08-10', score: 4.2 },
      { name: 'ธีรพงษ์ แสงทอง', phone: '087-890-1237', submittedAt: '2026-08-11', score: 4.8 }
    ]
  },

  followUps: [
    { name: 'อดิศักดิ์ พูลสวัสดิ์', activityName: 'กิจกรรมฟื้นฟูสุขภาวะชุมชน', period: '3 เดือน', dueDate: '2026-10-20', status: 'รอดำเนินการ' },
    { name: 'พิมพ์ใจ เพียรทำ', activityName: 'กิจกรรมฟื้นฟูสุขภาวะชุมชน', period: '3 เดือน', dueDate: '2026-08-01', status: 'เกินกำหนด' },
    { name: 'สมชาย ใจงาม', activityName: 'ปลูกผักปลอดสารสำหรับครอบครัว', period: '6 เดือน', dueDate: '2027-02-10', status: 'รอดำเนินการ' },
    { name: 'วิภาดา สายใจ', activityName: 'ปลูกผักปลอดสารสำหรับครอบครัว', period: '12 เดือน', dueDate: '2027-08-10', status: 'รอดำเนินการ' },
    { name: 'ประภาส ทองแท้', activityName: 'Workshop อาหารสุขภาพจากสวน', period: '3 เดือน', dueDate: '2026-07-15', status: 'เสร็จสิ้น' }
  ],

  activitySessions: {
    'ACT-2026-014': [
      { date: '2026-08-10', time: '09:00 - 12:00', location: 'ชุมชนพูนทรัพย์ เขตสายไหม', capacity: 40, registered: 32 }
    ],
    'ACT-2026-015': [
      { date: '2026-08-17', time: '09:00 - 15:00', location: 'ศูนย์การเรียนรู้ The Farm Concept', capacity: 30, registered: 30 }
    ],
    'ACT-2026-016': [
      { date: '2026-08-24', time: '09:00 - 12:00', location: 'ชุมชนหนองแขม', capacity: 25, registered: 9 },
      { date: '2026-09-07', time: '09:00 - 12:00', location: 'ชุมชนหนองแขม', capacity: 25, registered: 0 }
    ],
    'ACT-2026-017': [
      { date: '2026-07-20', time: '09:00 - 16:00', location: 'ชุมชนพูนทรัพย์ เขตสายไหม', capacity: 50, registered: 47 }
    ],
    'ACT-2026-018': [
      { date: '2026-09-05', time: '08:00 - 12:00', location: 'ชุมชนบางบัว', capacity: 60, registered: 4 }
    ]
  },

  recentActivityLog: [
    { time: '08:45', title: 'ยืนยันการลงทะเบียน', detail: 'คุณสมชาย ใจงาม ลงทะเบียนกิจกรรมปลูกผักปลอดสารสำเร็จ' },
    { time: 'เมื่อวาน 16:20', title: 'ตรวจสอบสลิปแล้ว', detail: 'ตรวจสอบการชำระเงิน Workshop อาหารสุขภาพจากสวน จำนวน 8 รายการ' },
    { time: 'เมื่อวาน 11:05', title: 'ปิดรับสมัคร', detail: 'กิจกรรมฟื้นฟูสุขภาวะชุมชน มีผู้ลงทะเบียนครบจำนวนแล้ว' },
    { time: '2 วันที่แล้ว', title: 'สร้างแบบประเมินใหม่', detail: 'แบบประเมินหลังเข้าร่วม: เรียนรู้การทำปุ๋ยหมัก' }
  ]
};

/* Shared low-level helpers that must exist before any other script runs (mock-data.js always loads first) */
window.TFC = window.TFC || {};

window.TFC.escapeHtml = function (str) {
  var div = document.createElement('div');
  div.textContent = str == null ? '' : str;
  return div.innerHTML;
};

/* Permission check against the current mock user's role — used by the Action Menu to hide items the
   user cannot perform. Omitted/falsy `key` is always allowed (e.g. "view" actions).

   The answer is derived from the role's granular `menuPermissions` (edited in the Role Forms-Popup's
   Permission Matrix) via window.TFC_PERMISSION_MAP: the role holds a broad permission if ANY of the
   menu items mapped to it is ticked. `permissions` on the role object is only a fallback for when
   menu-config.js has not loaded (e.g. a standalone page with no sidebar). */
window.TFC.hasPermission = function (key) {
  if (!key) return true;
  var mock = window.TFC_MOCK || {};
  var roleCode = mock.currentUser && mock.currentUser.roleCode;
  var role = (mock.roles || []).filter(function (r) { return r.code === roleCode; })[0];
  if (!role) return true;

  var mappedMenuKeys = window.TFC_PERMISSION_MAP && window.TFC_PERMISSION_MAP[key];
  if (mappedMenuKeys && role.menuPermissions) {
    return mappedMenuKeys.some(function (menuKey) { return !!role.menuPermissions[menuKey]; });
  }
  return !!(role.permissions && role.permissions[key]);
};

/* Badge class lookup for the status master lists above (activityStatuses / paymentStatuses / checkinStatuses).
   Returns 'badge-neutral' for values that are not in the list, so old data never renders unstyled. */
window.TFC.badgeClassOf = function (list, value) {
  var hit = (list || []).filter(function (item) { return item.value === value; })[0];
  return (hit && hit.badge) || 'badge-neutral';
};

/* ระดับความพึงพอใจจากคะแนนเฉลี่ย — mapping กลางที่ทั้งตารางและกราฟใช้ร่วมกัน */
window.TFC.satisfactionLevelOf = function (score) {
  var levels = (window.TFC_MOCK.satisfactionLevels || []);
  return levels.filter(function (level) { return score >= level.min; })[0] || levels[levels.length - 1];
};

/* ==========================================================================
   ข้อมูลผู้ลงทะเบียนรายกิจกรรม + ผลแบบประเมินรายกิจกรรม (สร้างแบบ deterministic)
   ใช้เฉพาะหน้ารายละเอียดกิจกรรม (แท็บลงทะเบียน/แบบประเมิน) และการค้นหาผู้เข้าร่วมในหน้า Index
   จงใจแยกออกจาก TFC_MOCK.registrations เดิม เพื่อไม่ให้จำนวนแถวในหน้า "ผู้ลงทะเบียนทั้งหมด" เปลี่ยนไป
   ========================================================================== */
(function () {
  var mock = window.TFC_MOCK;
  var opt = mock.registrationOptions;

  /* LCG seed คงที่ -> ข้อมูลชุดเดิมทุกครั้งที่รีเฟรช (กราฟไม่กระโดด) */
  function seededRandom(seed) {
    var state = seed;
    return function () {
      state = (state * 1103515245 + 12345) % 2147483648;
      return state / 2147483648;
    };
  }

  var firstNames = ['สมชาย', 'วิภาดา', 'ธีรพงษ์', 'กัลยา', 'ประภาส', 'มณีรัตน์', 'อดิศักดิ์', 'พิมพ์ใจ', 'ณัฐวุฒิ', 'สุพรรณี', 'ชูเกียรติ', 'อรทัย', 'บุญมี', 'ปิยะนุช', 'วรรณา', 'เอกชัย', 'จันทร์เพ็ญ', 'ทวีศักดิ์', 'ศิริพร', 'มานพ'];
  var lastNames = ['ใจงาม', 'สายใจ', 'แสงทอง', 'รุ่งเจริญ', 'ทองแท้', 'ใจบุญ', 'พูลสวัสดิ์', 'เพียรทำ', 'ศรีสุข', 'ปลูกรัก', 'มั่นคง', 'วงศ์ดี', 'อยู่เย็น', 'ก้าวหน้า', 'พงษ์ไพร'];
  var feedbacks = [
    'วิทยากรอธิบายเข้าใจง่าย ได้ลงมือทำจริงทุกขั้นตอน',
    'อยากให้เพิ่มเวลาช่วงลงมือปฏิบัติอีกสักหน่อย',
    'สถานที่จัดงานสะดวก เดินทางง่าย มีที่จอดรถเพียงพอ',
    'ได้ความรู้ไปใช้ที่บ้านได้จริง ขอบคุณทีมงานทุกคน',
    'อยากให้จัดกิจกรรมแบบนี้บ่อยขึ้นในชุมชน',
    'เอกสารประกอบชัดเจน แต่ตัวหนังสือเล็กไปนิดสำหรับผู้สูงอายุ',
    ''
  ];

  function pick(rand, list) { return list[Math.floor(rand() * list.length)]; }

  function buildRegistrations(activity) {
    var rand = seededRandom(activity.id.replace(/\D/g, '') * 7 + 13);
    var sessions = mock.activitySessions[activity.id] || [];
    var rows = [];

    for (var i = 0; i < activity.registered; i++) {
      var payment = activity.hasFee
        ? pick(rand, ['ชำระแล้ว', 'ชำระแล้ว', 'ชำระแล้ว', 'รอตรวจสอบ', 'ยังไม่ชำระ'])
        : 'ชำระแล้ว';
      var isPast = new Date(activity.endDate) < new Date('2026-08-07');
      var checkin = isPast
        ? pick(rand, ['เข้าร่วมแล้ว', 'เข้าร่วมแล้ว', 'เข้าร่วมแล้ว', 'ไม่ได้เข้าร่วม'])
        : pick(rand, ['ยังไม่เข้าร่วม', 'ยังไม่เข้าร่วม', 'เข้าร่วมแล้ว']);
      var interestCount = 1 + Math.floor(rand() * 2);
      var interests = [];
      while (interests.length < interestCount) {
        var interest = pick(rand, opt.interests);
        if (interests.indexOf(interest) === -1) interests.push(interest);
      }
      var name = pick(rand, firstNames) + ' ' + pick(rand, lastNames);

      rows.push({
        id: activity.id + '-R' + String(i + 1).padStart(3, '0'),
        activityId: activity.id,
        name: name,
        phone: '08' + (1 + Math.floor(rand() * 9)) + '-' + String(100 + Math.floor(rand() * 900)) + '-' + String(1000 + Math.floor(rand() * 9000)),
        email: 'user' + (i + 1) + '.' + activity.id.toLowerCase() + '@example.com',
        gender: pick(rand, ['หญิง', 'หญิง', 'ชาย', 'ชาย', 'อื่นๆ']),
        ageRange: pick(rand, opt.ageRanges),
        occupation: pick(rand, opt.occupations),
        sourceChannel: pick(rand, opt.sourceChannels),
        interests: interests,
        paymentStatus: payment,
        checkinStatus: checkin,
        session: sessions.length ? sessions[Math.floor(rand() * sessions.length)].date : activity.startDate,
        registeredAt: activity.startDate,
        manualEntry: rand() < 0.15
      });
    }
    return rows;
  }

  function buildEvaluations(activity, registrations) {
    /* ตอบแบบประเมินได้เฉพาะผู้ที่ Check-in แล้ว และเฉพาะกิจกรรมที่ผูกชุดแบบประเมินไว้ */
    if (!activity.evaluationFormIds || !activity.evaluationFormIds.length) return [];
    var rand = seededRandom(activity.id.replace(/\D/g, '') * 31 + 5);
    var attended = registrations.filter(function (r) { return r.checkinStatus === 'เข้าร่วมแล้ว'; });

    return attended.filter(function () { return rand() < 0.75; }).map(function (person, index) {
      var topicScores = {};
      var sum = 0;
      mock.evaluationTopics.forEach(function (topic) {
        var score = 3 + Math.floor(rand() * 3);          /* 3–5 คะแนน */
        topicScores[topic.key] = score;
        sum += score;
      });
      var average = Math.round((sum / mock.evaluationTopics.length) * 10) / 10;

      return {
        id: activity.id + '-E' + String(index + 1).padStart(3, '0'),
        activityId: activity.id,
        registrationId: person.id,
        name: person.name,
        average: average,
        level: window.TFC.satisfactionLevelOf(average).value,
        feedback: pick(rand, feedbacks),
        answeredAt: activity.endDate + 'T' + String(13 + Math.floor(rand() * 6)).padStart(2, '0') + ':' + pick(rand, ['05', '18', '27', '40', '52']),
        topicScores: topicScores
      };
    });
  }

  mock.activityRegistrations = {};
  mock.activityEvaluations = {};

  mock.activities.forEach(function (activity) {
    var registrations = buildRegistrations(activity);
    mock.activityRegistrations[activity.id] = registrations;
    mock.activityEvaluations[activity.id] = buildEvaluations(activity, registrations);
  });
})();
