# Coding Standards — TheFarmConcept (Laravel)

มาตรฐานการเขียนโค้ดของโปรเจกต์นี้ ทั้งฝั่งเซิร์ฟเวอร์และฝั่งหน้าจอ
เขียนจากโครงสร้างจริงที่มีอยู่ ไม่ใช่คำแนะนำทั่วไป — ทุกข้อมีที่มาจากโค้ดที่รันอยู่จริงและอ้างอิงไฟล์ไว้ให้เปิดดูได้

## ความสัมพันธ์กับเอกสารอื่น

| เอกสาร | ขอบเขต |
|---|---|
| `CLAUDE.md` | หน้าตา UI — สี ฟอนต์ ระยะห่าง น้ำหนักตัวอักษร (มีอำนาจสูงสุดเรื่อง UI) |
| `AGENTS.md` | กฎเชิงกระบวนการ — reuse ก่อนสร้างใหม่ ห้ามแตะ DB โดยไม่ขอ ขั้นตอนก่อน/หลังเขียนโค้ด |
| **`docs/coding-standards.md`** (ไฟล์นี้) | **มาตรฐานการเขียนโค้ดทั้งหมด** — การแบ่งชั้น ฐานข้อมูล frontend ประสิทธิภาพ ความปลอดภัย กับดักที่เจอจริง |
| `docs/database-schema-proposal.md` | โครงตาราง 44 ตาราง เหตุผลของแต่ละการตัดสินใจ และผลทดสอบ |

> ไฟล์นี้รวม `docs/coding-standard.md` เดิม (เอกพจน์) เข้ามาแล้วทั้งหมด และลบไฟล์นั้นทิ้ง
> เพราะชื่อต่างกันแค่ตัว `s` เดียวจนสับสน และเนื้อหาซ้อนกันเกินครึ่ง

---

## 1. การแบ่งชั้นความรับผิดชอบ

```
Route  →  Controller  →  Service (เมื่อ logic ซับซ้อน)  →  Model  →  Database
                      ↘  Model scope (เมื่อเป็นแค่การ query)
```

### Controller — จัดการ Request/Response เท่านั้น

ตัวอย่าง: `app/Http/Controllers/Admin/ActivityController.php`

```php
public function index(): View
{
    $activities = Activity::forList()->orderByDesc('updated_at')->get();

    return view('admin.activities.list', [...]);
}
```

**ห้าม** เขียน query ยาว ๆ หรือกฎธุรกิจใน Controller
การ query ที่ใช้ซ้ำให้เป็น **scope บน Model** · กฎธุรกิจที่ซับซ้อนให้เป็น **Service**

### Service — เมื่อ logic ไม่ใช่แค่การ query

ตัวอย่าง: `app/Services/MenuService.php` — กรองเมนูตามสิทธิ์ แปลง URL เป็น menu_key คำนวณสิทธิ์แบบกว้าง
เป็น logic ที่ทั้ง Controller, Middleware และ View ใช้ร่วมกัน จึงต้องอยู่ที่เดียว

**เกณฑ์ตัดสิน:** ถ้าตอบได้ด้วย query อย่างเดียว → scope · ถ้ามีเงื่อนไข การแปลงข้อมูล หรือหลายขั้นตอน → Service

### FormRequest — validate ที่นี่ ไม่ใช่ใน Controller

ตัวอย่าง: `app/Http/Requests/LoginRequest.php` — รวม rules, ข้อความภาษาไทย, การยืนยันตัวตน และการจำกัดจำนวนครั้งไว้ที่เดียว

### Middleware — ตรวจสิทธิ์ ไม่ใช่ทำงานธุรกิจ

ตัวอย่าง: `app/Http/Middleware/EnsureMenuAccess.php`

### Blade — แสดงผลเท่านั้น

ห้ามมี query หรือกฎธุรกิจใน view · ข้อมูลที่ view ต้องใช้ให้ Controller หรือ View Composer เตรียมมาให้ครบ

---

## 2. การตั้งชื่อ

