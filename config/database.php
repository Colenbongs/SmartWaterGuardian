<?php
/**
 * Smart Water Guardian - Database Configuration
 * MySQL connection with UTF-8 support and environment variables
 */

// Load environment variables if .env exists
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database configuration with fallbacks
$host = $_ENV['DB_HOST'] ?? 'localhost';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
$database = $_ENV['DB_DATABASE'] ?? 'smart_water_guardian';
$port = $_ENV['DB_PORT'] ?? 3306;

// Create connection
$conn = new mysqli($host, $username, $password, $database, (int)$port);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Set timezone
$conn->query("SET time_zone = '+02:00'");

// Enable error reporting for development (disable in production)
if ($_ENV['APP_ENV'] ?? 'development' === 'development') {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
} else {
    mysqli_report(MYSQLI_REPORT_OFF);
}

// Return connection
return $conn;
