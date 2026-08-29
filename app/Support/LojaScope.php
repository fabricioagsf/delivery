<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Escopo de loja: filtra as queries do model pela loja ativa da sessão.
 * Aplicado via trait PossuiLoja. Use `semLoja()` para consultas
 * que precisam atravessar lojas (ex.: webhooks).
 */
class LojaScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $id = loja_atual_id();

        if ($id === null) {
            // Sem loja ativa, nenhum registro pode ser visto/alterado:
            // condição impossível impede vazar dados de todas as lojas.
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->getTable().'.loja_id', $id);
    }
}
