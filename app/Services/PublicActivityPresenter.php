<?php

namespace App\Services;

use App\Models\Activity;
use Carbon\CarbonInterface;

/**
 * แปลงข้อมูลกิจกรรมเป็นรูปแบบที่หน้าเว็บสาธารณะใช้
 * ใช้ร่วมกันระหว่างหน้ารายการ/รายละเอียด และหน้าลงทะเบียน — ห้าม copy โครงนี้ไปไว้ที่อื่น
 */
class PublicActivityPresenter
{
    /** @return array<string, mixed> */
    public function present(Activity $activity): array
    {
        $round = $activity->rounds->first();
        $date = $round?->round_date ?? $activity->start_date;
        $startTime = $round?->time_start ? substr((string) $round->time_start, 0, 5) : null;
        $endTime = $round?->time_end ? substr((string) $round->time_end, 0, 5) : null;
        $timeLabel = $startTime
            ? $startTime.($endTime ? '–'.$endTime : '').' น.'
            : null;

        return [
            'code' => $activity->code,
            'title' => $activity->name,
            'description' => $activity->description,
            'type' => $activity->type,
            'category' => $activity->format?->name,
            'categoryIcon' => $activity->format?->icon,
            'image' => $activity->cover_image_path ? '/storage/'.ltrim($activity->cover_image_path, '/') : null,
            'date' => $date?->toDateString(),
            'dateLabel' => $this->thaiDate($date),
            'startTime' => $startTime,
            'endTime' => $endTime,
            'timeLabel' => $timeLabel,
            'scheduleLabel' => collect([$this->thaiDate($date), $timeLabel])->filter()->join(' · '),
            'location' => $round?->location ?: '-',
            'speaker' => $activity->instructors->pluck('name')->join(', ') ?: ($activity->organizer ?: '-'),
            'fee' => (float) $activity->fee,
            'isFree' => ! $activity->has_fee,
            'priceLabel' => $activity->has_fee
                ? number_format((float) $activity->fee, 0).' บาท / ท่าน'
                : 'เข้าร่วมฟรี',
            'isFeatured' => $activity->is_featured,
            'registrationStartAt' => $activity->registration_start_at?->toIso8601String(),
            'registrationEndAt' => $activity->registration_end_at?->toIso8601String(),
            'registrationDeadlineLabel' => $this->registrationDeadlineLabel($activity->registration_end_at),
            'canRegister' => $activity->acceptsRegistration(),
            'requiresRegistration' => $activity->requires_registration,
            'requiresCheckin' => $activity->requires_checkin,
            'hasPostSurvey' => $activity->has_post_survey,
            'seatsLeft' => $activity->seatsLeft(),
            'sortOrder' => $activity->public_sort_order,
        ];
    }

    public function thaiDate(?CarbonInterface $date): ?string
    {
        if (! $date) {
            return null;
        }

        $days = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
        $months = [1 => 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        return $days[$date->dayOfWeek].' '
            .$date->day.' '.$months[$date->month].' '
            .str_pad((string) (($date->year + 543) % 100), 2, '0', STR_PAD_LEFT);
    }

    public function thaiDateTime(CarbonInterface $date): string
    {
        return $this->thaiDate($date).' · '.$date->format('H:i').' น.';
    }

    /**
     * นับถอยหลังแบบภาษาพูด — "อีก 5 วันจะปิดรับสมัคร" แทนวันที่/เวลาตรง ๆ
     * ใกล้ปิดมากขึ้นก็ลดหน่วยลง (วัน → ชั่วโมง → นาที) ให้ยังกะเวลาที่เหลือได้จริง
     */
    public function registrationDeadlineLabel(?CarbonInterface $end): ?string
    {
        if (! $end) {
            return null;
        }

        $now = now();

        if ($end->isPast()) {
            return 'ปิดรับสมัครแล้ว';
        }

        if (($days = (int) floor($now->diffInDays($end))) >= 1) {
            return "อีก {$days} วันจะปิดรับสมัคร";
        }

        if (($hours = (int) floor($now->diffInHours($end))) >= 1) {
            return "อีก {$hours} ชั่วโมงจะปิดรับสมัคร";
        }

        $minutes = max(1, (int) floor($now->diffInMinutes($end)));

        return "อีก {$minutes} นาทีจะปิดรับสมัคร";
    }
}
