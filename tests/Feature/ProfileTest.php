<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $role = Role::create([
            'code' => 'staff',
            'name' => 'เจ้าหน้าที่',
            'is_active' => true,
        ]);

        $user = User::create([
            'code' => 'USR-PROFILE',
            'name' => 'ชื่อเดิม',
            'username' => 'profile-user',
            'email' => 'profile@example.test',
            'phone' => '0800000000',
            'password' => 'old-password',
            'status' => 'ใช้งานอยู่',
        ]);

        $user->roles()->attach($role);

        return $user;
    }

    public function test_profile_requires_authentication(): void
    {
        $this->post('/admin/profile')->assertRedirect('/login');
    }

    public function test_user_can_update_own_profile_and_username_without_changing_role_or_password(): void
    {
        $user = $this->user();
        $password = $user->password;

        $response = $this->actingAs($user)->postJson('/admin/profile', [
            'name' => 'ชื่อใหม่ นามสกุลใหม่',
            'phone' => '0812345678',
            'username' => 'profile-user-new',
            'role' => 'ผู้ดูแลระบบสูงสุด',
            'password' => '',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'ชื่อใหม่ นามสกุลใหม่')
            ->assertJsonPath('data.username', 'profile-user-new')
            ->assertJsonPath('data.role', 'เจ้าหน้าที่');

        $user->refresh();
        $this->assertSame('0812345678', $user->phone);
        $this->assertSame('profile-user-new', $user->username);
        $this->assertSame($password, $user->password);
    }

    public function test_user_can_change_password_and_avatar(): void
    {
        Storage::fake('public');
        $user = $this->user();

        $response = $this->actingAs($user)->post('/admin/profile', [
            'name' => $user->name,
            'phone' => $user->phone,
            'username' => $user->username,
            'password' => 'new-password',
            'avatar' => UploadedFile::fake()->image('profile.png', 320, 320)->size(300),
        ], ['Accept' => 'application/json']);

        $response->assertOk();
        $user->refresh();

        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertStringStartsWith('/storage/avatars/', $user->avatar_path);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $user->avatar_path));
    }

    public function test_profile_validation_rejects_missing_phone_and_large_avatar(): void
    {
        Storage::fake('public');
        $user = $this->user();

        $this->actingAs($user)->post('/admin/profile', [
            'name' => $user->name,
            'phone' => '',
            'username' => $user->username,
            'avatar' => UploadedFile::fake()->image('large.png')->size(5200),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone', 'avatar']);
    }
}
