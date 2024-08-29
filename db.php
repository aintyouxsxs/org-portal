<?php
$hostName = "localhost";
$dbUser = "root";
$dbPassword = "";
$dbName = "portal_system";

try {
    // Create a new PDO connection
    $conn = new PDO("mysql:host=$hostName;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPassword);

    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Handle connection errors
    die("Connection failed: " . $e->getMessage());
}
?>