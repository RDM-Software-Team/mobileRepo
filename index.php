<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Portal</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Customer Portal</h1>

        <div class="form-container">
            <h2>Login</h2>
            <form id="loginForm" action="/login.php" method="POST">
                <label for="email">Email:</label>
                <input title="email" type="email" id="loginEmail" name="email" required>

                <label for="password">Password:</label>
                <input title="pwrd" type="password" id="loginPassword" name="password" required>

                <button type="submit">Login</button>
                <p id="loginMessage"></p>
            </form>
        </div>

        <div class="form-container">
            <h2>Register</h2>
            <form id="registerForm" action="/register.php" method="POST">
                <label for="firstName">First Name:</label>
                <input type="text" id="firstName" name="firstName" required>

                <label for="lastName">Last Name:</label>
                <input type="text" id="lastName" name="lastName" required>

                <label for="email">Email:</label>
                <input title="email" type="email" id="registerEmail" name="email" required>

                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" required>

                <label for="address">Address:</label>
                <input type="text" id="address" name="address" required>

                <label for="password">Password:</label>
                <input title="pwrd" type="password" id="registerPassword" name="password" required>

                <button type="submit">Register</button>
                <p id="registerMessage"></p>
            </form>
        </div>

        <div class="customers-container">
            <h2>Customer List</h2>
            <button id="loadCustomers">Load Customers</button>
            <ul id="customerList"></ul>
        </div>
    </div>

    <script src="scripts.js"></script>
</body>
</html>
