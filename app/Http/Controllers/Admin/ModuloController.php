<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modulo;
use Illuminate\Contracts\View\View;

/**
 * Painel de módulos: mostra quais módulos estão ativos e inativos.
 * A ativação é feita APENAS direto no banco (tabela `modulos`, flag 1/0);
 * esta tela só lê e exibe o estado atual.
 */
class ModuloController extends Controller
{
    public function index(): View
    {
        $modulos = Modulo::query()
            ->where(fn ($q) => $q->where('loja_id', loja_atual_id())->orWhereNull('loja_id'))
            ->orderByRaw('loja_id IS NULL')
            ->orderBy('nome')
            ->get()
            ->keyBy('slug');

        return view('admin.modulos', [
            'modulos' => $modulos,
        ]);
    }
}