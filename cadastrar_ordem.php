<?php
require_once 'config/database.php';
$conn = getConnection();

$message = '';
$message_type = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Iniciar transação
        $conn->begin_transaction();
        
        // Cadastrar ordem de compra
        $numero_ordem = $conn->real_escape_string($_POST['numero_ordem']);
        $data_recebimento = $conn->real_escape_string($_POST['data_recebimento']);
        
        // Verificar se ordem já existe
        $check_query = "SELECT id FROM ordens_compra WHERE numero_ordem = '$numero_ordem'";
        $check_result = $conn->query($check_query);
        
        if ($check_result->num_rows > 0) {
            throw new Exception("Número de ordem já cadastrado!");
        }
        
        // Inserir ordem
        $ordem_query = "INSERT INTO ordens_compra (numero_ordem, data_recebimento) 
                       VALUES ('$numero_ordem', '$data_recebimento')";
        
        if (!$conn->query($ordem_query)) {
            throw new Exception("Erro ao cadastrar ordem: " . $conn->error);
        }
        
        $ordem_id = $conn->insert_id;
        
        // Processar itens
        $componentes = $_POST['componente_id'];
        $received_weights = $_POST['received_weight'];
        
        for ($i = 0; $i < count($componentes); $i++) {
            if (!empty($componentes[$i]) && !empty($received_weights[$i])) {
                $componente_id = intval($componentes[$i]);
                $received_weight = floatval(str_replace(',', '.', $received_weights[$i]));
                
                // Buscar g_weight do componente
                $comp_query = "SELECT g_weight FROM componentes_estampados WHERE id = $componente_id";
                $comp_result = $conn->query($comp_query);
                
                if ($comp_result->num_rows > 0) {
                    $comp_data = $comp_result->fetch_assoc();
                    $g_weight = floatval($comp_data['g_weight']);
                    
                    // Calcular quantidade esperada
                    $expected_qty = $received_weight / $g_weight;
                    
                    // Inserir recebimento
                    $recebimento_query = "INSERT INTO recebimentos 
                                         (ordem_compra_id, componente_id, received_weight, expected_qty) 
                                         VALUES ($ordem_id, $componente_id, $received_weight, $expected_qty)";
                    
                    if (!$conn->query($recebimento_query)) {
                        throw new Exception("Erro ao cadastrar recebimento: " . $conn->error);
                    }
                }
            }
        }
        
        $conn->commit();
        $message = "Ordem de compra e itens cadastrados com sucesso!";
        $message_type = "success";
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = $e->getMessage();
        $message_type = "danger";
    }
}

// Buscar componentes para o select
$componentes_query = "SELECT id, stampers_part_number, parts_name, spec, g_weight 
                     FROM componentes_estampados 
                     ORDER BY stampers_part_number";
$componentes_result = $conn->query($componentes_query);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Ordem de Compra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 10px; }
        .componente-item { transition: all 0.3s; }
        .componente-item:hover { background-color: #f1f3f4; }
        .btn-add { margin-top: 30px; }
        .expected-qty { font-weight: bold; color: #198754; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-boxes me-2"></i>Recebimento de Componentes
            </a>
            <div class="navbar-nav">
                <a class="nav-link" href="index.php">Home</a>
                <a class="nav-link active" href="cadastrar_ordem.php">Nova Ordem</a>
                <a class="nav-link" href="listar_ordens.php">Ordens</a>
                <a class="nav-link" href="listar_componentes.php">Componentes</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Cadastrar Nova Ordem de Compra</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="numero_ordem" class="form-label">Número da Ordem de Compra *</label>
                            <input type="text" class="form-control" id="numero_ordem" 
                                   name="numero_ordem" required placeholder="Ex: OC-2024-001">
                        </div>
                        <div class="col-md-6">
                            <label for="data_recebimento" class="form-label">Data do Recebimento *</label>
                            <input type="date" class="form-control" id="data_recebimento" 
                                   name="data_recebimento" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <hr>
                    <h5 class="mb-3"><i class="fas fa-boxes me-2"></i>Itens da Ordem</h5>
                    
                    <div id="itens-container">
                        <div class="componente-item border rounded p-3 mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Componente *</label>
                                    <select class="form-select componente-select" name="componente_id[]" required>
                                        <option value="">Selecione um componente...</option>
                                        <?php while ($row = $componentes_result->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>" 
                                                data-gweight="<?php echo $row['g_weight']; ?>">
                                            <?php echo $row['stampers_part_number']; ?> - 
                                            <?php echo $row['parts_name']; ?> 
                                            (<?php echo $row['spec']; ?>)
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Peso Unitário (g)</label>
                                    <input type="text" class="form-control g-weight" readonly 
                                           placeholder="Selecione o componente">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Received Weight (g) *</label>
                                    <input type="number" step="0.001" class="form-control received-weight" 
                                           name="received_weight[]" required placeholder="Ex: 1000">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Expected QTY</label>
                                    <div class="expected-qty form-control-plaintext">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-success" id="add-item">
                                <i class="fas fa-plus me-1"></i>Adicionar Item
                            </button>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="index.php" class="btn btn-secondary me-2">
                                <i class="fas fa-arrow-left me-1"></i>Voltar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Salvar Ordem
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Template para novos itens -->
    <template id="item-template">
        <div class="componente-item border rounded p-3 mb-3">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Componente *</label>
                    <select class="form-select componente-select" name="componente_id[]" required>
                        <option value="">Selecione um componente...</option>
                        <?php 
                        // Reset pointer do resultado
                        $componentes_result->data_seek(0);
                        while ($row = $componentes_result->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>" 
                                data-gweight="<?php echo $row['g_weight']; ?>">
                            <?php echo $row['stampers_part_number']; ?> - 
                            <?php echo $row['parts_name']; ?> 
                            (<?php echo $row['spec']; ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Peso Unitário (g)</label>
                    <input type="text" class="form-control g-weight" readonly 
                           placeholder="Selecione o componente">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Received Weight (g) *</label>
                    <input type="number" step="0.001" class="form-control received-weight" 
                           name="received_weight[]" required placeholder="Ex: 1000">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Expected QTY</label>
                    <div class="expected-qty form-control-plaintext">-</div>
                    <button type="button" class="btn btn-sm btn-danger mt-1 remove-item">
                        <i class="fas fa-trash"></i> Remover
                    </button>
                </div>
            </div>
        </div>
    </template>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Adicionar novo item
        $('#add-item').click(function() {
            const template = document.getElementById('item-template');
            const clone = template.content.cloneNode(true);
            $('#itens-container').append(clone);
        });
        
        // Remover item
        $(document).on('click', '.remove-item', function() {
            $(this).closest('.componente-item').remove();
        });
        
        // Calcular Expected QTY quando mudar componente ou peso
        $(document).on('change', '.componente-select, input.received-weight', function() {
            const item = $(this).closest('.componente-item');
            const selected = item.find('.componente-select option:selected');
            const gWeight = selected.data('gweight');
            const receivedWeight = item.find('.received-weight').val();
            
            // Atualizar campo de peso unitário
            item.find('.g-weight').val(gWeight || '');
            
            // Calcular expected qty se ambos valores existirem
            if (gWeight && receivedWeight) {
                const expectedQty = receivedWeight / gWeight;
                item.find('.expected-qty').text(expectedQty.toFixed(3));
            } else {
                item.find('.expected-qty').text('-');
            }
        });
    });
    </script>
</body>
</html>