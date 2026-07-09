<?php
require_once 'config/database.php';
$conn = getConnection();

// Buscar todas as ordens
$query = "SELECT oc.*, COUNT(r.id) as total_itens 
          FROM ordens_compra oc
          LEFT JOIN recebimentos r ON oc.id = r.ordem_compra_id
          GROUP BY oc.id
          ORDER BY oc.data_recebimento DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Ordens - Sistema de Etiquetas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">Ordens de Compra</h2>
        
        <div class="table-responsive">
            <table class="table table-striped" id="tabelaOrdens">
                <thead>
                    <tr>
                        <th>Nº Ordem</th>
                        <th>Supplier</th>
                        <th>Receipt No</th>
                        <th>Data Recebimento</th>
                        <th>Itens</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo $row['numero_ordem']; ?></strong></td>
                        <td><?php echo $row['supplier']; ?></td>
                        <td><?php echo $row['receipt_no']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['data_recebimento'])); ?></td>
                        <td><span class="badge bg-info"><?php echo $row['total_itens']; ?></span></td>
                        <td>
                            <a href="gerar_etiqueta_ordem.php?id=<?php echo $row['id']; ?>" 
                               class="btn btn-sm btn-warning" target="_blank">
                                <i class="fas fa-tag"></i> Etiqueta
                            </a>
                            <a href="gerar_etiqueta_ordem.php?id=<?php echo $row['id']; ?>&auto_print=1" 
                               class="btn btn-sm btn-success" target="_blank">
                                <i class="fas fa-print"></i> Imprimir
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tabelaOrdens').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
                },
                order: [[3, 'desc']]
            });
        });
    </script>
</body>
</html>