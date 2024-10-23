<?php
include 'DBconn.php';

header('Content-Type: application/json'); // Ensure JSON is always returned
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {

    // Get token and category from request
    $token = $_POST['token'] ?? $_GET['token'] ?? null;
    $category = $_POST['category'] ?? $_GET['category'] ?? '';

    if (!$token) {
        $response = ["message" => "Token not provided"];
        echo json_encode($response);
        exit;
    }

    // Set pagination variables
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
    $offset = ($page - 1) * $limit;

    if ($limit <= 0) {
        $limit = 10;
    }

    try {
        // Verify the token
        $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > GETDATE()");
        $stmt->execute([$token]);
        $customer_id = $stmt->fetchColumn();

        if ($customer_id) {
            if (empty($category)) {
                $response = ["message" => "Category not provided"];
                echo json_encode($response);
                exit;
            }

            // Query to fetch products by category with pagination
            $query = "SELECT product_id, pName, discription, price, category, images 
                      FROM products 
                      WHERE category = ? 
                      LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$category, $limit, $offset]);

            $products = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['images']) {
                    $imageData = $row['images'];
                    $imageName = 'image_' . $row['product_id'] . '.jpg';
                    $imagePath = 'images/' . $imageName;
                    
                    // Save image if not already saved
                    if (!file_exists($imagePath)) {
                        file_put_contents($imagePath, $imageData);
                    }

                    $row['image_path'] = "https://backend-php-dsehdsc0adbxbuey.southafricanorth-01.azurewebsites.net/" . $imagePath; // Replace with your actual URL
                    unset($row['images']); // Remove binary data from the response
                }
                $products[] = $row;
            }

            // Query to count total products in the category
            $count_query = "SELECT COUNT(*) FROM products WHERE category = ?";
            $count_stmt = $conn->prepare($count_query);
            $count_stmt->execute([$category]);
            $total_items = $count_stmt->fetchColumn();

            $more_pages = ($total_items > $page * $limit);

            // Include product details and pagination info
            $response = ["products" => $products, "more_pages" => $more_pages];
            echo json_encode($response);
        } else {
            $response = ["message" => "Invalid token"];
            echo json_encode($response);
        }
    } catch (Exception $e) {
        // Return error message if something fails
        $response = ["message" => "Error: " . $e->getMessage()];
        echo json_encode($response);
    } finally {
        // Close the connection
        $conn = null;
    }
}
?>
