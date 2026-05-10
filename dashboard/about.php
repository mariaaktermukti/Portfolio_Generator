<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = $_POST['bio'] ?? '';
    $title = $_POST['title'] ?? '';
    $profile_image = $_POST['profile_image'] ?? '';

    $stmt = $pdo->prepare("SELECT id FROM about WHERE user_id = ? AND is_deleted = 0");
    $stmt->execute([$user_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        $stmt = $pdo->prepare("UPDATE about SET bio = ?, title = ?, profile_image = ? WHERE user_id = ? AND is_deleted = 0");
        $stmt->execute([$bio, $title, $profile_image, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO about (user_id, bio, title, profile_image) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $bio, $title, $profile_image]);
    }
    
    $_SESSION['success_msg'] = "About information updated successfully!";
    header('Location: about.php');
    exit;
}

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

$stmt = $pdo->prepare("SELECT * FROM about WHERE user_id = ? AND is_deleted = 0");
$stmt->execute([$user_id]);
$about = $stmt->fetch() ?: ['bio' => '', 'title' => '', 'profile_image' => ''];

?>
<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <div class="glass-panel">
        <h2>Manage About Section</h2>
        
        <?php if ($success): ?>
            <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Professional Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($about['title']); ?>" required placeholder="e.g. Full Stack Developer">
            </div>
            
            <div class="form-group">
                <label>Profile Image URL</label>
                <input type="url" name="profile_image" value="<?php echo htmlspecialchars($about['profile_image']); ?>" placeholder="https://example.com/image.jpg">
            </div>
            
            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" rows="5" required placeholder="Write a short bio about yourself..."><?php echo htmlspecialchars($about['bio']); ?></textarea>
            </div>
            
            <button type="submit" class="btn">Save Changes</button>
        </form>
    </div>
</main>

<?php include 'inc/foot.php'; ?>