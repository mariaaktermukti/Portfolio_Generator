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
        $stmt = $pdo->prepare("UPDATE education SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $_SESSION['success_msg'] = "Education entry deleted.";
    } else {
        $degree = $_POST['degree'] ?? '';
        $institution = $_POST['institution'] ?? '';
        $start_date = $_POST['start_date'] ?? null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $description = $_POST['description'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO education (user_id, degree, institution, start_date, end_date, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $degree, $institution, $start_date, $end_date, $description]);
        $_SESSION['success_msg'] = "Education entry added.";
    }
    header('Location: education.php');
    exit;
}

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

$stmt = $pdo->prepare("SELECT * FROM education WHERE user_id = ? AND is_deleted = 0 ORDER BY start_date DESC");
$stmt->execute([$user_id]);
$educations = $stmt->fetchAll();
?>
<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <div class="glass-panel">
        <h2>Manage Education</h2>
        
        <?php if ($success): ?>
            <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <h3>Add New Education</h3>
            <div class="form-group">
                <label>Degree / Certificate</label>
                <input type="text" name="degree" required placeholder="e.g. BSc in Computer Science">
            </div>
            
            <div class="form-group">
                <label>Institution</label>
                <input type="text" name="institution" required placeholder="e.g. MIT">
            </div>
            
            <div class="form-group" style="display: flex; gap: 1rem;">
                <div style="flex: 1;">
                    <label>Start Date</label>
                    <input type="date" name="start_date" required>
                </div>
                <div style="flex: 1;">
                    <label>End Date</label>
                    <input type="date" name="end_date">
                    <small style="color: var(--text-muted);">Leave empty if currently studying</small>
                </div>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Briefly describe what you studied..."></textarea>
            </div>
            
            <button type="submit" class="btn">Add Education</button>
        </form>
    </div>

    <div class="glass-panel">
        <h3>Your Education History</h3>
        <?php if (count($educations) > 0): ?>
            <div class="timeline">
                <?php foreach ($educations as $edu): ?>
                    <div class="timeline-item">
                        <h4 style="margin-bottom: 0.2rem;"><?php echo htmlspecialchars($edu['degree']); ?></h4>
                        <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-university"></i> <?php echo htmlspecialchars($edu['institution']); ?> | 
                            <i class="fas fa-calendar-alt"></i> <?php echo $edu['start_date']; ?> to <?php echo $edu['end_date'] ?: 'Present'; ?>
                        </div>
                        <p style="margin-bottom: 1rem;"><?php echo htmlspecialchars($edu['description']); ?></p>
                        <form method="POST">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $edu['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="width: auto; padding: 0.4rem 1rem; font-size: 0.8rem;"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted);">No education records found. Add your first one above!</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'inc/foot.php'; ?>