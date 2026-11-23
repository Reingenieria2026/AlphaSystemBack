<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    use HasFactory;

    protected $table = 'municipio';
    protected $primaryKey = 'idMunicipio';

    protected $fillable = [
        'nombreMunicipio',
        'idEstado'
    ];

    public function estado()
    {
        return $this->belongsTo(Estado::class, 'idEstado');
    }

    public function ciudades()
    {
        return $this->hasMany(Ciudad::class, 'idMunicipio');
    }
}
