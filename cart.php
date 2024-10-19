<?php
include 'DBconn.php';

// Set response headers for JSON output
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? null;
    $product_id = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? null;

    // Validate the token, product_id, and quantity
    if (!$token) {
        echo json_encode(["error" => "Token is required"]);
        exit;
    }
    if (!$product_id || !$quantity || !is_numeric($quantity)) {
        echo json_encode(["error" => "Product ID and valid quantity are required"]);
        exit;
    }

    try {
        // Fetch customer_id based on the token
        $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > GETDATE()");
        $stmt->execute([$token]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If no customer_id found, return an error
        if (!$customer) {
            echo json_encode(["error" => "Invalid or expired token"]);
            exit;
        }
        
        $customer_id = $customer['customer_id'];

        // Check if the user has an active cart
        $stmt = $conn->prepare("SELECT cart_id FROM carts WHERE customer_id = ? AND status = 'active'");
        $stmt->execute([$customer_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        // If no active cart, create a new one
        if (!$cart) {
            $stmt = $conn->prepare("INSERT INTO carts (customer_id, cart_created, status) VALUES (?, GETDATE(), 'active')");
            $stmt->execute([$customer_id]);
            $cart_id = $conn->lastInsertId(); // Get the last inserted ID
        } else {
            $cart_id = $cart['cart_id'];
        }

        // Add or update the cart item
        $stmt = $conn->prepare("REPLACE INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$cart_id, $product_id, $quantity]);

        echo json_encode(["message" => "Cart updated successfully"]);
    } catch (Exception $e) {
        // Handle any errors during execution
        echo json_encode(["error" => $e->getMessage()]);
    } finally {
        // Ensure the database connection is closed
        if ($conn) {
            $conn = null; // Use null to close the connection in PDO
        }
    }
}
