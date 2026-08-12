<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $users = User::with(['roles', 'area'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $roles = Role::where('is_active', true)->get();

        $userPayloads = $users->map(fn (User $u) => $this->toUserPayload($u));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $userPayloads,
                'roles' => $roles->pluck('name'),
            ]);
        }

        return view('admin.users.index', [
            'users' => $userPayloads,
            'roles' => $roles,
        ]);
    }

    public function store(UserRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $nextId = (User::max('id') ?? 0) + 1;
            $code = 'USR-' . str_pad((string) $nextId, 3, '0', STR_PAD_LEFT);

            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $avatarPath = Storage::url($avatarPath);
            }

            $user = User::create([
                'code' => $code,
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => !empty($validated['email']) ? $validated['email'] : ($validated['username'] . '@farmconcept.local'),
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'status' => $validated['status'],
                'avatar_path' => $avatarPath,
            ]);

            $roleIds = Role::whereIn('name', $validated['roles'])->pluck('id')->toArray();
            $user->roles()->sync($roleIds);

            $user->load('roles');

            return response()->json([
                'success' => true,
                'message' => 'บันทึกผู้ใช้งานสำเร็จ',
                'data' => $this->toUserPayload($user),
            ]);
        });
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        return DB::transaction(function () use ($request, $user) {
            $validated = $request->validated();

            $updateData = [
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => !empty($validated['email']) ? $validated['email'] : ($user->email ?? ($validated['username'] . '@farmconcept.local')),
                'phone' => $validated['phone'] ?? null,
                'status' => $validated['status'],
            ];

            if (! empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $updateData['avatar_path'] = Storage::url($avatarPath);
            }

            $user->update($updateData);

            $roleIds = Role::whereIn('name', $validated['roles'])->pluck('id')->toArray();
            $user->roles()->sync($roleIds);

            $user->load('roles');

            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงข้อมูลผู้ใช้งานสำเร็จ',
                'data' => $this->toUserPayload($user),
            ]);
        });
    }

    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        if (auth()->id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถระงับสิทธิ์บัญชีผู้ใช้ของตนเองได้',
            ], 422);
        }

        $newStatus = $user->status === 'ระงับการใช้งาน' ? 'ใช้งานอยู่' : 'ระงับการใช้งาน';
        $user->update(['status' => $newStatus]);
        $user->load('roles');

        $actionText = $newStatus === 'ใช้งานอยู่' ? 'คืนสิทธิ์' : 'ระงับสิทธิ์';

        return response()->json([
            'success' => true,
            'message' => $actionText . ' ' . $user->name . ' เรียบร้อย',
            'data' => $this->toUserPayload($user),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if (auth()->id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบบัญชีผู้ใช้ของตนเองได้',
            ], 422);
        }

        $userName = $user->name;
        $user->roles()->detach();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบผู้ใช้งาน ' . $userName . ' เรียบร้อย',
        ]);
    }

    private function toUserPayload(User $user): array
    {
        $roleNames = $user->roles->pluck('name')->toArray();
        $roleCodes = $user->roles->pluck('code')->toArray();

        return [
            'id' => (string) $user->id,
            'db_id' => $user->id,
            'code' => $user->code,
            'name' => $user->name,
            'username' => $user->username ?? '',
            'email' => $user->email ?? '',
            'phone' => $user->phone ?? '',
            'avatar' => $user->avatar_path ?? '',
            'status' => $user->status ?? 'ใช้งานอยู่',
            'lastLogin' => $user->last_login_at?->toIso8601String() ?? $user->last_login_at?->toDateString(),
            'updatedAt' => $user->updated_at?->toIso8601String() ?? $user->updated_at?->toDateString(),
            'roles' => $roleNames,
            'roleCodes' => $roleCodes,
            'role' => $roleNames[0] ?? '',
        ];
    }
}
