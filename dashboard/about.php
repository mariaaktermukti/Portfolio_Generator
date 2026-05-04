<?php
session_start();
require_once '../config/config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
$uid = $_SESSION['user_id'];

// Save / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $profession = $_POST['profession'];
    $bio = $_POST['bio'];
    $image = $_POST['profile_image'] ?? '';

    $check = $pdo->prepare("SELECT COUNT(*) FROM about WHERE user_id = ? AND is_deleted = 0");
    $check->execute([$uid]);
    if ($check->fetchColumn()) {
        $stmt = $pdo->prepare("UPDATE about SET full_name=?, profession=?, bio=?, profile_image=? WHERE user_id=? AND is_deleted=0");
        $stmt->execute([$full_name, $profession, $bio, $image, $uid]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO about (user_id, full_name, profession, bio, profile_image) VALUES (?,?,?,?,?)");
        $stmt->execute([$uid, $full_name, $profession, $bio, $image]);
    }
    header('Location: about.php');
    exit;
}

// Soft delete
if (isset($_GET['delete'])) {
    $pdo->prepare("UPDATE about SET is_deleted = 1 WHERE user_id = ?")->execute([$uid]);
    header('Location: about.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM about WHERE user_id = ? AND is_deleted = 0");
$stmt->execute([$uid]);
$about = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head><title>Manage About</title></head>
<body>
    <h2>About Section</h2>
    <form method="POST">
        Full Name: <input type="text" name="full_name" value="<?= htmlspecialchars($about['full_name'] ?? '') ?>" required><br>
        Profession: <input type="text" name="profession" value="<?= htmlspecialchars($about['profession'] ?? '') ?>"><br>
        Bio: <br><textarea name="bio"><?= htmlspecialchars($about['bio'] ?? '') ?></textarea><br>
        Profile Image URL: <input type="text" name="profile_image" value="<?= htmlspecialchars($about['profile_image'] ?? '') ?>"><br><br>
        <button type="submit">Save</button>
        <a href="about.php?delete=1" onclick="return confirm('Delete about section?')">Delete (Soft)</a>
    </form>
    <p><a href="index.php">Back to Dashboard</a></p>
</body>
</html>