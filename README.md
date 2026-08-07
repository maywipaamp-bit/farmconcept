# TheFarmConcept AI Development Guide

ชุดเอกสารนี้ใช้เป็นมาตรฐานกลางสำหรับสั่ง Codex หรือ AI ช่วยพัฒนาโปรเจกต์ TheFarmConcept

## วิธีติดตั้ง
นำไฟล์ทั้งหมดไปวางที่ Root ของโปรเจกต์

```text
thefarmconcept/
├── AGENTS.md
├── README.md
└── docs/
    ├── design-system.md
    ├── component-library.md
    ├── screen-template.md
    ├── coding-standard.md
    ├── database-standard.md
    └── codex-prompts.md
```

## วิธีใช้กับ Codex
ก่อนเริ่มงานแต่ละครั้ง ใช้คำสั่งสั้น ๆ เช่น

```text
อ่าน AGENTS.md และเอกสารใน docs ที่เกี่ยวข้องก่อน
จากนั้นพัฒนาหน้าจอจัดการกิจกรรม โดยใช้ Component และ Template เดิม
ห้ามเปลี่ยน UI ส่วนอื่น และห้ามแก้ฐานข้อมูล
```

## หลักการสำคัญ
- ทำมาตรฐานกลางครั้งเดียว
- ใช้ Component ซ้ำ
- ใช้ Template ซ้ำ
- สั่งเฉพาะส่วนที่แตกต่าง
- ไม่ส่ง Prompt ยาวซ้ำทุกครั้ง
