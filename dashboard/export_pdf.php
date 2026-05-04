<?php
session_start();
require_once '../config/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

try {
    // Fetch user profile
    $stmt = $pdo->prepare("
        SELECT u.username, a.full_name, a.profession, a.bio, a.profile_image,
               c.email, c.phone, c.address, c.website, c.linkedin, c.github
        FROM users u
        JOIN about a ON u.id = a.user_id AND a.is_deleted = 0
        JOIN contact c ON u.id = c.user_id AND c.is_deleted = 0
        WHERE u.id = :uid AND u.is_deleted = 0
    ");
    $stmt->execute([':uid' => $user_id]);
    $profile = $stmt->fetch();

    // Fetch sections
    $stmt = $pdo->prepare("SELECT * FROM education WHERE user_id=:uid AND is_deleted=0 ORDER BY start_date DESC");
    $stmt->execute([':uid' => $user_id]);
    $education = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM skills WHERE user_id=:uid AND is_deleted=0");
    $stmt->execute([':uid' => $user_id]);
    $skills = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM work_experience WHERE user_id=:uid AND is_deleted=0 ORDER BY start_date DESC");
    $stmt->execute([':uid' => $user_id]);
    $work = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM achievements WHERE user_id=:uid AND is_deleted=0");
    $stmt->execute([':uid' => $user_id]);
    $achievements = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM blogs WHERE user_id=:uid AND is_deleted=0 ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([':uid' => $user_id]);
    $blogs = $stmt->fetchAll();

    // Build HTML content
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Portfolio - {$profile['full_name']}</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 900px; margin: 0 auto; padding: 20px; }
            .header { text-align: center; border-bottom: 3px solid #007bff; padding-bottom: 20px; margin-bottom: 20px; }
            .header h1 { color: #007bff; font-size: 32px; margin-bottom: 5px; }
            .header p { color: #666; font-size: 14px; }
            .section { margin-bottom: 30px; }
            .section h2 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 15px; font-size: 18px; }
            .section-content { margin-left: 15px; }
            .item { margin-bottom: 15px; page-break-inside: avoid; }
            .item h3 { color: #222; font-size: 14px; margin-bottom: 5px; }
            .item p { font-size: 13px; margin-bottom: 3px; color: #666; }
            .contact-info { display: flex; flex-wrap: wrap; gap: 20px; font-size: 12px; }
            .contact-item { flex: 1; min-width: 200px; }
            table { width: 100%; border-collapse: collapse; font-size: 12px; }
            td { padding: 8px; border-bottom: 1px solid #ddd; }
            .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>" . htmlspecialchars($profile['full_name']) . "</h1>
                <p>" . htmlspecialchars($profile['profession'] ?? 'Professional') . "</p>
            </div>

            <!-- About Section -->
            <div class='section'>
                <h2>About</h2>
                <div class='section-content'>
                    <p>" . nl2br(htmlspecialchars($profile['bio'] ?? '')) . "</p>
                </div>
            </div>

            <!-- Contact Section -->
            <div class='section'>
                <h2>Contact Information</h2>
                <div class='contact-info'>
                    <div class='contact-item'><strong>Email:</strong> " . htmlspecialchars($profile['email'] ?? 'N/A') . "</div>
                    <div class='contact-item'><strong>Phone:</strong> " . htmlspecialchars($profile['phone'] ?? 'N/A') . "</div>
                    <div class='contact-item'><strong>Location:</strong> " . htmlspecialchars($profile['address'] ?? 'N/A') . "</div>
                    <div class='contact-item'><strong>Website:</strong> " . htmlspecialchars($profile['website'] ?? 'N/A') . "</div>
                </div>
            </div>

            <!-- Skills Section -->
";

    if (!empty($skills)) {
        $html .= "
            <div class='section'>
                <h2>Skills</h2>
                <div class='section-content'>
                    <table>
                        <tr>
                            <td style='background: #f5f5f5; font-weight: bold;'>Skill</td>
                            <td style='background: #f5f5f5; font-weight: bold;'>Proficiency</td>
                        </tr>";
                        
        foreach ($skills as $skill) {
            $html .= "<tr>
                            <td>" . htmlspecialchars($skill['skill_name']) . "</td>
                            <td>" . htmlspecialchars($skill['proficiency'] ?? 'Intermediate') . "</td>
                        </tr>";
        }
        
        $html .= "
                    </table>
                </div>
            </div>";
    }

    $html .= "
            <!-- Education Section -->
";

    if (!empty($education)) {
        $html .= "
            <div class='section'>
                <h2>Education</h2>
                <div class='section-content'>";
                
        foreach ($education as $edu) {
            $html .= "
                    <div class='item'>
                        <h3>" . htmlspecialchars($edu['degree']) . " in " . htmlspecialchars($edu['field_of_study']) . "</h3>
                        <p><strong>" . htmlspecialchars($edu['institution']) . "</strong></p>
                        <p>" . (isset($edu['start_date']) ? htmlspecialchars($edu['start_date']) : '') . 
                           (isset($edu['end_date']) ? ' - ' . htmlspecialchars($edu['end_date']) : '') . "</p>
                        <p>" . htmlspecialchars($edu['description'] ?? '') . "</p>
                    </div>";
        }
        
        $html .= "
                </div>
            </div>";
    }

    $html .= "
            <!-- Work Experience Section -->
";

    if (!empty($work)) {
        $html .= "
            <div class='section'>
                <h2>Work Experience</h2>
                <div class='section-content'>";
                
        foreach ($work as $job) {
            $html .= "
                    <div class='item'>
                        <h3>" . htmlspecialchars($job['position']) . " at " . htmlspecialchars($job['company']) . "</h3>
                        <p>" . (isset($job['start_date']) ? htmlspecialchars($job['start_date']) : '') . 
                           (isset($job['end_date']) ? ' - ' . htmlspecialchars($job['end_date']) : '') . "</p>
                        <p>" . htmlspecialchars($job['description'] ?? '') . "</p>
                    </div>";
        }
        
        $html .= "
                </div>
            </div>";
    }

    $html .= "
            <!-- Achievements Section -->
";

    if (!empty($achievements)) {
        $html .= "
            <div class='section'>
                <h2>Achievements</h2>
                <div class='section-content'>";
                
        foreach ($achievements as $ach) {
            $html .= "
                    <div class='item'>
                        <h3>" . htmlspecialchars($ach['title']) . "</h3>
                        <p>" . htmlspecialchars($ach['description'] ?? '') . "</p>
                        <p><em>" . htmlspecialchars($ach['date_achieved'] ?? '') . "</em></p>
                    </div>";
        }
        
        $html .= "
                </div>
            </div>";
    }

    $html .= "
            <div class='footer'>
                <p>Generated on " . date('Y-m-d H:i:s') . "</p>
                <p>Portfolio URL: " . htmlspecialchars($_SERVER['HTTP_HOST']) . "/Portfolio_generator/portfolio.php?user=" . htmlspecialchars($username) . "</p>
            </div>
        </div>
    </body>
    </html>";

    // Output HTML and print
    echo $html;

} catch (Exception $e) {
    die("Error: " . htmlspecialchars($e->getMessage()));
}
?>