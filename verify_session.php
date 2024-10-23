<?php
include 'DBconn.php';
header('Content-Type: application/json');

// Function to send a standardized JSON response with status code
function sendResponse($status, $message, $code = 200, $data = null) {
    http_response_code($code);
    $response = ["status" => $status, "message" => $message];
    if ($data) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Log the request method to troubleshoot
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse("error", "Invalid request method", 405);
}

// Check if the token is provided
if (empty($_POST['token'])) {
    sendResponse("error", "Missing token", 400);
}

$token = $_POST['token'];

try {
    // Prepare and execute query to check if the session is valid
    // Use GETDATE() instead of NOW() for SQL Server
    $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > GETDATE()");
    $stmt->execute([$token]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customer && isset($customer['customer_id'])) {
        sendResponse("success", "Session valid", 200, ["customer_id" => $customer['customer_id']]);
    } else {
        sendResponse("error", "Session invalid or expired", 403);
    }
} catch (Exception $e) {
    sendResponse("error", "Server error: " . $e->getMessage(), 500);
} finally {
    $conn = null; // Close connection
}
