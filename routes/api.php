<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    PaisController,
    EstadoController,
    MunicipioController,
    CiudadController,
    TipoUsuarioController,
    EstadoUsuarioController,
    UsuarioController,
    EstadoLibroController,
    GeneroController,
    LibroController,
    PrestamoController
};
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\api\SinopsisController;
use App\Http\Controllers\Api\LibroInfoController;
use App\Http\Controllers\api\LibroRecomendacionController;
use App\Http\Middleware\EnsureRecoClientId;
use Illuminate\Http\Request;               
use Illuminate\Support\Facades\Auth; 
use App\Http\Controllers\Api\BibliotecaController;

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);

Route::middleware([ EnsureRecoClientId::class ])->group(function () {
    Route::post   ('/libros/recomendar',            [LibroRecomendacionController::class, 'recomendar']);
    // Route::get    ('/libros/recomendar/historial',  [LibroRecomendacionController::class, 'historial']);
    // Route::delete ('/libros/recomendar/historial',  [LibroRecomendacionController::class, 'limpiarHistorial']);
    Route::post   ('/libros/by-isbns',              [LibroRecomendacionController::class, 'byIsbns']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get   ('/libros/recomendar/historial',  [LibroRecomendacionController::class, 'historial']);
        Route::delete('/libros/recomendar/historial',  [LibroRecomendacionController::class, 'limpiarHistorial']);
    });
});

Route::post('/gemini-sinopsis', [GeminiController::class, 'generarSinopsis']);

Route::get('/usuarios/nombres', [UsuarioController::class, 'indexNames']);
Route::get('/libros/titulos',  [LibroController::class,  'indexTitles']);

Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);
Route::post('/usuarios', [UsuarioController::class, 'store']);
Route::put('/usuarios/{id}', [UsuarioController::class, 'update']);
Route::delete('/usuarios', [UsuarioController::class, 'destroy']);

Route::get('/usuarios', [UsuarioController::class, 'index']);
Route::get('usuarios/correo/{correo}', [UsuarioController::class, 'getByCorreo']);


Route::get('/ciudades', [CiudadController::class, 'index']);
    Route::get('/ciudades/{id}', [CiudadController::class, 'show']);
    Route::post('/ciudades', [CiudadController::class, 'store']);
    Route::put('/ciudades/{id}', [CiudadController::class, 'update']);
    Route::delete('/ciudades/{id}', [CiudadController::class, 'destroy']);

        Route::put('/libros/restaurar', [LibroController::class, 'restaurarLibro']);
        Route::put('/libros/eliminar-virtual', [LibroController::class, 'eliminarVirtualmente']);
    Route::get('/libros', [LibroController::class, 'index']);
    Route::get('/libros/{isbn}', [LibroController::class, 'show']);
    Route::post('/libros', [LibroController::class, 'store']);
Route::put('/libros/{isbn}', [LibroController::class, 'update']); 
    Route::delete('/libros', [LibroController::class, 'destroy']);
    Route::post('/generar-sinopsis', [SinopsisController::class, 'generar']);
    Route::get('/prestamos/metrics', [PrestamoController::class, 'metrics']);

    
    
Route::get('/libro/info', [LibroInfoController::class, 'obtenerInfo']);
Route::get('libro/info-completo', [LibroInfoController::class, 'infoCompleto']);

    
    Route::get('/prestamos', [PrestamoController::class, 'index']);
    Route::post('/prestamos', [PrestamoController::class, 'store']);
    Route::put('/prestamos', [PrestamoController::class, 'update']);
    Route::delete('/prestamos', [PrestamoController::class, 'destroy']);
    Route::get('/prestamos/{id}', [PrestamoController::class, 'show']);
