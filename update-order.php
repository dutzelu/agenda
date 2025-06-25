<?php
require 'conectaredb.php'; // adaptează după proiectul tău

header('Content-Type: application/json');

if (!isset($_POST['order']) || !is_array($_POST['order'])) {
    echo json_encode(['success'=>false, 'error'=>'Date lipsă']);
    exit;
}

try {
    $conn->begin_transaction();
    $stmt = $conn->prepare("UPDATE clerici_parohii SET sort_order = ? WHERE id = ?");
    foreach ($_POST['order'] as $row) {
        $sort = (int)$row['sort'];
        $id = (int)$row['id'];
        $stmt->bind_param("ii", $sort, $id);
        $stmt->execute();
    }
    $conn->commit();
    echo json_encode(['success'=>true]);
} catch(Exception $e){
    $conn->rollback();
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
}
