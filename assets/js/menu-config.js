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
    href: 'dashboard.html'
  },
  {
    key: 'all-participants',
    label: 'ผู้เข้าร่วมทั้งหมด',
    icon: '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>',
    href: 'pages/participants/list.html'
  },
  {
    key: 'activities',
    label: 'กิจกรรม',
    icon: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
    children: [
      { key: 'activities-list', label: 'รายการกิจกรรม', icon: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>', href: 'pages/activities/list.html' },
      { key: 'activities-registrations', label: 'ผู้ลงทะเบียน', icon: '<path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M9 14l2 2 4-4"/>', href: 'pages/registrations/list.html' },
      { key: 'activities-payments', label: 'ตรวจสอบชำระเงิน', icon: '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>', href: 'pages/registrations/payments.html' },
      { key: 'activities-checkin', label: 'Check-in', icon: '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>', href: 'pages/checkin/index.html' },
      {
        key: 'activities-satisfaction',
        label: 'ประเมินความพึงพอใจ',
        icon: '<circle cx="12" cy="12" r="9"/><path d="M9 12l2 2 4-4"/>',
        href: 'pages/system/placeholder.html?title=' + encodeURIComponent('ประเมินความพึงพอใจ'),
        status: 'placeholder'
      }
    ]
  },
  {
    key: 'health-change',
    label: 'การเปลี่ยนแปลงสุขภาพ',
    icon: '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
    children: [
      { key: 'health-change-followup', label: 'ติดตามผลประเมิน', icon: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>', href: 'pages/evaluations/follow-up.html' },
      {
        key: 'health-change-reminders',
        label: 'แจ้งเตือนตอบแบบประเมิน',
        icon: '<path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/>',
        href: 'pages/system/placeholder.html?title=' + encodeURIComponent('แจ้งเตือนตอบแบบประเมิน'),
        status: 'placeholder'
      }
    ]
  },
  {
    key: 'evaluation-forms',
    label: 'แบบประเมิน',
    icon: '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/>',
    children: [
      { key: 'evaluation-forms-manage', label: 'จัดการแบบประเมิน', icon: '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/>', href: 'pages/evaluations/list.html' }
    ]
  },
  {
    key: 'reports',
    label: 'รายงาน',
    icon: '<path d="M3 3v18h18"/><path d="M7 13l4-4 3 3 5-6"/>',
    children: [
      { key: 'reports-activities', label: 'รายงานกิจกรรม', icon: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>', href: 'pages/reports/activities.html' },
      { key: 'reports-areas', label: 'รายงานพื้นที่ดำเนินงาน', icon: '<path d="M21 10c0 6-9 12-9 12s-9-6-9-12a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>', href: 'pages/reports/areas.html' },
      { key: 'reports-participants', label: 'รายงานผู้เข้าร่วม', icon: '<path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/>', href: 'pages/reports/participants.html' },
      { key: 'reports-target-groups', label: 'รายงานกลุ่มเป้าหมาย', icon: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>', href: 'pages/reports/target-groups.html' },
      { key: 'reports-samples', label: 'รายงานกลุ่มตัวอย่าง', icon: '<path d="M9 2v6L3 20a1 1 0 001 2h16a1 1 0 001-2l-6-12V2"/>', href: 'pages/reports/samples.html' },
      { key: 'reports-individual', label: 'รายงานการเปลี่ยนแปลงสุขภาวะรายบุคคล', icon: '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>', href: 'pages/reports/individual.html' }
    ]
  },
  {
    key: 'master-data',
    label: 'พื้นฐาน',
    icon: '<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>',
    children: [
      { key: 'master-data-areas', label: 'พื้นที่ดำเนินงาน', icon: '<path d="M21 10c0 6-9 12-9 12s-9-6-9-12a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>', href: 'pages/areas/list.html' },
      { key: 'master-data-target-groups', label: 'กลุ่มเป้าหมาย', icon: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>', href: 'pages/master-data/target-groups.html' },
      { key: 'master-data-programs', label: 'โปรแกรม/หลักสูตร', icon: '<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>', href: 'pages/master-data/programs.html' },
      { key: 'master-data-instructors', label: 'วิทยากร', icon: '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>', href: 'pages/master-data/instructors.html' },
      { key: 'master-data-activity-formats', label: 'รูปแบบกิจกรรม', icon: '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>', href: 'pages/master-data/activity-formats.html' },
      { key: 'master-data-sample-rounds', label: 'รอบติดตามกลุ่มตัวอย่าง', icon: '<path d="M9 2v6L3 20a1 1 0 001 2h16a1 1 0 001-2l-6-12V2"/>', href: 'pages/master-data/sample-followup-rounds.html' }
    ]
  },
  {
    key: 'users',
    label: 'ผู้ใช้งาน',
    icon: '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>',
    children: [
      { key: 'users-list', label: 'ผู้ใช้', icon: '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>', href: 'pages/users/list.html' },
      { key: 'users-roles', label: 'บทบาท', icon: '<path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/>', href: 'pages/users/roles.html' }
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
     TFC.hasPermission falls back to the role's stored permissions.project for it. */
  users: ['users-list', 'users-roles'],
  participants: ['all-participants'],
  areas: ['master-data-areas'],
  master_data: [
    'master-data-target-groups', 'master-data-programs', 'master-data-instructors',
    'master-data-activity-formats', 'master-data-sample-rounds'
  ],
  activities: ['activities-list', 'activities-registrations', 'activities-checkin'],
  payments: ['activities-payments'],
  evaluations: ['evaluation-forms-manage', 'activities-satisfaction', 'health-change-followup', 'health-change-reminders'],
  reports: [
    'reports-activities', 'reports-areas', 'reports-participants',
    'reports-target-groups', 'reports-samples', 'reports-individual'
  ]
};
