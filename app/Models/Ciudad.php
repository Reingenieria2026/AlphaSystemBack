<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ciudad extends Model
{
    use HasFactory;

    protected $table = 'ciudad';
    protected $primaryKey = 'idCiudad';

    protected $fillable = [
        'nombreCiudad',
        'idMunicipio'
    ];
    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'idMunicipio');
    }

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'idCiudad');
    }
}
