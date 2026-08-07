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
- Pagination controls remain static markup (unchanged from before).
- No screenshots are attached to this doc — the sandboxed browser pane in this environment can't composite
  frames for pixel screenshots; all pages were instead verified via the accessibility tree, page text, and
  live DOM/computed-style checks (grid columns, overflow, icon positioning) at 375px / 768px / 1280px.
