# Standard Index Page Layout

Shared JS component functions for every CRUD List / Index screen: `assets/js/index-layout.js`.
Built from the pilot module (กิจกรรม) and rolled out to พื้นฐาน (โปรแกรม/วิทยากร/กลุ่มเป้าหมาย/พื้นที่),
ผู้ใช้งาน, ผู้เข้าร่วมทั้งหมด (ผู้ลงทะเบียน), และ Dashboard/รายงานผู้เข้าร่วม (stat cards only).

No framework — plain functions that take a mount element (or its `id`) and a config object ("Props"),
then set its `innerHTML`. Matches the existing pattern already used by `TFC.actionMenuTrigger` and the
per-page table-rendering code, so pages stay one self-contained HTML file with inline data logic.

Load order: after `mock-data.js`/`action-menu.js` (needs `TFC.escapeHtml`), before the page's own inline
`<script>`. See any refactored page (e.g. `pages/activities/list.html`) for the full pattern.

The Data Table itself is **not** part of this module — every page keeps its own `<table class="data-table">`
markup and render loop exactly as before.

## A. Page Header — `TFC.renderPageHeader(mountId, opts)`

```html
<div class="page-header" id="activity-page-header"></div>
```

```js
TFC.renderPageHeader('activity-page-header', {
  title: 'รายการกิจกรรม',
  description: 'จัดการกิจกรรมทั้งหมดของโครงการ TheFarmConcept', // optional
  actions: [ // optional, any number
    {
      label: 'สร้างกิจกรรมใหม่',
      href: 'create.html',        // renders <a>; omit for a <button type="button">
      icon: '<svg .../>',         // optional, printed before the label
      variant: 'primary',         // 'primary' (default) | 'outline' | 'secondary' | 'danger'
      attrs: { 'data-open-modal': 'create-modal' } // optional extra HTML attributes
    }
  ]
});
```

## B. Summary Stat Card Group — `TFC.renderStatCards(mountId, cards)`

```html
<div class="summary-cards" id="activity-stat-cards"></div>
```

```js
TFC.renderStatCards('activity-stat-cards', [
  {
    icon: '<svg .../>',
    tone: 'success',   // 'primary' (default) | 'info' | 'warning' | 'success' — soft/muted tones only
    label: 'เปิดรับสมัคร',
    value: 2,
    target: 5,         // optional -> renders "2/5" (current/target progress format)
    trend: { text: 'เพิ่มขึ้นจากเมื่อวาน', direction: 'up', icon: '<svg .../>' } // optional, dashboard-style caption
  }
]);
```

Any number of cards; the grid collapses 4 → 2 columns on tablet and mobile automatically
(`assets/css/responsive.css`). Omit the whole section on screens that don't need it.

## C. Filter Row — `TFC.renderFilterRow(mountId, opts)`

```html
<div class="page-toolbar" id="activity-filter-row"></div>
```

```js
TFC.renderFilterRow('activity-filter-row', {
  resultCountId: 'activity-result-count', // optional; omit to hide the count text entirely
  resultCount: activities.length,
  resultLabelPrefix: 'พบทั้งหมด', // optional, default 'พบทั้งหมด'
  resultLabelSuffix: 'รายการ',    // optional, default 'รายการ'
  filters: [ // optional, any number of <select> dropdowns
    {
      id: 'activity-filter-status',
      placeholder: 'สถานะทั้งหมด', // optional first blank option
      value: currentValue,          // optional, pre-selects a matching <option>
      options: [{ value: 'open', label: 'เปิดรับสมัคร' }] // value defaults to label if omitted
    }
  ],
  search: { id: 'activity-search', placeholder: 'ค้นหาชื่อกิจกรรมหรือรหัส...' }, // optional
  extraButton: { label: 'ตัวกรองเพิ่มเติม', icon: '<svg .../>', attrs: {} } // optional
});
```

