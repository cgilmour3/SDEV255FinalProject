<?php
/**
 * db_connect.php
 * Establishes a connection to the MySQL database.
 */

// Database configuration
$servername = "localhost"; // Usually localhost for XAMPP
$username = "root";        // Default XAMPP username
$password = "";            // Default XAMPP password (empty)
$dbname = "StudentDB";     // The database name

// Create connection using mysqli 
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Use die() for critical connection errors during development.
    // In production, log the error and show a user-friendly message.
    error_log("Database Connection Failed: " . $conn->connect_error); // Log the error
    die("Database connection failed. Please try again later."); // User message
}

// Set character set to utf8mb4 for broader character support
if (!$conn->set_charset("utf8mb4")) {
    error_log("Error loading character set utf8mb4: " . $conn->error);
    // Execution can often continue, but log the error.
}

?>
