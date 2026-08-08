# Activity Module — ระบบจัดการกิจกรรม

โมดูล "จัดการกิจกรรม" (Admin/Staff) ครอบคลุม 3 หน้าจอตามเอกสารวิเคราะห์ระบบ
สร้างต่อยอดจาก Component/Token เดิมของระบบทั้งหมด ไม่มี framework เพิ่ม (ยกเว้น Chart.js สำหรับกราฟ)

## ไฟล์ที่เกี่ยวข้อง

| ไฟล์ | หน้าที่ |
|---|---|
| `pages/activities/list.html` | Index — รายการกิจกรรม (ค้นหา/กรอง/เรียง/แบ่งหน้า/เปลี่ยนสถานะ/ลบ) |
| `pages/activities/create.html` | ฟอร์มเต็มจอ โหมดสร้าง (mount `TFC.renderActivityForm`) |
| `pages/activities/edit.html` | ฟอร์มเต็มจอ โหมดแก้ไข (อ่าน `?id=` แล้ว mount ตัวเดียวกัน) |
| `pages/activities/detail.html` | หน้ารายละเอียด 3 แท็บ + แท็บย่อย + Dashboard กราฟ |
| `assets/js/activity-module.js` | Helper กลางของโมดูล (อ่านกิจกรรมจาก `?id=`, รอบกิจกรรม, Dropdown สถานะมีสี, export CSV) |
| `assets/js/activity-form.js` | ฟอร์ม 4 section + Mobile preview แบบ real-time + validation |
| `assets/js/charts.js` | Wrapper ของ Chart.js (donut / bar / stackedBar / radar) ใช้ค่ามาตรฐานเดียวกันทุกกราฟ |

## Data Model (mock)

ขยายบน `TFC_MOCK.activities` เดิมแบบ **เพิ่มฟิลด์เท่านั้น** ฟิลด์เก่าคงไว้ครบ เพื่อไม่ให้หน้าจออื่นที่อ่านอยู่พัง

```
Activity  : type, participantType, format, course, targetGroups[], areaList[], instructorList[],
            hasFee, fee, coverImage, dataSource,
            evaluationFormIds[], checkinStart, checkinEnd,
            isPublished, publishStart, publishEnd, visibility, isFeatured
Schedule  : TFC_MOCK.activitySessions[activityId] -> { date, time, capacity, registered }
            (TFC.activity.schedules() แตก time เป็น timeStart/timeEnd ให้)
Master    : activityTypes, activityParticipantTypes, activityDataSources, activityVisibilityLevels,
            activityStatuses, paymentStatuses, checkinStatuses, satisfactionLevels, evaluationTopics,
            registrationOptions { genders, ageRanges, occupations, sourceChannels, interests }
Generated : activityRegistrations[activityId][]  — ผู้ลงทะเบียนรายกิจกรรม (พร้อมข้อมูลประชากรสำหรับกราฟ)
            activityEvaluations[activityId][]    — ผลแบบประเมิน (average, level, feedback, topicScores)
```

ชุด `activityRegistrations` / `activityEvaluations` สร้างด้วย LCG seed คงที่ ข้อมูลจึงเหมือนเดิมทุกครั้งที่รีเฟรช
และ **แยกจาก `TFC_MOCK.registrations` เดิม** เพื่อไม่ให้จำนวนแถวในหน้า "ผู้ลงทะเบียนทั้งหมด" เปลี่ยนไป

## Component ที่เพิ่มเข้าระบบ (ใช้ซ้ำได้กับหน้าจออื่น)

