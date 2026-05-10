<?php
require_once '../config/db.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$username = $_GET['user'] ?? '';

if (!$username) {
    die("User not specified.");
}

$stmt = $pdo->prepare("
    SELECT u.id as user_id, u.username, u.email, 
           a.bio, a.title, a.profile_image, 
           c.phone, c.address, c.linkedin, c.github
    FROM users u
    LEFT JOIN about a ON u.id = a.user_id AND a.is_deleted = 0
    LEFT JOIN contact c ON u.id = c.user_id AND c.is_deleted = 0
    WHERE u.username = ? AND u.account_status = 'approved' AND u.is_deleted = 0
");
$stmt->execute([$username]);
$profile = $stmt->fetch();

if (!$profile) {
    die("Portfolio not found or not approved.");
}

$user_id = $profile['user_id'];

// Fetch other data
$stmt_edu = $pdo->query("SELECT * FROM education WHERE user_id = $user_id AND is_deleted = 0 ORDER BY start_date DESC");
$education = $stmt_edu->fetchAll();

$stmt_skills = $pdo->query("SELECT * FROM skills WHERE user_id = $user_id AND is_deleted = 0 ORDER BY proficiency DESC");
$skills = $stmt_skills->fetchAll();

$stmt_work = $pdo->query("SELECT * FROM work_experience WHERE user_id = $user_id AND is_deleted = 0 ORDER BY start_date DESC");
$work = $stmt_work->fetchAll();

$stmt_ach = $pdo->query("SELECT * FROM achievements WHERE user_id = $user_id AND is_deleted = 0 ORDER BY date_earned DESC");
$achievements = $stmt_ach->fetchAll();

// Build HTML for PDF
$html = "
<html>
<head>
    <style>
        body { font-family: sans-serif; }
        h1 { color: #333; }
        h2 { color: #555; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        .contact { font-size: 14px; color: #666; margin-bottom: 20px; }
        .section { margin-bottom: 20px; }
        .item { margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>{$profile['username']}'s Portfolio</h1>
    " . ($profile['title'] ? "<h2>{$profile['title']}</h2>" : "") . "
    <div class='contact'>
        Email: {$profile['email']}<br>
        " . ($profile['phone'] ? "Phone: {$profile['phone']}<br>" : "") . "
        " . ($profile['address'] ? "Address: {$profile['address']}<br>" : "") . "
    </div>

    <div class='section'>
        <h2>About Me</h2>
        <p>" . nl2br(htmlspecialchars($profile['bio'] ?? '')) . "</p>
    </div>

    <div class='section'>
        <h2>Skills</h2>
        <ul>
";
foreach ($skills as $s) {
    $html .= "<li>" . htmlspecialchars($s['skill_name']) . " - {$s['proficiency']}%</li>";
}
$html .= "
        </ul>
    </div>

    <div class='section'>
        <h2>Education</h2>
";
foreach ($education as $e) {
    $end = $e['end_date'] ?: 'Present';
    $html .= "<div class='item'>
        <strong>" . htmlspecialchars($e['degree']) . "</strong> (" . htmlspecialchars($e['institution']) . ")<br>
        <small>{$e['start_date']} - $end</small><br>
        <p>" . htmlspecialchars($e['description']) . "</p>
    </div>";
}
$html .= "
    </div>

    <div class='section'>
        <h2>Work Experience</h2>
";
foreach ($work as $w) {
    $end = $w['end_date'] ?: 'Present';
    $html .= "<div class='item'>
        <strong>" . htmlspecialchars($w['job_title']) . "</strong> at " . htmlspecialchars($w['company']) . "<br>
        <small>{$w['start_date']} - $end</small><br>
        <p>" . htmlspecialchars($w['description']) . "</p>
    </div>";
}
$html .= "
    </div>
</body>
</html>
";

$options = new Options();
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream($profile['username'] . "_portfolio.pdf", ["Attachment" => true]);
?>
