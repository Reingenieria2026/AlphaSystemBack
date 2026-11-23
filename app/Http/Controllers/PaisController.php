<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Pais;

class PaisController extends Controller
{
    public function index(Request $request)
    {
        $paises = Pais::all();
        return response()->json(['estatus' => 1, 'data' => $paises]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombrePais' => 'required|string|unique:pais,nombrePais'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $pais = Pais::create(['nombrePais' => $request->nombrePais]);

        return response()->json(['estatus' => 1, 'mensaje' => 'Pais registrado con exito', 'data' => $pais]);
    }

    public function show($id)
    {
        $pais = Pais::find($id);
        if (!$pais) {
            return response()->json(['estatus' => 0, 'mensaje' => 'Pais no encontrado']);
        }
        return response()->json(['estatus' => 1, 'data' => $pais]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:pais,idPais',
            'nombrePais' => 'required|string|unique:pais,nombrePais,' . $request->id . ',idPais'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $pais = Pais::find($request->id);
        $pais->update(['nombrePais' => $request->nombrePais]);

        return response()->json(['estatus' => 1, 'mensaje' => 'Pais actualizado con exito']);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:pais,idPais'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        Pais::destroy($request->id);

        return response()->json(['estatus' => 1, 'mensaje' => 'Pais eliminado con exito']);
    }
}
