<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class HelpController extends Controller
{
    /**
     * Exibe o help do sistema no painel (mesma fonte: docs/HELP.md),
     * convertido para HTML por um mini-parser interno (sem dependências externas).
     */
    public function index(): View
    {
        $caminho = base_path('docs/HELP.md');
        $markdown = file_exists($caminho) ? (string) file_get_contents($caminho) : '';

        return view('admin.help', [
            'html' => $this->paraHtml($markdown),
        ]);
    }

    /**
     * Converte um subconjunto de Markdown (usado no HELP.md) para HTML:
     * cabeçalhos, listas, código, citação, tabelas, negrito, itálico e links.
     */
    protected function paraHtml(string $texto): string
    {
        $linhas = preg_split('/\r\n|\r|\n/', $texto) ?: [];

        $blocos = [];
        $buffer = [];
        $tipoBloco = null;

        $fecharBloco = function () use (&$blocos, &$buffer, &$tipoBloco) {
            if ($tipoBloco === null) {
                return;
            }
            if ($tipoBloco === 'ul') {
                $itens = array_map(fn ($l) => '<li>'.$this->inline($l).'</li>', $buffer);
                $blocos[] = '<ul>'.implode('', $itens).'</ul>';
            } elseif ($tipoBloco === 'pre') {
                $blocos[] = '<pre><code>'.htmlspecialchars(implode("\n", $buffer)).'</code></pre>';
            } elseif ($tipoBloco === 'table') {
                $blocos[] = '<table>'.implode('', $buffer).'</table>';
            } elseif ($tipoBloco === 'blockquote') {
                $linhasCita = array_map(fn ($l) => $this->inline($l), $buffer);
                $blocos[] = '<blockquote>'.implode('<br>', $linhasCita).'</blockquote>';
            }
            $buffer = [];
            $tipoBloco = null;
        };

        foreach ($linhas as $linha) {
            $t = rtrim($linha);

            // Cabeçalhos
            if (preg_match('/^(#{1,6})\s+(.*)$/', $t, $m)) {
                $fecharBloco();
                $nivel = strlen($m[1]);
                $blocos[] = '<h'.$nivel.'>'.$this->inline($m[2]).'</h'.$nivel.'>';
                continue;
            }

            // HR
            if (preg_match('/^\s*(?:-{3,}|\*{3,}|_{3,})\s*$/', $t)) {
                $fecharBloco();
                $blocos[] = '<hr>';
                continue;
            }

            // Lista
            if (preg_match('/^[-*]\s+(.*)$/', $t, $m)) {
                if ($tipoBloco !== 'ul') {
                    $fecharBloco();
                    $tipoBloco = 'ul';
                }
                $buffer[] = $m[1];
                continue;
            }

            // Código
            if (preg_match('/^\s*```/', $t)) {
                if ($tipoBloco === 'pre') {
                    $fecharBloco();
                } else {
                    $fecharBloco();
                    $tipoBloco = 'pre';
                }
                continue;
            }

            // Linha dentro de bloco de código: acumula no buffer sem fechar
            if ($tipoBloco === 'pre') {
                $buffer[] = $t;
                continue;
            }

            // Citação
            if (preg_match('/^>\s?(.*)$/', $t, $m)) {
                if ($tipoBloco !== 'blockquote') {
                    $fecharBloco();
                    $tipoBloco = 'blockquote';
                }
                $buffer[] = $m[1];
                continue;
            }

            // Tabela
            if (preg_match('/^\|/', $t)) {
                // Linha de separação (| --- |, | :---: |, |-|-|-|) — ignora
                $semDivisores = str_replace(['|', ':', ' ', "\t", '-'], '', $t);
                if ($semDivisores === '' && strpos($t, '-') !== false) {
                    continue;
                }
                $celulas = array_map('trim', explode('|', trim($t, '|')));
                if ($tipoBloco !== 'table') {
                    $fecharBloco();
                    $tipoBloco = 'table';
                    $buffer[] = '<thead><tr>'.implode('', array_map(fn ($c) => '<th>'.$this->inline($c).'</th>', $celulas))
                        .'</tr></thead>';
                    continue;
                }
                $buffer[] = '<tr>'.implode('', array_map(fn ($c) => '<td>'.$this->inline($c).'</td>', $celulas)).'</tr>';
                continue;
            }

            // Linha em branco fecha o bloco atual
            if ($t === '') {
                $fecharBloco();
                $blocos[] = '';
                continue;
            }

            // Texto comum
            $fecharBloco();
            if ($t !== '') {
                $blocos[] = '<p>'.$this->inline($t).'</p>';
            }
        }

        $fecharBloco();

        return implode("\n", array_filter($blocos, fn ($b) => $b !== ''));
    }

    /**
     * Formatação inline: código, negrito, itálico, links e escape de HTML.
     */
    protected function inline(string $texto): string
    {
        $texto = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');

        // `código`
        $texto = preg_replace('/`([^`]+)`/', '<code>$1</code>', $texto) ?? $texto;

        // **negrito**
        $texto = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $texto) ?? $texto;

        // *itálico*
        $texto = preg_replace('/(?<![\w*])\*([^*]+)\*(?![\w*])/', '<em>$1</em>', $texto) ?? $texto;

        // [texto](url)
        $texto = preg_replace(
            '/\[([^\]]+)\]\(([^)\s]+)\)/',
            '<a href="$2" target="_blank" rel="noopener">$1</a>',
            $texto
        ) ?? $texto;

        return $texto;
    }
}
