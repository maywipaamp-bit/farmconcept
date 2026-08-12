<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * พื้นที่ดำเนินงาน — เก็บ id แทนข้อความ
 *
 * ของเดิมเก็บ ประเภทพื้นที่ · กลุ่มพื้นที่ · จังหวัด · เขต/อำเภอ · หน่วยงานร่วม เป็นข้อความล้วน
 * ทั้งที่ตารางอ้างอิงมีอยู่แล้วครบ (mst_options และ mst_districts)
 *
 * ปัญหาที่เกิดแล้วจริงในข้อมูลชุดนี้: ช่องหน่วยงานร่วมมีทั้ง "สสส. พลเมืองอาสา" และ "สสส"
 * รายงาน "จำนวนพื้นที่ต่อหน่วยงาน" จึงนับเป็นสองหน่วยงาน — และจะเกิดกับอีกสามช่องเช่นกัน
 * เมื่อทีมเปลี่ยนชื่อตัวเลือก ข้อมูลเก่าจะกลายเป็นกลุ่มแยกที่ไม่มีใครรู้
 *
 * จังหวัดไม่เก็บซ้ำ — เก็บแค่ district_id แล้ว join เอา ไม่งั้นแก้จังหวัดแต่ลืมแก้อำเภอ
 * จะได้คู่ที่ขัดกันเอง
 *
 * ไมเกรชันนี้ทำแค่ "เพิ่มและเติมข้อมูล" ยังไม่ลบคอลัมน์ข้อความ
 * เพื่อให้ตรวจผลการเติมได้ก่อน แล้วค่อยลบในไมเกรชันถัดไป
 * (MySQL ย้อน DDL ไม่ได้ ถ้าเติมพลาดกลางทางจะเหลือตารางที่กู้ยาก)
 */
return new class extends Migration
{
    public function up(): void
    {
        /* ---------- หน่วยงานร่วม: หลายค่าต่อพื้นที่ และต้องรายงานรวมได้ ----------
           จึงเป็นตารางกับ pivot ไม่ใช่ JSON — JSON จัดกลุ่มต้องใช้ JSON_TABLE ซึ่งช้าและ index ไม่ได้
           และไม่ได้กันชื่อเพี้ยนอยู่ดี */
        Schema::create('mst_partner_orgs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mst_area_partner_org', function (Blueprint $table) {
            $table->foreignId('area_id')->constrained('mst_areas')->cascadeOnDelete();
            $table->foreignId('partner_org_id')->constrained('mst_partner_orgs')->cascadeOnDelete();
            $table->primary(['area_id', 'partner_org_id']);
        });

        Schema::table('mst_areas', function (Blueprint $table) {
            /* nullable ไว้ก่อน เพราะยังต้องเติมข้อมูล — บังคับ NOT NULL ในไมเกรชันถัดไป
               restrictOnDelete เพราะลบตัวเลือกที่พื้นที่ใช้อยู่ไม่ควรทำได้เงียบ ๆ */
            $table->foreignId('area_type_id')->nullable()->after('name')->constrained('mst_options')->restrictOnDelete();
            $table->foreignId('area_group_id')->nullable()->after('area_type_id')->constrained('mst_options')->restrictOnDelete();
            $table->foreignId('district_id')->nullable()->after('area_group_id')->constrained('mst_districts')->restrictOnDelete();
        });

        $this->backfill();
    }

    /**
     * เติม id จากข้อความเดิม
     *
     * จับคู่ด้วยชื่อแบบตัดช่องว่างหัวท้าย — ตรวจแล้วว่าข้อมูลชุดปัจจุบันจับคู่ได้ครบทุกค่า
     * แถวที่จับคู่ไม่ได้จะถูกปล่อยเป็น null และไมเกรชันถัดไปจะไม่ยอมให้ผ่าน
     */
    private function backfill(): void
    {
        $typeIds = DB::table('mst_options')->where('option_group', 'area_type')->pluck('id', 'label');
        $groupIds = DB::table('mst_options')->where('option_group', 'area_group')->pluck('id', 'label');

        $districtIds = DB::table('mst_districts')->get()
            ->mapWithKeys(fn ($d) => [$d->province . '|' . $d->name => $d->id]);

        foreach (DB::table('mst_areas')->get() as $area) {
            DB::table('mst_areas')->where('id', $area->id)->update([
                'area_type_id' => $typeIds[trim((string) $area->area_type)] ?? null,
                'area_group_id' => $groupIds[trim((string) $area->area_group)] ?? null,
                'district_id' => $districtIds[trim((string) $area->province) . '|' . trim((string) $area->district)] ?? null,
            ]);

            $this->backfillPartners($area);
        }
    }

    /**
     * หน่วยงานร่วมเดิมเก็บเป็นข้อความคั่นด้วยจุลภาค — แยกเป็นรายชื่อแล้วสร้างทีละหน่วยงาน
     *
     * ไม่รวม "สสส" กับ "สสส. พลเมืองอาสา" เข้าด้วยกันเอง เพราะเดาแทนทีมไม่ได้ว่าอันไหนถูก
     * ทั้งสองจะถูกสร้างไว้ให้ทีมมารวมเองทีหลังผ่านหน้าจอ
     */
    private function backfillPartners(object $area): void
    {
        $names = collect(explode(',', (string) $area->partner_org))
            ->map(fn (string $n) => trim($n))
            ->filter()
            ->unique();

        foreach ($names as $name) {
            $id = DB::table('mst_partner_orgs')->where('name', $name)->value('id')
                ?? DB::table('mst_partner_orgs')->insertGetId([
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table('mst_area_partner_org')->insertOrIgnore([
                'area_id' => $area->id,
                'partner_org_id' => $id,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('mst_areas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_type_id');
            $table->dropConstrainedForeignId('area_group_id');
            $table->dropConstrainedForeignId('district_id');
        });

        Schema::dropIfExists('mst_area_partner_org');
        Schema::dropIfExists('mst_partner_orgs');
    }
};
