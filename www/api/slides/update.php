<?php
/**
 * API Endpoint: Update Slide Metadata
 * 
 * Updates slide metadata such as tags.
 */

// Set content type to JSON
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid input data'
    ]);
    exit;
}

// Get slide name
$slideName = $data['slide'] ?? '';

if (!$slideName) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No slide specified'
    ]);
    exit;
}

// Get action
$action = $data['action'] ?? '';

if (!$action) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No action specified'
    ]);
    exit;
}

// Get slide directory
$outputDir = __DIR__ . '/../../output/' . $slideName;

if (!is_dir($outputDir)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Slide not found'
    ]);
    exit;
}

// Get metadata file
$metadataFile = $outputDir . '/metadata.json';
$metadata = [];

if (file_exists($metadataFile)) {
    $metadata = json_decode(file_get_contents($metadataFile), true);
}

// Handle different actions
switch ($action) {
    case 'update_tags':
        // Update tags
        $tags = $data['tags'] ?? [];
        $metadata['tags'] = $tags;
        
        // Save metadata
        if (file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT))) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Tags updated successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update tags'
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Unknown action'
        ]);
}