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

  activities: [
    {
      id: 'ACT-2026-014',
      name: 'ปลูกผักปลอดสารสำหรับครอบครัว',
      area: 'ชุมชนพูนทรัพย์ เขตสายไหม',
      program: 'โครงการฟื้นฟูสุขภาวะชุมชน',
      startDate: '2026-08-10',
      endDate: '2026-08-10',
      time: '09:00 - 12:00',
      capacity: 40,
      registered: 32,
      status: 'เปิดรับสมัคร',
      fee: 0,
      organizer: 'The Farm Concept ร่วมกับสำนักงานเขตสายไหม',
      instructor: 'อาจารย์สมพงษ์ ปลูกดี',
      tags: ['WORKSHOP', 'CRAFT'],
      description: 'กิจกรรมเรียนรู้การปลูกผักปลอดสารพิษ เหมาะสำหรับครอบครัวที่ต้องการเริ่มต้นปลูกผักไว้รับประทานเอง ผู้เข้าร่วมจะได้ลงมือปฏิบัติจริงตั้งแต่การเตรียมดิน เพาะกล้า จนถึงการดูแลรักษา โดยวิทยากรผู้เชี่ยวชาญจาก The Farm Concept'
    },
    {
      id: 'ACT-2026-015',
      name: 'Workshop อาหารสุขภาพจากสวน',
      area: 'ศูนย์การเรียนรู้ The Farm Concept',
      program: 'โครงการฟื้นฟูสุขภาวะชุมชน',
      startDate: '2026-08-17',
      endDate: '2026-08-17',
      time: '09:00 - 15:00',
      capacity: 30,
      registered: 30,
      status: 'เต็มแล้ว',
      fee: 200,
      organizer: 'The Farm Concept',
      instructor: 'คุณนภา ทำอาหารเพื่อสุขภาพ',
      tags: ['FOOD', 'WORKSHOP'],
      description: 'เรียนรู้การนำผักและสมุนไพรจากสวนมาปรุงเป็นเมนูอาหารเพื่อสุขภาพ พร้อมความรู้ด้านโภชนาการที่เหมาะกับทุกวัย ลงมือทำจริงและได้ชิมเมนูที่ปรุงเองในวันงาน'
    },
    {
      id: 'ACT-2026-016',
      name: 'เรียนรู้การทำปุ๋ยหมัก',
      area: 'ชุมชนหนองแขม',
      program: 'โครงการเกษตรเพื่อสุขภาพ',
      startDate: '2026-08-24',
      endDate: '2026-08-24',
      time: '09:00 - 12:00',
      capacity: 25,
      registered: 9,
      status: 'เปิดรับสมัคร',
      fee: 0,
      organizer: 'The Farm Concept ร่วมกับชุมชนหนองแขม',
      instructor: 'อาจารย์สมพงษ์ ปลูกดี',
      tags: ['CRAFT', 'WORKSHOP'],
      description: 'อบรมเชิงปฏิบัติการทำปุ๋ยหมักจากเศษอาหารและวัสดุเหลือใช้ในครัวเรือน ลดขยะ เพิ่มความอุดมสมบูรณ์ให้ดิน เหมาะสำหรับผู้เริ่มต้นปลูกผักที่บ้าน'
    },
    {
      id: 'ACT-2026-017',
      name: 'กิจกรรมฟื้นฟูสุขภาวะชุมชน',
      area: 'ชุมชนพูนทรัพย์ เขตสายไหม',
      program: 'โครงการฟื้นฟูสุขภาวะชุมชน',
      startDate: '2026-07-20',
      endDate: '2026-07-20',
      time: '09:00 - 16:00',
      capacity: 50,
      registered: 47,
      status: 'ดำเนินการเสร็จสิ้น',
      fee: 0,
      organizer: 'The Farm Concept',
      instructor: 'ทีมงาน The Farm Concept',
      tags: ['MIND', 'WORKSHOP'],
      description: 'กิจกรรมรวมฐานการเรียนรู้ด้านสุขภาวะ ทั้งการออกกำลังกาย โภชนาการ และการปลูกผักสวนครัว สำหรับทุกกลุ่มวัยในชุมชน'
    },
    {
      id: 'ACT-2026-018',
      name: 'ตลาดนัดผักปลอดสารประจำเดือน',
      area: 'ชุมชนบางบัว',
      program: 'โครงการเกษตรเพื่อสุขภาพ',
      startDate: '2026-09-05',
      endDate: '2026-09-05',
      time: '08:00 - 12:00',
      capacity: 60,
      registered: 4,
      status: 'ฉบับร่าง',
      fee: 0,
      organizer: 'The Farm Concept ร่วมกับชุมชนบางบัว',
      instructor: 'ทีมเกษตรกรอินทรีย์',
      tags: ['FOOD'],
      description: 'ตลาดนัดจำหน่ายผักปลอดสารพิษจากเกษตรกรในเครือข่ายชุมชน พบปะพูดคุยแลกเปลี่ยนความรู้การปลูกผักกับเกษตรกรตัวจริง'
    }
  ],

  areas: [
    { name: 'ชุมชนพูนทรัพย์ เขตสายไหม', province: 'กรุงเทพมหานคร', coordinator: 'อรุณี ทองสุข', activityCount: 6, totalParticipants: 172, avgSatisfaction: 4.6 },
    { name: 'ศูนย์การเรียนรู้ The Farm Concept', province: 'ปทุมธานี', coordinator: 'วีระ ศรีสมบัติ', activityCount: 4, totalParticipants: 98, avgSatisfaction: 4.7 },
    { name: 'ชุมชนหนองแขม', province: 'กรุงเทพมหานคร', coordinator: 'ปิยะดา รุ่งเรือง', activityCount: 3, totalParticipants: 41, avgSatisfaction: 4.3 },
    { name: 'ชุมชนบางบัว', province: 'กรุงเทพมหานคร', coordinator: 'ธนากร ใจดี', activityCount: 2, totalParticipants: 26, avgSatisfaction: 4.1 }
  ],

  targetGroups: [
    { name: 'กลุ่มเด็กและเยาวชน', ageRange: '6-18 ปี', memberCount: 84, avgScoreChange: 0.6 },
    { name: 'กลุ่มวัยทำงาน', ageRange: '19-59 ปี', memberCount: 156, avgScoreChange: 0.9 },
    { name: 'กลุ่มผู้สูงอายุ', ageRange: '60 ปีขึ้นไป', memberCount: 97, avgScoreChange: 1.2 }
  ],

  sampleGroups: [
    {
      id: 'SMP-001', name: 'กลุ่มตัวอย่างติดตามผล รุ่นที่ 1', activityName: 'กิจกรรมฟื้นฟูสุขภาวะชุมชน', targetGroupName: 'กลุ่มผู้สูงอายุ', sampleSize: 20, trackedCount: 12, avgScoreChange: 1.1,
      /* `members` is a new field (not part of the original report-only shape) added to support identity
         verification for the Form & Survey Builder's health-tracking form type — see docs/database-standard.md
         proposal in the accompanying task summary before treating this as a real schema. */
      members: [
        { name: 'อดิศักดิ์ พูลสวัสดิ์', phone: '085-678-9015', email: 'adisak@example.com' },
        { name: 'พิมพ์ใจ เพียรทำ', phone: '089-111-2233', email: 'pimjai@example.com' },
        { name: 'สมหญิง รักธรรมชาติ', phone: '086-222-3344', email: 'somying@example.com' },
        { name: 'บุญมี ทำดี', phone: '087-333-4455', email: 'boonmee@example.com' }
      ]
    },
    {
      id: 'SMP-002', name: 'กลุ่มตัวอย่างติดตามผล รุ่นที่ 2', activityName: 'ปลูกผักปลอดสารสำหรับครอบครัว', targetGroupName: 'กลุ่มวัยทำงาน', sampleSize: 15, trackedCount: 3, avgScoreChange: 0.7,
      members: [
        { name: 'สมชาย ใจงาม', phone: '081-234-5671', email: 'somchai@example.com' },
        { name: 'วิภาดา สายใจ', phone: '082-345-6782', email: 'wipada@example.com' }
      ]
    },
    {
      id: 'SMP-003', name: 'กลุ่มตัวอย่างติดตามผล รุ่นที่ 3', activityName: 'Workshop อาหารสุขภาพจากสวน', targetGroupName: 'กลุ่มเด็กและเยาวชน', sampleSize: 10, trackedCount: 10, avgScoreChange: 0.9,
      members: [
        { name: 'ประภาส ทองแท้', phone: '083-456-7893', email: 'prapas@example.com' },
        { name: 'มณีรัตน์ ใจบุญ', phone: '084-567-8904', email: 'maneerat@example.com' }
      ]
    }
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
    { id: 'USR-001', name: 'สุนิสา แก้วมณี', email: 'sunisa@thefarmconcept.org', role: 'เจ้าหน้าที่โครงการ', area: 'ชุมชนพูนทรัพย์ เขตสายไหม', status: 'ใช้งานอยู่', lastLogin: '2026-08-03' },
    { id: 'USR-002', name: 'วีระ ศรีสมบัติ', email: 'weera@thefarmconcept.org', role: 'ผู้ดูแลโครงการ', area: 'ศูนย์การเรียนรู้ The Farm Concept', status: 'ใช้งานอยู่', lastLogin: '2026-08-02' },
    { id: 'USR-003', name: 'ปิยะดา รุ่งเรือง', email: 'piyada@thefarmconcept.org', role: 'เจ้าหน้าที่โครงการ', area: 'ชุมชนหนองแขม', status: 'ระงับการใช้งาน', lastLogin: '2026-07-20' },
    { id: 'USR-004', name: 'ธนากร ใจดี', email: 'thanakorn@thefarmconcept.org', role: 'เจ้าหน้าที่โครงการ', area: 'ชุมชนบางบัว', status: 'ใช้งานอยู่', lastLogin: '2026-08-01' },
    { id: 'USR-005', name: 'อรุณี ทองสุข', email: 'arunee@thefarmconcept.org', role: 'ผู้ดูแลระบบสูงสุด', area: 'ส่วนกลาง', status: 'ใช้งานอยู่', lastLogin: '2026-08-03' }
  ],

  roles: [
    {
      name: 'ผู้ดูแลระบบสูงสุด', code: 'super_admin',
      description: 'จัดการโครงการ ผู้ใช้งาน และข้อมูลกลางทั้งหมดของระบบ',
      userCount: 1,
      permissions: { project: true, users: true, areas: true, master_data: true, activities: true, payments: true, evaluations: true, reports: true }
    },
    {
      name: 'ผู้ดูแลโครงการ', code: 'project_admin',
      description: 'จัดการพื้นที่ กิจกรรม และรายงานภายในโครงการที่รับผิดชอบ',
      userCount: 2,
      permissions: { project: false, users: false, areas: true, master_data: true, activities: true, payments: true, evaluations: true, reports: true }
    },
    {
      name: 'เจ้าหน้าที่โครงการ', code: 'staff',
      description: 'จัดการกิจกรรม ลงทะเบียน ตรวจสอบการชำระเงิน และติดตามผลในพื้นที่ที่รับผิดชอบ',
      userCount: 3,
      permissions: { project: false, users: false, areas: false, master_data: false, activities: true, payments: true, evaluations: true, reports: false }
    },
    {
      name: 'ผู้เข้าร่วมกิจกรรม', code: 'participant',
      description: 'ลงทะเบียนกิจกรรม แนบหลักฐานการชำระเงิน และทำแบบประเมิน',
      userCount: 337,
      permissions: { project: false, users: false, areas: false, master_data: false, activities: false, payments: false, evaluations: false, reports: false }
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
    { name: 'โครงการฟื้นฟูสุขภาวะชุมชน', category: 'สุขภาวะชุมชน', activityCount: 3, status: 'ดำเนินการอยู่' },
    { name: 'โครงการเกษตรเพื่อสุขภาพ', category: 'เกษตรและอาหาร', activityCount: 2, status: 'ดำเนินการอยู่' },
    { name: 'โครงการพัฒนาเยาวชนนักปลูกผัก', category: 'เยาวชน', activityCount: 0, status: 'ฉบับร่าง' }
  ],

  instructors: [
    { name: 'อาจารย์สมพงษ์ ปลูกดี', expertise: 'เกษตรอินทรีย์และปุ๋ยหมัก', phone: '08x-xxx-1111', activityCount: 4 },
    { name: 'คุณนภา ทำอาหารเพื่อสุขภาพ', expertise: 'โภชนาการและอาหารสุขภาพจากวัตถุดิบในสวน', phone: '08x-xxx-2222', activityCount: 2 },
    { name: 'อาจารย์ประสิทธิ์ สวนผักงาม', expertise: 'การออกแบบสวนผักในเมือง', phone: '08x-xxx-3333', activityCount: 3 }
  ],

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

  /* ===== Form & Survey Builder module (frm_*) — added for the unified 3-type form builder.
     Additive only: none of the arrays/keys above are modified. See the task's schema proposal
     for the equivalent real-migration shape (prefix `frm_` per docs/database-standard.md). ===== */
  forms: [
    {
      id: 'FRM-001', name: 'แบบลงทะเบียนกิจกรรม: ปลูกผักปลอดสารสำหรับครอบครัว', formType: 'registration',
      requiresIdentityVerification: true, verificationTiming: 'during_submission', linkedTo: 'activity',
      linkedActivityId: 'ACT-2026-014', linkedActivityName: 'ปลูกผักปลอดสารสำหรับครอบครัว',
      linkedSampleGroupId: null, roundId: null, allowResubmission: false,
      status: 'เผยแพร่แล้ว', questionCount: 1, responseCount: 2, createdAt: '2026-07-20'
    },
    {
      id: 'FRM-002', name: 'แบบประเมินความพึงพอใจ: Workshop อาหารสุขภาพจากสวน', formType: 'satisfaction',
      requiresIdentityVerification: false, verificationTiming: 'none', linkedTo: 'activity',
      /* satisfaction forms use the many-to-many linkedActivityIds array — registration/health_tracking
         keep their original single-relationship fields (linkedActivityId / linkedSampleGroupId)
         untouched, per this task's explicit prohibition on changing those two types' relationships. */
      linkedActivityIds: ['ACT-2026-015'],
      linkedSampleGroupId: null, roundId: null, allowResubmission: true,
      status: 'เผยแพร่แล้ว', questionCount: 3, responseCount: 5, createdAt: '2026-08-05'
    },
    {
      id: 'FRM-003', name: 'แบบประเมินความพึงพอใจ (ฉบับย่อ): Workshop อาหารสุขภาพจากสวน', formType: 'satisfaction',
      requiresIdentityVerification: false, verificationTiming: 'none', linkedTo: 'activity',
      linkedActivityIds: ['ACT-2026-015'],
      linkedSampleGroupId: null, roundId: null, allowResubmission: true,
      status: 'ฉบับร่าง', questionCount: 2, responseCount: 0, createdAt: '2026-08-06'
    },
    {
      id: 'FRM-004', name: 'แบบประเมินติดตามสุขภาวะ รุ่นที่ 1 (รอบ 3 เดือน)', formType: 'health_tracking',
      requiresIdentityVerification: true, verificationTiming: 'before_access', linkedTo: 'sample_group',
      linkedActivityId: null, linkedActivityName: null,
      linkedSampleGroupId: 'SMP-001', roundId: 'RND-001', allowResubmission: false,
      status: 'เผยแพร่แล้ว', questionCount: 3, responseCount: 1, createdAt: '2026-08-01'
    }
  ],

  /* Section master per form — new for the Section/Question Builder. Every question below carries
     a sectionId pointing here. Kept as a separate keyed object (same "keyed by formId" convention
     as formQuestions/formResponses) so existing per-formId lookups stay consistent. */
  formSections: {
    'FRM-001': [
      { id: 'SEC-001-1', order: 1, title: 'ข้อมูลเพิ่มเติม', description: '' }
    ],
    'FRM-002': [
      { id: 'SEC-002-1', order: 1, title: 'ความพึงพอใจโดยรวม', description: '' },
      { id: 'SEC-002-2', order: 2, title: 'ข้อเสนอแนะ', description: '' }
    ],
    'FRM-003': [
      { id: 'SEC-003-1', order: 1, title: 'ความคิดเห็นทั่วไป', description: '' }
    ],
    'FRM-004': [
      { id: 'SEC-004-1', order: 1, title: 'สุขภาพกาย', description: '' },
      { id: 'SEC-004-2', order: 2, title: 'ข้อสังเกตเพิ่มเติม', description: '' }
    ]
  },

  /* `options` (with per-option `score`) only applies to "ตัวเลือกเดียว"/"ตัวเลือกหลายข้อ" — every
     other type keeps options: [] (คะแนนความพึงพอใจ questions score by the numeric value picked,
     text questions never contribute a score). `order` is scoped within its section, matching how
     Section order works — both used by the ▲/▼ reorder controls in the builder. */
  formQuestions: {
    'FRM-001': [
      { id: 'Q-001-1', sectionId: 'SEC-001-1', order: 1, type: 'ตัวเลือกเดียว', text: 'ท่านทราบข่าวกิจกรรมนี้จากช่องทางใด', required: false,
        options: [{ text: 'Facebook', score: 0 }, { text: 'Line', score: 0 }, { text: 'เพื่อน/คนรู้จักแนะนำ', score: 0 }, { text: 'เจ้าหน้าที่ในพื้นที่', score: 0 }] }
    ],
    'FRM-002': [
      { id: 'Q-002-1', sectionId: 'SEC-002-1', order: 1, type: 'คะแนนความพึงพอใจ (1-5)', text: 'ความพึงพอใจโดยรวมต่อกิจกรรม', required: true, options: [] },
      { id: 'Q-002-2', sectionId: 'SEC-002-1', order: 2, type: 'คะแนนความพึงพอใจ (1-5)', text: 'วิทยากรถ่ายทอดความรู้ได้ชัดเจน', required: true, options: [] },
      { id: 'Q-002-3', sectionId: 'SEC-002-2', order: 1, type: 'ข้อความยาว', text: 'ข้อเสนอแนะเพิ่มเติม', required: false, options: [] }
    ],
    'FRM-003': [
      { id: 'Q-003-1', sectionId: 'SEC-003-1', order: 1, type: 'ตัวเลือกเดียว', text: 'ท่านจะแนะนำกิจกรรมนี้ให้ผู้อื่นหรือไม่', required: true,
        options: [{ text: 'แนะนำแน่นอน', score: 5 }, { text: 'อาจจะแนะนำ', score: 3 }, { text: 'ไม่แนะนำ', score: 1 }] },
      { id: 'Q-003-2', sectionId: 'SEC-003-1', order: 2, type: 'ข้อความสั้น', text: 'เมนูที่ประทับใจที่สุด', required: false, options: [] }
    ],
    'FRM-004': [
      { id: 'Q-004-1', sectionId: 'SEC-004-1', order: 1, type: 'คะแนนความพึงพอใจ (1-5)', text: 'ระดับความรู้สึกสุขภาพโดยรวมในช่วง 3 เดือนที่ผ่านมา', required: true, options: [] },
      { id: 'Q-004-2', sectionId: 'SEC-004-1', order: 2, type: 'คะแนนความพึงพอใจ (1-5)', text: 'ความสม่ำเสมอในการออกกำลังกาย/ทำกิจกรรมทางกาย', required: true, options: [] },
      { id: 'Q-004-3', sectionId: 'SEC-004-2', order: 1, type: 'ข้อความสั้น', text: 'การเปลี่ยนแปลงที่สังเกตเห็นได้ชัดที่สุด', required: false, options: [] }
    ]
  },

  formResponses: {
    'FRM-001': [
      { name: 'ธีรพงษ์ แสงทอง', phone: '087-890-1237', submittedAt: '2026-07-27', answers: { 1: 'Facebook' }, score: null },
      { name: 'มณีรัตน์ ใจบุญ', phone: '084-567-8904', submittedAt: '2026-07-20', answers: { 1: 'เพื่อน/คนรู้จักแนะนำ' }, score: null }
    ],
    'FRM-002': [
      { name: 'ประภาส ทองแท้', phone: '083-456-7893', submittedAt: '2026-08-17', answers: { 1: 5, 2: 5, 3: 'อาหารอร่อยมาก' }, score: 5.0 },
      { name: 'มณีรัตน์ ใจบุญ', phone: '084-567-8904', submittedAt: '2026-08-17', answers: { 1: 4, 2: 5, 3: '' }, score: 4.5 }
    ],
    'FRM-004': [
      { name: 'อดิศักดิ์ พูลสวัสดิ์', phone: '085-678-9015', submittedAt: '2026-08-01', answers: { 1: 4, 2: 3, 3: 'นอนหลับดีขึ้น' }, score: 3.5 }
    ]
  },

  trackingRounds: [
    { id: 'RND-001', sampleGroupId: 'SMP-001', roundNumber: 1, roundLabel: '3 เดือน', formId: 'FRM-004', dueDate: '2026-10-20', status: 'เปิดใช้งาน' },
    { id: 'RND-002', sampleGroupId: 'SMP-001', roundNumber: 2, roundLabel: '6 เดือน', formId: null, dueDate: '2027-01-20', status: 'รอเปิดใช้งาน' },
    { id: 'RND-003', sampleGroupId: 'SMP-002', roundNumber: 1, roundLabel: '3 เดือน', formId: null, dueDate: '2026-11-01', status: 'รอเปิดใช้งาน' }
  ],

  scoringCriteria: {
    'FRM-004': [
      { minScore: 0, maxScore: 2.9, label: 'ควรปรับปรุง', description: 'ควรติดตามอย่างใกล้ชิดและแนะนำให้พบเจ้าหน้าที่' },
      { minScore: 3, maxScore: 3.9, label: 'ปานกลาง', description: 'มีแนวโน้มคงที่ ควรติดตามต่อเนื่อง' },
      { minScore: 4, maxScore: 5, label: 'ดี', description: 'สุขภาวะโดยรวมดีขึ้นอย่างชัดเจน' }
    ]
  },

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

/* Permission check against the current mock user's role — used by the Action Menu to hide items the user cannot perform. Omitted/falsy `key` is always allowed (e.g. "view" actions). */
window.TFC.hasPermission = function (key) {
  if (!key) return true;
  var mock = window.TFC_MOCK || {};
  var roleCode = mock.currentUser && mock.currentUser.roleCode;
  var role = (mock.roles || []).filter(function (r) { return r.code === roleCode; })[0];
  if (!role) return true;
  return !!role.permissions[key];
};
