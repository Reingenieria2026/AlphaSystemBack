<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadLogoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'logo' => [
                'required','image','mimes:png',
                'max:1024', // 1MB
                'dimensions:min_width=300' // recomendado horizontal
            ],
        ];
    }
}