| สิ่งที่ตั้งชื่อ | รูปแบบ | ตัวอย่าง |
|---|---|---|
| ตาราง | `prefix_` + snake_case พหูพจน์ | `act_activities` `ptp_participants` |
| ตารางแกน Laravel | คงชื่อเดิม ไม่ใส่ prefix | `users` `sessions` `cache` |
| ตาราง pivot | ชื่อสองตารางเอกพจน์ เรียงตามตัวอักษร | `act_activity_area` |
| คอลัมน์ | snake_case | `publish_start_at` |
| Primary key | `id` (BIGINT auto) | |
| รหัสที่มนุษย์อ่าน | `code` VARCHAR + UNIQUE **ไม่ใช้เป็น PK** | `ACT-2026-014` |
| Foreign key | `<entity>_id` | `program_id` |
| วันเวลา | `_at` ลงท้าย | `checked_in_at` |
| วันที่ล้วน | `_date` ลงท้าย | `start_date` |
| Boolean | `is_` / `has_` / `requires_` นำหน้า | `is_published` `requires_checkin` |
| Model | เอกพจน์ PascalCase อยู่ใน `App\Models` แบน ๆ | `Activity` `FollowUpRound` |
| Controller | `<Entity>Controller` | `ActivityController` |
| Route name | `<group>.<entity>.<action>` | `admin.activities.index` |
| ตัวแปร/เมธอด | camelCase | `seatsLeft()` |
| ค่าคงที่ | UPPER_SNAKE_CASE | `MAX_ATTEMPTS` |
| CSS class | kebab-case | `.sidebar-profile-name` |
| ไฟล์ JS | kebab-case | `activity-module.js` |
| Blade view | kebab-case ตามโครงโฟลเดอร์ของ route | `admin/activities/list.blade.php` |

**ทุก Model ต้องประกาศ `protected $table`** เพราะชื่อตารางมี prefix ทำให้ Laravel เดาไม่ถูก

---

## 3. การเขียนโค้ดและคอมเมนต์

### รูปแบบ

- ปฏิบัติตาม **PSR-12**
- ประกาศ return type และ parameter type ทุกเมธอด
- ฟังก์ชันทำหน้าที่เดียว · แตกเป็นเมธอดย่อยเมื่อเกิน ~30 บรรทัด
- หลีกเลี่ยง nested condition ลึก ใช้ early return
- ห้าม magic number — ยกไปเป็นค่าคงที่หรือ config

### คอมเมนต์ — อธิบาย "ทำไม" ไม่ใช่ "ทำอะไร"

โค้ดบอกอยู่แล้วว่าทำอะไร คอมเมนต์ต้องบอกสิ่งที่อ่านจากโค้ดไม่ได้

**ต้องมีคอมเมนต์เมื่อ**

1. **กฎธุรกิจที่ไม่ชัดในตัวเอง**
   ```php
   /* name และ offset_days เป็น snapshot ห้าม join กลับไปที่ template
      ถ้าอ่านสด พอแอดมินแก้จำนวนวัน วันครบกำหนดของคนที่ตอบไปแล้วจะขยับทั้งกระดาน */
   ```
2. **การตัดสินใจด้านความปลอดภัย**
   ```php
   /* ข้อความผิดพลาดเหมือนกันทุกกรณี เพื่อไม่ให้ไล่เดาได้ว่าบัญชีไหนมีอยู่ในระบบ */
   ```
3. **สิ่งที่ดูเหมือนผิดแต่ตั้งใจ** — เช่น ตารางสองตารางที่จงใจไม่มี FK เชื่อมกัน
   ```php
   /* ห้ามมี relation เชื่อมไปยัง SatisfactionResponse
      ถ้ามีเมื่อไหร่ ความนิรนามของแบบประเมินหายทันที */
   ```
4. **การผูกกันข้ามไฟล์** — เช่น ลำดับการโหลดสคริปต์ที่ห้ามสลับ
5. **query ที่ซับซ้อนกว่าปกติ** และ **สูตรคำนวณ**

**ไม่ต้องคอมเมนต์** โค้ดที่อ่านแล้วเข้าใจทันที เช่น `$table->string('name', 160);`

### ภาษา

โค้ด ชื่อไฟล์ ชื่อตัวแปร = **อังกฤษทั้งหมด** · คอมเมนต์และข้อความที่ผู้ใช้เห็น = **ไทย**

---

