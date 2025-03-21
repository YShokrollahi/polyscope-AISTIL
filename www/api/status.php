<?php
// api/status.php - Track processing status

header('Content-Type: application/json');

// Function to get all active processes
function getAllProcesses() {
    $processDir = __DIR__ . '/../data/processes/';
    
    // Create the processes directory if it doesn't exist
    if (!file_exists($processDir)) {
        mkdir($processDir, 0755, true);
        return [];
    }
    
    $files = glob($processDir . '*.json');
    $processes = [];
    
    foreach ($files as $file) {
        $process = json_decode(file_get_contents($file), true);
        
        // Only include active processes or recently completed ones (less than 1 hour old)
        if ($process && 
            (($process['status'] === 'in_progress') || 
             (($process['status'] === 'completed' || $process['status'] === 'failed') && 
             (time() - $process['updated']) < 3600))) {
            $processes[] = $process;
        }
    }
    
    return $processes;
}

// Get process ID from request
$processId = isset($_GET['id']) ? $_GET['id'] : null;

if ($processId) {
    // Return status for specific process
    $processFile = __DIR__ . '/../data/processes/' . $processId . '.json';
    
    if (file_exists($processFile)) {
        $process = json_decode(file_get_contents($processFile), true);
        
        echo json_encode([
            'success' => true,
            'process' => $process
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Process not found'
        ]);
    }
} else {
    // Return all active processes
    $processes = getAllProcesses();
    
    echo json_encode([
        'success' => true,
        'processes' => $processes
    ]);
}