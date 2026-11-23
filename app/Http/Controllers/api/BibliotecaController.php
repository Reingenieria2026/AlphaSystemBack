<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBibliotecaRequest;
use App\Http\Requests\UpdateBibliotecaRequest;
use App\Http\Requests\UpdateTemaRequest;
use App\Http\Requests\UploadLogoRequest;
use App\Http\Requests\UploadIconRequest;
use App\Models\Biblioteca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BibliotecaController extends Controller
{
    // GET /bibliotecas
    public function index(Request $request)
    {
        $q = Biblioteca::query();

        if ($request->filled('search')) {
            $s = $request->string('search')->toString();
            $q->where(function($x) use ($s) {
                $x->where('nombre','like',"%$s%")
                  ->orWhere('slug','like',"%$s%")
                  ->orWhere('dominio','like',"%$s%");
            });
        }

        return response()->json([
            'estatus' => 1,
            'data' => $q->orderBy('nombre')->paginate(20)
        ]);
    }

    // GET /bibliotecas/{id}
    public function show($id)
    {
        $b = Biblioteca::findOrFail($id);
        return response()->json(['estatus'=>1, 'data'=>$b]);
    }

    // GET /bibliotecas/slug/{slug}  (para frontend bootstrap)
    public function showBySlug($slug)
    {
        $b = Biblioteca::where('slug', $slug)->firstOrFail();
        return response()->json(['estatus'=>1, 'data'=>$b]);
    }

    // POST /bibliotecas
    public function store(StoreBibliotecaRequest $request)
    {
        $data = $request->validated();

        // Si no viene slug, generarlo del nombre
        if (empty($data['slug']) && !empty($data['nombre'])) {
            $data['slug'] = Str::slug($data['nombre']);
        }

        $b = Biblioteca::create($data);

        return response()->json(['estatus'=>1, 'data'=>$b], 201);
    }

    // PUT /bibliotecas/{id}
    public function update(UpdateBibliotecaRequest $request, $id)
    {
        $b = Biblioteca::findOrFail($id);
        $b->fill($request->validated())->save();

        return response()->json(['estatus'=>1, 'data'=>$b]);
    }

    // DELETE /bibliotecas/{id}
    public function destroy($id)
    {
        $b = Biblioteca::findOrFail($id);

        // si quieres soft deletes, cambia a $b->delete(); y agrega SoftDeletes
        $this->deleteAssetIfExists($b->logo_path);
        $this->deleteAssetIfExists($b->icon_path);

        $b->delete();

        return response()->json(['estatus'=>1, 'msg'=>'Biblioteca eliminada']);
    }

    // PUT /bibliotecas/{id}/tema
    public function updateTema(UpdateTemaRequest $request, $id)
    {
        $b = Biblioteca::findOrFail($id);
        $b->theme_json = $request->validated()['theme_json'];
        $b->theme_version = ((int)$b->theme_version) + 1;
        $b->save();

        return response()->json(['estatus'=>1, 'data'=>$b]);
    }

    // POST /bibliotecas/{id}/logo
    public function uploadLogo(UploadLogoRequest $request, $id)
    {
        $b = Biblioteca::findOrFail($id);
        $file = $request->file('logo');

        $path = "bibliotecas/{$b->id}/logo.png";
        // Borra anterior si existe
        $this->deleteAssetIfExists($b->logo_path);

        $file->storeAs("bibliotecas/{$b->id}", 'logo.png', 'public');
        $b->logo_path = $path;
        $b->save();

        return response()->json(['estatus'=>1, 'logo_url' => $b->logo_url]);
    }

    // POST /bibliotecas/{id}/icon
    public function uploadIcon(UploadIconRequest $request, $id)
    {
        $b = Biblioteca::findOrFail($id);
        $file = $request->file('icon');

        $path = "bibliotecas/{$b->id}/icon.png";
        $this->deleteAssetIfExists($b->icon_path);

        $file->storeAs("bibliotecas/{$b->id}", 'icon.png', 'public');
        $b->icon_path = $path;
        $b->save();

        return response()->json(['estatus'=>1, 'icon_url' => $b->icon_url]);
    }

    private function deleteAssetIfExists(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
