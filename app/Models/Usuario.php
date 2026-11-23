<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuario';
    protected $primaryKey = 'idUsr';

    protected $fillable = [
        'nombreUsr',
        'apellidoP',
        'apellidoM',
        'fotoUsr',
        'calle',
        'colonia',
        'idCiudad',
        'telefonoUsr',
        'correoUsr',
        'contrasena',
        'idTipoUsr',
        'idEstadoUsr',
        'intentos_fallidos',
    ];

    protected $hidden = [
        'contrasena'
    ];

    protected $casts = [
        'intentos_fallidos' => 'integer',
    ];

    protected $appends = ['fotoUrl'];

    public function ciudad()
    {
        return $this->belongsTo(Ciudad::class, 'idCiudad');
    }

    public function tipoUsuario()
    {
        return $this->belongsTo(TipoUsuario::class, 'idTipoUsr');
    }

    public function estadoUsuario()
    {
        return $this->belongsTo(EstadoUsuario::class, 'idEstadoUsr');
    }

    public function prestamos()
    {
        return $this->hasMany(Prestamo::class, 'idUsuario');
    }

    public function getFotoUrlAttribute()
    {
        if (empty($this->fotoUsr)) {
            // Devuelve null y el frontend pone placeholder
            // (si prefieres una imagen por defecto en backend, usa asset('images/default-avatar.png'))
            return null;
        }
        return Storage::disk('public')->url($this->fotoUsr);
        // Equivalente: asset('storage/'.$this->fotoUsr) si ya tienes el storage:link
    }

    // === agregado: indicar a Auth que tu campo de password es 'contrasena' ===
    public function getAuthPassword()
    {
        return $this->contrasena;
    }

    // === agregado (opcional recomendado): mutador para hashear automáticamente la contraseña ===
    public function setContrasenaAttribute($value)
    {
        if (is_null($value) || $value === '') {
            $this->attributes['contrasena'] = $value;
            return;
        }

        // Evita doble hash si ya viene hasheado
        $this->attributes['contrasena'] = Hash::needsRehash($value)
            ? Hash::make($value)
            : $value;
    }
}
