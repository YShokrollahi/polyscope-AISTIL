<?php
/**
 * API Endpoint: Track Slide Processing Progress
 * 
 * Provides progress information for slide processing jobs.
 */

// Set content type to JSON
header('Content-Type: application/json');

// Initialize default response
$response = [
    'status' => 'processing',
    'percent' => 10,  // For testing, we'll always return 10% progress
    'message' => 'Processing the slide...',
    'stage' => 'processing'
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
            'status' => $statusData['status'] ?? 'complete',
            'percent' => 100,
            'message' => $statusData['message'] ?? 'Processing complete',
            'stage' => 'complete'
        ];
    }
    
    echo json_encode($response);
    exit;
}

// For testing purposes, let's simulate some progress
// In a real implementation, you would read the actual progress from the process file
// and update it as the process runs

// Get the process file
$processFile = $processFiles[0];
$processData = [];

if (file_exists($processFile)) {
    $processData = json_decode(file_get_contents($processFile), true);
}

// If we have actual data, use it
if (!empty($processData)) {
    // Simulate progress by incrementing the percent
    $percent = isset($processData['percent']) ? $processData['percent'] + 5 : 5;
    if ($percent > 100) $percent = 100;
    
    $stage = 'processing';
    if ($percent > 80) $stage = 'finishing';
    if ($percent >= 100) $stage = 'complete';
    
    $status = $stage === 'complete' ? 'complete' : 'processing';
    
    // Update process data
    $processData['percent'] = $percent;
    $processData['stage'] = $stage;
    $processData['status'] = $status;
    $processData['lastUpdate'] = time();
    $processData['message'] = $stage === 'complete' ? 'Processing complete' : 'Processing slide...';
    
    // Save updated data
    file_put_contents($processFile, json_encode($processData));
    
    // If process is complete, generate a status file
    if ($status === 'complete') {
        $outputDir = __DIR__ . '/../../output/' . $baseFilename;
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        $statusFileContent = [
            'status' => 'success',
            'endTime' => time(),
            'processingTime' => time() - $processData['startTime'],
            'message' => 'Slide processed successfully'
        ];
        
        file_put_contents($outputDir . '/status.json', json_encode($statusFileContent));
        
        // Remove the process file as it's complete
        unlink($processFile);
    }
    
    $response = [
        'status' => $status,
        'percent' => $percent,
        'message' => $processData['message'],
        'stage' => $stage
    ];
} else {
    // Fallback to default response
    $response = [
        'status' => 'processing',
        'percent' => 25,
        'message' => 'Processing the slide...',
        'stage' => 'processing'
    ];
}

// Return the response
echo json_encode($response);