<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityFormat;
use App\Models\ActivityRound;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class PublicActivitySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->activities() as $data) {
            $activity = Activity::updateOrCreate(['code' => $data['code']], [
                'name' => $data['name'],
                'description' => $data['description'],
                'type' => $data['type'],
                'format_id' => ActivityFormat::where('name', $data['format'])->value('id'),
                'status' => 'เปิดรับสมัคร',
                'requires_registration' => $data['requires_registration'],
                'has_fee' => $data['fee'] > 0,
                'fee' => $data['fee'],
                'capacity' => $data['capacity'],
                'organizer' => $data['speaker'],
                'cover_image_path' => $this->storeImage($data['image']),
                'start_date' => $data['date'],
                'end_date' => $data['date'],
                'is_published' => true,
                'publish_start_at' => null,
                'publish_end_at' => null,
                'registration_start_at' => null,
                'registration_end_at' => $data['registration_end_at'],
                'visibility' => 'สาธารณะ',
                'is_featured' => $data['is_featured'],
                'public_sort_order' => $data['sort_order'],
            ]);

            ActivityRound::updateOrCreate(
                ['activity_id' => $activity->id, 'round_date' => $data['date']],
                [
                    'time_start' => $data['start'],
                    'time_end' => $data['end'],
                    'location' => 'The Farm Concept',
                    'capacity' => $data['capacity'],
                ]
            );
        }
    }

    private function storeImage(string $filename): string
    {
        $source = public_path('assets/images/'.$filename);
        $path = 'activity-covers/demo/'.$filename;

        if (is_file($source)) {
            Storage::disk('public')->put($path, file_get_contents($source));
        }

        return $path;
    }

    private function activities(): array
    {
        return [
            $this->row('ACT-PUB-001', 'ชวนเพลิดเพลิน สร้างสวนในขวดแก้ว', 'เรียนรู้การจัดสวนขนาดเล็กในขวดแก้ว พร้อมนำผลงานกลับบ้าน', 'กิจกรรม', 'WORKSHOP', 'photo-terrarium-featured.png', '2026-08-02', '16:30', '18:30', 199, 'ทีมศิลปะ The Farm Concept', true, true, 1, '2026-08-01 23:59:00'),
            $this->row('ACT-PUB-002', 'พักมือถือมาเพ้นท์กระถางต้นไม้กัน', 'ลงมือเพ้นท์กระถางต้นไม้ให้น่ารักในสไตล์ของตัวเอง พร้อมนำต้นไม้กลับบ้านไปปลูกต่อ', 'กิจกรรม', 'WORKSHOP', 'photo-pot-painting.png', '2026-08-16', '16:30', '18:30', 199, 'ทีมศิลปะ The Farm Concept', true, true, 2, '2026-08-14 23:59:00'),
            $this->row('ACT-PUB-003', 'จัดดอกไม้จากวัสดุธรรมชาติ', 'จัดช่อดอกไม้และวัสดุจากธรรมชาติด้วยตัวเอง เรียนรู้เทคนิคการจัดวางเบื้องต้น', 'กิจกรรม', 'CRAFT', 'photo-flower-arranging.png', '2026-08-23', '13:00', '15:00', 0, 'ทีมจัดสวน The Farm Concept', true, true, 3, '2026-08-21 23:59:00'),
            $this->row('ACT-PUB-004', 'เบเกอรี่เพื่อสุขภาพ เมนูไร้น้ำตาล', 'ลงมือทำเบเกอรี่เพื่อสุขภาพแบบไร้น้ำตาล เรียนรู้การเลือกวัตถุดิบทดแทนน้ำตาล', 'กิจกรรม', 'FOOD', 'photo-baking-workshop.png', '2026-08-30', '10:00', '13:00', 199, 'อาจารย์พิมพ์ชนก ศรีสมบัติ', true, true, 4, '2026-08-28 23:59:00'),
            $this->row('ACT-PUB-005', 'จ่ายตลาดในสวน 🌿', 'ตลาดผักและผลิตภัณฑ์ชุมชนภายในสวน The Farm Concept', 'อีเว้นท์', 'COMMUNITY', 'photo-market-vegetables.png', '2026-08-09', '09:00', '14:00', 0, 'The Farm Concept', false, true, 5, null),
            $this->row('ACT-PUB-006', 'มาปะคอนเสิร์ต', 'กิจกรรมดนตรีในสวนสำหรับครอบครัวและชุมชน', 'อีเว้นท์', 'COMMUNITY', 'photo-garden-concert.png', '2026-08-24', '17:00', '20:00', 0, 'The Farm Concept', false, true, 6, null),
            $this->row('ACT-PUB-007', 'Happy Beagle Day', 'กิจกรรมพบปะสำหรับผู้เลี้ยงสุนัขบีเกิลและคนรักสัตว์', 'อีเว้นท์', 'COMMUNITY', 'photo-dog-run.png', '2026-09-06', '06:30', '09:00', 0, 'The Farm Concept', false, true, 7, null),
            $this->row('ACT-PUB-008', 'เลอะได้...ไม่เป็นไร', 'กิจกรรมสร้างสรรค์และการเล่นอย่างอิสระสำหรับเด็ก', 'อีเว้นท์', 'COMMUNITY', 'photo-messy-play.png', '2026-09-13', '09:00', '11:00', 0, 'The Farm Concept', false, true, 8, null),
        ];
    }

    private function row(string $code, string $name, string $description, string $type, string $format, string $image, string $date, string $start, string $end, float $fee, string $speaker, bool $requiresRegistration, bool $isFeatured, int $sortOrder, ?string $registrationEndAt): array
    {
        return compact('code', 'name', 'description', 'type', 'format', 'image', 'date', 'start', 'end', 'fee', 'speaker', 'requiresRegistration', 'isFeatured', 'sortOrder', 'registrationEndAt') + [
            'requires_registration' => $requiresRegistration,
            'is_featured' => $isFeatured,
            'sort_order' => $sortOrder,
            'registration_end_at' => $registrationEndAt,
            'capacity' => $requiresRegistration ? 30 : 0,
        ];
    }
}
