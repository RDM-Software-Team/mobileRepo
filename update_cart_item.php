<?php
include 'DBconn.php';
header('Content-Type: application/json');

// Function to send JSON responses with appropriate HTTP status codes
function sendResponse($status, $message, $code = 200) {
    http_response_code($code);
    echo json_encode(["status" => $status, "message" => $message]);
    exit;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse("error", "Invalid request method", 405);
}

// Check for required fields
if (empty($_POST['token']) || empty($_POST['cart_item_id']) || empty($_POST['quantity'])) {
    sendResponse("error", "Missing required fields", 400);
}

$token = $_POST['token'];
$cart_item_id = (int) $_POST['cart_item_id'];
$quantity = (int) $_POST['quantity'];

try {
    // Verify token and retrieve customer_id
    $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
    $stmt->execute([$token]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer || !isset($customer['customer_id'])) {
        sendResponse("error", "Invalid or expired token", 403);
    }

    $customer_id = $customer['customer_id'];

    // Update the cart item quantity
    $updateQuery = "
        UPDATE cart_items 
        SET quantity = ? 
        WHERE id = ? 
        AND cart_id = (
            SELECT cart_id 
            FROM carts 
            WHERE customer_id = ? 
            AND status = 'active'
        )
    ";
    $stmt = $conn->prepare($updateQuery);

    if ($stmt->execute([$quantity, $cart_item_id, $customer_id])) {
        sendResponse("success", "Item updated successfully", 200);
    } else {
        sendResponse("error", "Failed to update item", 500);
    }
} catch (Exception $e) {
    sendResponse("error", "Server error: " . $e->getMessage(), 500);
} finally {
    $conn = null; // Close connection
}
