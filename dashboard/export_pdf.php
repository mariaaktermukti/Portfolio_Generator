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
    // 1. Fetch user profile, about, and contact using LEFT JOIN
    // This ensures data loads even if about or contact tables are empty
    $stmt = $pdo->prepare("
        SELECT u.username, 
               a.full_name, a.profession, a.bio, a.profile_image,
               c.email, c.phone, c.address, c.website, c.linkedin, c.github
        FROM users u
        LEFT JOIN about a ON u.id = a.user_id AND a.is_deleted = 0
        LEFT JOIN contact c ON u.id = c.user_id AND c.is_deleted = 0
        WHERE u.id = :uid AND u.is_deleted = 0
    ");
    $stmt->execute([':uid' => $user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        throw new Exception("User profile not found or deleted.");
    }

    // 2. Fetch Education
    $stmt = $pdo->prepare("SELECT * FROM education WHERE user_id = :uid AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY start_date DESC");
    $stmt->execute([':uid' => $user_id]);
    $education = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch Skills
    $stmt = $pdo->prepare("SELECT * FROM skills WHERE user_id = :uid AND (is_deleted = 0 OR is_deleted IS NULL)");
    $stmt->execute([':uid' => $user_id]);
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Fetch Work Experience
    $stmt = $pdo->prepare("SELECT * FROM work_experience WHERE user_id = :uid AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY start_date DESC");
    $stmt->execute([':uid' => $user_id]);
    $work = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Fetch Achievements
    $stmt = $pdo->prepare("SELECT * FROM achievements WHERE user_id = :uid AND (is_deleted = 0 OR is_deleted IS NULL)");
    $stmt->execute([':uid' => $user_id]);
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Fetch Blogs
    $stmt = $pdo->prepare("SELECT * FROM blogs WHERE user_id = :uid AND (is_deleted = 0 OR is_deleted IS NULL) ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([':uid' => $user_id]);
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Helper function for safe HTML output
    function e($string) {
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }

    // Build HTML content - Template ready for dompdf
    $html = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <title>Portfolio - " . e($profile['full_name'] ?? $profile['username']) . "</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 900px; margin: 0 auto; padding: 20px; }
            .header { text-align: center; border-bottom: 3px solid #007bff; padding-bottom: 20px; margin-bottom: 20px; }
            .header h1 { color: #007bff; font-size: 32px; margin-bottom: 5px; }
            .header p { color: #666; font-size: 16px; }
            .section { margin-bottom: 30px; }
            .section h2 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 15px; font-size: 18px; text-transform: uppercase; }
            .section-content { margin-left: 10px; }
            .item { margin-bottom: 15px; page-break-inside: avoid; }
            .item h3 { color: #222; font-size: 16px; margin-bottom: 5px; }
            .item p { font-size: 14px; margin-bottom: 5px; color: #555; }
            .meta-info { font-style: italic; color: #777; font-size: 13px; margin-bottom: 5px; }
            .contact-info { display: table; width: 100%; margin-bottom: 10px; font-size: 14px; }
            .contact-row { display: table-row; }
            .contact-cell { display: table-cell; padding: 5px 10px; }
            table { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px; }
            th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
            th { background: #f5f5f5; font-weight: bold; color: #333; }
            .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>" . e($profile['full_name'] ?? $profile['username']) . "</h1>
                <p>" . e($profile['profession'] ?? 'Professional Portfolio') . "</p>
            </div>

            <!-- About Section -->
            <div class='section'>
                <h2>About</h2>
                <div class='section-content'>
                    <p>" . nl2br(e($profile['bio'] ?? 'No biography provided.')) . "</p>
                </div>
            </div>

            <!-- Contact Section -->
            <div class='section'>
                <h2>Contact Information</h2>
                <div class='contact-info'>
                    <div class='contact-row'>
                        <div class='contact-cell'><strong>Email:</strong> " . e($profile['email'] ?? 'N/A') . "</div>
                        <div class='contact-cell'><strong>Phone:</strong> " . e($profile['phone'] ?? 'N/A') . "</div>
                    </div>
                    <div class='contact-row'>
                        <div class='contact-cell'><strong>Location:</strong> " . e($profile['address'] ?? 'N/A') . "</div>
                        <div class='contact-cell'><strong>Website:</strong> " . e($profile['website'] ?? 'N/A') . "</div>
                    </div>
                    <div class='contact-row'>
                        <div class='contact-cell'><strong>LinkedIn:</strong> " . e($profile['linkedin'] ?? 'N/A') . "</div>
                        <div class='contact-cell'><strong>GitHub:</strong> " . e($profile['github'] ?? 'N/A') . "</div>
                    </div>
                </div>
            </div>";

    // Skills Section
    if (!empty($skills)) {
        $html .= "
            <div class='section'>
                <h2>Skills</h2>
                <div class='section-content'>
                    <table>
                        <tr>
                            <th>Skill</th>
                            <th>Proficiency</th>
                        </tr>";
        foreach ($skills as $skill) {
            $html .= "
                        <tr>
                            <td>" . e($skill['skill_name'] ?? '') . "</td>
                            <td>" . e($skill['proficiency'] ?? 'N/A') . "</td>
                        </tr>";
        }
        $html .= "
                    </table>
                </div>
            </div>";
    }

    // Education Section
    if (!empty($education)) {
        $html .= "
            <div class='section'>
                <h2>Education</h2>
                <div class='section-content'>";
        foreach ($education as $edu) {
            $startDate = !empty($edu['start_date']) ? e($edu['start_date']) : 'N/A';
            $endDate = !empty($edu['end_date']) ? e($edu['end_date']) : 'Present';
            $html .= "
                    <div class='item'>
                        <h3>" . e($edu['degree'] ?? '') . " in " . e($edu['field_of_study'] ?? '') . "</h3>
                        <p><strong>" . e($edu['institution'] ?? '') . "</strong></p>
                        <p class='meta-info'>{$startDate} - {$endDate}</p>
                        " . (!empty($edu['description']) ? "<p>" . nl2br(e($edu['description'])) . "</p>" : "") . "
                    </div>";
        }
        $html .= "
                </div>
            </div>";
    }

    // Work Experience Section
    if (!empty($work)) {
        $html .= "
            <div class='section'>
                <h2>Work Experience</h2>
                <div class='section-content'>";
        foreach ($work as $job) {
            $startDate = !empty($job['start_date']) ? e($job['start_date']) : 'N/A';
            $endDate = !empty($job['end_date']) ? e($job['end_date']) : 'Present';
            $html .= "
                    <div class='item'>
                        <h3>" . e($job['position'] ?? '') . " at " . e($job['company'] ?? '') . "</h3>
                        <p class='meta-info'>{$startDate} - {$endDate}</p>
                        " . (!empty($job['description']) ? "<p>" . nl2br(e($job['description'])) . "</p>" : "") . "
                    </div>";
        }
        $html .= "
                </div>
            </div>";
    }

    // Achievements Section
    if (!empty($achievements)) {
        $html .= "
            <div class='section'>
                <h2>Achievements</h2>
                <div class='section-content'>";
        foreach ($achievements as $ach) {
            $html .= "
                    <div class='item'>
                        <h3>" . e($ach['title'] ?? '') . "</h3>
                        " . (!empty($ach['date_achieved']) ? "<p class='meta-info'>" . e($ach['date_achieved']) . "</p>" : "") . "
                        " . (!empty($ach['description']) ? "<p>" . nl2br(e($ach['description'])) . "</p>" : "") . "
                    </div>";
        }
        $html .= "
                </div>
            </div>";
    }

    // Blogs Section
    if (!empty($blogs)) {
        $html .= "
            <div class='section'>
                <h2>Recent Blogs</h2>
                <div class='section-content'>";
        foreach ($blogs as $blog) {
            $html .= "
                    <div class='item'>
                        <h3>" . e($blog['title'] ?? '') . "</h3>
                        <p class='meta-info'>Published on: " . (!empty($blog['created_at']) ? e(date('F j, Y', strtotime($blog['created_at']))) : 'N/A') . "</p>
                        " . (!empty($blog['content']) ? "<p>" . nl2br(e(substr($blog['content'], 0, 150))) . "...</p>" : "") . "
                    </div>";
        }
        $html .= "
                </div>
            </div>";
    }

    $html .= "
            <div class='footer'>
                <p>Generated on " . date('Y-m-d H:i:s') . "</p>
                <p>Portfolio URL: " . (isset($_SERVER['HTTP_HOST']) ? e($_SERVER['HTTP_HOST']) : 'localhost') . "/Portfolio_generator/portfolio.php?user=" . urlencode($username) . "</p>
            </div>
        </div>
        <script>
            // Automatically trigger the print/save-as-PDF dialog when the page loads
            window.onload = function() { window.print(); }
        </script>
    </body>
    </html>";

    // Output HTML and let browser handle PDF generation
    echo $html;

} catch (PDOException $e) {
    // Database query errors
    error_log("Database Error in export_pdf.php: " . $e->getMessage());
    die("A database error occurred while generating the portfolio. Please try again later.");
} catch (Exception $e) {
    // Other exceptions
    error_log("Error in export_pdf.php: " . $e->getMessage());
    die("An error occurred: " . e($e->getMessage()));
}
?>