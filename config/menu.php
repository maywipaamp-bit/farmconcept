<?php

/*
|--------------------------------------------------------------------------
| โครงเมนูของระบบ — แหล่งความจริงเดียว
|--------------------------------------------------------------------------
| ย้ายมาจาก assets/js/menu-config.js เพื่อให้ฝั่งเซิร์ฟเวอร์กับฝั่งหน้าจอใช้ชุดเดียวกัน
| เซิร์ฟเวอร์ต้องรู้โครงนี้เพราะ:
|   1. กรองเมนูที่ผู้ใช้ไม่มีสิทธิ์ออกก่อนส่งไปหน้าจอ
|   2. แปลง URL ที่ขอมา -> menu_key เพื่อตรวจสิทธิ์ก่อน render
| หน้าจอรับโครงที่กรองแล้วผ่าน window.TFC_MENU ที่ layout ฉีดให้
|
| เพิ่ม/ลบ/เปลี่ยนชื่อเมนู ให้แก้ที่นี่ที่เดียว แล้วเพิ่มคีย์ใน RoleAndUserSeeder::MENU_KEYS ด้วย
|
| icon เก็บเฉพาะเนื้อในของ <svg> (เส้น path/rect/circle) ไม่รวมแท็กครอบ
| href เป็น root-relative ไม่มี / นำหน้า — sidebar-render.js เติม data-nav-base ให้เอง
*/

