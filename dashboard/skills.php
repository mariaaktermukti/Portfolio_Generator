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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE skills SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $_SESSION['success_msg'] = "Skill deleted.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $edit_id = $_POST['id'];
        $skill_name = $_POST['skill_name'] ?? '';
        $proficiency = (int)($_POST['proficiency'] ?? 0);

        $stmt = $pdo->prepare("UPDATE skills SET skill_name = ?, proficiency = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$skill_name, $proficiency, $edit_id, $user_id]);
        $_SESSION['success_msg'] = "Skill updated.";
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

// Check if editing
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM skills WHERE id = ? AND user_id = ? AND is_deleted = 0");
    $stmt->execute([$edit_id, $user_id]);
    $edit_data = $stmt->fetch();
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
            <h3><?php echo $edit_data ? 'Edit Skill' : 'Add New Skill'; ?></h3>
            <?php if ($edit_data): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
            <?php endif; ?>
            <div class="form-group">
                <label>Skill Name</label>
                <input type="text" name="skill_name" required placeholder="e.g. PHP, MySQL, CSS" value="<?php echo htmlspecialchars($edit_data['skill_name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Proficiency (%)</label>
                <input type="number" name="proficiency" min="1" max="100" required placeholder="80" value="<?php echo htmlspecialchars($edit_data['proficiency'] ?? ''); ?>">
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn"><?php echo $edit_data ? 'Update Skill' : 'Add Skill'; ?></button>
                <?php if ($edit_data): ?>
                    <a href="skills.php" class="btn" style="background: rgba(255,255,255,0.1); text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Cancel</a>
                <?php endif; ?>
            </div>
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
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div class="skill-bar" style="flex: 1;">
                                    <div class="skill-progress" style="width: <?php echo $skill['proficiency']; ?>%;"></div>
                                </div>
                                <span style="color: var(--text-muted); font-size: 0.9rem; min-width: 45px;"><?php echo $skill['proficiency']; ?>%</span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="skills.php?edit=<?php echo $skill['id']; ?>" class="btn" style="padding: 0.3rem 0.6rem; width: auto; font-size: 0.8rem; background: rgba(100,200,255,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;" title="Edit"><i class="fas fa-edit"></i></a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $skill['id']; ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 0.3rem 0.6rem; width: auto; font-size: 0.8rem;" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted);">No skills found. Add your first one above!</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'inc/foot.php'; ?>