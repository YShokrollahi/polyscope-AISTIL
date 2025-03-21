<?php
/**
 * API Endpoint: Track Slide Processing Progress
 * 
 * Provides progress information for slide processing jobs.
 */

// Include necessary files
require_once __DIR__ . '/../../../src/utils/logger.php';

// Set content type to JSON
header('Content-Type: application/json');

// Initialize default response
$response = [
    'status' => 'unknown',
    'percent' => 0,
    'message' => 'No processing information found',
    'stage' => 'waiting'
];

// Get file path from query parameter
$file = isset($_GET['file']) ? $_GET['file'] : null;

if (!$file) {
    echo json_encode($response);
    exit;
}

// Get base filename
$filename = basename($file);
$baseFilename = pathinfo($filename, PATHINFO_FILENAME);

// Check for process ID file
$processDir = __DIR__ . '/../../data/processes';
$processFiles = glob($processDir . '/*_' . $baseFilename . '.json');

if (empty($processFiles)) {
    // Look for the status file in the output directory
    $outputDir = __DIR__ . '/../../output/' . $baseFilename;
    $statusFile = $outputDir . '/status.json';
    
    if (file_exists($statusFile)) {
        $statusData = json_decode(file_get_contents($statusFile), true);
        
        $response = [
            'status' => $statusData['status'] ?? 'unknown',
            'percent' => 100,
            'message' => $statusData['message'] ?? 'Processing complete',
            'stage' => 'complete'
        ];
    }
    
    echo json_encode($response);
    exit;
}

// Get the process file
$processFile = $processFiles[0];
$processData = json_decode(file_get_contents($processFile), true);

if (!$processData) {
    echo json_encode($response);
    exit;
}

// Check if the process is still running
$pid = $processData['pid'] ?? 0;
$isRunning = false;

if ($pid > 0) {
    // Check if the process is still running
    if (function_exists('posix_kill')) {
        // For Unix-like systems
        $isRunning = posix_kill($pid, 0);
    } else {
        // For Windows systems
        $output = [];
        exec('tasklist /FI "PID eq ' . $pid . '" 2>NUL', $output);
        $isRunning = count($output) > 1;
    }
}

// Calculate progress based on process data
$startTime = $processData['startTime'] ?? time();
$lastUpdate = $processData['lastUpdate'] ?? time();
$stage = $processData['stage'] ?? 'preparing';
$percent = $processData['percent'] ?? 0;
$message = $processData['message'] ?? 'Processing slide...';

// If no update for a long time, consider it stalled
$stalledThreshold = 120; // 2 minutes
if (!$isRunning && time() - $lastUpdate > $stalledThreshold) {
    $response = [
        'status' => 'stalled',
        'percent' => $percent,
        'message' => 'Processing seems to be stalled',
        'stage' => $stage
    ];
    
    echo json_encode($response);
    exit;
}

// Check if the process is complete
if (!$isRunning && file_exists($outputDir . '/status.json')) {
    $statusData = json_decode(file_get_contents($outputDir . '/status.json'), true);
    
    $response = [
        'status' => $statusData['status'] ?? 'complete',
        'percent' => 100,
        'message' => $statusData['message'] ?? 'Processing complete',
        'stage' => 'complete'
    ];
} else {
    // Process is still running
    $response = [
        'status' => $stage === 'error' ? 'error' : 'processing',
        'percent' => $percent,
        'message' => $message,
        'stage' => $stage
    ];
}

// Return the response
echo json_encode($response);