# Codex Prompt Library

## Prompt เริ่มงานทั่วไป
```text
อ่าน AGENTS.md และเอกสารใน docs ที่เกี่ยวข้องก่อน
ตรวจสอบโครงสร้างและ Component เดิม
พัฒนาเฉพาะขอบเขตที่สั่ง
Reuse ของเดิมให้มากที่สุด
ห้ามแก้ฐานข้อมูล และห้ามเปลี่ยน UI ส่วนอื่น
หลังทำเสร็จให้สรุปไฟล์ที่แก้ วิธีทดสอบ และผลกระทบ
```

## Prompt ออกแบบหน้าจอใหม่
```text
อ่าน AGENTS.md, docs/design-system.md, docs/component-library.md และ docs/screen-template.md
วิเคราะห์ว่าหน้านี้ควรใช้ Template ใด
Reuse Layout และ Component เดิม
ห้ามออกแบบ Design System ใหม่
พัฒนาให้ Responsive และรองรับ Loading, Empty, Error, Success State
```

## Prompt แปลงภาพเป็น HTML 1 ไฟล์
```text
อ่านมาตรฐาน UI จาก AGENTS.md และ docs/design-system.md
สร้างภาพหน้าจอเป็น HTML เพียง 1 ไฟล์
ใช้ Font Kanit และโทนขาวเขียวของระบบ
รองรับ Desktop, Tablet และ Mobile
เน้นให้หน้าตาใกล้เคียงภาพอ้างอิง แต่ต้องคงมาตรฐาน Design System เดิม
ห้ามสร้างฐานข้อมูลหรือ Backend
```

## Prompt พัฒนา CRUD
```text
พัฒนา CRUD สำหรับ [ชื่อโมดูล]
ใช้ CRUD Template เดิม
ต้องมีค้นหา กรอง เพิ่ม แก้ไข ลบ ดูรายละเอียด Pagination Loading Empty State และ Confirmation
Reuse Table, Modal, Form และ Toast เดิม
ห้ามสร้าง Component ซ้ำ
ห้ามแก้ฐานข้อมูลโดยไม่ได้รับอนุญาต
```

## Prompt แก้ปัญหา Login Flicker
```text
ตรวจสอบ Authentication Flow ตอน Refresh
แก้ปัญหาหน้าจอวิ่งไปหน้า Login ชั่วครู่แล้วกลับเข้าระบบ
ต้องตรวจ Session/Auth ให้เสร็จก่อน Render Route
ใช้ Loading/Auth Guard ที่เหมาะสม
ห้ามเปลี่ยน UI Login
สรุป Root Cause และไฟล์ที่แก้
```

## Prompt ตรวจโค้ดก่อนส่ง
```text
ตรวจงานทั้งหมดเทียบกับ AGENTS.md
ตรวจ Syntax, Responsive, Accessibility, Loading, Error Handling, Double Submit, Performance และผลกระทบต่อหน้าจออื่น
แก้เฉพาะจุดที่จำเป็น
ห้าม Refactor ใหญ่เกินขอบเขต
```
