<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\EstadoLibro;

class EstadoLibroController extends Controller
{
    public function index()
    {
        return response()->json(['estatus' => 1, 'data' => EstadoLibro::all()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'estado' => 'required|string|unique:estado_libro,estado'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $estado = EstadoLibro::create($request->only('estado'));
        return response()->json(['estatus' => 1, 'mensaje' => 'Estado de libro creado', 'data' => $estado]);
    }

    public function show($id)
    {
        $estado = EstadoLibro::find($id);
        return $estado
            ? response()->json(['estatus' => 1, 'data' => $estado])
            : response()->json(['estatus' => 0, 'mensaje' => 'Estado de libro no encontrado']);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:estado_libro,idEstadoU',
            'estado' => 'required|string|unique:estado_libro,estado,' . $request->id . ',idEstadoU'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $estado = EstadoLibro::find($request->id);
        $estado->update($request->only('estado'));
        return response()->json(['estatus' => 1, 'mensaje' => 'Estado de libro actualizado']);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:estado_libro,idEstadoU'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        EstadoLibro::destroy($request->id);
        return response()->json(['estatus' => 1, 'mensaje' => 'Estado de libro eliminado']);
    }
}