## 4. ฐานข้อมูล

### กฎบังคับ

1. **ทุกการเปลี่ยน schema ต้องผ่าน Migration** ห้ามแก้ตารางตรงในฐานข้อมูล
2. **ต้องเสนอก่อนเปลี่ยน** ตาม `docs/database-standard.md` — เหตุผล ตารางที่กระทบ ผลต่อข้อมูลเดิม วิธี backup วิธี rollback
3. **ทุก Migration ต้องเขียน `down()` ให้ครบ** และทดสอบ `migrate:rollback` ได้จริงก่อน merge
4. FK เป็น `RESTRICT` เป็นค่าเริ่มต้น · ใช้ `CASCADE` เฉพาะตารางลูกที่ไม่มีความหมายเมื่อแม่หายไป
5. เงินใช้ `DECIMAL(10,2)` **ห้าม FLOAT** · charset `utf8mb4_unicode_ci`

### ค่าที่คำนวณได้ ห้ามเก็บเป็นคอลัมน์

`registered` · `activityCount` · `avgSatisfaction` · `memberCount` · `userCount` · สถานะรอบติดตาม · คะแนนเฉลี่ย
ทั้งหมดคำนวณสดเสมอ — เก็บซ้ำเมื่อไหร่ ตัวเลขในการ์ดกับในตารางจะขัดกันเองทันทีที่ข้อมูลเปลี่ยน

**ข้อยกเว้นที่ต้องเก็บ:** `ptp_follow_up_rounds.offset_days` และ `name` เป็น snapshot โดยตั้งใจ

### Index

ต้องมีที่: FK ทุกตัว · คอลัมน์สถานะที่ใช้กรอง · คอลัมน์วันที่ที่ใช้เรียง · คอลัมน์ที่ใช้ค้นหา (เช่น `phone`)
Index หลายคอลัมน์ให้เรียงจาก **เลือกได้แคบสุดไปกว้างสุด**

---

## 5. Checklist ประสิทธิภาพ

- [ ] **ไม่มี N+1** — `Model::preventLazyLoading()` เปิดอยู่นอก production ถ้ามี lazy load จะ throw ทันทีตอนพัฒนา
- [ ] **relation ที่หน้าจอใช้ รวมไว้ใน scope เดียว** — เช่น `Activity::forList()` ที่มี `with()` + `withCount()` ครบ
- [ ] **ใช้ `withCount()` แทนการนับทีละแถว**
- [ ] **เลือกเฉพาะคอลัมน์ที่ใช้** — `with(['program:id,name'])` ไม่ใช่ `with('program')`
- [ ] **Pagination ทุกรายการที่โตได้** — ห้ามดึงทั้งตารางมากรองที่ frontend
- [ ] **กรอง/เรียง/แบ่งหน้าทำที่ฐานข้อมูล** ด้วย `WHERE` / `ORDER BY` / `LIMIT`
- [ ] **Cache ผลลัพธ์ที่คำนวณหนักและไม่เปลี่ยนบ่อย** เช่น สรุปแดชบอร์ด
- [ ] **ไม่มีคำขอไฟล์เกินจำเป็น** — รวม asset ด้วย Vite ก่อนขึ้น production

**ฝั่งเบราว์เซอร์**

- [ ] ไม่สร้าง DOM เกินจำเป็น — วาดเฉพาะแถวของหน้าที่กำลังแสดง ไม่วาดทั้งชุดแล้วซ่อน
- [ ] ไม่ผูก event listener ซ้ำ — ใช้ event delegation ที่ container แทนการผูกทีละแถว
- [ ] ไม่เพิ่ม library ใหม่โดยไม่มีเหตุผลที่เขียนอธิบายได้
- [ ] รูปภาพผ่านการบีบอัด และกำหนด `width`/`height` หรือ `aspect-ratio` ไว้เสมอ เพื่อจองพื้นที่กันเลย์เอาต์กระโดด
- [ ] Lazy load รูปและข้อมูลที่ยังไม่ต้องใช้ทันที

**วิธีวัด**

```bash
php artisan tinker
>>> DB::enableQueryLog(); /* ...เรียกโค้ดที่จะวัด... */ count(DB::getQueryLog());
```

