<?php
require_once '../config/db.php';

// Add status column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE reviews ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
} catch (PDOException $e) {
    // Column already exists
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? 0;
    $username = $_POST['username'] ?? '';
    $visitor_name = $_POST['visitor_name'] ?? '';
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = $_POST['comment'] ?? '';

    if ($user_id && $rating >= 1 && $rating <= 5) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO reviews (user_id, visitor_name, rating, comment, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $visitor_name, $rating, $comment]);
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            // In a real app, log error or show it. Here we just fallback.
        }
    }
    header("Location: portfolio.php?user=" . urlencode($username) . "&review_submitted=1");
    exit;
} else {
    header("Location: ../index.php");
    exit;
}
