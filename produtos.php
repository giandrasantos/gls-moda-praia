<?php
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar') {
        $nome = trim($_POST['nome'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $preco = (float)($_POST['preco'] ?? 0);
        $estoque = (int)($_POST['estoque_atual'] ?? 0);
        $minimo = (int)($_POST['estoque_minimo'] ?? 5);
        $fornecedorId = !empty($_POST['fornecedor_id']) ? (int)$_POST['fornecedor_id'] : null;
        $categoriaNome = trim($_POST['categoria_nome'] ?? '');

        if ($nome === '' || $categoriaNome === '') {
            setFlash('error', 'Nome e categoria são obrigatórios.');
            header('Location: produtos.php');
            exit;
        }

        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('INSERT IGNORE INTO categorias (nome) VALUES (?)');
            $st->execute([$categoriaNome]);
            $catId = (int)$pdo->query("SELECT id FROM categorias WHERE nome = " . $pdo->quote($categoriaNome))->fetchColumn();

            $ins = $pdo->prepare('INSERT INTO produtos (categoria_id, fornecedor_id, nome, sku, preco, estoque_atual, estoque_minimo) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $ins->execute([$catId, $fornecedorId, $nome, $sku ?: null, $preco, $estoque, $minimo]);

            if ($estoque > 0) {
                $produtoId = (int)$pdo->lastInsertId();
                $mov = $pdo->prepare("INSERT INTO movimentacoes (produto_id, tipo, quantidade, observacao) VALUES (?, 'entrada', ?, 'Estoque inicial')");
                $mov->execute([$produtoId, $estoque]);
            }
            $pdo->commit();
            setFlash('success', 'Produto cadastrado com sucesso.');
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('error', 'Erro ao cadastrar produto: ' . $e->getMessage());
        }
    }

    if ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);
        $del = $pdo->prepare('UPDATE produtos SET ativo = 0 WHERE id = ?');
        $del->execute([$id]);
        setFlash('success', 'Produto desativado com sucesso.');
    }

    header('Location: produtos.php');
    exit;
}

$produtos = $pdo->query('SELECT p.*, c.nome AS categoria, f.nome AS fornecedor
FROM produtos p
JOIN categorias c ON c.id = p.categoria_id
LEFT JOIN fornecedores f ON f.id = p.fornecedor_id
WHERE p.ativo = 1
ORDER BY p.created_at DESC')->fetchAll();
$fornecedores = getFornecedores($pdo);

include 'includes/header.php';
?>

<?php flashMessage(); ?>
<div class="grid">
  <form class="card" method="post">
    <h3>Novo Produto</h3>
    <input type="hidden" name="acao" value="criar">
    <input type="text" name="nome" placeholder="Nome do produto" required>
    <input type="text" name="categoria_nome" placeholder="Categoria (ex: Biquínis)" required>
    <input type="text" name="sku" placeholder="SKU">
    <input type="number" step="0.01" min="0" name="preco" placeholder="Preço" required>
    <input type="number" min="0" name="estoque_atual" placeholder="Estoque inicial" value="0">
    <input type="number" min="0" name="estoque_minimo" placeholder="Estoque mínimo" value="5">
    <select name="fornecedor_id">
      <option value="">Sem fornecedor</option>
      <?php foreach ($fornecedores as $f): ?>
        <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Cadastrar</button>
  </form>

  <article class="card table-wrap" style="grid-column: span 2;">
    <h3>Produtos Cadastrados</h3>
    <table>
      <thead><tr><th>Nome</th><th>Categoria</th><th>Fornecedor</th><th>Preço</th><th>Estoque</th><th>Ações</th></tr></thead>
      <tbody>
      <?php foreach ($produtos as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['nome']) ?></td>
          <td><?= htmlspecialchars($p['categoria']) ?></td>
          <td><?= htmlspecialchars($p['fornecedor'] ?? '-') ?></td>
          <td>R$ <?= number_format($p['preco'],2,',','.') ?></td>
          <td>
            <?= (int)$p['estoque_atual'] ?>
            <?php if ($p['estoque_atual'] <= $p['estoque_minimo']): ?>
              <span class="badge low">baixo</span>
            <?php else: ?>
              <span class="badge ok">ok</span>
            <?php endif; ?>
          </td>
          <td>
            <form method="post" onsubmit="return true;">
              <input type="hidden" name="acao" value="excluir">
              <input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button class="btn-danger" data-confirm="Deseja realmente desativar este produto?">Desativar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </article>
</div>

<?php include 'includes/footer.php'; ?>
