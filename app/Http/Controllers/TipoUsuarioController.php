<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\TipoUsuario;

class TipoUsuarioController extends Controller
{
    public function index()
    {
        return response()->json(['estatus' => 1, 'data' => TipoUsuario::all()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombreTipo' => 'required|string|unique:tipo_usuario,nombreTipo',
            'descripcion' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $tipo = TipoUsuario::create($request->only(['nombreTipo', 'descripcion']));
        return response()->json(['estatus' => 1, 'mensaje' => 'Tipo de usuario creado', 'data' => $tipo]);
    }

    public function show($id)
    {
        $tipo = TipoUsuario::find($id);
        return $tipo
            ? response()->json(['estatus' => 1, 'data' => $tipo])
            : response()->json(['estatus' => 0, 'mensaje' => 'Tipo de usuario no encontrado']);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tipo_usuario,idTipoUsr',
            'nombreTipo' => 'required|string|unique:tipo_usuario,nombreTipo,' . $request->id . ',idTipoUsr',
            'descripcion' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $tipo = TipoUsuario::find($request->id);
        $tipo->update($request->only(['nombreTipo', 'descripcion']));
        return response()->json(['estatus' => 1, 'mensaje' => 'Tipo de usuario actualizado']);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tipo_usuario,idTipoUsr'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        TipoUsuario::destroy($request->id);
        return response()->json(['estatus' => 1, 'mensaje' => 'Tipo de usuario eliminado']);
    }
}
