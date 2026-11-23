<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Prestamo;
use App\Models\Libro;
use App\Models\EstadoLibro;
use App\Models\Usuario;
use Carbon\Carbon;
//solo se hace un update pero no se guarda en la base de datos
class PrestamoController extends Controller
{
    public function index()
    {
        $data = Prestamo::with([
            'usuario:idUsr,nombreUsr,apellidoP,correoUsr,fotoUsr',
            'libro:isbn,titulo,fotoUsr'
        ])->get();

        foreach ($data as $p) {
            $p->multa = $this->calcularMulta($p);
        }

        return response()->json(['estatus' => 1, 'data' => $data]);
    }
public function show($id)
{
    $p = Prestamo::with(['usuario', 'libro'])->find($id);

    if (!$p) {
        return response()->json(['estatus' => 0, 'mensaje' => 'Préstamo no encontrado']);
    }

    $multa = $this->calcularMulta($p); // ✅ esto la calcula con fecha actual

    return response()->json([
        'estatus' => 1,
        'data' => [
            'idPrestamo'     => $p->idPrestamo,
            'usuario'        => $p->usuario,
            'libro'          => $p->libro,
            'fechaPrestamo'  => $p->fechaPrestamo,
            'fechaEntrega'   => $p->fechaEntrega,
            'devuelto'       => $p->devuelto,
            'multa'          => $multa // ✅ devuelve la multa real, aunque no esté guardada
        ]
    ]);
}




 public function show1($id)
{
    $p = Prestamo::with(['usuario', 'libro'])->find($id);
    if (!$p) {
        return response()->json(['estatus' => 0, 'mensaje' => 'Préstamo no encontrado']);
    }

    // Forzamos el recálculo de la multa
    $multa = $this->calcularMulta($p);
    

    return response()->json([
        'estatus' => 1,
        'data' => [
            'idPrestamo'     => $p->idPrestamo,
            'usuario'        => $p->usuario,
            'libro'          => $p->libro,
            'fechaPrestamo'  => $p->fechaPrestamo,
            'fechaEntrega'   => $p->fechaEntrega,
            'devuelto'       => $p->devuelto,
            'multa'          => $multa
        ]
    ]);
}


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idUsuario'     => 'required|exists:usuario,idUsr',
            'idLibro'       => 'required|exists:libro,isbn',
            'fechaPrestamo' => 'required|date',
            'fechaEntrega'  => 'required|date|after_or_equal:fechaPrestamo',
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $usuario = Usuario::find($request->idUsuario);
        if (!$usuario || $usuario->idEstadoUsr !== 1) {
            return response()->json(['estatus' => 0, 'mensaje' => 'Usuario no activo.'], 422);
        }

        $libro = Libro::find($request->idLibro);
        $idDisponible = EstadoLibro::where('estadoLibro', 'Disponible')->value('idEstadoU');

        if (!$libro || $libro->idEstadoLibro != $idDisponible || $libro->cantidad <= 0) {
            return response()->json(['estatus' => 0, 'mensaje' => 'Libro no disponible.'], 422);
        }

        if (Prestamo::where([
            ['idUsuario', $request->idUsuario],
            ['idLibro', $request->idLibro],
            ['devuelto', false]
        ])->exists()) {
            return response()->json(['estatus' => 0, 'mensaje' => 'Ya tiene este libro sin devolver.'], 422);
        }

        $prestamo = Prestamo::create([
            'idUsuario'     => $request->idUsuario,
            'idLibro'       => $request->idLibro,
            'fechaPrestamo' => $request->fechaPrestamo,
            'fechaEntrega'  => $request->fechaEntrega,
            'multa'         => 0,
            'devuelto'      => false,
        ]);

        $libro->decrement('cantidad');

        $nuevoEstado = $libro->cantidad > 0
            ? $idDisponible
            : EstadoLibro::where('estadoLibro', 'Prestado')->value('idEstadoU');

        $libro->update(['idEstadoLibro' => $nuevoEstado]);

