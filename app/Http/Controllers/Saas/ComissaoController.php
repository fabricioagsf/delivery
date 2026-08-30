<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Models\Saas\Empresa;
use App\Models\Saas\Role;
use App\Models\Saas\Employee;
use App\Models\Saas\EmpresaConfig;
use App\Models\Pedido;
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

        $roles = Role::where('empresa_id', $empresa->id)->orderBy('nome')->get();
        $employees = Employee::where('empresa_id', $empresa->id)->orderBy('name')->get();

        $query = PedidoEmployee::query()
            ->whereHas('pedido', function ($q) use ($empresa, $inicio, $fim) {
                $q->whereHas('loja', function ($lq) use ($empresa) {
                    $lq->where('saas_empresa_id', $empresa->id);
                })
                ->whereDate('created_at', '>=', $inicio)
                ->whereDate('created_at', '<=', $fim);
            })
            ->with(['pedido', 'employee.roles']);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $registros = $query->orderByDesc('registrado_em')->get();

        $totalGeral = $registros->sum('comissao_valor');
        $totalPedidos = $registros->groupBy('pedido_id')->count();

        $porEmployee = $registros->groupBy('employee_id')->map(function ($rows) {
            return [
                'employee' => $rows->first()->employee,
                'pedidos' => $rows->count(),
                'total' => $rows->sum('comissao_valor'),
            ];
        })->sortByDesc('total');

        return view('saas.comissoes.index', [
            'empresa' => $empresa,
            'roles' => $roles,
            'employees' => $employees,
            'registros' => $registros,
            'porEmployee' => $porEmployee,
            'totalGeral' => $totalGeral,
            'totalPedidos' => $totalPedidos,
            'inicio' => $inicio,
            'fim' => $fim,
            'employeeId' => $employeeId,
        ]);
    }
}
