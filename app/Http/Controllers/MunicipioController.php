<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Municipio;

class MunicipioController extends Controller
{
    public function index()
    {
        return response()->json(['estatus' => 1, 'data' => Municipio::all()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombreMunicipio' => 'required|string',
            'idEstado' => 'required|exists:estado,idEstado'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $municipio = Municipio::create($request->only('nombreMunicipio', 'idEstado'));
        return response()->json(['estatus' => 1, 'mensaje' => 'Municipio creado', 'data' => $municipio]);
    }

    public function show($id)
    {
        $municipio = Municipio::find($id);
        return $municipio
            ? response()->json(['estatus' => 1, 'data' => $municipio])
            : response()->json(['estatus' => 0, 'mensaje' => 'Municipio no encontrado']);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:municipio,idMunicipio',
            'nombreMunicipio' => 'required|string',
            'idEstado' => 'required|exists:estado,idEstado'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $municipio = Municipio::find($request->id);
        $municipio->update($request->only('nombreMunicipio', 'idEstado'));
        return response()->json(['estatus' => 1, 'mensaje' => 'Municipio actualizado']);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:municipio,idMunicipio'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        Municipio::destroy($request->id);
        return response()->json(['estatus' => 1, 'mensaje' => 'Municipio eliminado']);
    }
}
