<?php
include 'DBconn.php';
header('Content-Type: application/json'); // Ensure JSON is always returned

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Fetch distinct product categories from the database
        $query = "SELECT DISTINCT category FROM products";
        $stmt = $conn->prepare($query);
        $stmt->execute();

        // Fetch all distinct categories
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Return categories in JSON format
        echo json_encode(["categories" => $categories]);
    } catch (Exception $e) {
        // Return error message in case of failure
        echo json_encode(["message" => "Error: " . $e->getMessage()]);
    } finally {
        // Close the connection
        $conn = null;
    }
}
?>
