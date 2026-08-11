/* ============================================================
   จุดรวม JavaScript ที่ทุกหน้าหลังบ้านใช้ร่วมกัน

   ลำดับ import ตรงกับลำดับ <script> เดิมของหน้าจอ ห้ามสลับ
   ทุกไฟล์เป็น IIFE ที่เขียนค่าลง window.TFC โดยตรง จึงนำมารวมเป็นโมดูลได้
   โดยไม่ต้องแก้ตัวไฟล์ (ตัวแปรระดับบนสุดไม่รั่วออก global อยู่แล้ว)

   *** ไม่รวม mock-data.js กับ sidebar-render.js โดยตั้งใจ ***
   สองไฟล์นั้นต้องทำงาน "ก่อนเบราว์เซอร์วาดเฟรมแรก" ไม่งั้นแถบเมนูจะกระพริบ
   ว่างแล้วค่อยเต็มทุกครั้งที่เปลี่ยนหน้า (เหตุผลเต็มอยู่ในหัวไฟล์ sidebar-render.js)
   แต่ไฟล์ที่ Vite สร้างเป็น type="module" ซึ่งถูก defer เสมอ จึงสายเกินไป
   ทั้งสองไฟล์จึงยังโหลดเป็นสคริปต์ธรรมดาไว้ต้น <body> ตามเดิม

   ไฟล์ต้นทางยังอยู่ที่ public/assets/js/ เพราะหน้า static ที่ยังไม่ได้ย้ายยังใช้อยู่
   ============================================================ */

import '../../public/assets/js/data-service.js';
import '../../public/assets/js/navigation.js';
import '../../public/assets/js/modal.js';
import '../../public/assets/js/profile-modal.js';
import '../../public/assets/js/action-menu.js';
import '../../public/assets/js/index-layout.js';
import '../../public/assets/js/toast.js';
import '../../public/assets/js/form.js';
import '../../public/assets/js/smart-select.js';
import '../../public/assets/js/search-popover.js';
import '../../public/assets/js/app.js';
