<?php
session_start();
require_once '../config/db.php';
require_once '../config/imgbb.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (empty(IMGBB_API_KEY)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ImgBB API key not configured. Please add your API key to config/imgbb.php']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No image file provided']);
    exit;
}

$file = $_FILES['image'];

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed_types)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed']);
    exit;
}

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File size exceeds 5MB limit']);
    exit;
}

try {
    // Upload to ImgBB
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.imgbb.com/1/upload',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'key' => IMGBB_API_KEY,
            'image' => curl_file_create($file['tmp_name'], $file['type'], $file['name']),
            'expiration' => 15552000  // 6 months
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    $response = curl_exec($curl);
    $curl_error = curl_error($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($curl_error) {
        throw new Exception('cURL Error: ' . $curl_error);
    }
    
    if ($http_code !== 200) {
        throw new Exception('HTTP Error ' . $http_code . ': ' . $response);
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['success'])) {
        throw new Exception('Invalid API response: ' . substr($response, 0, 200));
    }
    
    if (!$result['success']) {
        $error_msg = isset($result['error']['message']) ? $result['error']['message'] : 'Unknown error';
        throw new Exception('ImgBB Error: ' . $error_msg);
    }
    
    if (!isset($result['data']['url'])) {
        throw new Exception('No URL in ImgBB response');
    }
    
    $image_url = $result['data']['url'];
    
    echo json_encode([
        'success' => true,
        'url' => $image_url,
        'message' => 'Image uploaded successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Upload failed: ' . $e->getMessage()
    ]);
}
?>
