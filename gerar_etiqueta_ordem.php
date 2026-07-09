<?php
require_once 'config/database.php';
$conn = getConnection();

$ordem_id = intval($_GET['id'] ?? 0);
$numero_ordem = $_GET['numero_ordem'] ?? '';

if ($ordem_id > 0 || !empty($numero_ordem)) {
    // Construir consulta
    $where = $ordem_id > 0 ? "oc.id = $ordem_id" : "oc.numero_ordem = '" . $conn->real_escape_string($numero_ordem) . "'";
    
    $query = "
        SELECT 
            oc.*,
            c.stampers_part_number,
            c.parts_name,
            c.spec,
            c.thickness,
            c.width,
            c.g_weight,
            c.pitch,
            c.bl_sheet_weight,
            r.received_weight,
            r.expected_qty,
            DATE_FORMAT(oc.data_recebimento, '%d/%m/%Y') as data_recebimento_formatada,
            DATE_FORMAT(oc.production_date, '%d/%m/%Y') as production_date_formatada
        FROM 
            ordens_compra oc
        INNER JOIN 
            recebimentos r ON oc.id = r.ordem_compra_id
        INNER JOIN 
            componentes_estampados c ON r.componente_id = c.id
        WHERE 
            $where
        ORDER BY 
            r.received_at DESC
        LIMIT 1";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $dados = $result->fetch_assoc();
        
        // Formatar valores
        $received_weight = number_format($dados['received_weight'], 3, ',', '');
        $expected_qty = number_format($dados['expected_qty'], 3, ',', '');
        $thickness = str_replace('.', ',', $dados['thickness']);
        $width = str_replace('.', ',', $dados['width']);
        $pitch = $dados['pitch'] ? str_replace('.', ',', $dados['pitch']) : '-';
        $bl_weight = $dados['bl_sheet_weight'] ? str_replace('.', ',', $dados['bl_sheet_weight']) : '-';
    } else {
        die("Ordem não encontrada!");
    }
} else {
    die("Parâmetros inválidos!");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiqueta RAW Material - <?php echo $dados['numero_ordem']; ?></title>
    <style>
        @media print {
            @page { margin: 0; }
            body { margin: 0; padding: 10px; }
            .no-print { display: none !important; }
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .etiqueta-container {
            width: 400px;
            margin: 0 auto;
            background: white;
            padding: 25px;
            border: 1px solid #ccc;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        
        .info-section {
            margin-bottom: 15px;
        }
        
        .info-item {
            margin-bottom: 5px;
        }
        
        .label {
            font-weight: bold;
            font-size: 11px;
            color: #333;
            display: inline-block;
            width: 150px;
        }
        
        .value {
            font-size: 12px;
            color: #000;
            display: inline-block;
        }
        
        .divider {
            border-top: 1px solid #000;
            margin: 15px 0;
            padding-top: 15px;
        }
        
        .grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .spec-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 11px;
        }
        
        .spec-table th {
            background: #f0f0f0;
            padding: 8px 5px;
            text-align: center;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        
        .spec-table td {
            padding: 8px 5px;
            text-align: center;
            border: 1px solid #ddd;
        }
        
        .checkboxes {
            text-align: center;
            margin-top: 20px;
        }
        
        .checkbox-item {
            display: inline-block;
            margin: 0 15px;
            text-align: center;
        }
        
        .checkbox-box {
            width: 20px;
            height: 20px;
            border: 1px solid #000;
            margin: 0 auto 5px;
            display: block;
        }
        
        .checkbox-label {
            font-size: 9px;
        }
        
        .controls {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            background: white;
            border-radius: 5px;
        }
        
        .btn {
            padding: 10px 20px;
            margin: 0 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-print {
            background: #007bff;
            color: white;
        }
        
        .btn-close {
            background: #6c757d;
            color: white;
        }
        
        .timestamp {
            font-size: 10px;
            color: #666;
            text-align: right;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="controls no-print">
        <h3>Etiqueta RAW Material Sheet</h3>
        <p>Ordem: <strong><?php echo $dados['numero_ordem']; ?></strong></p>
        <button class="btn btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Imprimir Etiqueta
        </button>
        <button class="btn btn-close" onclick="window.close()">
            <i class="fas fa-times"></i> Fechar
        </button>
        <p><small>Configure a impressão para: Papel A4, Margens mínimas, Escala 100%</small></p>
    </div>
    
    <div class="etiqueta-container">
        <div class="header">
            <h1># RAW Material Sheet</h1>
        </div>
        
        <div class="info-section">
            <div class="info-item">
                <span class="label">Item:</span>
                <span class="value"><?php echo $dados['stampers_part_number']; ?></span>
            </div>
            
            <div class="info-item">
                <span class="label">Supplier:</span>
                <span class="value"><?php echo $dados['supplier']; ?></span>
            </div>
        </div>
        
        <div class="divider"></div>
        
        <div class="grid-2col">
            <div class="info-item">
                <span class="label">Warehouse:</span>
                <span class="value"><?php echo $dados['warehouse']; ?></span>
            </div>
            
            <div class="info-item">
                <span class="label">Packing Slip. / Coil No:</span>
                <span class="value"><?php echo $dados['packing_slip'] ?: '0'; ?></span>
            </div>
            
            <div class="info-item">
                <span class="label">Purchase Order:</span>
                <span class="value"><?php echo $dados['purchase_order']; ?></span>
            </div>
            
            <div class="info-item">
                <span class="label">Received Weight:</span>
                <span class="value"><?php echo $received_weight; ?></span>
            </div>
            
            <div class="info-item">
                <span class="label">Expected QTY.:</span>
                <span class="value"><?php echo $expected_qty; ?></span>
            </div>
            
            <div class="info-item">
                <span class="label">Receipt Date:</span>
                <span class="value"><?php echo $dados['data_recebimento_formatada']; ?> 11:00:41</span>
            </div>
            
            <div class="info-item">
                <span class="label">Production Date:</span>
                <span class="value"><?php echo $dados['production_date_formatada'] ?: ''; ?></span>
            </div>
            
            <div class="info-item">
                <span class="label">Receipt No.</span>
                <span class="value"><?php echo $dados['receipt_no']; ?></span>
            </div>
        </div>
        
        <table class="spec-table">
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
                    <td><?php echo $dados['spec']; ?></td>
                    <td><?php echo $thickness; ?></td>
                    <td><?php echo $width; ?></td>
                    <td><?php echo $pitch; ?></td>
                    <td><?php echo $bl_weight; ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="checkboxes">
            <div class="checkbox-item">
                <div class="checkbox-box"></div>
                <div class="checkbox-label">( )</div>
            </div>
            <div class="checkbox-item">
                <div class="checkbox-box"></div>
                <div class="checkbox-label">( )</div>
            </div>
            <div class="checkbox-item">
                <div class="checkbox-box"></div>
                <div class="checkbox-label">( )</div>
            </div>
            <div class="checkbox-item">
                <div class="checkbox-box"></div>
                <div class="checkbox-label">( )</div>
            </div>
        </div>
        
        <div class="timestamp">
            Gerado em: <?php echo date('d/m/Y H:i:s'); ?>
        </div>
    </div>
    
    <?php if (isset($_GET['auto_print']) && $_GET['auto_print'] == '1'): ?>
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
    <?php endif; ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>