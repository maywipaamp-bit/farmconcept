<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * ลำดับสำคัญ — MasterDataSeeder ต้องมาก่อน เพราะ users.area_id อ้าง mst_areas
     * และ DashboardDemoSeeder ต้องอยู่ท้ายสุด เพราะสร้างผู้เข้าร่วมจากใบลงทะเบียน
     * ที่ ActivitySeeder สร้างไว้
     */
    public function run(): void
    {
        $this->call([
            MasterDataSeeder::class,
            RoleAndUserSeeder::class,
            ActivitySeeder::class,
            PublicActivitySeeder::class,
            DashboardDemoSeeder::class,
            ReviewRoundSeeder::class,
        ]);
    }
}
