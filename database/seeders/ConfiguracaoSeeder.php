<?php

namespace Database\Seeders;

use App\Models\Configuracao;
use Illuminate\Database\Seeder;

class ConfiguracaoSeeder extends Seeder
{
    public function run(): void
    {
        $padroes = [
            'taxa_entrega' => '0.00',
            'chave_pix' => '',
            // margem de segurança (%) aplicada na previsão de produção por horário
            'margem_producao' => '20',

            // ===== Nota fiscal (NF-e / NFC-e) =====
            // '1' = emissão habilitada no sistema; a transmissão real à SEFAZ
            // ainda exige certificado digital (NFE_CERT_PATH/NFE_CERT_SENHA no .env)
            'emitir_nfe' => '0',
            'empresa_cnpj' => '',
            'empresa_razao_social' => '',
            'empresa_inscricao_estadual' => '',
            'nfe_ambiente' => '2', // 2 = homologação, 1 = produção

            // ===== WhatsApp Cloud API (envio automático de ofertas/senhas) =====
            'whatsapp_ativo' => '0',   // '1' = enviar pela API (sem abrir janelas)
            'whatsapp_token' => '',    // token permanente do app Meta
            'whatsapp_phone_id' => '', // Phone Number ID do número de negócio

            // ===== Login social (OAuth puro, sem pacotes) =====
            'google_login_ativo' => '0',
            'google_client_id' => '',
            'google_client_secret' => '',
            'facebook_login_ativo' => '0',
            'facebook_client_id' => '',
            'facebook_client_secret' => '',
            'microsoft_login_ativo' => '0',
            'microsoft_client_id' => '',
            'microsoft_client_secret' => '',

            // ===== Pagamento online (cartão/Pix) =====
            'mercadopago_ativo' => '0',        // '1' = botão "Cartão online" no checkout
            'mercadopago_access_token' => '',  // token do app Mercado Pago (APP_USR... ou TEST-...)
            'mercadopago_public_key' => '',    // public key do app (reserva p/ checkout transparente)
            'efi_ativo' => '0',                // '1' = Pix automático (Efí/Gerencianet)
            'efi_client_id' => '',
            'efi_client_secret' => '',
            'efi_pix_chave' => '',             // chave Pix cadastrada no app Efí
            'efi_sandbox' => '1',              // '1' = homologação (apisandbox)

            // ===== Módulo de produtos e serviços (item-venda) =====
            'item_venda_ativo' => '0',         // '1' = módulo de produtos/serviços ativo
            // O que o sistema vende: 'produtos' | 'servicos' | 'ambos' (delivery usa 'produtos' por padrão)
            'item_venda_tipo' => 'produtos',

            // ===== PWA (cardapio offline / instalavel) =====
            'pwa_ativo' => '1',            // '1' = service worker registrado (cardapio offline habilitado)
            'pwa_cache_versao' => '1',     // numero inteiro: aumente para forcar limpeza/recarga do cache do cliente

            // ===== Tema da loja =====
            'tema_loja' => 'guloseimas',   // guloseimas | italiana | japonesa | chinesa | mexicana
        ];

        foreach ($padroes as $chave => $valor) {
            Configuracao::updateOrCreate(
                ['chave' => $chave],
                ['valor' => $valor]
            );
        }
    }
}
