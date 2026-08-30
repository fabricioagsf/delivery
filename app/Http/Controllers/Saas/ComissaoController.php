<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Models\Saas\Empresa;
use App\Models\Saas\Employee;
use App\Models\PedidoEmployee;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ComissaoController extends Controller
{
    public function index(Request $request, Empresa $empresa): View
    {
        abort_unless(saas_empresa_atual()?->id === $empresa->id, 403);

        $inicio = $request->query('inicio') ?: now()->startOfMonth()->format('Y-m-d');
        $fim = $request->query('fim') ?: now()->endOfMonth()->format('Y-m-d');
        $employeeId = $request->query('employee_id');

        $comissaoPercentual = (float) $empresa->configFloat('comissao_padrao', 0.0);

        $query = PedidoEmployee::query()
            ->whereHas('pedido', function ($q) use ($empresa, $inicio, $fim) {
                $q->where('saas_empresa_id', $empresa->id)
                  ->whereDate('created_at', '>=', $inicio)
                  ->whereDate('created_at', '<=', $fim);
            })
            ->with(['pedido', 'employee']);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $registros = $query->orderByDesc('registrado_em')->get();
        $employees = Employee::where('empresa_id', $empresa->id)->orderBy('name')->get();

        $porFuncionario = $registros->groupBy('employee_id')->map(function ($rows) {
            $pedidosIds = $rows->pluck('pedido_id')->unique();
            $totalVendido = \App\Models\Pedido::whereIn('id', $pedidosIds)->sum('total');

            return [
                'employee' => $rows->first()->employee,
                'pedidos_count' => $pedidosIds->count(),
                'total_vendido' => $totalVendido,
                'comissao' => $rows->sum('comissao_valor'),
            ];
        })->sortByDesc('comissao');

        $totalVendidoGeral = $porFuncionario->sum('total_vendido');
        $totalComissaoGeral = $porFuncionario->sum('comissao');

        return view('saas.comissoes.index', [
            'empresa' => $empresa,
            'employees' => $employees,
            'registros' => $registros,
            'porFuncionario' => $porFuncionario,
            'totalVendidoGeral' => $totalVendidoGeral,
            'totalComissaoGeral' => $totalComissaoGeral,
            'comissaoPercentual' => $comissaoPercentual,
            'inicio' => $inicio,
            'fim' => $fim,
            'employeeId' => $employeeId,
        ]);
    }
}
