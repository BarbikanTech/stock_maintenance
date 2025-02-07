<?php
// Allow CORS for all origins (Adjust as needed)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Include database configuration
require_once '../dbconfig/config.php';

// Read and decode JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Check if 'customer_id' is provided in the input, if so, fetch that specific customer
if (isset($input['customer_id'])) {
    // Fetch customer by customer_id
    $customerId = $input['customer_id'];

    try {
        // Prepare the SQL query to fetch customer data
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = :customer_id AND deleted_at = 0");
        $stmt->execute(['customer_id' => $customerId]);

        // Fetch the customer data
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if the customer exists
        if ($customer) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Customer found.',
                'data' => $customer
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Customer not found or deleted.'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'An error occurred: ' . $e->getMessage()
        ]);
    }
} else {
    // If no customer_id is provided, fetch all active customers
    try {
        // Prepare the SQL query to fetch all active customer data
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE deleted_at = 0");
        $stmt->execute();

        // Fetch all customer records
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if any customers exist
        if ($customers) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Customers found.',
                'data' => $customers
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No active customers found.'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'An error occurred: ' . $e->getMessage()
        ]);
    }
}
?>
