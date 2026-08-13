<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivityQrController extends Controller
{
    use AuthorizesRequests;

    private const PURPOSES = ['public', 'checkin', 'post_survey'];

    /** สร้างภาพ PNG ที่สแกนได้จริงจาก URL และ token ที่บันทึกไว้ของกิจกรรม */
    public function show(Request $request, Activity $activity, string $purpose): Response
    {
        $this->authorize('update', $activity);
        abort_unless(in_array($purpose, self::PURPOSES, true), 404);

        $storedQr = $activity->qrCodes()->where('purpose', $purpose)->firstOrFail();
        $targetUrl = url($storedQr->target_url);

        $result = (new PngWriter)->write(new QrCode(
            data: $targetUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 512,
            margin: 20,
        ));

        $headers = [
            'Content-Type' => $result->getMimeType(),
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($request->boolean('download')) {
            $headers['Content-Disposition'] = sprintf(
                'attachment; filename="%s-%s.png"',
                strtolower($activity->code),
                str_replace('_', '-', $purpose),
            );
        }

        return response($result->getString(), 200, $headers);
    }
}
