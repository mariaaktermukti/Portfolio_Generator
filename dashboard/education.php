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
        $stmt = $pdo->prepare("UPDATE education SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $_SESSION['success_msg'] = "Education entry deleted.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $edit_id = $_POST['id'];
        $degree = $_POST['degree'] ?? '';
        $institution = $_POST['institution'] ?? '';
        $start_date = $_POST['start_date'] ?? null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $description = $_POST['description'] ?? '';

        $stmt = $pdo->prepare("UPDATE education SET degree = ?, institution = ?, start_date = ?, end_date = ?, description = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$degree, $institution, $start_date, $end_date, $description, $edit_id, $user_id]);
        $_SESSION['success_msg'] = "Education entry updated.";
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

// Check if editing
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM education WHERE id = ? AND user_id = ? AND is_deleted = 0");
    $stmt->execute([$edit_id, $user_id]);
    $edit_data = $stmt->fetch();
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
            <h3><?php echo $edit_data ? 'Edit Education' : 'Add New Education'; ?></h3>
            <?php if ($edit_data): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
            <?php endif; ?>
            <div class="form-group">
                <label>Degree / Certificate</label>
                <input type="text" name="degree" required placeholder="e.g. BSc in Computer Science" value="<?php echo htmlspecialchars($edit_data['degree'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Institution</label>
                <input type="text" name="institution" required placeholder="e.g. MIT" value="<?php echo htmlspecialchars($edit_data['institution'] ?? ''); ?>">
            </div>
            
            <div class="form-group" style="display: flex; gap: 1rem;">
                <div style="flex: 1;">
                    <label>Start Date</label>
                    <input type="date" name="start_date" required value="<?php echo htmlspecialchars($edit_data['start_date'] ?? ''); ?>">
                </div>
                <div style="flex: 1;">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($edit_data['end_date'] ?? ''); ?>">
                    <small style="color: var(--text-muted);">Leave empty if currently studying</small>
                </div>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Briefly describe what you studied..."><?php echo htmlspecialchars($edit_data['description'] ?? ''); ?></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn"><?php echo $edit_data ? 'Update Education' : 'Add Education'; ?></button>
                <?php if ($edit_data): ?>
                    <a href="education.php" class="btn" style="background: rgba(255,255,255,0.1); text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Cancel</a>
                <?php endif; ?>
            </div>
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
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="education.php?edit=<?php echo $edu['id']; ?>" class="btn" style="width: auto; padding: 0.4rem 1rem; font-size: 0.8rem; background: rgba(100,200,255,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem; border-radius: 6px;"><i class="fas fa-edit"></i> Edit</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $edu['id']; ?>">
                                <button type="submit" class="btn btn-danger" style="width: auto; padding: 0.4rem 1rem; font-size: 0.8rem;"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted);">No education records found. Add your first one above!</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'inc/foot.php'; ?>