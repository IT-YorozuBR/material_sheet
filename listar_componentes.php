<?php
require_once 'config/database.php';
$conn = getConnection();

$message = '';
$message_type = '';

// Processar edição
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = intval($_POST['id']);
    $g_weight = floatval(str_replace(',', '.', $_POST['g_weight']));
    $thickness = $_POST['thickness'] !== '' ? floatval(str_replace(',', '.', $_POST['thickness'])) : NULL;
    $width = $_POST['width'] !== '' ? floatval(str_replace(',', '.', $_POST['width'])) : NULL;
    $pitch = $_POST['pitch'] !== '' ? floatval(str_replace(',', '.', $_POST['pitch'])) : NULL;
    $bl_sheet_weight = $_POST['bl_sheet_weight'] !== '' ? floatval(str_replace(',', '.', $_POST['bl_sheet_weight'])) : NULL;

    if ($g_weight <= 0) {
        $message = "Peso unitário (g_weight) deve ser maior que zero!";
        $message_type = "danger";
    } else {
        $stmt = $conn->prepare(
            "UPDATE componentes_estampados
             SET g_weight = ?, thickness = ?, width = ?, pitch = ?, bl_sheet_weight = ?
             WHERE id = ?"
        );
        $stmt->bind_param("dddddi", $g_weight, $thickness, $width, $pitch, $bl_sheet_weight, $id);

        if ($stmt->execute()) {
            $message = "Componente atualizado com sucesso!";
            $message_type = "success";
        } else {
            $message = "Erro ao atualizar componente: " . $conn->error;
            $message_type = "danger";
        }
        $stmt->close();
    }
}

$componentes_query = "SELECT id, stampers_part_number, parts_name, spec, g_weight, thickness, width, pitch, bl_sheet_weight
                     FROM componentes_estampados
                     ORDER BY stampers_part_number";
$componentes_result = $conn->query($componentes_query);
$componentes = [];
while ($row = $componentes_result->fetch_assoc()) {
    $componentes[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Componentes Estampados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 10px; }
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
                <a class="nav-link" href="cadastrar_ordem.php">Nova Ordem</a>
                <a class="nav-link" href="listar_ordens.php">Ordens</a>
                <a class="nav-link active" href="listar_componentes.php">Componentes</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-cogs me-2"></i>Componentes Estampados</h4>
            </div>
            <div class="card-body">
                <table id="tabela-componentes" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Part Number</th>
                            <th>Nome</th>
                            <th>Spec</th>
                            <th>Peso Unitário (g)</th>
                            <th>Thickness</th>
                            <th>Width</th>
                            <th>Pitch</th>
                            <th>BL Sheet Weight</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($componentes as $c): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c['stampers_part_number']); ?></td>
                            <td><?php echo htmlspecialchars($c['parts_name']); ?></td>
                            <td><?php echo htmlspecialchars($c['spec']); ?></td>
                            <td><?php echo htmlspecialchars($c['g_weight']); ?></td>
                            <td><?php echo htmlspecialchars($c['thickness']); ?></td>
                            <td><?php echo htmlspecialchars($c['width']); ?></td>
                            <td><?php echo htmlspecialchars($c['pitch']); ?></td>
                            <td><?php echo htmlspecialchars($c['bl_sheet_weight']); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary btn-editar"
                                        data-id="<?php echo $c['id']; ?>"
                                        data-part="<?php echo htmlspecialchars($c['stampers_part_number']); ?>"
                                        data-nome="<?php echo htmlspecialchars($c['parts_name']); ?>"
                                        data-gweight="<?php echo htmlspecialchars($c['g_weight']); ?>"
                                        data-thickness="<?php echo htmlspecialchars($c['thickness']); ?>"
                                        data-width="<?php echo htmlspecialchars($c['width']); ?>"
                                        data-pitch="<?php echo htmlspecialchars($c['pitch']); ?>"
                                        data-blweight="<?php echo htmlspecialchars($c['bl_sheet_weight']); ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalEditar">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal de Edição -->
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Componente: <span id="edit-part"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted" id="edit-nome"></p>
                        <div class="mb-3">
                            <label class="form-label">Peso Unitário - g_weight (g) *</label>
                            <input type="number" step="0.0001" class="form-control" name="g_weight" id="edit-gweight" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Thickness</label>
                                <input type="number" step="0.0001" class="form-control" name="thickness" id="edit-thickness">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Width</label>
                                <input type="number" step="0.0001" class="form-control" name="width" id="edit-width">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pitch</label>
                                <input type="number" step="0.0001" class="form-control" name="pitch" id="edit-pitch">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">BL Sheet Weight</label>
                                <input type="number" step="0.0001" class="form-control" name="bl_sheet_weight" id="edit-blweight">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#tabela-componentes').DataTable({
            language: {
                search: "Buscar:",
                lengthMenu: "Mostrar _MENU_ registros",
                info: "Mostrando _START_ até _END_ de _TOTAL_ registros",
                paginate: { previous: "Anterior", next: "Próximo" }
            }
        });

        $(document).on('click', '.btn-editar', function() {
            $('#edit-id').val($(this).data('id'));
            $('#edit-part').text($(this).data('part'));
            $('#edit-nome').text($(this).data('nome'));
            $('#edit-gweight').val($(this).data('gweight'));
            $('#edit-thickness').val($(this).data('thickness'));
            $('#edit-width').val($(this).data('width'));
            $('#edit-pitch').val($(this).data('pitch'));
            $('#edit-blweight').val($(this).data('blweight'));
        });
    });
    </script>
</body>
</html>
