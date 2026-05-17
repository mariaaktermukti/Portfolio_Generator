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

// Add contact_image column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE contact ADD COLUMN contact_image VARCHAR(255) DEFAULT ''");
} catch (PDOException $e) {
    // Column already exists
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE contact SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $_SESSION['success_msg'] = "Contact entry deleted.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $edit_id = $_POST['id'];
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $linkedin = $_POST['linkedin'] ?? '';
        $github = $_POST['github'] ?? '';
        $contact_image = $_POST['contact_image'] ?? '';

        $stmt = $pdo->prepare("UPDATE contact SET phone = ?, address = ?, linkedin = ?, github = ?, contact_image = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$phone, $address, $linkedin, $github, $contact_image, $edit_id, $user_id]);
        $_SESSION['success_msg'] = "Contact entry updated.";
    } else {
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';
        $linkedin = $_POST['linkedin'] ?? '';
        $github = $_POST['github'] ?? '';
        $contact_image = $_POST['contact_image'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO contact (user_id, phone, address, linkedin, github, contact_image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $phone, $address, $linkedin, $github, $contact_image]);
        $_SESSION['success_msg'] = "Contact entry added.";
    }
    
    header('Location: contact.php');
    exit;
}

// Check if editing
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM contact WHERE id = ? AND user_id = ? AND is_deleted = 0");
    $stmt->execute([$edit_id, $user_id]);
    $edit_data = $stmt->fetch();
}

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

$stmt = $pdo->prepare("SELECT * FROM contact WHERE user_id = ? AND is_deleted = 0 ORDER BY id DESC");
$stmt->execute([$user_id]);
$contacts = $stmt->fetchAll();

?>
<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <div class="glass-panel">
        <h2>Manage Contact Section</h2>
        
        <?php if ($success): ?>
            <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <h3><?php echo $edit_data ? 'Edit Contact Information' : 'Add Contact Information'; ?></h3>
            <?php if ($edit_data): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Contact Section Image URL</label>
                <input type="text" name="contact_image" value="<?php echo htmlspecialchars($edit_data['contact_image'] ?? ''); ?>" placeholder="Enter image URL" style="padding: 0.8rem; background: rgba(0,0,0,0.2); border: 1px solid var(--border); border-radius: 8px; color: #fff; width: 100%;">
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($edit_data['phone'] ?? ''); ?>" placeholder="+1 234 567 890">
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="3" placeholder="City, Country"><?php echo htmlspecialchars($edit_data['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>LinkedIn URL</label>
                <input type="url" name="linkedin" value="<?php echo htmlspecialchars($edit_data['linkedin'] ?? ''); ?>" placeholder="https://linkedin.com/in/username">
            </div>
            
            <div class="form-group">
                <label>GitHub URL</label>
                <input type="url" name="github" value="<?php echo htmlspecialchars($edit_data['github'] ?? ''); ?>" placeholder="https://github.com/username">
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn"><?php echo $edit_data ? 'Update Contact' : 'Save Contact'; ?></button>
                <?php if ($edit_data): ?>
                    <a href="contact.php" class="btn btn-outline" style="text-decoration: none; padding: 0.8rem 1.5rem; border: 1px solid var(--border); border-radius: 8px; color: #fff;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>

        <h3 style="margin-top: 3rem;">Saved Contact Information</h3>
        <div class="list-container" style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
            <?php foreach ($contacts as $item): ?>
                <div class="list-item glass-panel" style="padding: 1rem; display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px;">
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <?php if (!empty($item['contact_image'])): ?>
                            <img src="<?php echo htmlspecialchars($item['contact_image']); ?>" alt="Contact Image" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
                        <?php endif; ?>
                        <div>
                            <h4 style="margin: 0;"><?php echo htmlspecialchars($item['phone']); ?></h4>
                            <p style="margin: 0.5rem 0 0 0; color: #aaa; font-size: 0.9rem;"><?php echo htmlspecialchars($item['address']); ?></p>
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
            <?php if (empty($contacts)): ?>
                <p style="color: #aaa;">No contact information saved yet.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'inc/foot.php'; ?>