จำนวน query ต้อง **คงที่** ไม่ว่าจะมีกี่แถว — ถ้าเพิ่มตามจำนวนแถวคือมี N+1
(หน้ารายการกิจกรรมปัจจุบัน: 5 กิจกรรม + 5 relation + withCount = **6 query คงที่**)

---

## 6. Checklist ความปลอดภัย

- [ ] **Validate ทั้งสองฝั่ง** — ฝั่งเบราว์เซอร์เพื่อบอกผู้ใช้เร็ว ฝั่งเซิร์ฟเวอร์เพื่อความถูกต้องจริง · **การตรวจฝั่งเบราว์เซอร์ไม่นับเป็นการป้องกัน**
- [ ] **Validate ที่ FormRequest ทุก endpoint ที่รับข้อมูล** ไม่ใช่ตรวจใน Controller
- [ ] **Escape ทุกข้อความที่มาจากผู้ใช้ก่อนแสดงผล** — Blade ใช้ `{{ }}` (escape ให้เอง) ห้ามใช้ `{!! !!}` กับข้อมูลผู้ใช้ · ฝั่ง JS ใช้ `TFC.escapeHtml()` ทุกครั้งที่ประกอบ HTML ด้วยสตริง
- [ ] **การเปลี่ยนแปลงข้อมูลต้องเป็น POST/PUT/DELETE พร้อม CSRF** ห้ามใช้ GET
- [ ] **ตรวจสิทธิ์ที่เซิร์ฟเวอร์เสมอ** — การซ่อนปุ่มฝั่งเบราว์เซอร์กันการกดพลาดได้ แต่กันคนตั้งใจไม่ได้
- [ ] **ข้อความล็อกอินผิดต้องเหมือนกันทุกกรณี** ไม่บอกว่าบัญชีไหนมีอยู่จริง
- [ ] **`session()->regenerate()` หลังล็อกอินสำเร็จ** กัน session fixation
- [ ] **จำกัดจำนวนครั้งที่กรอกผิด** นับแยกตามชื่อผู้ใช้ + IP ไม่ให้ใครล็อกบัญชีคนอื่นได้
- [ ] **ไฟล์ที่มีข้อมูลส่วนบุคคลเก็บนอก `public/`** และเสิร์ฟผ่าน route ที่ตรวจสิทธิ์ (เช่น สลิปการชำระเงิน)
- [ ] **path ที่ผู้ใช้ส่งมาต้องตรวจสองชั้น** — กรองด้วย regex แล้วยืนยันซ้ำด้วย `realpath`
- [ ] **หน้าหลังบ้านตั้ง `Cache-Control: no-store, private`**
- [ ] **ห้าม hardcode ค่าเชื่อมต่อหรือ secret ในโค้ด** ใช้ `.env` เท่านั้น และ `.env` ต้องอยู่ใน `.gitignore`
- [ ] **ห้าม log ข้อมูลส่วนบุคคล** และห้ามส่งฟิลด์ที่หน้าจอไม่ได้ใช้ออกไป

### Mass assignment

Model ใช้ `protected $guarded = ['id']` โดยการ validate จริงอยู่ที่ FormRequest
**ถ้า endpoint ไหนรับข้อมูลจากผู้ใช้ภายนอกโดยตรง ให้ระบุ `$fillable` เฉพาะคอลัมน์ที่อนุญาต** อย่าพึ่ง `$guarded` อย่างเดียว

---

## 7. Frontend

หน้าจอส่วนใหญ่ยังเป็น JavaScript ธรรมดาที่ประกอบ HTML ด้วยสตริง (ไม่มี framework) กติกาด้านล่างใช้ทั้งกับสคริปต์เดิมและกับหน้า Blade ใหม่

### Reuse ก่อนสร้างใหม่เสมอ

ก่อนเขียน component ใหม่ ต้องตรวจก่อนว่าของเดิมรองรับได้ไหม — รายการ component ที่มีอยู่ดูที่ `AGENTS.md` และ `docs/component-library.md`
Utility กลางที่ต้องใช้แทนการเขียนเอง: `TFC.escapeHtml()` · `TFC.renderPagination()` · `TFC.searchPopover()` · `TFC.actionMenuTrigger()` · `TFC.showToast()` · `TFC.exportCsv()`

