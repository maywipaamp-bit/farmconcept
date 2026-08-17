<?php

namespace Tests\Feature;

use App\Models\ConsentDocument;
use App\Models\Option;
use App\Models\Participant;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationMasterDataTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['code' => 'registration-master-admin', 'name' => 'Registration master admin', 'is_active' => true]);
        foreach (['master-data-registration-options', 'master-data-consents'] as $menuKey) {
            $role->menuPermissions()->create(['menu_key' => $menuKey, 'is_allowed' => true]);
        }

        $user = User::create([
            'code' => 'USR-REG-MASTER',
            'name' => 'Registration master tester',
            'email' => 'registration-master@example.test',
            'password' => 'not-used',
            'status' => 'ใช้งานอยู่',
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_registration_option_can_be_created_and_unused_option_can_be_deleted(): void
    {
        $user = $this->admin();

        $key = $this->actingAs($user)->postJson('/admin/master/registration-options', [
            'group' => 'age_range', 'label' => '70 ปีขึ้นไป', 'sortOrder' => 6, 'active' => true,
        ])->assertCreated()->json('row.id');

        $this->actingAs($user)->deleteJson('/admin/master/registration-options/'.urlencode($key))->assertOk();
        $this->assertDatabaseMissing('mst_options', ['option_group' => 'age_range', 'label' => '70 ปีขึ้นไป']);
    }

    public function test_registration_option_in_use_cannot_be_deleted(): void
    {
        $user = $this->admin();
        $option = Option::create([
            'option_group' => 'occupation', 'code' => 'OCC-999', 'label' => 'อาชีพทดสอบ',
            'sort_order' => 999, 'is_active' => true,
        ]);
        Participant::create([
            'code' => 'PTP-TEST', 'person_code' => 'PERSON-TEST', 'name' => 'ผู้ทดสอบ',
            'phone' => '0800000000', 'occupation_id' => $option->id,
        ]);

        $this->actingAs($user)->deleteJson('/admin/master/registration-options/occupation%3AOCC-999')
            ->assertForbidden()->assertJsonFragment(['message' => 'ลบไม่ได้ เพราะตัวเลือกนี้ถูกนำไปใช้แล้ว 1 รายการ']);

        $this->assertDatabaseHas('mst_options', ['id' => $option->id]);
    }

    public function test_only_one_consent_document_per_type_can_be_active(): void
    {
        $user = $this->admin();
        $payload = [
            'type' => 'pdpa', 'title' => 'PDPA ฉบับทดสอบ', 'version' => '1.0',
            'content' => 'รายละเอียดทดสอบ', 'effectiveDate' => null, 'required' => true, 'active' => true,
        ];

        $this->actingAs($user)->postJson('/admin/master/consent-documents', $payload)->assertCreated();
        $this->actingAs($user)->postJson('/admin/master/consent-documents', array_merge($payload, [
            'title' => 'PDPA ฉบับใหม่', 'version' => '2.0',
        ]))->assertUnprocessable()->assertJsonValidationErrors('active');

        $this->assertSame(1, ConsentDocument::where('consent_type', 'pdpa')->where('is_active', true)->count());
    }

    /** หน้า "ตั้งค่าระบบ" ถูกถอดออกแล้ว — เส้นทางต้องหายไปจริง ไม่ใช่แค่ซ่อนเมนู */
    public function test_หน้าตั้งค่าระบบถูกถอดออกแล้ว(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/master/system-settings')
            ->assertNotFound();
    }
}
