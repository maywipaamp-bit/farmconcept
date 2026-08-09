# Search Panel Popover — แผงค้นหาแบบ Popover

Component กลางสำหรับหน้ารายการ (List Page) ทุกหน้า — ย้ายแถบค้นหา/ตัวกรองที่เดิมแสดงตลอดเวลา
ไปซ่อนไว้ในไอคอนแว่นขยายทรงกลม แล้วเปิดเป็นกรอบลอยเมื่อคลิก เพื่อคืนพื้นที่หน้าจอให้ตาราง

- JS: `assets/js/search-popover.js`
- CSS: `assets/css/components.css` (หมวด 10) + `assets/css/responsive.css`
- หน้าแรกที่ใช้งานจริง: `pages/activities/list.html`

## การใช้งาน

```html
<div class="page-toolbar">
  <div class="page-toolbar-count">พบทั้งหมด <strong id="activity-result-count">0</strong> รายการ</div>
  <div class="ml-auto" id="activity-search-popover"></div>
</div>
```

```js
var panel = TFC.searchPopover('activity-search-popover', {
  note: 'แสดงผลไม่เกิน 250 รายการ',        // optional — ข้อความมุมขวาบนของแผง
  searchLabel: 'ค้นหา:',                    // optional (ค่าเริ่มต้นตามภาพตัวอย่าง)
  filterLabel: 'รายการค้นหา:',              // optional
  search: { placeholder: 'ค้นหาจากชื่อกิจกรรม, รหัสกิจกรรม, ...' },
  filters: [
    { id: 'participantType', label: 'ประเภท', placeholder: 'ประเภททั้งหมด',
      options: [{ value: 'sample', label: 'กลุ่มตัวอย่าง' }] }   // ไม่ใส่ value = ใช้ label เป็นค่า
  ],
  onSearch: function (values, done) {
    // values = { keyword: '', filters: { participantType: '' }, activeCount: 0 }
    applyFilterToTable(values);
    done();          // ปิด Loading ของปุ่ม + ปิดแผง (ถ้าไม่เรียก จะปิดเองใน 5 วินาที)
  },
  onClear: function () { showAllRows(); }
});
```

เมธอดที่คืนกลับ: `open()` / `close()` / `toggle()` / `getValues()` / `setValues({keyword, filters})` /
`reset()` / `setActiveCount(n)` / `element`

**หน้าใหม่ไม่ต้องแก้ `search-popover.js`** — กำหนดฟิลด์ค้นหาและตัวกรองผ่าน config ทั้งหมด

## พฤติกรรมที่มีในตัว

| เรื่อง | พฤติกรรม |
|---|---|
| เปิด/ปิด | คลิกไอคอนสลับเปิด-ปิด, คลิกนอกกรอบปิด, กด Esc ปิด (แล้วโฟกัสกลับที่ไอคอน) |
| ค้นหา | กดปุ่ม "ค้นหา" หรือกด Enter ในช่องค้นหา ได้ผลเหมือนกัน |
| ตำแหน่ง | ชิดขวาใต้ไอคอน; ถ้าพื้นที่ขวาไม่พอจะสลับไปชิดซ้าย (`is-align-left`), ถ้าล่างไม่พอและบนกว้างกว่าจะเปิดขึ้นบน (`is-above`) — คำนวณใหม่ทุกครั้งที่เปิดและตอน resize |
| ขนาด | แผงกว้าง 340px สเกลฟอนต์/ช่องกรอกย่อลง (ช่องกรอกสูง 32px, label 11px) เพื่อให้เนื้อหาพอดีจอโดยไม่ต้องเลื่อน |
| ไม่ล้นจอ | ถ้าความสูงแผงเกินพื้นที่ที่เหลือ จะตั้ง `max-height` ตามพื้นที่จริงแล้วเลื่อนดูข้างในแทน (ไม่มีทางล้นขอบบน/ล่างของจอ) |
| สถานะกำลังกรอง | ไอคอนเปลี่ยนสีเป็นเขียว + badge จำนวนเงื่อนไขที่ Active (คำค้น + ตัวกรองที่เลือก) |
| ล้างค่า | ปุ่ม "ล้างค่า" ล้างทั้งคำค้นและตัวกรอง แล้วเรียก `onClear` |
| Loading | ปุ่ม "ค้นหา" เข้าสถานะ `btn-loading` + กันกดซ้ำ จนกว่าหน้าจะเรียก `done()` |
| Empty State | เป็นหน้าที่ของหน้ารายการ (ตารางแสดง `.state-placeholder` เอง) — Component ส่งเงื่อนไขให้เท่านั้น |
| หลายแผงในหน้าเดียว | เปิดแผงใหม่จะปิดแผงอื่นอัตโนมัติ |

## หมายเหตุการออกแบบ

- **สีปุ่ม "ค้นหา"** — สเปกระบุสีน้ำเงิน แต่ระบบนี้ใช้ Design Token White+Green (`--color-primary #81C060`)
  และ AGENTS.md ห้าม hardcode สีนอก token จึงใช้ `.btn-primary` (เขียว) แทน หากยืนยันว่าต้องเป็นน้ำเงินจริง
  ให้เพิ่ม token สีใหม่ก่อน แล้วสร้าง variant ปุ่ม ไม่ควรใส่สีตรง ๆ ใน component
- ไม่ได้ติดตั้งไลบรารีสำหรับ positioning เพิ่ม — ใช้ `getBoundingClientRect()` + CSS class ธรรมดา
- Empty State / Loading ของตารางยังเป็นของหน้ารายการเหมือนเดิม เพื่อไม่ให้ Component ผูกกับโครงสร้างตาราง

## สถานะการนำไปใช้

| หน้า | สถานะ |
|---|---|
| `pages/activities/list.html` | ใช้งานแล้ว (ย้ายคำค้น + ตัวกรอง ประเภท/กลุ่มเป้าหมาย/แหล่งที่มาข้อมูล เข้า Popover, logic การค้นหาเดิมคงไว้ครบ) — หน้านี้ไม่ได้ส่ง `note` เพราะผู้ใช้ขอตัดข้อความส่วนเกินออก (ยังเรียกใช้ได้ถ้าหน้าอื่นต้องการ) |
| หน้ารายการอื่น | ยังไม่ติดตั้ง (อยู่นอกขอบเขตงานนี้) |
