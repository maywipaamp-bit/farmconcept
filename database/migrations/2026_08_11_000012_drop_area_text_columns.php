<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ลบคอลัมน์ข้อความของพื้นที่ หลังตรวจแล้วว่า id ถูกเติมครบ
 *
 * แยกจากไมเกรชันก่อนหน้าเพื่อให้ตรวจผลการเติมได้ก่อนลบของเดิมทิ้ง
 * ปฏิเสธตัวเองถ้ายังมีแถวที่เติมไม่ได้ — ลบไปแล้วข้อมูลกู้ไม่ได้
 *
 * ไม่ลบ area_type ทิ้งอย่างเดียว แต่บังคับ NOT NULL ให้ id ด้วย
 * เพื่อไม่ให้มีพื้นที่ที่ไม่รู้ว่าอยู่กลุ่มไหนหลุดเข้ามาภายหลัง
 * (ประเภทพื้นที่เป็น nullable ตามฟอร์มเดิมที่ไม่ได้บังคับกรอก)
 */
return new class extends Migration
{
    public function up(): void
    {
        $unfilled = DB::table('mst_areas')
            ->whereNull('area_group_id')
            ->orWhereNull('district_id')
            ->count();

        if ($unfilled > 0) {
            throw new RuntimeException(
                "ยังมีพื้นที่ {$unfilled} แห่งที่เติม id ไม่ได้ — แก้ข้อมูลให้ตรงกับตารางอ้างอิงก่อน แล้วค่อยรันใหม่"
            );
        }

        Schema::table('mst_areas', function (Blueprint $table) {
            $table->dropColumn(['area_type', 'area_group', 'province', 'district', 'partner_org']);
        });

        Schema::table('mst_areas', function (Blueprint $table) {
            $table->foreignId('area_group_id')->nullable(false)->change();
            $table->foreignId('district_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('mst_areas', function (Blueprint $table) {
            $table->foreignId('area_group_id')->nullable()->change();
            $table->foreignId('district_id')->nullable()->change();
        });

        Schema::table('mst_areas', function (Blueprint $table) {
            $table->string('area_type', 60)->nullable()->after('name');
            $table->string('area_group', 60)->nullable()->after('area_type');
            $table->string('province', 120)->nullable()->after('area_group');
            $table->string('district', 120)->nullable()->after('province');
            $table->string('partner_org', 200)->nullable()->after('end_date');
        });

        /* เขียนข้อความกลับจาก id เพื่อให้ย้อนกลับแล้วข้อมูลไม่หาย */
        DB::table('mst_areas')->orderBy('id')->each(function ($area) {
            $type = DB::table('mst_options')->where('id', $area->area_type_id)->value('label');
            $group = DB::table('mst_options')->where('id', $area->area_group_id)->value('label');
            $district = DB::table('mst_districts')->where('id', $area->district_id)->first();

            $partners = DB::table('mst_area_partner_org as p')
                ->join('mst_partner_orgs as o', 'o.id', '=', 'p.partner_org_id')
                ->where('p.area_id', $area->id)
                ->pluck('o.name')
                ->join(', ');

            DB::table('mst_areas')->where('id', $area->id)->update([
                'area_type' => $type,
                'area_group' => $group,
                'province' => $district?->province,
                'district' => $district?->name,
                'partner_org' => $partners ?: null,
            ]);
        });
    }
};
