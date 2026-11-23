<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBibliotecaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('biblioteca'); // según tu ruta

        return [
            'nombre' => ['sometimes','string','min:3','max:150', Rule::unique('biblioteca','nombre')->ignore($id)],
            'slug'   => ['sometimes','regex:/^[a-z0-9-]+$/','max:80', Rule::unique('biblioteca','slug')->ignore($id)],
            'dominio'=> ['nullable','string','max:190', Rule::unique('biblioteca','dominio')->ignore($id)],
            'email_contacto' => ['nullable','email','max:190'],
            'telefono' => ['nullable','string','max:50'],
            'direccion'=> ['nullable','string'],
            'theme_name' => ['nullable','string','max:80'],
            'is_activa'  => ['sometimes','boolean'],
        ];
    }
}
