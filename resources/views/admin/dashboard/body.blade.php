{{--
    เนื้อในของแดชบอร์ด — ทุกอย่างที่เปลี่ยนตามตัวกรองช่วงเวลาอยู่ในไฟล์นี้

    ใช้สองที่ด้วยโครงเดียวกัน: ตอนโหลดหน้าเต็ม (dashboard.blade.php)
    และตอนสลับช่วงเวลา (DashboardController::fragment) จึงไม่มีสูตรวาดกราฟชุดที่สอง

    ค่าตัวเลขทุกตัวอยู่ในข้อความบนหน้าจอ ไม่ได้ซ่อนไว้ใน tooltip อย่างเดียว
    tooltip เป็นข้อมูลเสริมตามข้อ A11y ของ handoff
--}}
@php
    /**
     * แอตทริบิวต์ของชิ้นส่วนที่ชี้ได้
     *
     * data-dbo-key  ใช้จับกลุ่มชิ้นที่เป็นเรื่องเดียวกัน (แท่ง + แถวในตาราง + ชิ้นโดนัท)
     *               ชี้ชิ้นไหนก็สว่างพร้อมกันทั้งกลุ่ม ที่เหลือจาง
     * data-dbo-tip  เนื้อหาในกล่อง tooltip — หัวข้อ + คู่ key/value
     *               ฝังมากับ HTML ไม่ต้องยิงคำขอตอน hover
     */
    $tip = function (string $key, string $title, array $lines): string {
        $payload = json_encode(['title' => $title, 'lines' => $lines], JSON_UNESCAPED_UNICODE);

        return 'data-dbo-key="' . e($key) . '" data-dbo-tip="' . e($payload) . '"';
    };

    /** จำนวนคนแบบมีคอมมา — ใช้ซ้ำทั้งหน้า */
    $num = fn (int $value): string => number_format($value);
@endphp

@include('admin.dashboard.rows.kpi')
@include('admin.dashboard.rows.participants')
@include('admin.dashboard.rows.cohort')
@include('admin.dashboard.rows.assessment')
@include('admin.dashboard.rows.areas')
