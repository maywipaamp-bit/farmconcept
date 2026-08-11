# ข้อเสนอโครงสร้างฐานข้อมูล — TheFarmConcept

เอกสารนี้เป็น **ข้อเสนอเพื่อขออนุมัติ** ตาม `docs/database-standard.md` และ `AGENTS.md` ข้อ 98–100
ยังไม่มีการสร้าง Migration หรือแตะฐานข้อมูลใด ๆ ทั้งสิ้น

- ที่มา: ถอดจากข้อมูลจำลอง 1,720 บรรทัดใน `assets/js/mock-data.js`, `cohort-data.js`, `round-data.js`, `survey-data.js`, `followup-template-service.js`
- ผลกระทบต่อข้อมูลเดิม: **ไม่มี** (สร้างใหม่ทั้งหมด ไม่มีข้อมูล production ให้เสียหาย)

> **สถานะ: ติดตั้งจริงแล้ว** — Laravel 13.24.0 + MySQL 8.0.42 · 44 ตารางของโปรเจกต์ + 9 ตารางแกน Laravel
> `migrate` และ `migrate:rollback` ทดสอบผ่านบน MySQL จริงแล้ว (ดูส่วนที่ 9)
> ไฟล์ Migration: `database/migrations/2026_08_11_00000{1..7}_*.php` จัดกลุ่มตามโมดูลเพื่อ rollback ทีละโมดูลได้

---

## ส่วนที่ 1 — เรื่องที่ต้องตัดสินใจก่อนเขียน Migration

โค้ดต้นแบบมีจุดที่ออกแบบไว้ **ซ้อนกันสองแบบ** อยู่ 7 จุด ซึ่งฐานข้อมูลเลือกได้ทางเดียว

### สถานะการตัดสินใจ

| ปม | เรื่อง | สถานะ | ผู้ตัดสิน |
|---|---|---|---|
| A | โมเดล "คน" ซ้อนกัน | ✅ รวมเป็น `ptp_participants` + `ptp_cohort_profiles` | เจ้าของโปรเจกต์ |
| B | master รอบติดตาม 2 ชุด | ✅ ใช้ `followupTemplateService` | ทีมพัฒนา (เทคนิค) |
| C | แบบประเมินระบุตัวตน | ✅ **นิรนาม** — แยกตารางตามข้อ C.4 | เจ้าของโปรเจกต์ |
| D | ผู้ลงทะเบียน 2 ชุด | ✅ รวมเป็น `act_registrations` | ทีมพัฒนา (เทคนิค) |
| E | registration ไม่ผูก participant | ✅ `participant_id` nullable | ทีมพัฒนา (เทคนิค) |
| F | master list ค่าไม่ตรงกัน | ✅ วิธีเก็บตัดสินแล้ว · ⏳ ค่าที่ถูกต้องรอทีมธุรกิจ (seed ทีหลังได้) | ทั้งคู่ |
| G | prefix ตาราง | ✅ ใช้ prefix ตามกฎโปรเจกต์ | ทีมพัฒนา (เทคนิค) |

ปม F แยกเป็นสองส่วน: **วิธีเก็บ** ตัดสินแล้ว (ดูข้อ F.1–F.4) ส่วน **ค่าที่ถูกต้อง** เป็นข้อมูล seed ที่ใส่ทีหลังได้โดยไม่แตะโครงตาราง

**ส่วนที่ 7** (ข้อกำหนดเพิ่มเติมของโมดูลกิจกรรมและประเมินสุขภาพ) ตัดสินครบแล้วเช่นกัน — ดูข้อ 7.4 (วงจรชีวิต QR) และ 7.8 (กิจกรรมที่ไม่เปิดลงทะเบียน)

> **สรุป: ไม่มีข้อใดบล็อกการเขียน Migration แล้ว**

### ปม A — โมเดล "คน" มีสองชุดที่ทับกัน — ✅ อนุมัติแล้ว

| ชุดที่ 1 | ชุดที่ 2 |
|---|---|
| `TFC_MOCK.participants` (โมดูล *ผู้เข้าร่วมทั้งหมด*) | `TFC.cohort` MEMBERS (โมดูล *กลุ่มตัวอย่าง*) |
| รหัส `PTP-0001` + `personCode: TFC-69-0001` | รหัส `PID-0001` |
| แยกกลุ่มตัวอย่าง/ทั่วไปด้วย `type: sample \| general` | ทุกคนในชุดนี้เป็นกลุ่มตัวอย่างอยู่แล้ว |
| แผนติดตามอยู่ใน `followUpPlan[]` เก็บ `dueDate` ตรง ๆ | รอบคำนวณจาก `base` + `offsetDays` ที่ snapshot ไว้ |
| มี consent, ผู้ดูแล, projectStatus | มี LINE, การซื้อสินค้า, บันทึกติดตาม |

ทั้งสองชุดเก็บ ชื่อ · เบอร์โทร · เพศ · พื้นที่ · กลุ่มเป้าหมาย ซ้ำกัน — คนคนเดียวกันจะมีสองระเบียน

> **ข้อเสนอ:** รวมเป็น `ptp_participants` ตารางเดียว แล้วแยก `ptp_cohort_profiles` เป็น 1:1 เฉพาะคนที่ถูกรับเข้ากลุ่มตัวอย่าง (ถือ `cohort_code` = PID- และ `entry_date` = base)
> **เหตุผล:** `cohort-data.js` บรรทัด 4 ระบุกติกาไว้ชัดว่า "1 คน = 1 profile และมีได้กลุ่มเดียว" การมีสองตารางคนจะขัดกติกานี้ทันทีที่มีข้อมูลจริง

### ปม B — master "รอบติดตาม" มีสองชุด — ✅ ตัดสินแล้ว

| `TFC_MOCK.sampleFollowUpRounds` | `followupTemplateService.defaults()` |
|---|---|
| `ROUND-001/002/003` | `FRT-1/2/3/4` |
| `trackDays`, `lineNotify`, `notifyDaysBefore` | `offsetDays`, `isActive`, `sortOrder` |
| 3 รอบ (3/6/12 เดือน) | 4 รอบ (เพิ่ม "ก่อนเข้าร่วม" offset 0) |

> **ข้อเสนอ:** ใช้ชุด `followupTemplateService` → ตาราง `mst_follow_up_round_templates`
> **เหตุผล:** เป็นชุดที่ใหม่กว่า มีไฟล์ service เฉพาะ และ `survey-data.js` บรรทัด 7–11 ระบุชื่อตารางจริงไว้แล้วว่า `follow_up_round_templates`. ฟิลด์ `lineNotify` / `notifyDaysBefore` ที่มีเฉพาะในชุดเก่า ให้ย้ายไปเป็นคอลัมน์ของ template ชุดใหม่ ไม่ทิ้ง

### ปม C — แบบประเมิน: ระบุตัวตนผู้ตอบได้หรือไม่ — ✅ ตัดสินแล้ว: **นิรนาม**

> **คำตัดสินจากเจ้าของโปรเจกต์:** แบบประเมินความพึงพอใจ **ไม่ต้องรู้ว่าใครตอบ**
> → ใช้โครงสร้างตามข้อ C.4 · กติกาที่เขียนไว้ใน `satisfaction-service.js` และ `activity-detail.js` ถูกต้องแล้ว ไม่ต้องแก้หน้าจอ
> → `evaluationRespondents` และ `participantActivityHistory[].evaluated` เป็นข้อมูลตกค้าง ไม่ต้องมีตารางรองรับ

#### C.1 ข้อมูลแบบประเมินมี 5 ชุด แต่มีแค่ 3 ชุดที่มีหน้าจอใช้จริง

| ชุดข้อมูล | หน้าจอที่อ่าน | ระบุตัวตน |
|---|---|---|
| `satisfactionResponses` (5 หัวข้อ) | `satisfaction-service.js` → `admin/activities/responses.html` | **ไม่ได้ — บังคับด้วยโค้ด** |
| `activityEvaluations` (6 หัวข้อ, generated) | `activity-detail.js` แท็บแบบประเมิน | มีฟิลด์ `registrationId` + `name` แต่ **หน้าจอไม่ใช้** |
| `evaluationForms` | `activity-create.js` (เลือกผูกแบบประเมินกับกิจกรรม) | — เป็น metadata ของฟอร์ม |
| `evaluationRespondents` (ชื่อ + เบอร์โทร) | **ไม่มีหน้าจอไหนอ่านเลย** | ได้ |
| `participantActivityHistory[].evaluated` | **ไม่มีหน้าจอไหนอ่านเลย** | ได้ (รู้ว่าใครตอบแล้ว) |

#### C.2 หน้าจอที่มีอยู่จริง "นิรนามทั้งหมด" อย่างสม่ำเสมอ

- `satisfaction-service.js` เขียนกฎข้อ 4 ไว้ในหัวไฟล์: *ไม่คืนข้อมูลที่ระบุตัวตนผู้ตอบ แม้แต่ในตารางรายคน* และบังคับด้วยฟังก์ชัน `toPublicRow()` ที่คืนแค่ `seq` / `scores` / `average` / `submittedAt` — ไม่มีทางให้ชื่อหลุดออกจาก service
- `activity-detail.js` บรรทัด 10 ประกาศไว้เองว่าใช้เฉพาะ `topicScores/average/feedback/answeredAt` และหมายเหตุกำกับว่า *ไม่มี user id* — ฟิลด์ `name` / `registrationId` ที่ตัว generator ใส่มาให้ ถูกปล่อยทิ้งไม่ได้ถูก render
- `mock-data.js` บรรทัด 564 อธิบายเหตุผล: เลข "ผู้ตอบ #N" ในตารางเป็นลำดับที่คำนวณตอนแสดงผล ไม่ใช่รหัสของคน

**สรุป:** ไม่ใช่การขัดกัน 3 ทางอย่างที่ผมสรุปไว้รอบก่อน — ของที่ยังมีชีวิตอยู่เห็นตรงกันหมดว่านิรนาม
ส่วนที่เก็บชื่อ/เบอร์โทร (`evaluationRespondents`, `participantActivityHistory.evaluated`) เป็น **ข้อมูลตกค้างจากเวอร์ชันก่อนที่ไม่มีใครอ่านแล้ว**

#### C.3 แต่ "แบบติดตามสุขภาพ" ต้องระบุตัวตน — และนั่นคือคนละแบบประเมิน

`survey-data.js` บรรทัด 7 เขียนโครงตารางที่ตั้งใจไว้ตรง ๆ:

```
survey_responses (person_id, round_id, form_id, submitted_at)
```

มี `person_id` เต็ม ๆ ซึ่งจำเป็น เพราะระบบต้องรู้ว่าใครยังไม่ตอบรอบไหนเพื่อไปตามตัว
ทั้งหน้ารอบติดตามและการส่งแจ้งเตือน LINE ทำงานบนข้อมูลรายคนทั้งหมด

ดังนั้นระบบมีแบบประเมิน **2 ชนิดที่มีข้อกำหนดตรงข้ามกัน** ไม่ใช่ชนิดเดียวที่ตั้งค่าได้:

| | ความพึงพอใจกิจกรรม | ติดตามสุขภาพกลุ่มตัวอย่าง |
|---|---|---|
| ผูกกับ | กิจกรรม + รอบของกิจกรรม | คน + รอบติดตามของคนนั้น |
| ระบุตัวตน | **ห้าม** | **ต้องได้** |
| ตอบซ้ำ | กันไม่ได้ (ไม่รู้ว่าใคร) | กันได้ |
| ใช้ตอบคำถาม | กิจกรรมนี้ดีแค่ไหน | คนนี้เปลี่ยนแปลงอย่างไร |

#### C.4 ข้อเสนอ — แยกตารางตามชนิด ไม่ใช้ flag

ทางเลือกที่เคยเสนอไว้ (ตารางเดียว + `evl_forms.is_anonymous` + CHECK constraint) **ใช้ไม่ได้จริง** — MySQL อนุญาตให้ `CHECK` อ้างได้เฉพาะคอลัมน์ในแถวเดียวกัน จะอ้างข้ามไปอ่าน `is_anonymous` ของตาราง `evl_forms` ไม่ได้ เหลือทางบังคับแค่ trigger หรือ Service layer ซึ่งทั้งคู่พลาดได้ ข้อเสนอนี้จึงยกเลิก

แทนที่ด้วยการแยกตาราง ให้ "ทำผิดไม่ได้ตั้งแต่โครงสร้าง":

| ตาราง | คอลัมน์ | หมายเหตุ |
|---|---|---|
| `evl_satisfaction_responses` | form_id, activity_id, activity_round_id, submitted_at | **ไม่มีคอลัมน์ใดชี้ไปยังคน** — ไม่มี FK ให้ join กลับ แม้จะอยากทำ |
| `evl_satisfaction_receipts` | form_id, registration_id, submitted_at · UNIQUE(form_id, registration_id) | บันทึกแค่ว่า "คนนี้ตอบแล้ว" **ห้ามมีคอลัมน์เชื่อมไปยัง response ใด** |
| `evl_survey_responses` | form_id, cohort_round_id, participant_id, submitted_at | แบบติดตามสุขภาพ — ระบุตัวตนตามที่ `survey-data.js` ระบุ |
| `evl_answers` | response_type, response_id, question_id, score, text_value | ใช้ร่วมกันทั้งสองชนิดผ่าน polymorphic key |

