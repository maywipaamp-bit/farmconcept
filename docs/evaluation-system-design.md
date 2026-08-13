# Evaluation System Design

## 1. เป้าหมาย

พัฒนาระบบแบบประเมินให้ใช้โครงสร้างเดียวและรองรับ 3 ประเภท

| type | ชื่อในหน้าจอ | การระบุตัวตน | การเชื่อมระบบ |
|---|---|---|---|
| `registration` | ตอนลงทะเบียน | ระบุตัวตน | กิจกรรมผ่าน `evl_form_activity.slot = registration` |
| `post_activity` | หลังกิจกรรม | นิรนาม | กิจกรรมผ่าน `evl_form_activity.slot = post_survey` |
| `health_follow_up` | ติดตามสุขภาพ | ระบุตัวตน | รอบติดตามผ่าน `evl_round_batches.form_id` |

กติกาการระบุตัวตนเป็นข้อกำหนดของประเภท ไม่ใช่ตัวเลือกของผู้ดูแล

- `registration` → `is_anonymous = false`
- `post_activity` → `is_anonymous = true`
- `health_follow_up` → `is_anonymous = false`

## 2. สิ่งที่มีอยู่แล้วและต้อง Reuse

- `evl_forms` — ข้อมูลหัวแบบประเมิน
- `evl_questions` — คำถามและลำดับ
- `evl_form_activity` — ผูกแบบลงทะเบียน/หลังกิจกรรมกับกิจกรรม
- `evl_satisfaction_responses` — คำตอบหลังกิจกรรมแบบนิรนาม
- `evl_satisfaction_receipts` — กันตอบซ้ำโดยไม่เชื่อมกลับไปยังคำตอบ
- `evl_survey_responses` — คำตอบติดตามสุขภาพแบบระบุตัวตน
- `evl_answers` — คำตอบรายข้อแบบ polymorphic
- `evl_round_batches` — ผูกแบบติดตามสุขภาพกับรอบติดตาม
- `Form`, `Question`, `Answer`, `SatisfactionResponse`, `SurveyResponse` Models

ฐานข้อมูลปัจจุบันมีแบบติดตามสุขภาพ 1 ชุด, 5 คำถาม, 76 responses และ 380 answers
จึงต้องเปลี่ยนแบบรักษาข้อมูลเดิม ห้ามสร้างโมดูลคู่ขนานหรือทิ้งตารางเดิม

## 3. ช่องว่างของโครงสร้างเดิม

1. `evl_forms` ไม่มีคำอธิบาย, ผู้แก้ไขล่าสุด, เวลาเปิดใช้งาน และค่ากำหนดการจอง
2. ไม่มีตารางเก็บฟิลด์มาตรฐานของแบบลงทะเบียนตาม Form ID
3. ไม่มีตารางตัวเลือกเฉพาะคำถาม จึงบันทึก single/multi/chips/dropdown ไม่ได้
4. `evl_answers.response_type` รองรับเฉพาะ `satisfaction` และ `survey` ยังไม่รองรับ `registration`
5. `evl_answers` ไม่มี `option_id` สำหรับคำตอบแบบเลือก
6. ค่า `type` และ `status` ในข้อมูลเดิมยังใช้ภาษาไทยหลายรูปแบบ ไม่เป็นค่ากลางเดียวกัน
7. หน้ารายการและหน้าสร้างยังทำงานกับ Mock data ไม่มี Controller, Request, Service และ Route CRUD จริง

## 4. โครงสร้างข้อมูลที่เสนอ

### 4.1 ขยาย `evl_forms`

เพิ่มคอลัมน์:

| column | type | nullable | ใช้สำหรับ |
|---|---|---:|---|
| `description` | `text` | yes | คำอธิบายแบบประเมิน |
| `registration_mode` | `varchar(20)` | yes | `single` หรือ `group` เฉพาะแบบลงทะเบียน |
| `max_participants` | `unsignedTinyInteger` | yes | 1–5 เฉพาะแบบลงทะเบียน |
| `published_at` | `timestamp` | yes | เวลาเปิดใช้งานครั้งแรก |
| `updated_by` | `foreignId -> users` | yes | ผู้แก้ไขล่าสุด |

ค่ากลางของคอลัมน์เดิม:

- `type`: `registration`, `post_activity`, `health_follow_up`
- `status`: `draft`, `active`, `inactive`

