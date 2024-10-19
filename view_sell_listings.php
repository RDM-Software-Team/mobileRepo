<?php
// Disable error reporting for security reasons
error_reporting(0);
ini_set('display_errors', 0);

// Ensure JSON output
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendResponse($status, $message = null, $data = null, $code = 200) {
    http_response_code($code);
    $response = ['status' => $status];
    if ($message) {
        $response['message'] = $message;
    }
    if ($data) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Start output buffering to handle any unexpected output
ob_start();

include 'DBconn.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse("error", "Invalid request method", null, 405);
}

// Get the token from the POST request
$token = $_POST['token'] ?? null;

if (empty($token)) {
    sendResponse("error", "Token is required", null, 400);
}

try {
    // Prepare the statement to validate the session token
    $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > GETDATE()");
    $stmt->execute([$token]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer || !isset($customer['customer_id'])) {
        sendResponse("error", "Invalid or expired token", null, 403);
    }

    $customer_id = $customer['customer_id'];

    // Prepare the statement to fetch sell items
    $stmt = $conn->prepare("SELECT sell_id, image1, image2, image3, description, price FROM sell WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $sells = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Update the image URLs with the full path
    foreach ($sells as &$sell) {
        $base_url = "http://192.168.18.113/computer_Complex_mobile/uploads/sells/";
        $sell['image1'] = $base_url . basename($sell['image1']);
        $sell['image2'] = $base_url . basename($sell['image2']);
        $sell['image3'] = $base_url . basename($sell['image3']);
    }

    // Clear the output buffer and send the response
    ob_end_clean();
    sendResponse("success", null, $sells);

} catch (Exception $e) {
    // Catch any errors and return a generic server error response
    ob_end_clean();
    sendResponse("error", "Server error: " . $e->getMessage(), null, 500);
} finally {
    // Close the connection
    $conn = null;
}
