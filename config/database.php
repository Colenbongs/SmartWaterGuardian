<?php
/**
 * Smart Water Guardian - Database Configuration
 * MySQL connection with UTF-8 support
 */

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'smart_water_guardian';

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    // Database created or already exists
} else {
    die("Error creating database: " . $conn->error);
}

// Select database
$conn->select_db($database);

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Set timezone
$conn->query("SET time_zone = '+02:00'");

// Error reporting (disable in production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Return connection
return $conn;
?>