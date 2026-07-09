<?php
require_once 'config/database.php';
$conn = getConnection();

// Buscar todas as ordens
$ordens_query = "SELECT oc.*, 
                 COUNT(r.id) as total_itens,
                 SUM(r.received_weight) as total_weight,
                 SUM(r.expected_qty) as total_qty
                 FROM ordens_compra oc
                 LEFT JOIN recebimentos r ON oc.id = r.ordem_compra_id
                 GROUP BY oc.id
                 ORDER BY oc.data_recebimento DESC, oc.created_at DESC";
$ordens_result = $conn->query($ordens_query);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordens de Compra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-boxes me-2"></i>Recebimento de Componentes
            </a>
            <div class="navbar-nav">
                <a class="nav-link" href="index.php">Home</a>
                <a class="nav-link" href="cadastrar_ordem.php">Nova Ordem</a>
                <a class="nav-link active" href="listar_ordens.php">Ordens</a>
                <a class="nav-link" href="listar_componentes.php">Componentes</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-list me-2"></i>Ordens de Compra</h4>
                <a href="cadastrar_ordem.php" class="btn btn-light">
                    <i class="fas fa-plus me-1"></i>Nova Ordem
                </a>
            </div>
            <div class="card-body">
                <?php if ($ordens_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="tabelaOrdens">
                        <thead class="table-light">
                            <tr>
                                <th>Nº Ordem</th>
                                <th>Data Recebimento</th>
                                <th>Itens</th>
                                <th>Total Weight (g)</th>
                                <th>Total QTY</th>
                                <th>Data Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($ordem = $ordens_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $ordem['numero_ordem']; ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($ordem['data_recebimento'])); ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $ordem['total_itens'] ?? 0; ?> itens
                                    </span>
                                </td>
                                <td><?php echo number_format($ordem['total_weight'] ?? 0, 3, ',', '.'); ?>g</td>
                                <td><?php echo number_format($ordem['total_qty'] ?? 0, 3, ',', '.'); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($ordem['created_at'])); ?></td>
                                <td>
                                    <a href="detalhes_ordem.php?id=<?php echo $ordem['id']; ?>" 
                                       class="btn btn-sm btn-info" title="Ver detalhes">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="confirmarExclusao(<?php echo $ordem['id']; ?>, '<?php echo $ordem['numero_ordem']; ?>')"
                                            title="Excluir ordem">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <h5>Nenhuma ordem de compra cadastrada</h5>
                    <p>Clique no botão "Nova Ordem" para começar</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação -->
    <div class="modal fade" id="modalExcluir" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir a ordem <strong id="ordemNumero"></strong>?</p>
                    <p class="text-danger"><small>Esta ação não pode ser desfeita e excluirá todos os itens associados.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="btnConfirmarExclusao" class="btn btn-danger">Excluir</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#tabelaOrdens').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
            },
            order: [[5, 'desc']]
        });
    });
    
    function confirmarExclusao(id, numero) {
        document.getElementById('ordemNumero').textContent = numero;
        document.getElementById('btnConfirmarExclusao').href = 'excluir_ordem.php?id=' + id;
        new bootstrap.Modal(document.getElementById('modalExcluir')).show();
    }
    </script>
</body>
</html>