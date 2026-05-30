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
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.5; font-size: 13px; margin: 20px 0; }
        .header { text-align: center; margin-bottom: 25px; }
        .header h1 { font-size: 28px; margin: 0 0 5px 0; color: #111; letter-spacing: 1px; text-transform: uppercase; }
        .header h2 { font-size: 16px; margin: 0 0 10px 0; color: #4f46e5; font-weight: normal; }
        .contact-info { font-size: 12px; color: #555; }
        .contact-info span { margin: 0 5px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 14px; color: #111; border-bottom: 1.5px solid #4f46e5; padding-bottom: 4px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; }
        .item { margin-bottom: 15px; }
        .item-header { margin-bottom: 4px; }
        .item-title { font-weight: bold; font-size: 14px; color: #222; }
        .item-subtitle { font-style: italic; color: #555; }
        .item-date { float: right; color: #4f46e5; font-size: 12px; font-weight: bold; }
        .item-desc { margin: 0; font-size: 13px; color: #444; text-align: justify; }
        .skills-table { width: 100%; border-collapse: collapse; }
        .skills-table td { padding: 4px 0; vertical-align: top; }
        .skill-group-name { font-weight: bold; color: #222; width: 140px; }
        .skill-group-items { color: #444; }
        a { color: #4f46e5; text-decoration: none; }
        .clear { clear: both; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>" . htmlspecialchars($profile['username']) . "</h1>
        " . ($profile['title'] ? "<h2>" . htmlspecialchars($profile['title']) . "</h2>" : "") . "
        <div class='contact-info'>";
        
$contacts = [];
if (!empty($profile['email'])) $contacts[] = htmlspecialchars($profile['email']);
if (!empty($profile['phone'])) $contacts[] = htmlspecialchars($profile['phone']);
if (!empty($profile['address'])) $contacts[] = htmlspecialchars($profile['address']);
if (!empty($profile['linkedin'])) $contacts[] = "<a href='" . htmlspecialchars($profile['linkedin']) . "'>LinkedIn</a>";
if (!empty($profile['github'])) $contacts[] = "<a href='" . htmlspecialchars($profile['github']) . "'>GitHub</a>";

$html .= implode(" &nbsp;|&nbsp; ", $contacts);

$html .= "
        </div>
    </div>";

if (!empty($profile['bio'])) {
    $html .= "
    <div class='section'>
        <div class='section-title'>Professional Summary</div>
        <div class='item-desc'>" . nl2br(htmlspecialchars($profile['bio'])) . "</div>
    </div>";
}

if (!empty($skills)) {
    $html .= "
    <div class='section'>
        <div class='section-title'>Technical Skills</div>
        <table class='skills-table'>";
        
    // Group skills by skill_group
    $grouped_skills = [];
    foreach ($skills as $s) {
        $group = $s['skill_group'] ?? 'Other';
        if (!isset($grouped_skills[$group])) {
            $grouped_skills[$group] = [];
        }
        $grouped_skills[$group][] = $s;
    }

    foreach ($grouped_skills as $group_name => $group_skills) {
        $skill_names = array_map(function($s) { return htmlspecialchars($s['skill_name']); }, $group_skills);
        $skills_str = implode(", ", $skill_names);
        
        $html .= "
            <tr>
                <td class='skill-group-name'>" . htmlspecialchars($group_name) . "</td>
                <td class='skill-group-items'>$skills_str</td>
            </tr>";
    }
    
    $html .= "
        </table>
    </div>";
}

if (!empty($work)) {
    $html .= "
    <div class='section'>
        <div class='section-title'>Experience</div>";
        
    foreach ($work as $w) {
        $end = $w['end_date'] ?: 'Present';
        $html .= "
        <div class='item'>
            <div class='item-header'>
                <span class='item-title'>" . htmlspecialchars($w['job_title']) . "</span>, 
                <span class='item-subtitle'>" . htmlspecialchars($w['company']) . "</span>
                <span class='item-date'>{$w['start_date']} - $end</span>
            </div>
            <div class='item-desc'>" . nl2br(htmlspecialchars($w['description'])) . "</div>
            <div class='clear'></div>
        </div>";
    }
    $html .= "</div>";
}

if (!empty($education)) {
    $html .= "
    <div class='section'>
        <div class='section-title'>Education</div>";
        
    foreach ($education as $e) {
        $end = $e['end_date'] ?: 'Present';
        $result_str = !empty($e['result']) ? "<br><strong>Result:</strong> " . htmlspecialchars($e['result']) : "";
        $html .= "
        <div class='item'>
            <div class='item-header'>
                <span class='item-title'>" . htmlspecialchars($e['degree']) . "</span>, 
                <span class='item-subtitle'>" . htmlspecialchars($e['institution']) . "</span>
                <span class='item-date'>{$e['start_date']} - $end</span>
            </div>
            <div class='item-desc'>" . nl2br(htmlspecialchars($e['description'])) . $result_str . "</div>
            <div class='clear'></div>
        </div>";
    }
    $html .= "</div>";
}

if (!empty($achievements)) {
    $html .= "
    <div class='section'>
        <div class='section-title'>Achievements & Certifications</div>";
        
    foreach ($achievements as $a) {
        $html .= "
        <div class='item'>
            <div class='item-header'>
                <span class='item-title'>" . htmlspecialchars($a['title']) . "</span>
                <span class='item-date'>" . htmlspecialchars($a['date_earned']) . "</span>
            </div>
            <div class='item-desc'>" . nl2br(htmlspecialchars($a['description']));
            
        if (!empty($a['certificate_url'])) {
            $html .= "<br><a href='" . htmlspecialchars($a['certificate_url']) . "'>View Certificate &raquo;</a>";
        }
            
        $html .= "</div>
            <div class='clear'></div>
        </div>";
    }
    $html .= "</div>";
}

$html .= "
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
