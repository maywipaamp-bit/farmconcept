<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ActivityPolicy
{
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

        return Response::allow();
    }
}
