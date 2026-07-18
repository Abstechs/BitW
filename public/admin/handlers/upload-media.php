<?php
// public/admin/handlers/upload-media.php

require_once __DIR__ . '/../includes/admin_init.php';
require_once __DIR__ . '/../../../core/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => ['message' => 'Invalid routing strategy profile protocol.']]);
    exit;
}

if (!isset($_FILES['upload'])) {
    echo json_encode(['error' => ['message' => 'No media source discovered inside tracking parameters.']]);
    exit;
}

$file = $_FILES['upload'];

// --- UPGRADED VALIDATION: MAP REAL FILE CONTENT TYPE DIRECTLY ---
$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp'
];

// Read internal file indicators rather than relying strictly on the filename string
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$detectedMime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!array_key_exists($detectedMime, $allowedMimeTypes)) {
    echo json_encode(['error' => ['message' => 'Image format profile variant rejected by platform policy rules. Detacted: ' . $detectedMime]]);
    exit;
}

// Fallback to determine standard format strings safely based on binary inspection
$extension = $allowedMimeTypes[$detectedMime];

// Limit upload profiles cleanly to 10MB to handle large clipboard snips safely
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['error' => ['message' => 'Attachment asset payload profile limits exceeded (Max 10MB).']]);
    exit;
}

// Point directory destinations directly to asset directory repositories
$uploadDirectory = __DIR__ . '/../../uploads/blog/';
if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0755, true);
}

// Generate an obfuscated tracking index tag to block duplicate overrides on disk
$newFileName = bin2hex(random_bytes(12)) . '_' . time() . '.' . $extension;
$destinationTarget = $uploadDirectory . $newFileName;

if (move_uploaded_file($file['tmp_name'], $destinationTarget)) {
    $publicBrowseUrl = '/uploads/blog/' . $newFileName;
    
    echo json_encode([
        'uploaded' => true,
        'url' => $publicBrowseUrl
    ]);
} else {
    echo json_encode(['error' => ['message' => 'Server file system encountered a write target error writing asset to disk.']]);
}
exit;