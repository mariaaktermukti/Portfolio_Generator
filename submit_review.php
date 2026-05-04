<?php
require_once 'config/config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rev_user_id = $_POST['rev_user_id'];
    $name = trim($_POST['reviewer_name']);
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    $username = $_POST['username'] ?? '';

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO reviews (reviewed_user_id, reviewer_name, rating, comment) VALUES (?,?,?,?)");
        $stmt->execute([$rev_user_id, $name, $rating, $comment]);
        $pdo->commit();
        header("Location: portfolio.php?user=" . urlencode($username));
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Review submission failed: " . $e->getMessage());
    }
}
?>