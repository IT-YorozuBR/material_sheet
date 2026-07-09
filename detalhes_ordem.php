<?php
require_once 'config/database.php';
$conn = getConnection();

$ordem_id = intval($_GET['id'] ?? 0);

// Buscar dados da ordem
$ordem_query = "SELECT * FROM ordens_compra WHERE id = $ordem_id";
$ordem_result = $conn->query($ordem_query);

if ($ordem_result->num_rows == 0) {
    die("Ordem não encontrada!");
}

$ordem = $ordem_result->fetch_assoc();

// Buscar itens da ordem
$itens_query = "
    SELECT r.*, 
           c.stampers_part_number, 
           c.parts_name, 
           c.spec,
           c.g_weight
    FROM recebimentos r
    INNER JOIN componentes_estampados c ON r.componente_id = c.id
    WHERE r.ordem_compra_id = $ordem_id
    ORDER BY r.received_at DESC";
$itens_result = $conn->query($itens_query);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Ordem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-3">
            <div class="col">
                <a href="listar_ordens.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Voltar para Lista
                </a>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-file-invoice me-2"></i>
                    Ordem de Compra: <?php echo $ordem['numero_ordem']; ?>
                </h4>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="info-card">
                            <h6><i class="fas fa-hashtag me-2"></i>Número da Ordem</h6>
                            <p class="fs-5"><?php echo $ordem['numero_ordem']; ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-card">
                            <h6><i class="fas fa-calendar-day me-2"></i>Data de Recebimento</h6>
                            <p class="fs-5"><?php echo date('d/m/Y', strtotime($ordem['data_recebimento'])); ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-card">
                            <h6><i class="fas fa-calendar-plus me-2"></i>Data de Cadastro</h6>
                            <p class="fs-5"><?php echo date('d/m/Y H:i', strtotime($ordem['created_at'])); ?></p>
                        </div>
                    </div>
                </div>

                <h5 class="border-bottom pb-2 mb-3">
                    <i class="fas fa-box-open me-2"></i>Itens Recebidos
                    <span class="badge bg-primary ms-2"><?php echo $itens_result->num_rows; ?> itens</span>
                </h5>

                <?php if ($itens_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Part Number</th>
                                <th>Descrição</th>
                                <th>Material</th>
                                <th>Peso Unitário (g)</th>
                                <th>Received Weight (g)</th>
                                <th>Expected QTY</th>
                                <th>Data/Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_weight = 0;
                            $total_qty = 0;
                            while ($item = $itens_result->fetch_assoc()): 
                                $total_weight += $item['received_weight'];
                                $total_qty += $item['expected_qty'];
                            ?>
                            <tr>
                                <td><strong><?php echo $item['stampers_part_number']; ?></strong></td>
                                <td><?php echo $item['parts_name']; ?></td>
                                <td><span class="badge bg-secondary"><?php echo $item['spec']; ?></span></td>
                                <td><?php echo number_format($item['g_weight'], 3, ',', '.'); ?>g</td>
                                <td><?php echo number_format($item['received_weight'], 3, ',', '.'); ?>g</td>
                                <td><span class="badge bg-success"><?php echo number_format($item['expected_qty'], 3, ',', '.'); ?></span></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($item['received_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot class="table-dark">
                            <tr>
                                <th colspan="4" class="text-end">TOTAIS:</th>
                                <th><?php echo number_format($total_weight, 3, ',', '.'); ?>g</th>
                                <th><?php echo number_format($total_qty, 3, ',', '.'); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6><i class="fas fa-weight me-2"></i>Resumo do Peso</h6>
                                <p class="mb-1">Total Recebido: <strong><?php echo number_format($total_weight, 3, ',', '.'); ?>g</strong></p>
                                <p>Média por Item: <strong><?php echo number_format($total_weight / $itens_result->num_rows, 3, ',', '.'); ?>g</strong></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6><i class="fas fa-calculator me-2"></i>Resumo da Quantidade</h6>
                                <p class="mb-1">Total Esperado: <strong><?php echo number_format($total_qty, 3, ',', '.'); ?></strong></p>
                                <p>Média por Item: <strong><?php echo number_format($total_qty / $itens_result->num_rows, 3, ',', '.'); ?></strong></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                    <h5>Nenhum item recebido nesta ordem</h5>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
    .info-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        height: 100%;
        border-left: 4px solid #0d6efd;
    }
    .info-card h6 {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 5px;
    }
    .info-card p {
        margin-bottom: 0;
    }
    </style>
</body>
</html>