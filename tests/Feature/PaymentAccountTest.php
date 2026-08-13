<?php

namespace Tests\Feature;

use App\Models\PaymentAccount;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentAccountTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create([
            'code' => 'payment-admin',
            'name' => 'ผู้ดูแลข้อมูลรับชำระ',
            'is_active' => true,
        ]);
        $role->menuPermissions()->create([
            'menu_key' => 'master-data-payment-accounts',
            'is_allowed' => true,
        ]);

        $user = User::create([
            'code' => 'USR-PAYMENT',
            'name' => 'ผู้ทดสอบข้อมูลรับชำระ',
            'email' => 'payment@example.test',
            'password' => 'not-used',
            'status' => 'ใช้งานอยู่',
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'bankCode' => 'KBANK',
            'accountNumber' => '035-3-67251-7',
            'accountName' => 'นางสาวทดสอบ ระบบรับชำระ',
            'active' => true,
        ];
    }

    public function test_หน้าข้อมูลการรับชำระใช้สิทธิ์เมนูและเปิด_popup_เพิ่มได้(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/master/payment-accounts')
            ->assertOk()
            ->assertSee('ข้อมูลการรับชำระ')
            ->assertSee('payment-account-form-modal')
            ->assertDontSee('ค้นหาธนาคาร เลขที่บัญชี หรือชื่อบัญชี');
    }

    public function test_เปิดใช้งานได้เพียงหนึ่งบัญชีและบัญชีเดิมถูกปิดอัตโนมัติ(): void
    {
        $user = $this->admin();

        $first = $this->actingAs($user)
            ->postJson('/admin/master/payment-accounts', $this->payload())
            ->assertCreated()
            ->json('row.id');

        $second = $this->actingAs($user)
            ->postJson('/admin/master/payment-accounts', $this->payload([
                'bankCode' => 'KTB',
                'accountNumber' => '060-3-25483-7',
            ]))
            ->assertCreated()
            ->json('row.id');

        $this->assertSame(1, PaymentAccount::where('is_active', true)->count());
        $this->assertFalse(PaymentAccount::where('code', $first)->firstOrFail()->is_active);
        $this->assertTrue(PaymentAccount::where('code', $second)->firstOrFail()->is_active);
    }

    public function test_บัญชีธนาคารและเลขที่บัญชีซ้ำกันไม่ได้(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->postJson('/admin/master/payment-accounts', $this->payload())->assertCreated();

        $this->actingAs($user)
            ->postJson('/admin/master/payment-accounts', $this->payload(['accountName' => 'ชื่อใหม่']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('accountNumber');
    }

    public function test_แนบภาพ_qr_code_และลบรายการพร้อมไฟล์ได้(): void
    {
        Storage::fake('public');
        $user = $this->admin();

        $code = $this->actingAs($user)
            ->postJson('/admin/master/payment-accounts', $this->payload(['active' => false]))
            ->assertCreated()
            ->json('row.id');

        $uploadResponse = $this->actingAs($user)
            ->post('/admin/master/payment-accounts/'.$code.'/qr-code', [
                'qrCode' => UploadedFile::fake()->image('qr-code.png', 300, 300),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertStringStartsWith('/storage/payment-qr-codes/', $uploadResponse->json('url'));

        $path = PaymentAccount::where('code', $code)->firstOrFail()->qr_code_path;
        Storage::disk('public')->assertExists($path);

        $this->actingAs($user)
            ->deleteJson('/admin/master/payment-accounts/'.$code)
            ->assertOk();

        $this->assertDatabaseMissing('mst_payment_accounts', ['code' => $code]);
        Storage::disk('public')->assertMissing($path);
    }
}
