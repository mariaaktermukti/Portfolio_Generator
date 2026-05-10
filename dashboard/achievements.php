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
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE achievements SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $_SESSION['success_msg'] = "Achievement deleted.";
    } else {
        $title = $_POST['title'] ?? '';
        $date_earned = $_POST['date_earned'] ?? null;
        $description = $_POST['description'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO achievements (user_id, title, date_earned, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $date_earned, $description]);
        $_SESSION['success_msg'] = "Achievement added.";
    }
    header('Location: achievements.php');
    exit;
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
            <h3>Add New Achievement</h3>
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required placeholder="e.g. Hackathon Winner">
            </div>
            
            <div class="form-group">
                <label>Date Earned</label>
                <input type="date" name="date_earned" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Describe the achievement..."></textarea>
            </div>
            
            <button type="submit" class="btn">Add Achievement</button>
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
                        <form method="POST">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $ach['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="width: auto; padding: 0.3rem 0.8rem; font-size: 0.8rem;"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted);">No achievements found. Add some to stand out!</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'inc/foot.php'; ?>
