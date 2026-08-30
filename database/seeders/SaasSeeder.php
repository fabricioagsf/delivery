<?php

namespace Database\Seeders;

use App\Models\Saas\Empresa;
use App\Models\Saas\Filial;
use App\Models\Saas\Employee;
use App\Models\Saas\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SaasSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::firstOrCreate(
            ['slug' => 'gostosuras'],
            [
                'nome' => 'Guloseimas Doces Artesanais',
                'slug' => 'gostosuras',
                'cnpj' => '12.345.678/0001-90',
                'ativo' => true,
            ]
        );

        Role::firstOrCreate(
            ['empresa_id' => $empresa->id, 'nome' => 'super_admin'],
            [
                'descricao' => 'Acesso total a todas as filiais e funcionalidades',
                'permissions' => ['*'],
            ]
        );

        Role::firstOrCreate(
            ['empresa_id' => $empresa->id, 'nome' => 'gerente'],
            [
                'descricao' => 'Gerencia filiais, produtos e pedidos',
                'permissions' => [
                    'filiais.view', 'filiais.editar',
                    'produtos.view', 'produtos.criar', 'produtos.editar',
                    'pedidos.view', 'pedidos.editar',
                    'modulos.view',
                ],
            ]
        );

        Role::firstOrCreate(
            ['empresa_id' => $empresa->id, 'nome' => 'atendente'],
            [
                'descricao' => 'Atendimento e pedidos',
                'permissions' => [
                    'pedidos.view', 'pedidos.criar',
                    'caixa.view',
                ],
            ]
        );

        Role::firstOrCreate(
            ['empresa_id' => $empresa->id, 'nome' => 'garcom'],
            [
                'descricao' => 'Garçom — registra pedidos na mesa',
                'permissions' => [
                    'pedidos.view', 'pedidos.criar', 'pedidos.entregar',
                    'caixa.view',
                ],
            ]
        );

        $superAdmin = Employee::firstOrCreate(
            ['email' => 'admin@gostosuras.local'],
            [
                'empresa_id' => $empresa->id,
                'name' => 'Administrador Guloseimas',
                'email' => 'admin@gostosuras.local',
                'password' => Hash::make('12345678'),
                'cargo' => 'Administrador',
                'ativo' => true,
            ]
        );

        $superRole = Role::where('empresa_id', $empresa->id)->where('nome', 'super_admin')->first();
        if ($superRole) {
            $superAdmin->roles()->syncWithoutDetaching([$superRole->id => ['filial_id' => null]]);
        }

        foreach ($empresa->filiais as $filial) {
            $superAdmin->filiais()->syncWithoutDetaching([$filial->id]);
        }
    }
}
