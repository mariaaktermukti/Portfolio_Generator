<?php
// Database Configuration - Local and Online

// Detect environment based on hostname
$hostname = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = stripos($hostname, 'localhost') !== false || stripos($hostname, '127.0.0.1') !== false;

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
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