`evl_satisfaction_receipts` คือส่วนที่กู้ความสามารถที่หายไปกลับมา — ตอบได้ว่าใครยังไม่ตอบ (ไว้ทวง) และกันตอบซ้ำ โดยยังไม่รู้ว่าคนนั้นให้คะแนนเท่าไร เพราะไม่มีเส้นเชื่อมระหว่างสองตาราง

**ต้นทุนที่ต้องยอมรับ:** วิเคราะห์แบบ "ผู้สูงอายุให้คะแนนต่างจากวัยทำงานไหม" ทำไม่ได้ ถ้าจำเป็นจริงต้องเก็บข้อมูลประชากรแบบไม่ระบุตัวตน (เพศ/ช่วงอายุ) ลงใน `evl_satisfaction_responses` โดยตรง ซึ่งต้องประเมินความเสี่ยงการระบุตัวตนย้อนกลับก่อน — กิจกรรมที่มีผู้เข้าร่วมน้อย เพศ+ช่วงอายุ+เวลาที่ตอบ อาจชี้ตัวคนได้

**สิ่งที่ต้องยืนยัน:** นิรนามคือข้อกำหนดจากทีมธุรกิจ/จริยธรรมวิจัย หรือเป็นเพียงสิ่งที่ต้นแบบเลือกทำ — ถ้าเป็นอย่างหลังและธุรกิจอยากได้รายคน ต้องแก้ที่หน้าจอ 2 หน้าและกฎในหัวไฟล์ `satisfaction-service.js` ด้วย ไม่ใช่แค่ schema

### ปม D — ผู้ลงทะเบียนมีสองชุด — ✅ ตัดสินแล้ว

`TFC_MOCK.registrations` (REG-0001, 7 แถว) กับ `activityRegistrations[activityId]` (สร้างด้วย LCG seed)
`docs/activity-module.md` บรรทัด 37 บอกว่าจงใจแยกเพื่อไม่ให้จำนวนแถวหน้าเดิมเปลี่ยน

> **ข้อเสนอ:** ในฐานข้อมูลเป็น `act_registrations` ตารางเดียว — การแยกสองชุดเป็นข้อจำกัดของต้นแบบเท่านั้น ไม่ใช่ requirement

### ปม E — ผู้ลงทะเบียนยังไม่ผูกกับผู้เข้าร่วม — ✅ ตัดสินแล้ว

`registrations` เก็บ `name` / `phone` เป็นข้อความล้วน ไม่มี `participantId` — จึงนับ "คนนี้เข้ากี่กิจกรรม" ไม่ได้ ทั้งที่หน้าจอแสดงตัวเลขนั้นอยู่ (`participantsSummary.activitiesJoined`)

> **ข้อเสนอ:** `act_registrations.participant_id` เป็น nullable FK — Walk-in ที่ยังไม่มี profile ปล่อยว่างไว้ แล้วให้แอดมินจับคู่ทีหลังด้วยเบอร์โทร

### ปม F — master list ที่ควรเป็นชุดเดียวกัน แต่ค่าไม่ตรงกัน — ⏳ รอทีมธุรกิจ

| เรื่อง | ค่าที่พบ |
|---|---|
| เพศ | `['หญิง','ชาย','อื่นๆ']` · `['ชาย','หญิง','ไม่ระบุ']` · `['หญิง','ชาย','ไม่ระบุ']` — **3 ชุด** |
| ช่วงอายุ | `registrationOptions.ageRanges` (`ต่ำกว่า 18 ปี`…) vs `cohort.AGES` (`ต่ำกว่า 25 ปี`…) — **คนละเกณฑ์** |
| อาชีพ | `occupations` 7 ค่า vs `cohort.JOBS` 5 ค่า — **คนละชุด** |
| แหล่งที่มา | `activityDataSources` 4 ค่า · `cohort.SOURCES` 5 ค่า · `sourceChannels` 6 ค่า — **3 ชุด คนละความหมาย** |

ปมนี้แยกเป็นสองคำถามที่ตอบคนละแบบ — **"เก็บอย่างไร"** ตัดสินแล้ว · **"ค่าที่ถูกต้องคืออะไร"** ยังรอทีมธุรกิจ แต่ไม่บล็อกเพราะ seed ทีหลังได้โดยไม่แตะโครงตาราง

#### หลักการ — แยกตามว่าค่าจะเปลี่ยนไหม

| ชนิด | วิธีเก็บ | ใช้กับ |
|---|---|---|
| ไม่เปลี่ยนตลอดอายุระบบ | คอลัมน์ code | เพศ |
| เปลี่ยนเป็นครั้งคราว | ตาราง master + FK | อาชีพ · ช่องทางที่รู้จัก · ความสนใจ |
| คำนวณได้ | **ห้ามเก็บ** — เก็บวัตถุดิบแทน | ช่วงอายุ |

#### F.1 ช่วงอายุ — เก็บ `birth_year` ไม่เก็บช่วง

ระบบนี้ติดตามผล 3/6/12 เดือน — คนที่เลือก "45-59 ปี" ตอนลงทะเบียน พอถึงรอบ 12 เดือนอาจข้ามไปช่วง "60 ปีขึ้นไป" แล้ว ถ้าเก็บช่วงตายตัว **รายงานเปรียบเทียบก่อน–หลังจะผิดโดยไม่มีใครรู้**

ปัญหาซ้อนอีกชั้น: `mst_target_groups.age_range` เป็นชุดที่ 3 (`6-18 ปี` / `19-59 ปี` / `60 ปีขึ้นไป` / `ทุกช่วงวัย`) และใช้จับคู่คนเข้ากลุ่มเป้าหมาย — จับคู่อัตโนมัติไม่ได้เลยถ้าเกณฑ์คนละชุด

```
ptp_participants.birth_year   SMALLINT UNSIGNED NULL
act_registrations.birth_year  SMALLINT UNSIGNED NULL
```

เก็บ **ปีเดียว ไม่เก็บวันเกิดเต็ม** — ลดความอ่อนไหวตาม PDPA และพอสำหรับทุกเกณฑ์ที่ระบบใช้
เปลี่ยนเกณฑ์ช่วงอายุเมื่อไหร่ก็คำนวณย้อนหลังได้ทั้งฐานโดยไม่ต้อง migrate

> **สิ่งที่ต้องแก้ที่หน้าจอ:** ฟอร์มลงทะเบียนเปลี่ยนจาก dropdown ช่วงอายุ → dropdown ปีเกิด · กดครั้งเดียวเหมือนกัน ไม่ยากขึ้นสำหรับผู้กรอก

#### F.2 เพศ — คอลัมน์ code 4 ค่า

ค่าสามชุดต่างกันแค่ตัวที่ 3 แต่ `อื่นๆ` กับ `ไม่ระบุ` **คนละความหมาย** (เพศอื่น vs ไม่ต้องการตอบ) ต้องมีทั้งคู่

```
gender ENUM('male','female','other','undisclosed')
```

เก็บเป็น code ไม่ใช่ข้อความไทย — เปลี่ยนคำที่แสดงบนจอได้โดยไม่ต้องแตะข้อมูล

#### F.3 อาชีพ · ช่องทาง · ความสนใจ — ตาราง `mst_options` ตัวเดียว

ถ้าเก็บเป็นข้อความ พอทีมเปลี่ยน `ธุรกิจส่วนตัว` → `ค้าขาย / ธุรกิจส่วนตัว` (ซึ่ง**เกิดขึ้นแล้ว**ระหว่างสองชุดที่มีอยู่) รายงานจะนับแยกเป็นสองอาชีพทันที

รายการแบน ๆ ที่ไม่มีคุณสมบัติอื่นมี 5–6 ชุด พฤติกรรมเหมือนกันหมด → ตารางเดียว ไม่แตกเป็น 5 ตาราง 5 หน้าจอ

`option_group` = `occupation` · `source_channel` · `interest` · `contact_channel` · `note_kind` · `purchase_channel`

**กฎบังคับ:** ลบไม่ได้ ใช้ `is_active = 0` เท่านั้น ไม่งั้นข้อมูลเก่าที่อ้างอยู่จะกลายเป็นกำพร้า

#### F.4 ข้อมูลนำเข้าจากไฟล์ที่ไม่ตรง master

`activityDataSources` มีค่า `นำเข้าจากไฟล์` — ข้อมูลที่ import มาจะมีค่าที่ไม่ตรงรายการแน่นอน

```
occupation_id   FK NULL ได้
occupation_raw  VARCHAR  เก็บข้อความดิบจากไฟล์
```

**ห้ามทิ้งข้อความดิบ** ไม่งั้นตามกลับไปแก้ทีหลังไม่ได้ว่าเดิมเขากรอกว่าอะไร

#### F.5 สิ่งที่ยังรอทีมธุรกิจ

รายการอาชีพชุดจริง (7 ค่า vs 5 ค่า vs ชุดใหม่) และเกณฑ์ช่วงอายุที่ใช้ในรายงาน — ทั้งคู่เป็นข้อมูล seed และ config ไม่ใช่โครงตาราง

### ปม G — Prefix ตาราง — ✅ ตัดสินแล้ว

`docs/database-standard.md` บรรทัด 23–33 กำหนด prefix `act_` `evl_` `usr_` `mst_` `rpt_`
แต่ prompt ข้อ 4.3 บอกให้ใช้ snake_case ตามมาตรฐาน Laravel (ซึ่งไม่มี prefix)

> **ข้อเสนอ:** ใช้ prefix ตาม `database-standard.md` (กฎของโปรเจกต์มาก่อน) ยกเว้น `users` / `sessions` / `cache` / `jobs` ที่เป็นตารางแกนของ Laravel ให้คงชื่อเดิม
> ต้นทุน: ทุก Model ต้องประกาศ `protected $table` — ยอมรับได้

---

## ส่วนที่ 2 — ตารางที่เสนอ (30 ตาราง)

**กติกาที่ใช้ทุกตาราง**
- PK: `id` BIGINT UNSIGNED AUTO_INCREMENT (มาตรฐาน Laravel)
- รหัสที่มนุษย์อ่าน (`ACT-2026-014`, `PID-0001`) เก็บเป็นคอลัมน์ `code` VARCHAR + UNIQUE — **ไม่ใช้เป็น PK** เพื่อไม่ให้ FK ทั้งระบบผูกกับข้อความที่อาจต้องเปลี่ยนรูปแบบภายหลัง
- `created_at` / `updated_at` ทุกตาราง · `deleted_at` เฉพาะตารางที่ระบุ
- `created_by` / `updated_by` = FK → `users.id` (ต้นแบบเก็บเป็นชื่อคนในฟิลด์ `updatedBy` ซึ่งใช้อ้างอิงจริงไม่ได้)
- Charset `utf8mb4` / Collation `utf8mb4_unicode_ci` (ข้อมูลไทยทั้งระบบ)
- เงินเก็บเป็น `DECIMAL(10,2)` ห้ามใช้ FLOAT

### 2.1 ข้อมูลพื้นฐาน — `mst_`

| ตาราง | คอลัมน์หลัก | Index |
|---|---|---|
| `mst_areas` | code, name, province, district, area_type, area_group, start_date, end_date, partner_org, coordinator_name, coordinator_phone, coordinator_position, map_url, status | `code` UQ · `status` · `province, district` |
| `mst_target_groups` | code, name, age_range, target_count, is_active, sort_order | `code` UQ · `is_active` |
| `mst_programs` | code, name, category, status, is_active | `code` UQ · `is_active` |
| `mst_courses` | program_id, name, sort_order | `program_id, sort_order` |
| `mst_instructors` | code, name, phone, photo_path, expertise, bio, is_active | `code` UQ · `is_active` |
| `mst_instructor_expertises` | instructor_id, name | `instructor_id` |
| `mst_instructor_course` | instructor_id, course_id | PK รวม 2 คอลัมน์ |
| `mst_activity_formats` | code, name, icon, is_active | `code` UQ |
| `mst_follow_up_round_templates` | code, name, offset_days, is_active, sort_order, line_notify, notify_days_before | `offset_days` **UQ** · `is_active, sort_order` |
| `mst_districts` | province, name | `province, name` UQ |
| `mst_options` | option_group, code, label, sort_order, is_active | `option_group, code` UQ · `option_group, is_active, sort_order` |

`mst_options` คือรายการแบนที่ไม่มีคุณสมบัติอื่น รวมไว้ตารางเดียวตามข้อ F.3 — `option_group` = `occupation` · `source_channel` · `interest` · `contact_channel` · `note_kind` · `purchase_channel`