เพิ่ม index ที่ `type` และ index รวม `type, status`

### 4.2 ตารางใหม่ `evl_form_fields`

เก็บการเปิด/ปิดและลำดับฟิลด์มาตรฐานของแบบลงทะเบียน โดยให้ Form เป็น Source of Truth

| column | type | rule |
|---|---|---|
| `id` | bigint | PK |
| `form_id` | foreignId | cascade delete |
| `field_key` | varchar(60) | key จากระบบ |
| `is_enabled` | boolean | default true |
| `is_required` | boolean | default false |
| `sort_order` | unsignedSmallInteger | default 0 |

Constraints:

- unique `form_id, field_key`
- index `form_id, sort_order`
- `name`, `phone`, `pdpa` บังคับที่ Service layer และปิดไม่ได้
- field key มาตรฐาน: `name`, `phone`, `gender`, `age_range`, `email`, `occupation`, `source_channel`, `interests`, `pdpa`

`act_activity_reg_fields` เดิมยังคงไว้เพื่อไม่ทำลายข้อมูลเดิม แต่เมื่อเชื่อมกิจกรรมกับแบบลงทะเบียนแล้ว
ระบบใหม่จะอ่าน `evl_form_fields` เป็นหลัก ห้ามบันทึกสองที่พร้อมกัน

### 4.3 ตารางใหม่ `evl_question_options`

เก็บตัวเลือกเฉพาะคำถามสำหรับ `single`, `multi`, `chips`, `dropdown`

| column | type | rule |
|---|---|---|
| `id` | bigint | PK |
| `question_id` | foreignId | cascade delete |
| `sort_order` | unsignedSmallInteger | default 0 |
| `label` | varchar(255) | required |
| `value` | varchar(120) | required |
| `is_other` | boolean | default false |
| timestamps | timestamps | |

Constraints:

- unique `question_id, sort_order`
- unique `question_id, value`

### 4.4 ขยาย `evl_answers`

- เพิ่ม `option_id` nullable FK ไป `evl_question_options` และ `nullOnDelete`
- ขยาย `response_type` เป็น `registration`, `satisfaction`, `survey`
- คำถามแบบเลือกหนึ่งข้อเก็บหนึ่ง answer พร้อม `option_id`
- คำถามแบบเลือกหลายข้อเก็บหลาย answer ต่อ question/response
- ตัวเลือก “อื่น ๆ” เก็บ `option_id` และข้อความจริงใน `text_value`
- rating เก็บ `score`
- text เก็บ `text_value`

### 4.5 `evl_questions`

ใช้โครงเดิมได้ โดยกำหนดค่ากลางของ `question_type`:

- `section`
- `rating`
- `single`
- `multi`
- `chips`
- `dropdown`
- `text`

แถว `section` ใช้จัดหัวข้อ ไม่มี answer และ `is_required = false`

## 5. Domain Rules

1. แบบร่างแก้ไขได้เต็มรูปแบบ
2. แบบ active ที่ยังไม่มีคำตอบแก้ไขได้
3. แบบ active ที่มีคำตอบแล้วห้ามเปลี่ยนชนิด/ลบ/เปลี่ยนตัวเลือกเดิม ให้ทำสำเนาเป็นแบบใหม่
4. ลบได้เฉพาะแบบร่างที่ยังไม่ถูกผูกกับกิจกรรม/รอบและไม่มีคำตอบ
5. `post_activity` บังคับนิรนามและห้ามมี FK จาก response กลับไปยังผู้ตอบ
6. `health_follow_up` ต้องมี participant และ cohort round
7. `registration` ใช้ registration เป็น response owner และใช้ข้อมูลมาตรฐานจาก `evl_form_fields`
8. เปิดใช้งานได้เมื่อชื่อ ประเภท และคำถามทุกข้อผ่าน validation
9. คำถามแบบเลือกต้องมีตัวเลือกอย่างน้อย 2 รายการ
10. แบบลงทะเบียนเปิดใช้งานได้แม้ไม่มีคำถามเพิ่มเติม เพราะมีฟิลด์ระบบอยู่แล้ว

## 6. Backend ที่จะพัฒนาเมื่อ Schema ได้รับอนุมัติ

### Routes

