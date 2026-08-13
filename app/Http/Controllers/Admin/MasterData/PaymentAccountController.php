<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Models\PaymentAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentAccountController extends MasterDataController
{
    private const QR_MAX_KB = 5120;

    protected function model(): string
    {
        return PaymentAccount::class;
    }

    protected function view(): string
    {
        return 'admin.master.payment-accounts';
    }

    protected function label(): string
    {
        return 'ข้อมูลการรับชำระ';
    }

    protected function codePrefix(): string
    {
        return 'PAY';
    }

    protected function query()
    {
        return PaymentAccount::query()->with('updatedBy:id,name')->orderByDesc('id');
    }

    protected function viewData(): array
    {
        return [
            'banks' => config('farmconcept.banks'),
            'qrMaxBytes' => self::qrMaxBytes(),
        ];
    }

    protected function rules(?Model $current): array
    {
        return [
            'bankCode' => ['required', 'string', Rule::in(array_keys(config('farmconcept.banks')))],
            'accountNumber' => [
                'required', 'string', 'max:30', 'regex:/^[0-9\-\s]+$/',
                Rule::unique('mst_payment_accounts', 'account_number')
                    ->where(fn ($query) => $query->where('bank_code', request('bankCode')))
                    ->ignore($current?->id),
            ],
            'accountName' => ['required', 'string', 'max:150'],
            'active' => ['required', 'boolean'],
        ];
    }

    protected function attributes(): array
    {
        return [
            'bankCode' => 'ธนาคาร',
            'accountNumber' => 'เลขที่บัญชี',
            'accountName' => 'ชื่อบัญชี',
            'active' => 'สถานะเปิดรับชำระ',
        ];
    }

    protected function validated(Request $request, ?Model $current): array
    {
        /* ตัดช่องว่างก่อนตรวจ unique ให้ค่าที่เห็นต่างกันแค่การเว้นวรรคเป็นบัญชีเดียวกัน */
        $request->merge([
            'accountNumber' => preg_replace('/\s+/', '', (string) $request->input('accountNumber')),
        ]);

        return parent::validated($request, $current);
    }

    protected function columns(array $data): array
    {
        /* สถานะจริงเขียนใน syncRelations หลังปิดรายการเดิมแล้ว เพื่อไม่ชน unique active_slot */
        return [
            'bank_code' => $data['bankCode'],
            'account_number' => preg_replace('/\s+/', '', $data['accountNumber']),
            'account_name' => $data['accountName'],
            'is_active' => false,
            'active_slot' => null,
            'updated_by' => auth()->id(),
        ];
    }

    protected function syncRelations(Model $record, array $data): void
    {
        if (! $data['active']) {
            return;
        }

        /* ล็อกบัญชีอื่นก่อนปิด เพื่อกันคำขอสองคนเปิดคนละบัญชีในเวลาเดียวกัน */
        PaymentAccount::query()
            ->whereKeyNot($record->getKey())
            ->lockForUpdate()
            ->get();

        PaymentAccount::query()
            ->whereKeyNot($record->getKey())
            ->where('is_active', true)
            ->update(['is_active' => false, 'active_slot' => null]);

        $record->forceFill(['is_active' => true, 'active_slot' => 1])->save();
    }

    protected function toRow(Model $record): array
    {
        return [
            'id' => $record->code,
            'bankCode' => $record->bank_code,
            'bankName' => config('farmconcept.banks.'.$record->bank_code, $record->bank_code),
            'accountNumber' => $record->account_number,
            'accountName' => $record->account_name,
            'qrCode' => $this->qrUrl($record->qr_code_path),
            'active' => $record->is_active,
            'deleteUsageCount' => 0,
            'updatedBy' => $record->updatedBy?->name,
            'updatedAt' => $record->updated_at?->toDateString(),
            'updatedTime' => $record->updated_at?->format('H.i'),
        ];
    }

    public function uploadQrCode(Request $request, string $code): JsonResponse
    {
        /** @var PaymentAccount $account */
        $account = $this->find($code);

        $request->validate(
            ['qrCode' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::QR_MAX_KB]],
            [],
            ['qrCode' => 'ภาพ QR Code']
        );

        $file = $request->file('qrCode');
        if (! $file || ! $file->isValid()) {
            return response()->json([
                'message' => 'ไฟล์ภาพ QR Code ไม่ถูกต้อง หรือมีขนาดใหญ่เกินกำหนด',
                'errors' => ['qrCode' => ['ไม่สามารถอ่านไฟล์ภาพ QR Code ได้ กรุณาลองใหม่อีกครั้ง']],
            ], 422);
        }

        $newPath = $this->storeQrFile($file);
        if (! $newPath) {
            return response()->json(['message' => 'ไม่สามารถบันทึกภาพ QR Code ได้'], 500);
        }

        $oldPath = $account->qr_code_path;
        $account->forceFill(['qr_code_path' => $newPath, 'updated_by' => auth()->id()])->save();
        $this->deleteQrFile($oldPath);

        return response()->json([
            'message' => 'บันทึกภาพ QR Code แล้ว',
            'url' => $this->qrUrl($newPath),
        ]);
    }

    public function deleteQrCode(string $code): JsonResponse
    {
        /** @var PaymentAccount $account */
        $account = $this->find($code);
        $oldPath = $account->qr_code_path;

        $account->forceFill(['qr_code_path' => null, 'updated_by' => auth()->id()])->save();
        $this->deleteQrFile($oldPath);

        return response()->json(['message' => 'ลบภาพ QR Code แล้ว']);
    }

    public function destroy(string $code): JsonResponse
    {
        $qrPath = null;
        $blocked = null;

        DB::transaction(function () use ($code, &$qrPath, &$blocked): void {
            /** @var PaymentAccount $record */
            $record = PaymentAccount::where('code', $code)->lockForUpdate()->firstOrFail();

            if ($reason = $this->blockedFromDelete($record)) {
                $blocked = $reason;

                return;
            }

            $qrPath = $record->qr_code_path;
            $this->log($record, 'deleted', 'ลบ');
            $record->delete();
        });

        if ($blocked !== null) {
            return response()->json(['message' => $blocked], 403);
        }

        $this->deleteQrFile($qrPath);

        return response()->json(['message' => 'ลบข้อมูลการรับชำระแล้ว']);
    }

    private function qrUrl(?string $path): string
    {
        if (! $path) {
            return '';
        }

        if (Str::startsWith($path, ['http://', 'https://', '/storage/'])) {
            return $path;
        }

        /* ใช้ URL แบบอ้างอิงจาก host ปัจจุบัน เพราะเครื่องพัฒนาอาจเปิดด้วย
           127.0.0.1:8000 แต่ APP_URL เป็น localhost ทำให้ภาพไปผิด host/port */
        return '/storage/'.ltrim(Str::after($path, 'storage/'), '/');
    }

    private function storeQrFile(UploadedFile $file): ?string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'png');
        $path = 'payment-qr-codes/'.Str::random(40).'.'.$extension;
        $temporaryPath = $file->getRealPath() ?: $file->getPathname();

        if ($temporaryPath && file_exists($temporaryPath)) {
            $contents = @file_get_contents($temporaryPath);
            if ($contents !== false && Storage::disk('public')->put($path, $contents)) {
                return $path;
            }
        }

        return $file->store('payment-qr-codes', 'public') ?: null;
    }

    private function deleteQrFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        $path = ltrim(Str::after($path, 'storage/'), '/');
        if (Str::startsWith($path, 'payment-qr-codes/') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public static function qrMaxBytes(): int
    {
        $toBytes = function (string $value): int {
            $unit = strtolower(substr(trim($value), -1));
            $number = (int) $value;

            return match ($unit) {
                'g' => $number * 1024 ** 3,
                'm' => $number * 1024 ** 2,
                'k' => $number * 1024,
                default => $number,
            };
        };

        $phpLimit = min($toBytes((string) ini_get('upload_max_filesize')), $toBytes((string) ini_get('post_max_size')));

        return min(self::QR_MAX_KB * 1024, $phpLimit ?: self::QR_MAX_KB * 1024);
    }
}
