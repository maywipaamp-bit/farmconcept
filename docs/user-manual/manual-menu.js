/* ============================================================
   เมนูของคู่มือ — แหล่งความจริงเดียว
   เพิ่มหน้าใหม่ให้แก้ที่ไฟล์นี้ที่เดียว ทุกหน้าจะเห็นเมนูชุดเดียวกัน

   หมวด "ลำดับการทำงาน" เรียงตามลำดับที่ใช้งานจริงตั้งแต่สร้างกิจกรรมจนจบงาน
   ไม่ได้เรียงตามโครงเมนูของระบบ เพราะคนอ่านคู่มือกำลังทำงานเป็นเรื่อง ไม่ได้ไล่ดูเมนู

   หัวข้อที่ยังไม่มี href = ยังไม่ได้เขียน จะแสดงเป็น "เร็ว ๆ นี้" และกดไม่ได้
   ============================================================ */

window.MANUAL_MENU = [
  {
    title: 'เริ่มต้น',
    items: [
      { label: 'เข้าสู่ระบบและหน้าตาระบบ', href: 'index.html' },
    ],
  },
  {
    title: 'ลำดับการทำงาน',
    items: [
      { no: '1', label: 'เพิ่มกิจกรรมใหม่', href: 'activities-create.html' },
      { no: '2', label: 'ดาวน์โหลด QR', href: 'qr-download.html' },
      { no: '3', label: 'ผู้เข้าร่วมลงทะเบียน', href: 'registration.html' },
      { no: '4', label: 'ดูผลการลงทะเบียน', href: 'registrants.html' },
      { no: '5', label: 'เช็กอินหน้างาน', href: 'checkin.html' },
      { no: '6', label: 'แบบประเมินหลังกิจกรรม', href: 'survey.html' },
    ],
  },
  {
    title: 'กลุ่มตัวอย่าง',
    items: [
      { no: '1', label: 'ลงทะเบียนกลุ่มตัวอย่าง', href: 'cohort-register.html' },
      { no: '2', label: 'แบบประเมินครั้งแรก', href: 'cohort-baseline.html' },
      { no: '3', label: 'แจ้งเตือนติดตาม', href: 'cohort-rounds.html' },
      { no: '4', label: 'รับแจ้งเตือนผ่าน LINE', href: 'cohort-line.html' },
      { no: '5', label: 'ตอบแบบประเมิน', href: 'cohort-answer.html' },
    ],
  },
  {
    title: 'ผลและการวิเคราะห์',
    items: [
      { label: 'ผลตอบรายคน' },
      { label: 'สรุปผลแบบประเมิน' },
      { label: 'ผลการวิเคราะห์' },
    ],
  },
  {
    title: 'รายงาน',
    items: [
      { label: 'ผู้เข้าร่วมทั้งหมด' },
      { label: 'ภาพรวมและประสิทธิภาพ' },
      { label: 'การเงิน' },
      { label: 'สุขภาพกลุ่มตัวอย่าง' },
    ],
  },
  {
    title: 'ตั้งค่า',
    items: [
      { label: 'ข้อมูลพื้นฐาน' },
      { label: 'ผู้ใช้งานและบทบาท' },
    ],
  },
];

/* ไฟล์นี้ถูกเรียกทันทีหลัง <nav id="manual-nav"> ในทุกหน้า ไม่ใช่ท้าย body
   เมนูกับแถบลำดับงานจึงมีเนื้อหาตั้งแต่เฟรมแรกที่เบราว์เซอร์วาด
   ถ้าย้ายไปท้าย body เมื่อไหร่ จะเห็นกล่องเปล่าแวบหนึ่งทุกครั้งที่เปลี่ยนหน้า */
(function () {
  /* เทียบด้วยชื่อไฟล์อย่างเดียว เปิดจากดิสก์หรือจากเว็บก็ไฮไลต์ถูกเหมือนกัน
     เสิร์ฟผ่าน /manual จะไม่มีชื่อไฟล์ใน URL — นับเป็นหน้าแรก */
  var last = location.pathname.split('/').pop();
  var here = /\.html$/.test(last) ? last : 'index.html';

  /* แถบลำดับงาน — อยู่ในหัวเอกสาร ซึ่ง parse เสร็จก่อนสคริปต์นี้แล้ว
     แสดงลำดับของ "หมวดที่หน้านี้อยู่" เอง หมวดไหนมีเลขกำกับก็ใช้ได้หมด
     ไม่ต้องผูกกับชื่อหมวดใดหมวดหนึ่ง เวลาเพิ่มหมวดใหม่จะได้ไม่ต้องแก้ตรงนี้ */
  var flowMount = document.getElementById('manual-flow');
  var flow = window.MANUAL_MENU.filter(function (g) {
    return g.items.some(function (i) { return i.no && i.href === here; });
  })[0];

  if (flowMount && flow) {
    var parts = flow.items.map(function (item) {
      var inner = '<span class="m-flow-no">' + item.no + '</span>' + item.label;
      if (item.href === here) return '<span class="m-flow-step is-here">' + inner + '</span>';
      if (!item.href) return '<span class="m-flow-step">' + inner + '</span>';
      return '<a href="' + item.href + '">' + inner + '</a>';
    });
    flowMount.innerHTML = parts.join('<span class="m-flow-arrow" aria-hidden="true">›</span>');
  }

  var mount = document.getElementById('manual-nav');
  if (!mount) return;

  var html = '<span class="m-nav-brand">คู่มือการใช้งาน</span>';

  window.MANUAL_MENU.forEach(function (group) {
    html += '<div class="m-nav-group">';
    html += '<span class="m-nav-title">' + group.title + '</span>';

    group.items.forEach(function (item) {
      var no = item.no ? '<i>' + item.no + '</i>' : '';

      if (!item.href) {
        html += '<span class="m-nav-item is-soon">' + no + '<span>' + item.label + '</span>'
             + '<em>เร็ว ๆ นี้</em></span>';
        return;
      }

      var active = item.href === here ? ' is-active' : '';
      html += '<a class="m-nav-item' + active + '" href="' + item.href + '"'
           + (active ? ' aria-current="page"' : '') + '>' + no + '<span>' + item.label + '</span></a>';
    });

    html += '</div>';
  });

  mount.innerHTML = html;
})();
