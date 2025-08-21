<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'loan_application_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application settings
define('BASE_URL', 'http://localhost/loan_application/');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/loan_application/assets/uploads/');
define('SIGNATURE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/loan_application/assets/signatures/');

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
