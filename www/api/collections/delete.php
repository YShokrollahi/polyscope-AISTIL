<?php
// Process API endpoint

// Include necessary files
require_once __DIR__ . '/../../src/core/dzi_generator.php';

// Check if this is a redirect after processing
if (isset($_GET['redirect'])) {
    header('Location: ../index.php');
    exit;
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Process request
if ($method === 'POST') {
    // Process POST request
    $action = $_POST['action'] ?? 'process';
    
    if ($action === 'process') {
        // Process files
        $files = isset($_POST['files']) ? json_decode($_POST['files'], true) : [];
        
        if (empty($files)) {
            $_SESSION['message'] = 'No files selected for processing.';
        } else {
            // Process each file
            foreach ($files as $file) {
                // Check if file exists
                if (!file_exists($file)) {
                    continue;
                }
                
                try {
                    // Process the file
                    $outputDir = __DIR__ . '/../output';
                    convertToDZI($file, $outputDir);
                } catch (Exception $e) {
                    // Log error
                    error_log('Error processing file: ' . $e->getMessage());
                }
            }
            
            $_SESSION['message'] = 'Files processed successfully.';
        }
    } elseif ($action === 'multizoom') {
        // Include multizoom module
        require_once __DIR__ . '/../../src/core/multizoom.php';
        
        // Get all processed DZI files
        $outputDir = __DIR__ . '/../output';
        $dziFiles = [];
        
        $dirs = glob($outputDir . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            if (basename($dir) !== 'multizoom') {
                $dziFile = glob($dir . '/*_deepzoom.dzi');
                if (!empty($dziFile)) {
                    $dziFiles[] = $dziFile[0];
                }
            }
        }
        
        if (empty($dziFiles)) {
            $_SESSION['message'] = 'No DZI files found for multizoom.';
        } else {
            try {
                // Create multizoom view
                $multizoomDir = $outputDir . '/multizoom';
                if (!is_dir($multizoomDir)) {
                    mkdir($multizoomDir, 0755, true);
                }
                
                createMultizoom($dziFiles, $multizoomDir);
                
                $_SESSION['message'] = 'Multi-zoom view created successfully.';
                
            } catch (Exception $e) {
                $_SESSION['message'] = 'Error creating multi-zoom view: ' . $e->getMessage();
            }
        }
    }
    
    // Redirect back to dashboard
    header('Location: ../index.php');
    exit;
    
} else {
    // Process GET request
    $action = $_GET['action'] ?? 'process';
    $response = [];
    
    if ($action === 'process') {
        // Process files
        $files = isset($_GET['files']) ? json_decode(urldecode($_GET['files']), true) : [];
        
        if (empty($files)) {
            $response = [
                'status' => 'error',
                'message' => 'No files selected for processing'
            ];
        } else {
            $results = [];
            foreach ($files as $file) {
                // Check if file exists
                if (!file_exists($file)) {
                    $results[] = [
                        'file' => $file,
                        'status' => 'error',
                        'message' => 'File not found'
                    ];
                    continue;
                }
                
                try {
                    // Process the file
                    $outputDir = __DIR__ . '/../output';
                    $result = convertToDZI($file, $outputDir);
                    $results[] = [
                        'file' => $file,
                        'status' => 'success',
                        'result' => $result
                    ];
                } catch (Exception $e) {
                    $results[] = [
                        'file' => $file,
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                }
            }
            
            $response = [
                'status' => 'complete',
                'results' => $results
            ];
        }
        
    } elseif ($action === 'multizoom') {
        // Similar to the POST handling but formatted as JSON response
        // ...
    }
    
    // Set content type to JSON
    header('Content-Type: application/json');
    
    // Return JSON response
    echo json_encode($response);
}
?>