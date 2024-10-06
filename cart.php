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
        $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
        if (!$stmt) {
            throw new Exception("Failed to prepare statement for fetching customer_id: " . $conn->error);
        }
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->bind_result($customer_id);
        $stmt->fetch();
        $stmt->close();

        // If no customer_id found, return an error
        if (!$customer_id) {
            echo json_encode(["error" => "Invalid or expired token"]);
            exit;
        }

        // Check if the user has an active cart
        $stmt = $conn->prepare("SELECT cart_id FROM carts WHERE customer_id = ? AND status = 'active'");
        if (!$stmt) {
            throw new Exception("Failed to prepare statement for checking cart: " . $conn->error);
        }
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $stmt->bind_result($cart_id);
        $stmt->fetch();
        $stmt->close();

        // If no active cart, create a new one
        if (!$cart_id) {
            $stmt = $conn->prepare("INSERT INTO carts (customer_id, cart_created, status) VALUES (?, NOW(), 'active')");
            if (!$stmt) {
                throw new Exception("Failed to prepare statement for creating a cart: " . $conn->error);
            }
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $cart_id = $stmt->insert_id;
            $stmt->close();
        }

        // Add or update the cart item
        $stmt = $conn->prepare("REPLACE INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Failed to prepare statement for updating cart items: " . $conn->error);
        }
        $stmt->bind_param("iii", $cart_id, $product_id, $quantity);
        $stmt->execute();
        $stmt->close();

        echo json_encode(["message" => "Cart updated successfully"]);
    } catch (Exception $e) {
        // Handle any errors during execution
        echo json_encode(["error" => $e->getMessage()]);
    } finally {
        // Ensure the database connection is closed
        if ($conn) {
            $conn->close();
        }
    }
}
