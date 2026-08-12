/* TheFarmConcept — Central Mock Data (ข้อมูลจำลองภาษาไทย ไม่มีการเชื่อมต่อฐานข้อมูลจริง) */
window.TFC_MOCK = {
  /* ผู้ใช้เดโม: ใช้บทบาท "ผู้ดูแลโครงการ" เพื่อให้เห็นและใช้งานเมนูพื้นฐาน (master_data) ได้ครบ
     ถ้าเปลี่ยนกลับเป็น staff เมนูจัดการในหน้าข้อมูลพื้นฐานจะขึ้นว่า "ไม่มีสิทธิ์ดำเนินการ" ตามระบบสิทธิ์ */
  /* ใช้กับ topbar และ Popup "โปรไฟล์ของฉัน" (assets/js/profile-modal.js)
     แก้ไขได้เฉพาะ avatar / name / phone / password — username กับ role อ่านอย่างเดียว */
  currentUser: {
    name: 'วีระ ศรีสมบัติ',
    phone: '082-222-3333',
    username: 'weera.s',
    role: 'ผู้ดูแลโครงการ',
    roleCode: 'project_admin',
    initials: 'วร',
    avatar: ''
  },

  notifications: [
    { title: 'มีผู้ลงทะเบียนใหม่', detail: 'กิจกรรมปลูกผักปลอดสารสำหรับครอบครัว รอบที่ 2', time: '10 นาทีที่แล้ว', type: 'info' },
    { title: 'รอตรวจสอบสลิปการชำระเงิน', detail: 'Workshop อาหารสุขภาพจากสวน มีสลิปรอตรวจ 5 รายการ', time: '1 ชั่วโมงที่แล้ว', type: 'warning' },
    { title: 'ถึงกำหนดติดตามผล 3 เดือน', detail: 'ผู้เข้าร่วมผู้สูงอายุ 12 คน ครบกำหนดติดตามแล้ว', time: 'เมื่อวาน', type: 'danger' }
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
      area: 'ชุมชนพูนทรัพย์',
      areaList: ['ชุมชนพูนทรัพย์'],
      program: 'โปรแกรมกินดี อยู่ดี',
      course: 'ปลูกผักสวนครัวเบื้องต้น',
      type: 'กิจกรรม',
      participantType: 'กลุ่มตัวอย่าง',
      format: 'WORKSHOP',
      dataSource: 'ลงทะเบียนออนไลน์',
      targetGroups: ['วัยทำงาน'],
      startDate: '2026-08-10',
      endDate: '2026-08-10',
      time: '09:00 - 12:00',
      capacity: 40,
      registered: 32,
      status: 'เปิดรับสมัคร',
      hasFee: false,
      fee: 0,
      organizer: 'The Farm Concept ร่วมกับสำนักงานเขตสายไหม',
      instructor: 'ดร.กิตติพงศ์ วัฒนสุข',
      instructorList: ['ดร.กิตติพงศ์ วัฒนสุข'],
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
      area: 'The Farm Concept',
      areaList: ['The Farm Concept'],
      program: 'โปรแกรมกินดี อยู่ดี',
      course: 'รู้จักอาหารหลัก 5 หมู่',
      type: 'กิจกรรม',
      participantType: 'กลุ่มทั่วไป',
      format: 'WORKSHOP',
      dataSource: 'ลงทะเบียนออนไลน์',
      targetGroups: ['วัยทำงาน', 'ผู้สูงอายุ'],
      startDate: '2026-08-17',
      endDate: '2026-08-17',
      time: '09:00 - 15:00',
      capacity: 30,
      registered: 30,
      status: 'เต็มแล้ว',
      hasFee: true,
      fee: 200,
      organizer: 'The Farm Concept',
      instructor: 'อาจารย์พิมพ์ชนก ศรีสมบัติ',
      instructorList: ['อาจารย์พิมพ์ชนก ศรีสมบัติ'],
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
      area: 'ชุมชนตึกร้าง',
      areaList: ['ชุมชนตึกร้าง'],
      program: 'โปรแกรมปลูกกินเอง',
      course: 'ทำปุ๋ยหมักจากเศษอาหาร',
      type: 'กิจกรรม',
      participantType: 'กลุ่มทั่วไป',
      format: 'MIND',
      dataSource: 'ลงทะเบียนหน้างาน',
      targetGroups: ['วัยทำงาน'],
      startDate: '2026-08-24',
      endDate: '2026-09-07',
      time: '09:00 - 12:00',
      capacity: 25,
      registered: 9,
      status: 'เปิดรับสมัคร',
      hasFee: false,
      fee: 0,
      organizer: 'The Farm Concept ร่วมกับชุมชนตึกร้าง',
      instructor: 'ดร.กิตติพงศ์ วัฒนสุข',
      instructorList: ['ดร.กิตติพงศ์ วัฒนสุข'],
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
      area: 'ชุมชนพูนทรัพย์',
      areaList: ['ชุมชนพูนทรัพย์', 'ชุมชนตึกร้าง'],
      program: 'โปรแกรมกินดี อยู่ดี',
      course: 'ลดหวาน มัน เค็ม',
      type: 'อีเว้นท์',
      participantType: 'กลุ่มตัวอย่าง',
      format: 'COMMUNITY',
      dataSource: 'นำเข้าจากไฟล์',
      targetGroups: ['ผู้สูงอายุ', 'วัยทำงาน'],
      startDate: '2026-07-20',
      endDate: '2026-07-20',
      time: '09:00 - 16:00',
      capacity: 50,
      registered: 47,
      status: 'ดำเนินการเสร็จสิ้น',
      hasFee: false,
      fee: 0,
      organizer: 'The Farm Concept',
      instructor: 'คุณกัญญารัตน์ มีสุข',
      instructorList: ['คุณกัญญารัตน์ มีสุข'],
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
      area: 'ชุมชนตึกร้าง',
      areaList: ['ชุมชนตึกร้าง'],
      program: 'โปรแกรมปลูกกินเอง',
      course: '',
      type: 'อีเว้นท์',
      participantType: 'กลุ่มทั่วไป',
      format: 'COMMUNITY',
      dataSource: 'บันทึกโดยเจ้าหน้าที่',
      targetGroups: ['เด็กและเยาวชน', 'วัยทำงาน', 'ผู้สูงอายุ'],
      startDate: '2026-09-05',
      endDate: '2026-09-05',
      time: '08:00 - 12:00',
      capacity: 60,
      registered: 4,
      status: 'ฉบับร่าง',
      hasFee: false,
      fee: 0,
      organizer: 'The Farm Concept ร่วมกับชุมชนตึกร้าง',
      instructor: 'คุณปกรณ์ชัย ใจดี',
      instructorList: ['คุณปกรณ์ชัย ใจดี'],
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
  activityTypes: ['กิจกรรม', 'อีเว้นท์'],
  activityParticipantTypes: ['กลุ่มตัวอย่าง', 'กลุ่มทั่วไป'],
  activityDataSources: ['ลงทะเบียนออนไลน์', 'ลงทะเบียนหน้างาน', 'นำเข้าจากไฟล์', 'บันทึกโดยเจ้าหน้าที่'],
  activityVenueModes: ['จัดในพื้นที่ (Onsite)', 'ออนไลน์', 'ผสมผสาน (Hybrid)'],

  /* วิธีเข้าร่วม: ลงทะเบียนล่วงหน้า หรือเดินเข้าร่วมได้เลย */
  activityRegistrationModes: [
    { value: 'เปิดให้ลงทะเบียนล่วงหน้า', hint: 'ผู้เข้าร่วมจองที่นั่งผ่านหน้าเว็บ' },
    { value: 'เข้าร่วมได้เลย (Walk-in)', hint: 'ไม่ต้องลงทะเบียนล่วงหน้า' }
  ],

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
      id: 'AREA-001', name: 'The Farm Concept', province: 'กรุงเทพมหานคร', district: 'เขตบางนา',
      areaType: 'เอกชน', areaGroup: 'พื้นที่ต้นแบบ',
      startDate: '2024-06-01', endDate: '', partnerOrg: 'สสส. พลเมืองอาสา',
      coordinator: 'วีระ ศรีสมบัติ', coordinatorPhone: '082-222-3333', coordinatorPosition: 'หัวหน้าพื้นที่ต้นแบบ',
      mapUrl: 'https://maps.google.com/?q=The+Farm+Concept+บางนา', status: 'ดำเนินการอยู่',
      activityCount: 6, totalParticipants: 172, avgSatisfaction: 4.6,
      updatedAt: '2026-08-01', updatedBy: 'แอมมี่'
    },
    {
      id: 'AREA-002', name: 'ชุมชนพูนทรัพย์', province: 'กรุงเทพมหานคร', district: 'เขตสายไหม',
      areaType: 'ชุมชน/หมู่บ้าน', areaGroup: 'พื้นที่ต้นแบบส่วนขยาย',
      startDate: '2025-01-15', endDate: '', partnerOrg: 'สสส. พลเมืองอาสา',
      coordinator: 'อรุณี ทองสุข', coordinatorPhone: '081-111-2222', coordinatorPosition: 'ผู้ประสานงานชุมชน',
      mapUrl: 'https://maps.google.com/?q=ชุมชนพูนทรัพย์+เขตสายไหม', status: 'ดำเนินการอยู่',
      activityCount: 4, totalParticipants: 98, avgSatisfaction: 4.7,
      updatedAt: '2026-07-28', updatedBy: 'แอมมี่'
    },
    {
      id: 'AREA-003', name: 'ชุมชนตึกร้าง', province: 'กรุงเทพมหานคร', district: 'เขตบางพลัด',
      areaType: 'ชุมชน/หมู่บ้าน', areaGroup: 'พื้นที่ต้นแบบส่วนขยาย',
      startDate: '2025-03-10', endDate: '', partnerOrg: 'สสส. พลเมืองอาสา',
      coordinator: 'ปิยะดา รุ่งเรือง', coordinatorPhone: '083-333-4444', coordinatorPosition: 'ผู้ประสานงานชุมชน',
      mapUrl: 'https://maps.google.com/?q=ชุมชนตึกร้าง+เขตบางพลัด', status: 'ดำเนินการอยู่',
      activityCount: 3, totalParticipants: 41, avgSatisfaction: 4.3,
      updatedAt: '2026-07-15', updatedBy: 'วีระ ศรีสมบัติ'
    }
  ],

  areaTypes: ['เอกชน', 'ชุมชน/หมู่บ้าน', 'โรงเรียน', 'สถานประกอบการเอกชน', 'โรงพยาบาล'],
  areaGroups: ['พื้นที่ต้นแบบ', 'พื้นที่ต้นแบบส่วนขยาย', 'พื้นที่จัดกิจกรรม'],
  areaStatuses: ['รอดำเนินงาน', 'ดำเนินการอยู่', 'สิ้นสุดแล้ว'],

  /* สถานะพร้อมสี badge สำหรับ Dropdown สถานะในตาราง (ใช้กับ TFC.statusSelectHTML) */
  areaStatusList: [
    { value: 'รอดำเนินงาน', badge: 'badge-info' },
    { value: 'ดำเนินการอยู่', badge: 'badge-success' },
    { value: 'สิ้นสุดแล้ว', badge: 'badge-neutral' }
  ],

  /* ข้อมูลพื้นฐาน (โปรแกรม/วิทยากร/กลุ่มเป้าหมาย/รูปแบบกิจกรรม) ใช้สถานะใช้งาน-ไม่ใช้งาน */
  masterActiveStatuses: [
    { value: 'ใช้งาน', badge: 'badge-success' },
    { value: 'ไม่ใช้งาน', badge: 'badge-neutral' }
  ],

  provinceDistricts: {
    'กรุงเทพมหานคร': ['เขตบางนา', 'เขตสายไหม', 'เขตบางพลัด', 'เขตบางเขน', 'เขตดอนเมือง', 'เขตจตุจักร'],
    'ปทุมธานี': ['อำเภอเมืองปทุมธานี', 'อำเภอคลองหลวง', 'อำเภอลำลูกกา', 'อำเภอธัญบุรี'],
    'นนทบุรี': ['อำเภอเมืองนนทบุรี', 'อำเภอปากเกร็ด', 'อำเภอบางบัวทอง'],
    'สมุทรปราการ': ['อำเภอเมืองสมุทรปราการ', 'อำเภอบางพลี', 'อำเภอบางบ่อ']
  },

  targetGroups: [
    { id: 'TG-001', name: 'เด็กและเยาวชน', ageRange: '6-18 ปี', targetCount: 5000, memberCount: 84, avgScoreChange: 0.6, active: true, updatedAt: '2026-07-20', updatedBy: 'สุนิสา แก้วมณี' },
    { id: 'TG-002', name: 'วัยทำงาน', ageRange: '19-59 ปี', targetCount: 2000, memberCount: 156, avgScoreChange: 0.9, active: true, updatedAt: '2026-07-22', updatedBy: 'สุนิสา แก้วมณี' },
    { id: 'TG-003', name: 'ผู้สูงอายุ', ageRange: '60 ปีขึ้นไป', targetCount: 1000, memberCount: 97, avgScoreChange: 1.2, active: true, updatedAt: '2026-07-18', updatedBy: 'วีระ ศรีสมบัติ' },
    { id: 'TG-004', name: 'กลุ่มเปราะบาง', ageRange: 'ทุกช่วงวัย', targetCount: 1000, memberCount: 32, avgScoreChange: 0.8, active: true, updatedAt: '2026-08-02', updatedBy: 'สุนิสา แก้วมณี' }
  ],

  sampleGroups: [
    { name: 'กลุ่มตัวอย่างติดตามผล รุ่นที่ 1', activityName: 'กิจกรรมฟื้นฟูสุขภาวะชุมชน', targetGroupName: 'ผู้สูงอายุ', sampleSize: 20, trackedCount: 12, avgScoreChange: 1.1 },
    { name: 'กลุ่มตัวอย่างติดตามผล รุ่นที่ 2', activityName: 'ปลูกผักปลอดสารสำหรับครอบครัว', targetGroupName: 'วัยทำงาน', sampleSize: 15, trackedCount: 3, avgScoreChange: 0.7 },
    { name: 'กลุ่มตัวอย่างติดตามผล รุ่นที่ 3', activityName: 'Workshop อาหารสุขภาพจากสวน', targetGroupName: 'เด็กและเยาวชน', sampleSize: 10, trackedCount: 10, avgScoreChange: 0.9 }
  ],

  scoreTrend: [
    { period: 'ก่อนเข้าร่วมกิจกรรม', score: 3.2 },
    { period: 'หลังเข้าร่วมกิจกรรมทันที', score: 4.5 },
    { period: 'ติดตามผล 3 เดือน', score: 4.1 },
    { period: 'ติดตามผล 6 เดือน', score: 4.3 },
    { period: 'ติดตามผล 12 เดือน', score: 4.4 }
  ],

  participantsSummary: [
    { name: 'สมชาย ใจงาม', area: 'ชุมชนพูนทรัพย์', targetGroup: 'วัยทำงาน', activitiesJoined: 3, avgSatisfaction: 4.6, followUpStatus: 'ติดตามตามกำหนด' },
    { name: 'วิภาดา สายใจ', area: 'ชุมชนพูนทรัพย์', targetGroup: 'วัยทำงาน', activitiesJoined: 2, avgSatisfaction: 4.2, followUpStatus: 'ติดตามตามกำหนด' },
    { name: 'อดิศักดิ์ พูลสวัสดิ์', area: 'ชุมชนพูนทรัพย์', targetGroup: 'ผู้สูงอายุ', activitiesJoined: 1, avgSatisfaction: 4.8, followUpStatus: 'เกินกำหนดติดตามผล' },
    { name: 'ประภาส ทองแท้', area: 'The Farm Concept', targetGroup: 'เด็กและเยาวชน', activitiesJoined: 1, avgSatisfaction: 4.4, followUpStatus: 'ติดตามครบแล้ว' },
    { name: 'กัลยา รุ่งเจริญ', area: 'ชุมชนตึกร้าง', targetGroup: 'วัยทำงาน', activitiesJoined: 1, avgSatisfaction: 0, followUpStatus: 'ยังไม่เข้าร่วมกิจกรรม' }
  ],

  users: [
    { id: 'USR-001', name: 'สุนิสา แก้วมณี', username: 'sunisa01', avatar: '', email: 'sunisa@thefarmconcept.org', role: 'เจ้าหน้าที่โครงการ', roles: ['เจ้าหน้าที่โครงการ'], area: 'ชุมชนพูนทรัพย์', status: 'ใช้งานอยู่', lastLogin: '2026-08-03' },
    { id: 'USR-002', name: 'วีระ ศรีสมบัติ', username: 'weera02', avatar: '', email: 'weera@thefarmconcept.org', role: 'ผู้ดูแลโครงการ', roles: ['ผู้ดูแลโครงการ', 'เจ้าหน้าที่โครงการ'], area: 'The Farm Concept', status: 'ใช้งานอยู่', lastLogin: '2026-08-02' },
    { id: 'USR-003', name: 'ปิยะดา รุ่งเรือง', username: 'piyada03', avatar: '', email: 'piyada@thefarmconcept.org', role: 'เจ้าหน้าที่โครงการ', roles: ['เจ้าหน้าที่โครงการ'], area: 'ชุมชนตึกร้าง', status: 'ระงับการใช้งาน', lastLogin: '2026-07-20' },
    { id: 'USR-004', name: 'ธนากร ใจดี', username: 'thanakorn04', avatar: '', email: 'thanakorn@thefarmconcept.org', role: 'เจ้าหน้าที่โครงการ', roles: ['เจ้าหน้าที่โครงการ'], area: 'ชุมชนตึกร้าง', status: 'ใช้งานอยู่', lastLogin: '2026-08-01' },
    { id: 'USR-005', name: 'อรุณี ทองสุข', username: 'arunee05', avatar: '', email: 'arunee@thefarmconcept.org', role: 'ผู้ดูแลระบบสูงสุด', roles: ['ผู้ดูแลระบบสูงสุด'], area: 'ส่วนกลาง', status: 'ใช้งานอยู่', lastLogin: '2026-08-03' }
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
        'dashboard': true,
        'activities': true, 'activities-list': true, 'activities-registrants': true, 'activities-checkin': true,
        'master-data': true, 'master-data-areas': true, 'master-data-target-groups': true, 'master-data-programs': true, 'master-data-instructors': true, 'master-data-activity-formats': true,
        'users': true, 'users-list': true, 'users-roles': true
      }
    },
    {
      id: 'ROLE-002', name: 'ผู้ดูแลโครงการ', code: 'project_admin',
      description: 'จัดการพื้นที่ กิจกรรม และรายงานภายในโครงการที่รับผิดชอบ',
      userCount: 2, active: true,
      permissions: { project: false, users: true, areas: true, master_data: true, activities: true, payments: true, evaluations: true, reports: true },
      /* เมนู "ผู้ใช้งาน" แสดงให้บทบาทนี้อยู่แล้วในแถบเมนูซ้าย จึงต้องเปิดสิทธิ์ให้ตรงกัน
         ไม่งั้นเข้าหน้าได้แต่เมนู ⋮ ในตารางขึ้น "ไม่มีสิทธิ์ดำเนินการ" */
      menuPermissions: {
        'dashboard': true,
        'activities': true, 'activities-list': true, 'activities-registrants': true, 'activities-checkin': true,
        'master-data': true, 'master-data-areas': true, 'master-data-target-groups': true, 'master-data-programs': true, 'master-data-instructors': true, 'master-data-activity-formats': true,
        'users': true, 'users-list': true, 'users-roles': true
      }
    },
    {
      id: 'ROLE-003', name: 'เจ้าหน้าที่โครงการ', code: 'staff',
      description: 'จัดการกิจกรรม ลงทะเบียน ตรวจสอบการชำระเงิน และติดตามผลในพื้นที่ที่รับผิดชอบ',
      userCount: 3, active: true,
      permissions: { project: false, users: false, areas: false, master_data: false, activities: true, payments: true, evaluations: true, reports: false },
      menuPermissions: {
        'dashboard': true,
        'activities': true, 'activities-list': true, 'activities-registrants': true, 'activities-checkin': true,
        'master-data': false, 'master-data-areas': false, 'master-data-target-groups': false, 'master-data-programs': false, 'master-data-instructors': false, 'master-data-activity-formats': false,
        'users': false, 'users-list': false, 'users-roles': false
      }
    },
    {
      id: 'ROLE-004', name: 'ผู้เข้าร่วมกิจกรรม', code: 'participant',
      description: 'ลงทะเบียนกิจกรรม แนบหลักฐานการชำระเงิน และทำแบบประเมิน',
      userCount: 337, active: true,
      permissions: { project: false, users: false, areas: false, master_data: false, activities: false, payments: false, evaluations: false, reports: false },
      menuPermissions: {
        'dashboard': false,
        'activities': false, 'activities-list': false, 'activities-registrants': false, 'activities-checkin': false,
        'master-data': false, 'master-data-areas': false, 'master-data-target-groups': false, 'master-data-programs': false, 'master-data-instructors': false, 'master-data-activity-formats': false,
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
    { key: 'evaluations', label: 'ประเมินสุขภาพ' },
    /* เมนู "รายงาน" ถูกลบออกจากระบบแล้ว สิทธิ์นี้จึงเหลือหน้าที่คุมปุ่ม Export
       และปุ่ม "รายงานผล" ของโมดูลแบบฟอร์มเท่านั้น */
    { key: 'reports', label: 'ดูรายงานผลและส่งออกข้อมูล' }
  ],

  programs: [
    {
      id: 'PROG-001', name: 'โปรแกรมกินดี อยู่ดี', category: 'โภชนาการ', activityCount: 2, status: 'ดำเนินการอยู่', active: true,
      courses: [
        { order: 1, name: 'รู้จักอาหารหลัก 5 หมู่' },
        { order: 2, name: 'ผัก 5 สี สุขภาพดีทุกวัน' },
        { order: 3, name: 'ลดหวาน มัน เค็ม' },
        { order: 4, name: 'อ่านฉลากอาหารให้เป็น' }
      ],
      updatedAt: '2026-07-30', updatedBy: 'สุนิสา แก้วมณี'
    },
    {
      id: 'PROG-002', name: 'โปรแกรมปลูกกินเอง', category: 'เกษตรและอาหาร', activityCount: 2, status: 'ดำเนินการอยู่', active: true,
      courses: [
        { order: 1, name: 'ปลูกผักสวนครัวเบื้องต้น' },
        { order: 2, name: 'ปลูกผักในพื้นที่จำกัด' },
        { order: 3, name: 'ทำปุ๋ยหมักจากเศษอาหาร' },
        { order: 4, name: 'จากแปลงสู่จาน' }
      ],
      updatedAt: '2026-07-25', updatedBy: 'วีระ ศรีสมบัติ'
    },
    {
      id: 'PROG-003', name: 'โปรแกรม Food Literacy', category: 'ความรอบรู้ด้านอาหาร', activityCount: 1, status: 'ดำเนินการอยู่', active: true,
      courses: [
        { order: 1, name: 'รู้เลือก รู้กิน' },
        { order: 2, name: 'จ่ายตลาดอย่างฉลาด' },
        { order: 3, name: 'รู้จักอาหารปลอดภัย' },
        { order: 4, name: 'วางแผนมื้ออาหารสุขภาพ' }
      ],
      updatedAt: '2026-08-02', updatedBy: 'สุนิสา แก้วมณี'
    },
    {
      id: 'PROG-004', name: 'โปรแกรมครัวสุขภาวะ', category: 'ครัวและการปรุงอาหาร', activityCount: 0, status: 'ดำเนินการอยู่', active: true,
      courses: [
        { order: 1, name: 'เมนูสุขภาพทำง่าย' },
        { order: 2, name: 'Cooking Workshop ลดหวาน มัน เค็ม' },
        { order: 3, name: 'อาหารสำหรับครอบครัว' },
        { order: 4, name: 'ครัวชุมชนเพื่อสุขภาวะ' }
      ],
      updatedAt: '2026-08-05', updatedBy: 'วีระ ศรีสมบัติ'
    }
  ],

  instructors: [
    {
      id: 'INS-001', name: 'ดร.กิตติพงศ์ วัฒนสุข', phone: '08x-xxx-1111', activityCount: 4, active: true,
      photo: '', expertise: 'ผู้เชี่ยวชาญด้านโภชนาการและสุขภาวะ',
      expertiseList: ['โภชนาการ', 'สุขภาวะชุมชน'],
      courseList: ['รู้จักอาหารหลัก 5 หมู่', 'อ่านฉลากอาหารให้เป็น'],
      bio: '',
      updatedAt: '2026-07-28', updatedBy: 'สุนิสา แก้วมณี'
    },
    {
      id: 'INS-002', name: 'อาจารย์พิมพ์ชนก ศรีสมบัติ', phone: '08x-xxx-2222', activityCount: 3, active: true,
      photo: '', expertise: 'วิทยากรด้านอาหารและการปรับเปลี่ยนพฤติกรรม',
      expertiseList: ['อาหารเพื่อสุขภาพ', 'การปรับเปลี่ยนพฤติกรรม'],
      courseList: ['ผัก 5 สี สุขภาพดีทุกวัน', 'ลดหวาน มัน เค็ม', 'รู้เลือก รู้กิน'],
      bio: '',
      updatedAt: '2026-07-20', updatedBy: 'สุนิสา แก้วมณี'
    },
    {
      id: 'INS-003', name: 'คุณภูริณัฐ วงศ์สวัสดิ์', phone: '08x-xxx-3333', activityCount: 2, active: true,
      photo: '', expertise: 'วิทยากรด้านสุขภาพและการออกกำลังกาย',
      expertiseList: ['สุขภาพ', 'การออกกำลังกาย'],
      courseList: ['วางแผนมื้ออาหารสุขภาพ'],
      bio: '',
      updatedAt: '2026-07-10', updatedBy: 'วีระ ศรีสมบัติ'
    },
    {
      id: 'INS-004', name: 'คุณกัญญารัตน์ มีสุข', phone: '08x-xxx-4444', activityCount: 2, active: true,
      photo: '', expertise: 'วิทยากรด้านการดูแลสุขภาวะครอบครัว',
      expertiseList: ['สุขภาวะครอบครัว', 'การดูแลผู้สูงอายุ'],
      courseList: ['ปลูกผักสวนครัวเบื้องต้น', 'จากแปลงสู่จาน'],
      bio: '',
      updatedAt: '2026-08-01', updatedBy: 'สุนิสา แก้วมณี'
    },
    {
      id: 'INS-005', name: 'คุณปกรณ์ชัย ใจดี', phone: '08x-xxx-5555', activityCount: 1, active: true,
      photo: '', expertise: 'วิทยากรด้านการแปรรูปผลิตภัณฑ์ชุมชน',
      expertiseList: ['การแปรรูปอาหาร', 'ผลิตภัณฑ์ชุมชน'],
      courseList: ['ทำปุ๋ยหมักจากเศษอาหาร', 'จ่ายตลาดอย่างฉลาด'],
      bio: '',
      updatedAt: '2026-08-03', updatedBy: 'วีระ ศรีสมบัติ'
    }
  ],

  activityFormats: [
    { id: 'FMT-001', name: 'CRAFT', active: true, icon: 'craft', updatedAt: '2026-07-15', updatedBy: 'สุนิสา แก้วมณี' },
    { id: 'FMT-002', name: 'MIND', active: true, icon: 'heart', updatedAt: '2026-07-15', updatedBy: 'สุนิสา แก้วมณี' },
    { id: 'FMT-003', name: 'FOOD', active: true, icon: 'food', updatedAt: '2026-07-10', updatedBy: 'วีระ ศรีสมบัติ' },
    { id: 'FMT-004', name: 'WORKSHOP', active: true, icon: 'tool', updatedAt: '2026-07-20', updatedBy: 'สุนิสา แก้วมณี' },
    { id: 'FMT-005', name: 'COMMUNITY', active: true, icon: 'users', updatedAt: '2026-07-22', updatedBy: 'วีระ ศรีสมบัติ' }
  ],

  /* ไอคอนหมวดหมู่กิจกรรม — เก็บเฉพาะเนื้อใน <svg> วาดด้วยเส้น (stroke) เหมือนไอคอนอื่นในระบบ */
  activityCategoryIcons: [
    { value: 'leaf', label: 'ใบไม้', path: '<path d="M11 20A7 7 0 019.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>' },
    { value: 'sprout', label: 'ต้นกล้า', path: '<path d="M7 20h10"/><path d="M12 20V9"/><path d="M12 9C12 6 9.5 3.5 5 3.5c0 4.5 2.5 7 7 7z"/><path d="M12 12c0-2.5 2-5 6-5 0 3.5-2.5 5.5-6 5.5z"/>' },
    { value: 'food', label: 'อาหาร', path: '<path d="M3 2v7a2 2 0 002 2h1a2 2 0 002-2V2"/><path d="M5.5 2v20"/><path d="M21 14V2a5 5 0 00-4 4.9V12a2 2 0 002 2h2zm0 0v8"/>' },
    { value: 'coffee', label: 'เครื่องดื่ม', path: '<path d="M17 8h1a4 4 0 010 8h-1"/><path d="M3 8h14v9a4 4 0 01-4 4H7a4 4 0 01-4-4z"/><path d="M6 2v3M10 2v3M14 2v3"/>' },
    { value: 'craft', label: 'งานฝีมือ', path: '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M20 4L8.12 15.88M14.47 14.48L20 20M8.12 8.12L12 12"/>' },
    { value: 'tool', label: 'เวิร์กช็อป', path: '<path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>' },
    { value: 'heart', label: 'สุขภาพใจ', path: '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0016.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 002 8.5c0 2.3 1.5 4.05 3 5.5l7 7z"/>' },
    { value: 'users', label: 'ชุมชน', path: '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>' },
    { value: 'book', label: 'เรียนรู้', path: '<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>' },
    { value: 'flask', label: 'ทดลอง', path: '<path d="M9 3h6M10 3v6.5L4.6 18.3A2 2 0 006.3 21h11.4a2 2 0 001.7-2.7L14 9.5V3"/><path d="M7 15h10"/>' },
    { value: 'sun', label: 'กลางแจ้ง', path: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>' },
    { value: 'droplet', label: 'น้ำ', path: '<path d="M12 2.7l5.66 5.65a8 8 0 11-11.31 0z"/>' },
    { value: 'home', label: 'ในร่ม', path: '<path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1z"/><path d="M9 21v-7h6v7"/>' },
    { value: 'camera', label: 'ถ่ายภาพ', path: '<path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/>' },
    { value: 'music', label: 'ดนตรี', path: '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>' },
    { value: 'star', label: 'พิเศษ', path: '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14l-5-4.87 6.91-1.01z"/>' }
  ],

  /* ==========================================================================
     ความพึงพอใจ — หัวข้อประเมิน + คำตอบรายคน
     คำตอบรายคนคือ "แหล่งข้อมูลเดียว" ของหน้าความพึงพอใจ ทุกตัวเลขบนหน้าจอ
     (คะแนนเฉลี่ย · คะแนนรายหัวข้อ · การกระจายดาว · จำนวนผู้ตอบ · ความเห็น)
     คำนวณจากชุดนี้ทั้งหมด — ห้ามเก็บค่าเฉลี่ยหรือ distribution ไว้ซ้ำที่อื่น
     เพราะจะทำให้ตัวเลขในการ์ดกับตารางขัดกันเองเมื่อข้อมูลเปลี่ยน

     แบบประเมินไม่เก็บ user_id / ชื่อ / เบอร์โทร โดยเจตนา — ระบุตัวตนผู้ตอบไม่ได้
     เลขที่แสดงในตาราง ("ผู้ตอบ #N") เป็นลำดับที่คำนวณตอนแสดงผลเท่านั้น
     ========================================================================== */
  satisfactionTopics: [
    { key: 'overall', label: 'ความพึงพอใจโดยรวม', short: 'ภาพรวม' },
    { key: 'content', label: 'เนื้อหาตรงกับที่คาดหวัง', short: 'เนื้อหา' },
    { key: 'speaker', label: 'วิทยากรอธิบายเข้าใจง่าย', short: 'วิทยากร' },
    { key: 'duration', label: 'ระยะเวลาที่ใช้เหมาะสม', short: 'เวลา' },
    { key: 'venue', label: 'สถานที่และการต้อนรับ', short: 'สถานที่' }
  ],

  /* scores เรียงตามลำดับเดียวกับ satisfactionTopics · comment เว้นว่างได้ (ไม่บังคับตอบ) */
  satisfactionResponses: [
    /* ACT-2026-014 — เข้าร่วมจริง 10 คน ตอบ 6 คน = 60% (ต่ำกว่าเกณฑ์ 70% การ์ดจะเป็นสีเหลือง) */
    { id: 'SAT-014-01', activityId: 'ACT-2026-014', scores: [5, 4, 5, 4, 4], comment: 'ได้ลงมือทำจริงทุกขั้นตอน กลับบ้านแล้วทำต่อได้เลย ชอบที่มีชุดเพาะกล้าให้ด้วย', submittedAt: '2026-08-10T12:45' },
    { id: 'SAT-014-02', activityId: 'ACT-2026-014', scores: [5, 5, 5, 4, 5], comment: 'วิทยากรอธิบายเข้าใจง่าย ถามอะไรก็ตอบได้หมด', submittedAt: '2026-08-10T12:41' },
    { id: 'SAT-014-03', activityId: 'ACT-2026-014', scores: [4, 4, 4, 3, 4], comment: 'สถานที่ร่มรื่นดี แต่ที่จอดรถน้อย มาสายเลยหาที่จอดยาก', submittedAt: '2026-08-10T12:37' },
    { id: 'SAT-014-04', activityId: 'ACT-2026-014', scores: [5, 5, 5, 3, 5], comment: 'เนื้อหาดีมาก แต่ช่วงลงแปลงจริงเวลาน้อยไปหน่อย อยากให้เพิ่มเป็นครึ่งวัน', submittedAt: '2026-08-10T12:33' },
    { id: 'SAT-014-05', activityId: 'ACT-2026-014', scores: [3, 3, 4, 2, 3], comment: 'อยากให้มีเอกสารสรุปส่งทางไลน์หลังจบกิจกรรมด้วย', submittedAt: '2026-08-10T12:29' },
    { id: 'SAT-014-06', activityId: 'ACT-2026-014', scores: [5, 4, 5, 4, 4], comment: '', submittedAt: '2026-08-10T12:25' },

    /* ACT-2026-015 — เข้าร่วมจริง 8 คน ตอบ 7 คน = 88% */
    { id: 'SAT-015-01', activityId: 'ACT-2026-015', scores: [5, 5, 5, 5, 4], comment: 'เมนูที่สอนทำตามได้จริง วัตถุดิบหาซื้อง่าย', submittedAt: '2026-08-17T15:20' },
    { id: 'SAT-015-02', activityId: 'ACT-2026-015', scores: [4, 4, 5, 4, 4], comment: '', submittedAt: '2026-08-17T15:16' },
    { id: 'SAT-015-03', activityId: 'ACT-2026-015', scores: [5, 4, 5, 3, 5], comment: 'ครึ่งวันแรกแน่นไปนิด อยากให้พักนานกว่านี้', submittedAt: '2026-08-17T15:12' },
    { id: 'SAT-015-04', activityId: 'ACT-2026-015', scores: [4, 5, 4, 4, 4], comment: 'ชอบที่ได้ชิมทุกเมนูที่ทำ', submittedAt: '2026-08-17T15:08' },
    { id: 'SAT-015-05', activityId: 'ACT-2026-015', scores: [5, 5, 5, 4, 5], comment: '', submittedAt: '2026-08-17T15:04' },
    { id: 'SAT-015-06', activityId: 'ACT-2026-015', scores: [3, 3, 4, 3, 3], comment: 'ห้องครัวคนเยอะ แย่งอุปกรณ์กันนิดหน่อย', submittedAt: '2026-08-17T15:00' },
    { id: 'SAT-015-07', activityId: 'ACT-2026-015', scores: [4, 4, 4, 4, 5], comment: '', submittedAt: '2026-08-17T14:56' },

    /* ACT-2026-016 — เข้าร่วมจริง 6 คน ตอบ 3 คน = 50% (ต่ำกว่าเกณฑ์) */
    { id: 'SAT-016-01', activityId: 'ACT-2026-016', scores: [4, 4, 4, 4, 3], comment: 'ได้ความรู้เรื่องอัตราส่วนเศษอาหารกับใบไม้แห้ง เอาไปทำที่บ้านได้', submittedAt: '2026-08-24T12:10' },
    { id: 'SAT-016-02', activityId: 'ACT-2026-016', scores: [3, 3, 3, 2, 3], comment: 'กลิ่นแรงกว่าที่คิด อยากให้เตือนล่วงหน้าและเตรียมถุงมือให้', submittedAt: '2026-08-24T12:06' },
    { id: 'SAT-016-03', activityId: 'ACT-2026-016', scores: [5, 4, 5, 4, 4], comment: '', submittedAt: '2026-08-24T12:02' },

    /* ACT-2026-017 — เข้าร่วมจริง 35 คน ตอบ 26 คน = 74% (ชุดใหญ่ ใช้ทดสอบการแบ่งหน้า 3 หน้า) */
    { id: 'SAT-017-01', activityId: 'ACT-2026-017', scores: [5, 5, 5, 4, 5], comment: 'จัดได้ดีมาก อยากให้มีทุกไตรมาส', submittedAt: '2026-07-20T16:30' },
    { id: 'SAT-017-02', activityId: 'ACT-2026-017', scores: [4, 4, 5, 4, 4], comment: '', submittedAt: '2026-07-20T16:27' },
    { id: 'SAT-017-03', activityId: 'ACT-2026-017', scores: [5, 4, 5, 3, 4], comment: 'ช่วงบ่ายยาวไปหน่อย คนสูงอายุเริ่มล้า', submittedAt: '2026-07-20T16:24' },
    { id: 'SAT-017-04', activityId: 'ACT-2026-017', scores: [5, 5, 5, 5, 5], comment: 'ทีมงานดูแลดีมากตั้งแต่ลงทะเบียนจนจบงาน', submittedAt: '2026-07-20T16:21' },
    { id: 'SAT-017-05', activityId: 'ACT-2026-017', scores: [4, 4, 4, 3, 4], comment: '', submittedAt: '2026-07-20T16:18' },
    { id: 'SAT-017-06', activityId: 'ACT-2026-017', scores: [3, 3, 4, 2, 3], comment: 'เสียงไมค์ไม่ค่อยชัดตอนอยู่หลังห้อง', submittedAt: '2026-07-20T16:15' },
    { id: 'SAT-017-07', activityId: 'ACT-2026-017', scores: [5, 5, 5, 4, 5], comment: '', submittedAt: '2026-07-20T16:12' },
    { id: 'SAT-017-08', activityId: 'ACT-2026-017', scores: [4, 5, 4, 4, 4], comment: 'ได้เจอเพื่อนบ้านที่ไม่เคยคุยกันมาก่อน ชอบบรรยากาศ', submittedAt: '2026-07-20T16:09' },
    { id: 'SAT-017-09', activityId: 'ACT-2026-017', scores: [5, 4, 5, 4, 5], comment: '', submittedAt: '2026-07-20T16:06' },
    { id: 'SAT-017-10', activityId: 'ACT-2026-017', scores: [4, 4, 5, 3, 4], comment: 'อยากให้มีน้ำดื่มวางไว้หลายจุดกว่านี้', submittedAt: '2026-07-20T16:03' },
    { id: 'SAT-017-11', activityId: 'ACT-2026-017', scores: [5, 5, 5, 5, 4], comment: '', submittedAt: '2026-07-20T16:00' },
    { id: 'SAT-017-12', activityId: 'ACT-2026-017', scores: [4, 3, 4, 3, 4], comment: 'กิจกรรมกลุ่มสนุก แต่เวลาน้อยไปนิด', submittedAt: '2026-07-20T15:57' },
    { id: 'SAT-017-13', activityId: 'ACT-2026-017', scores: [5, 5, 5, 4, 5], comment: '', submittedAt: '2026-07-20T15:54' },
    { id: 'SAT-017-14', activityId: 'ACT-2026-017', scores: [4, 4, 4, 4, 3], comment: 'ห้องน้ำอยู่ไกลจากจุดจัดงาน', submittedAt: '2026-07-20T15:51' },
    { id: 'SAT-017-15', activityId: 'ACT-2026-017', scores: [5, 4, 5, 3, 5], comment: '', submittedAt: '2026-07-20T15:48' },
    { id: 'SAT-017-16', activityId: 'ACT-2026-017', scores: [3, 4, 4, 3, 3], comment: 'อาหารกลางวันมาช้า รอนานพอสมควร', submittedAt: '2026-07-20T15:45' },
    { id: 'SAT-017-17', activityId: 'ACT-2026-017', scores: [5, 5, 5, 4, 5], comment: 'ประทับใจช่วงแบ่งปันประสบการณ์ของผู้สูงอายุในชุมชน', submittedAt: '2026-07-20T15:42' },
    { id: 'SAT-017-18', activityId: 'ACT-2026-017', scores: [4, 4, 5, 4, 4], comment: '', submittedAt: '2026-07-20T15:39' },
    { id: 'SAT-017-19', activityId: 'ACT-2026-017', scores: [5, 5, 4, 4, 5], comment: '', submittedAt: '2026-07-20T15:36' },
    { id: 'SAT-017-20', activityId: 'ACT-2026-017', scores: [2, 3, 3, 2, 3], comment: 'ลงทะเบียนหน้างานช้ามาก ต่อคิวเกือบครึ่งชั่วโมง', submittedAt: '2026-07-20T15:33' },
    { id: 'SAT-017-21', activityId: 'ACT-2026-017', scores: [5, 4, 5, 4, 4], comment: '', submittedAt: '2026-07-20T15:30' },
    { id: 'SAT-017-22', activityId: 'ACT-2026-017', scores: [4, 5, 5, 3, 4], comment: 'อยากได้สรุปผลตรวจสุขภาพเป็นไฟล์ด้วย', submittedAt: '2026-07-20T15:27' },
    { id: 'SAT-017-23', activityId: 'ACT-2026-017', scores: [5, 5, 5, 5, 5], comment: '', submittedAt: '2026-07-20T15:24' },
    { id: 'SAT-017-24', activityId: 'ACT-2026-017', scores: [4, 4, 4, 3, 4], comment: '', submittedAt: '2026-07-20T15:21' },
    { id: 'SAT-017-25', activityId: 'ACT-2026-017', scores: [5, 4, 5, 4, 5], comment: 'เจ้าหน้าที่ยิ้มแย้ม ตอบคำถามดี', submittedAt: '2026-07-20T15:18' },
    { id: 'SAT-017-26', activityId: 'ACT-2026-017', scores: [4, 4, 4, 4, 4], comment: '', submittedAt: '2026-07-20T15:15' }

    /* ACT-2026-018 ยังเป็นฉบับร่าง ยังไม่มีผู้ตอบ — ใช้ทดสอบ Empty State */
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
      targetGroup: 'วัยทำงาน', area: 'ชุมชนพูนทรัพย์',
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
      targetGroup: 'วัยทำงาน', area: 'ชุมชนพูนทรัพย์',
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
      targetGroup: 'ผู้สูงอายุ', area: 'ชุมชนพูนทรัพย์',
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
      targetGroup: 'เด็กและเยาวชน', area: 'The Farm Concept',
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
      targetGroup: 'วัยทำงาน', area: 'ชุมชนตึกร้าง',
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
      targetGroup: 'ผู้สูงอายุ', area: 'ชุมชนตึกร้าง',
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
      { id: 'PAH-0001', activityName: 'กิจกรรมฟื้นฟูสุขภาวะชุมชน', location: 'ชุมชนพูนทรัพย์', price: 0, joinDate: '2026-07-20', evaluated: true },
      { id: 'PAH-0002', activityName: 'ปลูกผักปลอดสารสำหรับครอบครัว', location: 'ชุมชนพูนทรัพย์', price: 0, joinDate: '2026-08-10', evaluated: true },
      { id: 'PAH-0003', activityName: 'Workshop อาหารสุขภาพจากสวน', location: 'The Farm Concept', price: 200, joinDate: '2026-08-17', evaluated: false }
    ],
    'PTP-0002': [
      { id: 'PAH-0004', activityName: 'ปลูกผักปลอดสารสำหรับครอบครัว', location: 'ชุมชนพูนทรัพย์', price: 0, joinDate: '2026-08-10', evaluated: true },
      { id: 'PAH-0005', activityName: 'เรียนรู้การทำปุ๋ยหมัก', location: 'ชุมชนตึกร้าง', price: 0, joinDate: '2026-08-24', evaluated: false }
    ],
    'PTP-0003': [
      { id: 'PAH-0006', activityName: 'กิจกรรมฟื้นฟูสุขภาวะชุมชน', location: 'ชุมชนพูนทรัพย์', price: 0, joinDate: '2026-07-20', evaluated: true }
    ],
    'PTP-0004': [
      { id: 'PAH-0007', activityName: 'Workshop อาหารสุขภาพจากสวน', location: 'The Farm Concept', price: 200, joinDate: '2026-08-17', evaluated: true }
    ],
    'PTP-0005': [],
    'PTP-0006': [
      { id: 'PAH-0008', activityName: 'กิจกรรมฟื้นฟูสุขภาวะชุมชน', location: 'ชุมชนพูนทรัพย์', price: 0, joinDate: '2026-07-20', evaluated: false }
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
      { date: '2026-08-10', time: '09:00 - 12:00', location: 'ชุมชนพูนทรัพย์', capacity: 40, registered: 32 }
    ],
    'ACT-2026-015': [
      { date: '2026-08-17', time: '09:00 - 15:00', location: 'The Farm Concept', capacity: 30, registered: 30 }
    ],
    'ACT-2026-016': [
      { date: '2026-08-24', time: '09:00 - 12:00', location: 'ชุมชนตึกร้าง', capacity: 25, registered: 9 },
      { date: '2026-09-07', time: '09:00 - 12:00', location: 'ชุมชนตึกร้าง', capacity: 25, registered: 0 }
    ],
    'ACT-2026-017': [
      { date: '2026-07-20', time: '09:00 - 16:00', location: 'ชุมชนพูนทรัพย์', capacity: 50, registered: 47 }
    ],
    'ACT-2026-018': [
      { date: '2026-09-05', time: '08:00 - 12:00', location: 'ชุมชนตึกร้าง', capacity: 60, registered: 4 }
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

      /* เวลาที่ลงทะเบียนจริง (registeredAtISO) และเวลาที่เช็คอินจริง (checkedInAt)
         ฟิลด์ registeredAt เดิมเป็นวันที่เดียวกันทุกแถว ใช้ทำกราฟรายวัน/รายชั่วโมงไม่ได้
         จึงเพิ่มสองฟิลด์นี้แทน โดยไม่แตะของเดิม เพื่อไม่ให้หน้าที่ใช้ registeredAt อยู่แล้วเปลี่ยนไป
         - ลงทะเบียนกระจายใน 12 วันก่อนวันจัด และเกาะกลุ่มช่วงเย็น ตามพฤติกรรมจริงของผู้ใช้
         - เช็คอินกระจายรอบเวลาเริ่มกิจกรรม โดยส่วนใหญ่มาช่วง 15 นาทีแรก */
      var daysBefore = 12 - Math.floor(Math.pow(rand(), 0.7) * 12);
      var regDate = new Date(activity.startDate);
      regDate.setDate(regDate.getDate() - daysBefore);
      var regHour = pick(rand, [7, 9, 10, 11, 12, 13, 14, 16, 18, 19, 19, 20, 20, 21, 22]);
      var regMin = Math.floor(rand() * 60);

      var startMin = 540;
      var timeMatch = /(\d{1,2})[:.](\d{2})/.exec(activity.time || '');
      if (timeMatch) startMin = Number(timeMatch[1]) * 60 + Number(timeMatch[2]);
      /* -15 ถึง +75 นาทีจากเวลาเริ่ม ถ่วงน้ำหนักให้กระจุกช่วงต้น */
      var checkOffset = Math.round(-15 + Math.pow(rand(), 1.8) * 90);
      var checkAbs = Math.max(0, startMin + checkOffset);

      rows.push({
        registeredAtISO: regDate.toISOString().slice(0, 10) + 'T' +
          String(regHour).padStart(2, '0') + ':' + String(regMin).padStart(2, '0'),
        checkedInAt: checkin === 'เข้าร่วมแล้ว'
          ? String(Math.floor(checkAbs / 60) % 24).padStart(2, '0') + ':' + String(checkAbs % 60).padStart(2, '0')
          : '',
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
        manualEntry: rand() < 0.15,
        /* ข้อจำกัดด้านอาหาร — ฟิลด์ในแบบลงทะเบียน ส่วนใหญ่ไม่ได้กรอก */
        dietaryNote: pick(rand, ['', '', '', '', 'แพ้อาหารทะเล', 'มังสวิรัติ', 'ไม่กินเนื้อวัว', 'แพ้ถั่ว'])
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
