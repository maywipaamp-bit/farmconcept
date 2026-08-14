<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class PublicQrController extends Controller
{
    public function registration(string $token): RedirectResponse|Response
    {
        return $this->scan($token, 'public', 'registration');
    }

    public function checkin(string $token): RedirectResponse|Response
    {
        return $this->scan($token, 'checkin', 'checkin');
    }

    public function postSurvey(string $token): RedirectResponse|Response
    {
        return $this->scan($token, 'post_survey', 'post-survey');
    }

    private function scan(string $token, string $purpose, string $action): RedirectResponse|Response
    {
        $qr = QrCode::with('activity')
            ->where('token', $token)
            ->where('purpose', $purpose)
            ->firstOrFail();

        if (! $qr->is_active || ($qr->expires_at && $qr->expires_at->isPast()) || ! $qr->activity) {
            return response()->view('public.qr-unavailable', [
                'activity' => $qr->activity,
            ], 410);
        }

        $qr->increment('scan_count');

        /* QR ลงทะเบียนพาเข้า flow ลงทะเบียนโดยตรง ไม่ต้องแวะหน้ารายละเอียดก่อน */
        if ($action === 'registration') {
            return redirect()->route('public.activities.register', [
                'activity' => $qr->activity->code,
                'qr' => $qr->token,
            ]);
        }

        return redirect()->route('public.activities.show', [
            'activity' => $qr->activity->code,
            'action' => $action,
            'qr' => $qr->token,
        ]);
    }
}
