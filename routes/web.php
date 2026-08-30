<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CaixaController;
use App\Http\Controllers\Admin\ConfiguracaoController;
use App\Http\Controllers\Admin\CupomController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ModuloController;
use App\Http\Controllers\Admin\PedidoController as AdminPedidoController;
use App\Http\Controllers\Admin\ProdutoController as AdminProdutoController;
use App\Http\Controllers\Admin\RelatorioController;
use App\Http\Controllers\Admin\SairController as AdminSairController;
use App\Http\Controllers\CarrinhoController;
use App\Http\Controllers\CartaoController;
use App\Http\Controllers\CardapioController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ClienteAuthController;
use App\Http\Controllers\ContaController;
use App\Http\Controllers\EnderecoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\VitrineController;
use App\Http\Controllers\Admin\FidelidadeController;
use App\Http\Controllers\Admin\LojasController;
use App\Http\Controllers\Admin\MesaController;
use Illuminate\Support\Facades\Route;

// PWA + troca de loja pública (um domínio, seletor na vitrine)
Route::post('/loja/trocar', [\App\Http\Controllers\LojaController::class, 'trocar'])->name('loja.trocar');

Route::get('/', [VitrineController::class, 'index'])->name('vitrine');
Route::get('/vitrine/versao', [VitrineController::class, 'versao'])->name('vitrine.versao');
Route::get('/cardapio', [CardapioController::class, 'index'])->name('cardapio');

