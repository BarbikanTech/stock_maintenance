<?php
// Allow CORS for all origins
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

include 'dbconfig/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $query = "SELECT * FROM stock_history WHERE deleted_at = 0 ORDER BY id ASC";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        $stock_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(["success" => true, "data" => $stock_history]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error fetching stock history.", "error" => $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
