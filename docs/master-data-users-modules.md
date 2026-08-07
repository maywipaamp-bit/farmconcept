# Sidebar Restructure + พื้นฐาน/ผู้ใช้งาน Modules

Summary of the sidebar reorganization and the 8 rebuilt/new modules (A–H). Everything still runs on
mock data only — no real database/API connection this round — but the file structure is split so a
real backend can be wired in later at one place per entity (see "Data layer" below).

## 1. Sidebar (Part 1)

- **`assets/js/menu-config.js`** — the single source of truth for the whole 2-level sidebar. Every
  item is `{ key, label, icon, href, status, children }`. `status: 'placeholder'` points at
  `pages/system/placeholder.html?title=...` for menus explicitly out of scope this round.
- **`assets/js/navigation.js`** — gained `TFC.renderSidebarNav()`, which reads `TFC_MENU` and builds
  the accordion (`.nav-group` / `.nav-item-parent` / `.nav-submenu`, expand via the existing
  `[data-nav-submenu-toggle]` mechanism that was already in the codebase but unused). Auto-expands
  the group containing the current page; active-link matching now compares pathname **and query
  string**, since several placeholder links share a pathname.
- **`pages/system/placeholder.html`** — one generic "อยู่ระหว่างพัฒนา" page for every not-yet-built
  menu item, titled via its `?title=` query param.
- All **33 existing pages** were migrated from a hand-duplicated `<nav class="sidebar-nav">…</nav>`
  block to `<nav id="sidebar-nav" data-nav-base="…">` + a `menu-config.js` script include (done with a
  small Node script since the block was mechanically identical in shape per folder depth — see none
  needed if you just want the new pages, but relevant if you diff old vs new sidebar markup anywhere).
- New CSS: `.nav-group`, `.nav-item-row`, `.nav-item-parent-toggle`, `.nav-submenu-toggle`,
  `.nav-chevron` (layout.css).

### Notes on the sidebar structure

- **"กิจกรรม"** is a pure group header (no link of its own); "รายการกิจกรรม" is its first child, linking
  to `pages/activities/list.html`. ✅ confirmed
- **"กิจกรรม > ประเมินความพึงพอใจ"** and **"การเปลี่ยนแปลงสุขภาพ > แจ้งเตือนตอบแบบประเมิน"** have no
  existing page — both point at the placeholder per the "ห้ามพัฒนาเนื้อหา…กิจกรรม(เนื้อหา),
  การเปลี่ยนแปลงสุขภาพ" restriction.
- **"ผู้เข้าร่วมทั้งหมด"** (top-level) is a placeholder per the explicit ban list, even though a related
  page (`pages/registrations/list.html`, "ผู้ลงทะเบียน") already exists under กิจกรรม — kept them as two
  separate concepts rather than merging, since the ban list named this exact menu item.
- **"แบบประเมิน"** section only lists "จัดการแบบประเมิน" (→ `evaluations/list.html`, relabeled from
  "รายการแบบประเมิน"); the existing `evaluations/respondents.html` ("ผู้ตอบแบบประเมิน") is no longer in
  the sidebar at all — the file is untouched, just unlinked, in case that page is still needed
  elsewhere.

### No-flicker rendering (มาตรฐาน: ห้ามกระพริบ)

Three separate causes of the "หน้าจอกระพริบเด้งไปมา" on every menu click, all fixed:

1. **Page width jumping** — a short page has no vertical scrollbar and a long one does, so the whole
   layout shifted sideways between navigations. Fixed with `scrollbar-gutter: stable` on `html`
   (base.css), which always reserves the scrollbar track.
