<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Models\Modulo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class ModuloController extends Controller
{
    public function index(): View
    {
        $modulos = Modulo::whereNull('loja_id')
            ->orderBy('nome')
            ->get()
            ->keyBy('slug');

        return view('saas.modulos.index', [
            'modulos' => $modulos,
        ]);
    }

    public function toggle(string $slug): JsonResponse
    {
        $modulo = Modulo::where('slug', $slug)->whereNull('loja_id')->firstOrFail();

        $modulo->update(['ativo' => ! $modulo->ativo]);

        return response()->json([
            'ativo' => $modulo->ativo,
            'mensagem' => $modulo->ativo ? 'Módulo ativado.' : 'Módulo desativado.',
        ]);
    }
}