- `TFC.renderPagination(mountId, { page, pageSize, total, onChange })` — ใน `index-layout.js` (ของเดิมทุกหน้าเป็น markup static, ฟังก์ชันนี้เป็น opt-in)
- Tabs รองรับแท็บซ้อนแท็บ: ใส่ `data-tab-panels="#container"` ที่แถบแท็บ จะสลับเฉพาะ panel ที่เป็นลูกโดยตรงของ container นั้น และยิง event `tfc:tabshown` ทุกครั้งที่เปลี่ยนแท็บ (ไม่ใส่ = พฤติกรรมเดิมทั้งหน้า)
- CSS: `.status-select`, `.cell-stack`, `.tag-list/.tag`, `.filter-count`, `.tabs-sub`, `.form-split` + `.phone-frame`, `.schedule-row`, `.chart-grid/.chart-card/.chart-legend`, `.checkbox-grid`
- `TFC.exportCsv(filename, headers, rows)` — ดาวน์โหลด CSV (UTF-8 BOM เปิดด้วย Excel ได้)

## Chart

ใช้ Chart.js 4 จาก CDN (โหลดเฉพาะ `detail.html`) ถ้าเปิดแบบออฟไลน์ `charts.js` จะ fallback เป็นกล่อง `.chart-placeholder` แทน ไม่ทำให้หน้าพัง
กราฟถูกวาดตอนแท็บรายงานถูกเปิดครั้งแรก เพราะ canvas ที่ยัง `display:none` วัดขนาดไม่ได้

- Donut: ยอดรวมกลางวง + legend แสดงจำนวนและ % + โทน pastel + ความสูงคงที่ 220px ทั้ง 3 การ์ด
- Bar: โทนเขียวอ่อนของระบบ มีตัวเลขบนปลายแท่ง (plugin ในตัว ไม่พึ่ง chartjs-plugin-datalabels) และ tooltip
- Stacked Bar: การกระจายระดับความพึงพอใจรายหัวข้อ
- Radar: คะแนนเฉลี่ยรายด้าน (สเกล 0–5)

## กติกาที่ตีความไว้ (ควรยืนยันกับทีมธุรกิจ)

1. **ค้นหาในหน้า Index** — ค้นได้ทั้งชื่อ/รหัสกิจกรรม และชื่อ/เบอร์/อีเมลผู้เข้าร่วม โดยผลลัพธ์คือ "กิจกรรมที่มีผู้เข้าร่วมตรงเงื่อนไข"
2. **ตัวกรอง "ประเภท"** — ใช้ฟิลด์ `participantType` (กลุ่มตัวอย่าง/ทั่วไป) แยกจากฟิลด์ `type` (กิจกรรม/อีเวนท์) ในฟอร์ม
3. **สถานะกิจกรรม** — ใช้ 7 ค่า (ฉบับร่าง/เปิดรับสมัคร/ปิดรับสมัคร/เต็มแล้ว/กำลังดำเนินการ/ดำเนินการเสร็จสิ้น/ยกเลิก) ยังไม่ได้ใส่ state machine
4. **ลบกิจกรรม** — ถ้ามีผู้ลงทะเบียนแล้วจะ block การลบ และแนะนำให้เปลี่ยนสถานะเป็น "ยกเลิก" แทน
5. **ช่วงเวลา Check-in/ทำแบบประเมิน** — ใช้ช่วงเดียวกันทุกชุดแบบประเมินที่เลือก
6. **การ์ดที่ 5 ของรายงานแบบประเมิน** — ใช้ "ระดับความพึงพอใจโดยรวม" ไปก่อน
7. **ปุ่ม "บันทึก" ที่หัวหน้ารายละเอียด** — เลือกทางไปหน้าฟอร์ม (สเปกอนุญาตทั้ง inline และไปหน้าฟอร์ม) หน้ารายละเอียดจึงมีเฉพาะปุ่ม "แก้ไข"
8. **บันทึกร่าง vs บันทึกและเผยแพร่** — ร่างตรวจแค่ชื่อกิจกรรม, เผยแพร่ตรวจครบทุกฟิลด์บังคับ + รอบกิจกรรมไม่ทับซ้อน + ช่วงเวลาเผยแพร่ถูกต้อง
