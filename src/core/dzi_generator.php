<?php
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
    
    // Start logging
    $logFile = $workingDir . '/process.log';
    file_put_contents($logFile, "Processing file: $inputFile\n");
    file_put_contents($logFile, "Started: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    
    // Check if vips is installed
    exec('which vips', $whichOutput, $whichReturnCode);
    if ($whichReturnCode !== 0) {
        $errorMsg = "Error: vips command not found. Please install vips.";
        file_put_contents($logFile, $errorMsg . "\n", FILE_APPEND);
        
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
    
    // Build the vips command
    $vipsCommand = "vips dzsave \"$inputFile\" \"$workingDir/{$baseFilename}\" " .
                   "--tile-size={$options['tileSize']} " .
                   "--overlap={$options['overlap']} " .
                   "--suffix=.jpg[Q={$options['quality']}] " .
                   "2>> \"$logFile\"";
    
    // Log the command
    file_put_contents($logFile, "Command: $vipsCommand\n", FILE_APPEND);
    
    // Execute the command
    $startTime = microtime(true);
    exec($vipsCommand, $output, $returnCode);
    $endTime = microtime(true);
    
    // Log the output and return code
    file_put_contents($logFile, "Return code: $returnCode\n", FILE_APPEND);
    file_put_contents($logFile, "Output: " . implode("\n", $output) . "\n", FILE_APPEND);
    
    // Check if the command was successful
    if ($returnCode !== 0) {
        $errorMsg = "Error: vips dzsave failed with code $returnCode";
        file_put_contents($logFile, $errorMsg . "\n", FILE_APPEND);
        
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
    
    // Create an HTML viewer for this slide
    createViewer($dziOut, $workingDir, $baseFilename);
    
    // Log completion
    file_put_contents($logFile, "Completed: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    file_put_contents($logFile, "Processing time: " . round($endTime - $startTime, 2) . " seconds\n", FILE_APPEND);
    
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