<?php
require_once 'config/database.php';
$conn = getConnection();

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $conn->begin_transaction();
        
        // Primeiro excluir os recebimentos
        $conn->query("DELETE FROM recebimentos WHERE ordem_compra_id = $id");
        
        // Depois excluir a ordem
        $conn->query("DELETE FROM ordens_compra WHERE id = $id");
        
        $conn->commit();
        
        // Redirecionar com mensagem de sucesso
        session_start();
        $_SESSION['message'] = "Ordem excluída com sucesso!";
        $_SESSION['message_type'] = "success";
        
    } catch (Exception $e) {
        $conn->rollback();
        session_start();
        $_SESSION['message'] = "Erro ao excluir ordem: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }
}

header("Location: listar_ordens.php");
exit();