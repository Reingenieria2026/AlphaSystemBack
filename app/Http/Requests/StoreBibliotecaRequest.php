<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBibliotecaRequest extends FormRequest
{
    public function authorize(): bool { return true; } // Ajusta con policies si quieres

    public function rules(): array
    {
        return [
            'nombre' => ['required','string','min:3','max:150','unique:biblioteca,nombre'],
            'slug'   => ['required','regex:/^[a-z0-9-]+$/','max:80','unique:biblioteca,slug'],
            'dominio'=> ['nullable','string','max:190','unique:biblioteca,dominio'],
            'email_contacto' => ['nullable','email','max:190'],
            'telefono' => ['nullable','string','max:50'],
            'direccion'=> ['nullable','string'],
            'theme_name' => ['nullable','string','max:80'],
            'theme_json' => ['nullable','array'],
            'is_activa'  => ['boolean'],
        ];
    }
}
