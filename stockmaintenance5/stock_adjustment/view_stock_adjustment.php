<?php

header('Content-Type: application/json');

require_once '../dbconfig/config.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM stock_adjustment");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

?>