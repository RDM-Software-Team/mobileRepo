<?php
include 'DBconn.php';  // Ensure this file sets up your PDO connection
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed"]);
    exit;
}

$token = $_POST['token'] ?? null;
$cart_item_id = $_POST['cart_item_id'] ?? null;

if (!$token || !$cart_item_id) {
    http_response_code(400);
    echo json_encode(["message" => "Missing required parameters"]);
    exit;
}

try {
    // Start a transaction
    $conn->beginTransaction();

    // Fetch customer_id using token
    $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
    $stmt->execute([$token]);  // Bind parameters as an array
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        throw new Exception("Invalid token");
    }

    // Delete cart item based on customer_id and active cart
    $stmt = $conn->prepare("
        DELETE FROM cart_items 
        WHERE item_id = ? 
        AND cart_id = (SELECT cart_id FROM carts WHERE customer_id = ? AND status = 'active')
    ");
    $stmt->execute([$cart_item_id, $customer['customer_id']]);

    // Check if the deletion affected any rows
    if ($stmt->rowCount() === 0) {
        throw new Exception("Item not found or already deleted");
    }

    // Commit the transaction
    $conn->commit();
    echo json_encode(["message" => "Item deleted successfully"]);

} catch (Exception $e) {
    // Rollback the transaction in case of error
    $conn->rollBack();
    http_response_code(400);
    echo json_encode(["message" => $e->getMessage()]);
} finally {
    $conn = null;  // Close the connection
}
