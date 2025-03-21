<?php
/**
 * API Endpoint: Get Slide Details
 * 
 * Provides detailed information about a processed slide.
 */

// Set content type to JSON
header('Content-Type: application/json');

// Get slide name from query parameter
$slideName = isset($_GET['slide']) ? $_GET['slide'] : null;

if (!$slideName) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No slide specified'
    ]);
    exit;
}

// Get slide details
$outputDir = __DIR__ . '/../../output/' . $slideName;

if (!is_dir($outputDir)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Slide not found'
    ]);
    exit;
}

// Get status file data
$statusFile = $outputDir . '/status.json';
$statusData = [];

if (file_exists($statusFile)) {
    $statusData = json_decode(file_get_contents($statusFile), true);
}

// Get metadata file data
$metadataFile = $outputDir . '/metadata.json';
$metadata = [];

if (file_exists($metadataFile)) {
    $metadata = json_decode(file_get_contents($metadataFile), true);
}

// Check for thumbnail
$thumbnailPath = '';
$potentialThumbnails = [
    $outputDir . '/thumbnail.jpg',
    $outputDir . '/thumbnail.png',
    $outputDir . '/thumb.jpg',
    $outputDir . '/thumb.png'
];

foreach ($potentialThumbnails as $path) {
    if (file_exists($path)) {
        $thumbnailPath = str_replace(__DIR__ . '/../../', '', $path);
        break;
    }
}

// Get viewer path if available
$viewerPath = '';
if (isset($statusData['viewerPath'])) {
    $viewerPath = str_replace(__DIR__ . '/../../', '', $statusData['viewerPath']);
}

// Prepare slide data
$slideData = [
    'name' => $slideName,
    'status' => $statusData['status'] ?? 'unknown',
    'processingTime' => $statusData['processingTime'] ?? 'N/A',
    'dateProcessed' => $statusData['endTime'] ?? null,
    'dateFormatted' => isset($statusData['endTime']) ? date("Y-m-d H:i:s", $statusData['endTime']) : 'N/A',
    'metadata' => $metadata,
    'tags' => $metadata['tags'] ?? [],
    'thumbnailPath' => $thumbnailPath,
    'viewerPath' => $viewerPath,
    'hasLog' => file_exists($outputDir . '/process.log'),
    'logPath' => file_exists($outputDir . '/process.log') ? str_replace(__DIR__ . '/../../', '', $outputDir . '/process.log') : ''
];

// Return the response
echo json_encode([
    'status' => 'success',
    'slide' => $slideData
]);