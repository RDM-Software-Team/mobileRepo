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

    $customer_id = null;
    $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->bind_result($customer_id);
    $stmt->fetch();
    $stmt->close(); // Close the statement

    if ($customer_id) {
        // Get cart items with pagination
        $items_query = "SELECT ci.item_id, ci.product_id, ci.quantity, c.cart_id, c.cart_created, c.status,
                                p.pName, p.discription, p.price, p.images
                        FROM cart_items ci 
                        JOIN carts c ON ci.cart_id = c.cart_id
                        JOIN products p ON ci.product_id = p.product_id
                        WHERE c.customer_id = ? AND c.status = 'active'
                        LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($items_query);
        $stmt->bind_param("iii", $customer_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['images']) {
                $imageData = $row['images'];
                $imageName = 'image_' . $row['product_id'] . '.jpg';
                $imagePath = 'images/' . $imageName;
                file_put_contents($imagePath, $imageData);
                $row['image_path'] = "http://192.168.18.113/computer_Complex_mobile/" . $imagePath;
                unset($row['images']); // Remove the binary image data from the response
            } else {
                $row['image_path'] = null; // or a default image path
            }
            $items[] = $row;
        }
        $stmt->close(); // Close the statement

        // Check if there are more pages
        $count_query = "SELECT COUNT(*) FROM cart_items ci
                        JOIN carts c ON ci.cart_id = c.cart_id
                        WHERE c.customer_id = ? AND c.status = 'active'";
        $stmt = $conn->prepare($count_query);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $stmt->bind_result($total_items);
        $stmt->fetch();
        $stmt->close(); // Close the statement

        $more_pages = ($total_items > $page * $limit);

        echo json_encode(["items" => $items, "more_pages" => $more_pages]);
    } else {
        echo json_encode(["message" => "Invalid token"]);
    }

    $conn->close(); // Close the database connection
}
?>