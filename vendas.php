<?php
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = trim($_POST['cliente_nome'] ?? '');
    $itens = json_decode($_POST['itens'] ?? '[]', true);

    if (!$itens || !is_array($itens)) {
        setFlash('error', 'Carrinho vazio. Adicione itens para registrar a venda.');
        header('Location: vendas.php');
        exit;
    }

    $pdo->beginTransaction();
    try {
        $total = 0;
        $itensValidados = [];

        foreach ($itens as $item) {
            $produtoId = (int)($item['produto_id'] ?? 0);
            $qtd = (int)($item['quantidade'] ?? 0);
            if ($produtoId <= 0 || $qtd <= 0) throw new Exception('Item inválido no carrinho.');

            $st = $pdo->prepare('SELECT nome, preco, estoque_atual FROM produtos WHERE id = ? AND ativo = 1 FOR UPDATE');
            $st->execute([$produtoId]);
            $produto = $st->fetch();
            if (!$produto) throw new Exception('Produto não encontrado.');
            if ((int)$produto['estoque_atual'] < $qtd) throw new Exception('Sem estoque suficiente para ' . $produto['nome'] . '.');

            $subtotal = $qtd * (float)$produto['preco'];
            $total += $subtotal;
            $itensValidados[] = [
                'produto_id' => $produtoId,
                'quantidade' => $qtd,
                'preco' => (float)$produto['preco'],
                'subtotal' => $subtotal,
            ];
        }

        $vendaSt = $pdo->prepare('INSERT INTO vendas (cliente_nome, total) VALUES (?, ?)');
        $vendaSt->execute([$cliente ?: null, $total]);
        $vendaId = (int)$pdo->lastInsertId();

        $itemSt = $pdo->prepare('INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (?, ?, ?, ?, ?)');
        $upEstoque = $pdo->prepare('UPDATE produtos SET estoque_atual = estoque_atual - ? WHERE id = ?');
        $movSt = $pdo->prepare("INSERT INTO movimentacoes (produto_id, tipo, quantidade, observacao) VALUES (?, 'saida', ?, ?)");

        foreach ($itensValidados as $item) {
            $itemSt->execute([$vendaId, $item['produto_id'], $item['quantidade'], $item['preco'], $item['subtotal']]);
            $upEstoque->execute([$item['quantidade'], $item['produto_id']]);
            $movSt->execute([$item['produto_id'], $item['quantidade'], "Venda #{$vendaId}"]);
        }

        $pdo->commit();
        setFlash('success', 'Venda registrada com sucesso.');
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Erro ao registrar venda: ' . $e->getMessage());
    }

    header('Location: vendas.php');
    exit;
}

$produtos = getProdutosAtivos($pdo);
include 'includes/header.php';
?>

<?php flashMessage(); ?>
<div class="grid">
  <article class="card">
    <h3>Adicionar ao Carrinho</h3>
    <div class="flex">
      <select id="produtoSelect">
        <option value="">Selecione...</option>
        <?php foreach ($produtos as $p): ?>
          <option value="<?= $p['id'] ?>" data-preco="<?= $p['preco'] ?>" data-estoque="<?= (int)$p['estoque_atual'] ?>">
            <?= htmlspecialchars($p['nome']) ?> — R$ <?= number_format($p['preco'],2,',','.') ?> (estoque <?= (int)$p['estoque_atual'] ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <input id="qtdInput" type="number" min="1" placeholder="Qtd" value="1">
      <button type="button" class="btn-secondary" id="addItemBtn">Adicionar</button>
    </div>

    <div class="table-wrap" style="margin-top:1rem;">
      <table id="cartTable">
        <thead><tr><th>Produto</th><th>Qtd</th><th>Preço</th><th>Subtotal</th><th></th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
    <p class="total-box">Total: R$ <span id="totalVenda">0,00</span></p>
  </article>

  <form class="card" method="post" id="checkoutForm">
    <h3>Finalizar Venda</h3>
    <input type="text" name="cliente_nome" placeholder="Cliente (opcional)">
    <input type="hidden" name="itens" id="itensInput">
    <button type="submit">Registrar Venda</button>
  </form>
</div>

<script>
const carrinho = [];
const select = document.getElementById('produtoSelect');
const qtdInput = document.getElementById('qtdInput');
const tableBody = document.querySelector('#cartTable tbody');
const totalEl = document.getElementById('totalVenda');
const itensInput = document.getElementById('itensInput');

function moeda(v){ return v.toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2}); }

function renderCart() {
  tableBody.innerHTML = '';
  let total = 0;
  carrinho.forEach((item, i) => {
    const subtotal = item.quantidade * item.preco;
    total += subtotal;
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${item.nome}</td><td>${item.quantidade}</td><td>R$ ${moeda(item.preco)}</td><td>R$ ${moeda(subtotal)}</td><td><button type="button" class="btn-danger" onclick="removerItem(${i})">x</button></td>`;
    tableBody.appendChild(tr);
  });
  totalEl.textContent = moeda(total);
  itensInput.value = JSON.stringify(carrinho.map(i => ({produto_id:i.produto_id, quantidade:i.quantidade})));
}

function removerItem(index) {
  carrinho.splice(index, 1);
  renderCart();
}

window.removerItem = removerItem;

document.getElementById('addItemBtn').addEventListener('click', () => {
  const opt = select.options[select.selectedIndex];
  if (!opt || !opt.value) return alert('Selecione um produto');
  const produtoId = Number(opt.value);
  const nome = opt.text.split(' — ')[0];
  const preco = Number(opt.dataset.preco);
  const estoque = Number(opt.dataset.estoque);
  const qtd = Number(qtdInput.value);
  if (!qtd || qtd <= 0) return alert('Quantidade inválida');

  const existente = carrinho.find(i => i.produto_id === produtoId);
  const totalQtd = (existente ? existente.quantidade : 0) + qtd;
  if (totalQtd > estoque) return alert('Quantidade maior que o estoque disponível.');

  if (existente) existente.quantidade += qtd;
  else carrinho.push({produto_id: produtoId, nome, quantidade: qtd, preco});

  renderCart();
});

document.getElementById('checkoutForm').addEventListener('submit', (e) => {
  if (carrinho.length === 0) {
    e.preventDefault();
    alert('Adicione itens ao carrinho antes de finalizar.');
  }
});
</script>

<?php include 'includes/footer.php'; ?>
