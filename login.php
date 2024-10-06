<?php
include 'DBconn.php';
header('Content-Type: application/json'); // Set content type to JSON

// Retrieve parameters from both $_POST and $_GET
$email = isset($_POST['email']) ? $_POST['email'] : (isset($_GET['email']) ? $_GET['email'] : null);
$password = isset($_POST['password']) ? $_POST['password'] : (isset($_GET['password']) ? $_GET['password'] : null);

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($email && $password) {
        $stmt = $conn->prepare("SELECT customer_id, pwrd FROM customers WHERE email = ?");
        if (!$stmt) {
            echo json_encode(["message" => "Failed to prepare statement"]);
            exit();
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($customer_id, $hashed_password);
        $stmt->fetch();

        if ($customer_id && password_verify($password, $hashed_password)) {
            $stmt->close(); // Close the first statement

            $token = bin2hex(random_bytes(16));
            $expiry = new DateTime('now', new DateTimeZone('Africa/Johannesburg'));
            $expiry->modify('+5 minutes');
            $expiryFormatted = $expiry->format('Y-m-d H:i:s');

            // Prepare the second statement
            $stmt = $conn->prepare("REPLACE INTO sessions (customer_id, token, expiry) VALUES (?, ?, ?)");
            if (!$stmt) {
                echo json_encode(["message" => "Failed to prepare statement"]);
                exit();
            }
            $stmt->bind_param("iss", $customer_id, $token, $expiryFormatted);
            $stmt->execute();

            echo json_encode(["token" => $token]);
        } else {
            echo json_encode(["message" => "Invalid credentials"]);
        }

        $stmt->close(); // Close the second statement
    } else {
        echo json_encode(["message" => "Email and password are required"]);
    }

    $conn->close();
} else {
    echo json_encode(["message" => "Invalid request method"]);
}