Layout order left → right: result count, filter dropdowns, search box, extra button. The page owns all
filtering/searching logic — it reads the `id`s it passed in and re-renders the table + count itself
(see `pages/master-data/programs.html` for a working search+filter example).

## Search Input default icon — `TFC.searchInputHTML(opts)`

Used internally by `renderFilterRow`'s `search` option, and directly available for the topbar search or any
one-off search box:

```js
TFC.searchInputHTML({ id, name, placeholder, value, wrapperClass })
```

Every search box in the system now gets the magnifier icon by construction — there's no "icon-less" variant
to opt out of.

## D. Pagination — `TFC.renderPagination(mountId, opts)`

```html
<div class="table-wrapper" id="activity-pagination"></div>
```

```js
TFC.renderPagination('activity-pagination', {
  page: state.page,
  pageSize: state.pageSize,
  total: rows.length,
  pageSizeOptions: [10, 20, 50],                 // optional — ไม่ใส่ = ไม่แสดงตัวเลือก "รายการต่อหน้า"
  onChange: function (page) { state.page = page; render(); },
  onPageSizeChange: function (size) { state.pageSize = size; state.page = 1; render(); }
});
```

แสดง "แสดง 1-10 จาก N รายการ" + ปุ่มหน้า (‹ 1 2 3 ›) และตัวเลือกจำนวนต่อหน้าเมื่อส่ง `pageSizeOptions`
หน้าที่ยังใช้ markup `.pagination` แบบ static เดิมไม่ได้รับผลกระทบ

## หน้ารายการแบบกระชับ (Clean List)

คลาสเสริมสำหรับหน้ารายการที่ต้องการความหนาแน่นแบบระบบเอกสารทั่วไป — อยู่ใน `components.css` หมวด 11

| คลาส | ใช้ทำอะไร |
|---|---|
| `.summary-cards.is-compact` | การ์ดสรุปแบบเตี้ย (ไอคอน 32px, ค่าตัวเลขเล็กลง) — ยังเป็นการ์ดแยกใบ |
| `.summary-cards.is-strip` | รวมการ์ดเป็น **แถบเดียวมีเส้นคั่น** (ไอคอน 28px) เตี้ยที่สุด ใช้เมื่อพื้นที่บนจอมีค่า — จอเล็กจะพับเป็น 2 ช่องต่อแถวอัตโนมัติ |
| หัวหน้าจอแถวเดียว | แทรกจำนวนผลลัพธ์ + ปุ่มค้นหาเข้าไปใน `.page-header-actions` หลังเรียก `renderPageHeader()` แทนการมี `.page-toolbar` แยก (ดู `pages/activities/list.html`) — ประหยัดพื้นที่แนวตั้ง 1 แถว |
| `.status-dot` + `.is-success/.is-warning/...` | จุดสีบอกสถานะหน้าชื่อรายการ อ่านสถานะได้โดยไม่ต้องกวาดตาไปคอลัมน์ขวาสุด |
| `.cell-stack-title-row` | แถวชื่อ (จุดสถานะ + ลิงก์ชื่อ) ภายใน `.cell-stack` |
| `.cell-link` | ชื่อรายการที่กดเข้าหน้าแก้ไขได้ (สีเหมือนข้อความปกติ, hover เขียวเข้ม) |
| `.data-table td.nowrap` | คอลัมน์ที่ห้ามตัดบรรทัด (วันที่/ตัวเลข/สถานะ/ปุ่ม) — คอลัมน์อื่นตัดบรรทัดได้เพื่อให้ตารางพอดีจอ ไม่ต้องเลื่อนแนวนอน |

## โครงหน้ารายการมาตรฐาน (List Page Structure)

หน้าอ้างอิง: `pages/activities/list.html` — เรียงจากบนลงล่าง 4 ส่วน

