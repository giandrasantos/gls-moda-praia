<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Moda Praia • Estoque e Vendas</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="topbar">
    <h1>🌴 Moda Praia</h1>
    <p>Controle de Estoque e Vendas</p>
  </header>
  <nav class="menu">
    <a href="index.php">Dashboard</a>
    <a href="produtos.php">Produtos</a>
    <a href="fornecedores.php">Fornecedores</a>
    <a href="estoque.php">Estoque</a>
    <a href="vendas.php">Registrar Venda</a>
    <a href="relatorio.php">Relatório Mensal</a>
  </nav>
  <main class="container">
