<?php
session_start();
require_once '../config/db.php';
require_once '../config/imgbb.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$edit_data = null;
$edit_id = null;

// Add skill_group and image_url columns if they don't exist
try {
    $pdo->exec("ALTER TABLE skills ADD COLUMN skill_group VARCHAR(100) DEFAULT 'Other'"); // Add skill_group column with default value 'Other'
} catch (PDOException $e) {
    // Column already exists
}

try {
    $pdo->exec("ALTER TABLE skills ADD COLUMN image_url VARCHAR(500) DEFAULT ''"); // Add image_url column to store the URL of the skill image
} catch (PDOException $e) {
    // Column already exists
}

// Function to upload image to imgbb
function uploadToImgbb($filePath) {
    $postFields = array(
        'image' => new CURLFile($filePath)
    );
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.imgbb.com/1/upload?key=' . IMGBB_API_KEY,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
    ));
    
    $response = curl_exec($curl);
    curl_close($curl);
    
    $result = json_decode($response, true);
    
    if ($result['success']) {
        return $result['data']['url'];
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE skills SET is_deleted = 1 WHERE id = ? AND user_id = ?"); // Soft delete by setting is_deleted flag
        $stmt->execute([$id, $user_id]);
        $_SESSION['success_msg'] = "Skill deleted.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $edit_id = $_POST['id']; // Get the ID of the skill being edited
        $skill_name = $_POST['skill_name'] ?? ''; // Get the skill name from the form input
        $proficiency = (int)($_POST['proficiency'] ?? 0); //  Get the proficiency percentage and cast it to an integer
        $skill_group = $_POST['skill_group'] ?? 'Other'; // Get the skill group from the form input, default to 'Other' if not provided
        $image_url = $_POST['image_url'] ?? ''; // Get the existing image URL from the hidden input field in the form, which is used if the user does not upload a new image

        // Handle new image upload
        if (isset($_FILES['skill_image']) && $_FILES['skill_image']['size'] > 0) {
            $fileTmpPath = $_FILES['skill_image']['tmp_name'];
            $fileSize = $_FILES['skill_image']['size'];
            $fileType = $_FILES['skill_image']['type'];
            
            // Validate file (max 5MB)
            if ($fileSize > 5 * 1024 * 1024) {
                $_SESSION['error_msg'] = "File too large. Maximum size is 5MB.";
            } else if (!in_array($fileType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                $_SESSION['error_msg'] = "Invalid file type. Please upload an image.";
            } else {
                $imgbbUrl = uploadToImgbb($fileTmpPath);
                if ($imgbbUrl) {
                    $image_url = $imgbbUrl;
                } else {
                    $_SESSION['error_msg'] = "Failed to upload image. Please try again.";
                }
            }
        }

        $stmt = $pdo->prepare("UPDATE skills SET skill_name = ?, proficiency = ?, skill_group = ?, image_url = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$skill_name, $proficiency, $skill_group, $image_url, $edit_id, $user_id]);
        $_SESSION['success_msg'] = "Skill updated.";
    } else {
        $skill_name = $_POST['skill_name'] ?? '';
        $proficiency = (int)($_POST['proficiency'] ?? 0);
        $skill_group = $_POST['skill_group'] ?? 'Other';
        $image_url = '';

        // Handle image upload
        if (isset($_FILES['skill_image']) && $_FILES['skill_image']['size'] > 0) {
            $fileTmpPath = $_FILES['skill_image']['tmp_name'];
            $fileSize = $_FILES['skill_image']['size'];
            $fileType = $_FILES['skill_image']['type'];
            
            // Validate file (max 5MB)
            if ($fileSize > 5 * 1024 * 1024) {
                $_SESSION['error_msg'] = "File too large. Maximum size is 5MB.";
                header('Location: skills.php');
                exit;
            } else if (!in_array($fileType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                $_SESSION['error_msg'] = "Invalid file type. Please upload an image.";
                header('Location: skills.php');
                exit;
            } else {
                $imgbbUrl = uploadToImgbb($fileTmpPath);
                if ($imgbbUrl) {
                    $image_url = $imgbbUrl;
                } else {
                    $_SESSION['error_msg'] = "Failed to upload image. Please try again.";
                    header('Location: skills.php');
                    exit;
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO skills (user_id, skill_name, proficiency, skill_group, image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $skill_name, $proficiency, $skill_group, $image_url]);
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

if (isset($_SESSION['error_msg'])) {
    $error = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

$stmt = $pdo->prepare("SELECT * FROM skills WHERE user_id = ? AND is_deleted = 0 ORDER BY skill_group, proficiency DESC");
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
        
        <?php if (isset($error)): ?>
            <div class="msg-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <h3><?php echo $edit_data ? 'Edit Skill' : 'Add New Skill'; ?></h3>
            <?php if ($edit_data): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                <input type="hidden" name="image_url" value="<?php echo htmlspecialchars($edit_data['image_url'] ?? ''); ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Skill Name</label>
                <input type="text" name="skill_name" required placeholder="e.g. PHP, Python, JavaScript" value="<?php echo htmlspecialchars($edit_data['skill_name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Skill Group</label>
                <input type="text" name="skill_group" required placeholder="e.g. Programming Languages, Databases, Tools" value="<?php echo htmlspecialchars($edit_data['skill_group'] ?? 'Other'); ?>">
            </div>
            
            <div class="form-group">
                <label>Proficiency (%)</label>
                <input type="number" name="proficiency" min="1" max="100" required placeholder="80" value="<?php echo htmlspecialchars($edit_data['proficiency'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Skill Image (Optional)</label>
                <input type="file" name="skill_image" accept="image/jpeg,image/png,image/gif,image/webp" placeholder="Upload skill icon/logo">
                <small style="color: var(--text-muted);">Max 5MB. Formats: JPG, PNG, GIF, WebP</small>
                <?php if ($edit_data && !empty($edit_data['image_url'])): ?>
                    <div style="margin-top: 0.5rem;">
                        <img src="<?php echo htmlspecialchars($edit_data['image_url']); ?>" alt="Skill Image" style="max-width: 100px; max-height: 100px; border-radius: 8px;">
                    </div>
                <?php endif; ?>
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
            <?php
            // Group skills by skill_group
            $grouped_skills = [];
            foreach ($skills as $skill) {
                $group = $skill['skill_group'] ?? 'Other';
                if (!isset($grouped_skills[$group])) {
                    $grouped_skills[$group] = [];
                }
                $grouped_skills[$group][] = $skill;
            }
            ?>
            <?php foreach ($grouped_skills as $group_name => $group_skills): ?>
                <div style="margin-bottom: 2rem;">
                    <h4 style="color: var(--accent); margin-bottom: 1rem; font-size: 1.2rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-folder"></i> <?php echo htmlspecialchars($group_name); ?>
                    </h4>
                    <div class="card-grid">
                        <?php foreach ($group_skills as $skill): ?>
                            <div class="card" style="display: flex; flex-direction: column; gap: 1rem;">
                                <?php if (!empty($skill['image_url'])): ?>
                                    <div style="width: 100%; height: 120px; border-radius: 8px; overflow: hidden; background: rgba(59, 130, 246, 0.1);">
                                        <img src="<?php echo htmlspecialchars($skill['image_url']); ?>" alt="Skill" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <strong style="font-size: 1rem;"><?php echo htmlspecialchars($skill['skill_name']); ?></strong>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                                        <div class="skill-bar" style="flex: 1;">
                                            <div class="skill-progress" style="width: <?php echo $skill['proficiency']; ?>%;"></div>
                                        </div>
                                        <span style="color: var(--text-muted); font-size: 0.9rem; min-width: 45px;"><?php echo $skill['proficiency']; ?>%</span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem; margin-top: auto;">
                                    <a href="skills.php?edit=<?php echo $skill['id']; ?>" class="btn" style="padding: 0.4rem 0.8rem; width: auto; font-size: 0.85rem; background: rgba(100,200,255,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem; flex: 1; justify-content: center;" title="Edit"><i class="fas fa-edit"></i> Edit</a>
                                    <form method="POST" style="display: inline; flex: 1;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $skill['id']; ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 0.4rem 0.8rem; width: 100%; font-size: 0.85rem;" title="Delete" onclick="return confirm('Are you sure?');"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: var(--text-muted);">No skills found. Add your first one above!</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'inc/foot.php'; ?>