`offset_days` ต้อง UNIQUE ตามกติกาที่ `followup-template-service.js` บรรทัด 142 บังคับไว้แล้วฝั่งหน้าจอ — ถ้าซ้ำ คนหนึ่งคนจะได้รอบครบกำหนดวันเดียวกันสองรอบ

### 2.2 ผู้ใช้และสิทธิ์ — `usr_`

| ตาราง | คอลัมน์หลัก | Index |
|---|---|---|
| `users` | code, name, username, email, password, phone, avatar_path, area_id, status, last_login_at, remember_token | `username` UQ · `email` UQ · `area_id` · `status` |
| `usr_roles` | code, name, description, is_active | `code` UQ |
| `usr_role_user` | user_id, role_id | PK รวม 2 คอลัมน์ |
| `usr_role_menu_permissions` | role_id, menu_key, is_allowed | `role_id, menu_key` UQ |

`users.roles[]` ในต้นแบบเป็น array (USR-002 มี 2 บทบาท) จึงต้องเป็น many-to-many ไม่ใช่คอลัมน์เดียว
สิทธิ์แบบกว้าง (`permissions.activities` ฯลฯ) **ไม่เก็บ** — คำนวณจาก `usr_role_menu_permissions` ตามที่ `mock-data.js` บรรทัด 913–925 ทำอยู่แล้ว

### 2.3 ผู้เข้าร่วมและกลุ่มตัวอย่าง — `ptp_`

| ตาราง | คอลัมน์หลัก | Index |
|---|---|---|
| `ptp_participants` | code, person_code, name, phone, email, gender, birth_year, occupation_id, occupation_raw, target_group_id, area_id, source, contact_channel_id, has_caregiver, caregiver_name, caregiver_relation, caregiver_phone, status, project_status, consent_status, line_user_id | `code` UQ · `person_code` UQ · `phone` · `line_user_id` **UQ** · `area_id, target_group_id` · `status` |
| `ptp_consents` | participant_id, status, consent_version, consented_at, file_path, note, recorded_via, recorded_by | `participant_id, created_at` |
| `ptp_cohort_profiles` | participant_id, cohort_code, entry_date, source_type, stopped_at, stopped_reason, stopped_by | `participant_id` UQ · `cohort_code` UQ · `entry_date` |
| `ptp_follow_up_rounds` | cohort_profile_id, template_id, name, offset_days, due_date, answered_at | `cohort_profile_id, offset_days` UQ · `due_date` · `answered_at` |
| `ptp_follow_up_notes` | participant_id, source, kind, noted_at, body, created_by | `participant_id, noted_at` |
| `ptp_purchases` | participant_id, store_name, channel, order_date, status, amount | `participant_id, order_date` |
| `ptp_purchase_items` | purchase_id, product_name, quantity | `purchase_id` |

**จุดสำคัญ:** `ptp_follow_up_rounds` เก็บ `name` และ `offset_days` เป็น **snapshot** ไม่ join กลับไปที่ template
เป็นกติกาที่เขียนไว้ชัดใน `cohort-data.js` บรรทัด 94–97 — ถ้าอ่าน template สด พอแอดมินแก้จำนวนวัน วันครบกำหนดของคนที่ตอบไปแล้วจะขยับทั้งกระดาน
`template_id` เก็บไว้อ้างอิงเฉย ๆ ต้องเป็น `ON DELETE SET NULL` ห้าม cascade

`line_user_id` UNIQUE คือกติกาความปลอดภัยที่ `cohort-data.js` บรรทัด 156–165 ระบุไว้: LINE บัญชีเดียวผูกได้คนเดียว ผูกซ้อนต้องถูกปฏิเสธ

### 2.4 กิจกรรม — `act_`

| ตาราง | คอลัมน์หลัก | Index |
|---|---|---|
| `act_activities` | code, name, description, type, participant_type, program_id, course_id, format_id, data_source, venue_mode, registration_mode, status, has_fee, fee, capacity, organizer, cover_image_path, start_date, end_date, checkin_start_at, checkin_end_at, is_published, publish_start_at, publish_end_at, visibility, is_featured | `code` UQ · `status` · `start_date` · `is_published, publish_start_at, publish_end_at` · `program_id` · `participant_type` |
| `act_activity_reg_fields` | activity_id, field_key, is_enabled, is_required, sort_order | `activity_id, field_key` UQ |
| `act_activity_area` | activity_id, area_id | PK รวม |
| `act_activity_instructor` | activity_id, instructor_id | PK รวม |
| `act_activity_target_group` | activity_id, target_group_id | PK รวม |
| `act_activity_rounds` | activity_id, round_date, time_start, time_end, location, capacity | `activity_id, round_date` |
| `act_registrations` | code, activity_id, activity_round_id, participant_id, name, phone, email, gender, birth_year, occupation_id, occupation_raw, source_channel_id, dietary_note, payment_status, checkin_status, registered_at, checked_in_at, is_manual_entry | `code` UQ · `activity_id, checkin_status` · `activity_id, payment_status` · `participant_id` · `phone` · `registered_at` |
| `act_registration_interests` | registration_id, option_id | `registration_id` · PK รวม 2 คอลัมน์ |

ต้นแบบมีทั้ง `area` (เดี่ยว) และ `areaList[]`, ทั้ง `instructor` และ `instructorList[]` — เก็บเฉพาะตาราง pivot ตัวเดียว ฟิลด์เดี่ยวเป็นร่องรอยของเวอร์ชันเก่า

### 2.5 แบบประเมิน — `evl_`

| ตาราง | คอลัมน์หลัก | Index |
|---|---|---|
| `evl_forms` | code, name, type, status, is_anonymous, created_by | `code` UQ · `status` |
| `evl_questions` | form_id, sort_order, question_type, text, dimension, is_required | `form_id, sort_order` |
| `evl_form_activity` | form_id, activity_id | PK รวม |
| `evl_satisfaction_responses` | form_id, activity_id, activity_round_id, submitted_at | `activity_id, submitted_at` · `activity_round_id` |
| `evl_satisfaction_receipts` | form_id, registration_id, submitted_at | `form_id, registration_id` UQ |
| `evl_survey_responses` | form_id, participant_id, cohort_round_id, submitted_at | `participant_id, submitted_at` · `cohort_round_id` UQ |
| `evl_answers` | response_type, response_id, question_id, score, text_value | `response_type, response_id` · `question_id` |
| `evl_round_batches` | code, name, due_from, due_to, form_id, state, created_by | `state` · `due_from, due_to` |
| `evl_round_batch_members` | batch_id, cohort_profile_id, follow_up_round_id, notified_at, notify_result, offline_kind, offline_note, offline_at, offline_by | `batch_id` · `cohort_profile_id` |

`satisfactionTopics` (5 หัวข้อ) และ `evaluationTopics` (6 หัวข้อ) กลายเป็นแถวใน `evl_questions` ของฟอร์มมาตรฐาน ไม่ต้องมีตารางหัวข้อแยก — คอลัมน์ `dimension` รองรับการจัดกลุ่มรายด้านสำหรับ Radar Chart

`evl_satisfaction_responses` กับ `evl_satisfaction_receipts` **ต้องไม่มีคอลัมน์ใดเชื่อมถึงกัน** — เป็นข้อบังคับของปม C.4 ไม่ใช่การออกแบบที่ลืมใส่ FK ให้เขียนหมายเหตุนี้กำกับไว้ในไฟล์ Migration ด้วย

`activity_round_id` อ้าง `act_activity_rounds` — ใช้ชื่อ "rounds" ตามที่ `satisfaction-service.js` บรรทัด 103 คาดไว้ (`activity_rounds`) ไม่ใช่ "sessions" · ต้นแบบเก็บชุดนี้ใน `TFC_MOCK.activitySessions` ซึ่งเป็นชื่อฝั่งหน้าจอเท่านั้น

### 2.6 ระบบ — `sys_`

| ตาราง | คอลัมน์หลัก | Index |
|---|---|---|
| `sys_notifications` | user_id, title, detail, type, read_at | `user_id, read_at` |
| `sys_activity_logs` | user_id, action, subject_type, subject_id, detail | `subject_type, subject_id` · `created_at` |

---

## ส่วนที่ 3 — ค่าที่ห้ามเก็บลงตาราง (ต้องคำนวณสด)

ต้นแบบเก็บตัวเลขสรุปไว้ในออบเจ็กต์เดียวกับข้อมูลหลัก ซึ่งใช้ได้กับข้อมูลจำลอง แต่ในฐานข้อมูลจริงจะกลายเป็นตัวเลขที่ขัดกันเองทันทีที่ข้อมูลเปลี่ยน — `mock-data.js` บรรทัด 559–562 เตือนเรื่องนี้ไว้เอง

| ค่า | ที่มาที่ถูกต้อง |
|---|---|
| `activities.registered` | `COUNT(act_registrations)` |
| `areas.activityCount` / `totalParticipants` / `avgSatisfaction` | JOIN + aggregate |
| `targetGroups.memberCount` / `avgScoreChange` | JOIN + aggregate |
| `programs.activityCount` · `instructors.activityCount` | `COUNT` |
| `roles.userCount` | `COUNT(usr_role_user)` |
| `evaluationForms.questionCount` / `responseCount` | `COUNT` |
| คะแนนเฉลี่ย · การกระจายดาว · ระดับความพึงพอใจ | คำนวณจาก `evl_answers` |
| สถานะรอบติดตาม (ยังไม่ถึงกำหนด/รอติดตาม/เกินกำหนด) | คำนวณจาก `due_date` เทียบวันปัจจุบัน |
| สถานะรายคน (กำลังติดตาม/ติดตามครบ/หลุดการติดตาม) | คำนวณจากสถานะรอบทั้งหมดของคนนั้น |

ค่าที่คำนวณหนักและไม่เปลี่ยนบ่อย (สรุปแดชบอร์ด) ให้ทำ Cache ตาม prompt ข้อ 4.5 — ไม่ใช่เก็บเป็นคอลัมน์

**ข้อยกเว้นที่ต้องเก็บ:** `ptp_follow_up_rounds.offset_days` และ `name` เป็น snapshot โดยตั้งใจ (ดูหัวข้อ 2.3) ห้ามเปลี่ยนเป็นการคำนวณสด

---

## ส่วนที่ 4 — ลำดับการสร้าง Migration

เรียงตาม dependency ของ FK — ต้องรันตามลำดับนี้

1. `users` → `usr_roles` → `usr_role_user` → `usr_role_menu_permissions`
2. `mst_options` → `mst_districts` → `mst_areas` → `mst_target_groups` → `mst_programs` → `mst_courses` → `mst_instructors` → `mst_instructor_expertises` → `mst_instructor_course` → `mst_activity_formats` → `mst_follow_up_round_templates`
3. `ptp_participants` → `ptp_cohort_profiles` → `ptp_follow_up_rounds` → `ptp_follow_up_notes` → `ptp_purchases` → `ptp_purchase_items`
4. `act_activities` → pivot 3 ตาราง → `act_activity_rounds` → `act_registrations` → `act_registration_interests`
5. `evl_forms` → `evl_questions` → `evl_form_activity` → `evl_satisfaction_responses` → `evl_satisfaction_receipts` → `evl_survey_responses` → `evl_answers` → `evl_round_batches` → `evl_round_batch_members`
6. `sys_notifications` → `sys_activity_logs`

`users.area_id` อ้าง `mst_areas` ซึ่งสร้างทีหลัง — ให้แยก FK constraint ออกเป็น migration ต่างหากในกลุ่มที่ 2

---

## ส่วนที่ 5 — Backup และ Rollback

**ก่อนรันครั้งแรก:** ยังไม่มีข้อมูลใดในระบบ จึงไม่ต้อง backup — แต่ให้สร้าง database เปล่าแยกชื่อไว้ ไม่ใช้ database ที่มีข้อมูลของระบบอื่นอยู่แล้วบนเครื่องเดียวกัน

**คำสั่ง backup ก่อนรัน migration รอบถัด ๆ ไป**

```bash
mysqldump -u [DB_USERNAME] -p --single-transaction --routines [DB_DATABASE] > backup_$(date +%Y%m%d_%H%M%S).sql
```

**Rollback**

```bash
php artisan migrate:rollback --step=1
```

ทุก Migration ต้องเขียนเมธอด `down()` ให้ครบ และต้องทดสอบ `migrate:rollback` ได้จริงก่อน merge
FK ทั้งหมดตั้งเป็น `RESTRICT` เป็นค่าเริ่มต้น — ใช้ `CASCADE` เฉพาะตารางลูกที่ไม่มีความหมายเมื่อแม่หายไป (`evl_answers`, `ptp_purchase_items`, `act_registration_interests`, ตาราง pivot ทั้งหมด)

---

## ส่วนที่ 6 — สิ่งที่ยังตอบไม่ได้ ต้องถามทีมธุรกิจ

