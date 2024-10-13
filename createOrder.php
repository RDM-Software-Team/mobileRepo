<?php
include 'DBconn.php';  // Ensure this file sets up your PDO connection
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? null;

    if ($token) {
        try {
            // Step 1: Get customer ID from session token
            $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
            $stmt->execute([$token]);  // Bind token using execute
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);  // Fetch customer data

            if ($customer && isset($customer['customer_id'])) {
                $customer_id = $customer['customer_id'];

                // Step 2: Get cart items
                $stmt = $conn->prepare("SELECT ci.product_id, ci.quantity, p.price, c.cart_id 
                                        FROM cart_items ci 
                                        JOIN carts c ON ci.cart_id = c.cart_id 
                                        JOIN products p ON ci.product_id = p.product_id 
                                        WHERE c.customer_id = ? AND c.status = 'active'");
                $stmt->execute([$customer_id]);  // Bind customer_id using execute
                $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);  // Fetch all cart items

                $totalPrice = 0;
                $cart_id = null;

                foreach ($cartItems as $row) {
                    $totalPrice += $row['price'] * $row['quantity'];
                    $cart_id = $row['cart_id'];  // Set cart ID
                }

                if ($cart_id) {
                    // Step 3: Insert order into the orders table
                    $custumer_name = "John Doe";  // Replace with the actual customer name
                    $stmt = $conn->prepare("INSERT INTO orders (custumer_name, order_Date, totalPrice, customer_id, cart_id) 
                                            VALUES (?, NOW(), ?, ?, ?)");
                    $stmt->execute([$custumer_name, $totalPrice, $customer_id, $cart_id]);

                    $order_id = $conn->lastInsertId();  // Get last inserted order ID

                    if ($order_id) {
                        // Send back the order ID and total price for the payment process
                        echo json_encode(["success" => true, "order_id" => $order_id, "totalPrice" => $totalPrice]);
                    } else {
                        echo json_encode(["success" => false, "message" => "Error creating order"]);
                    }
                } else {
                    echo json_encode(["success" => false, "message" => "Cart not found or empty"]);
                }
            } else {
                echo json_encode(["success" => false, "message" => "Invalid token"]);
            }
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Token not provided"]);
    }

    $conn = null;  // Close the PDO connection
}
