# Sistema de Recebimento de Componentes

Aplicação em PHP + MySQL para controle de recebimento de componentes estampados: cadastro de ordens de compra, registro dos itens recebidos, cálculo automático de quantidade esperada e geração de etiquetas de material (RAW Material Sheet) para impressão.

## Requisitos

- PHP 8+ com extensão `mysqli`
- MySQL / MariaDB
- Servidor web (Apache/XAMPP) ou o servidor embutido do PHP

## Configuração

1. Crie o banco e as tabelas a partir do `schema.sql`:
   ```
   mysql -u root -p < schema.sql
   ```
2. Ajuste as credenciais em `config/database.php` (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`) se necessário.
3. Suba o servidor a partir da raiz do projeto:
   ```
   php -S localhost:8000
   ```
4. Acesse `http://localhost:8000/index.php`.

## Funcionalidades

- **Home** (`index.php`) — atalhos para as telas principais e estatísticas rápidas (total de ordens, recebimentos e componentes únicos).
- **Nova Ordem de Compra** (`cadastrar_ordem.php`) — cadastra uma ordem (número + data de recebimento) e um ou mais itens recebidos, calculando `expected_qty` a partir do peso recebido e do `g_weight` do componente.
- **Ordens de Compra** (`listar_ordens.php`) — lista as ordens cadastradas com totais de peso/quantidade, acesso aos detalhes e exclusão (`excluir_ordem.php`).
- **Detalhes da Ordem** (`detalhes_ordem.php`) — mostra os itens recebidos de uma ordem específica com totais e médias.
- **Componentes** (`listar_componentes.php`) — lista os componentes estampados cadastrados e permite editar peso unitário, thickness, width, pitch e bl_sheet_weight.
- **Etiqueta de Ordem** (`gerar_etiqueta_ordem.php`) — gera a etiqueta de material pronta para impressão de um item recebido (suporta `?auto_print=1` para imprimir automaticamente).
- **Lista de Ordens com Etiquetas** (`listar_ordens_com_etiquetas.php`) — lista alternativa focada em gerar/imprimir etiquetas por ordem.

## Estrutura do Banco (`schema.sql`)

- `componentes_estampados` — cadastro dos componentes (part number, nome, spec, peso unitário e dimensões).
- `ordens_compra` — ordens de compra/recebimento.
- `recebimentos` — itens recebidos, vinculados a uma ordem e a um componente.

## Estrutura de Pastas

```
config/database.php   Conexão com o banco (mysqli) e configuração de sessão
css/style.css         Estilos compartilhados
*.php                 Páginas da aplicação (uma por funcionalidade)
schema.sql            Schema do banco + dados de exemplo
```
