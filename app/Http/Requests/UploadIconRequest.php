<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadIconRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'icon' => [
                'required','image','mimes:png',
                'max:512', // 512 KB
                'dimensions:min_width=256,min_height=256,ratio=1/1'
            ],
        ];
    }
}
