<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * ค้นรายชื่อเพื่อเช็กอินด้วยเบอร์โทรศัพท์หรืออีเมล
 *
 * ช่องเดียวรับได้ทั้งสองแบบ เพราะหน้างานคนจำไม่ได้ว่าตอนลงทะเบียนกรอกอะไรไว้
 * การให้เลือกประเภทก่อนพิมพ์เป็นขั้นตอนที่เพิ่มมาโดยไม่ได้ช่วยอะไร
 */
class PublicCheckinLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $contact = trim((string) $this->input('contact'));

        /* ตัดอักขระคั่นออกเฉพาะกรณีที่พิมพ์มาเป็นตัวเลขล้วน (08x-xxx-xxxx, 08x xxx xxxx)
           ห้ามตัดกับอีเมล ไม่งั้น a@b.com จะกลายเป็นค่าว่าง */
        if ($contact !== '' && ! str_contains($contact, '@')) {
            $contact = preg_replace('/\D+/', '', $contact) ?? '';
        }

        $this->merge(['contact' => $contact]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'contact' => ['required', 'string', 'max:160'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $contact = (string) $this->input('contact');

            if ($contact === '' || $validator->errors()->has('contact')) {
                return;
            }

            $isPhone = (bool) preg_match('/^0[689]\d{8}$/', $contact);
            $isEmail = filter_var($contact, FILTER_VALIDATE_EMAIL) !== false;

            if (! $isPhone && ! $isEmail) {
                $validator->errors()->add('contact', 'กรุณากรอกเบอร์โทรศัพท์มือถือ 10 หลัก หรืออีเมลที่ใช้ลงทะเบียน');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'contact.required' => 'กรุณากรอกเบอร์โทรศัพท์หรืออีเมล',
        ];
    }
}
