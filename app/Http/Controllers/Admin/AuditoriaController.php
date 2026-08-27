<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAuditoria;
use App\Services\Auditoria;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AuditoriaController extends Controller
{
    public function __construct(protected Auditoria $auditoria) {}

    public function index(Request $request): View
    {
        $filtros = $request->only(['tabela', 'acao', 'origem', 'registro']);

        return view('admin.auditoria', [
            'eventos' => $this->auditoria->listar($filtros),
            'filtros' => $filtros,
            'tabelas' => LogAuditoria::query()
                ->select('tabela')
                ->distinct()
                ->orderBy('tabela')
                ->pluck('tabela'),
        ]);
    }

    public function show(LogAuditoria $log): View
    {
        return view('admin.auditoria_detalhe', ['log' => $log]);
    }

    public function restaurar(Request $request, LogAuditoria $log): RedirectResponse
    {
        $dados = $request->validate([
            'senha_master' => ['required', 'string'],
        ], [
            'required' => texto('admin_auditoria', 'erro.senha_obrigatoria', 'Informe a senha master.'),
        ]);

        try {
            $mensagem = $this->auditoria->restaurar($log, $dados['senha_master']);
        } catch (InvalidArgumentException $erro) {
            return back()->withErrors(['senha_master' => $erro->getMessage()]);
        }

        return redirect()
            ->route('admin.auditoria.index')
            ->with('sucesso_auditoria', $mensagem);
    }
}
