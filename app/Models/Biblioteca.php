<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Biblioteca extends Model
{
    // Tabla en singular (tu esquema)
    protected $table = 'biblioteca';

    protected $fillable = [
        'nombre', 'slug', 'dominio',
        'email_contacto', 'telefono', 'direccion',
        'logo_path', 'icon_path',
        'theme_json', 'theme_version', 'theme_name',
        'is_activa',
    ];

    protected $casts = [
        'theme_json' => 'array',
        'is_activa'  => 'boolean',
    ];

    protected $appends = ['logo_url', 'icon_url'];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::url($this->logo_path) : null;
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->icon_path ? Storage::url($this->icon_path) : null;
    }
}
