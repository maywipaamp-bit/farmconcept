# Coding Standard

## General
- ใช้ชื่อภาษาอังกฤษ
- ชื่อสื่อความหมาย
- Function ต้องทำหน้าที่เดียว
- ลด Nested Condition
- หลีกเลี่ยง Magic Number
- แยกค่าคงที่ไว้ส่วนกลาง
- ไม่เขียน Logic ธุรกิจใน View

## Naming
- Class/Component: PascalCase
- Function/Variable: camelCase
- Constant: UPPER_SNAKE_CASE
- CSS Class: kebab-case หรือมาตรฐาน Framework เดิม

## Frontend
- Reuse Component
- แยก State ให้ชัดเจน
- มี Loading และ Error Handling
- ป้องกัน Double Submit
- Debounce Search เมื่อเหมาะสม
- Lazy Load ข้อมูลหรือรูปภาพที่ไม่จำเป็นต้องโหลดทันที

## Performance
- ลด Query ซ้ำ
- ลด DOM ที่ไม่จำเป็น
- ไม่โหลด Library เพิ่มโดยไม่มีเหตุผล
- ไม่สร้าง Event Listener ซ้ำ
- Optimize Image

## Security
- Validate Input ทั้ง Client และ Server
- Escape Output
- ตรวจ Authorization
- ห้ามเก็บ Secret ใน Source Code
- ห้าม Log ข้อมูลส่วนบุคคลโดยไม่จำเป็น
