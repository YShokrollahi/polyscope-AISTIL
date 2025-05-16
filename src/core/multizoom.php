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
    
    // Define possible locations for OpenSeadragon files
    $possibleJsSources = [
        __DIR__ . '/../../www/js/openseadragon.min.js',
        __DIR__ . '/../../templates/js/openseadragon.min.js'
    ];
    
    // Try to find OpenSeadragon JS file
    $sourceJsFile = null;
    foreach ($possibleJsSources as $path) {
        if (file_exists($path)) {
            $sourceJsFile = $path;
            break;
        }
    }
    
    // Copy the JS file if found
    $destJsFile = $jsDir . '/openseadragon.min.js';
    if ($sourceJsFile && !file_exists($destJsFile)) {
        copy($sourceJsFile, $destJsFile);
    } else if (!$sourceJsFile) {
        // Log error and try to download directly
        error_log("Error: OpenSeadragon JS file not found at any of the expected locations. Attempting to download directly.");
        
        // Attempt to download directly from CDN
        $osJsContent = @file_get_contents('https://cdn.jsdelivr.net/npm/openseadragon@3.0.0/build/openseadragon/openseadragon.min.js');
        if ($osJsContent !== false) {
            file_put_contents($destJsFile, $osJsContent);
            error_log("Successfully downloaded OpenSeadragon JS directly from CDN.");
        } else {
            error_log("Failed to download OpenSeadragon JS from CDN.");
        }
    }
    
    // Define possible locations for image files
    $possibleImageDirs = [
        __DIR__ . '/../../www/js/images',
        __DIR__ . '/../../templates/js/images'
    ];
    
    // Try to find images directory
    $sourceImagesDir = null;
    foreach ($possibleImageDirs as $path) {
        if (is_dir($path)) {
            $sourceImagesDir = $path;
            break;
        }
    }
    
    // Copy image files or download them if needed
    $basicImages = ['home.png', 'fullpage.png', 'zoomin.png', 'zoomout.png'];
    if ($sourceImagesDir) {
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
        // Download basic images if source directory doesn't exist
        error_log("Warning: Could not find OpenSeadragon images directory. Downloading images from GitHub.");
        
        foreach ($basicImages as $imageFile) {
            $destImageFile = $imagesDir . '/' . $imageFile;
            if (!file_exists($destImageFile)) {
                $imageUrl = "https://raw.githubusercontent.com/openseadragon/openseadragon/master/images/$imageFile";
                $imageContent = @file_get_contents($imageUrl);
                if ($imageContent !== false) {
                    file_put_contents($destImageFile, $imageContent);
                    error_log("Downloaded image: $imageFile");
                } else {
                    error_log("Failed to download image: $imageFile");
                }
            }
        }
    }
    
    // Create a CSS file for custom OpenSeadragon styles
    $cssContent = <<<CSS
/* Custom OpenSeadragon controls */
.openseadragon-container .openseadragon-control {
    margin: 5px;
    width: 36px !important;
    height: 36px !important;
    border-radius: 50% !important;
    background-color: rgba(0, 0, 0, 0.6) !important;
    border: 2px solid #fff !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: background-color 0.2s !important;
}

.openseadragon-container .openseadragon-control:hover {
    background-color: rgba(0, 0, 0, 0.8) !important;
}

.openseadragon-container img.openseadragon-control {
    padding: 8px !important;
    box-sizing: border-box !important;
}

/* Reposition the controls for better spacing */
.openseadragon-container .navigator-wrapper {
    margin-right: 10px !important;
    margin-bottom: 10px !important;
    border: 2px solid rgba(255, 255, 255, 0.6) !important;
    border-radius: 5px !important;
}

