/* GOSTOSURAS — service worker (PWA offline do cardápio) */
var VERSAO = {{ json_encode($cache) }};
var CACHE_ASSETS = VERSAO + ':assets';
var CACHE_PAGINAS = VERSAO + ':paginas';

var ASSETS = @json($assets);
var IMAGENS = @json($imagens);

self.addEventListener('install', function (evento) {
    evento.waitUntil(
        caches.open(CACHE_ASSETS)
            .then(function (cache) {
                return cache.addAll(ASSETS.concat(IMAGENS));
            })
            .then(function () { return self.skipWaiting(); })
            .catch(function () { /* falha parcial não impede a ativação */ })
    );
});

self.addEventListener('activate', function (evento) {
    evento.waitUntil(
        caches.keys().then(function (chaves) {
            return Promise.all(
                chaves
                    .filter(function (chave) { return chave.indexOf(VERSAO) !== 0; })
                    .map(function (chave) { return caches.delete(chave); })
            );
        }).then(function () { return self.clients.claim(); })
    );
});

// Cache-first com atualização em segundo plano, para assets estáticos (css/js/imagens).
function staleWhileRevalidate(requisicao) {
    var pegarCache = caches.open(CACHE_ASSETS).then(function (cache) {
        return cache.match(requisicao).then(function (resposta) {
            var polite = fetch(requisicao).then(function (rede) {
                if (rede && rede.ok) cache.put(requisicao, rede.clone());
                return rede;
            }).catch(function () { /* offline: usa o cache */ });
            return resposta || polite;
        });
    });
    return pegarCache;
}

// Navegação: tenta a rede primeiro; falhou, usa a última cópia guardada (offline).
function networkFirstPagina(requisicao) {
    return fetch(requisicao).then(function (rede) {
        if (rede && rede.ok) {
            var copia = rede.clone();
            caches.open(CACHE_PAGINAS).then(function (cache) { cache.put(requisicao, copia); });
        }
        return rede;
    }).catch(function () {
        return caches.open(CACHE_PAGINAS).then(function (cache) {
            return cache.match(requisicao).then(function (resposta) {
                return resposta || cache.match('/cardapio');
            });
        });
    });
}

self.addEventListener('fetch', function (evento) {
    var requisicao = evento.request;

    // Só cuidamos de GET de mesmo origem (não mexemos em POST/checkout/webhooks).
    if (requisicao.method !== 'GET') return;
    var url = new URL(requisicao.url);
    if (url.origin !== self.location.origin) return;

    // Navegação (páginas): network-first com fallback offline.
    if (requisicao.mode === 'navigate') {
        evento.respondWith(networkFirstPagina(requisicao));
        return;
    }

    // Assets estáticos: cache-first com atualização em segundo plano.
    evento.respondWith(staleWhileRevalidate(requisicao));
});
