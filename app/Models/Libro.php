<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Libro extends Model
{
    use HasFactory;

    protected $table = 'libro';
    protected $primaryKey = 'isbn';
    public $incrementing = false; 

    protected $fillable = [
        'isbn', 'titulo', 'fotoUsr', 'cantidad', 'anioPubli', 'edicion',
        'autor', 'editorial', 'idGenero', 'idEstadoLibro'
    ];

    public function genero()
    {
        return $this->belongsTo(Genero::class, 'idGenero');
    }

    public function estadoLibro()
    {
        return $this->belongsTo(EstadoLibro::class, 'idEstadoLibro');
    }

    public function prestamos()
    {
        return $this->hasMany(Prestamo::class, 'idLibro');
    }
}
