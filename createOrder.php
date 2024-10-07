<?php
include 'DBconn.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? null;

    if ($token) {
        // Step 1: Get customer ID from session token
        $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->bind_result($customer_id);
        $stmt->fetch();
        $stmt->close();

        if ($customer_id) {
            // Step 2: Get cart items
            $stmt = $conn->prepare("SELECT ci.product_id, ci.quantity, p.price, c.cart_id 
                                    FROM cart_items ci 
                                    JOIN carts c ON ci.cart_id = c.cart_id 
                                    JOIN products p ON ci.product_id = p.product_id 
                                    WHERE c.customer_id = ? AND c.status = 'active'");
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $totalPrice = 0;
            $cart_id = null;
            while ($row = $result->fetch_assoc()) {
                $totalPrice += $row['price'] * $row['quantity'];
                $cart_id = $row['cart_id'];
            }
            $stmt->close();

            if ($cart_id) {
                // Step 3: Insert order into the orders table
                $stmt = $conn->prepare("INSERT INTO orders (custumer_name, order_Date, totalPrice, customer_id, cart_id) 
                                        VALUES (?, NOW(), ?, ?, ?)");
                $custumer_name = "John Doe"; // Fetch the real customer name
                $stmt->bind_param("sdii", $custumer_name, $totalPrice, $customer_id, $cart_id);
                $stmt->execute();
                $order_id = $stmt->insert_id;
                $stmt->close();

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
    } else {
        echo json_encode(["success" => false, "message" => "Token not provided"]);
    }

    $conn->close();
}
?>
