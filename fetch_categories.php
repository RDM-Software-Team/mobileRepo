<?php
include 'DBconn.php';
header('Content-Type: application/json'); // Ensure JSON is always returned

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch distinct product categories from the database
    $query = "SELECT DISTINCT category FROM products";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt->execute()) {
        echo json_encode(["message" => "Error executing category query: " . $stmt->error]);
        exit;
    }

    $result = $stmt->get_result();
    
    if ($result === false) {
        echo json_encode(["message" => "Error fetching result: " . $stmt->error]);
        exit;
    }

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }

    $stmt->close();
    $conn->close();

    // Return categories in JSON format
    echo json_encode(["categories" => $categories]);
}
?>
