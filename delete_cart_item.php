<?php
include 'DBconn.php';
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
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $stmt->close();

    if (!$customer) {
        throw new Exception("Invalid token");
    }

    $stmt = $conn->prepare("DELETE FROM cart_items WHERE item_id = ? AND cart_id = (SELECT cart_id FROM carts WHERE customer_id = ? AND status = 'active')");
    $stmt->bind_param("ii", $cart_item_id, $customer['customer_id']);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("Item not found or already deleted");
    }

    $conn->commit();
    echo json_encode(["message" => "Item deleted successfully"]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(["message" => $e->getMessage()]);
} finally {
    $stmt->close();
    $conn->close();
}
?>