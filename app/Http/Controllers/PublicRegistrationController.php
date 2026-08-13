<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicRegistrationRequest;
use App\Models\Activity;
use App\Services\PublicRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicRegistrationController extends Controller
{
    public function checkPhone(Request $request, Activity $activity, PublicRegistrationService $service): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'regex:/^0[689]\d{8}$/'],
        ], [
            'phone.regex' => 'กรุณากรอกเบอร์โทรศัพท์มือถือ 10 หลัก',
        ]);

        if (! $this->canRegister($activity, $service)) {
            return response()->json([
                'message' => 'กิจกรรมนี้ยังไม่เปิดรับลงทะเบียนหรือปิดรับสมัครแล้ว',
            ], 409);
        }

        $duplicate = $service->alreadyRegistered($activity, $data['phone']);

        return response()->json([
            'available' => ! $duplicate,
            'message' => $duplicate
                ? 'เบอร์โทรศัพท์นี้ลงทะเบียนกิจกรรมนี้แล้ว'
                : 'เบอร์โทรศัพท์นี้ยังไม่ได้ลงทะเบียน สามารถดำเนินการต่อได้',
            'maxSeats' => $service->maxSeats($activity),
        ], $duplicate ? 409 : 200);
    }

    public function store(
        PublicRegistrationRequest $request,
        Activity $activity,
        PublicRegistrationService $service,
    ): JsonResponse {
        $registrations = $service->register($activity, $request->validated());

        return response()->json([
            'message' => 'ลงทะเบียนสำเร็จ '.$registrations->count().' ที่นั่ง',
            'registrationCodes' => $registrations->pluck('code'),
            'names' => $registrations->pluck('name'),
        ], 201);
    }

    private function canRegister(Activity $activity, PublicRegistrationService $service): bool
    {
        $activity->loadCount('registrations');

        return $activity->is_published
            && $activity->visibility === 'สาธารณะ'
            && $activity->acceptsRegistration()
            && $service->registrationForm($activity) !== null;
    }
}
