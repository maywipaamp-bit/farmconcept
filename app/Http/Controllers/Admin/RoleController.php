<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Models\Role;
use App\Models\RoleMenuPermission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $roles = Role::withCount('users')
            ->with('menuPermissions')
            ->orderBy('id')
            ->get();

        $menuStructure = config('menu.items', []);

        $rolePayloads = $roles->map(fn (Role $r) => $this->toRolePayload($r));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $rolePayloads,
                'menuStructure' => $menuStructure,
            ]);
        }

        return view('admin.users.roles.index', [
            'roles' => $rolePayloads,
            'menuStructure' => $menuStructure,
        ]);
    }

    public function store(RoleRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $baseCode = Str::slug($validated['name'], '_');
            if (empty($baseCode)) {
                $baseCode = 'role_' . time();
            }
            $code = $baseCode;
            $counter = 1;
            while (Role::where('code', $code)->exists()) {
                $code = $baseCode . '_' . $counter++;
            }

            $role = Role::create([
                'code' => $code,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'],
            ]);

            $this->syncMenuPermissions($role, $request->input('permissions', []));

            $role->loadCount('users')->load('menuPermissions');

            return response()->json([
                'success' => true,
                'message' => 'บันทึกบทบาทและสิทธิ์สำเร็จ',
                'data' => $this->toRolePayload($role),
            ]);
        });
    }

    public function update(RoleRequest $request, Role $role): JsonResponse
    {
        return DB::transaction(function () use ($request, $role) {
            $validated = $request->validated();

            $role->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'],
            ]);

            $this->syncMenuPermissions($role, $request->input('permissions', []));

            $role->loadCount('users')->load('menuPermissions');

            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงบทบาทและสิทธิ์สำเร็จ',
                'data' => $this->toRolePayload($role),
            ]);
        });
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->code === 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบบทบาทผู้ดูแลระบบสูงสุด (Super Admin) ได้',
            ], 422);
        }

        return DB::transaction(function () use ($role) {
            $roleName = $role->name;
            $role->users()->detach();
            $role->menuPermissions()->delete();
            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'ลบบทบาท "' . $roleName . '" เรียบร้อย',
            ]);
        });
    }

    private function syncMenuPermissions(Role $role, array $permissions): void
    {
        $role->menuPermissions()->delete();

        $rows = [];
        foreach ($permissions as $menuKey => $isAllowed) {
            if ($isAllowed) {
                $rows[] = [
                    'role_id' => $role->id,
                    'menu_key' => $menuKey,
                    'is_allowed' => true,
                ];
            }
        }

        if (! empty($rows)) {
            RoleMenuPermission::insert($rows);
        }
    }

    private function toRolePayload(Role $role): array
    {
        $menuPerms = [];
        foreach ($role->menuPermissions as $perm) {
            $menuPerms[$perm->menu_key] = (bool) $perm->is_allowed;
        }

        return [
            'id' => (string) $role->id,
            'db_id' => $role->id,
            'code' => $role->code,
            'name' => $role->name,
            'description' => $role->description ?? '',
            'active' => (bool) $role->is_active,
            'userCount' => $role->users_count ?? $role->users()->count(),
            'menuPermissions' => $menuPerms,
        ];
    }
}