### แยก state ให้ชัด

เก็บ state ของหน้าไว้ในออบเจ็กต์เดียว แล้ววาดใหม่จาก state นั้น — ห้ามอ่านค่าปัจจุบันกลับจาก DOM มาใช้ตัดสินใจ

```js
var state = { status: '', search: '', sort: 'updatedAt', page: 1, pageSize: 10 };
```

### ทุกหน้าจอต้องมีครบ 4 สถานะ

`ว่าง (empty)` · `กำลังโหลด (loading)` · `ผิดพลาด (error)` · `สำเร็จ (success)`
สถานะว่างต้องบอกด้วยว่าให้ทำอะไรต่อ ไม่ใช่แค่ "ไม่พบข้อมูล"

### ป้องกันการกดซ้ำ

ปุ่มที่กดแล้วต้องรอ ให้เข้าสถานะ pending **ทันที** — disable + เปลี่ยนข้อความ ห้ามปล่อยให้กดซ้ำได้
ดูตัวอย่างที่ `resources/views/auth/login.blade.php`

### Debounce การค้นหา

ช่องค้นหาที่ยิงคำขอต้อง debounce ไม่ยิงทุกตัวอักษร

### ห้ามให้จอกระพริบ

รายละเอียดทั้งหมดอยู่ใน `CLAUDE.md` หัวข้อ "มาตรฐาน Motion และการโหลด" — สรุปข้อที่พลาดบ่อยที่สุด:

1. **ห้าม unmount เนื้อหาเดิมตอนโหลดข้อมูลใหม่** — ใส่ `.is-refreshing` แล้วค่อยสลับข้อมูล
2. **โหลดครั้งแรกใช้ skeleton ไม่ใช่ spinner**
3. **จองพื้นที่ล่วงหน้า** — ตารางใส่ `min-height` รูปกำหนดขนาด ตัวเลขที่เปลี่ยนค่าใส่ `min-width` + `tabular-nums`

### แผงลอยทุกชนิดใช้มุมมนชุดเดียว

Dropdown · combobox · เมนู · popover ค้นหา · ปฏิทิน — ทุกแผงที่ลอยเหนือเนื้อหาใช้ค่าชุดเดียวกัน
ผู้ใช้จะได้รู้สึกว่าเป็นของระบบเดียวกัน ไม่ใช่ของที่แต่ละหน้าทำกันเอง

```css
border-radius: var(--radius-card);              /* 14px — ตัวแผง */
border: 1px solid var(--color-border-soft);
box-shadow: 0 1px 2px rgba(16,24,40,.04), 0 16px 40px rgba(16,24,40,.10);
```

**รายการข้างในแผงใช้ `--radius-sm` (8px)** — เล็กกว่าตัวแผงเสมอ ไม่งั้นมุมของรายการจะล้นมุมของแผง

| ใช้กับ | ตัวอย่างคลาส |
|---|---|
| แผง | `.dropdown-menu` `.smart-select-panel` `.ac-combo-panel` `.thai-date-panel` `.dtp-sheet.is-anchored` `.search-popover-panel` `.el-menu` `.co-menu` `.fb-menu` |
| รายการในแผง | `.dropdown-item` `.ac-combo-item` `.el-menu-item` |

**ห้ามใช้ `--radius-modal` (16px) กับแผงลอย** — ค่านั้นสงวนไว้ให้ popup ที่มีฉากหลังหรี่จอเท่านั้น

### ช่องวันที่และเวลา — ใช้ของระบบเท่านั้น

**ห้ามใช้ `<input type="date">` `<input type="time">` `<input type="datetime-local">` ของเบราว์เซอร์**
หน้าตาต่างกันทุกเครื่อง คุมสไตล์ไม่ได้ และแสดงปี ค.ศ. กับรูปแบบสากลที่ไม่ตรงกับส่วนอื่นของระบบ

```html
<input type="text" class="input" data-picker="date" data-iso="2026-08-11" placeholder="เลือกวันที่">
<input type="text" class="input" data-picker="time" data-iso="09:00" placeholder="09:30">
```

