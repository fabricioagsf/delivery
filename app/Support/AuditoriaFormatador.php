<?php

namespace App\Support;

use App\Models\LogAuditoria;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Transforma os dados brutos do log de auditoria em texto claro para pessoas:
 * rótulos amigáveis por coluna, datas legíveis, moeda, Sim/Não, enums da
 * regra de negócio e frases prontas do tipo
 * "A categoria 'X' (id 8) foi excluída às 11:11 pelo banco."
 */
class AuditoriaFormatador
{
    /** @var array<string, string> */
    protected const ROTULOS = [
        'id' => 'ID',
        'nome' => 'Nome',
        'name' => 'Nome',
        'slug' => 'Identificador na URL',
        'descricao' => 'Descrição',
        'preco' => 'Preço',
        'imagem' => 'Imagem',
        'destaque' => 'Em destaque',
        'ativo' => 'Ativo na vitrine',
        'estoque' => 'Estoque',
        'estoque_minimo' => 'Estoque mínimo',
        'categoria_id' => 'Categoria',
        'produto_id' => 'Produto',
        'pedido_id' => 'Pedido',
        'cliente_id' => 'Cliente',
        'endereco_id' => 'Endereço usado',
        'cartao_id' => 'Cartão usado',
        'created_at' => 'Criado em',
        'updated_at' => 'Atualizado em',
        'email_verified_at' => 'E-mail verificado em',
        'remember_token' => 'Token de sessão',
        'codigo' => 'Código',
        'nome_cliente' => 'Nome do cliente',
        'telefone' => 'Telefone',
        'email' => 'E-mail',
        'tipo_entrega' => 'Tipo de entrega',
        'forma_pagamento' => 'Forma de pagamento',
        'troco_para' => 'Troco para',
        'subtotal' => 'Subtotal',
        'taxa_entrega' => 'Taxa de entrega',
        'total' => 'Total',
        'status' => 'Status',
        'observacoes' => 'Observações',
        'rua' => 'Rua',
        'numero' => 'Número',
        'complemento' => 'Complemento',
        'bairro' => 'Bairro',
        'cidade' => 'Cidade',
        'cep' => 'CEP',
        'principal' => 'Endereço principal',
        'apelido' => 'Apelido',
        'bandeira' => 'Bandeira',
        'numero_final' => 'Final do cartão',
        'validade' => 'Validade',
        'titular' => 'Titular',
        'pagina' => 'Página',
        'chave' => 'Chave',
        'valor' => 'Texto',
        'origem' => 'Origem',
        'acao' => 'Ação',
        'autor' => 'Autor',
        'ip' => 'IP',
        'url' => 'URL',
    ];

    public static function campo(string $coluna): string
    {
        return self::ROTULOS[$coluna] ?? ucfirst(str_replace('_', ' ', $coluna));
    }

    /**
     * Mapa: tabela => [substantivo com artigo, feminino?]
     */
    protected const ASSUNTOS = [
        'categorias' => ['a categoria', true],
        'produtos' => ['o produto', false],
        'pedidos' => ['o pedido', false],
        'clientes' => ['o cliente', false],
        'cartoes' => ['o cartão', false],
        'enderecos' => ['o endereço', false],
        'banners' => ['o banner', false],
        'textos' => ['o texto da interface', false],
        'configuracoes' => ['a configuração', true],
        'users' => ['o usuário administrador', false],
        'notas_fiscais' => ['a nota fiscal', true],
    ];