1. **State machine ของสถานะกิจกรรม** — `mock-data.js` บรรทัด 230 ระบุเองว่า 3 ใน 7 ค่าเป็นค่าใหม่ที่ "รอยืนยัน" และ `docs/activity-module.md` ข้อ 3 บอกว่ายังไม่มี state machine
2. **ช่วงอายุและอาชีพชุดที่ถูกต้อง** (ปม F.5) — เป็นข้อมูล seed และ config ไม่ใช่โครงตาราง ใส่ทีหลังได้
3. ~~**เกณฑ์ "หลุดการติดตาม"**~~ — ✅ ตัดสินแล้ว: เก็บเป็น config (`config/farmconcept.php`) ค่าเริ่มต้น "เกินกำหนด ≥ 2 รอบ" ตาม `cohort-data.js` บรรทัด 126 — ทีมธุรกิจเปลี่ยนค่าทีหลังได้โดยไม่ต้องแก้โค้ดหรือ Migration
4. ~~**ช่วงติดตาม −7 ถึง +14 วัน**~~ — ✅ ตัดสินแล้ว: เก็บเป็น config เช่นกัน ค่าเริ่มต้น −7/+14 ตามต้นแบบ
5. **การเก็บข้อมูลสุขภาพ** — โมดูลชื่อ "ประเมินการเปลี่ยนแปลงสุขภาพ" แต่ต้นแบบยังไม่มีฟิลด์ข้อมูลสุขภาพจริงเลยสักตัว (มีแค่ metadata ว่าตอบเมื่อไหร่) ถ้าจะเก็บค่าตรวจสุขภาพต้องออกแบบเพิ่ม และเป็นข้อมูลอ่อนไหวตาม PDPA

---

## ส่วนที่ 7 — ส่วนขยายจากข้อกำหนดเพิ่มเติม (โมดูลกิจกรรม + ประเมินสุขภาพ)

ข้อกำหนดชุดนี้ **ตรงกับที่ต้นแบบทำไว้แล้วเกือบทั้งหมด** — ส่วนที่ขาดคือชั้นฐานข้อมูลรองรับ ซึ่ง `activity-create.js` บรรทัด 859 เขียนเตือนไว้เองว่า *"ชุดข้อมูลกลางยังไม่มีฟิลด์ ต้องลงทะเบียนไหม / มีแบบประเมินหลังจบไหม ตรง ๆ"*

### 7.1 เงื่อนไขของกิจกรรม — 4 สวิตช์ที่เปิด/ปิดอิสระ

`activity-create.js` บรรทัด 48–57 ประกาศไว้ชัดว่าเป็น *"สามขั้นตอนที่เป็นอิสระต่อกัน ติ๊กแยกกันได้ทั้งหมด"* — `reg` / `chk` / `survey` บวกกับค่าใช้จ่ายที่แยกอยู่แล้ว รวมเป็น 4 สวิตช์

**คอลัมน์ใหม่ใน `act_activities`**

| คอลัมน์ | ชนิด | ผลที่ตามมา |
|---|---|---|
| `requires_registration` | BOOL | เปิดหน้าลงทะเบียน + สร้าง `act_registrations` ได้ |
| `requires_checkin` | BOOL | สร้าง QR เช็คอิน + เปิดหน้าเช็คอินหน้างาน |
| `has_post_survey` | BOOL | ผูกแบบประเมินหลังจบ + สร้าง QR แบบประเมิน |
| `has_fee` | BOOL (มีอยู่แล้ว) | บังคับแนบสลิปใต้แบบลงทะเบียน + เปิดคิวตรวจสลิป |

`is_published` เป็นเงื่อนไขคร่อมทั้งหมด — `activity-create.js` บรรทัด 517 ระบุว่า QR ใช้ได้จริงหลังเผยแพร่เท่านั้น

### 7.2 แบบประเมินของกิจกรรมมี 2 ช่อง ไม่ใช่ช่องเดียว

`activity-create.js` บรรทัด 66 และ 73 แยกไว้แล้ว: ชุดที่ `type` มีคำว่า "ลงทะเบียน" เข้าช่อง *ตอนลงทะเบียน* ที่เหลือเข้าช่อง *หลังจบ*

นี่คือคำตอบว่าทำไมข้อ 1.3 กับ 1.5 ไม่ขัดกัน — เป็นคนละช่อง คนละกติกาความเป็นส่วนตัว:

| ช่อง | เก็บที่ | ระบุตัวตน | เหตุผล |
|---|---|---|---|
| ตอนลงทะเบียน (1.3) | `evl_survey_responses` ผูก `registration_id` | **ได้** | เป็นส่วนหนึ่งของการจองที่นั่ง ต้องรู้ว่าใครจอง |
| หลังจบกิจกรรม (1.5) | `evl_satisfaction_responses` | **ไม่ได้** | ตามปม C |

**ตาราง `evl_form_activity` เพิ่มคอลัมน์ `slot`** ค่า `registration` \| `post_survey` — แทนที่จะเป็น FK สองคอลัมน์บน `act_activities` เพราะต้นแบบเก็บ `evaluationFormIds[]` เป็น array อยู่แล้ว (ผูกได้มากกว่าหนึ่งชุดต่อช่อง)

### 7.3 การชำระเงินและสลิป — ตารางใหม่

| ตาราง | คอลัมน์หลัก | Index |
|---|---|---|
| `act_payment_slips` | registration_id, file_path, amount, transferred_at, status, reviewed_by, reviewed_at, reject_reason | `registration_id` · `status` |

- **ไม่ UNIQUE ที่ `registration_id`** — คนที่สลิปถูกปฏิเสธต้องแนบใหม่ได้ ประวัติเดิมต้องอยู่ครบ
- `status` ใช้ชุดเดียวกับ `paymentStatuses` ที่ต้นแบบมีแล้ว (ชำระแล้ว / รอตรวจสอบ / ยังไม่ชำระ / ปฏิเสธ)
- `act_registrations.payment_status` = สถานะของสลิปล่าสุด ไม่ใช่ค่าที่คีย์แยก
- ไฟล์สลิปเป็นข้อมูลส่วนบุคคล — เก็บนอก `public/` และเสิร์ฟผ่าน route ที่ตรวจสิทธิ์เสมอ

### 7.4 QR ของระบบ — ตารางใหม่

ต้นแบบมี QR 3 ชนิดที่ `activity-create.js` บรรทัด 88–92 (ลงทะเบียน `/r/` · เช็คอิน `/c/` · แบบประเมิน `/s/`)

**QR ประชาสัมพันธ์ = QR ลงทะเบียน — ✅ ตัดสินแล้วว่าไม่แยก**
`public/activities.html` เป็นหน้าเดียวที่ไล่ตั้งแต่รายละเอียดกิจกรรม → ปุ่มลงทะเบียน → ฟอร์ม → ชำระเงิน → สำเร็จ ปลายทางของทั้งสองอย่างคือหน้าเดียวกัน จึงไม่มีเหตุผลให้มีสองแถว

| ตาราง | คอลัมน์หลัก | Index |
|---|---|---|
| `act_qr_codes` | activity_id (nullable), purpose, token, target_url, is_active, expires_at, scan_count | `token` UQ · `activity_id, purpose` UQ |

`purpose` = `public` \| `checkin` \| `post_survey` \| `health` — **4 ชนิด**

**ทำไมชื่อ `public` ไม่ใช่ `register`** — ถ้าตั้งชื่อว่า `register` แล้วผูกการสร้างไว้กับสวิตช์ "ลงทะเบียน" ตามกฎด้านล่าง กิจกรรมที่ไม่เปิดลงทะเบียน (เช่น ตลาดนัดที่เดินเข้าร่วมได้เลย) จะ **ไม่มี QR เลยสักอัน** ทั้งที่ยังต้องประชาสัมพันธ์
`public` = QR ของหน้ากิจกรรมสาธารณะ **สร้างเสมอทุกกิจกรรม** ไม่ผูกกับสวิตช์ใด — ตัวหน้าเว็บเป็นผู้ตัดสินเองว่าจะแสดงฟอร์มลงทะเบียนต่อท้ายหรือไม่ ตาม `requires_registration`

`activity_id` เป็น NULL ได้ เพื่อรองรับ **QR ถาวรของระบบติดตามสุขภาพ** ซึ่งเป็นแถวเดียวทั้งระบบ (ดู 7.6)

**วงจรชีวิตของ QR รายกิจกรรม (รวม QR ประชาสัมพันธ์)** — ✅ ตัดสินแล้ว

| ขั้น | เกิดอะไร |
|---|---|
| ยังไม่บันทึกกิจกรรม | ยังไม่มีแถวใน `act_qr_codes` — ไม่มี `activity_id` ให้ผูก |
| บันทึกกิจกรรมแล้ว (แม้ยังเป็นฉบับร่าง) | สร้างแถว `public` **เสมอ** + แถว `checkin` / `post_survey` ตามสวิตช์ที่เปิด · ทั้งหมด `is_active = 0` |
| เผยแพร่กิจกรรม | `is_active = 1` — สแกนแล้วใช้งานได้จริง |
| ปิดสวิตช์ภายหลัง | ตั้ง `is_active = 0` ของแถวนั้น **ห้ามลบแถว** — QR ที่พิมพ์แจกไปแล้วต้องสแกนแล้วเจอหน้าอธิบาย ไม่ใช่ 404 |

แยก "สร้างแถว" ออกจาก "เปิดใช้งาน" เพราะข้อกำหนดบอกว่าผูก QR กับกิจกรรมได้หลังบันทึก แต่ `activity-create.js` บรรทัด 517 ระบุว่า QR ใช้งานได้จริงหลังเผยแพร่ — คอลัมน์ `is_active` รองรับทั้งสองข้อพร้อมกัน โดยแอดมินเห็นและดาวน์โหลด QR ไปเตรียมงานได้ตั้งแต่ยังเป็นฉบับร่าง

`token` ต้องสุ่ม ไม่ derive จาก `activity_id` — ไม่งั้นเดา URL ของกิจกรรมที่ยังไม่เผยแพร่ได้

### 7.5 บันทึกการเช็คอิน — ตารางใหม่

`checkin-service.js` บรรทัด 12–14 บังคับไว้ว่าการยกเลิกเช็คอินต้องมี audit log ฝั่งเซิร์ฟเวอร์ และบรรทัด 122 แยก `scan` (ผู้เข้าร่วมสแกนเอง) ออกจาก `staff` (เจ้าหน้าที่กดให้)

| ตาราง | คอลัมน์หลัก | Index |
|---|---|---|
| `act_checkin_logs` | registration_id, action, method, performed_by, performed_at | `registration_id, performed_at` |

`action` = `check_in` \| `undo` · `method` = `scan` \| `staff`

`act_registrations.checked_in_at` = เวลาจากแถวล่าสุดที่ `action = check_in` — **เวลาต้องมาจากนาฬิกาเซิร์ฟเวอร์เท่านั้น** ตามกฎข้อ 2 ของ `checkin-service.js` เพราะนาฬิกาเครื่องหน้างานตั้งเองได้

### 7.6 ประเมินสุขภาพ — QR เดียวทั้งโครงการ

`admin/evaluations/rounds.html` บรรทัด 56–58 ทำไว้แล้วตรงตามข้อกำหนด: *"QR ถาวร สร้างครั้งเดียว ใช้ตลอดโครงการ · URL ไม่มีรหัสคน รหัสรอบ หรือรหัสแบบประเมินอยู่ข้างใน · แบบประเมินที่ผู้ตอบได้เห็น มาจากรอบของเขา ไม่ได้มาจาก QR"* — ลิงก์คือ `farmconcept.th/h`

**ผลต่อ schema:** ไม่ต้องมีตาราง token รายคน — ต่างจากที่ `cohort-data.js` บรรทัด 150–153 จำลองไว้ (`surveyLink()` สร้าง token จาก pid+round) ซึ่งเป็นข้อจำกัดของต้นแบบ ไม่ใช่ข้อกำหนด

ลำดับการทำงานจริง: สแกน QR → ยืนยันตัวตน → ระบบหาว่าคนนี้มีรอบไหนเปิดอยู่ → แสดงแบบประเมินของรอบนั้น

**ตารางใหม่สำหรับการยืนยันตัวตน**

| ตาราง | คอลัมน์หลัก | Index |
|---|---|---|
| `ptp_verification_codes` | participant_id, channel, destination, code_hash, expires_at, used_at, attempts | `participant_id, expires_at` |

`channel` = `phone` \| `email` \| `line`

**เหตุผลที่ต้องมี:** ถ้าให้กรอกแค่เบอร์โทรแล้วเข้าได้เลย ใครก็ตอบแทนคนอื่นได้ด้วยการเดาเบอร์ — และข้อมูลสุขภาพเป็นข้อมูลอ่อนไหวตาม PDPA
`code_hash` เก็บเป็น hash ไม่เก็บรหัสดิบ · `attempts` ไว้ล็อกเมื่อกรอกผิดหลายครั้ง
ผู้ที่ผูก LINE แล้วข้ามขั้นนี้ได้ เพราะ `line_user_id` เป็น UNIQUE อยู่แล้วและยืนยันตัวตนมาตั้งแต่ตอนผูก

