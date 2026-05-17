<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$edit_data = null;
$edit_id = null;

try {
    $pdo->exec("ALTER TABLE about ADD COLUMN about_image VARCHAR(255) DEFAULT ''");
} catch (PDOException $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE about SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $_SESSION['success_msg'] = "About entry deleted.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $edit_id = $_POST['id'];
        $bio = $_POST['bio'] ?? '';
        $title = $_POST['title'] ?? '';
        $profile_image = $_POST['profile_image'] ?? '';
        $about_image = $_POST['about_image'] ?? '';
        $stmt = $pdo->prepare("UPDATE about SET bio = ?, title = ?, profile_image = ?, about_image = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$bio, $title, $profile_image, $about_image, $edit_id, $user_id]);
        $_SESSION['success_msg'] = "About entry updated.";
    } else {
        $bio = $_POST['bio'] ?? '';
        $title = $_POST['title'] ?? '';
        $profile_image = $_POST['profile_image'] ?? '';
        $about_image = $_POST['about_image'] ?? '';
        $stmt = $pdo->prepare("INSERT INTO about (user_id, bio, title, profile_image, about_image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $bio, $title, $profile_image, $about_image]);
        $_SESSION['success_msg'] = "About entry added.";
    }
    header('Location: about.php');
    exit;
}

if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM about WHERE id = ? AND user_id = ? AND is_deleted = 0");
    $stmt->execute([$edit_id, $user_id]);
    $edit_data = $stmt->fetch();
}
if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}
$stmt = $pdo->prepare("SELECT * FROM about WHERE user_id = ? AND is_deleted = 0 ORDER BY id DESC");
$stmt->execute([$user_id]);
$abouts = $stmt->fetchAll();
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
            <h3><?php echo $edit_data ? 'Edit About Information' : 'Add About Information'; ?></h3>
            <?php if ($edit_data): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
            <?php endif; ?>
            <div class="form-group">
                <label>Professional Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($edit_data['title'] ?? ''); ?>" required placeholder="e.g. Full Stack Developer">
            </div>
            <div class="form-group">
                <label>Profile Image URL</label>
                <input type="text" name="profile_image" value="<?php echo htmlspecialchars($edit_data['profile_image'] ?? ''); ?>" placeholder="Enter image URL">
            </div>
            <div class="form-group">
                <label>About Image URL</label>
                <input type="text" name="about_image" value="<?php echo htmlspecialchars($edit_data['about_image'] ?? ''); ?>" placeholder="Enter image URL">
            </div>
            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" rows="5" required placeholder="Write a short bio about yourself..."><?php echo htmlspecialchars($edit_data['bio'] ?? ''); ?></textarea>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn"><?php echo $edit_data ? 'Update About' : 'Save About'; ?></button>
                <?php if ($edit_data): ?>
                    <a href="about.php" class="btn btn-outline" style="text-decoration: none; padding: 0.8rem 1.5rem; border: 1px solid var(--border); border-radius: 8px; color: #fff;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>

        <h3 style="margin-top: 3rem;">Saved About Information</h3>
        <div class="list-container" style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
            <?php foreach ($abouts as $item): ?>
                <div class="list-item glass-panel" style="padding: 1rem; display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px;">
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <?php if (!empty($item['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($item['profile_image']); ?>" alt="Profile" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
                        <?php endif; ?>
                        <div>
                            <h4 style="margin: 0;"><?php echo htmlspecialchars($item['title']); ?></h4>
                            <p style="margin: 0.5rem 0 0 0; color: #aaa; font-size: 0.9rem;"><?php echo htmlspecialchars(substr($item['bio'], 0, 100)); ?></p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="?edit=<?php echo $item['id']; ?>" class="btn" style="padding: 0.5rem 1rem; font-size: 0.9rem; text-decoration: none;">Edit</a>
                        <form method="POST" style="margin: 0; display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="btn" onclick="return confirm('Are you sure you want to delete this entry?')" style="background: rgba(255, 50, 50, 0.2); border: 1px solid rgba(255, 50, 50, 0.4); padding: 0.5rem 1rem; font-size: 0.9rem; color: #fff;">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($abouts)): ?>
                <p style="color: #aaa;">No about information saved yet.</p>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php include 'inc/foot.php'; ?>