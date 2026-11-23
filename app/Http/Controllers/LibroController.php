<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Libro;

class LibroController extends Controller
{
    public function index()
    {
        $books = Libro::query()
            ->orderBy('titulo', 'asc') // ascendente
            ->get();

        return response()->json(['estatus' => 1, 'data' => $books]);
    }

    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'isbn' => 'required|string|unique:libro,isbn',
        'titulo' => 'required|string',
        'cantidad' => 'required|integer|min:1',
        'anioPubli' => 'required|integer',
        'edicion' => 'nullable|string',
        'autor' => 'required|string',
        'editorial' => 'required|string',
        'idGenero' => 'required|exists:genero,idGenero',
        'idEstadoLibro' => 'required|exists:estado_libro,idEstadoU',
        'fotoUsr' => 'nullable|image|max:10240',
    ]);

    if ($validator->fails()) {
        return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
    }

    $data = $request->only([
        'isbn', 'titulo', 'cantidad', 'anioPubli', 'edicion', 'autor',
        'editorial', 'idGenero', 'idEstadoLibro'
    ]);

    if ($request->hasFile('fotoUsr')) {
        $ruta = $request->file('fotoUsr')->store('libros', 'public');
        $data['fotoUsr'] = $ruta;
    }

    $libro = \App\Models\Libro::create($data);

    return response()->json(['estatus' => 1, 'mensaje' => 'Libro creado', 'data' => $libro]);
}



    public function show($isbn)
    {
        $libro = Libro::find($isbn);
        return $libro
            ? response()->json(['estatus' => 1, 'data' => $libro])
            : response()->json(['estatus' => 0, 'mensaje' => 'Libro no encontrado']);
    }

    public function update(Request $request)
{
    $validator = Validator::make($request->all(), [
        'isbn' => 'required|exists:libro,isbn',
        'titulo' => 'required|string',
        'cantidad' => 'required|integer|min:1',
        'anioPubli' => 'required|integer',
        'edicion' => 'nullable|string',
        'autor' => 'required|string',
        'editorial' => 'required|string',
        'idGenero' => 'required|exists:genero,idGenero',
        'idEstadoLibro' => 'required|exists:estado_libro,idEstadoU',
        'fotoUsr' => 'nullable|image|max:10240',
    ]);

    if ($validator->fails()) {
        return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
    }

    $libro = \App\Models\Libro::findOrFail($request->isbn);
    $datos = $request->except(['isbn']);

    // Si se sube nueva imagen
    if ($request->hasFile('fotoUsr')) {
        // Borramos anterior
        if ($libro->fotoUsr && \Storage::disk('public')->exists($libro->fotoUsr)) {
            \Storage::disk('public')->delete($libro->fotoUsr);
        }
        // Guardamos la nueva
        $datos['fotoUsr'] = $request->file('fotoUsr')->store('libros', 'public');
    }
    // Si envían campo vacío (quieren borrar la imagen)
    elseif ($request->has('fotoUsr') && $request->input('fotoUsr') === '') {
        if ($libro->fotoUsr && \Storage::disk('public')->exists($libro->fotoUsr)) {
            \Storage::disk('public')->delete($libro->fotoUsr);
        }
        $datos['fotoUsr'] = null;
    }

    $libro->update($datos);

    return response()->json(['estatus' => 1, 'mensaje' => 'Libro actualizado', 'data' => $libro]);
}



    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'isbn' => 'required|exists:libro,isbn'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        Libro::destroy($request->isbn);
        return response()->json(['estatus' => 1, 'mensaje' => 'Libro eliminado']);
    }

    public function eliminarVirtualmente(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'isbn' => 'required|exists:libro,isbn'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $libro = Libro::find($request->isbn);
        $libro->idEstadoLibro = 6; 
        $libro->save();

        return response()->json(['estatus' => 1, 'mensaje' => 'Libro marcado como eliminado']);
    }

    public function restaurarLibro(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'isbn' => 'required|exists:libro,isbn'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $libro = Libro::find($request->isbn);
        $libro->idEstadoLibro = 1; // Estado 'Disponible'
        $libro->save();

        return response()->json(['estatus' => 1, 'mensaje' => 'Libro restaurado correctamente']);
    }

    public function indexTitles()
{
    $books = Libro::query()
        ->where('cantidad', '>', 0)
        ->whereHas('estadoLibro', function ($q) {
            $q->where('estadoLibro', 'Disponible'); // nombre exacto del estado
        })
        ->select('isbn', 'titulo')
        ->orderBy('titulo')
        ->get();

    return response()->json(['estatus' => 1, 'data' => $books]);
}


}
