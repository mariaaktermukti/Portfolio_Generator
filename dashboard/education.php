<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';
$edit_data = null;
$edit_id = null;

// Add result column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE education ADD COLUMN result VARCHAR(50) DEFAULT ''");
} catch (PDOException $e) {
    // Column already exists
}

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
        $result = $_POST['result'] ?? '';

        if ($start_date && $end_date && $end_date < $start_date) {
            $_SESSION['error_msg'] = "End date cannot be earlier than start date.";
            header('Location: education.php?edit=' . $edit_id);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE education SET degree = ?, institution = ?, start_date = ?, end_date = ?, description = ?, result = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$degree, $institution, $start_date, $end_date, $description, $result, $edit_id, $user_id]);
        $_SESSION['success_msg'] = "Education entry updated.";
    } else {
        $degree = $_POST['degree'] ?? '';
        $institution = $_POST['institution'] ?? '';
        $start_date = $_POST['start_date'] ?? null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $description = $_POST['description'] ?? '';
        $result = $_POST['result'] ?? '';

        if ($start_date && $end_date && $end_date < $start_date) {
            $_SESSION['error_msg'] = "End date cannot be earlier than start date.";
            header('Location: education.php');
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO education (user_id, degree, institution, start_date, end_date, description, result) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $degree, $institution, $start_date, $end_date, $description, $result]);
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

if (isset($_SESSION['error_msg'])) {
    $error = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
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
        <?php if ($error): ?>
            <div class="msg-error" style="color: #ff4d4d; background: rgba(255, 77, 77, 0.1); padding: 10px; border-radius: 5px; margin-bottom: 15px; border: 1px solid rgba(255, 77, 77, 0.3);"><?php echo htmlspecialchars($error); ?></div>
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
            
            <div class="form-group">
                <label>Result / CGPA / GPA</label>
                <input type="text" name="result" placeholder="e.g. GPA 5.00 or CGPA 3.85" value="<?php echo htmlspecialchars($edit_data['result'] ?? ''); ?>">
            </div>
            
            <div class="form-group" style="display: flex; gap: 1rem;">
                <div style="flex: 1;">
                    <label>Start Date</label>
                    <input type="text" class="date-picker" name="start_date" required placeholder="YYYY-MM-DD" value="<?php echo htmlspecialchars($edit_data['start_date'] ?? ''); ?>">
                </div>
                <div style="flex: 1;">
                    <label>End Date</label>
                    <input type="text" class="date-picker" name="end_date" placeholder="YYYY-MM-DD" value="<?php echo htmlspecialchars($edit_data['end_date'] ?? ''); ?>">
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

    <!-- Add modern datepicker library -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <style>
        /* Custom calendar theme to match the modern project theme */
        .flatpickr-calendar.dark {
            background: var(--bg-secondary);
            box-shadow: var(--glass-shadow);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-family: inherit;
        }
        .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange, .flatpickr-day.selected.inRange, .flatpickr-day.startRange.inRange, .flatpickr-day.endRange.inRange, .flatpickr-day.selected:focus, .flatpickr-day.startRange:focus, .flatpickr-day.endRange:focus, .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover, .flatpickr-day.selected.prevMonthDay, .flatpickr-day.startRange.prevMonthDay, .flatpickr-day.endRange.prevMonthDay, .flatpickr-day.selected.nextMonthDay, .flatpickr-day.startRange.nextMonthDay, .flatpickr-day.endRange.nextMonthDay {
            background: var(--accent);
            border-color: var(--accent);
        }
        .flatpickr-day:hover, .flatpickr-day.prevMonthDay:hover, .flatpickr-day.nextMonthDay:hover, .flatpickr-day:focus, .flatpickr-day.prevMonthDay:focus, .flatpickr-day.nextMonthDay:focus {
            background: var(--bg-tertiary);
            border-color: var(--border);
        }
        .flatpickr-months .flatpickr-month {
            background: transparent;
            color: var(--text-main);
        }
        .flatpickr-current-month .flatpickr-monthDropdown-months {
            background: var(--bg-secondary);
            color: var(--text-main);
            outline: none;
        }
        .flatpickr-weekdays {
            background: transparent;
        }
        span.flatpickr-weekday {
            color: var(--text-muted);
        }
        input.date-picker {
            cursor: pointer;
            background-color: var(--glass-bg); /* Match existing inputs */
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize End Date internally first so we can reference it
            const endDateInput = document.querySelector('input[name="end_date"]');
            const endDatePicker = flatpickr(endDateInput, {
                theme: "dark",
                dateFormat: "Y-m-d",
                allowInput: true
            });

            // Initialize Start Date and bind validation dynamically
            const startDateInput = document.querySelector('input[name="start_date"]');
            const startDatePicker = flatpickr(startDateInput, {
                theme: "dark",
                dateFormat: "Y-m-d",
                allowInput: true,
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length > 0) {
                        endDatePicker.set("minDate", selectedDates[0]);
                        // If current end date is earlier, clear it
                        if (endDatePicker.selectedDates.length > 0 && endDatePicker.selectedDates[0] < selectedDates[0]) {
                            endDatePicker.clear();
                        }
                    } else {
                        endDatePicker.set("minDate", null);
                    }
                }
            });

            // On load, apply initial min date if start date exists
            if (startDateInput.value) {
                endDatePicker.set("minDate", startDateInput.value);
            }
        });
    </script>

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
                            <?php if (!empty($edu['result'])): ?>
                                <br><i class="fas fa-graduation-cap"></i> Result: <strong><?php echo htmlspecialchars($edu['result']); ?></strong>
                            <?php endif; ?>
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