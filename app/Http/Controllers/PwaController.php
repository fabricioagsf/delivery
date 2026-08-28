<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Produto;
use Illuminate\Http\Response;

class PwaController extends Controller
{
    /**
     * Nome base do cache; a versão configurável (pwa_cache_versao) permite forçar
     * a reativação/limpeza do cache nos clientes sem novo deploy.
     */
    public const CACHE_BASE = 'gostosuras';

    /**
     * Web App Manifest do PWA. Nome/slogan vêm da tabela `textos`.
     */
    public function manifest(): Response
    {
        $nome = tema_texto('nome', 'Guloseimas');
        $slogan = tema_texto('slogan', 'sabores artesanais');

        return response((string) json_encode([
            'name' => $nome.' — '.$slogan,
            'short_name' => $nome,
            'description' => tema_texto('sobre', 'Comidas e guloseimas artesanais feitas com carinho.'),
            'start_url' => url('/cardapio'),
            'scope' => url('/'),
            'id' => url('/'),
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => '#fff7f2',
            'theme_color' => '#ffb59c',
            'lang' => 'pt-BR',
            'icons' => [
                [
                    'src' => asset('icons/icon.svg'),
                    'sizes' => 'any',
                    'type' => 'image/svg+xml',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('icons/icon.svg'),
                    'sizes' => '512x512',
                    'type' => 'image/svg+xml',
                    'purpose' => 'maskable',
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Service worker: pré-cacheia os assets essenciais e as imagens do cardápio
     * (produtos ativos + banners), permitindo consultar o cardápio offline.
     */
    public function serviceWorker(): Response
    {
        $imagens = collect()
            ->merge(Produto::query()->where('ativo', true)->whereNotNull('imagem')->pluck('imagem'))
            ->merge(Banner::query()->where('ativo', true)->whereNotNull('imagem')->pluck('imagem'))
            ->map(fn ($img) => $this->urlAbsolutoDaImagem($img))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $assets = [
            asset('css/loja.css').'?v='.filemtime(public_path('css/loja.css')),
            asset('js/loja.js').'?v='.filemtime(public_path('js/loja.js')),
            asset('icons/icon.svg'),
            asset('manifest.webmanifest'),
        ];

        return response(view('pwa.sw', [
            'cache' => static::versaoCache(),
            'assets' => $assets,
            'imagens' => $imagens,
        ]), 200)->header('Content-Type', 'application/javascript');
    }

    /**
     * Nome do cache atual (base + versão configurável). Mudar a versão faz o
     * service worker recriar o cache e limpar o antigo no `activate`.
     */
    public static function versaoCache(): string
    {
        $versao = (int) (config_loja('pwa_cache_versao') ?? 1);
        return static::CACHE_BASE.'-v'.$versao;
    }

    /**
     * Total de imagens que o service worker vai pré-cachear (cardápio offline).
     */
    public static function totalImagens(): int
    {
        return Produto::query()->where('ativo', true)->whereNotNull('imagem')->count()
            + Banner::query()->where('ativo', true)->whereNotNull('imagem')->count();
    }

    /**
     * Resolve um caminho relativo de imagem (ex.: img/produtos/x.jpg) para URL.
     */
    protected function urlAbsolutoDaImagem(string $img): string
    {
        $path = str_starts_with($img, '/') ? ltrim($img, '/') : $img;
        return file_exists(public_path($path)) ? asset($path) : '';
    }
}
