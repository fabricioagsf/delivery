<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfiguracaoController extends Controller
{
    public const CHAVES = [
        'taxa_entrega',
        'chave_pix',
        'margem_producao',
        'emitir_nfe',
        'empresa_cnpj',
        'empresa_razao_social',
        'empresa_inscricao_estadual',
        'nfe_ambiente',
        'whatsapp_ativo',
        'whatsapp_token',
        'whatsapp_phone_id',
        'google_login_ativo',
        'google_client_id',
        'google_client_secret',
        'facebook_login_ativo',
        'facebook_client_id',
        'facebook_client_secret',
        'microsoft_login_ativo',
        'microsoft_client_id',
        'microsoft_client_secret',
        'instagram_login_ativo',
        'instagram_client_id',
        'instagram_client_secret',
        'mercadopago_ativo',
        'mercadopago_access_token',
        'mercadopago_public_key',
        'efi_ativo',
        'efi_client_id',
        'efi_client_secret',
        'efi_pix_chave',
        'efi_sandbox',
        'item_venda_ativo',
        'item_venda_tipo',
        'tema_loja',
        'mesa_qrcode_ativo',
    ];

    private const SOCIAL_ENV_MAP = [
        'google_login_ativo' => 'AUTH_MULTI_GOOGLE_HABILITADO',
        'google_client_id' => 'AUTH_MULTI_GOOGLE_CLIENT_ID',
        'google_client_secret' => 'AUTH_MULTI_GOOGLE_CLIENT_SECRET',
        'facebook_login_ativo' => 'AUTH_MULTI_FACEBOOK_HABILITADO',
        'facebook_client_id' => 'AUTH_MULTI_FACEBOOK_CLIENT_ID',
        'facebook_client_secret' => 'AUTH_MULTI_FACEBOOK_CLIENT_SECRET',
        'microsoft_login_ativo' => 'AUTH_MULTI_MICROSOFT_HABILITADO',
        'microsoft_client_id' => 'AUTH_MULTI_MICROSOFT_CLIENT_ID',
        'microsoft_client_secret' => 'AUTH_MULTI_MICROSOFT_CLIENT_SECRET',
        'instagram_login_ativo' => 'AUTH_MULTI_INSTAGRAM_HABILITADO',
        'instagram_client_id' => 'AUTH_MULTI_INSTAGRAM_CLIENT_ID',
        'instagram_client_secret' => 'AUTH_MULTI_INSTAGRAM_CLIENT_SECRET',
    ];

    public function index(): View
    {
        $valores = DB::table('configuracoes')
            ->whereIn('chave', self::CHAVES)
            ->where(function ($q) {
                $q->where('loja_id', loja_atual_id())->orWhereNull('loja_id');
            })
            ->orderByRaw('loja_id IS NULL')
            ->orderBy('chave')
            ->get()
            ->keyBy('chave')
            ->map(fn ($linha) => $linha->valor);

        $passosMp = [
            texto('admin_config', 'passo.mp.1', 'Crie o app em developers.mercadopago.com'),
            texto('admin_config', 'passo.mp.2', 'Copie o Access Token de teste (começa com TEST-)'),
            texto('admin_config', 'passo.mp.3', 'Cole no campo abaixo, marque a flag e salve'),
            str_replace(':url', '<code>'.url('/webhooks/mercadopago').'</code>', texto('admin_config', 'passo.mp.4', 'No painel do Mercado Pago, cadastre a URL de notificação: :url')),
        ];

        $passosEfi = [
            texto('admin_config', 'passo.efi.1', 'Crie o app em seuefi.com com o produto Pix'),
            texto('admin_config', 'passo.efi.2', 'Copie Client ID/Secret de homologação e cadastre uma chave Pix'),
            texto('admin_config', 'passo.efi.3', 'Cole nos campos abaixo, marque a flag (com "Homologação") e salve'),
            str_replace(':url', '<code>'.url('/webhooks/efi').'</code>', texto('admin_config', 'passo.efi.4', 'No painel da Efí, cadastre a URL de notificação: :url')),
        ];

        return view('admin.configuracoes', [
            'valores' => $valores,
            'passosMp' => $passosMp,
            'passosEfi' => $passosEfi,
            'temas' => \App\Support\Temas::opcoes(),
        ]);
    }

    public function salvar(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'taxa_entrega' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'chave_pix' => ['nullable', 'string', 'max:120'],
            'margem_producao' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'emitir_nfe' => ['nullable', 'boolean'],
            'empresa_cnpj' => ['nullable', 'string', 'max:20'],
            'empresa_razao_social' => ['nullable', 'string', 'max:200'],
            'empresa_inscricao_estadual' => ['nullable', 'string', 'max:30'],
            'nfe_ambiente' => ['nullable', 'in:1,2'],
            'whatsapp_token' => ['nullable', 'string', 'max:400'],
            'whatsapp_phone_id' => ['nullable', 'string', 'max:50'],
            'google_client_id' => ['nullable', 'string', 'max:200'],
            'google_client_secret' => ['nullable', 'string', 'max:200'],
            'facebook_client_id' => ['nullable', 'string', 'max:200'],
            'facebook_client_secret' => ['nullable', 'string', 'max:200'],
            'microsoft_client_id' => ['nullable', 'string', 'max:200'],
            'microsoft_client_secret' => ['nullable', 'string', 'max:200'],
            'instagram_client_id' => ['nullable', 'string', 'max:200'],
            'instagram_client_secret' => ['nullable', 'string', 'max:200'],
            'mercadopago_access_token' => ['nullable', 'string', 'max:300'],
            'mercadopago_public_key' => ['nullable', 'string', 'max:300'],
            'efi_client_id' => ['nullable', 'string', 'max:120'],
            'efi_client_secret' => ['nullable', 'string', 'max:120'],
            'efi_pix_chave' => ['nullable', 'string', 'max:120'],
            'item_venda_tipo' => ['nullable', 'in:produtos,servicos,ambos'],
            'tema_loja' => ['nullable', 'in:guloseimas,italiana,japonesa,chinesa,mexicana'],
        ], [
            '*.numeric' => texto('admin_config', 'erro.numero', 'Informe um número válido.'),
            '*.in' => texto('checkout', 'erro.opcao_invalida', 'Escolha uma opção válida.'),
        ]);

        foreach (self::CHAVES as $chave) {
            $valor = match ($chave) {
                'emitir_nfe', 'whatsapp_ativo', 'google_login_ativo',
                'facebook_login_ativo', 'microsoft_login_ativo', 'instagram_login_ativo',
                'mercadopago_ativo', 'efi_ativo', 'efi_sandbox',
                'item_venda_ativo' => $request->boolean($chave) ? '1' : '0',
                default => (string) ($dados[$chave] ?? ''),
            };

            DB::table('configuracoes')->updateOrInsert(
                ['loja_id' => loja_atual_id(), 'chave' => $chave],
                ['valor' => $valor, 'updated_at' => now()]
            );

            if (isset(self::SOCIAL_ENV_MAP[$chave])) {
                $this->gravarEnv(self::SOCIAL_ENV_MAP[$chave], $valor);
            }
        }

        return back()->with('sucesso_config', texto('admin_config', 'sucesso.salvo', 'Configurações salvas!'));
    }

    private function gravarEnv(string $chave, string $valor): void
    {
        $path = base_path('.env');

        if (! file_exists($path)) {
            return;
        }

        $conteudo = file_get_contents($path);
        $padrao = '/^' . preg_quote($chave, '/') . '=.*/m';

        if (preg_match($padrao, $conteudo)) {
            $conteudo = preg_replace($padrao, $chave . '=' . $valor, $conteudo);
        } else {
            $conteudo = rtrim($conteudo, "\r\n") . "\n{$chave}={$valor}\n";
        }

        file_put_contents($path, $conteudo);
    }
}
