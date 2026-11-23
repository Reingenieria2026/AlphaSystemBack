<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Ciudad;

class CiudadController extends Controller
{
    public function index()
    {
        return response()->json(['estatus' => 1, 'data' => Ciudad::all()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombreCiudad' => 'required|string',
            'idMunicipio' => 'required|exists:municipio,idMunicipio'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $ciudad = Ciudad::create($request->only('nombreCiudad', 'idMunicipio'));
        return response()->json(['estatus' => 1, 'mensaje' => 'Ciudad creada', 'data' => $ciudad]);
    }

    public function show($id)
    {
        $ciudad = Ciudad::find($id);
        return $ciudad
            ? response()->json(['estatus' => 1, 'data' => $ciudad])
            : response()->json(['estatus' => 0, 'mensaje' => 'Ciudad no encontrada']);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:ciudad,idCiudad',
            'nombreCiudad' => 'required|string',
            'idMunicipio' => 'required|exists:municipio,idMunicipio'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $ciudad = Ciudad::find($request->id);
        $ciudad->update($request->only('nombreCiudad', 'idMunicipio'));
        return response()->json(['estatus' => 1, 'mensaje' => 'Ciudad actualizada']);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:ciudad,idCiudad'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        Ciudad::destroy($request->id);
        return response()->json(['estatus' => 1, 'mensaje' => 'Ciudad eliminada']);
    }
}