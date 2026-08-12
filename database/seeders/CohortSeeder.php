<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\CohortProfile;
use App\Models\FollowUpRound;
use App\Models\FollowUpRoundTemplate;
use App\Models\Participant;
use App\Models\TargetGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CohortSeeder extends Seeder
{
    public function run(): void
    {
        $templates = FollowUpRoundTemplate::where('is_active', true)->orderBy('offset_days')->get();
        if ($templates->isEmpty()) {
            return;
        }

        $areas = Area::all()->keyBy('name');
        $targetGroups = TargetGroup::all()->keyBy('name');

        $members = [
            [
                'code' => 'PID-0001',
                'person_code' => 'PID-0001',
                'name' => 'สมชาย ใจดี',
                'phone' => '081-234-5678',
                'gender' => 'male',
                'area' => 'ชุมชนพูนทรัพย์',
                'target' => 'วัยทำงาน',
                'entry_date' => '2025-08-05',
                'answered_offsets' => [0 => '2025-08-05', 90 => '2025-11-07', 180 => '2026-02-08'],
            ],
            [
                'code' => 'PID-0002',
                'person_code' => 'PID-0002',
                'name' => 'วิภาดา ศรีสุข',
                'phone' => '089-876-5432',
                'gender' => 'female',
                'area' => 'The Farm Concept',
                'target' => 'วัยทำงาน',
                'entry_date' => '2026-05-10',
                'answered_offsets' => [0 => '2026-05-10'],
            ],
            [
                'code' => 'PID-0003',
                'person_code' => 'PID-0003',
                'name' => 'ธนกฤต พงษ์ทอง',
                'phone' => '062-345-6789',
                'gender' => 'male',
                'area' => 'ชุมชนตึกร้าง',
                'target' => 'เด็กและเยาวชน',
                'entry_date' => '2025-06-01',
                'answered_offsets' => [0 => '2025-06-01', 90 => '2025-09-03'],
            ],
            [
                'code' => 'PID-0004',
                'person_code' => 'PID-0004',
                'name' => 'อารีย์ แสงทอง',
                'phone' => '084-567-8901',
                'gender' => 'female',
                'area' => 'ชุมชนพูนทรัพย์',
                'target' => 'ผู้สูงอายุ',
                'entry_date' => '2025-07-20',
                'answered_offsets' => [0 => '2025-07-20', 90 => '2025-10-22', 180 => '2026-01-24', 365 => '2026-07-22'],
            ],
            [
                'code' => 'PID-0005',
                'person_code' => 'PID-0005',
                'name' => 'ณัฐพล บุญมี',
                'phone' => '091-234-5670',
                'gender' => 'male',
                'area' => 'The Farm Concept',
                'target' => 'กลุ่มเปราะบาง',
                'entry_date' => '2026-07-15',
                'answered_offsets' => [0 => '2026-07-15'],
            ],
            [
                'code' => 'PID-0006',
                'person_code' => 'PID-0006',
                'name' => 'กมลชนก อินทร์แก้ว',
                'phone' => '095-777-8899',
                'gender' => 'female',
                'area' => 'ชุมชนตึกร้าง',
                'target' => 'วัยทำงาน',
                'entry_date' => '2026-02-01',
                'answered_offsets' => [0 => '2026-02-01', 90 => '2026-05-04'],
            ],
            [
                'code' => 'PID-0007',
                'person_code' => 'PID-0007',
                'name' => 'สุริยา ทองใบ',
                'phone' => '080-222-3344',
                'gender' => 'male',
                'area' => 'ชุมชนพูนทรัพย์',
                'target' => 'ผู้สูงอายุ',
                'entry_date' => '2025-09-10',
                'answered_offsets' => [0 => '2025-09-10', 90 => '2025-12-12', 180 => '2026-03-14'],
            ],
        ];

        foreach ($members as $data) {
            $areaId = isset($areas[$data['area']]) ? $areas[$data['area']]->id : Area::first()?->id;
            $targetGroupId = isset($targetGroups[$data['target']]) ? $targetGroups[$data['target']]->id : TargetGroup::first()?->id;

            $participant = Participant::updateOrCreate(
                ['code' => $data['code']],
                [
                    'person_code' => $data['person_code'],
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'gender' => $data['gender'],
                    'area_id' => $areaId,
                    'target_group_id' => $targetGroupId,
                ]
            );

            $entryDate = Carbon::parse($data['entry_date']);

            $cohortProfile = CohortProfile::updateOrCreate(
                ['participant_id' => $participant->id],
                [
                    'cohort_code' => 'CHT-' . sprintf('%04d', $participant->id),
                    'entry_date' => $entryDate,
                ]
            );

            foreach ($templates as $tpl) {
                $dueDate = $entryDate->copy()->addDays($tpl->offset_days);
                $answeredAt = isset($data['answered_offsets'][$tpl->offset_days])
                    ? Carbon::parse($data['answered_offsets'][$tpl->offset_days])
                    : null;

                FollowUpRound::updateOrCreate(
                    [
                        'cohort_profile_id' => $cohortProfile->id,
                        'template_id' => $tpl->id,
                    ],
                    [
                        'name' => $tpl->name,
                        'offset_days' => $tpl->offset_days,
                        'due_date' => $dueDate,
                        'answered_at' => $answeredAt,
                    ]
                );
            }
        }
    }
}