ใส่ `data-picker` แล้วจบ ไม่ต้องเรียกอะไรเพิ่ม — `assets/js/datetime-picker.js` จับเองทั้งตอนโหลดหน้าและตอนที่ DOM ถูกวาดใหม่

**สัญญาที่ต้องรู้**

| เรื่อง | กติกา |
|---|---|
| ค่าจริง | อยู่ที่ **`data-iso`** เสมอ (`YYYY-MM-DD` / `HH:MM`) — **ไม่ใช่ `.value`** ซึ่งเป็นข้อความไทยไว้ให้คนอ่าน |
| อ่านค่า | `el.getAttribute('data-iso')` |
| ตั้งค่า | `el.setAttribute('data-iso', iso)` ก่อนที่ picker จะ decorate หรือหลังจากนั้นต้องวาดใหม่ |
| เปลี่ยนค่า | ยิง `input` และ `change` ให้ทั้งคู่ โค้ดที่ดักสองอีเวนต์นี้อยู่แล้วทำงานต่อได้ แค่เปลี่ยนไปอ่าน `data-iso` |
| เวลาที่ยังพิมพ์ไม่ครบ | `data-iso` เป็นค่าว่าง จนกว่าจะอ่านออกเป็นเวลาจริง |

**วันที่เลือกจากปฏิทิน · เวลาพิมพ์เอง** — เวลาที่ต้องการมักเจาะจงเป็นนาที (09:45, 13:20) วงล้อที่ขยับทีละ 5 นาทีกรอกค่าจริงไม่ได้

**ปฏิทินยึดกับช่องที่กด ไม่ใช่ modal กลางจอ** — แผงโผล่ใต้ช่อง มุมมนครบสี่ด้านเท่ากับแผงของ combobox ไม่มีฉากหลังหรี่จอ
ที่ว่างด้านล่างไม่พอจะพลิกไปอยู่เหนือช่องเอง และหนีบไม่ให้ล้นขอบจอ

หน้าที่มีช่องวันที่ต้องโหลด `assets/js/datetime-picker.js` **หลัง `app.js`**

### ลำดับสคริปต์ในหน้า Blade ห้ามสลับ

```
mock-data.js  →  partials/client-context  →  sidebar-render.js  →  (สคริปต์อื่น)  →  app.js  →  สคริปต์ของหน้า
```

`sidebar-render.js` อ่าน `TFC_MENU` และ `currentUser` ไปวาดตั้งแต่ก่อนจบ `<body>` ถ้าสลับลำดับ แถบเมนูจะว่างหรือขึ้นข้อมูลผิดคน

---

## 8. กับดักที่เจอจริงในโปรเจกต์นี้

บันทึกไว้เพราะเสียเวลาไปแล้วครั้งหนึ่ง อย่าให้เสียซ้ำ

| อาการ | สาเหตุ | วิธีเลี่ยง |
|---|---|---|
| `Identifier name ... is too long` | ชื่อ index ที่ Laravel ตั้งอัตโนมัติเกิน 64 ตัวอักษร | **ตั้งชื่อเองทุกครั้งที่ index มีตั้งแต่ 3 คอลัมน์ขึ้นไป** |
| rollback ล้ม `error in index ... after drop column` | `down()` drop คอลัมน์โดยไม่ถอด index ก่อน | ถอด unique/index ก่อน แล้วค่อย `dropColumn` |
| `Access denied` ทั้งที่รหัสถูก | รหัสผ่านใน `.env` มี `#` แล้วถูกตีความเป็นคอมเมนต์ ค่าถูกตัดกลางคัน **โดยไม่มี error บอก** | ครอบด้วยเครื่องหมายคำพูด `DB_PASSWORD="a#b"` |
| Migration ล้มแล้วรันซ้ำไม่ได้ | MySQL ไม่ rollback DDL ตารางที่สร้างไปแล้วค้างโดยไม่ถูกบันทึก | ตอนพัฒนาใช้ `migrate:fresh` · **ห้ามใช้กับฐานที่มีข้อมูลจริง** |
| Route ใช้ไม่ได้ ขึ้น 404 | มีโฟลเดอร์ชื่อเดียวกันใน `public/` บัง เว็บเซิร์ฟเวอร์เห็นเป็นไดเรกทอรีแล้วไม่ส่งต่อให้ Laravel | อย่าให้ชื่อ route ชนกับโฟลเดอร์ใน `public/` |
| routing ตายทั้งระบบ | มี `public/index.html` เว็บเซิร์ฟเวอร์เลือกก่อน `index.php` | **ห้ามมี `public/index.html`** |
| หน้าเว็บช้ามาก ทั้งที่เซิร์ฟเวอร์ตอบใน 40 ms | เปิดผ่าน `localhost` บน Windows ซึ่งลอง IPv6 `::1` ก่อน แต่ `artisan serve` ผูก IPv4 อย่างเดียว | ใช้ **`127.0.0.1`** หรือใช้ Herd |
| คำสั่ง artisan ล้ม | composer ชี้ไป PHP 8.1 ของ XAMPP ซึ่ง Laravel 13 ไม่รองรับ | เรียกผ่าน PHP ของ Herd เสมอ |
| ฟอนต์เพี้ยน | `--font-sans` ประกาศฟอนต์สำรองที่ไม่เคยถูกโหลดมา | ฟอนต์ทุกตัวในสแตกต้องอยู่ใน `@import`/`<link>` |

