<?php
include 'DBconn.php';

header('Content-Type: application/json'); // Set content type to JSON

// Retrieve parameters from both $_POST and $_GET
$email = $_POST['email'] ?? $_GET['email'] ?? null;
$password = $_POST['password'] ?? $_GET['password'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($email && $password) {
        try {
            // Prepare statement to fetch user details
            $stmt = $conn->prepare("SELECT customer_id, pwrd FROM customers WHERE email = ?");
            $stmt->execute([$email]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['pwrd'])) {
                // Close first statement
                $stmt = null;

                // Generate a random token and expiry date
                $token = bin2hex(random_bytes(16));
                $expiry = new DateTime('now', new DateTimeZone('Africa/Johannesburg'));
                $expiry->modify('+5 minutes');
                $expiryFormatted = $expiry->format('Y-m-d H:i:s');

                // Use MERGE to insert or update the session token
                $stmt = $conn->prepare("
                    MERGE INTO sessions AS target
                    USING (SELECT ? AS customer_id, ? AS token, ? AS expiry) AS source
                    ON (target.customer_id = source.customer_id)
                    WHEN MATCHED THEN
                        UPDATE SET token = source.token, expiry = source.expiry
                    WHEN NOT MATCHED THEN
                        INSERT (customer_id, token, expiry) VALUES (source.customer_id, source.token, source.expiry);
                ");
                $stmt->execute([$user['customer_id'], $token, $expiryFormatted]);

                echo json_encode(["token" => $token]);
            } else {
                echo json_encode(["message" => "Invalid credentials"]);
            }
        } catch (Exception $e) {
            echo json_encode(["message" => "Error: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["message" => "Email and password are required"]);
    }

    // Close the connection
    $conn = null;
} else {
    echo json_encode(["message" => "Invalid request method"]);
}
