<?php
require_once 'includes/functions.php';
include 'includes/header.php';

$totalProdutos = (int)$pdo->query('SELECT COUNT(*) FROM produtos WHERE ativo = 1')->fetchColumn();
$estoqueBaixo = (int)$pdo->query('SELECT COUNT(*) FROM produtos WHERE estoque_atual <= estoque_minimo AND ativo = 1')->fetchColumn();
$totalMes = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM vendas WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')")->fetchColumn();
$totalVendasMes = (int)$pdo->query("SELECT COUNT(*) FROM vendas WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')")->fetchColumn();

$top5 = $pdo->query("SELECT p.nome, SUM(iv.quantidade) AS qtd
FROM itens_venda iv
JOIN produtos p ON p.id = iv.produto_id
JOIN vendas v ON v.id = iv.venda_id
WHERE DATE_FORMAT(v.created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
GROUP BY p.id, p.nome
ORDER BY qtd DESC
LIMIT 5")->fetchAll();

$alertas = $pdo->query('SELECT nome, estoque_atual, estoque_minimo FROM produtos WHERE estoque_atual <= estoque_minimo AND ativo = 1 ORDER BY estoque_atual ASC')->fetchAll();
?>

<?php flashMessage(); ?>

<section class="grid">
  <article class="card"><h3>Produtos Ativos</h3><div class="kpi"><?= $totalProdutos ?></div></article>
  <article class="card"><h3>Alertas de Estoque</h3><div class="kpi"><?= $estoqueBaixo ?></div></article>
  <article class="card"><h3>Vendas no Mês</h3><div class="kpi"><?= $totalVendasMes ?></div></article>
  <article class="card"><h3>Faturamento Mensal</h3><div class="kpi">R$ <?= number_format($totalMes, 2, ',', '.') ?></div></article>
</section>

<section class="grid" style="margin-top:1rem;">
  <article class="card">
    <h3>Top 5 Produtos Mais Vendidos (mês atual)</h3>
    <ol>
      <?php if (!$top5): ?>
        <li>Sem vendas registradas neste mês.</li>
      <?php else: foreach ($top5 as $item): ?>
        <li><?= htmlspecialchars($item['nome']) ?> — <?= (int)$item['qtd'] ?> un.</li>
      <?php endforeach; endif; ?>
    </ol>
  </article>

  <article class="card">
    <h3>Alertas de Estoque Mínimo</h3>
    <?php if (!$alertas): ?>
      <p>Todos os produtos estão acima do estoque mínimo 🎉</p>
    <?php else: ?>
      <ul>
        <?php foreach ($alertas as $a): ?>
          <li><?= htmlspecialchars($a['nome']) ?>: <?= (int)$a['estoque_atual'] ?> / mínimo <?= (int)$a['estoque_minimo'] ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </article>
</section>

<?php include 'includes/footer.php'; ?>
