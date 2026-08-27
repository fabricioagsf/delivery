<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Arrastar e Soltar</title>
    <style>
        .lista { display: flex; gap: 10px; padding: 20px; }
        .item {
            padding: 15px 30px;
            background: #3b82f6;
            color: white;
            border-radius: 8px;
            cursor: grab;
        }
        .item:active { cursor: grabbing; opacity: 0.5; }
        .area {
            min-height: 100px;
            margin: 20px;
            padding: 20px;
            border: 2px dashed #ccc;
            border-radius: 8px;
        }
        .area.sobre { border-color: #3b82f6; background: #eff6ff; }
    </style>
</head>
<body>

<div class="lista" id="produtos">
    <div class="item" draggable="true" data-id="1">Arroz</div>
    <div class="item" draggable="true" data-id="2">Feijao</div>
    <div class="item" draggable="true" data-id="3">Oleo</div>
</div>

<div class="area" id="destino">
    Arraste os produtos aqui
</div>

<script>
    const itens = document.querySelectorAll('.item');
    const area = document.getElementById('destino');

    itens.forEach(item => {
        item.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', item.dataset.id);
        });
    });

    area.addEventListener('dragover', (e) => {
        e.preventDefault();
        area.classList.add('sobre');
    });

    area.addEventListener('dragleave', () => {
        area.classList.remove('sobre');
    });

    area.addEventListener('drop', (e) => {
        e.preventDefault();
        area.classList.remove('sobre');

        const id = e.dataTransfer.getData('text/plain');
        const item = document.querySelector(`[data-id="${id}"]`);

        area.appendChild(item);

        fetch('{{ route("admin.produtos.ordenar") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ id: id })
        });
    });
</script>

</body>
</html>
