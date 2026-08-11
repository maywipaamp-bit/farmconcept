<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * ลำดับสำคัญ — MasterDataSeeder ต้องมาก่อน เพราะ users.area_id อ้าง mst_areas
     */
    public function run(): void
    {
        $this->call([
            MasterDataSeeder::class,
            RoleAndUserSeeder::class,
            ActivitySeeder::class,
        ]);
    }
}