Route::put('/prestamo/devolver/{id}', [PrestamoController::class, 'marcarDevuelto']);
Route::put('/prestamo/{id}/pagar-multa', [PrestamoController::class, 'pagarMulta']);

    // Catálogos auxiliares
    Route::get('/paises', [PaisController::class, 'index']);
    Route::get('/paises/{id}', [PaisController::class, 'show']);
    Route::post('/paises', [PaisController::class, 'store']);
    Route::put('/paises/{id}', [PaisController::class, 'update']);
    Route::delete('/paises/{id}', [PaisController::class, 'destroy']);

    Route::get('/estados', [EstadoController::class, 'index']);
    Route::get('/estados/{id}', [EstadoController::class, 'show']);
    Route::post('/estados', [EstadoController::class, 'store']);
    Route::put('/estados/{id}', [EstadoController::class, 'update']);
    Route::delete('/estados/{id}', [EstadoController::class, 'destroy']);

    Route::get('/municipios', [MunicipioController::class, 'index']);
    Route::get('/municipios/{id}', [MunicipioController::class, 'show']);
    Route::post('/municipios', [MunicipioController::class, 'store']);
    Route::put('/municipios/{id}', [MunicipioController::class, 'update']);
    Route::delete('/municipios/{id}', [MunicipioController::class, 'destroy']);

    

    Route::get('/generos', [GeneroController::class, 'index']);
    Route::get('/generos/{id}', [GeneroController::class, 'show']);
    Route::post('/generos', [GeneroController::class, 'store']);
    Route::put('/generos/{id}', [GeneroController::class, 'update']);
    Route::delete('/generos/{id}', [GeneroController::class, 'destroy']);

    Route::get('/estados-usuario', [EstadoUsuarioController::class, 'index']);
    Route::post('/estados-usuario', [EstadoUsuarioController::class, 'store']);
    Route::put('/estados-usuario/{id}', [EstadoUsuarioController::class, 'update']);
    Route::delete('/estados-usuario/{id}', [EstadoUsuarioController::class, 'destroy']);

    Route::get('/tipos-usuario', [TipoUsuarioController::class, 'index']);
    Route::post('/tipos-usuario', [TipoUsuarioController::class, 'store']);
    Route::put('/tipos-usuario/{id}', [TipoUsuarioController::class, 'update']);
    Route::delete('/tipos-usuario/{id}', [TipoUsuarioController::class, 'destroy']);

    Route::get('/estados-libro', [EstadoLibroController::class, 'index']);
    Route::post('/estados-libro', [EstadoLibroController::class, 'store']);
    Route::put('/estados-libro/{id}', [EstadoLibroController::class, 'update']);
    Route::delete('/estados-libro/{id}', [EstadoLibroController::class, 'destroy']);

    Route::middleware('auth:sanctum')->get('/debug-user', function(Request $request) {
    return response()->json([
        'user'  => $request->user(),        
        'check' => Auth::check()
    ]);
});

Route::prefix('bibliotecas')->group(function () {
    Route::get('/', [BibliotecaController::class, 'index']);
    Route::get('/{id}', [BibliotecaController::class, 'show'])->whereNumber('id');
    Route::get('/slug/{slug}', [BibliotecaController::class, 'showBySlug']);
Route::post('/{id}/logo', [BibliotecaController::class, 'uploadLogo'])->whereNumber('id');
        Route::post('/{id}/icon', [BibliotecaController::class, 'uploadIcon'])->whereNumber('id');
    // Protege estas con auth:sanctum si deseas (recomendado):
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [BibliotecaController::class, 'store']);
        Route::put('/{id}', [BibliotecaController::class, 'update'])->whereNumber('id');
        Route::delete('/{id}', [BibliotecaController::class, 'destroy'])->whereNumber('id');

        Route::put('/{id}/tema', [BibliotecaController::class, 'updateTema'])->whereNumber('id');
        
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // ➕ Reactivar cuenta (solo bibliotecario/admin según lógica del controlador)
    Route::put('/usuario/{id}/reactivar', [AuthController::class, 'reactivarCuenta'])->whereNumber('id');
});