    /**
     * Frase pronta descrevendo o evento, ex.:
     * "A categoria 'Doces' (id 8) foi excluída às 11:11 pelo banco (gatilho)."
     */
    public static function frase(LogAuditoria $log): string
    {
        [$assunto, $feminino] = self::ASSUNTOS[$log->tabela]
            ?? ['o registro da tabela '.$log->tabela, false];

        $nome = self::nomeDoRegistro($log);
        $identificacao = $nome
            ? "'{$nome}' (id {$log->registro_id})"
            : "id {$log->registro_id}";

        $verbos = [
            'INSERT' => $feminino ? 'foi criada' : 'foi criado',
            'UPDATE' => $feminino ? 'foi alterada' : 'foi alterado',
            'DELETE' => $feminino ? 'foi excluída' : 'foi excluído',
        ];

        $verbo = $verbos[$log->acao] ?? 'foi modificado';

        $quando = $log->criado_em?->format('d/m/Y \à\s H:i') ?? 'em momento não registrado';

        $origem = $log->origem === 'gatilho'
            ? 'direto no banco'
            : ($log->autor && $log->autor !== 'sistema' ? "por {$log->autor}" : 'pelo sistema');

        return ucfirst($assunto)." {$identificacao} {$verbo} em {$quando} {$origem}.";
    }

    /**
     * Tenta extrair um nome legível do snapshot do evento.
     */
    protected static function nomeDoRegistro(LogAuditoria $log): ?string
    {
        $snapshot = $log->dados_novos ?? $log->dados_antigos ?? [];

        foreach (['nome', 'name', 'codigo', 'titulo', 'apelido', 'chave', 'email'] as $campo) {
            if (! empty($snapshot[$campo]) && is_scalar($snapshot[$campo])) {
                if ($campo === 'chave' && isset($snapshot['pagina'])) {
                    return $snapshot['pagina'].'.'.$snapshot[$campo];
                }

                return (string) $snapshot[$campo];
            }
        }

        return null;
    }

    public static function valor(string $coluna, mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '— vazio —';
        }

        // Relações: troca o número pelo nome real do registro relacionado
        if (str_ends_with($coluna, '_id') && ($resolvido = self::relacao($coluna, $valor)) !== null) {
            return "{$resolvido} ({$valor})";
        }

        // Enums da regra de negócio usam os mesmos textos da interface
        $rotulosEnum = [
            'tipo_entrega' => ['entrega' => 'Entrega', 'retirada' => 'Retirada'],
            'forma_pagamento' => ['pix' => 'Pix', 'cartao' => 'Cartão', 'dinheiro' => 'Dinheiro'],
            'origem' => ['gatilho' => 'Banco (gatilho)', 'aplicacao' => 'Aplicação'],
            'acao' => ['INSERT' => 'Criação', 'UPDATE' => 'Alteração', 'DELETE' => 'Exclusão'],
        ];

        if (isset($rotulosEnum[$coluna])) {
            return $rotulosEnum[$coluna][$valor] ?? (string) $valor;
        }

        if ($coluna === 'status') {
            return status_pedido((string) $valor);
        }

        // Booleanos guardados como 1/0
        if (in_array($coluna, ['ativo', 'destaque', 'principal'], true)) {
            return ((int) $valor) === 1 ? 'Sim' : 'Não';
        }

        // Datas e horários
        if (str_contains($coluna, '_at') || str_ends_with($coluna, '_em')) {
            try {
                return Carbon::parse($valor)->format('d/m/Y H:i');
            } catch (\Throwable) {
                return (string) $valor;
            }
        }

        // Valores monetários (pelo nome da coluna)
        if (preg_match('/(preco|total|subtotal|taxa|troco)/i', $coluna)) {
            return preco_br($valor);
        }

        // Listas vindas de JSON
        if (is_array($valor)) {
            return implode(', ', array_map(fn ($v) => (string) $v, $valor));
        }

        return (string) $valor;
    }

    /**
     * Resolve nomes reais para as relações mais comuns do sistema.
     */
    protected static function relacao(string $coluna, mixed $valor): ?string
    {
        $mapa = [
            'categoria_id' => ['categorias', 'nome'],
            'produto_id' => ['produtos', 'nome'],
            'cliente_id' => ['clientes', 'nome'],
            'pedido_id' => ['pedidos', 'codigo'],
        ];

        if (! isset($mapa[$coluna]) || ! is_numeric($valor)) {
            return null;
        }

        [$tabela, $colunaNome] = $mapa[$coluna];

        try {
            return DB::table($tabela)->where('id', $valor)->value($colunaNome);
        } catch (\Throwable) {
            return null;
        }
    }
}
