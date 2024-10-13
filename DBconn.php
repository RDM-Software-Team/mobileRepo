<?php
// PDO Connection
try {
    $conn = new PDO("sqlsrv:server = tcp:disappdd.database.windows.net,1433; Database = ComputerCmplex", "st10107568", "dianaK1209$");
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected successfully using PDO";  // Optional message for successful connection
} catch (PDOException $e) {
    // Display detailed error information
    die("Error connecting to SQL Server using PDO: " . $e->getMessage());
}

// SQLSRV Connection
/*$connectionInfo = array(
    "UID" => "st10107568",
    "PWD" => "dianaK1209$", 
    "Database" => "ComputerCmplex",
    "LoginTimeout" => 30,
    "Encrypt" => 1,
    "TrustServerCertificate" => 0
);
$serverName = "tcp:disappdd.database.windows.net,1433";
$connSqlsrv = sqlsrv_connect($serverName, $connectionInfo);

if ($connSqlsrv) {
    echo "Connected successfully using SQLSRV";  // Optional message for successful connection
} else {
    // Retrieve detailed error information if connection fails
    $errors = sqlsrv_errors();
    foreach ($errors as $error) {
        echo "SQLSRV Error: " . $error['message'] . "<br/>";
    }
}


*/