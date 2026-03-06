<?php
require_once __DIR__ . '/../config/database.php';

function flashMessage(): void
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $type = htmlspecialchars($flash['type']);
        $message = htmlspecialchars($flash['message']);
        echo "<div class='alert {$type}'>{$message}</div>";
    }
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getCategorias(PDO $pdo): array
{
    return $pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();
}

function getFornecedores(PDO $pdo): array
{
    return $pdo->query('SELECT id, nome FROM fornecedores ORDER BY nome')->fetchAll();
}

function getProdutosAtivos(PDO $pdo): array
{
    $sql = 'SELECT p.id, p.nome, p.preco, p.estoque_atual, p.sku FROM produtos p WHERE ativo = 1 ORDER BY nome';
    return $pdo->query($sql)->fetchAll();
}
