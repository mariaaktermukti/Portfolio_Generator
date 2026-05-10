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
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $linkedin = $_POST['linkedin'] ?? '';
    $github = $_POST['github'] ?? '';

    $stmt = $pdo->prepare("SELECT id FROM contact WHERE user_id = ? AND is_deleted = 0");
    $stmt->execute([$user_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        $stmt = $pdo->prepare("UPDATE contact SET phone = ?, address = ?, linkedin = ?, github = ? WHERE user_id = ? AND is_deleted = 0");
        $stmt->execute([$phone, $address, $linkedin, $github, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO contact (user_id, phone, address, linkedin, github) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $phone, $address, $linkedin, $github]);
    }
    
    $_SESSION['success_msg'] = "Contact information updated successfully!";
    header('Location: contact.php');
    exit;
}

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

$stmt = $pdo->prepare("SELECT * FROM contact WHERE user_id = ? AND is_deleted = 0");
$stmt->execute([$user_id]);
$contact = $stmt->fetch() ?: ['phone' => '', 'address' => '', 'linkedin' => '', 'github' => ''];

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
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($contact['phone']); ?>" placeholder="+1 234 567 890">
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="3" placeholder="City, Country"><?php echo htmlspecialchars($contact['address']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>LinkedIn URL</label>
                <input type="url" name="linkedin" value="<?php echo htmlspecialchars($contact['linkedin']); ?>" placeholder="https://linkedin.com/in/username">
            </div>
            
            <div class="form-group">
                <label>GitHub URL</label>
                <input type="url" name="github" value="<?php echo htmlspecialchars($contact['github']); ?>" placeholder="https://github.com/username">
            </div>
            
            <button type="submit" class="btn">Save Changes</button>
        </form>
    </div>
</main>

<?php include 'inc/foot.php'; ?>