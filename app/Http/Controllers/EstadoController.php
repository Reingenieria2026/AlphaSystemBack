<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Estado;

class EstadoController extends Controller
{
    public function index()
    {
        return response()->json(['estatus' => 1, 'data' => Estado::all()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombreEstado' => 'required|string',
            'idPais' => 'required|exists:pais,idPais'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $estado = Estado::create($request->only('nombreEstado', 'idPais'));
        return response()->json(['estatus' => 1, 'mensaje' => 'Estado creado', 'data' => $estado]);
    }

    public function show($id)
    {
        $estado = Estado::find($id);
        return $estado
            ? response()->json(['estatus' => 1, 'data' => $estado])
            : response()->json(['estatus' => 0, 'mensaje' => 'Estado no encontrado']);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:estado,idEstado',
            'nombreEstado' => 'required|string',
            'idPais' => 'required|exists:pais,idPais'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $estado = Estado::find($request->id);
        $estado->update($request->only('nombreEstado', 'idPais'));
        return response()->json(['estatus' => 1, 'mensaje' => 'Estado actualizado']);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:estado,idEstado'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        Estado::destroy($request->id);
        return response()->json(['estatus' => 1, 'mensaje' => 'Estado eliminado']);
    }
}
