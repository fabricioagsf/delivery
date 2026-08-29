<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mesa;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MesaController extends Controller
{
    public function index(): View
    {
        $mesas = Mesa::query()->orderBy('id')->get();

        return view('admin.mesas', [
            'mesas' => $mesas,
        ]);
    }

    public function create(): View
    {
        return view('admin.mesas_form', ['mesa' => new Mesa]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $this->validar($request);

        Mesa::create($dados);

        return redirect()
            ->route('admin.mesas.index')
            ->with('sucesso_mesas', texto('admin_mesas', 'sucesso.criado', 'Mesa criada!'));
    }

    public function edit(Mesa $mesa): View
    {
        return view('admin.mesas_form', ['mesa' => $mesa]);
    }

    public function update(Request $request, Mesa $mesa): RedirectResponse
    {
        $dados = $this->validar($request, $mesa);

        $mesa->update($dados);

        return redirect()
            ->route('admin.mesas.index')
            ->with('sucesso_mesas', texto('admin_mesas', 'sucesso.atualizado', 'Mesa atualizada!'));
    }

    /**
     * Troca o status da mesa (ativa/inativa).
     */
    public function alternarStatus(Mesa $mesa): JsonResponse
    {
        $mesa->update(['ativo' => !$mesa->ativo]);

        return response()->json([
            'mensagem' => $mesa->ativo
                ? texto('admin_mesas', 'sucesso.ativa', 'Mesa ativada')
                : texto('admin_mesas', 'sucesso.inativa', 'Mesa inativada'),
            'ativo' => $mesa->ativo,
        ]);
    }

    protected function validar(Request $request, ?Mesa $mesa = null): array
    {
        $id = $mesa?->id;
        $lojaId = loja_atual_id();

        return $request->validate([
            'nome' => ['required', 'string', 'max:50'],
            'codigo' => [
                'nullable', 'string', 'max:10',
                $lojaId !== null
                    ? \Illuminate\Validation\Rule::unique('mesas', 'codigo')->where(fn ($q) => $q->where('loja_id', $lojaId))->ignore($id)
                    : 'unique:mesas,codigo'.($id ? ','.$id : ''),
            ],
            'capacidade' => ['required', 'integer', 'min:1', 'max:20'],
            'ativo' => ['boolean'],
        ], [
            'nome.required' => texto('admin_mesas', 'erro.nome_obrigatorio', 'Informe o nome da mesa.'),
            'codigo.unique' => texto('admin_mesas', 'erro.codigo_duplicado', 'Já existe uma mesa com esse código.'),
            'capacidade.min' => texto('admin_mesas', 'erro.capacidade_min', 'A capacidade deve ser de pelo menos 1 pessoa.'),
            'capacidade.max' => texto('admin_mesas', 'erro.capacidade_max', 'A capacidade máxima é de 20 pessoas.'),
        ], [
            'nome' => texto('admin_mesas', 'campo.nome', 'Nome'),
            'codigo' => texto('admin_mesas', 'campo.codigo', 'Código (opcional)'),
            'capacidade' => texto('admin_mesas', 'campo.capacidade', 'Capacidade'),
            'ativo' => texto('admin_mesas', 'campo.ativo', 'Situação'),
        ]);
    }
}