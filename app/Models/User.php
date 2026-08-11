<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['code', 'name', 'username', 'email', 'password', 'phone', 'avatar_path', 'status', 'area_id', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'usr_role_user');
    }

    /**
     * ข้อมูลผู้ใช้ที่ส่งให้ฝั่งหน้าจอ — รูปแบบเดียวกับ TFC_MOCK.currentUser ของต้นแบบ
     * จึงเสียบแทนที่ได้เลยโดยที่ sidebar-render.js และ profile-modal.js ไม่ต้องแก้
     *
     * ห้ามใส่อีเมลหรือข้อมูลอื่นที่หน้าจอไม่ได้ใช้ — ทุกฟิลด์ที่ส่งไปคือข้อมูลที่หลุดออกหน้าเว็บ
     *
     * @return array<string, string>
     */
    public function toClientPayload(): array
    {
        $role = $this->roles->first();

        return [
            'name' => $this->name,
            'username' => $this->username ?? '',
            'phone' => $this->phone ?? '',
            'role' => $role?->name ?? '',
            'roleCode' => $role?->code ?? '',
            'initials' => $this->initials(),
            'avatar' => $this->avatar_path ?? '',
        ];
    }

    /** ตัวย่อจากพยางค์แรกของชื่อและนามสกุล ใช้เป็น avatar สำรองเมื่อไม่มีรูป */
    private function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1);
    }

    /**
     * ผู้ใช้เข้าเมนูนี้ได้ไหม — จริงถ้าบทบาทใดบทบาทหนึ่งอนุญาต
     *
     * ตรรกะเดียวกับ TFC.hasPermission() ในต้นแบบ: สิทธิ์แบบกว้างไม่ได้เก็บไว้
     * แต่คำนวณจากสิทธิ์ระดับเมนู เพื่อไม่ให้สองที่ขัดกันเอง
     *
     * เรียกในลูปต้อง eager load ก่อน: User::with('roles.menuPermissions')
     */
    public function canAccessMenu(string $menuKey): bool
    {
        return $this->roles->contains(
            fn (Role $role) => $role->allowsMenu($menuKey)
        );
    }
}