---

## 9. การย้ายหน้าจอจาก static เป็น Blade

โปรเจกต์อยู่ระหว่างย้ายทีละหน้า ระหว่างนี้ต้องรักษากติกาเหล่านี้

1. **ไฟล์ static ที่ยังไม่ย้าย อยู่ที่ `resources/legacy/`** ไม่ใช่ `public/` — เพื่อให้ผ่าน middleware ตรวจสิทธิ์ก่อนเสมอ
   ถ้าอยู่ใน `public/` เว็บเซิร์ฟเวอร์จะส่งไฟล์ให้ตรง ๆ โดยไม่ผ่าน Laravel เลย
2. **URL ต้องไม่เปลี่ยนตอนย้ายไฟล์** เพื่อให้ path สัมพัทธ์ `../../assets/` ในไฟล์ยังชี้ถูก
3. **โครงเมนูมีที่เดียวคือ `config/menu.php`** — เพิ่มเมนูต้องเพิ่มคีย์ใน `RoleAndUserSeeder::MENU_KEYS` ด้วย
4. **หน้าใหม่ต้องลงทะเบียนสิทธิ์** ใน `config/menu.php` (เป็นเมนู หรือใส่ใน `extra_paths`) ไม่งั้นจะได้ 404
5. **ลำดับสคริปต์ห้ามสลับ** — `mock-data.js` → `partials/client-context` → `sidebar-render.js`
   เพราะ `sidebar-render.js` อ่าน `TFC_MENU` และ `currentUser` ไปวาดตั้งแต่ก่อนจบ `<body>`
6. **สะพานข้อมูลชั่วคราว** — หน้าที่ยังใช้สคริปต์เดิม ให้ Controller แปลงข้อมูลเป็นรูปแบบที่สคริปต์คาดไว้
   (camelCase ตามที่ `activity-module.js` ใช้) แล้วเขียนคอมเมนต์กำกับว่าเป็นสะพานชั่วคราวที่จะถูกถอดออก

### Asset ระหว่างย้าย — สองระบบอยู่ด้วยกัน

| | หน้า Blade | หน้า static ที่ยังไม่ได้ย้าย |
|---|---|---|
| CSS | bundle เดียวจาก Vite | 8 ไฟล์แยก |
| JS ที่ใช้ร่วมกัน | bundle เดียวจาก Vite | 11 ไฟล์แยก |
| ไฟล์ต้นทาง | `public/assets/` (เหมือนกันทั้งคู่) | `public/assets/` |

ไฟล์ต้นทางมีชุดเดียว Vite แค่ import เข้ามารวม จึงไม่มีการแก้สองที่
**แต่ต้อง `npm run build` ทุกครั้งที่แก้** ไม่งั้นหน้า Blade กับหน้า static จะเห็นคนละเวอร์ชัน
เมื่อย้ายหน้าครบแล้วให้ย้ายไฟล์ต้นทางเข้า `resources/` แล้วลบ `public/assets/` ทิ้ง ปัญหานี้จะหมดไป

