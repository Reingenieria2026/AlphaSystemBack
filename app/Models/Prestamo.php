<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prestamo extends Model
{
    use HasFactory;

    protected $table = 'prestamo';
    protected $primaryKey = 'idPrestamo';

    protected $fillable = [
        'idUsuario',
        'idLibro',
        'fechaPrestamo',
        'fechaEntrega',
        'multa',
        'devuelto',
    ];

    // Relación al usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'idUsuario', 'idUsr');
    }

    // Relación al libro
    public function libro()
    {
        return $this->belongsTo(Libro::class, 'idLibro', 'isbn');
    }
}
