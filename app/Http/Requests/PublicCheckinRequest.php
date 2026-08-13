<?php

namespace App\Http\Requests;

class PublicCheckinRequest extends PublicCheckinLookupRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return parent::rules() + [
            'registration_code' => ['required', 'string', 'max:24'],
        ];
    }
}
