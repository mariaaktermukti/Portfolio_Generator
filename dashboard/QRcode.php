<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
$username = $_SESSION['username'];

// পোর্টফোলিওর পূর্ণ URL তৈরি (আপনার ডোমেইন/লোকালহোস্ট অনুযায়ী বদলান)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
// বেস URL থেকে public ফোল্ডারে portfolio.php-তে যাওয়ার পাথ
$portfolioUrl = "$protocol://$host/smart_portfolio/public/portfolio.php?user=" . urlencode($username);

// Google Charts API দিয়ে QR ইমেজ তৈরি
$qrImageUrl = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($portfolioUrl) . "&choe=UTF-8";
?>
<!DOCTYPE html>
<html>
<head><title>Your Portfolio QR Code</title></head>
<body>
    <h2>QR Code for Your Portfolio</h2>
    <p>Scan this QR code to view your portfolio:</p>
    <img src="<?= $qrImageUrl ?>" alt="QR Code" style="border:2px solid #000; padding:10px;">
    <p><strong>Your Portfolio Link:</strong> <a href="<?= htmlspecialchars($portfolioUrl) ?>" target="_blank"><?= htmlspecialchars($portfolioUrl) ?></a></p>
    <p><a href="index.php">Back to Dashboard</a></p>
</body>
</html>