2. **Sidebar flashing in empty-then-filled** — the sidebar is built by JS, and its script sat at the end
   of `<body>`, so the browser painted an empty sidebar first. Fixed by extracting the renderer into
   **`assets/js/sidebar-render.js`** and loading it (with mock-data.js + menu-config.js) immediately
   after the sidebar's `</aside>` — the mount exists there but nothing has painted yet, so the sidebar
   is complete on the first frame. `navigation.js` stays at the end of `<body>` and only wires up the
   interactions (it also binds to topbar/content elements that don't exist that early).
3. **Sidebar width animating on load** — restoring the collapsed preference from localStorage triggered
   the `transition: width` on every page load. Fixed with an `.is-preload` class on `<html>` that kills
   all transitions/animations, removed by app.js after the first paint (double `requestAnimationFrame`).

## 2. Data layer (Part 4.6 — backend-ready structure)

- **`assets/js/mock-data.js`** — still the single mock data source (`window.TFC_MOCK.*`), extended
  with new fields for areas/targetGroups/programs/instructors, plus new `activityFormats` and
  `sampleFollowUpRounds` arrays, `roles[].menuPermissions`, and `users[].roles`/`username`.
- **`assets/js/data-service.js`** — new. `TFC.dataService('areas').list()/get()/create()/update()/remove()`
  all currently read/write `TFC_MOCK[entityKey]` and resolve a `Promise` after a short delay to behave
  like a real network call. Every module A–E, G, H's popup form goes through this instead of touching
  `TFC_MOCK` directly — swapping to real `fetch()` later means editing this one file, not any page.
- **`assets/js/dynamic-row.js`** — new. Shared add/remove row-list controller used by the course list
  (C), expertise list (D), and reused pattern for follow-up rounds (F, hand-rolled per-`<tr>` since F's
  rows are table rows, not the div-based repeater).

## 3. Modules A–H

| # | เมนู | ไฟล์ | Popup | หมายเหตุ |
|---|---|---|---|---|
| A | พื้นที่ดำเนินงาน | `pages/areas/list.html` | ✔ | จังหวัด→เขต/อำเภอ dependent dropdown (`TFC_MOCK.provinceDistricts`), สถานะเป็น Dropdown ตามที่ระบุ |
| B | กลุ่มเป้าหมาย | `pages/master-data/target-groups.html` | ✔ | ชื่อ + Toggle เท่านั้น |
| C | โปรแกรม/หลักสูตร | `pages/master-data/programs.html` | ✔ | หลักสูตร = Dynamic Row (`TFC.dynamicRowList`) |
| D | วิทยากร | `pages/master-data/instructors.html` | ✔ | Upload zone (มีอยู่แล้ว, reuse) + ความเชี่ยวชาญ Dynamic Row |
| E | รูปแบบกิจกรรม | `pages/master-data/activity-formats.html` (ใหม่) | ✔ | มีฟิลด์สี Badge เพิ่ม — 🔸 ดูด้านล่าง |
| F | รอบติดตามกลุ่มตัวอย่าง | `pages/master-data/sample-followup-rounds.html` (ใหม่) | ไม่มี (Inline Table) | เพิ่ม/ลบแถวในตารางโดยตรง + ปุ่ม "บันทึกทั้งหมด" เดียว |
| G | ผู้ใช้ | `pages/users/list.html` | ✔ | Username/Password + บทบาท Multi-select (checkbox dropdown + `.multiselect-tag` chips, เขียนใหม่เฉพาะหน้านี้) |
| H | บทบาท | `pages/users/roles.html` | ✔ | Permission Matrix สร้างจาก `TFC_MENU` ทั้งหมด (29 checkbox) พร้อมเลือกทั้งหมด/ยกเลิกทั้งหมดต่อหมวดหมู่ |

Every Index page follows the same pattern established in the กิจกรรม pilot module last round:
`TFC.renderPageHeader` / `TFC.renderFilterRow` (from `assets/js/index-layout.js`) + a plain
`<table class="data-table">`. Every Forms-Popup uses the existing `.modal-overlay` / `data-mock-submit`
pattern; `assets/js/form.js` was extended so a form **inside a modal now closes that modal** after a
successful mock-submit (previously it just showed the toast and left the modal open — true for every
existing modal in the app before this change, not just the new ones).

`pages/areas/create.html` and `pages/users/create.html` (the old full-page create forms) were **left in
place but unlinked** — G and A now use the popup instead, per the spec's "Forms-Popup" requirement, and
nothing else in the app still links to those two files.

### Notes on modules A–H

- **B (กลุ่มเป้าหมาย):** table column "จำนวนกลุ่มเป้าหมาย" = "จำนวนสมาชิก" (reuses the existing
  `memberCount` field) ✅ confirmed. Also added a สถานะ column to the table (not explicitly listed) since
  the popup has a status toggle — otherwise there'd be no way to see it without opening Edit.
- **E (รูปแบบกิจกรรม):** "สีป้ายกำกับ (Badge Color)" ✅ confirmed as a real field — 5 options tied to the
  existing badge color tokens (`.badge-primary` is new, alongside success/warning/danger/info/neutral
  which already existed). The Index table renders each row's badge in its chosen colour.
- **G (ผู้ใช้):** Username/Password = "ภาษาอังกฤษ **และ** ตัวเลข 6 หลัก" ✅ confirmed — enforced with the
  HTML5 pattern `(?=.*[A-Za-z])(?=(?:\D*\d){6}\D*$)[A-Za-z0-9]+`, i.e. letters plus exactly six digits.
  Password is never re-displayed or prefilled on Edit (write-only field); left blank = "don't change".
- **H (บทบาท):** ✅ confirmed — `menuPermissions` is now **wired into the real gating**, not additive.
  `assets/js/menu-config.js` exports **`window.TFC_PERMISSION_MAP`**, mapping each broad permission key
  to the menu keys that grant it, and `TFC.hasPermission()` (mock-data.js) resolves through it: a role
  holds a permission if ANY mapped menu item is ticked. Saving the Role popup also recomputes
  `roles[].permissions` from the matrix, so the Permission Matrix is the single place permissions are
  edited and the row-action gating (edit/delete buttons app-wide) follows it immediately. Verified all
  four seeded roles derive exactly their previous permission values — no behaviour regression.
  `project` (จัดการโครงการ) has no matching sidebar menu, so it is deliberately unmapped and still falls
  back to the stored `permissions.project`.
- Table columns across A/C/D/G were trimmed to match each module's spec'd column list exactly (e.g.
  dropped "จำนวนกิจกรรมที่สอน" from D, "อีเมล"/"พื้นที่รับผิดชอบ" from G) — the underlying mock fields
  are untouched, just not shown in these specific tables anymore.

## 4. Testing performed

Every module was exercised via the Browser pane + direct JS calls (search/filter, dependent dropdown,
dynamic row add/remove, upload zone, multi-select tags, permission-matrix select-all/clear-all/category
cascade, full create+edit+prefill+submit+modal-close flows) — not just eyeballed. Also spot-checked
~6 untouched pages (activities/detail, evaluations/list, reports/overview, checkin/index, the orphaned
users/create) after the sidebar bulk-migration to confirm no regressions, and mobile/desktop viewports
for the busiest new screens (areas popup, roles popup with 29-checkbox matrix).

Pixel screenshots aren't available in this sandboxed browser tool (same limitation noted in
`docs/index-layout-component.md`) — verification was via DOM state, computed styles, and the
accessibility tree instead.
