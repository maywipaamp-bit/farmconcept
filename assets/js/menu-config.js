/* TheFarmConcept — Central Sidebar Menu Config (single source of truth)
   Consumed by:
   - assets/js/sidebar-render.js -> builds the actual <nav class="sidebar-nav"> on every page
   - pages/users/roles.html      -> builds the Permission Matrix checkbox table in the Role Forms-Popup
   - assets/js/mock-data.js      -> TFC.hasPermission() maps a role's menuPermissions through TFC_PERMISSION_MAP
   Do NOT hardcode the menu list anywhere else — add/rename/remove a menu item here only.

   Item shape: { key, label, icon (svg path markup only, no <svg> wrapper), href (root-relative, optional),
                 status ('ready' | 'placeholder', default 'ready'), children (optional array of the same shape) }
   href is root-relative (e.g. "pages/areas/list.html") — sidebar-render.js prefixes it with the current
   page's `data-nav-base` (e.g. "../../") to build the actual link. Placeholder items point at
   pages/system/placeholder.html?title=... — every placeholder gets its own query string so the sidebar can
   still tell them apart for the active-link highlight. */
window.TFC_MENU = [
  {
    key: 'dashboard',
    label: 'แดชบอร์ด',
    icon: '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><path d="M9 22V12h6v10"/>',
    href: 'admin/dashboard.html'
  },
  /* เมนูที่ถูกลบออกจากระบบพร้อมไฟล์หน้าจอ (รอบเดียวกับที่ลบ pages/reports/):
       - ผู้เข้าร่วมทั้งหมด        -> pages/participants/
       - กิจกรรม > ผู้ลงทะเบียน / ตรวจสอบชำระเงิน / Check-in / ความพึงพอใจ
                                  -> pages/registrations/, pages/checkin/
       - ประเมินสุขภาพ            -> pages/evaluations/follow-up.html
       - จัดการแบบประเมิน          -> pages/evaluations/
     เมนู "กิจกรรม" เหลือลูกเดียวคือ "รายการกิจกรรม" ซึ่งยังใช้งานอยู่ */
  {
    key: 'activities',
    label: 'กิจกรรม',
    icon: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    children: [
      { key: 'activities-list', label: 'รายการกิจกรรม', icon: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>', href: 'admin/activities/list.html' },
      { key: 'activities-registrants', label: 'ผู้ลงทะเบียน', icon: '<path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M9 14l2 2 4-4"/>', href: 'admin/activities/registrants.html' },
      { key: 'activities-checkin', label: 'Check-in', icon: '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 12.5l2.5 2.5L16 9.5"/>', href: 'admin/activities/checkin.html' }
    ]
  },
  /* เมนู 'รายงาน' และหน้าจอ pages/reports/* ถูกลบออกจากระบบแล้ว
     สิทธิ์ `reports` ยังคงอยู่ใน roles[].permissions เพราะยังใช้คุมปุ่ม Export และปุ่ม
     "รายงานผล" ของโมดูลแบบฟอร์ม — ดูหมายเหตุที่ TFC_PERMISSION_MAP ด้านล่าง */
  {
    key: 'evaluations',
    label: 'จัดการแบบประเมิน',
    icon: '<path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M8 12h8M8 16h5"/>',
    children: [
      { key: 'evaluations-create', label: 'สร้างแบบประเมิน', icon: '<path d="M12 5v14M5 12h14"/>', href: 'admin/evaluations/create.html' }
    ]
  },
  {
    key: 'master-data',
    label: 'พื้นฐาน',
    icon: '<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>',
    children: [
      { key: 'master-data-areas', label: 'พื้นที่ดำเนินงาน', icon: '<path d="M21 10c0 6-9 12-9 12s-9-6-9-12a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>', href: 'admin/areas/list.html' },
      { key: 'master-data-target-groups', label: 'กลุ่มเป้าหมาย', icon: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>', href: 'admin/basic/target-groups.html' },
      { key: 'master-data-programs', label: 'โปรแกรม/หลักสูตร', icon: '<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>', href: 'admin/basic/programs.html' },
      { key: 'master-data-instructors', label: 'วิทยากร', icon: '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>', href: 'admin/basic/instructors.html' },
      { key: 'master-data-activity-formats', label: 'หมวดหมู่กิจกรรม', icon: '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>', href: 'admin/basic/activity-formats.html' }
    ]
  },
  {
    key: 'users',
    label: 'ผู้ใช้งาน',
    icon: '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>',
    children: [
      { key: 'users-list', label: 'ผู้ใช้', icon: '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>', href: 'admin/users/list.html' },
      { key: 'users-roles', label: 'บทบาท', icon: '<path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/>', href: 'admin/users/roles.html' }
    ]
  }
];

/* Maps each broad permission key (used by TFC.hasPermission() to gate row actions like edit/delete
   across the whole app) to the sidebar menu keys that grant it. A role holds the permission if ANY of
   its mapped menu items is ticked in the Role popup's Permission Matrix — so the matrix is now the
   single place permissions are edited, and roles[].permissions is derived from roles[].menuPermissions
   rather than maintained separately. */
window.TFC_PERMISSION_MAP = {
  /* `project` (จัดการโครงการ) has no corresponding sidebar menu, so it is deliberately NOT mapped —
     TFC.hasPermission falls back to the role's stored permissions.project for it.
     `reports` (ดูรายงาน) is in the same situation since the รายงาน menu was removed: it no longer
     gates any screen, only the Export buttons and the forms module's "รายงานผล" action, so it must
     stay unmapped and fall back to permissions.reports — mapping it to an empty array would make
     hasPermission('reports') always false and silently hide every Export button.
     `participants`, `payments` และ `evaluations` เข้าเงื่อนไขเดียวกันแล้ว หลังลบเมนู
     ผู้เข้าร่วมทั้งหมด / ตรวจสอบชำระเงิน / ประเมินสุขภาพ / จัดการแบบประเมิน ออกจากระบบ
     ทั้งสามยังถูกใช้คุมปุ่มในหน้าที่เหลืออยู่ จึงต้องปล่อยให้ fallback ไปที่ permissions เช่นกัน */
  users: ['users-list', 'users-roles'],
  areas: ['master-data-areas'],
  master_data: [
    'master-data-target-groups', 'master-data-programs', 'master-data-instructors',
    'master-data-activity-formats'
  ],
  activities: ['activities-list', 'activities-registrants', 'activities-checkin'],
  /* `evaluations` เคยเป็น fallback ไปที่ permissions.evaluations ตอนที่ไม่มีเมนู
     พอมีหน้าจอกลับมาแล้ว จึงผูกกับเมนูตามปกติเหมือนโมดูลอื่น */
  evaluations: ['evaluations-create']
};
