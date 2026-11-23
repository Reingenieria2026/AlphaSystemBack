<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoUsuario extends Model
{
    use HasFactory;

    protected $table = 'estado_usuario';
    protected $primaryKey = 'idEstadoUsr';
    protected $fillable = [
        'estadoUsr'
    ];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'idEstadoUsr');
    }
}
