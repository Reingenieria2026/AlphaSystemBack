<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    use HasFactory;

    protected $table = 'estado';
    protected $primaryKey = 'idEstado';

    protected $fillable = [
        'nombreEstado',
        'idPais'
    ];

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'idPais');
    }

    public function municipios()
    {
        return $this->hasMany(Municipio::class, 'idEstado');
    }
}
