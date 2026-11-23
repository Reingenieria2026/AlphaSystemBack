<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\EstadoUsuario;

class EstadoUsuarioController extends Controller
{
    public function index()
    {
        return response()->json(['estatus' => 1, 'data' => EstadoUsuario::all()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'estadoUsr' => 'required|string|unique:estado_usuario,estadoUsr'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $estado = EstadoUsuario::create($request->only('estadoUsr'));
        return response()->json(['estatus' => 1, 'mensaje' => 'Estado de usuario creado', 'data' => $estado]);
    }

    public function show($id)
    {
        $estado = EstadoUsuario::find($id);
        return $estado
            ? response()->json(['estatus' => 1, 'data' => $estado])
            : response()->json(['estatus' => 0, 'mensaje' => 'Estado de usuario no encontrado']);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:estado_usuario,idEstadoUsr',
            'estadoUsr' => 'required|string|unique:estado_usuario,estadoUsr,' . $request->id . ',idEstadoUsr'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $estado = EstadoUsuario::find($request->id);
        $estado->update($request->only('estadoUsr'));
        return response()->json(['estatus' => 1, 'mensaje' => 'Estado de usuario actualizado']);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:estado_usuario,idEstadoUsr'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        EstadoUsuario::destroy($request->id);
        return response()->json(['estatus' => 1, 'mensaje' => 'Estado de usuario eliminado']);
    }
}
