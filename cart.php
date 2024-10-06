<?php
include 'DBconn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->bind_result($customer_id);
    $stmt->fetch();

    if ($customer_id) {
        // Check if the user has an active cart
        $stmt = $conn->prepare("SELECT cart_id FROM carts WHERE customer_id = ? AND status = 'active'");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $stmt->bind_result($cart_id);
        $stmt->fetch();

        if (!$cart_id) {
            // Create a new cart if none exists
            $stmt = $conn->prepare("INSERT INTO carts (customer_id, cart_created, status) VALUES (?, NOW(), 'active')");
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $cart_id = $stmt->insert_id;
        }

        // Add or update the cart item
        $stmt = $conn->prepare("REPLACE INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $cart_id, $product_id, $quantity);
        $stmt->execute();

        echo json_encode(["message" => "Cart updated"]);
    } else {
        echo json_encode(["message" => "Invalid token"]);
    }

    $stmt->close();
    $conn->close();
}