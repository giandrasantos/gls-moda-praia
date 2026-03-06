<?php
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produtoId = (int)($_POST['produto_id'] ?? 0);
    $tipo = $_POST['tipo'] ?? 'entrada';
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $obs = trim($_POST['observacao'] ?? '');

    if ($quantidade <= 0) {
        setFlash('error', 'Quantidade deve ser maior que zero.');
        header('Location: estoque.php');
        exit;
    }

    $pdo->beginTransaction();
    try {
        $produtoSt = $pdo->prepare('SELECT estoque_atual, nome FROM produtos WHERE id = ? AND ativo = 1 FOR UPDATE');
        $produtoSt->execute([$produtoId]);
        $produto = $produtoSt->fetch();
        if (!$produto) throw new Exception('Produto não encontrado.');

        $estoqueAtual = (int)$produto['estoque_atual'];
        $novoEstoque = $tipo === 'saida' ? $estoqueAtual - $quantidade : $estoqueAtual + $quantidade;

        if ($novoEstoque < 0) throw new Exception('Saída inválida: estoque insuficiente.');

        $up = $pdo->prepare('UPDATE produtos SET estoque_atual = ? WHERE id = ?');
        $up->execute([$novoEstoque, $produtoId]);

        $mov = $pdo->prepare('INSERT INTO movimentacoes (produto_id, tipo, quantidade, observacao) VALUES (?, ?, ?, ?)');
        $mov->execute([$produtoId, $tipo, $quantidade, $obs]);

        $pdo->commit();
        setFlash('success', 'Movimentação registrada com sucesso.');
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', $e->getMessage());
    }

    header('Location: estoque.php');
    exit;
}

$produtos = $pdo->query('SELECT id, nome, estoque_atual, estoque_minimo FROM produtos WHERE ativo = 1 ORDER BY nome')->fetchAll();
$movimentacoes = $pdo->query('SELECT m.*, p.nome as produto_nome FROM movimentacoes m JOIN produtos p ON p.id = m.produto_id ORDER BY m.created_at DESC LIMIT 30')->fetchAll();

include 'includes/header.php';
?>

<?php flashMessage(); ?>
<div class="grid">
  <form class="card" method="post">
    <h3>Entrada / Saída de Estoque</h3>
    <select name="produto_id" required>
      <option value="">Selecione o produto</option>
      <?php foreach ($produtos as $p): ?>
      <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?> (atual: <?= (int)$p['estoque_atual'] ?>)</option>
      <?php endforeach; ?>
    </select>
    <select name="tipo" required>
      <option value="entrada">Entrada</option>
      <option value="saida">Saída</option>
    </select>
    <input type="number" name="quantidade" min="1" required placeholder="Quantidade">
    <textarea name="observacao" placeholder="Observação"></textarea>
    <button type="submit">Registrar Movimentação</button>
  </form>

  <article class="card table-wrap" style="grid-column: span 2;">
    <h3>Posição de Estoque</h3>
    <table>
      <thead><tr><th>Produto</th><th>Atual</th><th>Mínimo</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($produtos as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['nome']) ?></td>
        <td><?= (int)$p['estoque_atual'] ?></td>
        <td><?= (int)$p['estoque_minimo'] ?></td>
        <td><?= $p['estoque_atual'] <= $p['estoque_minimo'] ? '<span class="badge low">Atenção</span>' : '<span class="badge ok">OK</span>' ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </article>
</div>

<article class="card table-wrap" style="margin-top:1rem;">
  <h3>Últimas Movimentações</h3>
  <table>
    <thead><tr><th>Data</th><th>Produto</th><th>Tipo</th><th>Qtd</th><th>Observação</th></tr></thead>
    <tbody>
      <?php foreach ($movimentacoes as $m): ?>
        <tr>
          <td><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
          <td><?= htmlspecialchars($m['produto_nome']) ?></td>
          <td><?= htmlspecialchars($m['tipo']) ?></td>
          <td><?= (int)$m['quantidade'] ?></td>
          <td><?= htmlspecialchars($m['observacao'] ?? '-') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</article>

<?php include 'includes/footer.php'; ?>