// PWA (cardápio offline / instalável) — manifesto e service worker
Route::get('/manifest.webmanifest', [\App\Http\Controllers\PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sw.js', [\App\Http\Controllers\PwaController::class, 'serviceWorker'])->name('pwa.service_worker');

Route::prefix('carrinho')->name('carrinho.')->group(function () {
    Route::get('/', [CarrinhoController::class, 'index'])->name('index');
    Route::post('/adicionar', [CarrinhoController::class, 'adicionar'])->name('adicionar');
    Route::post('/atualizar', [CarrinhoController::class, 'atualizar'])->name('atualizar');
    Route::post('/remover', [CarrinhoController::class, 'remover'])->name('remover');
});

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.finalizar');
Route::post('/checkout/cupom', [CheckoutController::class, 'validarCupom'])->name('checkout.validar_cupom');
Route::post('/checkout/pontos', [CheckoutController::class, 'validarPontos'])->name('checkout.validar_pontos');

Route::get('/pedido/{codigo}', [PedidoController::class, 'confirmacao'])->name('pedido.confirmacao');
Route::post('/pedido/{codigo}/pagar', [PedidoController::class, 'pagar'])->name('pedido.pagar');

// Webhooks dos gateways (provedores chamam sem sessão; CSRF liberado no bootstrap)
Route::post('/webhooks/mercadopago', [\App\Http\Controllers\WebhookController::class, 'mercadopago'])->name('webhook.mercadopago');
Route::post('/webhooks/efi', [\App\Http\Controllers\WebhookController::class, 'efi'])->name('webhook.efi');

Route::prefix('cliente')->name('cliente.')->group(function () {
    // Token CSRF fresco para o AJAX do menu lateral (padrão SPA:
    // em caso de 419, o JS busca um token novo e repete a requisição)
    Route::get('/csrf', function () {
        return response()->json(['token' => csrf_token()]);
    })->name('csrf');

    Route::post('/registrar', [ClienteAuthController::class, 'registrar'])->name('registrar');
    Route::post('/login', [ClienteAuthController::class, 'login'])->name('login');

    Route::middleware('auth.multi:cliente')->group(function () {
        Route::post('/logout', [ClienteAuthController::class, 'logout'])->name('logout');
        Route::get('/painel', [ContaController::class, 'painel'])->name('painel');
        Route::put('/dados', [ContaController::class, 'atualizarDados'])->name('dados');
        Route::post('/senha', [ContaController::class, 'trocarSenha'])->name('senha');
        Route::post('/completar', [ContaController::class, 'completarCadastro'])->name('completar');

        Route::post('/enderecos', [EnderecoController::class, 'store'])->name('enderecos.store');
        Route::delete('/enderecos/{endereco}', [EnderecoController::class, 'destroy'])->name('enderecos.destroy');
        Route::patch('/enderecos/{endereco}/principal', [EnderecoController::class, 'principal'])->name('enderecos.principal');

        Route::post('/cartoes', [CartaoController::class, 'store'])->name('cartoes.store');
        Route::delete('/cartoes/{cartao}', [CartaoController::class, 'destroy'])->name('cartoes.destroy');
    });

    // Login social (Google / Facebook / Microsoft) + completar cadastro depois
    Route::get('/social/{provedor}', [\App\Http\Controllers\SocialLoginController::class, 'redirecionar'])->name('social');
    Route::get('/social/{provedor}/callback', [\App\Http\Controllers\SocialLoginController::class, 'callback'])->name('social.callback');
});

/*
|--------------------------------------------------------------------------
| Painel de administração
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('auth.multi:admin')->group(function () {
        Route::post('/sair', AdminSairController::class)->name('logout');
        Route::get('/painel', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/produtos', [AdminProdutoController::class, 'index'])->name('produtos.index');
        Route::get('/produtos/criar', [AdminProdutoController::class, 'create'])->name('produtos.create');
        Route::post('/produtos', [AdminProdutoController::class, 'store'])->name('produtos.store');
        Route::get('/produtos/{produto}/editar', [AdminProdutoController::class, 'edit'])->name('produtos.edit');
        Route::post('/produtos/{produto}', [AdminProdutoController::class, 'update'])->name('produtos.update');
        Route::post('/produtos/{produto}/remover', [AdminProdutoController::class, 'destroy'])->name('produtos.destroy');
        Route::post('/produtos/{produto}/estoque', [AdminProdutoController::class, 'atualizarEstoque'])->name('produtos.estoque');
        Route::post('/produtos/{produto}/ativo', [AdminProdutoController::class, 'alternarAtivo'])->name('produtos.ativo');
        Route::post('/produtos/{produto}/destaque', [AdminProdutoController::class, 'alternarDestaque'])->name('produtos.destaque');

        Route::get('/pedidos', [AdminPedidoController::class, 'index'])->name('pedidos.index');
        Route::get('/pedidos/{pedido}', [AdminPedidoController::class, 'show'])->name('pedidos.show');
        Route::post('/pedidos/{pedido}/status', [AdminPedidoController::class, 'atualizarStatus'])->name('pedidos.status');
        Route::post('/pedidos/{pedido}/nota', [AdminPedidoController::class, 'gerarNota'])->name('pedidos.nota');
        Route::post('/pedidos/{pedido}/whatsapp', [AdminPedidoController::class, 'enviarWhatsApp'])->name('pedidos.whatsapp');

        Route::get('/configuracoes', [ConfiguracaoController::class, 'index'])->name('configuracoes.index');
        Route::post('/configuracoes', [ConfiguracaoController::class, 'salvar'])->name('configuracoes.salvar');

        Route::get('/item-venda', [\App\Http\Controllers\Admin\ItemVendaController::class, 'index'])->name('item-venda.index');
        Route::post('/item-venda', [\App\Http\Controllers\Admin\ItemVendaController::class, 'atualizar'])->name('item-venda.atualizar');

        Route::get('/pwa', [\App\Http\Controllers\Admin\AdminPwaController::class, 'index'])->name('pwa.index');
        Route::post('/pwa', [\App\Http\Controllers\Admin\AdminPwaController::class, 'atualizar'])->name('pwa.atualizar');

        Route::get('/fidelidade', [\App\Http\Controllers\Admin\FidelidadeController::class, 'index'])->name('fidelidade.index');
        Route::post('/fidelidade', [\App\Http\Controllers\Admin\FidelidadeController::class, 'atualizar'])->name('fidelidade.atualizar');

        Route::get('/lojas', [LojasController::class, 'index'])->name('lojas.index');
        Route::get('/lojas/criar', [LojasController::class, 'create'])->name('lojas.create');
        Route::post('/lojas', [LojasController::class, 'store'])->name('lojas.store');
        Route::get('/lojas/{loja}/editar', [LojasController::class, 'edit'])->name('lojas.edit');
        Route::post('/lojas/trocar', [LojasController::class, 'trocar'])->name('lojas.trocar');
        Route::post('/lojas/{loja}', [LojasController::class, 'update'])->name('lojas.update');
        Route::post('/lojas/{loja}/status', [LojasController::class, 'alternarStatus'])->name('lojas.status');

        Route::get('/help', [\App\Http\Controllers\Admin\HelpController::class, 'index'])->name('help');

        Route::get('/relatorios', [RelatorioController::class, 'index'])->name('relatorios');
        Route::get('/relatorios/exportar', [RelatorioController::class, 'exportar'])->name('relatorios.exportar');
        Route::get('/relatorios/mensal', [RelatorioController::class, 'mensal'])->name('relatorios.mensal');
        Route::get('/relatorios/simples', [RelatorioController::class, 'simples'])->name('relatorios.simples');

        Route::get('/clientes', [\App\Http\Controllers\Admin\ClienteController::class, 'index'])->name('clientes.index');
        Route::post('/clientes/{cliente}/senha-whatsapp', [\App\Http\Controllers\Admin\ClienteController::class, 'senhaWhatsapp'])->name('clientes.senha');
        Route::post('/clientes/campanha', [\App\Http\Controllers\Admin\ClienteController::class, 'campanha'])->name('clientes.campanha');

        Route::get('/banners', [BannerController::class, 'index'])->name('banners.index');
        Route::get('/banners/criar', [BannerController::class, 'create'])->name('banners.create');
        Route::post('/banners', [BannerController::class, 'store'])->name('banners.store');
        Route::get('/banners/{banner}/editar', [BannerController::class, 'edit'])->name('banners.edit');
        Route::post('/banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
        Route::post('/banners/{banner}/ativo', [BannerController::class, 'alternarAtivo'])->name('banners.ativo');
        Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');

        Route::get('/cupons', [CupomController::class, 'index'])->name('cupons.index');
        Route::get('/cupons/criar', [CupomController::class, 'create'])->name('cupons.create');
        Route::post('/cupons', [CupomController::class, 'store'])->name('cupons.store');
        Route::get('/cupons/{cupom}/editar', [CupomController::class, 'edit'])->name('cupons.edit');
        Route::post('/cupons/{cupom}', [CupomController::class, 'update'])->name('cupons.update');
        Route::post('/cupons/{cupom}/ativo', [CupomController::class, 'alternarAtivo'])->name('cupons.ativo');
        Route::post('/cupons/{cupom}/divulgar', [CupomController::class, 'divulgar'])->name('cupons.divulgar');
        Route::post('/cupons/parar-divulgacao', [CupomController::class, 'pararDivulgacao'])->name('cupons.parar_divulgacao');
        Route::delete('/cupons/{cupom}', [CupomController::class, 'destroy'])->name('cupons.destroy');

        Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
        Route::get('/auditoria/{log}', [AuditoriaController::class, 'show'])->name('auditoria.show');
        Route::post('/auditoria/{log}/restaurar', [AuditoriaController::class, 'restaurar'])->name('auditoria.restaurar');

        Route::post('/produtos/ordenar', fn (\Illuminate\Http\Request $r) => response()->json(['ok' => true, 'id' => $r->id]))->name('produtos.ordenar');

        Route::get('/mesas', [MesaController::class, 'index'])->name('mesas.index');
        Route::get('/mesas/criar', [MesaController::class, 'create'])->name('mesas.create');
        Route::post('/mesas', [MesaController::class, 'store'])->name('mesas.store');
        Route::get('/mesas/{mesa}/editar', [MesaController::class, 'edit'])->name('mesas.edit');
        Route::post('/mesas/{mesa}', [MesaController::class, 'update'])->name('mesas.update');
        Route::post('/mesas/{mesa}/status', [MesaController::class, 'alternarStatus'])->name('mesas.status');

        Route::get('/mesas-controle', [\App\Http\Controllers\Admin\MesaPedidosController::class, 'index'])->name('mesas-controle.index');
        Route::get('/mesas-controle/estado', [\App\Http\Controllers\Admin\MesaPedidosController::class, 'estado'])->name('mesas-controle.estado');
        Route::get('/mesas-controle/mesa/{mesa}', [\App\Http\Controllers\Admin\MesaPedidosController::class, 'detalhe'])->name('mesas-controle.detalhe');

        Route::get('/mesa/{mesa}/pedido', [\App\Http\Controllers\Admin\MesaPedidosController::class, 'pedir'])->name('mesa.pedido');
        Route::post('/mesa/{mesa}/pedido/confirmar', [\App\Http\Controllers\Admin\MesaPedidosController::class, 'confirmarPedido'])->name('mesa.pedidoConfirmar');
        Route::post('/pedidos/{pedido}/entregue-mesa', [\App\Http\Controllers\Admin\MesaPedidosController::class, 'entregueMesa'])->name('pedidos.entregueMesa');

        Route::get('/caixa', [CaixaController::class, 'index'])->name('caixa.index');
        Route::get('/caixa/estado', [CaixaController::class, 'estado'])->name('caixa.estado');
        Route::get('/caixa/mesa/{mesa}', [CaixaController::class, 'conta'])->name('caixa.conta');
        Route::post('/caixa/mesa/{mesa}/fechar', [CaixaController::class, 'fechar'])->name('caixa.fechar');

        Route::post('/caixa/mesa/{mesa}/pix-efi', [CaixaController::class, 'pixEfi'])->name('caixa.pixEfi');

        Route::get('/modulos', [ModuloController::class, 'index'])->name('modulos.index');
    });
});

/*
|--------------------------------------------------------------------------
| Painel SaaS (nível plataforma: empresas, filiais, employees, módulos)
|--------------------------------------------------------------------------
*/
Route::prefix('saas')->name('saas.')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Saas\SaasAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Saas\SaasAuthController::class, 'login'])->name('login.processar');
    Route::post('/logout', [\App\Http\Controllers\Saas\SaasAuthController::class, 'logout'])->name('logout');

    Route::middleware('saas.auth')->group(function () {
        Route::get('/', [\App\Http\Controllers\Saas\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/empresas', [\App\Http\Controllers\Saas\EmpresaController::class, 'index'])->name('empresas.index');
        Route::get('/empresas/criar', [\App\Http\Controllers\Saas\EmpresaController::class, 'create'])->name('empresas.create');
        Route::post('/empresas', [\App\Http\Controllers\Saas\EmpresaController::class, 'store'])->name('empresas.store');
        Route::get('/empresas/{empresa}/editar', [\App\Http\Controllers\Saas\EmpresaController::class, 'edit'])->name('empresas.edit');
        Route::put('/empresas/{empresa}', [\App\Http\Controllers\Saas\EmpresaController::class, 'update'])->name('empresas.update');
        Route::delete('/empresas/{empresa}', [\App\Http\Controllers\Saas\EmpresaController::class, 'destroy'])->name('empresas.destroy');

        Route::get('/empresas/{empresa}/config', [\App\Http\Controllers\Saas\EmpresaConfigController::class, 'index'])->name('empresas.config');
        Route::post('/empresas/{empresa}/config', [\App\Http\Controllers\Saas\EmpresaConfigController::class, 'salvar'])->name('empresas.config.salvar');

        Route::get('/empresas/{empresa}/comissoes', [\App\Http\Controllers\Saas\ComissaoController::class, 'index'])->name('comissoes.index');

        Route::get('/filiais', [\App\Http\Controllers\Saas\FilialController::class, 'index'])->name('filiais.index');
        Route::get('/filiais/criar', [\App\Http\Controllers\Saas\FilialController::class, 'create'])->name('filiais.create');
        Route::post('/filiais', [\App\Http\Controllers\Saas\FilialController::class, 'store'])->name('filiais.store');
        Route::get('/filiais/{filial}/editar', [\App\Http\Controllers\Saas\FilialController::class, 'edit'])->name('filiais.edit');
        Route::put('/filiais/{filial}', [\App\Http\Controllers\Saas\FilialController::class, 'update'])->name('filiais.update');
        Route::delete('/filiais/{filial}', [\App\Http\Controllers\Saas\FilialController::class, 'destroy'])->name('filiais.destroy');

        Route::get('/employees', [\App\Http\Controllers\Saas\EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/criar', [\App\Http\Controllers\Saas\EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [\App\Http\Controllers\Saas\EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}/editar', [\App\Http\Controllers\Saas\EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [\App\Http\Controllers\Saas\EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/employees/{employee}', [\App\Http\Controllers\Saas\EmployeeController::class, 'destroy'])->name('employees.destroy');

        Route::get('/modulos', [\App\Http\Controllers\Saas\ModuloController::class, 'index'])->name('modulos.index');
        Route::post('/modulos/{slug}/toggle', [\App\Http\Controllers\Saas\ModuloController::class, 'toggle'])->name('modulos.toggle');
    });
});
