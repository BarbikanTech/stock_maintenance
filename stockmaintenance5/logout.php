<?php
session_start();

// Destroy the session
session_unset();
session_destroy();

// Return a JSON response
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'Logout successful.',
    'redirect' => 'login.php'
]);
exit;
?>