### 7.7 กลุ่มตัวอย่างที่มาจากผู้เข้าร่วมกิจกรรม

**คอลัมน์ใหม่ใน `ptp_cohort_profiles`**

| คอลัมน์ | ความหมาย |
|---|---|
| `source_type` | `activity` \| `walk_in` \| `import` \| `research` — มาจากช่องทางไหน |
| `source_registration_id` | nullable FK → `act_registrations` — ถ้ามาจากกิจกรรม มาจากการลงทะเบียนครั้งไหน |

ทำให้ตอบได้ว่า "กิจกรรมไหนป้อนคนเข้ากลุ่มตัวอย่างได้มากที่สุด" โดยไม่ต้องเดาจากชื่อ

### 7.8 ช่องโหว่ที่รับทราบและยอมรับแล้ว — ✅ ตัดสินแล้ว

**กิจกรรมที่ไม่เปิดลงทะเบียน แต่มีแบบประเมินหลังจบ** — ไม่มี `registration_id` ให้บันทึกลง `evl_satisfaction_receipts` จึงกันคนตอบซ้ำไม่ได้และตอบไม่ได้ว่าใครยังไม่ตอบ

> **คำตัดสิน: ยอมรับ** — กิจกรรมแบบนี้ได้แค่คะแนนรวมและจำนวนผู้ตอบ ไม่มีอัตราการตอบ
> ทางเลือกอื่นถูกปฏิเสธเพราะการบังคับให้เปิดลงทะเบียนก่อนขัดหลัก "สวิตช์อิสระ" ที่โค้ดยึดไว้ ส่วน cookie ต่ออุปกรณ์กันไม่ได้จริง

**สิ่งที่ต้องทำตามคำตัดสินนี้**
1. หน้าสร้างกิจกรรม: เมื่อติ๊ก "ทำแบบประเมิน" โดยไม่ติ๊ก "ลงทะเบียน" ให้ขึ้นข้อความบอกแอดมินว่ากิจกรรมนี้จะไม่มีอัตราการตอบและกันตอบซ้ำไม่ได้
2. หน้ารายงานความพึงพอใจ: ซ่อนการ์ด "อัตราการตอบ" เมื่อกิจกรรมไม่มีข้อมูลผู้เข้าร่วมจริง — ห้ามแสดงเป็น 0% เพราะจะอ่านผิดว่าไม่มีใครตอบ
3. `satisfaction-service.js` กฎข้อ 2 (อัตราการตอบ = ผู้ตอบ ÷ ผู้เข้าร่วมจริง) ต้องคืนค่า `null` ไม่ใช่ `0` เมื่อไม่มีตัวหาร

### 7.9 สรุปส่วนขยาย

- ตารางใหม่จากส่วนที่ 7: `act_payment_slips` · `act_qr_codes` · `act_checkin_logs` · `ptp_verification_codes` (`evl_form_activity` เดิมเพิ่มคอลัมน์ `slot`)
- ตารางใหม่จากปม F: `mst_options`
- คอลัมน์ใหม่: `act_activities` 3 คอลัมน์ · `ptp_cohort_profiles` 2 คอลัมน์
- ตารางใหม่จากส่วนที่ 8: `ptp_consents` · `act_activity_reg_fields`
- **รวมทั้งระบบ 44 ตาราง** (ไม่นับตารางแกนของ Laravel 9 ตาราง — `users` เป็นตารางแกนที่ขยายคอลัมน์เพิ่ม ไม่ได้สร้างใหม่)

---

## ส่วนที่ 8 — แบบลงทะเบียน 3 ชั้น

ข้อกำหนดจากเจ้าของโปรเจกต์: ฟอร์มลงทะเบียนไม่ใช่ "คำถามอิสระทั้งใบ" แต่มี 3 ชั้นที่มีอิสระต่างกัน

### ชั้นที่ 1 — ฟิกตายตัว แก้ไม่ได้ ปิดไม่ได้

**ชื่อ · เบอร์โทร · ความยินยอม PDPA**

เป็นแกนของตัวตน ถ้าปล่อยเป็นคำถามอิสระเมื่อไร ระบบจะ dedupe คนไม่ได้ ออก `person_code` ไม่ได้ และผูก LINE ไม่ได้

| ข้อมูล | เก็บที่ |
|---|---|
| ชื่อ · เบอร์โทร | คอลัมน์ตายตัวบน `act_registrations` และ `ptp_participants` |
| ความยินยอม | **ตาราง `ptp_consents` ของตัวเอง** ไม่ใช่คอลัมน์ ไม่ใช่คำตอบในฟอร์ม |

`ptp_consents` เป็นตาราง **append-only** — เปลี่ยนสถานะเมื่อไหร่ให้ INSERT แถวใหม่ ห้าม UPDATE แถวเดิม
ไม่งั้นพิสูจน์ย้อนหลังไม่ได้ว่า ณ วันที่เก็บข้อมูล เขายินยอมไว้จริง ซึ่งเป็นหัวใจของการตรวจสอบตาม PDPA
`ptp_participants.consent_status` เก็บสถานะปัจจุบันไว้กรองในตารางเท่านั้น **ห้ามใช้เป็นหลักฐาน**

เบอร์โทรเป็นกุญแจ dedupe → เมื่อมีการลงทะเบียนใหม่ ระบบจับคู่ `act_registrations.participant_id` ให้อัตโนมัติจากเบอร์ ไม่ปล่อยให้แอดมินจับคู่เอง (ต่อยอดจากปม E)

### ชั้นที่ 2 — ฟิกโครงไว้ เปิด/ปิดรายกิจกรรมได้

**เพศ · ปีเกิด · อาชีพ · พื้นที่ · กลุ่มเป้าหมาย · ช่องทางที่รู้จัก**

กิจกรรมเลือกได้แค่ **"ถามหรือไม่ถาม"** และ **"บังคับหรือไม่บังคับ"** — ตั้งชื่อตัวเลือกเองไม่ได้

ถ้าปล่อยอิสระ กิจกรรม A เขียน `วัยทำงาน` กิจกรรม B เขียน `คนทำงาน` แล้วโดนัทกับรายงานจะรวมข้ามกิจกรรมไม่ได้ — เป็นปัญหาเดียวกับปม F ที่เกิดขึ้นแล้วในข้อมูลต้นแบบ

```
act_activity_reg_fields (activity_id, field_key, is_enabled, is_required, sort_order)
field_key ∈ {gender, birth_year, occupation, area, target_group, source_channel}   ← ปิดตาย
```

คำตอบลงคอลัมน์ **ชุดเดียวกับ `ptp_participants` เสมอ** ฟิลด์ที่กิจกรรมปิดไว้เป็น NULL ไม่ใช่ไปเก็บที่อื่นคนละรูปแบบ

> **หมายเหตุเรื่อง "ช่วงอายุ" กับปม F.1** — ชั้นที่ 2 ระบุ "ช่วงอายุ" แต่ปม F.1 ตัดสินว่าเก็บ `birth_year` เพราะช่วงอายุขยับเมื่อเวลาผ่านไป
> ทางออก: `field_key` ใช้ชื่อ `birth_year` · ฟอร์มถามปีเกิด · **รายงานแสดงเป็นช่วง** โดยคำนวณจาก `config/farmconcept.php` → `age_bands`
> ผู้กรอกยังกดครั้งเดียวเหมือนเดิม และเปลี่ยนเกณฑ์ช่วงเมื่อไหร่ รายงานย้อนหลังทั้งฐานเปลี่ยนตามทันที

### ชั้นที่ 3 — คำถามอิสระเฉพาะกิจกรรม

คือ `evl_forms` ที่ผูกด้วย `evl_form_activity.slot = 'registration'` (ข้อ 7.2)
คำตอบไปที่ `evl_answers` ไม่ปนกับคอลัมน์ของชั้นที่ 1–2

| ชั้น | อิสระแค่ไหน | เก็บที่ | รวมรายงานข้ามกิจกรรม |
|---|---|---|---|
| 1 | ไม่มีเลย | คอลัมน์ตายตัว + `ptp_consents` | ได้ |
| 2 | เปิด/ปิด + บังคับ/ไม่บังคับ | คอลัมน์ตายตัว คุมด้วย `act_activity_reg_fields` | ได้ |
| 3 | ตั้งคำถามเองได้ทั้งหมด | `evl_answers` | ไม่ได้ — ตามธรรมชาติของคำถามอิสระ |

---

## ส่วนที่ 9 — สถานะการติดตั้งและผลทดสอบ

### สภาพแวดล้อมจริงบนเครื่อง

| รายการ | ค่า |
|---|---|
| Laravel | 13.24.0 |
| PHP | 8.4.23 (Herd) — **ไม่ใช่ 8.1 ของ XAMPP** ซึ่ง Laravel 13 ไม่รองรับ |
| MySQL | 8.0.42 (Oracle) ติดตั้งเป็น Windows Service ชื่อ `MySQL80` |
| Client | `C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe` |
| Database | `farmconcept` · utf8mb4 / utf8mb4_unicode_ci |

Service `mysql` ของ XAMPP หยุดอยู่ — client ของ XAMPP เชื่อมต่อไม่ได้เพราะเป็นคนละเวอร์ชัน (`caching_sha2_password` โหลดไม่ขึ้น) ให้ใช้ client ตามตารางข้างบนเสมอ

คำสั่ง artisan ต้องเรียกผ่าน PHP ของ Herd:

```bash
& "C:\Users\maywi\.config\herd\bin\php.bat" artisan migrate:status
```

### ผลทดสอบตามเกณฑ์ตรวจรับข้อ 7

| เกณฑ์ | ผล |
|---|---|
| เชื่อมต่อ MySQL Local สำเร็จ · `migrate:status` ผ่าน | ✅ ผ่าน — 10 migration แสดงสถานะ Ran ครบ |
| `migrate` สร้างตารางครบ | ✅ 53 ตาราง (44 ของโปรเจกต์ + 9 แกน Laravel) |
| `migrate:rollback` ถอยกลับได้จริง | ✅ ผ่าน — จาก 53 ตารางเหลือ 1 แล้ว migrate กลับคืนได้ |
| ป้องกัน N+1 · Index ครบ | ✅ ผ่าน — 6 query คงที่สำหรับหน้ารายการกิจกรรม |
| Build asset ผ่าน Vite | ✅ ผ่าน — `npm run build` สำเร็จ ไม่มี error · CSS 293 kB (gzip 38.5) · JS 46.7 kB (gzip 12.3) |
| `docs/coding-standards.md` | ✅ เสร็จแล้ว |

### ปัญหาที่เจอระหว่างติดตั้งและวิธีแก้

**1. ชื่อ index ยาวเกิน 64 ตัวอักษร**
Laravel ตั้งชื่อ index อัตโนมัติเป็น `act_activities_is_published_publish_start_at_publish_end_at_index` = 66 ตัว เกินเพดานของ MySQL
SQLite ไม่บ่น จึงไม่เจอตอนทดสอบด้วย SQLite → **ต้องตั้งชื่อเองทุกครั้งที่ index มีตั้งแต่ 3 คอลัมน์ขึ้นไป**

**2. `down()` ที่ drop คอลัมน์ต้องถอด index ก่อน**
ไม่งั้น rollback ล้มด้วย `error in index ... after drop column` และ MySQL ทิ้ง index กำพร้าไว้

**3. รหัสผ่านใน `.env` ที่มีอักขระ `#` ต้องครอบด้วยเครื่องหมายคำพูด**
ไม่งั้น `#` ถูกตีความเป็นคอมเมนต์ ค่าถูกตัดกลางคัน โดยไม่มี error บอก — อาการที่เห็นคือ `Access denied` เฉย ๆ

```
DB_PASSWORD="รหัสที่มี#อยู่ข้างใน"
```

**4. MySQL ไม่ rollback DDL**
Migration ที่ล้มกลางคันจะทิ้งตารางที่สร้างไปแล้วค้างไว้ โดยไม่บันทึกลงตาราง `migrations`
ตอนพัฒนาที่ยังไม่มีข้อมูลจริงให้ใช้ `migrate:fresh` ล้างแล้วรันใหม่ — **ห้ามใช้กับฐานที่มีข้อมูลจริงเด็ดขาด**

### Seeder — ✅ เสร็จแล้ว

| ไฟล์ | เนื้อหา |
|---|---|
| `database/seeders/MasterDataSeeder.php` | `mst_options` 35 · `mst_districts` 16 · `mst_areas` 3 · `mst_target_groups` 4 · `mst_programs` 4 + `mst_courses` 16 · `mst_instructors` 5 + ความเชี่ยวชาญ 10 + หลักสูตรที่สอน 10 · `mst_activity_formats` 5 · `mst_follow_up_round_templates` 4 |
| `database/seeders/RoleAndUserSeeder.php` | `usr_roles` 4 · `usr_role_menu_permissions` 84 (4 บทบาท × 21 เมนู) · `users` 5 · `usr_role_user` 6 |

