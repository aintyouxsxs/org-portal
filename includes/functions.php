<?php
// functions.php

include 'db.php';

function registerUser($firstName, $lastName, $email, $password) {
    global $mysqli;

    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Prepare and execute the SQL statement
    $stmt = $mysqli->prepare("INSERT INTO users (FirstName, LastName, Email, PasswordHash) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($mysqli->error));
    }

    $stmt->bind_param("ssss", $firstName, $lastName, $email, $passwordHash);

    if ($stmt->execute()) {
        $stmt->close();
        return true; // Success
    } else {
        $stmt->close();
        return false; // Failure
    }
}
?>
