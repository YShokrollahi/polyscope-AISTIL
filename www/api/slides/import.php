<?php
/**
 * API Endpoint: Import Slides
 * 
 * Handles file uploads for new slides.
 */

// Include necessary files
require_once __DIR__ . '/../../../src/utils/file_helper.php';
require_once __DIR__ . '/../../../src/utils/logger.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if this is a file upload request
if (!isset($_FILES['files'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No files uploaded'
    ]);
    exit;
}

// Define allowed file types
$allowedTypes = ['svs', 'tif', 'tiff', 'ndpi', 'scn', 'bif'];

// Get input directory
$inputDir = defined('INPUT_DIR') ? INPUT_DIR : dirname(dirname(dirname(__DIR__))) . '/input';

// Create directory if it doesn't exist
if (!is_dir($inputDir)) {
    mkdir($inputDir, 0755, true);
}

// Process uploaded files
$files = $_FILES['files'];
$uploadedFiles = [];
$errorFiles = [];
$successCount = 0;

// Handle multiple files
for ($i = 0; $i < count($files['name']); $i++) {
    $filename = $files['name'][$i];
    $tmpName = $files['tmp_name'][$i];
    $error = $files['error'][$i];
    $size = $files['size'][$i];
    
    // Skip files with errors
    if ($error !== UPLOAD_ERR_OK) {
        $errorFiles[] = [
            'name' => $filename,
            'error' => getUploadErrorMessage($error)
        ];
        continue;
    }
    
    // Check file type
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        $errorFiles[] = [
            'name' => $filename,
            'error' => 'File type not allowed'
        ];
        continue;
    }
    
    // Generate a safe filename
    $safeFilename = sanitizeFilename($filename);
    $destination = $inputDir . '/' . $safeFilename;
    
    // Check if file already exists
    if (file_exists($destination)) {
        // Append timestamp to filename
        $pathInfo = pathinfo($safeFilename);
        $safeFilename = $pathInfo['filename'] . '_' . time() . '.' . $pathInfo['extension'];
        $destination = $inputDir . '/' . $safeFilename;
    }
    
    // Move uploaded file
    if (move_uploaded_file($tmpName, $destination)) {
        $uploadedFiles[] = [
            'name' => $safeFilename,
            'original' => $filename,
            'path' => $destination,
            'size' => $size,
            'type' => $extension
        ];
        $successCount++;
    } else {
        $errorFiles[] = [
            'name' => $filename,
            'error' => 'Failed to move uploaded file'
        ];
    }
}

// Check for process after upload
$processAfterUpload = isset($_POST['process_after_upload']) && $_POST['process_after_upload'] === 'on';

// Prepare response
$response = [
    'status' => count($errorFiles) === 0 ? 'success' : (count($uploadedFiles) > 0 ? 'partial' : 'error'),
    'message' => count($uploadedFiles) > 0 
        ? "Successfully uploaded $successCount " . ($successCount === 1 ? 'file' : 'files') 
        : 'Failed to upload any files',
    'uploaded' => $uploadedFiles,
    'errors' => $errorFiles,
    'process' => $processAfterUpload,
    'files' => array_column($uploadedFiles, 'path')
];

// Return the response
echo json_encode($response);

/**
 * Get upload error message
 * 
 * @param int $errorCode PHP upload error code
 * @return string Human-readable error message
 */
function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'A PHP extension stopped the file upload';
        default:
            return 'Unknown upload error';
    }
}

/**
 * Sanitize filename
 * 
 * @param string $filename Original filename
 * @return string Sanitized filename
 */
function sanitizeFilename($filename) {
    // Remove special characters
    $filename = preg_replace('/[^\w\.-]/', '_', $filename);
    
    // Remove multiple underscores
    $filename = preg_replace('/_+/', '_', $filename);
    
    return $filename;
}