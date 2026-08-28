<?php

namespace App\Support;

class Temas
{
    /** Tema padrão (confeitaria de guloseimas). */
    public const PADRAO = 'guloseimas';

    /**
     * Registro de todos os temas.
     * `css` (relativo a public/) define o arquivo com a paleta; null = usa o base.
     */
    public static function todos(): array
    {
        return [
            'guloseimas' => ['nome' => 'Guloseimas', 'css' => null],
            'italiana'   => ['nome' => 'Italiana',   'css' => 'css/themes/italiana.css'],
            'japonesa'   => ['nome' => 'Japonesa',   'css' => 'css/themes/japonesa.css'],
            'chinesa'    => ['nome' => 'Chinesa',    'css' => 'css/themes/chinesa.css'],
            'mexicana'   => ['nome' => 'Mexicana',   'css' => 'css/themes/mexicana.css'],
        ];
    }

    /** Tema ativo (config `tema_loja`), sempre retorna um tema válido. */
    public static function ativo(): string
    {
        $tema = config_loja('tema_loja', static::PADRAO) ?? static::PADRAO;

        return array_key_exists($tema, static::todos()) ? $tema : static::PADRAO;
    }

    /** Nome de exibição de um tema (padrão: o ativo). */
    public static function nome(?string $tema = null): string
    {
        $tema = $tema ?? static::ativo();

        return static::todos()[$tema]['nome'] ?? 'Guloseimas';
    }

    /** Caminho (relativo a public/) da CSS de paleta de um tema (null = tema base, sem override). */
    public static function css(?string $tema = null): ?string
    {
        $tema = $tema ?? static::ativo();

        return static::todos()[$tema]['css'] ?? null;
    }

    /** Lista de temas para o seletor do admin (id => nome). */
    public static function opcoes(): array
    {
        return array_map(fn ($t) => $t['nome'], static::todos());
    }
}
