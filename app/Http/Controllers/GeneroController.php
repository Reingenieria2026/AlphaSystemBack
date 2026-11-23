<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Genero;

class GeneroController extends Controller
{
    public function index()
    {
        return response()->json(['estatus' => 1, 'data' => Genero::all()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombreGenero' => 'required|string|unique:genero,nombreGenero'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $genero = Genero::create($request->only('nombreGenero'));
        return response()->json(['estatus' => 1, 'mensaje' => 'Género creado', 'data' => $genero]);
    }

    public function show($id)
    {
        $genero = Genero::find($id);
        return $genero
            ? response()->json(['estatus' => 1, 'data' => $genero])
            : response()->json(['estatus' => 0, 'mensaje' => 'Género no encontrado']);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:genero,idGenero',
            'nombreGenero' => 'required|string|unique:genero,nombreGenero,' . $request->id . ',idGenero'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $genero = Genero::find($request->id);
        $genero->update($request->only('nombreGenero'));
        return response()->json(['estatus' => 1, 'mensaje' => 'Género actualizado']);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:genero,idGenero'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        Genero::destroy($request->id);
        return response()->json(['estatus' => 1, 'mensaje' => 'Género eliminado']);
    }
}
