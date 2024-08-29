<?php
// db.php

$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "portal_system";

// Create connection
$mysqli = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
?>
