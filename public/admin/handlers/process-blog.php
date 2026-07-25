<?php
// public/admin/handlers/process-blog.php

// 1. Force errors to return JSON strings instead of outputting raw HTML crash screens
error_reporting(0); 
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    // 2. Load the standard administrative session configurations
    require_once __DIR__ . '/../includes/admin_init.php';
    
    // 3. Import your real core database framework engine file
    // Stepping back 3 steps ('../../..') gets us out of public/admin/handlers/ and into the root folder
    $dbPath = __DIR__ . '/../../../core/database.php';
    if (!file_exists($dbPath)) {
        throw new Exception("Core Database file configuration asset missing from directory tree.");
    }
    
    require_once $dbPath;

    // Access the global variable initialized by core/database.php
    global $pdo;
    if (!isset($pdo)) {
        throw new Exception("The global database connection instance variable object (\$pdo) failed to initialize.");
    }

    // 4. Capture and decode incoming form fields safely
    $user = getUser($_SESSION['user_id']);
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $type = isset($_POST['type']) ? $_POST['type'] : 'admin_blog';
    $is_verified = isset($_POST['is_verified']) ? (int)$_POST['is_verified'] : 0;

    // Decode the base64 content bypass shield string back into raw HTML content parameters
    if (isset($_POST['is_encoded']) && $_POST['is_encoded'] === '1') {
        $content = urldecode(base64_decode($content));
    }

    if (empty($title) || empty($content)) {
        echo json_encode(['status' => 'error', 'message' => 'Validation Error: Form elements cannot contain blank values.']);
        exit;
    }

    // 5. Native PDO Prepared Statement mapping sequence matching the public front-end rules
    // Updated table target to 'oracle_posts' and type tracking key field to 'post_type'
    $stmt = $pdo->prepare("INSERT INTO oracle_posts (author_id, title, content, post_type, is_verified, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $result = $stmt->execute([
        $user['id'],
        $title, 
        $content, 
        $type,          // Maps to 'admin_blog' 
        $is_verified    // Maps to 1 (checked/verified)
    ]);

    // Send the ultimate validation payload indicator flag up to Toastify on the frontend canvas layout
    echo json_encode([
        'status' => 'success', 
        'message' => 'Ecosystem update successfully published to Oracle repository ledger metrics!'
    ]);

} catch (\Throwable $e) {
    // Intercept database execution errors cleanly and print the message directly inside the toaster alert box
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database Engine Fault: ' . $e->getMessage()
    ]);
}
exit;