รวม **211 แถว** · ทุกตารางใช้ `updateOrInsert` อ้าง `code` จึง **รันซ้ำได้โดยไม่เกิดข้อมูลซ้ำ** (ทดสอบแล้ว รันสองครั้งได้ 211 เท่าเดิม)

```bash
php artisan db:seed
```

**ผู้ใช้เดโมทุกคนใช้รหัสผ่าน `password`** — `RoleAndUserSeeder` มี guard โยน exception ถ้ารันบน production ห้ามถอด guard นี้ออก

`usr_role_menu_permissions` เขียนเป็น deny-list ในโค้ด แล้วแปลงเป็นแถว allow/deny ครบทุกคีย์ตอน seed — คีย์ทั้ง 21 ต้องตรงกับ `assets/js/menu-config.js` เพิ่มเมนูที่นั่นแล้วต้องมาเพิ่มใน `RoleAndUserSeeder::MENU_KEYS` ด้วย

**หมายเหตุ occupation** — seed เป็นชุดรวม 8 ค่า (7 ค่าของ `registrationOptions.occupations` + `รับจ้างทั่วไป` ที่มีเฉพาะใน `cohort.JOBS`) รอทีมธุรกิจชี้ขาดตามปม F.5 แก้ที่ `MasterDataSeeder::seedOptions()` ที่เดียวแล้วรันซ้ำ

### Model + ความสัมพันธ์ — ✅ เสร็จแล้ว

**38 Model** ใน `app/Models/` (flat namespace ตามมาตรฐาน Laravel) · ทุกตัวประกาศ `$table` เพราะชื่อตารางมี prefix

**กติกาที่บังคับไว้ในชั้น Model**

| เรื่อง | ทำอย่างไร |
|---|---|
| กัน N+1 | `Model::preventLazyLoading()` เปิดนอก production ใน `AppServiceProvider` — lazy load เมื่อไหร่ throw ทันทีตอนพัฒนา ไม่ใช่ไปเจอตอนขึ้นจริง |
| Eager load ที่หน้าจอต้องใช้ | scope `forList()` บน `Activity` และ `Registration` รวม relation ที่คอลัมน์ในตารางต้องใช้ไว้ที่เดียว |
| `registered` ที่ไม่มีคอลัมน์เก็บ | `scopeForList()` ใส่ `withCount('registrations')` · `seatsLeft()` อ่านจาก count ที่โหลดมาแล้ว |
| สถานะรอบติดตาม | `FollowUpRound::state()` คำนวณจาก `due_date` + config ทุกครั้ง ไม่มีคอลัมน์เก็บ |
| ช่วงอายุ | `Participant::ageBand()` คำนวณจาก `birth_year` + `config('farmconcept.age_bands')` |
| ความนิรนามของแบบประเมิน | `SatisfactionResponse` ไม่มี relation ใดชี้ไปยังคน · `SatisfactionReceipt` ไม่มี relation ไปยัง response — บังคับด้วยโครงสร้าง ไม่ใช่วินัย |
| polymorphic ของ `evl_answers` | `Relation::enforceMorphMap` แปลง `satisfaction` / `survey` เป็นคลาส — ฐานข้อมูลไม่เก็บชื่อคลาสเต็ม ย้าย namespace ได้โดยข้อมูลไม่พัง |

**ผลทดสอบความสัมพันธ์บนข้อมูลจริง**

```
USR-002 วีระ ศรีสมบัติ · บทบาท [ผู้ดูแลโครงการ, เจ้าหน้าที่โครงการ] · เข้าเมนู users-roles ได้
USR-001 สุนิสา แก้วมณี (staff)                                    · เข้าเมนู users-roles ไม่ได้
คุณปกรณ์ชัย ใจดี · สอน 2 หลักสูตร ข้ามโปรแกรม (ปลูกกินเอง + Food Literacy)
เกิด 1994 → อายุ 32 → ช่วง "30-44 ปี"   ·   เกิด 1960 → อายุ 66 → ช่วง "60 ปีขึ้นไป"
รวม 16 query · ไม่มี lazy load หลุด
```

### หน้านำร่อง — ✅ เสร็จแล้ว

**`/admin/activities/list`** ทำงานบนข้อมูลจริงจาก MySQL แล้ว

| ไฟล์ | บทบาท |
|---|---|
| `resources/views/layouts/admin.blade.php` | โครงหน้าจอกลาง — markup เดิมทุกบรรทัด รวมสคริปต์กันเมนูกระพริบ |
| `resources/views/admin/activities/list.blade.php` | เนื้อหาหน้า + สคริปต์เดิม เปลี่ยนเฉพาะแหล่งข้อมูล |
| `app/Http/Controllers/Admin/ActivityController.php` | Controller บาง — query ผ่าน scope ของ Model |
| `database/seeders/ActivitySeeder.php` | กิจกรรม 5 · รอบ 6 · ผู้ลงทะเบียน 122 · QR 14 |

**การย้ายไฟล์ static** — `assets/` และหน้า HTML เดิมทั้งหมดย้ายเข้า `public/` ด้วย `git mv`
path สัมพัทธ์ `../../assets/` ของ 55 หน้าเดิมยังชี้ถูกทั้งหมด เพราะย้ายทั้งคู่พร้อมกัน
`index.html` เปลี่ยนชื่อเป็น `home.html` — **ห้ามมี `public/index.html`** เพราะเว็บเซิร์ฟเวอร์ส่วนใหญ่เลือกก่อน `index.php` แล้ว routing ของ Laravel จะตายทั้งระบบ (แก้ลิงก์ที่ชี้มา 25 จุด)

**URL ต้องเป็น `/admin/activities/list` ไม่ใช่ `/admin/activities`** — โฟลเดอร์ `public/admin/activities/` ที่ยังเก็บหน้า static เดิมอยู่จะบัง URL นั้น เว็บเซิร์ฟเวอร์เห็นเป็นไดเรกทอรีแล้วไม่ส่งต่อให้ Laravel
เมื่อย้ายหน้าในโฟลเดอร์นั้นครบและลบทิ้งแล้ว ค่อยเปลี่ยนกลับ

**ผลทดสอบ**

```
/admin/activities/list  -> 200 · asset ทุกไฟล์ 200 · ไม่มี error ใน console
ตาราง 5 แถวจากฐานจริง · ชิปสถานะ 5 ชิป · ตัวกรองวิทยากร 4 · พื้นที่ 3 · ประเภท 2
N+1: 5 กิจกรรม + 5 relation + withCount = 6 query คงที่ไม่ว่าจะกี่แถว
```

### Authentication — ✅ เสร็จแล้ว

| ไฟล์ | บทบาท |
|---|---|
| `app/Http/Controllers/Auth/AuthController.php` | แสดงหน้าล็อกอิน · ยืนยันตัวตน · ออกจากระบบ |
| `app/Http/Requests/LoginRequest.php` | validate + `Auth::attempt` + จำกัดจำนวนครั้งที่กรอกผิด |
| `resources/views/auth/login.blade.php` | หน้าล็อกอิน — ดีไซน์เดิมทุกบรรทัด เปลี่ยนเฉพาะฟอร์มกับสคริปต์ |
| `app/Http/Controllers/LegacyPageController.php` | เสิร์ฟหน้า HTML เดิม 26 หน้าหลังผ่านการตรวจสิทธิ์ |

**ช่องโหว่ที่ปิดไป: หน้า static เดิมเข้าถึงได้โดยไม่ต้องล็อกอิน**
ตราบใดที่ไฟล์อยู่ใน `public/` เว็บเซิร์ฟเวอร์จะส่งให้ตรง ๆ โดยไม่ผ่าน Laravel เลย ใครก็เปิดหน้าหลังบ้านได้
จึงย้าย `public/admin/` → `resources/legacy/admin/` แล้วเสิร์ฟผ่าน route ที่มี middleware `auth`
**URL เหมือนเดิมทุกตัวอักษร** path สัมพัทธ์ `../../assets/` ในไฟล์จึงยังชี้ถูก

**ผู้ใช้จริงแทนที่ผู้ใช้จำลอง** — เซิร์ฟเวอร์ฉีด `window.TFC_MOCK.currentUser` ทับ **ทันทีหลัง** `mock-data.js`
ต้องตรงตำแหน่งนั้นเพราะ `sidebar-render.js` อ่านค่าไปวาดโปรไฟล์ตั้งแต่ก่อนจบ `<body>`
`User::toClientPayload()` ใช้ร่วมกันทั้งฝั่ง Blade และหน้า legacy จึงไม่มีสองสูตร

**ข้อกำหนดความปลอดภัยที่ทำไว้**

| เรื่อง | วิธีทำ |
|---|---|
| ไม่บอกว่าบัญชีไหนมีอยู่จริง | ชื่อผู้ใช้ผิด · รหัสผ่านผิด · บัญชีถูกระงับ → ข้อความเดียวกันหมด |
| บัญชีที่ถูกระงับล็อกอินไม่ได้ | ใส่ `status = ใช้งานอยู่` เป็นเงื่อนไขใน `Auth::attempt` |
| Session fixation | `session()->regenerate()` หลังล็อกอินสำเร็จ |
| เดารหัสผ่านรัว ๆ | `RateLimiter` 5 ครั้ง นับแยกตาม **ชื่อผู้ใช้ + IP** เพื่อไม่ให้ใครล็อกบัญชีคนอื่นได้ |
| CSRF ตอนออกจากระบบ | `POST /logout` พร้อม token — ไม่ใช่ลิงก์ธรรมดาที่เว็บอื่นฝัง `<img src="/logout">` เตะผู้ใช้ออกได้ |
| Path traversal | กรองด้วย regex แล้วยืนยันซ้ำด้วย `realpath` ว่าอยู่ใต้โฟลเดอร์ที่อนุญาต |
| หน้าหลังบ้านถูก cache | `Cache-Control: no-store, private` |

**ผลทดสอบ**

```
/admin/activities/list (ไม่ล็อกอิน)  -> 302 /login
/admin/dashboard.html  (ไม่ล็อกอิน)  -> 302 /login      ← หน้า static ก็ถูกกันแล้ว
รหัสผ่านผิด          -> "ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง"
บัญชีถูกระงับ         -> ข้อความเดียวกัน (ไม่บอกว่าบัญชีมีอยู่)
ล็อกอินสำเร็จ         -> 302 กลับไปหน้าที่ตั้งใจเปิด
ออกจากระบบ           -> 302 /login · เข้าหลังบ้านซ้ำถูกเด้งกลับ
/admin/../../.env    -> 404 (ทั้งแบบดิบและแบบ URL-encode)
```

ข้อกำหนดใน `AGENTS.md` บรรทัด 83-86 (ตรวจ session ก่อน render · ห้ามเห็นหน้า login แวบก่อน redirect · refresh แล้วไม่ flicker) **ได้มาฟรีจากการย้ายมาตรวจฝั่งเซิร์ฟเวอร์** เพราะเซิร์ฟเวอร์ตัดสินก่อนส่ง HTML ออกไป ไม่มีจังหวะให้หน้าจอกระพริบ

### สิทธิ์ระดับเมนู — ✅ เสร็จแล้ว

| ไฟล์ | บทบาท |
|---|---|
| `config/menu.php` | **แหล่งความจริงเดียวของโครงเมนู** — ย้ายมาจาก `assets/js/menu-config.js` (ลบไฟล์นั้นแล้ว) |
| `app/Services/MenuService.php` | กรองเมนูตามสิทธิ์ · แปลง URL → menu_key · คำนวณสิทธิ์แบบกว้าง |
| `app/Http/Middleware/EnsureMenuAccess.php` | ตรวจสิทธิ์ก่อน render |
| `resources/views/partials/client-context.blade.php` | ก้อนข้อมูลที่ฉีดให้ฝั่งหน้าจอ ใช้ร่วมกันทั้งหน้า Blade และหน้า static |

**ทำไมต้องย้ายเมนูมาไว้ที่ PHP** — เซิร์ฟเวอร์ต้องรู้โครงเมนูเพื่อสองอย่าง คือกรองเมนูที่ผู้ใช้ไม่มีสิทธิ์ออกก่อนส่ง และแปลง URL ที่ขอมาเป็น `menu_key` เพื่อตรวจสิทธิ์
ถ้าเก็บไว้สองที่จะหลุดจากกันแน่นอน — และ `menu-config.js` เขียนกำกับไว้เองว่าห้ามเขียนรายการเมนูซ้ำที่อื่น
ตอนนี้แก้ที่ `config/menu.php` ที่เดียว หน้าจอรับโครงที่กรองแล้วผ่าน `window.TFC_MENU` ที่เซิร์ฟเวอร์ฉีดให้

