<?php

namespace App\Support;

/**
 * Trait de multi-lojas: todo registro criado recebe automaticamente a loja
 * ativa (session) e as queries são filtradas para a loja corrente pelo
 * escopo global LojaScope. Para ler/gravar atravessando lojas (webhooks,
 * indicadores gerais), use o scope `semLoja()`.
 */
trait PossuiLoja
{
    protected static function bootPossuiLoja(): void
    {
        static::creating(function ($model) {
            if ($model->loja_id === null) {
                $model->loja_id = loja_atual_id();
            }
        });

        static::addGlobalScope(new LojaScope);
    }

    /** Query sem o filtro da loja ativa. */
    public function scopeSemLoja($query)
    {
        return $query->withoutGlobalScope(LojaScope::class);
    }
}