return [

    'items' => [
        [
            'key' => 'dashboard',
            'label' => 'แดชบอร์ด',
            'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><path d="M9 22V12h6v10"/>',
            /* ย้ายเป็นหน้า Blade แล้ว (ไม่มี .html) — alsoMatch ไว้ให้ลิงก์เดิมยังไฮไลต์ถูก
               ระหว่างที่หน้า static ยังชี้ ../dashboard.html อยู่ */
            'href' => 'admin/dashboard',
            'alsoMatch' => ['admin/dashboard.html'],
        ],
        [
            'key' => 'activities',
            'label' => 'กิจกรรม',
            'icon' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            'children' => [
                [
                    'key' => 'activities-list',
                    'label' => 'รายการกิจกรรม',
                    'icon' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
                    /* ย้ายเป็นหน้า Blade แล้ว (ไม่มี .html) — alsoMatch ไว้ให้ไฟล์ static เดิมยังไฮไลต์ถูก */
                    'href' => 'admin/activities/list',
                    'alsoMatch' => ['admin/activities/list.html'],
                    'alsoMatchPatterns' => [
                        '^/admin/activities/create$',
                        '^/admin/activities/[^/]+/edit$',
                    ],
                ],
                [
                    'key' => 'activities-registrants',
                    'label' => 'ผู้ลงทะเบียน',
                    'icon' => '<path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M9 14l2 2 4-4"/>',
                    'href' => 'admin/activities/registrants.html',
                ],
                [
                    'key' => 'activities-checkin',
                    'label' => 'Check-in',
                    'icon' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 12.5l2.5 2.5L16 9.5"/>',
                    'href' => 'admin/activities/checkin.html',
                ],
                [
                    'key' => 'activities-responses',
                    'label' => 'ประเมินกิจกรรม',
                    'icon' => '<path d="M7 3.5h7l4 4v13H7zM14 3.5v4h4M10 12h5M10 15.5h5"/>',
                    'href' => 'admin/activities/responses.html',
                ],
            ],
        ],
        [
            'key' => 'health-assessment',
            'label' => 'ประเมินสุขภาพ',
            'icon' => '<path d="M3.5 12.5h4L10 7l3.5 10L16 12.5h4.5"/>',
            'children' => [
                [
                    'key' => 'cohort',
                    'label' => 'กลุ่มตัวอย่าง',
                    'icon' => '<path d="M9 11a3.2 3.2 0 1 0 0-6.4A3.2 3.2 0 0 0 9 11ZM3.5 20c0-3 2.5-5.2 5.5-5.2s5.5 2.2 5.5 5.2M16 5.2a3 3 0 0 1 0 5.9M17.5 14.9c2 .6 3.3 2.4 3.3 4.6"/>',
                    'href' => 'admin/cohort',
                    'alsoMatch' => ['admin/cohort/list.html', 'admin/cohort/list', 'admin/cohort/detail.html', 'admin/cohort'],
                ],
                [
                    'key' => 'evaluations-rounds',
                    'label' => 'รอบติดตาม',
                    'icon' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
                    'href' => 'admin/evaluations/rounds.html',
                ],
                [
                    'key' => 'evaluations-responses',
                    'label' => 'ตอบแบบประเมิน',
                    'icon' => '<path d="M7 3.5h7l4 4v13H7zM14 3.5v4h4M10 12h5M10 15.5h5"/>',
                    'href' => 'admin/evaluations/responses.html',
                ],
            ],
        ],
        [
            'key' => 'evaluations',
            'label' => 'แบบประเมิน',
            'icon' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>',
            'href' => 'admin/evaluations',
            'alsoMatch' => [
                'admin/evaluations/create',
                'admin/evaluations/list.html',
                'admin/evaluations/create.html',
            ],
            'alsoMatchPatterns' => ['^/admin/evaluations/[A-Za-z0-9-]+/edit$'],
        ],
        [
            'key' => 'master-data',
            'label' => 'พื้นฐาน',
            'icon' => '<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>',
            'children' => [
                [
                    'key' => 'master-data-areas',
                    'label' => 'พื้นที่ดำเนินงาน',
                    'icon' => '<path d="M21 10c0 6-9 12-9 12s-9-6-9-12a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>',
                    'href' => 'admin/master/areas',
                ],
                [
                    'key' => 'master-data-target-groups',
                    'label' => 'กลุ่มเป้าหมาย',
                    'icon' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
                    'href' => 'admin/master/target-groups',
                ],
                [
                    'key' => 'master-data-programs',
                    'label' => 'โปรแกรมการเรียนรู้',
                    'icon' => '<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>',
                    'href' => 'admin/master/programs',
                ],
                [
                    'key' => 'master-data-instructors',
                    'label' => 'วิทยากร',
                    'icon' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
                    'href' => 'admin/master/instructors',
                ],
                [
                    'key' => 'master-data-activity-formats',
                    'label' => 'หมวดหมู่กิจกรรม',
                    'icon' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
                    'href' => 'admin/master/activity-formats',
                ],
                [
                    /* คนละหน้ากับ "รอบติดตาม" ในกลุ่มประเมินสุขภาพ — ตัวนั้นตั้งช่วงวันที่จริงร่วมกันทั้งระบบ
                       ตัวนี้เป็น master data ของระยะห่างเป็นวัน ที่ระบบกลุ่มตัวอย่างใช้คำนวณวันครบกำหนดรายคน */
                    'key' => 'master-data-follow-up-rounds',
                    'label' => 'รอบประเมิน',
                    'icon' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
                    'href' => 'admin/master/follow-up-rounds',
                ],
                [
                    'key' => 'master-data-payment-accounts',
                    'label' => 'ข้อมูลการรับชำระ',
                    'icon' => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h3"/>',
                    'href' => 'admin/master/payment-accounts',
                ],
                [
                    'key' => 'master-data-registration-options',
                    'label' => 'ตัวเลือกการลงทะเบียน',
                    'icon' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>',
                    'href' => 'admin/master/registration-options',
                ],
                [
                    'key' => 'master-data-consents',
                    'label' => 'เอกสารและความยินยอม',
                    'icon' => '<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h6"/>',
                    'href' => 'admin/master/consent-documents',
                ],
                [
                    'key' => 'master-data-system-settings',
                    'label' => 'ตั้งค่าระบบ',
                    'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 00-1.9-.3 1.7 1.7 0 00-1 1.6v.2h-4V21a1.7 1.7 0 00-1-1.6 1.7 1.7 0 00-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 00.3-1.9A1.7 1.7 0 003 14H2.8v-4H3a1.7 1.7 0 001.6-1 1.7 1.7 0 00-.3-1.9L4.2 7 7 4.2l.1.1A1.7 1.7 0 009 4.6 1.7 1.7 0 0010 3V2.8h4V3a1.7 1.7 0 001 1.6 1.7 1.7 0 001.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 00-.3 1.9 1.7 1.7 0 001.6 1h.2v4H21a1.7 1.7 0 00-1.6 1z"/>',
                    'href' => 'admin/master/system-settings',
                ],
            ],
        ],
        [
            'key' => 'users',
            'label' => 'ผู้ใช้งาน',
            'icon' => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>',
            'children' => [
                [
                    'key' => 'users-list',
                    'label' => 'ผู้ใช้',
                    'icon' => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>',
                    'href' => 'admin/users',
                    'alsoMatch' => ['admin/users/list.html', 'admin/users/list', 'admin/users'],
                ],
                [
                    'key' => 'users-roles',
                    'label' => 'บทบาท',
                    'icon' => '<path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/>',
                    'href' => 'admin/users/roles',
                    'alsoMatch' => ['admin/users/roles.html', 'admin/users/roles'],
                ],
            ],
        ],
    ],

    /*
    | หน้าที่เปิดจากปุ่มในหน้าอื่น ไม่มีเมนูของตัวเอง — ต้องระบุว่าใช้สิทธิ์ของเมนูไหน
    | ถ้าไม่อยู่ในรายการนี้และไม่ตรงกับ href/alsoMatch ของเมนูใด จะถูกปฏิเสธด้วย 403
    | กติกาคือ "ไม่รู้จัก = ไม่ให้เข้า" ปลอดภัยกว่าปล่อยผ่าน
    */
    'extra_paths' => [
        'admin/activities/create.html' => 'activities-list',
        'admin/activities/detail.html' => 'activities-list',
        'admin/activities/publish.html' => 'activities-list',
        'admin/evaluations/round-create.html' => 'evaluations-rounds',
        'admin/evaluations/round-detail.html' => 'evaluations-rounds',
        'admin/users/create.html' => 'users-list',
        'admin/profile.html' => null,   /* null = ทุกคนที่ล็อกอินแล้วเข้าได้ */
    ],

    /*
    | สิทธิ์แบบกว้างที่ TFC.hasPermission() ใช้คุมปุ่มในตาราง -> ผูกกับเมนูไหนบ้าง
    | บทบาทมีสิทธิ์นั้นถ้าติ๊กเมนูใดเมนูหนึ่งที่แมปไว้
    |
    | project / reports / payments ไม่ได้แมปไว้โดยตั้งใจ เพราะไม่มีเมนูของตัวเองแล้ว
    | ถ้าแมปเป็น array ว่าง hasPermission จะคืน false เสมอ แล้วปุ่ม Export จะหายไปทั้งระบบ
    */
    'permission_map' => [
        'users' => ['users-list', 'users-roles'],
        'areas' => ['master-data-areas'],
        'master_data' => [
            'master-data-target-groups', 'master-data-programs', 'master-data-instructors',
            'master-data-activity-formats', 'master-data-payment-accounts', 'master-data-registration-options',
            'master-data-consents', 'master-data-system-settings', 'master-data-follow-up-rounds',
        ],
        'activities' => ['activities-list', 'activities-registrants', 'activities-checkin', 'activities-responses'],
        'evaluations' => ['cohort', 'evaluations', 'evaluations-rounds', 'evaluations-responses'],
    ],

];
