{{--
    ไอคอนเส้นของแดชบอร์ด — เส้นบาง ไม่มีพื้น สีมาจาก currentColor ของกล่องที่ครอบอยู่
    รับ $name และ $size (ค่าเริ่มต้น 21) · aria-hidden เพราะทุกไอคอนมีข้อความกำกับอยู่ข้าง ๆ แล้ว
--}}
@php
    $paths = [
        'users' => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8m14 10v-2a4 4 0 0 0-3-3.87',
        'calendar' => 'M8 2v4m8-4v4M3 10h18M5 6h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2',
        'check' => 'M9 11l3 3 8-8M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11',
        'pin' => 'M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11m0-8.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5',
        'female' => 'M12 14a5 5 0 1 0 0-10 5 5 0 0 0 0 10m0 0v7m-3-3h6',
        'male' => 'M10 14a5 5 0 1 0 0-10 5 5 0 0 0 0 10m3.5-6.5L20 4m0 0h-5m5 0v5',
        'neutral' => 'M12 13a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9m0 0v7M8.5 20h7',
        'trend' => 'M3 17l6-6 4 4 7-7m0 0h-5m5 0v5',
        'star' => 'M12 2l2.4 6.9H22l-6 4.4 2.3 7-6.3-4.5L5.7 20 8 13.3l-6-4.4h7.6z',
        'alert' => 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0',
        'clock' => 'M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0',
        'chart' => 'M3 3v18h18M7 15l4-4 3 3 5-6',
    ];
    $size ??= 21;
@endphp
<svg viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" fill="none" stroke="currentColor"
     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
  <path d="{{ $paths[$name] ?? $paths['chart'] }}"></path>
</svg>
