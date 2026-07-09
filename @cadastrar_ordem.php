<?php
require_once 'config/database.php';
$conn = getConnection();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn->begin_transaction();
        
        // Coletar dados do formulário
        $numero_ordem = $conn->real_escape_string($_POST['numero_ordem']);
        $data_recebimento = $conn->real_escape_string($_POST['data_recebimento']);
        $supplier = $conn->real_escape_string($_POST['supplier']);
        $warehouse = $conn->real_escape_string($_POST['warehouse']);
        $packing_slip = $conn->real_escape_string($_POST['packing_slip']);
        $purchase_order = $conn->real_escape_string($_POST['purchase_order']);
        $receipt_no = $conn->real_escape_string($_POST['receipt_no']);
        $production_date = $conn->real_escape_string($_POST['production_date']);
        
        // Verificar se ordem já existe
        $check_query = "SELECT id FROM ordens_compra WHERE numero_ordem = '$numero_ordem'";
        if ($conn->query($check_query)->num_rows > 0) {
            throw new Exception("Número de ordem já cadastrado!");
        }
        
        // Inserir ordem com todos os campos
        $ordem_query = "INSERT INTO ordens_compra 
                       (numero_ordem, data_recebimento, supplier, warehouse, 
                        packing_slip, purchase_order, receipt_no, production_date) 
                       VALUES ('$numero_ordem', '$data_recebimento', '$supplier', 
                               '$warehouse', '$packing_slip', '$purchase_order', 
                               '$receipt_no', " . ($production_date ? "'$production_date'" : "NULL") . ")";
        
        if (!$conn->query($ordem_query)) {
            throw new Exception("Erro ao cadastrar ordem: " . $conn->error);
        }
        
        $ordem_id = $conn->insert_id;
        
        // Processar itens
        $componentes = $_POST['componente_id'];
        $received_weights = $_POST['received_weight'];
        $pitches = $_POST['pitch'];
        $bl_weights = $_POST['bl_sheet_weight'];
        
        for ($i = 0; $i < count($componentes); $i++) {
            if (!empty($componentes[$i]) && !empty($received_weights[$i])) {
                $componente_id = intval($componentes[$i]);
                $received_weight = floatval(str_replace(',', '.', $received_weights[$i]));
                $pitch = !empty($pitches[$i]) ? floatval(str_replace(',', '.', $pitches[$i])) : NULL;
                $bl_weight = !empty($bl_weights[$i]) ? floatval(str_replace(',', '.', $bl_weights[$i])) : NULL;
                
                // Buscar g_weight do componente
                $comp_query = "SELECT g_weight FROM componentes_estampados WHERE id = $componente_id";
                $comp_result = $conn->query($comp_query);
                
                if ($comp_result->num_rows > 0) {
                    $comp_data = $comp_result->fetch_assoc();
                    $g_weight = floatval($comp_data['g_weight']);
                    
                    // Atualizar pitch e bl_sheet_weight no componente se fornecidos
                    if ($pitch !== NULL || $bl_weight !== NULL) {
                        $update_query = "UPDATE componentes_estampados SET ";
                        $updates = [];
                        if ($pitch !== NULL) $updates[] = "pitch = $pitch";
                        if ($bl_weight !== NULL) $updates[] = "bl_sheet_weight = $bl_weight";
                        $update_query .= implode(', ', $updates) . " WHERE id = $componente_id";
                        $conn->query($update_query);
                    }
                    
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

// Buscar componentes
$componentes_query = "SELECT id, stampers_part_number, parts_name, spec, g_weight, pitch, bl_sheet_weight 
                     FROM componentes_estampados 
                     ORDER BY stampers_part_number";
$componentes_result = $conn->query($componentes_query);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Ordem - Etiqueta Completa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 10px; }
        .etiqueta-preview { 
            border: 1px solid #ddd; 
            padding: 20px; 
            background: white;
            font-family: Arial, sans-serif;
            width: 400px;
            margin: 0 auto;
        }
        .info-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 10px; 
            margin-bottom: 15px;
        }
        .info-item { margin-bottom: 5px; }
        .label { font-weight: bold; font-size: 11px; color: #333; }
        .value { font-size: 12px; color: #000; }
        .table-spec { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 11px;
            margin-top: 10px;
        }
        .table-spec th { 
            background: #f0f0f0; 
            padding: 5px; 
            text-align: center;
            border: 1px solid #ddd;
        }
        .table-spec td { 
            padding: 5px; 
            text-align: center;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-boxes me-2"></i>Sistema de Etiquetas RAW Material
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Cadastrar Nova Ordem</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="formOrdem">
                            <h5 class="mb-3">Informações da Ordem</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Número da Ordem *</label>
                                    <input type="text" class="form-control" name="numero_ordem" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Data Recebimento *</label>
                                    <input type="date" class="form-control" name="data_recebimento" 
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Supplier *</label>
                                    <input type="text" class="form-control" name="supplier" 
                                           value="PN0000003" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Warehouse *</label>
                                    <input type="text" class="form-control" name="warehouse" 
                                           value="B94" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Packing Slip / Coil No</label>
                                    <input type="text" class="form-control" name="packing_slip">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Purchase Order *</label>
                                    <input type="text" class="form-control" name="purchase_order" 
                                           value="YAB000382/20/1" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Receipt No *</label>
                                    <input type="text" class="form-control" name="receipt_no" 
                                           value="R00251823" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Production Date</label>
                                    <input type="date" class="form-control" name="production_date">
                                </div>
                            </div>
                            
                            <hr>
                            <h5 class="mb-3">Itens da Ordem</h5>
                            
                            <div id="itens-container">
                                <div class="item-ordem border rounded p-3 mb-3">
                                    <div class="row g-2">
                                        <div class="col-md-5">
                                            <label class="form-label">Item (Part Number) *</label>
                                            <select class="form-select componente-select" name="componente_id[]" required>
                                                <option value="">Selecione...</option>
                                                <?php while ($row = $componentes_result->fetch_assoc()): ?>
                                                <option value="<?php echo $row['id']; ?>"
                                                        data-spec="<?php echo $row['spec']; ?>"
                                                        data-thickness="<?php echo $row['thickness']; ?>"
                                                        data-width="<?php echo $row['width']; ?>"
                                                        data-pitch="<?php echo $row['pitch']; ?>"
                                                        data-blweight="<?php echo $row['bl_sheet_weight']; ?>"
                                                        data-gweight="<?php echo $row['g_weight']; ?>">
                                                    <?php echo $row['stampers_part_number']; ?> - <?php echo $row['parts_name']; ?>
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Received Weight (kg) *</label>
                                            <input type="number" step="0.001" class="form-control received-weight" 
                                                   name="received_weight[]" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Pitch</label>
                                            <input type="number" step="0.01" class="form-control pitch" 
                                                   name="pitch[]">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">BL Sheet Weight</label>
                                            <input type="number" step="0.001" class="form-control bl-weight" 
                                                   name="bl_sheet_weight[]">
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-3">
                                            <small class="text-muted">Spec: <span class="spec-value">-</span></small>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">Thickness: <span class="thickness-value">-</span></small>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">Width: <span class="width-value">-</span></small>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">G Weight: <span class="gweight-value">-</span></small>
                                        </div>
                                    </div>
                                    <div class="row mt-1">
                                        <div class="col-md-12">
                                            <small class="text-success">Expected QTY: <span class="expected-qty">-</span></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <button type="button" class="btn btn-success btn-sm" id="add-item">
                                    <i class="fas fa-plus me-1"></i>Adicionar Item
                                </button>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Salvar Ordem e Gerar Etiqueta
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-tag me-2"></i>Pré-visualização da Etiqueta</h5>
                    </div>
                    <div class="card-body">
                        <div class="etiqueta-preview" id="etiquetaPreview">
                            <div style="text-align: center; margin-bottom: 15px;">
                                <h5 style="margin: 0; font-weight: bold; font-size: 14px;">
                                    # RAW Material Sheet
                                </h5>
                            </div>
                            
                            <div class="info-item">
                                <div class="label">Item:</div>
                                <div class="value" id="preview-item">-</div>
                            </div>
                            
                            <div class="info-item">
                                <div class="label">Supplier:</div>
                                <div class="value" id="preview-supplier">PN0000003</div>
                            </div>
                            
                            <div style="border-top: 1px solid #000; margin: 10px 0; padding-top: 10px;">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <div class="label">Warehouse:</div>
                                        <div class="value" id="preview-warehouse">B94</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">Packing Slip. / Coil No:</div>
                                        <div class="value" id="preview-packing">0</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">Purchase Order:</div>
                                        <div class="value" id="preview-purchase">YAB000382/20/1</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">Received Weight:</div>
                                        <div class="value" id="preview-received">-</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">Expected QTY.:</div>
                                        <div class="value" id="preview-expected">-</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">Receipt Date:</div>
                                        <div class="value" id="preview-receipt-date">-</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">Production Date:</div>
                                        <div class="value" id="preview-production">-</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">Receipt No.</div>
                                        <div class="value" id="preview-receipt-no">R00251823</div>
                                    </div>
                                </div>
                                
                                <table class="table-spec">
                                    <thead>
                                        <tr>
                                            <th>Spec.</th>
                                            <th>Thickness</th>
                                            <th>Width</th>
                                            <th>Pitch</th>
                                            <th>BL Sheet Weight</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td id="preview-spec">-</td>
                                            <td id="preview-thickness">-</td>
                                            <td id="preview-width">-</td>
                                            <td id="preview-pitch">-</td>
                                            <td id="preview-blweight">-</td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                <div style="margin-top: 10px; text-align: center;">
                                    <div style="display: inline-block; margin: 0 10px;">
                                        <div style="border: 1px solid #000; width: 20px; height: 20px; display: inline-block;"></div>
                                        <div style="font-size: 9px;">( )</div>
                                    </div>
                                    <div style="display: inline-block; margin: 0 10px;">
                                        <div style="border: 1px solid #000; width: 20px; height: 20px; display: inline-block;"></div>
                                        <div style="font-size: 9px;">( )</div>
                                    </div>
                                    <div style="display: inline-block; margin: 0 10px;">
                                        <div style="border: 1px solid #000; width: 20px; height: 20px; display: inline-block;"></div>
                                        <div style="font-size: 9px;">( )</div>
                                    </div>
                                    <div style="display: inline-block; margin: 0 10px;">
                                        <div style="border: 1px solid #000; width: 20px; height: 20px; display: inline-block;"></div>
                                        <div style="font-size: 9px;">( )</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <button class="btn btn-warning btn-sm" onclick="imprimirEtiqueta()">
                                <i class="fas fa-print me-1"></i>Imprimir Etiqueta
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template id="item-template">
        <div class="item-ordem border rounded p-3 mb-3">
            <div class="row g-2">
                <div class="col-md-5">
                    <label class="form-label">Item (Part Number) *</label>
                    <select class="form-select componente-select" name="componente_id[]" required>
                        <option value="">Selecione...</option>
                        <?php 
                        $componentes_result->data_seek(0);
                        while ($row = $componentes_result->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $row['id']; ?>"
                                data-spec="<?php echo $row['spec']; ?>"
                                data-thickness="<?php echo $row['thickness']; ?>"
                                data-width="<?php echo $row['width']; ?>"
                                data-pitch="<?php echo $row['pitch']; ?>"
                                data-blweight="<?php echo $row['bl_sheet_weight']; ?>"
                                data-gweight="<?php echo $row['g_weight']; ?>">
                            <?php echo $row['stampers_part_number']; ?> - <?php echo $row['parts_name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Received Weight (kg) *</label>
                    <input type="number" step="0.001" class="form-control received-weight" 
                           name="received_weight[]" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Pitch</label>
                    <input type="number" step="0.01" class="form-control pitch" 
                           name="pitch[]">
                </div>
                <div class="col-md-2">
                    <label class="form-label">BL Sheet Weight</label>
                    <input type="number" step="0.001" class="form-control bl-weight" 
                           name="bl_sheet_weight[]">
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-3">
                    <small class="text-muted">Spec: <span class="spec-value">-</span></small>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">Thickness: <span class="thickness-value">-</span></small>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">Width: <span class="width-value">-</span></small>
                </div>
                <div class="col-md-3">
                    <small class="text-muted">G Weight: <span class="gweight-value">-</span></small>
                </div>
            </div>
            <div class="row mt-1">
                <div class="col-md-12">
                    <small class="text-success">Expected QTY: <span class="expected-qty">-</span></small>
                    <button type="button" class="btn btn-sm btn-danger float-end remove-item">
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
            $(this).closest('.item-ordem').remove();
            atualizarPreview();
        });
        
        // Atualizar campos quando componente for selecionado
        $(document).on('change', '.componente-select', function() {
            const item = $(this).closest('.item-ordem');
            const selected = item.find('.componente-select option:selected');
            
            if (selected.val()) {
                // Atualizar campos do item
                item.find('.spec-value').text(selected.data('spec') || '-');
                item.find('.thickness-value').text(selected.data('thickness') || '-');
                item.find('.width-value').text(selected.data('width') || '-');
                item.find('.gweight-value').text(selected.data('gweight') || '-');
                
                // Preencher pitch e BL weight se existirem no banco
                if (selected.data('pitch')) {
                    item.find('.pitch').val(selected.data('pitch'));
                }
                if (selected.data('blweight')) {
                    item.find('.bl-weight').val(selected.data('blweight'));
                }
                
                // Se for o primeiro item, atualizar preview
                if (item.is(':first-child')) {
                    atualizarPreview();
                }
            }
        });
        
        // Calcular Expected QTY quando mudar received weight
        $(document).on('input', '.received-weight', function() {
            const item = $(this).closest('.item-ordem');
            const selected = item.find('.componente-select option:selected');
            const receivedWeight = parseFloat($(this).val()) || 0;
            const gWeight = parseFloat(selected.data('gweight')) || 1;
            
            if (receivedWeight > 0 && gWeight > 0) {
                const expectedQty = receivedWeight / gWeight;
                item.find('.expected-qty').text(expectedQty.toFixed(3));
                
                // Se for o primeiro item, atualizar preview
                if (item.is(':first-child')) {
                    atualizarPreview();
                }
            } else {
                item.find('.expected-qty').text('-');
            }
        });
        
        // Atualizar preview quando campos gerais mudarem
        $('input[name="supplier"], input[name="warehouse"], input[name="packing_slip"], ' +
          'input[name="purchase_order"], input[name="receipt_no"], ' +
          'input[name="data_recebimento"], input[name="production_date"]').on('input', function() {
            atualizarPreview();
        });
        
        function atualizarPreview() {
            // Pegar dados do primeiro item
            const primeiroItem = $('.item-ordem:first');
            const selected = primeiroItem.find('.componente-select option:selected');
            const receivedWeight = parseFloat(primeiroItem.find('.received-weight').val()) || 0;
            const gWeight = parseFloat(selected.data('gweight')) || 1;
            const expectedQty = receivedWeight > 0 && gWeight > 0 ? (receivedWeight / gWeight).toFixed(3) : '-';
            
            // Atualizar preview
            $('#preview-item').text(selected.val() ? selected.text().split(' - ')[0] : '-');
            $('#preview-supplier').text($('input[name="supplier"]').val());
            $('#preview-warehouse').text($('input[name="warehouse"]').val());
            $('#preview-packing').text($('input[name="packing_slip"]').val() || '0');
            $('#preview-purchase').text($('input[name="purchase_order"]').val());
            $('#preview-received').text(receivedWeight > 0 ? receivedWeight.toFixed(3) : '-');
            $('#preview-expected').text(expectedQty);
            $('#preview-receipt-date').text($('input[name="data_recebimento"]').val() ? 
                formatDate($('input[name="data_recebimento"]').val()) + ' 11:00:41' : '-');
            $('#preview-production').text($('input[name="production_date"]').val() ? 
                formatDate($('input[name="production_date"]').val()) : '-');
            $('#preview-receipt-no').text($('input[name="receipt_no"]').val());
            
            // Dados técnicos
            $('#preview-spec').text(selected.data('spec') || '-');
            $('#preview-thickness').text(selected.data('thickness') || '-');
            $('#preview-width').text(selected.data('width') || '-');
            $('#preview-pitch').text(primeiroItem.find('.pitch').val() || '-');
            $('#preview-blweight').text(primeiroItem.find('.bl-weight').val() || '-');
        }
        
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('pt-BR');
        }
        
        // Inicializar preview
        atualizarPreview();
    });
    
    function imprimirEtiqueta() {
        const printContent = document.getElementById('etiquetaPreview').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Imprimir Etiqueta</title>
                <style>
                    body { 
                        margin: 0; 
                        padding: 20px; 
                        font-family: Arial, sans-serif; 
                        background: white;
                    }
                    .etiqueta-container { 
                        width: 400px; 
                        margin: 0 auto; 
                        border: 1px solid #000;
                        padding: 20px;
                    }
                    @media print {
                        @page { margin: 0; }
                        body { padding: 0; }
                    }
                </style>
            </head>
            <body>
                <div class="etiqueta-container">
                    ${printContent}
                </div>
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(function() {
                            window.close();
                        }, 100);
                    };
                <\/script>
            </body>
            </html>
        `;
    }
    </script>
</body>
</html>