- `GET /admin/evaluations`
- `GET /admin/evaluations/create`
- `POST /admin/evaluations`
- `GET /admin/evaluations/{form}/edit`
- `PUT /admin/evaluations/{form}`
- `POST /admin/evaluations/{form}/duplicate`
- `PATCH /admin/evaluations/{form}/status`
- `DELETE /admin/evaluations/{form}`

ทุก Route ใช้ `auth` และ `menu:evaluations`

### Classes

- `Admin/EvaluationController`
- `EvaluationRequest`
- `EvaluationStatusRequest`
- `EvaluationService`
- ขยาย `Form`, `Question`, `Answer`
- เพิ่ม `FormField`, `QuestionOption`

`EvaluationService` ใช้ Transaction ในการบันทึก Form + Fields + Questions + Options ให้สำเร็จพร้อมกัน

### Frontend

- ย้ายหน้ารายการและ Create/Edit จาก Legacy mock ไป Blade
- ใช้ UI และ Component ที่ทำไว้แล้ว
- โหลด Form เดิมเข้า state สำหรับ Edit
- ส่ง payload เดียวกันทั้ง Save Draft และ Publish
- แสดง Loading, validation errors, success toast และป้องกันกดซ้ำ

## 7. Payload กลาง

```json
{
  "name": "แบบลงทะเบียนกิจกรรมทั่วไป",
  "description": "",
  "type": "registration",
  "status": "active",
  "registration": {
    "mode": "group",
    "max_participants": 5,
    "fields": [
      { "key": "name", "enabled": true, "required": true, "sort_order": 1 },
      { "key": "phone", "enabled": true, "required": true, "sort_order": 2 },
      { "key": "gender", "enabled": true, "required": false, "sort_order": 3 },
      { "key": "pdpa", "enabled": true, "required": true, "sort_order": 9 }
    ]
  },
  "questions": [
    {
      "type": "single",
      "text": "ต้องการรับอาหารประเภทใด",
      "required": false,
      "sort_order": 1,
      "options": [
        { "label": "ปกติ", "value": "normal", "sort_order": 1 },
        { "label": "มังสวิรัติ", "value": "vegetarian", "sort_order": 2 }
      ]
    }
  ]
}
```

## 8. ผลกระทบต่อข้อมูลเดิม

- ไม่มีการลบตารางหรือคอลัมน์เดิม
- แบบติดตามสุขภาพ `DEMO-FRM-01` และคำตอบ 76 ชุด/380 answers ต้องใช้งานได้เหมือนเดิม
- Migration จะ map ค่าเดิม:
  - `แบบติดตามสุขภาพ` → `health_follow_up`
  - `เปิดใช้งาน`, `เผยแพร่แล้ว` → `active`
  - `ฉบับร่าง` → `draft`
- `is_anonymous` ของข้อมูลเดิมคงเดิม
- `evl_answers.option_id` เป็น nullable จึงไม่กระทบคำตอบ rating เดิม
- Dashboard ที่อ่าน `response_type = survey` และ `score` ทำงานต่อได้

## 9. Backup ก่อน Migration

สำรองเฉพาะโมดูลประเมินและตารางเชื่อมก่อน:

```text
evl_forms
evl_questions
evl_form_activity
evl_satisfaction_responses
evl_satisfaction_receipts
evl_survey_responses
evl_answers
evl_round_batches
evl_round_batch_members
act_activity_reg_fields
```

ใช้ `mysqldump --single-transaction` และตั้งชื่อไฟล์พร้อม timestamp เก็บนอก repository

## 10. Rollback

Migration `down()` จะ:

1. ลบ FK/index และคอลัมน์ `option_id` จาก `evl_answers`
2. คืน enum `response_type` เป็น `satisfaction`, `survey`
3. ลบ `evl_question_options`
4. ลบ `evl_form_fields`
5. ลบ index/คอลัมน์ใหม่จาก `evl_forms`
6. map `health_follow_up` กลับเป็น `แบบติดตามสุขภาพ`, `active` กลับเป็น `เปิดใช้งาน`, `draft` กลับเป็น `ฉบับร่าง`

ถ้ามีข้อมูลชนิด `registration` เกิดขึ้นหลังเปิดใช้ ต้อง export ข้อมูลนั้นก่อน rollback เพราะ schema เดิมไม่รองรับคำตอบชนิดนี้