.openseadragon-container .navigator {
    border-radius: 3px !important;
}
CSS;

    $cssFile = $jsDir . '/openseadragon-custom.css';
    file_put_contents($cssFile, $cssContent);
    
    // Check if OpenSeadragon JS is now available
    if (file_exists($destJsFile)) {
        error_log("OpenSeadragon JS is available at: $destJsFile");
    } else {
        error_log("ERROR: Failed to provide OpenSeadragon JS");
    }
    
    // Check if images are available
    $availableImages = glob($imagesDir . '/*.png');
    error_log(count($availableImages) . " OpenSeadragon image files available in $imagesDir");
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
    
    // Create QC Mask legend HTML
    $qcMaskLegendHTML = '
        <div class="legend qc-mask-legend">
            <div class="legend-title">QC Mask</div>
            <div class="legend-items">
                <span class="legend-item"><span class="color-box" style="background-color: #808080;"></span>No Artifact</span>
                <span class="legend-item"><span class="color-box" style="background-color: #FF6347;"></span>Fold</span>
                <span class="legend-item"><span class="color-box" style="background-color: #00FF00;"></span>Darkspot</span>
                <span class="legend-item"><span class="color-box" style="background-color: #FF0000;"></span>Pen</span>
                <span class="legend-item"><span class="color-box" style="background-color: #FF00FF;"></span>Edge</span>
                <span class="legend-item"><span class="color-box" style="background-color: #4B0082;"></span>Out of focus</span>
            </div>
        </div>';
    
    // Create Cell Classification legend HTML
    $cellClassLegendHTML = '
        <div class="legend cell-classification-legend">
            <div class="legend-title">Cell Classification</div>
            <div class="legend-items">
                <span class="legend-item"><span class="color-box" style="background-color: #00FF00;"></span>Epithelial</span>
                <span class="legend-item"><span class="color-box" style="background-color: #0000FF;"></span>Lymphocyte</span>
                <span class="legend-item"><span class="color-box" style="background-color: #FFFF00;"></span>Fibroblast</span>
                <span class="legend-item"><span class="color-box" style="background-color: #FFFFFF; border: 1px solid #999;"></span>Other</span>
            </div>
        </div>';
    
    // Create TMEseg Mask legend HTML
    $tmesegMaskLegendHTML = '
        <div class="legend tmeseg-mask-legend">
            <div class="legend-title">TMEseg Mask</div>
            <div class="legend-items">
                <span class="legend-item"><span class="color-box" style="background-color: #FFCC00;"></span>Stroma</span>
                <span class="legend-item"><span class="color-box" style="background-color: #800000;"></span>Tumor</span>
                <span class="legend-item"><span class="color-box" style="background-color: #FF00FF;"></span>Necrosis/Hemorrhage</span>
                <span class="legend-item"><span class="color-box" style="background-color: #808000;"></span>Adipose</span>
                <span class="legend-item"><span class="color-box" style="background-color: #00FFFF;"></span>Parenchyma</span>
            </div>
        </div>';
    
    // Determine which legend to use for each viewer based on the name
    $viewersHTML = '';
    foreach ($viewerData as $index => $viewer) {
        $legendHTML = '';
        $shouldShowLegend = true;
        
        // Skip legend for the first (top-left) viewer
        if ($index === 0) {
            $shouldShowLegend = false;
        } else {
            // Check the viewer name to determine which legend to show
            $viewerName = strtolower($viewer['name']);
            
            if (strpos($viewerName, 'qc') !== false || strpos($viewerName, 'map_qc') !== false) {
                $legendHTML = $qcMaskLegendHTML;
            } 
            elseif (strpos($viewerName, 'classification') !== false) {
                $legendHTML = $cellClassLegendHTML;
            } 
            elseif (strpos($viewerName, 'tmeseg') !== false || 
                   strpos($viewerName, 'mask') !== false ||
                   strpos($viewerName, 'ss1') !== false) {
                $legendHTML = $tmesegMaskLegendHTML;
            }
            else {
                // If no specific match, make a best guess based on position
                if ($index == 1) {
                    $legendHTML = $qcMaskLegendHTML;
                } elseif ($index == 2) {
                    $legendHTML = $tmesegMaskLegendHTML;
                } elseif ($index == 3) {
                    $legendHTML = $cellClassLegendHTML;
                } else {
                    $shouldShowLegend = false; // If can't determine, don't show a legend
                }
            }
        }
        
        $legendContainer = $shouldShowLegend ? 
            "<div class=\"viewer-legend\">{$legendHTML}</div>" : '';
        
        $viewersHTML .= "
        <div class=\"viewer-container" . (!$shouldShowLegend ? " no-legend" : "") . "\">
            <div class=\"viewer-title\">{$viewer['name']}</div>
            <div id=\"{$viewer['id']}\" class=\"viewer\"></div>
            {$legendContainer}
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
    <link rel="stylesheet" href="js/openseadragon-custom.css">
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
            display: flex;
            flex-direction: column;
        }
        .viewer {
            flex: 1;
            width: 100%;
        }
        .viewer-container.no-legend .viewer {
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
        
        /* Viewer Legend Styles */
        .viewer-legend {
            background-color: rgba(245, 245, 245, 0.9);
            border-top: 1px solid #ddd;
            padding: 5px;
            height: 40px;
            overflow: hidden;
        }
        .legend {
            font-size: 11px;
        }
        .legend-title {
            font-weight: bold;
            margin-bottom: 2px;
        }
        .legend-items {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            white-space: nowrap;
            margin-right: 8px;
        }
        .color-box {
            display: inline-block;
            width: 10px;
            height: 10px;
            margin-right: 3px;
            border: 1px solid #999;
        }
        .toggle-legends {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background-color: rgba(76, 175, 80, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 10px;
            cursor: pointer;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .toggle-legends:hover {
            background-color: rgba(76, 175, 80, 1);
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
        
        // Define viewer relationships for synchronization
        // Each viewer syncs with all other viewers
        const ViewerRelationships = {};
        
        // Build the relationships dynamically based on viewerData
        viewerData.forEach(viewer => {
            ViewerRelationships[viewer.id] = viewerData
                .filter(v => v.id !== viewer.id)
                .map(v => v.id);
        });
        
        // Debug function
        function toggleDebug() {
            const debugEl = document.getElementById('debug-info');
            if (debugEl.style.display === 'none') {
                debugEl.style.display = 'block';
                debugEl.innerHTML = 'Viewer Data:<br>' + JSON.stringify(viewerData, null, 2).replace(/\\n/g, '<br>') + 
                    '<br><br>ViewerRelationships:<br>' + JSON.stringify(ViewerRelationships, null, 2).replace(/\\n/g, '<br>');
            } else {
                debugEl.style.display = 'none';
            }
        }
        
        // Add toggle buttons for each legend
        document.querySelectorAll('.viewer-container:not(.no-legend)').forEach(container => {
            const legend = container.querySelector('.viewer-legend');
            if (legend) {
                const toggleBtn = document.createElement('button');
                toggleBtn.className = 'toggle-legends';
                toggleBtn.innerHTML = '<span style="font-size:14px;">L</span>';
                toggleBtn.title = 'Toggle Legend';
                toggleBtn.addEventListener('click', function() {
                    if (legend.style.display === 'none') {
                        legend.style.display = 'block';
                        this.innerHTML = '<span style="font-size:14px;">L</span>';
                    } else {
                        legend.style.display = 'none';
                        this.innerHTML = '<span style="font-size:14px;">L</span>';
                    }
                    
                    // Update viewer after layout change
                    setTimeout(() => {
                        const viewerId = container.querySelector('.viewer').id;
                        if (viewers[viewerId]) {
                            viewers[viewerId].updateOnResize();
                        }
                    }, 100);
                });
                container.appendChild(toggleBtn);
            }
        });
        
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
                        autoResize: true,
                        controlsFadeDelay: 1500,    // Delay before controls fade out
                        controlsFadeLength: 1000,   // Duration of the fade animation
                        zoomInButton: 'zoomIn',     // Custom button IDs
                        zoomOutButton: 'zoomOut',   // Custom button IDs
                        homeButton: 'home',         // Custom button IDs
                        fullPageButton: 'fullPage'  // Custom button IDs
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
                setTimeout(syncAll, 1000); // Slight delay to ensure all viewers are ready
            }
        });
        
        // Function to synchronize one viewer to another
        function syncImage(targetViewer, sourceViewer) {
            console.log('Syncing ' + targetViewer.element.id + ' with ' + sourceViewer.element.id);
            
            // Synchronize pan position
            const center = sourceViewer.viewport.getCenter();
            targetViewer.viewport.panTo(center, false);
            
            // Synchronize zoom level
            const zoom = sourceViewer.viewport.getZoom();
            targetViewer.viewport.zoomTo(zoom, null, false);
        }
        
        // Animation event handler for synchronization
        function animationHandler(event) {
            if (!syncViews || activeSync) return;
            
            activeSync = true;
            
            const sourceViewer = event.eventSource;
            console.log('Animation event from ' + sourceViewer.element.id);
            
            // Get target viewers to sync with this source
            const targetsToSync = ViewerRelationships[sourceViewer.element.id] || [];
            
            // Sync each target viewer
            targetsToSync.forEach(targetId => {
                if (viewers[targetId] && viewers[targetId] !== sourceViewer) {
                    syncImage(viewers[targetId], sourceViewer);
                }
            });
            
            // Release sync lock after a short delay
            setTimeout(() => { 
                activeSync = false;
                console.log('Sync complete, lock released');
            }, 50);
        }
        
        // Apply synchronization to all viewers
        function syncAll() {
            console.log('Setting up synchronization for all viewers');
            
            // First remove any existing handlers
            Object.values(viewers).forEach(viewer => {
                if (viewer) {
                    viewer.removeHandler('animation', animationHandler);
                }
            });
            
            // Then add handlers if sync is enabled
            if (syncViews) {
                Object.values(viewers).forEach(viewer => {
                    if (viewer) {
                        console.log('Adding animation handler to ' + viewer.element.id);
                        viewer.addHandler('animation', animationHandler);
                    }
                });
            }
        }
        
        // Sync toggle handler
        document.getElementById('sync-toggle').addEventListener('change', function() {
            syncViews = this.checked;
            console.log('Synchronization toggled ' + (syncViews ? 'on' : 'off'));
            syncAll();
        });
        
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