### ลำดับสคริปต์เมื่อใช้ Vite

`@vite` สร้าง `<script type="module">` ซึ่ง **ถูก defer เสมอ** — ทำงานหลัง HTML ถูก parse จบ
ลำดับจริงที่เกิดขึ้นบนหน้า Blade จึงเป็น

```
1. สคริปต์ธรรมดา (ทำงานตอน parse)  : mock-data.js → client-context → sidebar-render.js → สคริปต์เฉพาะหน้า
2. โมดูล (ทำงานหลัง parse ตามลำดับในเอกสาร) : bundle ของ Vite → สคริปต์ของหน้า
```

**สคริปต์ของหน้าต้องประกาศเป็น `<script type="module">`** ไม่งั้นจะทำงานตอน parse แล้วหาฟังก์ชันใน bundle ไม่เจอ
**`mock-data.js` กับ `sidebar-render.js` ห้ามย้ายเข้า bundle** เพราะต้องทำงานก่อนเบราว์เซอร์วาดเฟรมแรก ไม่งั้นแถบเมนูจะกระพริบ

---

## 10. Checklist ก่อนส่งงาน

**โค้ด**
- [ ] `php -l` ผ่านทุกไฟล์ที่แก้
- [ ] ไม่มี query หรือกฎธุรกิจใน Controller และ Blade
- [ ] คอมเมนต์อธิบาย "ทำไม" ในจุดที่อ่านจากโค้ดไม่ได้

**ฐานข้อมูล**
- [ ] `php artisan migrate` ผ่าน
- [ ] `php artisan migrate:rollback` ผ่าน **บน MySQL จริง ไม่ใช่ SQLite**
- [ ] ไม่มีค่าที่คำนวณได้ถูกเก็บเป็นคอลัมน์

**ประสิทธิภาพ**
- [ ] นับ query แล้วคงที่ไม่ว่าจะกี่แถว
- [ ] มี pagination ทุกรายการที่โตได้

**ความปลอดภัย**
- [ ] ทุก endpoint ที่รับข้อมูลมี FormRequest
- [ ] ทุกหน้าหลังบ้านผ่าน `auth` และ `menu` middleware
- [ ] ไม่มี secret หลุดเข้า git

**UI** — ตาม `CLAUDE.md`
- [ ] ไม่มี `font-weight: 700` · `#000` · สี hex ที่ไม่ได้มาจาก tokens
- [ ] ทุก interactive element มี hover และ focus-visible

---

## 11. คำสั่งที่ใช้บ่อย

> ต้องเรียกผ่าน PHP 8.4 ของ Herd — PHP 8.1 ของ XAMPP ใช้กับ Laravel 13 ไม่ได้

```bash
& "C:\Users\maywi\.config\herd\bin\php.bat" artisan migrate:status
```

| งาน | คำสั่ง |
|---|---|
| ตรวจสถานะ migration | `artisan migrate:status` |
| รัน migration | `artisan migrate` |
| ถอย migration ล่าสุด | `artisan migrate:rollback` |
| ล้างแล้วสร้างใหม่ (**เฉพาะตอนพัฒนา**) | `artisan migrate:fresh --seed` |
| ใส่ข้อมูลตั้งต้น | `artisan db:seed` |
| ดูเส้นทางทั้งหมด | `artisan route:list` |
| เปิดเซิร์ฟเวอร์ | `artisan serve` แล้วเข้าที่ **`http://127.0.0.1:8000`** |

**Asset (Vite)**

| งาน | คำสั่ง |
|---|---|
| ติดตั้ง dependency ครั้งแรก | `npm install` |
| Build สำหรับ production | `npm run build` |
| โหมดพัฒนา (hot reload) | `npm run dev` |

**ต้องรัน `npm run build` ทุกครั้งที่แก้ไฟล์ใน `public/assets/css/` หรือ `public/assets/js/`** ที่ถูก import เข้า bundle
ไม่งั้นหน้า Blade จะยังใช้ของเก่า (หน้า static ที่ยังไม่ได้ย้ายจะเห็นการแก้ทันทีเพราะโหลดไฟล์ตรง — **ทำให้สองฝั่งไม่ตรงกันได้ ระวังจุดนี้**)
