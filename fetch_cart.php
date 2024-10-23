<?php
include 'DBconn.php';
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = $_POST['token'] ?? $_GET['token'] ?? null;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $limit = 10; // Number of items per page
    $offset = ($page - 1) * $limit;

    if ($token) {
        try {
            // Step 1: Get customer ID from session token
            $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > GETDATE()");
            $stmt->execute([$token]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer && isset($customer['customer_id'])) {
                $customer_id = $customer['customer_id'];

                // Step 2: Get cart items with pagination
                $items_query = "SELECT ci.item_id, ci.product_id, ci.quantity, c.cart_id, c.cart_created, c.status,
                                       p.pName, p.discription, p.price, p.images
                                FROM cart_items ci 
                                JOIN carts c ON ci.cart_id = c.cart_id
                                JOIN products p ON ci.product_id = p.product_id
                                WHERE c.customer_id = ? AND c.status = 'active'
                                LIMIT ? OFFSET ?";
                $stmt = $conn->prepare($items_query);
                $stmt->execute([$customer_id, $limit, $offset]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Step 3: Process items and save images
                foreach ($items as &$row) {
                    if (!empty($row['images'])) {
                        $imageData = $row['images'];
                        $imageName = 'image_' . $row['product_id'] . '.jpg';
                        $imagePath = 'images/' . $imageName;
                        file_put_contents($imagePath, $imageData); // Save image to file
                        $row['image_path'] = "https://backend-php-dsehdsc0adbxbuey.southafricanorth-01.azurewebsites.net/" . $imagePath;
                        unset($row['images']); // Remove binary image data
                    } else {
                        $row['image_path'] = null; // Default image path
                    }
                }

                // Step 4: Get the total number of items
                $count_query = "SELECT COUNT(*) FROM cart_items ci
                                JOIN carts c ON ci.cart_id = c.cart_id
                                WHERE c.customer_id = ? AND c.status = 'active'";
                $stmt = $conn->prepare($count_query);
                $stmt->execute([$customer_id]);
                $total_items = $stmt->fetchColumn();

                // Step 5: Check if there are more pages
                $more_pages = ($total_items > $page * $limit);

                // Output the items and whether more pages exist
                echo json_encode(["items" => $items, "more_pages" => $more_pages]);
            } else {
                // Invalid token response
                echo json_encode(["message" => "Invalid token"]);
            }
        } catch (Exception $e) {
            // Handle any exception
            echo json_encode(["error" => "Error: " . $e->getMessage()]);
        } finally {
            $conn = null; // Close the PDO connection
        }
    } else {
        echo json_encode(["message" => "Token is missing"]);
    }
}
?>
