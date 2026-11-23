<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genero extends Model
{
    use HasFactory;

    protected $table = 'genero';
    protected $primaryKey = 'idGenero';

    // Permitir asignación masiva de nombreGenero
    protected $fillable = [
        'nombreGenero',
    ];

    public function libros()
    {
        return $this->hasMany(Libro::class, 'idGenero');
    }
}
