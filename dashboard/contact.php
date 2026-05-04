<?php
session_start();
require_once '../config/config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $website = $_POST['website'] ?? '';
    $linkedin = $_POST['linkedin'] ?? '';
    $github = $_POST['github'] ?? '';

    $check = $pdo->prepare("SELECT COUNT(*) FROM contact WHERE user_id = ? AND is_deleted = 0");
    $check->execute([$uid]);
    if ($check->fetchColumn()) {
        $stmt = $pdo->prepare("UPDATE contact SET email=?, phone=?, address=?, website=?, linkedin=?, github=? WHERE user_id=? AND is_deleted=0");
        $stmt->execute([$email, $phone, $address, $website, $linkedin, $github, $uid]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO contact (user_id, email, phone, address, website, linkedin, github) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$uid, $email, $phone, $address, $website, $linkedin, $github]);
    }
    header('Location: contact.php');
    exit;
}

if (isset($_GET['delete'])) {
    $pdo->prepare("UPDATE contact SET is_deleted = 1 WHERE user_id = ?")->execute([$uid]);
    header('Location: contact.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM contact WHERE user_id = ? AND is_deleted = 0");
$stmt->execute([$uid]);
$contact = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head><title>Manage Contact</title></head>
<body>
    <h2>Contact Information</h2>
    <form method="POST">
        Email: <input type="email" name="email" value="<?= htmlspecialchars($contact['email'] ?? '') ?>"><br>
        Phone: <input type="text" name="phone" value="<?= htmlspecialchars($contact['phone'] ?? '') ?>"><br>
        Address: <input type="text" name="address" value="<?= htmlspecialchars($contact['address'] ?? '') ?>"><br>
        Website: <input type="text" name="website" value="<?= htmlspecialchars($contact['website'] ?? '') ?>"><br>
        LinkedIn: <input type="text" name="linkedin" value="<?= htmlspecialchars($contact['linkedin'] ?? '') ?>"><br>
        GitHub: <input type="text" name="github" value="<?= htmlspecialchars($contact['github'] ?? '') ?>"><br><br>
        <button type="submit">Save</button>
        <a href="contact.php?delete=1" onclick="return confirm('Delete contact info?')">Delete (Soft)</a>
    </form>
    <p><a href="index.php">Back to Dashboard</a></p>
</body>
</html>