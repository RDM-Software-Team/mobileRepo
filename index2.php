<?php
// Include database connection
include 'DBconn.php';

$errorMessage = '';
$registrationMessage = '';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT customer_id, pwrd FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($customer_id, $hashed_password);
    $stmt->fetch();

    if ($customer_id && password_verify($password, $hashed_password)) {
        // Login successful
        $token = bin2hex(random_bytes(16));
        $expiry = date("Y-m-d H:i:s", strtotime('+5 minutes'));

        $stmt = $conn->prepare("REPLACE INTO sessions (customer_id, token, expiry) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $customer_id, $token, $expiry);
        $stmt->execute();

        // Redirect or process login
        //header("Location: dashboard.php?token=$token");
        echo "we good";
        exit();
    } else {
        $errorMessage = "Invalid credentials";
    }

    $stmt->close();
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['ddress'];
    $password = password_hash($_POST['pwrd'], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO customers (firstName, lastName, email, phone, ddress, pwrd) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $firstName, $lastName, $email, $phone, $address, $password);

    if ($stmt->execute()) {
        $registrationMessage = "Registration successful";
    } else {
        $registrationMessage = "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Login & Registration</title>
    <style>
        /* Add your CSS styles here */
    </style>
</head>
<body>

    <!-- Display login form -->
    <h2>Login</h2>
    <?php if ($errorMessage): ?>
        <p style="color: red;"><?php echo $errorMessage; ?></p>
    <?php endif; ?>
    <form action="" method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>

    <!-- Display registration form -->
    <h2>Register</h2>
    <?php if ($registrationMessage): ?>
        <p><?php echo $registrationMessage; ?></p>
    <?php endif; ?>
    <form action="" method="POST">
        <input type="text" name="firstName" placeholder="First Name" required>
        <input type="text" name="lastName" placeholder="Last Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone" placeholder="Phone" required>
        <input type="text" name="ddress" placeholder="Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="register">Register</button>
    </form>

</body>
</html>
