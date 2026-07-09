<?php
require_once 'config/database.php';
$conn = getConnection();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Recebimento de Componentes</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="text-center">Sistema de Recebimento de Componentes</h3>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <img src="https://cdn-icons-png.flaticon.com/512/3050/3050520.png" 
                                 alt="Logo" width="100" class="mb-3">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">📦 Nova Ordem de Compra</h5>
                                        <p class="card-text">Cadastre uma nova ordem de compra e registre os recebimentos</p>
                                        <a href="cadastrar_ordem.php" class="btn btn-primary">Cadastrar Nova Ordem</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">📋 Ordens de Compra</h5>
                                        <p class="card-text">Visualize e gerencie as ordens de compra cadastradas</p>
                                        <a href="listar_ordens.php" class="btn btn-success">Ver Ordens</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">⚙️ Componentes</h5>
                                        <p class="card-text">Consulte e edite peso unitário e demais dados dos componentes</p>
                                        <a href="listar_componentes.php" class="btn btn-warning">Ver Componentes</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h5>📊 Estatísticas Rápidas</h5>
                            <div class="row">
                                <?php
                                // Consulta para estatísticas
                                $stats_query = "
                                    SELECT 
                                        (SELECT COUNT(*) FROM ordens_compra) as total_ordens,
                                        (SELECT COUNT(*) FROM recebimentos) as total_recebimentos,
                                        (SELECT COUNT(DISTINCT componente_id) FROM recebimentos) as componentes_unicos
                                ";
                                
                                $result = $conn->query($stats_query);
                                if ($row = $result->fetch_assoc()) {
                                ?>
                                <div class="col-md-4">
                                    <div class="stat-card">
                                        <h6>Total de Ordens</h6>
                                        <h3 class="text-primary"><?php echo $row['total_ordens']; ?></h3>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-card">
                                        <h6>Total de Recebimentos</h6>
                                        <h3 class="text-success"><?php echo $row['total_recebimentos']; ?></h3>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-card">
                                        <h6>Componentes Únicos</h6>
                                        <h3 class="text-warning"><?php echo $row['componentes_unicos']; ?></h3>
                                    </div>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <small>Sistema de Gerenciamento de Recebimentos © 2026</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>