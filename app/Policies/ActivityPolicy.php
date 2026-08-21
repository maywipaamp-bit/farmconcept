<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ActivityPolicy
{
    /**
     * สร้างกิจกรรมใหม่ — ใช้สิทธิ์เดียวกับการแก้ไข
     *
     * ยังไม่มีกิจกรรมให้ตรวจสถานะ จึงเหลือแค่เรื่องสิทธิ์เมนู
     */
    public function create(User $user): Response
    {
        return $user->canAccessMenu('activities-list')
            ? Response::allow()
            : Response::deny('ไม่มีสิทธิ์สร้างกิจกรรม');
    }

    /**
     * แก้ไขได้ทุกสถานะ ยกเว้นที่ยกเลิกไปแล้ว
     *
     * กิจกรรมที่ยกเลิกเป็นบันทึกทางประวัติศาสตร์ — แก้ย้อนหลังจะทำให้รายงานที่อ้างอิงไปแล้วเพี้ยน
     */
    public function update(User $user, Activity $activity): Response
    {
        if (! $user->canAccessMenu('activities-list')) {
            return Response::deny('ไม่มีสิทธิ์แก้ไขกิจกรรม');
        }

        return $activity->status === Activity::STATUS_CANCELLED
            ? Response::deny('กิจกรรมที่ยกเลิกแล้วแก้ไขไม่ได้')
            : Response::allow();
    }

    /**
     * เช็คอินหน้างาน — ใช้สิทธิ์จัดการกิจกรรม
     *
     * เดิมแยกสิทธิ์เมนู Check-in ไว้ให้เจ้าหน้าที่หน้างานที่แก้กิจกรรมไม่ได้
     * แต่เมนูนั้นถูกถอดออกแล้ว งานเช็คอินอยู่ในแท็บของหน้ารายละเอียดกิจกรรม
     * ซึ่งต้องมีสิทธิ์จัดการกิจกรรมถึงจะเข้าได้อยู่แล้ว
     *
     * กิจกรรมที่ยกเลิกแล้วเช็คอินไม่ได้ ไม่งั้นจะมีคนเข้าร่วมกิจกรรมที่ระบบบอกว่าไม่เกิดขึ้น
     */
    public function checkIn(User $user, Activity $activity): Response
    {
        /* เดิมยอมรับสิทธิ์เมนู Check-in ด้วย แต่เมนูนั้นถูกถอดออกแล้ว
           เหลือเกณฑ์เดียวคือสิทธิ์จัดการกิจกรรม ซึ่งเป็นทางเข้าเดียวของงานเช็คอินตอนนี้ */
        if (! $user->canAccessMenu('activities-list')) {
            return Response::deny('ไม่มีสิทธิ์เช็คอินผู้เข้าร่วม');
        }

        return $activity->status === Activity::STATUS_CANCELLED
            ? Response::deny('กิจกรรมนี้ถูกยกเลิกแล้ว เช็คอินไม่ได้')
            : Response::allow();
    }

    /**
     * ดู QR ของกิจกรรม
     *
     * QR ชี้ไป URL สาธารณะที่ใครสแกนก็เปิดได้อยู่แล้ว การจำกัดให้แคบกว่าสิทธิ์เมนู
     * มีผลแค่ทำให้หน้าจอที่ต้องใช้ QR ใช้ไม่ได้
     */
    public function viewQr(User $user, Activity $activity): Response
    {
        return $user->canAccessMenu('activities-list')
            ? Response::allow()
            : Response::deny('ไม่มีสิทธิ์ดู QR ของกิจกรรม');
    }

    /**
     * ลบกิจกรรมได้ทุกสถานะ
     *
     * เดิมจำกัดไว้เฉพาะฉบับร่าง เพราะกลัวข้อมูลผู้เข้าร่วมหาย
     * แต่ Activity ใช้ SoftDeletes — การลบเป็นการซ่อน ไม่ได้ล้างแถวลูกทิ้ง
     * ข้อมูลลงทะเบียน/Check-in/คำตอบยังอยู่ครบและกู้คืนได้ ข้อจำกัดเดิมจึงเข้มเกินเหตุ
     * และทำให้กิจกรรมที่เผยแพร่ผิดหรือสร้างซ้ำค้างอยู่ในระบบโดยไม่มีทางเอาออก
     *
     * ที่ยังกันไว้คืออีเวนท์ที่มีกิจกรรมลูก เพราะ FK ของ parent_event_id เป็น RESTRICT
     * ส่วนคำเตือนว่ามีข้อมูลผูกอยู่เท่าไร แสดงที่กล่องยืนยันฝั่งหน้าจอ
     *
     * ต้องตรวจซ้ำที่นี่แม้หน้าจอจะเตือนแล้ว เพราะการเตือนกันแค่การกดพลาด
     * ไม่ได้กันคนที่ยิงคำขอตรงเข้ามา
     */
    public function delete(User $user, Activity $activity): Response
    {
        if (! $user->canAccessMenu('activities-list')) {
            return Response::deny('ไม่มีสิทธิ์ลบกิจกรรม');
        }

        /* อีเวนท์ที่ยังมีกิจกรรมอยู่ข้างในลบไม่ได้ — ต้องย้ายหรือลบกิจกรรมออกให้หมดก่อน
           FK ตั้งเป็น RESTRICT ไว้อีกชั้นแล้ว ตรงนี้มีไว้เพื่อบอกเหตุผลเป็นภาษาคน
           แทนที่จะให้ผู้ใช้เจอ error ของฐานข้อมูลดิบ ๆ */
        $childCount = $activity->childActivities()->count();

        if ($childCount > 0) {
            return Response::deny(
                'อีเวนท์นี้มีกิจกรรมอยู่ ' . $childCount . ' รายการ ลบไม่ได้ '
                . 'ให้ย้ายกิจกรรมออกจากอีเวนท์หรือลบกิจกรรมเหล่านั้นก่อน'
            );
        }

        return Response::allow();
    }
}