1. **Header** — `renderPageHeader()`: ชื่อหน้า (ซ้าย) + ปุ่มหลัก (ขวา)
2. **Toolbar** (`.list-toolbar`) — ชิปสถานะตัวกรอง `.filter-chip` + `.toolbar-divider` + ปุ่มไอคอน `.icon-btn-sm` (ส่งออก ฯลฯ) ทางซ้าย, ปุ่มค้นหา (Search Panel Popover) ทางขวาด้วย `.ml-auto`
3. **Table** — `.table-wrapper > .table-scroll > table.data-table.is-header-filled` (หัวตารางพื้นเทา `--color-surface-muted` sticky ค้างไว้, ตัวตารางเลื่อนในกรอบสูงสุด 60vh)
   คอลัมน์มาตรฐาน: **# | ภาพ | ชื่อกิจกรรม (จุดสถานะ + ชื่อ แล้วเรียงข้อมูลย่อยลงมา: วันเวลา / พื้นที่ / วิทยากร / ค่าลงทะเบียน) | ประเภท | รูปแบบกิจกรรม | ผู้ลงทะเบียน | สถานะ | ปรับปรุงล่าสุด (ผู้บันทึก + วันเวลา) | จัดการ**
4. **Footer** — `renderPagination(..., { footer: true })` อยู่ในกรอบตารางเดียวกัน: ปุ่มหน้า « ‹ 1 2 › » + รายการต่อหน้า (ซ้าย), ข้อความสรุปจำนวน (ขวา)

ชิปตัวกรองผูกกับสถานะจริง — ไม่มีตัวกรอง = "แสดงทั้งหมด", มีตัวกรอง = "กรองอยู่: ..." และคลิกชิปเพื่อเปิดแผงค้นหาได้
หน้ารายการนี้ไม่มีการ์ดสรุป (stat cards) แล้ว เพื่อให้ตารางได้พื้นที่เต็ม — `TFC.renderStatCards()` ยังใช้ได้ตามเดิมในหน้าที่ต้องการ

## Screens refactored onto this standard

| Screen | Header | Stat Cards | Filter Row | Notes |
|---|---|---|---|---|
| `pages/activities/list.html` (pilot) | ✔ | ✔ (new) | ✔ | reference implementation |
| `pages/master-data/programs.html` | ✔ | – | ✔ (new: search + category filter, live) | previously had no search/filter at all |
| `pages/master-data/instructors.html` | ✔ | – | ✔ (new: search, live) | previously had no search/filter at all |
| `pages/master-data/target-groups.html` | ✔ | – | ✔ (new: search, live) | previously had no search/filter at all |
| `pages/users/list.html` | ✔ | – | ✔ | |
| `pages/areas/list.html` | ✔ | – | ✔ | |
| `pages/registrations/list.html` (ผู้เข้าร่วมทั้งหมด) | ✔ | ✔ (new) | ✔ | activity filter now lists all activities present in data, not a fixed 2 |
| `dashboard.html` | ✔ | ✔ (migrated) | – | values/trends unchanged |
| `pages/reports/participants.html` | ✔ | ✔ (migrated) | ✔ | order corrected to Header → Stats → Filter (was Filter → Stats) |

## Still to do / needs confirmation (รอยืนยัน)

- The pre-existing filter dropdowns on กิจกรรม, ผู้ใช้งาน, พื้นที่, ผู้ลงทะเบียน (status/role/activity/payment
  selects) were **not** wired to live-filter the table — they were already non-functional before this
  refactor, and wiring them up was out of scope for a layout-standardization pass. Only the **newly added**
  master-data search boxes were wired to real client-side filtering, since they replace a previously-missing
  feature rather than an existing but static one.
- Pagination: ตอนนี้มี `TFC.renderPagination()` แล้ว (หัวข้อ D) — `pages/activities/list.html` ใช้จริงแล้ว
  ส่วนหน้าอื่นยังเป็น markup static เหมือนเดิม รอทยอยย้าย
- No screenshots are attached to this doc — the sandboxed browser pane in this environment can't composite
  frames for pixel screenshots; all pages were instead verified via the accessibility tree, page text, and
  live DOM/computed-style checks (grid columns, overflow, icon positioning) at 375px / 768px / 1280px.
