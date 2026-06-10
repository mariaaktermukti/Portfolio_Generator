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

if (isset($_SESSION['error_msg'])) {
    $error = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE work_experience SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $_SESSION['success_msg'] = "Work experience deleted.";
    } else if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $job_title = $_POST['job_title'] ?? '';
        $company = $_POST['company'] ?? '';
        $start_date = $_POST['start_date'] ?? null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $description = $_POST['description'] ?? '';

        if ($start_date && $end_date && $end_date < $start_date) {
            $_SESSION['error_msg'] = "End date cannot be earlier than start date.";
            header('Location: work.php');
            exit;
        }

        $stmt = $pdo->prepare("UPDATE work_experience SET job_title = ?, company = ?, start_date = ?, end_date = ?, description = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$job_title, $company, $start_date, $end_date, $description, $id, $user_id]);
        $_SESSION['success_msg'] = "Work experience updated.";
    } else {
        $job_title = $_POST['job_title'] ?? '';
        $company = $_POST['company'] ?? '';
        $start_date = $_POST['start_date'] ?? null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $description = $_POST['description'] ?? '';

        if ($start_date && $end_date && $end_date < $start_date) {
            $_SESSION['error_msg'] = "End date cannot be earlier than start date.";
            header('Location: work.php');
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO work_experience (user_id, job_title, company, start_date, end_date, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $job_title, $company, $start_date, $end_date, $description]);
        $_SESSION['success_msg'] = "Work experience added.";
    }
    header('Location: work.php');
    exit;
}

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

$stmt = $pdo->prepare("SELECT * FROM work_experience WHERE user_id = ? AND is_deleted = 0 ORDER BY start_date DESC");
$stmt->execute([$user_id]);
$works = $stmt->fetchAll();
?>
<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <div class="glass-panel">
        <h2>Manage Work Experience</h2>
        
        <?php if ($success): ?>
            <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="msg-error" style="color: #ff4d4d; background: rgba(255, 77, 77, 0.1); padding: 10px; border-radius: 5px; margin-bottom: 15px; border: 1px solid rgba(255, 77, 77, 0.3);"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" id="workForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="workId" value="">
            <h3 id="formTitle">Add Work Experience</h3>
            <div class="form-group">
                <label>Job Title</label>
                <input type="text" name="job_title" required placeholder="e.g. Senior Software Engineer">
            </div>
            
            <div class="form-group">
                <label>Company</label>
                <input type="text" name="company" required placeholder="e.g. Google">
            </div>
            
            <div class="form-group" style="display: flex; gap: 1rem;">
                <div style="flex: 1;">
                    <label>Start Date</label>
                    <input type="text" class="date-picker" name="start_date" required placeholder="YYYY-MM-DD">
                </div>
                <div style="flex: 1;">
                    <label>End Date</label>
                    <input type="text" class="date-picker" name="end_date" placeholder="YYYY-MM-DD">
                    <small style="color: var(--text-muted);">Leave empty if currently working here</small>
                </div>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Describe your responsibilities..."></textarea>
            </div>
            
            <div style="display: flex; align-items: center;">
                <button type="submit" class="btn" id="submitBtn">Add Work Experience</button>
            </div>
        </form>
    </div>

    <div class="glass-panel">
        <h3>Your Work History</h3>
        <?php if (count($works) > 0): ?>
            <div class="timeline">
                <?php foreach ($works as $work): ?>
                    <div class="timeline-item">
                        <h4 style="margin-bottom: 0.2rem;"><?php echo htmlspecialchars($work['job_title']); ?></h4>
                        <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($work['company']); ?> | 
                            <i class="fas fa-calendar-alt"></i> <?php echo $work['start_date']; ?> to <?php echo $work['end_date'] ?: 'Present'; ?>
                        </div>
                        <p style="margin-bottom: 1rem;"><?php echo htmlspecialchars($work['description']); ?></p>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="button" class="btn edit-work-btn" data-work='<?php echo htmlspecialchars(json_encode($work), ENT_QUOTES, 'UTF-8'); ?>' style="width: auto; padding: 0.4rem 1rem; font-size: 0.8rem; background-color: var(--accent); color: white;"><i class="fas fa-edit"></i> Edit</button>
                            <form method="POST">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $work['id']; ?>">
                                <button type="submit" class="btn btn-danger" style="width: auto; padding: 0.4rem 1rem; font-size: 0.8rem;"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted);">No work experience records found.</p>
        <?php endif; ?>
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

            // Edit button handler
            const editBtns = document.querySelectorAll('.edit-work-btn');
            editBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const work = JSON.parse(this.getAttribute('data-work'));
                    document.getElementById('formTitle').innerText = 'Edit Work Experience';
                    document.getElementById('formAction').value = 'edit';
                    document.getElementById('workId').value = work.id;
                    
                    document.querySelector('input[name="job_title"]').value = work.job_title;
                    document.querySelector('input[name="company"]').value = work.company;
                    document.querySelector('textarea[name="description"]').value = work.description || '';
                    
                    startDatePicker.setDate(work.start_date);
                    if (work.end_date && work.end_date !== '0000-00-00') {
                        endDatePicker.setDate(work.end_date);
                    } else {
                        endDatePicker.clear();
                    }
                    
                    document.getElementById('submitBtn').innerText = 'Update Work Experience';
                    
                    if (!document.getElementById('cancelEditBtn')) {
                        const cancelBtn = document.createElement('button');
                        cancelBtn.type = 'button';
                        cancelBtn.id = 'cancelEditBtn';
                        cancelBtn.className = 'btn';
                        cancelBtn.style.marginLeft = '1rem';
                        cancelBtn.style.backgroundColor = 'var(--bg-tertiary)';
                        cancelBtn.style.color = 'var(--text-main)';
                        cancelBtn.innerText = 'Cancel';
                        cancelBtn.onclick = function() {
                            document.getElementById('workForm').reset();
                            document.getElementById('formTitle').innerText = 'Add Work Experience';
                            document.getElementById('formAction').value = 'add';
                            document.getElementById('workId').value = '';
                            document.getElementById('submitBtn').innerText = 'Add Work Experience';
                            startDatePicker.clear();
                            endDatePicker.clear();
                            this.remove();
                        };
                        document.getElementById('submitBtn').parentNode.appendChild(cancelBtn);
                    }
                    
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });
        });
    </script>
</main>

<?php include 'inc/foot.php'; ?>