<?php
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'criar') {
        $st = $pdo->prepare('INSERT INTO fornecedores (nome, email, telefone, cidade) VALUES (?, ?, ?, ?)');
        $st->execute([
            trim($_POST['nome'] ?? ''),
            trim($_POST['email'] ?? ''),
            trim($_POST['telefone'] ?? ''),
            trim($_POST['cidade'] ?? '')
        ]);
        setFlash('success', 'Fornecedor cadastrado com sucesso.');
    }
    if ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM fornecedores WHERE id = ?')->execute([$id]);
        setFlash('success', 'Fornecedor removido com sucesso.');
    }
    header('Location: fornecedores.php');
    exit;
}

$fornecedores = getFornecedores($pdo);
include 'includes/header.php';
?>

<?php flashMessage(); ?>
<div class="grid">
  <form class="card" method="post">
    <h3>Novo Fornecedor</h3>
    <input type="hidden" name="acao" value="criar">
    <input type="text" name="nome" placeholder="Nome" required>
    <input type="email" name="email" placeholder="E-mail">
    <input type="text" name="telefone" placeholder="Telefone">
    <input type="text" name="cidade" placeholder="Cidade">
    <button type="submit">Salvar</button>
  </form>

  <article class="card table-wrap" style="grid-column: span 2;">
    <h3>Fornecedores</h3>
    <table>
      <thead><tr><th>Nome</th><th>Ações</th></tr></thead>
      <tbody>
      <?php foreach ($fornecedores as $f): ?>
        <tr>
          <td><?= htmlspecialchars($f['nome']) ?></td>
          <td>
            <form method="post">
              <input type="hidden" name="acao" value="excluir">
              <input type="hidden" name="id" value="<?= $f['id'] ?>">
              <button class="btn-danger" data-confirm="Excluir fornecedor?">Excluir</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </article>
</div>

<?php include 'includes/footer.php'; ?>
