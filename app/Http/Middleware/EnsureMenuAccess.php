<?php

namespace App\Http\Middleware;

use App\Services\MenuService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ตรวจสิทธิ์ระดับเมนูก่อน render
 *
 * ใช้ menu_key ที่ระบุมากับ route ถ้าไม่ระบุจะเดาจาก path ที่ขอมา (สำหรับหน้า static เดิม)
 * path ที่ไม่รู้จักจะถูกปฏิเสธ ไม่ใช่ปล่อยผ่าน — เพิ่มหน้าใหม่แล้วลืมลงทะเบียนจะเจอ 403 ทันที
 * ดีกว่าเผลอเปิดหน้าที่ยังไม่มีใครกำหนดสิทธิ์ให้
 *
 * ระบุได้หลายคีย์คั่นด้วย | แล้วผ่านเมื่อมีสิทธิ์ "อย่างน้อยหนึ่งคีย์"
 * ใช้กับ endpoint ที่มีหลายหน้าจอเรียก เช่นการยืนยันชำระเงินที่เรียกได้ทั้งจาก
 * หน้าผู้ลงทะเบียนและจากแท็บในหน้ารายละเอียดกิจกรรม — คนที่จัดการกิจกรรมได้
 * ต้องทำงานในแท็บของกิจกรรมได้ครบ โดยไม่ต้องเปิดสิทธิ์เมนูอื่นเพิ่ม
 */
class EnsureMenuAccess
{
    public function __construct(private readonly MenuService $menu)
    {
    }

    public function handle(Request $request, Closure $next, ?string $menuKey = null): Response
    {
        $key = $menuKey ?? $this->menu->menuKeyFor($request->path());

        if ($key === false) {
            abort(404);
        }

        /* null = หน้าที่ทุกคนที่ล็อกอินแล้วเข้าได้ ไม่ต้องตรวจสิทธิ์เมนู */
        if ($key === null) {
            return $next($request);
        }

        $allowed = collect(explode('|', $key))
            ->filter()
            ->contains(fn (string $one) => $request->user()->canAccessMenu($one));

        abort_unless($allowed, 403, 'ไม่มีสิทธิ์เข้าถึงหน้านี้');

        return $next($request);
    }
}
