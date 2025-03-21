<?php
// Remove execution time limit completely
set_time_limit(0);

/**
 * DZI Generator
 * Converts slides to Deep Zoom Image format using vips
 */

/**
 * Convert a slide to DZI format
 * 
 * @param string $inputFile Path to the input slide file
 * @param string $outputDir Path to the output directory
 * @param array $options Additional options for conversion
 * @return array Result information
 */
function convertToDZI($inputFile, $outputDir, $options = []) {
    // Default options
    $defaults = [
        'tileSize' => 254,
        'overlap' => 1,
        'quality' => 90
    ];
    
    $options = array_merge($defaults, $options);
    
    // Create output directory if it doesn't exist
    if (!file_exists($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    // Get file information
    $filename = basename($inputFile);
    $baseFilename = pathinfo($filename, PATHINFO_FILENAME);
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    
    // Create working directory for this slide
    $workingDir = $outputDir . '/' . $baseFilename;
    if (!file_exists($workingDir)) {
        mkdir($workingDir, 0755, true);
    }
    
    // Initialize progress tracking
    $progressFile = $workingDir . '/progress.json';
    $progressData = [
        'status' => 'initializing',
        'percent' => 0,
        'stage' => 'Starting process',
        'message' => 'Preparing to process file',
        'startTime' => microtime(true)
    ];
    file_put_contents($progressFile, json_encode($progressData, JSON_PRETTY_PRINT));
    
    // Start logging
    $logFile = $workingDir . '/process.log';
    file_put_contents($logFile, "Processing file: $inputFile\n");
    file_put_contents($logFile, "Started: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    
    // Update progress
    updateProgress($progressFile, 'checking', 5, 'Checking dependencies', 'Verifying vips installation');
    
    // Check if vips is installed
    exec('which vips', $whichOutput, $whichReturnCode);
    if ($whichReturnCode !== 0) {
        $errorMsg = "Error: vips command not found. Please install vips.";
        file_put_contents($logFile, $errorMsg . "\n", FILE_APPEND);
        
        updateProgress($progressFile, 'error', 100, 'Error', $errorMsg);
        
        $statusData = [
            'filename' => $filename,
            'dziPath' => "$workingDir/{$baseFilename}_deepzoom.dzi",
            'status' => 'error',
            'startTime' => microtime(true),
            'endTime' => microtime(true),
            'processingTime' => 0,
            'viewerPath' => "$workingDir/$baseFilename.html",
            'error' => $errorMsg
        ];
        
        file_put_contents("$workingDir/status.json", json_encode($statusData, JSON_PRETTY_PRINT));
        
        // Create an error viewer that mentions the problem
        createErrorViewer($workingDir, $baseFilename, $errorMsg);
        
        return $statusData;
    }
    
    // Update progress
    updateProgress($progressFile, 'preparing', 10, 'Preparing conversion', 'Building vips command');
    
    // Build the vips command
    $vipsCommand = "vips dzsave \"$inputFile\" \"$workingDir/{$baseFilename}\" " .
                   "--tile-size={$options['tileSize']} " .
                   "--overlap={$options['overlap']} " .
                   "--suffix=.jpg[Q={$options['quality']}] " .
                   "2>> \"$logFile\"";
    
    // Log the command
    file_put_contents($logFile, "Command: $vipsCommand\n", FILE_APPEND);
    
    // Update progress
    updateProgress($progressFile, 'processing', 15, 'Processing image', 'Converting image to DZI format');
    
    // Set up a background process that periodically updates progress
    $progressUpdaterPid = startProgressUpdater($workingDir, $baseFilename, $progressFile);
    
    // Execute the command
    $startTime = microtime(true);
    exec($vipsCommand, $output, $returnCode);
    $endTime = microtime(true);
    
    // Stop the progress updater
    if ($progressUpdaterPid) {
        exec("kill $progressUpdaterPid 2>/dev/null");
    }
    
    // Log the output and return code
    file_put_contents($logFile, "Return code: $returnCode\n", FILE_APPEND);
    file_put_contents($logFile, "Output: " . implode("\n", $output) . "\n", FILE_APPEND);
    
    // Check if the command was successful
    if ($returnCode !== 0) {
        $errorMsg = "Error: vips dzsave failed with code $returnCode";
        file_put_contents($logFile, $errorMsg . "\n", FILE_APPEND);
        
        updateProgress($progressFile, 'error', 100, 'Error', $errorMsg);
        
        $statusData = [
            'filename' => $filename,
            'dziPath' => "$workingDir/{$baseFilename}_deepzoom.dzi",
            'status' => 'error',
            'startTime' => $startTime,
            'endTime' => $endTime,
            'processingTime' => round($endTime - $startTime, 2),
            'viewerPath' => "$workingDir/$baseFilename.html",
            'error' => $errorMsg,
            'returnCode' => $returnCode
        ];
        
        file_put_contents("$workingDir/status.json", json_encode($statusData, JSON_PRETTY_PRINT));
        
        // Create an error viewer that mentions the problem
        createErrorViewer($workingDir, $baseFilename, $errorMsg);
        
        return $statusData;
    }
    
    // Update progress
    updateProgress($progressFile, 'finishing', 85, 'Finalizing', 'Preparing viewer');
    
    // Rename the output files to match our convention
    $dziIn = "$workingDir/$baseFilename.dzi";
    $filesIn = "$workingDir/{$baseFilename}_files";
    $dziOut = "$workingDir/{$baseFilename}_deepzoom.dzi";
    $filesOut = "$workingDir/{$baseFilename}_deepzoom_files";
    
    if (file_exists($dziIn)) {
        rename($dziIn, $dziOut);
    } else {
        $errorMsg = "Error: DZI file not created at expected path: $dziIn";
        file_put_contents($logFile, $errorMsg . "\n", FILE_APPEND);
        
        updateProgress($progressFile, 'error', 100, 'Error', $errorMsg);
        
        $statusData = [
            'filename' => $filename,
            'dziPath' => "$workingDir/{$baseFilename}_deepzoom.dzi",
            'status' => 'error',
            'startTime' => $startTime,
            'endTime' => $endTime,
            'processingTime' => round($endTime - $startTime, 2),
            'viewerPath' => "$workingDir/$baseFilename.html",
            'error' => $errorMsg
        ];
        
        file_put_contents("$workingDir/status.json", json_encode($statusData, JSON_PRETTY_PRINT));
        
        // Create an error viewer that mentions the problem
        createErrorViewer($workingDir, $baseFilename, $errorMsg);
        
        return $statusData;
    }
    
    if (file_exists($filesIn)) {
        rename($filesIn, $filesOut);
    }
    
    // Update progress
    updateProgress($progressFile, 'creating_viewer', 90, 'Creating viewer', 'Generating HTML viewer');
    
    // Create an HTML viewer for this slide
    createViewer($dziOut, $workingDir, $baseFilename);
    
    // Log completion
    file_put_contents($logFile, "Completed: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    file_put_contents($logFile, "Processing time: " . round($endTime - $startTime, 2) . " seconds\n", FILE_APPEND);
    
    // Update progress to complete
    updateProgress($progressFile, 'complete', 100, 'Complete', 'Processing completed successfully');
    
    // Create status file
    $statusData = [
        'filename' => $filename,
        'dziPath' => $dziOut,
        'status' => 'success',
        'startTime' => $startTime,
        'endTime' => $endTime,
        'processingTime' => round($endTime - $startTime, 2),
        'viewerPath' => "$workingDir/$baseFilename.html"
    ];
    
    file_put_contents("$workingDir/status.json", json_encode($statusData, JSON_PRETTY_PRINT));
    
    return $statusData;
}

/**
 * Update the progress file with current status
 * 
 * @param string $progressFile Path to the progress file
 * @param string $status Current status (initializing, processing, complete, error)
 * @param int $percent Completion percentage (0-100)
 * @param string $stage Current processing stage
 * @param string $message Status message
 */
function updateProgress($progressFile, $status, $percent, $stage, $message) {
    $progressData = [
        'status' => $status,
        'percent' => $percent,
        'stage' => $stage,
        'message' => $message,
        'timestamp' => microtime(true)
    ];
    
    file_put_contents($progressFile, json_encode($progressData, JSON_PRETTY_PRINT));
}

/**
 * Start a background process that updates progress during long operations
 * 
 * @param string $workingDir Directory containing processing files
 * @param string $baseFilename Base filename without extension
 * @param string $progressFile Path to the progress file
 * @return int|null Process ID of the background process or null on failure
 */
function startProgressUpdater($workingDir, $baseFilename, $progressFile) {
    // Create a temp script that will monitor the progress
    $scriptPath = $workingDir . '/progress_updater.php';
    
    $scriptContent = '<?php
// This is a background progress updater
set_time_limit(0);

$workingDir = ' . var_export($workingDir, true) . ';
$baseFilename = ' . var_export($baseFilename, true) . ';
$progressFile = ' . var_export($progressFile, true) . ';

// Get initial progress data
$progressData = json_decode(file_get_contents($progressFile), true);
$startPercent = $progressData["percent"] ?? 15;

// Check for DZI files directory as a progress indicator
$filesDir = "$workingDir/{$baseFilename}_files";
if (!file_exists($filesDir)) {
    $filesDir = "$workingDir/{$baseFilename}_deepzoom_files";
}

// Run for about 5 minutes max (300 seconds)
$timeout = time() + 300;
$lastUpdate = time();

while (time() < $timeout) {
    // Sleep for a bit to reduce CPU usage
    sleep(2);
    
    // Check if the progress file still exists (process might be done)
    if (!file_exists($progressFile)) {
        break;
    }
    
    // Read current progress
    $progressData = json_decode(file_get_contents($progressFile), true);
    
    // If status is complete or error, exit
    if (in_array($progressData["status"], ["complete", "error"])) {
        break;
    }
    
    // Increment the percent over time to show progress
    // This is an approximation since we can\'t easily get progress from vips
    $elapsedTime = time() - $lastUpdate;
    $newPercent = min($progressData["percent"] + ($elapsedTime * 0.1), 80);
    
    // Update progress file if directories are being created
    if (file_exists($filesDir)) {
        // Count number of zoom level directories as a rough progress indicator
        $zoomLevels = glob("$filesDir/*", GLOB_ONLYDIR);
        $levelCount = count($zoomLevels);
        
        if ($levelCount > 0) {
            // Use zoom levels to estimate progress
            // Max is typically around 12-15 levels for large images
            $maxLevels = 15;
            $estimatedPercent = 15 + (min($levelCount, $maxLevels) / $maxLevels * 65);
            $newPercent = max($newPercent, $estimatedPercent);
        }
    }
    
    // Update progress
    $progressData["percent"] = min(round($newPercent), 80); // Cap at 80%
    $progressData["message"] = "Creating image pyramid (approx. " . $progressData["percent"] . "%)";
    $progressData["timestamp"] = microtime(true);
    
    file_put_contents($progressFile, json_encode($progressData, JSON_PRETTY_PRINT));
    $lastUpdate = time();
}
';
    
    file_put_contents($scriptPath, $scriptContent);
    
    // Start background process
    $command = "php -f $scriptPath > /dev/null 2>&1 & echo $!";
    exec($command, $output);
    
    if (isset($output[0]) && is_numeric($output[0])) {
        return $output[0]; // Return the process ID
    }
    
    return null;
}

/**
 * Create a viewer HTML file for a DZI file
 * 
 * @param string $dziFile Path to the DZI file
 * @param string $outputDir Directory to save the viewer
 * @param string $title Title for the viewer
 * @return string Path to the created HTML file
 */
function createViewer($dziFile, $outputDir, $title) {
    // Path to the template
    $templatePath = __DIR__ . '/../../templates/viewer.html';
    
    // If template doesn't exist, create a basic one
    if (!file_exists($templatePath)) {
        $template = '<!DOCTYPE html>
<html>
<head>
    <title>{{TITLE}} - AI Slide Viewer</title>
    <script src="../../js/openseadragon.min.js"></script>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; }
        #header {
            background-color: #333;
            color: white;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        #viewer { width: 100%; height: calc(100% - 50px); }
        .button {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 8px 16px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div id="header">
        <h2>{{TITLE}}</h2>
        <div>
            <a href="../../index.php" class="button">Back to Dashboard</a>
        </div>
    </div>
    <div id="viewer"></div>
    <script>
        var viewer = OpenSeadragon({
            id: "viewer",
            prefixUrl: "../../js/images/",
            tileSources: "{{DZI_PATH}}",
            showNavigator: true,
            navigatorPosition: "BOTTOM_RIGHT"
        });
    </script>
</body>
</html>';
    } else {
        $template = file_get_contents($templatePath);
    }
    
    // Replace placeholders
    $html = str_replace('{{TITLE}}', $title, $template);
    $html = str_replace('{{DZI_PATH}}', basename($dziFile), $html);
    
    // Save the HTML file
    $htmlPath = "$outputDir/$title.html";
    file_put_contents($htmlPath, $html);
    
    return $htmlPath;
}

/**
 * Create an error viewer HTML file
 * 
 * @param string $outputDir Directory to save the viewer
 * @param string $title Title for the viewer
 * @param string $errorMsg Error message to display
 * @return string Path to the created HTML file
 */
function createErrorViewer($outputDir, $title, $errorMsg) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <title>' . $title . ' - Error</title>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; font-family: Arial, sans-serif; }
        #header {
            background-color: #333;
            color: white;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        #content { 
            width: 80%; 
            margin: 50px auto; 
            padding: 20px;
            background-color: #f8f8f8;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .error-message {
            color: #d9534f;
            padding: 15px;
            background-color: #f9f2f2;
            border-left: 5px solid #d9534f;
            margin-bottom: 20px;
        }
        .button {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 8px 16px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div id="header">
        <h2>' . $title . ' - Error</h2>
        <div>
            <a href="../../index.php" class="button">Back to Dashboard</a>
        </div>
    </div>
    <div id="content">
        <h2>Error Processing Slide</h2>
        <div class="error-message">
            ' . htmlspecialchars($errorMsg) . '
        </div>
        <p>There was an error processing this slide. Please check the process log for more details.</p>
        <p>Common issues:</p>
        <ul>
            <li>Missing vips installation</li>
            <li>Unsupported image format</li>
            <li>Corrupted image file</li>
        </ul>
    </div>
</body>
</html>';
    
    // Save the HTML file
    $htmlPath = "$outputDir/$title.html";
    file_put_contents($htmlPath, $html);
    
    return $htmlPath;
}
?>