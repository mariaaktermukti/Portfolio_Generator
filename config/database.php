<?php
/**
 * Database Configuration
 * Portfolio Generator Application
 */

// Detect environment based on hostname
$hostname = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = stripos($hostname, 'localhost') !== false || stripos($hostname, '127.0.0.1') !== false;

// Database credentials
// Local Database Credentials (XAMPP)
if ($isLocal) {
    $host = 'localhost';
    $dbname = 'portfolio_db';
    $user = 'root';
    $pass = '';
} 
// Online Database Credentials (InfinityFree)
else {
    $host = 'sql210.infinityfree.com';
    $dbname = 'if0_42052675_portfolio';
    $user = 'if0_42052675';
    $pass = 'ilovemyjamai13';
}

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