**เมนูที่ไม่มีสิทธิ์ไม่ถูกส่งมาเลย** ไม่ใช่ส่งมาแล้วซ่อนด้วย CSS — เปิดดู source ก็ไม่เห็นชื่อเมนูที่ตัวเองเข้าไม่ได้
หัวข้อกลุ่มที่ลูกถูกตัดหมดจะหายตามไปด้วย ไม่เหลือหัวข้อว่างที่กดแล้วไม่มีอะไร

**path ที่ไม่รู้จัก = ปฏิเสธ** — หน้าที่ไม่ตรงกับ `href`/`alsoMatch` ของเมนูใด และไม่อยู่ใน `extra_paths` จะได้ 404
เพิ่มหน้าใหม่แล้วลืมลงทะเบียนจะเจอทันทีตอนทดสอบ ดีกว่าเผลอเปิดหน้าที่ยังไม่มีใครกำหนดสิทธิ์ให้

**ผลทดสอบ**

| หน้า | ผู้ดูแลระบบสูงสุด | เจ้าหน้าที่โครงการ |
|---|---|---|
| `/admin/activities/list` | 200 | 200 |
| `/admin/activities/checkin.html` | 200 | 200 |
| `/admin/cohort/list.html` | 200 | 200 |
| `/admin/users/list.html` | 200 | **403** |
| `/admin/users/roles.html` | 200 | **403** |
| `/admin/areas/list.html` | 200 | **403** |
| `/admin/basic/programs.html` | 200 | **403** |
| `/admin/nope.html` | 404 | 404 |

```
เมนูที่ส่งให้เจ้าหน้าที่: แดชบอร์ด · กิจกรรม(4) · ประเมินสุขภาพ(3) · แบบประเมิน
                        (ไม่มี "พื้นฐาน" และ "ผู้ใช้งาน")
สิทธิ์แบบกว้าง: users=false · areas=false · master_data=false · activities=true · evaluations=true
หน้า static เดิมได้เมนูชุดเดียวกัน · ไม่มี menu-config.js เหลือในผลลัพธ์
```

### ที่ยังไม่ได้ทำ

1. **กรอง/เรียง/แบ่งหน้ายังทำที่ฝั่งหน้าจอ** — ต้องย้ายไป `WHERE`/`ORDER BY`/`LIMIT` ที่เซิร์ฟเวอร์ตามข้อ 4.5
2. **ปุ่มลบยังลบแค่ในหน่วยความจำ** — ต้องทำ `DELETE /admin/activities/{code}`
3. `create.html` / `detail.html` ยังอ่านข้อมูลจำลอง (แต่เข้าถึงได้เฉพาะคนที่มีสิทธิ์แล้ว)
4. **สิทธิ์คุมได้แค่ระดับ "เข้าหน้าได้/ไม่ได้"** ยังไม่มีระดับ "ดูได้แต่แก้ไม่ได้" — ปุ่มในตารางยังอาศัย `TFC.hasPermission()` ฝั่งเบราว์เซอร์ ซึ่งกันการกดพลาดได้ แต่กันคนตั้งใจไม่ได้ ต้องตรวจซ้ำที่เซิร์ฟเวอร์ตอนทำ endpoint เขียนข้อมูล

### งานถัดไป

1. ย้าย filter/sort/paginate ไปฝั่งเซิร์ฟเวอร์
2. ย้าย `detail` เป็น Blade แล้วลบไฟล์ static ของโมดูลกิจกรรม (`create` ย้ายแล้ว — ดูส่วนที่ 11)
3. `docs/coding-standards.md` (ข้อ 4.6 ของ prompt เดิม) — โครงจริงนิ่งพอจะเขียนได้แล้ว

---

## ส่วนที่ 10 — อีเวนท์กับกิจกรรม (ลำดับชั้นชั้นเดียว)

**โจทย์** อีเวนท์หนึ่งมีได้หลายกิจกรรม กรอกอีเวนท์ก็บันทึกปกติ กรอกกิจกรรมต้องระบุได้ว่าอยู่ในอีเวนท์ไหนหรือไม่อยู่เลย

### ทำไมไม่แยกตาราง `act_events`

อีเวนท์กับกิจกรรมใช้ฟิลด์เดียวกันเกือบทั้งหมด — ชื่อ · วันที่ · สถานที่ · รอบ · QR · แบบประเมิน
แยกตารางแปลว่าต้องทำหน้าจอ · สิทธิ์ · รายงาน ซ้ำอีกชุดเพื่อข้อมูลชุดเดิม
ต่างกันแค่ค่าในคอลัมน์ `type` ซึ่งมีอยู่แล้ว จึงเก็บตารางเดียวและชี้กลับตัวเองด้วย FK

```
act_activities.parent_event_id → act_activities.id   (nullable, ON DELETE RESTRICT)
```

`null` = กิจกรรมเดี่ยว ไม่ได้อยู่ในอีเวนท์ใด

### สามกฎที่ตัดสินไว้แล้ว

| กฎ | พฤติกรรม | บังคับที่ไหน |
|---|---|---|
| ลบอีเวนท์ที่ยังมีกิจกรรม | **ปฏิเสธ** ให้ย้ายหรือลบกิจกรรมออกก่อน | `ActivityPolicy::delete()` บอกเหตุผลเป็นภาษาคนพร้อมจำนวนลูก + FK `RESTRICT` กันซ้ำที่ชั้นฐานข้อมูล |
| ค่าของกิจกรรมในอีเวนท์ | **อิสระ** ไม่สืบทอดวันที่ สถานที่ หรือรอบจากอีเวนท์ | ไม่มีโค้ดคัดลอกค่า — เป็นค่าตั้งต้นของการไม่ทำอะไร |
| การแสดงในรายการ | **ปนกัน** แต่แถวที่อยู่ในอีเวนท์ขึ้นป้าย `อีเวนท์ · <ชื่อ>` เหนือชื่อ | `toListRow()` ส่ง `parentEventName` · `.grid-parent` ในหน้ารายการ |

ลำดับชั้นลึกได้ชั้นเดียว — อีเวนท์ซ้อนอีเวนท์ไม่ได้ และกิจกรรมอยู่ในตัวเองไม่ได้
ตรวจใน `ActivityRequest::checkParentEvent()` ทั้งสามกรณี (เป็นอีเวนท์แล้วเลือกแม่ · เลือกตัวเอง · เลือกสิ่งที่ไม่ใช่อีเวนท์)

### หน้าจอ

ช่อง "อยู่ในอีเวนท์" เป็น combobox ตัวที่ 8 ของฟอร์ม ซ่อนอัตโนมัติเมื่อประเภทเป็น "อีเว้นท์"
ตัวเลือกมาจาก `Activity::selectableEvents($excludeId)` ซึ่งตัดตัวเองออกให้แล้วตั้งแต่ฝั่งเซิร์ฟเวอร์
กดตัวเลือกเดิมซ้ำ = เอาออก จึงไม่ต้องมีปุ่มล้างค่าแยก

### ผลทดสอบ

| กรณี | ผล |
|---|---|
| ผูกกิจกรรมเข้าอีเวนท์แล้ว วันที่/รูปแบบ/จำนวนที่นั่งไม่ถูกทับ | ผ่าน |
| ลบอีเวนท์ที่มีกิจกรรม 1 รายการ (ผ่าน Policy) | ปฏิเสธ พร้อมข้อความบอกจำนวน |
| ลบตรงที่ตารางข้ามชั้น Policy | ปฏิเสธ SQLSTATE 23000 |
| อีเวนท์เลือกอีเวนท์เป็นแม่ | ปฏิเสธ "อีเวนท์ซ้อนในอีเวนท์อื่นไม่ได้" |
| กิจกรรมเลือกตัวเอง | ปฏิเสธ "กิจกรรมอยู่ในตัวเองไม่ได้" |
| เลือกกิจกรรมเป็นแม่ | ปฏิเสธ "รายการที่เลือกไม่ใช่อีเวนท์" |
| เลือกอีเวนท์ถูกต้อง / ไม่เลือกเลย | ผ่านทั้งคู่ |
| หน้ารายการส่ง `parentEventName` ถูกแถว | ผ่าน |

---

## ส่วนที่ 11 — สร้างกิจกรรมใหม่

`GET /admin/activities/create` · `POST /admin/activities`

ใช้ Blade **ไฟล์เดียวกับหน้าแก้ไข** (`admin/activities/form.blade.php`) เพราะฟิลด์เหมือนกันทุกช่อง
แยกไฟล์เมื่อไหร่ก็ต้องแก้สองที่ทุกครั้งที่เพิ่มฟิลด์ ต่างกันแค่ปลายทางที่ส่งข้อมูลไป
`ActivityController::formView()` ประกอบข้อมูลชุดเดียวให้ทั้งสองโหมด — เพิ่ม master data ที่นี่จุดเดียว

โหมดสร้างใช้ `new Activity([...])` ที่ยังไม่บันทึก แล้ว `setRelation()` เป็นคอลเลกชันว่างเอง
ไม่ปล่อยให้ Eloquent ยิง query ด้วยคีย์ `null` และไม่ชนกับ `preventLazyLoading()`

### รหัสกิจกรรม

`ACT-{ปี}-{ลำดับ 3 หลัก}` ออกให้ตอนบันทึกครั้งแรกใน `ActivityService::nextCode()`

- อยู่ใน transaction เดียวกับการสร้าง และใช้ `lockForUpdate` — สองคนกดบันทึกพร้อมกันจะไม่ได้รหัสซ้ำ
  ถ้าไม่ล็อก unique index จะปฏิเสธคนหลังทิ้งโดยที่ผู้ใช้ไม่เข้าใจว่าทำอะไรผิด
- นับจาก **รหัสสูงสุด** ไม่ใช่จำนวนแถว เพราะกิจกรรมที่ soft delete ไปแล้วยังกินเลขเดิมอยู่

### ข้อจำกัดที่ตั้งใจ

| | เหตุผล |
|---|---|
| รูปปกเลือกได้ตั้งแต่ต้น แต่อัปหลังบันทึกครั้งแรก | endpoint รูปปกผูกกับรหัสกิจกรรมซึ่งยังไม่มี จึงพักไฟล์ไว้ในหน่วยความจำแล้วส่งตามทันทีที่บันทึกสำเร็จ ถ้าบล็อกไม่ให้เลือกเลย เช็กลิสต์ "รูปภาพปก" จะไม่มีวันติ๊ก และปุ่มเผยแพร่จะกดไม่ได้ตลอดกาล |
| บันทึกสำเร็จแล้วเด้งไปหน้าแก้ไขทันที | ไม่งั้นกดบันทึกซ้ำจะสร้างกิจกรรมใหม่อีกใบ |
| `visibility` / `organizer` / `data_source` ไม่มีช่องกรอก | ฟอร์มแนบค่าจาก `TFC_ACTIVITY_CURRENT` กลับไปเสมอ โหมดสร้างจึงต้องมีค่าตั้งต้น (`สาธารณะ` / `ฉบับร่าง` / `กิจกรรม`) |

### ผลทดสอบ (ผ่าน HTTP จริง พร้อม session และ CSRF)

| กรณี | ผล |
|---|---|
| `GET /create` | 200 · หัวข้อ "เพิ่มกิจกรรม" · บันทึกด้วย POST · ปุ่มรูปปกปิดพร้อมคำอธิบาย |
| สร้างอีเวนท์ | 201 |
| สร้างกิจกรรมในอีเวนท์ พร้อมพื้นที่/วิทยากร/กลุ่มเป้าหมาย/รอบ | 201 · เขียนครบทุกตาราง · สร้าง QR ให้ 1 ใบ · บันทึก `created_by` |
| ชื่อว่าง | 422 "กรุณากรอกชื่อกิจกรรม" |
| อีเวนท์ซ้อนอีเวนท์ | 422 "อีเวนท์ซ้อนในอีเวนท์อื่นไม่ได้" |
| ลบอีเวนท์ที่มีกิจกรรม | 403 พร้อมบอกจำนวนลูก |
| รหัสไม่ซ้ำทั้งตาราง | ผ่าน |

### สองปมเรื่องอัปโหลดรูปปก (แก้แล้วทั้งคู่)

**1. `artisan serve` ไม่ส่ง `TMP`/`TEMP` ให้กระบวนการลูก**

`ServeCommand::$passthroughVariables` เป็นรายชื่อ environment ที่ยอมส่งต่อ และไม่มีสองตัวนี้
PHP ในกระบวนการลูกจึงถอยไปใช้ `C:\WINDOWS` เป็นที่เก็บไฟล์ชั่วคราว ซึ่งเขียนไม่ได้
ผลคือ `unable to create a temporary file` ทุกครั้งที่อัปโหลด ทั้งที่โค้ดและสิทธิ์ถูกต้องหมด

แก้ที่ `AppServiceProvider::boot()` โดยเติมสองตัวนี้เข้ารายชื่อ (เฉพาะนอก production)
ไม่แก้ `php.ini` เพราะเป็นเรื่องของ dev server ไม่ใช่ของเครื่อง
**ต้องรีสตาร์ต `artisan serve` หลังแก้** ค่านี้อ่านตอนสร้างกระบวนการ