        return response()->json([
            'estatus' => 1,
            'mensaje' => 'Préstamo registrado y stock actualizado',
            'data'    => $prestamo
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'       => 'required|exists:prestamo,idPrestamo',
            'multa'    => 'nullable|numeric|min:0',
            'devuelto' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        $p = Prestamo::find($request->id);
        $devolviendo = $request->filled('devuelto') && $request->devuelto && !$p->devuelto;

        if ($devolviendo) {
            $multa = $this->calcularMulta($p);
            if ($multa > 0) {
                return response()->json(['estatus' => 0, 'mensaje' => 'No se puede devolver con multa activa.']);
            }

            $p->multa = 0;
            $p->devuelto = true;
            $p->save();
            $this->incrementarStockYActualizarEstado($p->idLibro);
        } else {
            $p->update($request->only(['multa', 'devuelto']));
        }

        return response()->json(['estatus' => 1, 'mensaje' => 'Préstamo actualizado']);
    }

public function pagarMulta($id)
{
    $prestamo = Prestamo::find($id);

    if (!$prestamo) {
        return response()->json(['estatus' => 0, 'mensaje' => 'Préstamo no encontrado']);
    }

    if ($prestamo->devuelto) {
        return response()->json(['estatus' => 0, 'mensaje' => 'El préstamo ya fue devuelto.']);
    }

    // Recalcular y guardar la multa actual
    $this->calcularMulta($prestamo);

    if ($prestamo->multa <= 0) {
        return response()->json(['estatus' => 0, 'mensaje' => 'No hay multa pendiente.']);
    }

    // ✅ Pagar multa Y marcar como devuelto para evitar recálculo
    $prestamo->multa = 0;
    $prestamo->devuelto = true;
    $prestamo->save();

    $this->incrementarStockYActualizarEstado($prestamo->idLibro);

    return response()->json([
        'estatus' => 1,
        'mensaje' => 'Multa pagada y préstamo cerrado correctamente.',
        'data' => $prestamo
    ]);
}




public function pagarMulta1($id)
{
    $prestamo = Prestamo::find($id);

    if (!$prestamo) {
        return response()->json(['estatus' => 0, 'mensaje' => 'Préstamo no encontrado']);
    }

    // Revisar si el préstamo ya fue devuelto
    if ($prestamo->devuelto) {
        return response()->json(['estatus' => 0, 'mensaje' => 'El préstamo ya fue devuelto.'] );
    }

    // Revisamos si hay multa actualmente guardada
    if ($prestamo->multa <= 0) {
        // Recalcular por si no se ha actualizado correctamente
        $fechaEntrega = Carbon::parse($prestamo->fechaEntrega);
        $fechaDevolucion = now();

        $diasRetraso = $fechaDevolucion->diffInDays($fechaEntrega, false);
        $multa = max(0, $diasRetraso * 10);

        if ($multa <= 0) {
            return response()->json(['estatus' => 0, 'mensaje' => 'El préstamo no tiene multa.']);
        }

        // Asignamos y pagamos
        $prestamo->multa = 0;
        $prestamo->save();

        return response()->json(['estatus' => 1, 'mensaje' => 'Multa calculada y pagada correctamente.']);
    }

    // Si ya hay multa positiva guardada, se paga directamente
    $prestamo->multa = 0;
    $prestamo->save();

    return response()->json(['estatus' => 1, 'mensaje' => 'Multa pagada correctamente.']);
}



    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:prestamo,idPrestamo'
        ]);

        if ($validator->fails()) {
            return response()->json(['estatus' => 0, 'mensaje' => $validator->errors()]);
        }

        Prestamo::destroy($request->id);
        return response()->json(['estatus' => 1, 'mensaje' => 'Préstamo eliminado']);
    }

    /** UTILIDADES **/

/* private function calcularMulta(Prestamo $p)
{
    $fechaEntrega = Carbon::parse($p->fechaEntrega);
    $fechaDevolucion = $p->devuelto
        ? Carbon::parse($p->updated_at)
        : now();

    $diasRetraso = $fechaEntrega->diffInDays($fechaDevolucion, false); 

    $multa = max(0, $diasRetraso * 10);

    \Log::info("Calculando multa para préstamo {$p->idPrestamo}:");
    \Log::info("Fecha entrega: " . $fechaEntrega);
    \Log::info("Fecha devolución: " . $fechaDevolucion);
    \Log::info("Días de retraso: " . $diasRetraso);
    \Log::info("Multa calculada: $" . $multa);

    return $multa;
} */
private function calcularMulta(Prestamo $p)
{
    $fechaEntrega = Carbon::parse($p->fechaEntrega);
    $fechaDevolucion = $p->devuelto
        ? Carbon::parse($p->updated_at)
        : now();

    $diasRetraso = $fechaEntrega->diffInDays($fechaDevolucion, false);
    $multa = max(0, $diasRetraso * 10);

    if (!$p->devuelto) {
        $p->multa = $multa;
        $p->save(); // ✅ guarda en la base de datos
    }

    return $multa;
}



private function calcularMulta1(Prestamo $p)
{
    $fechaEntrega = Carbon::parse($p->fechaEntrega);
    $fechaDevolucion = $p->devuelto
        ? Carbon::parse($p->updated_at)
        : now();

    $diasRetraso = $fechaEntrega->diffInDays($fechaDevolucion, false);
    $multa = max(0, $diasRetraso * 10);

    // Guardar solo si no está devuelto
    if (!$p->devuelto) {
        $p->multa = $multa;
        $p->save();
    }

    return $multa;
}

   private function incrementarStockYActualizarEstado($idLibro)
    {
        $libro = Libro::find($idLibro);
        if (!$libro) return;

        $libro->increment('cantidad');

        if ($libro->cantidad > 0) {
            $idDisp = EstadoLibro::where('estadoLibro', 'Disponible')->value('idEstadoU');
            if ($idDisp) {
                $libro->update(['idEstadoLibro' => $idDisp]);
            }
        }
    } 

    public function metrics()
    {
        // 1. Total de usuarios registrados
        $totalUsuarios = Usuario::count();

        // 2. Total de libros disponibles (suma de stock)
        $totalLibrosDisponibles = Libro::sum('cantidad');

        // 3. Préstamos activos (no devueltos)
        $totalLibrosPrestados = Prestamo::where('devuelto', 0)->count();

        // 4. Deudas (préstamos con multa > 0)
        $totalDeudas = Prestamo::where('multa', '>', 0)->count();

        return response()->json([
            'estatus' => 1,
            'data' => compact(
                'totalUsuarios',
                'totalLibrosDisponibles',
                'totalLibrosPrestados',
                'totalDeudas'
            )
        ]);
    }

}
