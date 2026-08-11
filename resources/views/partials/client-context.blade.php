{{--
    บริบทฝั่งหน้าจอ — ผู้ใช้จริง เมนูที่กรองสิทธิ์แล้ว และตารางสิทธิ์

    ต้องวางไว้ "ทันทีหลัง" mock-data.js และ "ก่อน" sidebar-render.js
    เพราะ sidebar-render.js อ่าน TFC_MENU กับ currentUser ไปวาดตั้งแต่ก่อนจบ <body>
    ถ้าวางผิดตำแหน่ง แถบเมนูจะว่างหรือขึ้นชื่อของข้อมูลจำลอง

    ไฟล์นี้ถูกใช้ทั้งจาก layouts/admin.blade.php และจาก LegacyPageController
    ที่ฉีดผลลัพธ์เดียวกันนี้เข้าไปในหน้า HTML เดิม จึงมีสูตรเดียวไม่แตกเป็นสองทาง
--}}
<script>
(function () {
  var ctx = @json($clientContext);

  window.TFC_MENU = ctx.menu;
  window.TFC_PERMISSION_MAP = ctx.permissionMap;

  window.TFC_MOCK = window.TFC_MOCK || {};
  window.TFC_MOCK.currentUser = ctx.currentUser;

  /* TFC.hasPermission() อ่านสิทธิ์จาก TFC_MOCK.roles โดยจับคู่กับ currentUser.roleCode
     ส่งมาเฉพาะบทบาทของผู้ใช้คนนี้ ไม่ส่งบทบาทอื่นทั้งระบบมาให้เบราว์เซอร์ */
  window.TFC_MOCK.roles = [ctx.role];

  /* ท้ายแผงเมนูแสดงเวอร์ชันกับปีตามสเปก — มาจากเซิร์ฟเวอร์ ไม่ใช่ค่าที่เขียนไว้ในไฟล์ JS
     ปีเป็น พ.ศ. เพราะทั้งระบบใช้ พ.ศ. */
  window.TFC_APP = { version: @json(config('app.version', '1.0.0')), year: @json(now()->year + 543) };
})();
</script>
