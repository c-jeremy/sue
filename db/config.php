<?php
// config.php - Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'seiue_db'); // Change to your database username
define('DB_PASSWORD', '12345678'); // Change to your database password
define('DB_NAME', 'seiue_db');

// Attempt to connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to ensure proper handling of special characters
$conn->set_charset("utf8mb4");
?>