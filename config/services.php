<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
     * LINE Login v2.1 — ใช้กับหน้าลงทะเบียนกิจกรรมของบุคคลทั่วไป
     * ค่าทั้งสองมาจาก LINE Developers Console › Channel (LINE Login) › Basic settings
     * ไม่ตั้งค่าไว้ = ปุ่ม "เข้าสู่ระบบด้วย LINE" จะไม่แสดง ระบบยังลงทะเบียนด้วยเบอร์โทรได้ตามปกติ
     */
    'line' => [
        'channel_id' => env('LINE_LOGIN_CHANNEL_ID'),
        'channel_secret' => env('LINE_LOGIN_CHANNEL_SECRET'),

        /* คนละ channel กับ LINE Login — ตัวนี้ใช้ "ส่งข้อความหา" ผู้ใช้ (Messaging API)
           ยังไม่ตั้งค่า = ระบบจะรายงานว่าส่งแจ้งเตือนไม่สำเร็จ ไม่ใช่ทำเป็นว่าส่งแล้ว */
        'messaging_token' => env('LINE_MESSAGING_CHANNEL_ACCESS_TOKEN'),

        /* ปลายทางแจ้งเตือนแอดมิน — ปกติเป็น "group id" ของกลุ่ม LINE ทีมงานที่เชิญ OA เข้าไปแล้ว
           (ใส่ user id ของคนคนเดียวก็ได้) ไม่ตั้งค่า = ไม่ส่ง ไม่ใช่ error
           ใช้ค่าคอนฟิกแทนการเก็บ LINE id รายคนในตาราง users เพราะทีมงานเปลี่ยนคนบ่อยกว่ากลุ่ม */
        'admin_notify_to' => env('LINE_ADMIN_NOTIFY_TO'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
