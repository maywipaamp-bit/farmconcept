<?php

/*
|--------------------------------------------------------------------------
| ข้อความแจ้งเตือนการตรวจข้อมูล (ภาษาไทย)
|--------------------------------------------------------------------------
| Laravel 11 ขึ้นไปไม่ได้แถมไฟล์ภาษามาให้ ต้องสร้างเอง
| คีย์ที่ไม่ได้แปลไว้จะถอยไปใช้ภาษาอังกฤษตาม APP_FALLBACK_LOCALE
|
| :attribute จะถูกแทนด้วยชื่อฟิลด์ภาษาไทยที่ FormRequest ประกาศไว้ในเมธอด attributes()
| ถ้าไม่ได้ประกาศจะได้ชื่อคอลัมน์ดิบออกมา ซึ่งผู้ใช้อ่านไม่รู้เรื่อง —
| ทุก FormRequest จึงต้องประกาศ attributes() ให้ครบทุกฟิลด์ที่ผู้ใช้เห็น
*/

return [

    'required' => 'กรุณากรอก:attribute',
    'required_if' => 'กรุณากรอก:attribute เมื่อ :other เป็น :value',
    'required_with' => 'กรุณากรอก:attribute เมื่อมี :values',
    'required_without' => 'กรุณากรอก:attribute เมื่อไม่มี :values',
    'filled' => ':attribute ต้องไม่เว้นว่าง',
    'present' => 'ต้องส่ง:attribute มาด้วย',

    'confirmed' => ':attribute ยืนยันไม่ตรงกัน',
    'in' => ':attribute ที่เลือกไม่ถูกต้อง',
    'not_in' => ':attribute ที่เลือกใช้ไม่ได้',
    'exists' => 'ไม่พบ:attribute ที่เลือกในระบบ',
    'unique' => ':attribute นี้ถูกใช้ไปแล้ว',

    /* ใช้กับรายการที่กรอกได้หลายบรรทัด เช่น หลักสูตรในโปรแกรม หรือจำนวนวันของรอบติดตาม */
    'distinct' => ':attribute ซ้ำกัน ต้องไม่ให้ซ้ำ',
    'present' => 'ต้องส่ง :attribute มาด้วยเสมอ',

    'boolean' => ':attribute ต้องเป็นใช่หรือไม่ใช่เท่านั้น',
    'integer' => ':attribute ต้องเป็นจำนวนเต็ม',
    'numeric' => ':attribute ต้องเป็นตัวเลข',
    'string' => ':attribute ต้องเป็นข้อความ',
    'array' => ':attribute ต้องเป็นรายการ',
    'date' => ':attribute ต้องเป็นวันที่ที่ถูกต้อง',
    'date_format' => ':attribute ต้องอยู่ในรูปแบบ :format',
    'email' => ':attribute ต้องเป็นอีเมลที่ถูกต้อง',
    'url' => ':attribute ต้องเป็นลิงก์ที่ถูกต้อง',
    'image' => ':attribute ต้องเป็นไฟล์รูปภาพ',
    'file' => ':attribute ต้องเป็นไฟล์',
    'mimes' => ':attribute ต้องเป็นไฟล์ชนิด :values',
    'mimetypes' => ':attribute ต้องเป็นไฟล์ชนิด :values',
    'dimensions' => ':attribute มีขนาดภาพไม่ถูกต้อง',

    /* เกิดเมื่อ PHP รับไฟล์ไม่สำเร็จตั้งแต่ต้น เช่น ไฟล์ใหญ่เกิน upload_max_filesize
       หรือเซิร์ฟเวอร์สร้างไฟล์ชั่วคราวไม่ได้ — เป็นปัญหาฝั่งเซิร์ฟเวอร์ ไม่ใช่ผู้ใช้กรอกผิด */
    'uploaded' => 'อัปโหลด:attribute ไม่สำเร็จ ไฟล์อาจใหญ่เกินที่เซิร์ฟเวอร์รับได้',

    'after' => ':attribute ต้องอยู่หลัง :date',
    'after_or_equal' => ':attribute ต้องเป็น :date หรือหลังจากนั้น',
    'before' => ':attribute ต้องอยู่ก่อน :date',
    'before_or_equal' => ':attribute ต้องเป็น :date หรือก่อนหน้านั้น',

    'min' => [
        'numeric' => ':attribute ต้องไม่น้อยกว่า :min',
        'string' => ':attribute ต้องมีอย่างน้อย :min ตัวอักษร',
        'array' => 'ต้องเลือก:attribute อย่างน้อย :min รายการ',
        'file' => ':attribute ต้องมีขนาดอย่างน้อย :min กิโลไบต์',
    ],

    'max' => [
        'numeric' => ':attribute ต้องไม่เกิน :max',
        'string' => ':attribute ต้องยาวไม่เกิน :max ตัวอักษร',
        'array' => 'เลือก:attribute ได้ไม่เกิน :max รายการ',
        'file' => ':attribute ต้องมีขนาดไม่เกิน :max กิโลไบต์',
    ],

    'between' => [
        'numeric' => ':attribute ต้องอยู่ระหว่าง :min ถึง :max',
        'string' => ':attribute ต้องยาว :min ถึง :max ตัวอักษร',
    ],

    'size' => [
        'numeric' => ':attribute ต้องเท่ากับ :size',
        'string' => ':attribute ต้องยาว :size ตัวอักษร',
        'array' => 'ต้องเลือก:attribute :size รายการ',
    ],

    /* ชื่อฟิลด์ที่ใช้ร่วมกันหลายฟอร์ม — ฟิลด์เฉพาะฟอร์มให้ประกาศใน attributes() ของ FormRequest นั้น */
    'attributes' => [
        'name' => 'ชื่อ',
        'email' => 'อีเมล',
        'phone' => 'เบอร์โทรศัพท์',
        'username' => 'ชื่อผู้ใช้งาน',
        'password' => 'รหัสผ่าน',
        'status' => 'สถานะ',
    ],

];
