<?php
require_once 'includes/functions.php';

$mes = $_GET['mes'] ?? date('Y-m');

$stResumo = $pdo->prepare("SELECT COUNT(*) AS qtd_vendas, COALESCE(SUM(total),0) AS faturamento FROM vendas WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$stResumo->execute([$mes]);
$resumo = $stResumo->fetch();

$stVendas = $pdo->prepare("SELECT id, cliente_nome, total, created_at FROM vendas WHERE DATE_FORMAT(created_at, '%Y-%m') = ? ORDER BY created_at DESC");
$stVendas->execute([$mes]);
$vendas = $stVendas->fetchAll();

$stProdutos = $pdo->prepare("SELECT p.nome, SUM(iv.quantidade) AS qtd, SUM(iv.subtotal) AS receita
FROM itens_venda iv
JOIN vendas v ON v.id = iv.venda_id
JOIN produtos p ON p.id = iv.produto_id
WHERE DATE_FORMAT(v.created_at, '%Y-%m') = ?
GROUP BY p.id, p.nome
ORDER BY qtd DESC");
$stProdutos->execute([$mes]);
$produtos = $stProdutos->fetchAll();

include 'includes/header.php';
?>

<div class="card">
  <h3>Relatório Mensal</h3>
  <form class="flex" method="get">
    <input type="month" name="mes" value="<?= htmlspecialchars($mes) ?>">
    <button type="submit">Filtrar</button>
  </form>
  <div class="grid" style="margin-top:1rem;">
    <article class="card"><h3>Vendas</h3><div class="kpi"><?= (int)$resumo['qtd_vendas'] ?></div></article>
    <article class="card"><h3>Faturamento</h3><div class="kpi">R$ <?= number_format($resumo['faturamento'],2,',','.') ?></div></article>
  </div>
</div>

<section class="grid" style="margin-top:1rem;">
  <article class="card table-wrap" style="grid-column: span 2;">
    <h3>Vendas do Mês</h3>
    <table>
      <thead><tr><th>Data</th><th>ID</th><th>Cliente</th><th>Total</th></tr></thead>
      <tbody>
      <?php foreach ($vendas as $v): ?>
        <tr>
          <td><?= date('d/m/Y H:i', strtotime($v['created_at'])) ?></td>
          <td>#<?= $v['id'] ?></td>
          <td><?= htmlspecialchars($v['cliente_nome'] ?: '-') ?></td>
          <td>R$ <?= number_format($v['total'],2,',','.') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </article>

  <article class="card table-wrap">
    <h3>Produtos Vendidos no Mês</h3>
    <table>
      <thead><tr><th>Produto</th><th>Qtd</th><th>Receita</th></tr></thead>
      <tbody>
      <?php foreach ($produtos as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['nome']) ?></td>
          <td><?= (int)$p['qtd'] ?></td>
          <td>R$ <?= number_format($p['receita'],2,',','.') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </article>
</section>

<?php include 'includes/footer.php'; ?>
