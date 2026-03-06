# Sistema de Controle de Estoque e Vendas - Moda Praia

Projeto em **PHP + MySQL (XAMPP)** com interface moderna em **HTML, CSS e JavaScript**.

## Funcionalidades
- CRUD de produtos.
- CRUD de fornecedores.
- Entrada e saída de estoque.
- Registro de vendas com carrinho simples.
- Relatório mensal de vendas e produtos vendidos.
- Regras de negócio:
  - Baixa automática de estoque ao vender.
  - Bloqueio de venda sem estoque.
- Extras:
  - Alertas de estoque mínimo.
  - Dashboard com Top 5 vendidos no mês.

## Banco de dados
Tabelas: `produtos`, `categorias`, `fornecedores`, `movimentacoes`, `vendas`, `itens_venda`.

Arquivo de criação: `database/schema.sql`.

## Como rodar no XAMPP
1. Copie a pasta do projeto para `htdocs`.
2. Inicie **Apache** e **MySQL** no XAMPP.
3. Abra o phpMyAdmin e execute o script `database/schema.sql`.
4. Ajuste credenciais no arquivo `config/database.php` se necessário.
5. Acesse no navegador: `http://localhost/gls-moda-praia`.

## Estrutura de telas
- `index.php`: dashboard + alertas + top 5 vendidos.
- `produtos.php`: cadastro e listagem de produtos.
- `fornecedores.php`: cadastro e listagem de fornecedores.
- `estoque.php`: movimentação e posição de estoque.
- `vendas.php`: carrinho e finalização de venda.
- `relatorio.php`: relatório mensal.
