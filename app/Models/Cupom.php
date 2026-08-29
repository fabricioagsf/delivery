<?php

namespace App\Models;

use App\Support\PossuiLoja;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cupom extends Model
{
    use PossuiLoja;

    protected $table = 'cupons';

    protected $fillable = [
        'loja_id',
        'codigo',
        'tipo',
        'valor',
        'valor_minimo',
        'limite_usos',
        'usos',
        'ativo',
        'inicio_em',
        'fim_em',
    ];

    protected $casts = [
        'valor' => 'float',
        'valor_minimo' => 'float',
        'limite_usos' => 'integer',
        'usos' => 'integer',
        'ativo' => 'boolean',
        'inicio_em' => 'datetime',
        'fim_em' => 'datetime',
    ];

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'cupom_id');
    }

    /**
     * Se o cupom ainda está vigente (não expirado e, se houver período, dentro dele).
     */
    public function vigente(?string $referencia = null): bool
    {
        $agora = $referencia ? now()->parse($referencia) : now();

        if ($this->inicio_em && $agora->lt($this->inicio_em)) {
            return false;
        }

        if ($this->fim_em && $agora->gt($this->fim_em)) {
            return false;
        }

        return true;
    }

    /**
     * Desconto permitido para um subtotal, respeitando o valor mínimo.
     * Percentual: percentual do subtotal; fixo: valor fixo (não pode passar do subtotal).
     * Para percentual não há teto além do próprio subtotal (100%).
     */
    public function desconto(float $subtotal): float
    {
        if ($this->valor_minimo !== null && $subtotal < $this->valor_minimo) {
            return 0.0;
        }

        if ($subtotal <= 0) {
            return 0.0;
        }

        $desconto = $this->tipo === 'percentual'
            ? ($subtotal * $this->valor / 100)
            : $this->valor;

        return (float) min(max($desconto, 0), $subtotal);
    }

    /**
     * Se o cupom ainda tem usos disponíveis.
     */
    public function temUsosDisponiveis(): bool
    {
        return $this->limite_usos === null || $this->usos < $this->limite_usos;
    }

    /**
     * Se o cupom pode ser aplicado a um subtotal (código válido, ativo, vigente,
     * com usos e atingindo o valor mínimo).
     */
    public function aplicavel(float $subtotal, ?string $referencia = null): bool
    {
        if (! $this->ativo || ! $this->vigente($referencia) || ! $this->temUsosDisponiveis()) {
            return false;
        }

        return $this->desconto($subtotal) > 0;
    }
}
