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
     * เช็คอินหน้างาน — เปิดให้ทั้งเจ้าหน้าที่หน้างานและคนที่จัดการกิจกรรมได้
     *
     * เจ้าหน้าที่หน้างานมีสิทธิ์เมนู Check-in อย่างเดียวได้ ไม่จำเป็นต้องแก้กิจกรรมเป็น
     * ส่วนคนที่มีสิทธิ์จัดการกิจกรรมต้องทำงานในแท็บ Check-in ของหน้ารายละเอียดได้ครบ
     * ไม่งั้นจะเห็นปุ่มแล้วกดไม่ได้ ทั้งที่เข้าหน้านั้นได้อยู่แล้ว
     *
     * กิจกรรมที่ยกเลิกแล้วเช็คอินไม่ได้ ไม่งั้นจะมีคนเข้าร่วมกิจกรรมที่ระบบบอกว่าไม่เกิดขึ้น
     */
    public function checkIn(User $user, Activity $activity): Response
    {
        if (! $user->canAccessMenu('activities-checkin') && ! $user->canAccessMenu('activities-list')) {
            return Response::deny('ไม่มีสิทธิ์เช็คอินผู้เข้าร่วม');
        }

        return $activity->status === Activity::STATUS_CANCELLED
            ? Response::deny('กิจกรรมนี้ถูกยกเลิกแล้ว เช็คอินไม่ได้')
            : Response::allow();
    }

    /**
     * ดู QR ของกิจกรรม — เปิดให้ทั้งคนที่แก้กิจกรรมได้และเจ้าหน้าที่หน้างาน
     *
     * QR ชี้ไป URL สาธารณะที่ใครสแกนก็เปิดได้อยู่แล้ว การจำกัดให้แคบกว่าสิทธิ์เมนู
     * มีผลแค่ทำให้หน้าจอที่ต้องใช้ QR ใช้ไม่ได้
     */
    public function viewQr(User $user, Activity $activity): Response
    {
        return $user->canAccessMenu('activities-list') || $user->canAccessMenu('activities-checkin')
            ? Response::allow()
            : Response::deny('ไม่มีสิทธิ์ดู QR ของกิจกรรม');
    }

    /**
     * ลบกิจกรรมได้เฉพาะที่ยังเป็นฉบับร่าง
     *
     * เผยแพร่แล้วแปลว่ามีคนเห็นและลงทะเบียนได้ การลบทิ้งจะทำให้ข้อมูลผู้เข้าร่วมหายไปด้วย
     * เกณฑ์นี้ต้องตรงกับที่หน้าจอใช้ซ่อนเมนู ไม่งั้นจะมีปุ่มให้กดแล้วค่อยบอกว่าลบไม่ได้
     *
     * ต้องตรวจซ้ำที่นี่แม้หน้าจอจะซ่อนปุ่มไว้แล้ว เพราะการซ่อนปุ่มกันแค่การกดพลาด
     * ไม่ได้กันคนที่ยิงคำขอตรงเข้ามา
     */
    public function delete(User $user, Activity $activity): Response
    {
        if (! $user->canAccessMenu('activities-list')) {
            return Response::deny('ไม่มีสิทธิ์ลบกิจกรรม');
        }

        if ($activity->status !== Activity::STATUS_DRAFT) {
            return Response::deny('กิจกรรมที่เผยแพร่แล้วลบไม่ได้ หากต้องการยุติกิจกรรม ให้เปลี่ยนสถานะเป็น "ยกเลิก" แทน');
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
