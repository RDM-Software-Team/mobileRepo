<?php
include 'DBconn.php';

header('Content-Type: application/json'); // Ensure JSON is always returned
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Remove debug prints like these
    // echo "Raw POST Data: ";
    // print_r($_POST);
    // echo "Raw GET Data: ";
    // print_r($_GET);

    $token = $_POST['token'] ?? $_GET['token'] ?? null;
    $category = $_POST['category'] ?? $_GET['category'] ?? '';

    if (!$token) {
        $response = ["message" => "Token not provided"];
        echo json_encode($response);
        exit;
    }

    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
    $offset = ($page - 1) * $limit;

    if ($limit <= 0) {
        $limit = 10;
    }

    // Verify the token
    $stmt = $conn->prepare("SELECT customer_id FROM sessions WHERE token = ? AND expiry > NOW()");
    $stmt->bind_param("s", $token);
    if (!$stmt->execute()) {
        $response = ["message" => "Error executing token query: " . $stmt->error];
        echo json_encode($response);
        exit;
    }
    $stmt->bind_result($customer_id);
    $stmt->fetch();
    $stmt->close();

    if ($customer_id) {
        if (empty($category)) {
            $response = ["message" => "Category not provided"];
            echo json_encode($response);
            exit;
        }

        $query = "SELECT product_id, pName, discription, price, category, images 
                    FROM products 
                    WHERE category = ? 
                    LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sii", $category, $limit, $offset);
        if (!$stmt->execute()) {
            $response = ["message" => "Error executing product query: " . $stmt->error];
            echo json_encode($response);
            exit;
        }
        $result = $stmt->get_result();
        if ($result === false) {
            $response = ["message" => "Error fetching result: " . $stmt->error];
            echo json_encode($response);
            exit;
        }

        $products = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['images']) {
                $imageData = $row['images'];
                $imageName = 'image_' . $row['product_id'] . '.jpg';
                $imagePath = 'images/' . $imageName;
                file_put_contents($imagePath, $imageData);
                $row['image_path'] = $imagePath;
                unset($row['images']);
            }
            $products[] = $row;
        }
        $stmt->close();

        $count_query = "SELECT COUNT(*) FROM products WHERE category = ?";
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bind_param("s", $category);
        if (!$count_stmt->execute()) {
            $response = ["message" => "Error executing count query: " . $count_stmt->error];
            echo json_encode($response);
            exit;
        }
        $count_stmt->bind_result($total_items);
        $count_stmt->fetch();
        $count_stmt->close();

        $more_pages = ($total_items > $page * $limit);

        $response = ["products" => $products, "more_pages" => $more_pages];
        echo json_encode($response);
    } else {
        $response = ["message" => "Invalid token"];
        echo json_encode($response);
    }
    $conn->close();
}
?>
