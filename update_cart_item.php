<?php
include 'DBconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $cart_item_id = $_POST['cart_item_id'];
    $quantity = $_POST['quantity'];

    $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->bind_result($customer_id);
    $stmt->fetch();

    if ($customer_id) {
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND cart_id = 
        (SELECT cart_id FROM carts WHERE customer_id = ? AND status = 'active')");
        $stmt->bind_param("iii", $quantity, $cart_item_id, $customer_id);
        if ($stmt->execute()) {
            echo json_encode(["message" => "Item updated successfully"]);
        } else {
            echo json_encode(["message" => "Failed to update item"]);
        }
    } else {
        echo json_encode(["message" => "Invalid token"]);
    }

    $stmt->close();
    $conn->close();
}
