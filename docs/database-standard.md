# Database Standard

## Mandatory Rule
ห้ามสร้างหรือแก้ไขฐานข้อมูลโดยไม่ได้รับอนุญาตจากเจ้าของโปรเจกต์

## Before Database Change
ต้องเสนอ
- เหตุผลที่ต้องเปลี่ยน
- ตาราง/คอลัมน์ที่เกี่ยวข้อง
- SQL หรือ Migration ที่จะใช้
- ผลกระทบต่อข้อมูลเดิม
- วิธี Backup
- วิธี Rollback

## Naming
- ใช้ภาษาอังกฤษ
- ใช้ snake_case
- Primary Key: `id`
- Foreign Key: `<entity>_id`
- Timestamp: `created_at`, `updated_at`
- Soft Delete: `deleted_at` เมื่อจำเป็น

## Group Prefix
หากโปรเจกต์กำหนด Prefix ตาราง ให้ใช้ Prefix ที่สัมพันธ์กับ Module และ Path เดียวกันอย่างสม่ำเสมอ

ตัวอย่าง
- `act_` สำหรับ Activity
- `evl_` สำหรับ Evaluation
- `usr_` สำหรับ User/Permission
- `mst_` สำหรับ Master Data
- `rpt_` สำหรับ Report Aggregate เมื่อจำเป็น

ห้ามสร้าง Prefix ใหม่โดยไม่ตรวจสอบมาตรฐานเดิม