| | ก่อนแก้ | หลังแก้ |
|---|---|---|
| ที่เก็บชั่วคราวของ dev server | `C:\WINDOWS` เขียนไม่ได้ | `%LOCALAPPDATA%\Temp` เขียนได้ |
| `tempnam()` | ล้มเหลว | สำเร็จ |

**2. `upload_max_filesize = 2M` เตี้ยกว่ากฎของแอป (5MB)**

PHP ตัดไฟล์ที่เกินทิ้งตั้งแต่ก่อนถึง Laravel — ฝั่งเซิร์ฟเวอร์จึงเห็นเป็น "ไม่ได้แนบไฟล์มา"
แล้วฟ้องผิดเรื่อง ผู้ใช้เห็นแค่รูปหายเฉย ๆ

`ActivityController::coverMaxBytes()` คำนวณค่าที่เล็กที่สุดระหว่าง `COVER_MAX_KB` (กฎแอป) ·
`upload_max_filesize` · `post_max_size` แล้วส่งไปให้หน้าจอตรวจก่อนส่ง
ผู้ใช้จึงได้ข้อความจริงว่า "เครื่องนี้รับได้ไม่เกิน 2.0 MB" แทนที่จะเงียบ

เพดานจริงจะขึ้นเป็น 5MB เองเมื่อไหร่ก็ตามที่ `php.ini` ถูกปรับ — ไม่ต้องแก้โค้ดซ้ำ

---

## ส่วนที่ 12 — เมนู "พื้นฐาน" (master data)

หกตารางในเมนูนี้ทำงานเหมือนกันหมด — แสดงรายการ · เพิ่ม · แก้ · ลบ ผ่านโมดัลใบเดียว
ต่างกันแค่ตาราง ฟิลด์ และเงื่อนไขว่าลบได้เมื่อไหร่ จึงรวมส่วนที่เหมือนกันไว้ที่
`MasterDataController` (abstract) — คลาสลูกประกาศเฉพาะสิ่งที่ต่างจริง 8 เมธอด

| ส่วนที่รวมไว้ในฐาน | ส่วนที่คลาสลูกประกาศ |
|---|---|
| สิทธิ์ · transaction · audit log · รูปแบบคำตอบ · การออกรหัส · content negotiation | model · view · label · คำนำหน้ารหัส · กฎตรวจ · ชื่อฟิลด์ไทย · การแปลงคอลัมน์ · การแปลงแถว |

### URL เดียวทำสองหน้าที่

`GET /admin/master/target-groups` เปิดจากเบราว์เซอร์ได้หน้าจอ · ยิงด้วย `Accept: application/json` ได้ข้อมูล
ทำแบบนี้เพื่อให้ลิงก์ในเมนูกับปลายทางที่หน้าจอเรียกเป็นที่เดียวกัน ไม่ต้องจำสอง URL ต่อหนึ่งตาราง
`{code}` เป็นรหัสข้อความ (TG-001) ไม่ใช่ id ตัวเลข — URL อ่านออกและไม่บอกลำดับข้อมูล

### `data-service.js` ต่อของจริงแล้ว

ไฟล์นี้ถูกออกแบบไว้ตั้งแต่ต้นให้เป็น "ทางผ่านเดียว" ของทุกหน้าจอ และคอมเมนต์เดิมเขียนไว้ว่า
ให้เปลี่ยนไส้ในเป็น fetch เมื่อมี backend จริง — ตอนนี้ทำแล้ว

entity ที่ประกาศ endpoint ไว้ที่ `window.TFC_API` (หน้า Blade เป็นคนใส่) จะยิง fetch ของจริง
entity ที่ยังไม่ได้ย้ายยังทำงานกับ `window.TFC_MOCK` ในหน่วยความจำเหมือนเดิม
**หน้าจอไม่ต้องรู้ว่าตัวเองอยู่โหมดไหน** จึงย้ายทีละหน้าได้โดยหน้าอื่นไม่พัง

`list()` เขียนผลกลับลง `window.TFC_MOCK[entity]` ด้วย เพราะหลายหน้าอ่านตัวแปรนั้นตรง ๆ
เพื่อวาด dropdown โดยไม่ผ่าน dataService

### ลบไม่ได้ = บอกเหตุผล ไม่ใช่ปล่อยให้ FK ปฏิเสธ

`blockedFromDelete()` นับจำนวนที่อ้างอิงอยู่แล้วคืนข้อความภาษาคน พร้อมทางออกที่ทำได้จริง
("ให้เปลี่ยนสถานะเป็นไม่ใช้งานแทน") ตอบ 403 ไม่ใช่ 500

### สถานะการย้าย

ย้ายครบทั้งหกตารางแล้ว

| ตาราง | รหัส | เงื่อนไขที่ลบไม่ได้ | ส่วนที่ต่างจากแม่พิมพ์ |
|---|---|---|---|
| กลุ่มเป้าหมาย | `TG-` | มีกิจกรรมหรือผู้เข้าร่วมอ้างอิง | — |
| หมวดหมู่กิจกรรม | `FMT-` | มีกิจกรรมใช้อยู่ | ไอคอนตรวจกับ `config('farmconcept.category_icons')` |
| โปรแกรม/หลักสูตร | `PROG-` | มีกิจกรรมใช้ หรือมีหลักสูตรที่ถูกใช้อยู่ | หลักสูตรซ้อนในโมดัลเดียว — `syncRelations()` |
| วิทยากร | `INS-` | อยู่ในกิจกรรม | รูปโปรไฟล์ (endpoint แยก) · ความเชี่ยวชาญ · หลักสูตรที่สอน |
| พื้นที่ดำเนินงาน | `AREA-` | มีกิจกรรมใช้อยู่ | ฟิลด์ 13 ช่อง · ตัวเลือกมาจาก config |
| ตั้งค่ารอบติดตาม | `FRT-` | รอบที่มีข้อมูลผู้เข้าร่วมแล้ว | ไม่ใช่ CRUD ทีละแถว — PUT ก้อนเดียว |

### สามจุดที่ต้องออกแบบเพิ่มนอกแม่พิมพ์

**1. หลักสูตรในโปรแกรม — จับคู่ด้วยชื่อ ไม่ใช่ลำดับ**

`ProgramController::syncRelations()` เทียบรายชื่อเดิมกับใหม่แล้วเก็บ id ของหลักสูตรที่ชื่อตรงกันไว้
ถ้าลบทิ้งแล้วสร้างใหม่ทุกครั้งที่กดบันทึก กิจกรรมและวิทยากรที่ผูกหลักสูตรไว้จะขาดทันที
หลักสูตรที่ถูกเอาออกจากรายการจะลบเฉพาะที่ยังไม่มีใครใช้ ที่ถูกใช้อยู่ปล่อยไว้ตามเดิม

**2. รูปวิทยากร — endpoint แยก แบบเดียวกับรูปปกกิจกรรม**

PHP อ่าน multipart จาก PUT ไม่ได้ตรง ๆ ตอนแก้ไขจึงอัปทันทีที่เลือกไฟล์
ตอนเพิ่มใหม่ยังไม่มีรหัส จึงพักไฟล์ไว้แล้วส่งตามทันทีที่บันทึกครั้งแรกสำเร็จ
เพดานขนาดคำนวณจาก `min(กฎแอป, upload_max_filesize, post_max_size)` เหมือนรูปปก

**3. ตั้งค่ารอบติดตาม — PUT ก้อนเดียวใน transaction เดียว**

หน้านี้แก้ทั้งตารางแล้วกดบันทึกครั้งเดียว ถ้าแยกเป็นคำขอต่อแถว การบันทึกที่สำเร็จครึ่งทาง
จะทำให้ลำดับรอบเพี้ยนโดยไม่มีใครรู้ · แถวที่ไม่มี `id` = แถวใหม่ · แถวที่หายไป = ถูกลบ
`followup-template-service.js` มีชั้นขนส่งข้อมูลแยกไว้ให้อยู่แล้ว เปลี่ยนแค่ไส้ใน
และแปลง id ชั่วคราว `FRT-NEW-n` ของหน้าจอเป็น `null` ก่อนส่ง

`today` กับ `usage` เคยเขียนตายตัวอยู่ในไฟล์ JS ตอนนี้มาจากเซิร์ฟเวอร์แล้ว

### รหัสที่ระบบออกให้ — นับจากตัวเลข ไม่ใช่จากข้อความ

ข้อมูลตั้งต้นบางตารางใช้เลขไม่เติมศูนย์ (`FRT-1`) ปนกับรหัสที่ระบบออกให้ (`FRT-005`)
`max()` ของข้อความจะบอกว่า `FRT-4` มากกว่า `FRT-005` แล้วออกรหัสซ้ำเดิมทุกครั้ง
`MasterDataController::runningCode()` จึงดึงรหัสทั้งหมดมาแล้วหาเลขท้ายสูงสุดแทน
พบจากการทดสอบจริง ไม่ใช่จากการอ่านโค้ด

หน้า static เดิมยังอยู่ที่ `admin/basic/*.html` และ `admin/areas/list.html` ยังเปิดได้
(ทำงานกับข้อมูลจำลองเหมือนเดิม) เมนูซ้ายชี้หน้าใหม่หมดแล้ว รอลบเมื่อยืนยันว่าไม่มีลิงก์ค้าง

### ผลทดสอบ

| กรณี | กลุ่มเป้าหมาย | หมวดหมู่กิจกรรม |
|---|---|---|
| เปิดหน้าจอ (HTML) | 200 | 200 |
| อ่านรายการ (JSON) | 200 · 4 แถว | 200 · 5 แถว |
| เพิ่มใหม่ | 201 · ได้รหัสต่อจากเดิม | 201 · ได้รหัสต่อจากเดิม |
| ชื่อซ้ำ | 422 | — |
| ค่าไม่ถูกต้อง | 422 · ฟ้องครบทุกช่อง | 422 · ไอคอนที่ไม่มีจริง |
| แก้ไข | 200 | 200 |
| ลบตัวที่ไม่มีใครใช้ | 200 | 200 |
| ลบตัวที่ถูกใช้อยู่ | 403 พร้อมบอกจำนวน | 403 พร้อมบอกจำนวน |
| รหัสไม่มีจริง | 404 | — |
| ผู้ใช้ที่ไม่มีสิทธิ์ | — | 403 |
| audit log | ครบทั้งเพิ่ม/แก้/ลบ | ครบทั้งเพิ่ม/แก้/ลบ |

### ผลทดสอบ — โปรแกรม · วิทยากร · พื้นที่ · รอบติดตาม

| กรณี | ผล |
|---|---|
| โปรแกรม: เพิ่มพร้อมหลักสูตร 2 รายการ | 201 |
| โปรแกรม: สลับลำดับ + เพิ่ม + เอาออก ในครั้งเดียว | 200 · หลักสูตรที่ยังใช้อยู่ไม่ถูกลบ |
| โปรแกรม: หลักสูตรชื่อซ้ำกันในโปรแกรมเดียว | 422 |
| โปรแกรม: ลบตัวที่มีกิจกรรมใช้อยู่ | 403 พร้อมบอกจำนวน |
| วิทยากร: เพิ่มพร้อมความเชี่ยวชาญ | 201 |
| วิทยากร: เลือกหลักสูตรที่ไม่มีในระบบ | 422 |
| วิทยากร: ลบตัวที่อยู่ในกิจกรรม | 403 พร้อมบอกจำนวน |
| พื้นที่: เพิ่มครบ 13 ช่อง | 201 |
| พื้นที่: วันสิ้นสุดมาก่อนวันเริ่ม | 422 |
| พื้นที่: กลุ่มพื้นที่ที่ไม่มีในระบบ | 422 |
| พื้นที่: ลบตัวที่มีกิจกรรมใช้อยู่ | 403 พร้อมบอกจำนวน |
| รอบติดตาม: เพิ่มรอบใหม่ได้รหัสต่อจากเดิม | 200 |
| รอบติดตาม: จำนวนวันซ้ำกัน | 422 |
| รอบติดตาม: ปิดใช้งานทุกรอบ | 422 |
| รอบติดตาม: ลบรอบที่มีข้อมูลผู้เข้าร่วมแล้ว | 403 พร้อมบอกชื่อรอบและจำนวน |
| รอบติดตาม: ปิดใช้งานแทนการลบ | 200 |
| ทั้งหกหน้า: เรนเดอร์ HTML | 200 ทุกหน้า · สคริปต์ inline 33 ก้อนผ่านไวยากรณ์ |

เงื่อนไข "ลบรอบที่มีคนใช้อยู่" ทดสอบโดยสร้างผู้เข้าร่วมชั่วคราวขึ้นมาหนึ่งคน
เพราะตาราง `ptp_*` ยังว่างทั้งหมด ถ้าไม่ทำแบบนี้เงื่อนไขจะไม่ถูกแตะเลย — ลบข้อมูลทดสอบออกแล้ว
