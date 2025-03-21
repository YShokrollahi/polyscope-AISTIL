<?php
/**
 * Multi-zoom module
 * Creates synchronized views of multiple DZI files
 */

/**
 * Create a multizoom view from multiple DZI files
 * 
 * @param array $dziFiles Array of paths to DZI files
 * @param string $outputDir Directory to save the multizoom view
 * @param array $options Additional options
 * @return array Result information
 */
function createMultizoom($dziFiles, $outputDir, $options = []) {
    // Default options
    $defaults = [
        'title' => 'Multi-Zoom View',
        'syncViews' => true
    ];
    
    $options = array_merge($defaults, $options);
    
    // Create output directory if it doesn't exist
    if (!file_exists($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    // Log file for debugging
    $logFile = $outputDir . '/multizoom_debug.log';
    file_put_contents($logFile, "Creating multizoom with " . count($dziFiles) . " files\n");
    file_put_contents($logFile, "DZI Files: " . json_encode($dziFiles, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
    
    // Prepare data for the viewer
    $viewerData = [];
    foreach ($dziFiles as $index => $dziFile) {
        $id = 'viewer' . ($index + 1);
        $name = basename(dirname($dziFile));
        
        // Get the path relative to the multizoom folder
        $relativePath = getRelativePathForMultizoom($outputDir, $dziFile);
        
        file_put_contents($logFile, "File $index: $dziFile\n", FILE_APPEND);
        file_put_contents($logFile, "  ID: $id\n", FILE_APPEND);
        file_put_contents($logFile, "  Name: $name\n", FILE_APPEND);
        file_put_contents($logFile, "  Relative Path: $relativePath\n", FILE_APPEND);
        
        $viewerData[] = [
            'id' => $id,
            'name' => $name,
            'path' => $relativePath
        ];
    }
    
    // Create the HTML file
    $htmlPath = $outputDir . '/index.html';
    $html = generateMultizoomHTML($viewerData, $options['title'], $options['syncViews']);
    file_put_contents($htmlPath, $html);
    
    // Copy OpenSeadragon JS to ensure it's available
    ensureOpenSeadragonAvailable($outputDir);
    
    // Save viewer data for reference
    file_put_contents($outputDir . '/config.json', json_encode([
        'title' => $options['title'],
        'syncViews' => $options['syncViews'],
        'viewers' => $viewerData
    ], JSON_PRETTY_PRINT));
    
    return [
        'htmlPath' => $htmlPath,
        'viewerCount' => count($dziFiles),
        'title' => $options['title']
    ];
}

/**
 * Ensure OpenSeadragon JS is available in the output directory
 * 
 * @param string $outputDir The output directory
 */
function ensureOpenSeadragonAvailable($outputDir) {
    // Create js directory if it doesn't exist
    $jsDir = $outputDir . '/js';
    if (!file_exists($jsDir)) {
        mkdir($jsDir, 0755, true);
    }
    
    // Create images directory if it doesn't exist
    $imagesDir = $jsDir . '/images';
    if (!file_exists($imagesDir)) {
        mkdir($imagesDir, 0755, true);
    }
    
    // Copy OpenSeadragon JS if it doesn't exist
    $sourceJsFile = __DIR__ . '/../../www/js/openseadragon.min.js';
    $destJsFile = $jsDir . '/openseadragon.min.js';
    if (!file_exists($destJsFile) && file_exists($sourceJsFile)) {
        copy($sourceJsFile, $destJsFile);
    }
    
    // Copy all necessary image files
    $sourceImagesDir = __DIR__ . '/../../www/js/images';
    if (is_dir($sourceImagesDir)) {
        $imageFiles = scandir($sourceImagesDir);
        foreach ($imageFiles as $imageFile) {
            if ($imageFile != '.' && $imageFile != '..') {
                $sourceImageFile = $sourceImagesDir . '/' . $imageFile;
                $destImageFile = $imagesDir . '/' . $imageFile;
                if (!file_exists($destImageFile) && file_exists($sourceImageFile)) {
                    copy($sourceImageFile, $destImageFile);
                }
            }
        }
    } else {
        // Copy basic required images if source directory doesn't exist
        $imageFiles = ['home.png', 'fullpage.png', 'zoomin.png', 'zoomout.png'];
        foreach ($imageFiles as $imageFile) {
            $sourceImageFile = __DIR__ . '/../../www/js/images/' . $imageFile;
            $destImageFile = $imagesDir . '/' . $imageFile;
            if (!file_exists($destImageFile) && file_exists($sourceImageFile)) {
                copy($sourceImageFile, $destImageFile);
            }
        }
    }
}

/**
 * Generate HTML for the multizoom viewer
 * 
 * @param array $viewerData Data for each viewer
 * @param string $title Title for the page
 * @param bool $syncViews Whether to synchronize views
 * @return string HTML content
 */
function generateMultizoomHTML($viewerData, $title, $syncViews = true) {
    $viewerCount = count($viewerData);
    $layoutClass = ($viewerCount <= 2) ? 'dual-view' : 'grid-view';
    
    $viewersHTML = '';
    foreach ($viewerData as $viewer) {
        $viewersHTML .= "
        <div class=\"viewer-container\">
            <div class=\"viewer-title\">{$viewer['name']}</div>
            <div id=\"{$viewer['id']}\" class=\"viewer\"></div>
        </div>";
    }
    
    $viewerDataJSON = json_encode($viewerData);
    $syncViewsValue = $syncViews ? 'true' : 'false';
    
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>$title - AI-Polyscope</title>
    <script src="js/openseadragon.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            overflow: hidden;
        }
        #header {
            background-color: #333;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 40px;
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
        #viewers-container {
            display: flex;
            flex-wrap: wrap;
            position: absolute;
            top: 60px;
            left: 0;
            right: 0;
            bottom: 0;
            height: calc(100vh - 60px);
            width: 100%;
        }
        .viewer-container {
            position: relative;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        .viewer {
            width: 100%;
            height: 100%;
        }
        .viewer-title {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            z-index: 10;
        }
        .dual-view .viewer-container {
            width: 50%;
            height: 100%;
        }
        .grid-view .viewer-container {
            width: 50%;
            height: 50%;
        }
        .controls {
            display: flex;
            align-items: center;
        }
        .sync-control {
            margin-right: 15px;
            color: white;
        }
        #debug-info {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 1000;
            display: none;
            max-height: 300px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <div id="header">
        <h2 style="margin: 0;">$title</h2>
        <div class="controls">
            <div class="sync-control">
                <input type="checkbox" id="sync-toggle" checked>
                <label for="sync-toggle">Synchronize Views</label>
            </div>
            <a href="../index.php" class="button">Back to Dashboard</a>
            <button onclick="toggleDebug()" class="button" style="margin-left: 10px;">Debug</button>
        </div>
    </div>
    
    <div id="viewers-container" class="$layoutClass">
        $viewersHTML
    </div>
    
    <div id="debug-info"></div>
    
    <script>
        // Viewer data
        const viewerData = $viewerDataJSON;
        const viewers = {};
        let syncViews = $syncViewsValue;
        let activeSync = false; // Flag to prevent recursive synchronization
        
        // Debug function
        function toggleDebug() {
            const debugEl = document.getElementById('debug-info');
            if (debugEl.style.display === 'none') {
                debugEl.style.display = 'block';
                debugEl.innerHTML = 'Viewer Data:<br>' + JSON.stringify(viewerData, null, 2).replace(/\\n/g, '<br>');
            } else {
                debugEl.style.display = 'none';
            }
        }
        
        // Wait for OpenSeadragon to load
        window.addEventListener('load', function() {
            console.log('Page loaded, initializing viewers');
            
            if (typeof OpenSeadragon === 'undefined') {
                alert('Error: OpenSeadragon library not loaded. Please check console for details.');
                console.error('OpenSeadragon is not defined. Check that the JS file is loaded correctly.');
                return;
            }
            
            // Initialize viewers
            viewerData.forEach(data => {
                console.log('Creating viewer:', data.id, 'with source:', data.path);
                try {
                    viewers[data.id] = OpenSeadragon({
                        id: data.id,
                        prefixUrl: "js/images/",
                        tileSources: data.path,
                        showNavigator: true,
                        navigatorPosition: "BOTTOM_RIGHT",
                        visibilityRatio: 1.0,
                        constrainDuringPan: true,
                        autoResize: true
                    });
                    
                    // Log successful creation
                    console.log('Viewer created successfully:', data.id);
                } catch (error) {
                    console.error('Error creating viewer:', error);
                    document.getElementById(data.id).innerHTML = 
                        '<div style="color: red; padding: 20px;">Error loading viewer: ' + error.message + '</div>';
                }
            });
            
            // Add synchronization handlers after all viewers are initialized
            if (syncViews) {
                setTimeout(setupSyncHandlers, 1000); // Slight delay to ensure all viewers are ready
            }
        });
        
        // Set up synchronization handlers
        function setupSyncHandlers() {
            viewerData.forEach(data => {
                if (viewers[data.id]) {
                    viewers[data.id].addHandler('pan', function() {
                        if (syncViews && !activeSync) {
                            activeSync = true;
                            syncViewers(data.id, 'pan');
                            setTimeout(() => { activeSync = false; }, 50);
                        }
                    });
                    
                    viewers[data.id].addHandler('zoom', function() {
                        if (syncViews && !activeSync) {
                            activeSync = true;
                            syncViewers(data.id, 'zoom');
                            setTimeout(() => { activeSync = false; }, 50);
                        }
                    });
                }
            });
        }
        
        // Sync toggle handler
        document.getElementById('sync-toggle').addEventListener('change', function() {
            syncViews = this.checked;
            if (syncViews) {
                setupSyncHandlers();
            }
        });
        
        // Function to synchronize viewers
        function syncViewers(sourceId, eventType) {
            const sourceViewer = viewers[sourceId];
            if (!sourceViewer) return;
            
            Object.keys(viewers).forEach(id => {
                if (id !== sourceId && viewers[id]) {
                    const targetViewer = viewers[id];
                    
                    if (eventType === 'pan') {
                        const center = sourceViewer.viewport.getCenter();
                        targetViewer.viewport.panTo(center, false);
                    } else if (eventType === 'zoom') {
                        const zoom = sourceViewer.viewport.getZoom();
                        targetViewer.viewport.zoomTo(zoom, null, false);
                    }
                }
            });
        }
        
        // Handle window resize to ensure viewers fit correctly
        window.addEventListener('resize', function() {
            Object.keys(viewers).forEach(id => {
                if (viewers[id]) {
                    viewers[id].updateOnResize();
                }
            });
        });
    </script>
</body>
</html>
HTML;

    return $html;
}

/**
 * Get the relative path specifically for multizoom usage
 * 
 * @param string $multizoomDir Path to the multizoom directory
 * @param string $dziFile Path to the DZI file
 * @return string Properly formatted relative path
 */
function getRelativePathForMultizoom($multizoomDir, $dziFile) {
    // For debugging, save full path info
    $debugInfo = [
        'multizoomDir' => $multizoomDir,
        'dziFile' => $dziFile,
        'realMultizoomDir' => realpath($multizoomDir),
        'realDziFile' => realpath($dziFile)
    ];
    file_put_contents($multizoomDir . '/path_debug.json', json_encode($debugInfo, JSON_PRETTY_PRINT));
    
    // Simple approach using .. to navigate up one level then back down
    // This works if multizoom is in the same parent directory as the slide directories
    $slideName = basename(dirname($dziFile));
    return "../$slideName/" . basename($dziFile);
}

/**
 * Get the relative path from one directory to another
 * 
 * @param string $from Source directory
 * @param string $to Target file
 * @return string Relative path
 */
function getRelativePath($from, $to) {
    // Convert paths to absolute paths
    $from = realpath($from);
    $to = realpath($to);
    
    // If on Windows, convert path separators
    if (DIRECTORY_SEPARATOR === '\\') {
        $from = str_replace('\\', '/', $from);
        $to = str_replace('\\', '/', $to);
    }
    
    $from = rtrim($from, '/') . '/';
    $to = rtrim(dirname($to), '/') . '/' . basename($to);
    
    // Find the common path
    $commonPath = '';
    $fromParts = explode('/', $from);
    $toParts = explode('/', $to);
    
    $commonLen = min(count($fromParts), count($toParts));
    for ($i = 0; $i < $commonLen; $i++) {
        if ($fromParts[$i] === $toParts[$i]) {
            $commonPath .= $fromParts[$i] . '/';
        } else {
            break;
        }
    }
    
    // Calculate the relative path
    $relative = '';
    for ($i = strlen($commonPath); $i < strlen($from); $i++) {
        if ($from[$i] === '/') {
            $relative .= '../';
        }
    }
    
    $relative .= substr($to, strlen($commonPath));
    
    return $relative;
}
?>