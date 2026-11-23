<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoLibro extends Model
{
    protected $table = 'estado_libro';

    protected $primaryKey = 'idEstadoU';

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = ['estadoLibro'];
}
