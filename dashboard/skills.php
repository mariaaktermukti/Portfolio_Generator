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
        $stmt = $pdo->prepare("UPDATE skills SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $_SESSION['success_msg'] = "Skill deleted.";
    } else {
        $skill_name = $_POST['skill_name'] ?? '';
        $proficiency = (int)($_POST['proficiency'] ?? 0);

        $stmt = $pdo->prepare("INSERT INTO skills (user_id, skill_name, proficiency) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $skill_name, $proficiency]);
        $_SESSION['success_msg'] = "Skill added.";
    }
    header('Location: skills.php');
    exit;
}

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

$stmt = $pdo->prepare("SELECT * FROM skills WHERE user_id = ? AND is_deleted = 0 ORDER BY proficiency DESC");
$stmt->execute([$user_id]);
$skills = $stmt->fetchAll();
?>
<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <div class="glass-panel">
        <h2>Manage Skills</h2>
        
        <?php if ($success): ?>
            <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <h3>Add New Skill</h3>
            <div class="form-group">
                <label>Skill Name</label>
                <input type="text" name="skill_name" required placeholder="e.g. PHP, MySQL, CSS">
            </div>
            
            <div class="form-group">
                <label>Proficiency (%)</label>
                <input type="number" name="proficiency" min="1" max="100" required placeholder="80">
            </div>
            
            <button type="submit" class="btn">Add Skill</button>
        </form>
    </div>

    <div class="glass-panel">
        <h3>Your Skills</h3>
        <?php if (count($skills) > 0): ?>
            <div class="card-grid">
                <?php foreach ($skills as $skill): ?>
                    <div class="card" style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="flex: 1; margin-right: 1rem;">
                            <strong><?php echo htmlspecialchars($skill['skill_name']); ?></strong>
                            <div class="skill-bar">
                                <div class="skill-progress" style="width: <?php echo $skill['proficiency']; ?>%;"></div>
                            </div>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $skill['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 0.3rem 0.6rem; width: auto; font-size: 0.8rem;" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted);">No skills found. Add your first one above!</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'inc/foot.php'; ?>