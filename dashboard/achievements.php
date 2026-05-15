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

// Add certificate_url column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE achievements ADD COLUMN certificate_url VARCHAR(500) DEFAULT ''");
} catch (PDOException $e) {
    // Column already exists
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE achievements SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $_SESSION['success_msg'] = "Achievement deleted.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $edit_id = $_POST['id'];
        $title = $_POST['title'] ?? '';
        $date_earned = $_POST['date_earned'] ?? null;
        $description = $_POST['description'] ?? '';
        $certificate_url = $_POST['certificate_url'] ?? '';

        $stmt = $pdo->prepare("UPDATE achievements SET title = ?, date_earned = ?, description = ?, certificate_url = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $date_earned, $description, $certificate_url, $edit_id, $user_id]);
        $_SESSION['success_msg'] = "Achievement updated.";
    } else {
        $title = $_POST['title'] ?? '';
        $date_earned = $_POST['date_earned'] ?? null;
        $description = $_POST['description'] ?? '';
        $certificate_url = $_POST['certificate_url'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO achievements (user_id, title, date_earned, description, certificate_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $date_earned, $description, $certificate_url]);
        $_SESSION['success_msg'] = "Achievement added.";
    }
    header('Location: achievements.php');
    exit;
}

// Check if editing
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM achievements WHERE id = ? AND user_id = ? AND is_deleted = 0");
    $stmt->execute([$edit_id, $user_id]);
    $edit_data = $stmt->fetch();
}

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

$stmt = $pdo->prepare("SELECT * FROM achievements WHERE user_id = ? AND is_deleted = 0 ORDER BY date_earned DESC");
$stmt->execute([$user_id]);
$achievements = $stmt->fetchAll();
?>
<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <div class="glass-panel">
        <h2>Manage Achievements</h2>
        
        <?php if ($success): ?>
            <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <h3><?php echo $edit_data ? 'Edit Achievement' : 'Add New Achievement'; ?></h3>
            <?php if ($edit_data): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
            <?php endif; ?>
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required placeholder="e.g. Hackathon Winner" value="<?php echo htmlspecialchars($edit_data['title'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Date Earned</label>
                <input type="date" name="date_earned" required value="<?php echo htmlspecialchars($edit_data['date_earned'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Describe the achievement..."><?php echo htmlspecialchars($edit_data['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Certificate URL (Optional)</label>
                <input type="url" name="certificate_url" placeholder="e.g. https://example.com/certificate.pdf" value="<?php echo htmlspecialchars($edit_data['certificate_url'] ?? ''); ?>">
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn"><?php echo $edit_data ? 'Update Achievement' : 'Add Achievement'; ?></button>
                <?php if ($edit_data): ?>
                    <a href="achievements.php" class="btn" style="background: rgba(255,255,255,0.1); text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="glass-panel">
        <h3>Your Achievements</h3>
        <?php if (count($achievements) > 0): ?>
            <div class="card-grid">
                <?php foreach ($achievements as $ach): ?>
                    <div class="card">
                        <h4 style="margin-bottom: 0.2rem; color: var(--accent);"><i class="fas fa-award"></i> <?php echo htmlspecialchars($ach['title']); ?></h4>
                        <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-calendar-alt"></i> <?php echo $ach['date_earned']; ?>
                        </div>
                        <p style="margin-bottom: 1rem; font-size: 0.95rem;"><?php echo htmlspecialchars($ach['description']); ?></p>
                        <?php if (!empty($ach['certificate_url'])): ?>
                            <div style="margin-bottom: 1rem;">
                                <a href="<?php echo htmlspecialchars($ach['certificate_url']); ?>" target="_blank" class="btn" style="width: auto; padding: 0.3rem 0.8rem; font-size: 0.8rem; background: rgba(100,200,255,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;"><i class="fas fa-certificate"></i> View Certificate</a>
                            </div>
                        <?php endif; ?>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="achievements.php?edit=<?php echo $ach['id']; ?>" class="btn" style="width: auto; padding: 0.3rem 0.8rem; font-size: 0.8rem; background: rgba(100,200,255,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;"><i class="fas fa-edit"></i> Edit</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $ach['id']; ?>">
                                <button type="submit" class="btn btn-danger" style="width: auto; padding: 0.3rem 0.8rem; font-size: 0.8rem;"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted);">No achievements found. Add some to stand out!</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'inc/foot.php'; ?>
