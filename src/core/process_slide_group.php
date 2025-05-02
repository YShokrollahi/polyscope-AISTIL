<?php
/**
 * Process Slide Group
 * Takes a folder containing multiple slide images and creates a multi-zoom view
 */

/**
 * Process a slide group from a folder
 * 
 * @param string $inputFolder Path to the input folder containing slide images
 * @param string $outputDir Path to the output directory
 * @param array $options Additional options for processing
 * @return array Result information including the multi-zoom URL
 */
function processSlideGroup($inputFolder, $outputDir, $options = []) {
    // Default options
    $defaults = [
        'tileSize' => 254,
        'overlap' => 1,
        'quality' => 90,
        'multizoomTitle' => 'Multi-Zoom View',
        'svsFile' => '',  // Allow explicit SVS file path
        'svsBasename' => '' // Allow explicit SVS basename
    ];
    
    $options = array_merge($defaults, $options);
    
    // Create output directory if it doesn't exist
    if (!file_exists($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    // Get base name of the input folder for logging
    $baseFolderName = basename($inputFolder);
    
    // Initialize debugging log
    $debugLog = "/tmp/debug.log";
    file_put_contents($debugLog, "Starting processSlideGroup at " . date('Y-m-d H:i:s') . "\n");
    file_put_contents($debugLog, "Input folder: $inputFolder\n", FILE_APPEND);
    file_put_contents($debugLog, "Output directory: $outputDir\n", FILE_APPEND);
    
    // Use provided SVS file if available, otherwise search for it
    $svsFilePath = '';
    $svsBaseName = '';
    $svsFileName = '';
    
    if (!empty($options['svsFile']) && file_exists($options['svsFile'])) {
        $svsFilePath = $options['svsFile'];
        $svsFileName = basename($svsFilePath);
        $svsBaseName = !empty($options['svsBasename']) ? $options['svsBasename'] : pathinfo($svsFileName, PATHINFO_FILENAME);
        
        file_put_contents($debugLog, "Using provided SVS file: $svsFilePath\n", FILE_APPEND);
        file_put_contents($debugLog, "Using provided SVS basename: $svsBaseName\n", FILE_APPEND);
    } else {
        // Find SVS file in the input folder
        $svsFiles = glob($inputFolder . '/*.svs');
        
        if (!empty($svsFiles)) {
            $svsFilePath = $svsFiles[0]; // Use the first SVS file found
            $svsFileName = basename($svsFilePath);
            $svsBaseName = pathinfo($svsFileName, PATHINFO_FILENAME);
            
            file_put_contents($debugLog, "Found SVS file in input folder: $svsFilePath\n", FILE_APPEND);
        } else {
            file_put_contents($debugLog, "No SVS file found in: $inputFolder\n", FILE_APPEND);
            
            // Try looking in subdirectories
            $subDirs = glob($inputFolder . '/*', GLOB_ONLYDIR);
            foreach ($subDirs as $subDir) {
                $subDirSvsFiles = glob($subDir . '/*.svs');
                if (!empty($subDirSvsFiles)) {
                    $svsFilePath = $subDirSvsFiles[0];
                    $svsFileName = basename($svsFilePath);
                    $svsBaseName = pathinfo($svsFileName, PATHINFO_FILENAME);
                    
                    file_put_contents($debugLog, "Found SVS file in subdirectory: $svsFilePath\n", FILE_APPEND);
                    break;
                }
            }
        }
        
        if (empty($svsFilePath)) {
            file_put_contents($debugLog, "Error: No SVS file found in input directory or subdirectories\n", FILE_APPEND);
            return [
                'status' => 'error',
                'error' => 'No SVS file found',
                'slideName' => $baseFolderName,
                'completedAt' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    // Use the SVS basename for the output directory
    $outputBaseName = $svsBaseName;
    
    // Define working directory for output
    $workingDir = $outputDir . '/' . $outputBaseName;
    if (!file_exists($workingDir)) {
        mkdir($workingDir, 0755, true);
    }
    
    // Start logging
    $logFile = $workingDir . '/process.log';
    file_put_contents($logFile, "Processing slide group from folder: $inputFolder\n");
    file_put_contents($logFile, "Started: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    file_put_contents($logFile, "SVS File: $svsFilePath\n", FILE_APPEND);
    file_put_contents($logFile, "SVS Filename: $svsFileName\n", FILE_APPEND);
    file_put_contents($logFile, "SVS Basename: $svsBaseName\n", FILE_APPEND);
    file_put_contents($logFile, "Output directory: $workingDir\n", FILE_APPEND);
    
    // IMPORTANT: Create two versions of each filename we're looking for
    // One with the .svs in the filename, one without
    $auxiliaryFiles = [
        // Format: ["prefix_basename_suffix", "prefix_basename.svs_suffix"]
        'classification' => [
            "${svsBaseName}_classification_stitched.tif",
            "${svsBaseName}.svs_classification_stitched.tif"
        ],
        'tme' => [
            "${svsBaseName}_Ss1.png",
            "${svsBaseName}.svs_Ss1.png"
        ],
        'qc' => [
            "${svsBaseName}_map_QC.png",
            "${svsBaseName}.svs_map_QC.png"
        ]
    ];
    
    $foundFiles = [];
    
    // Search for each auxiliary file using both patterns
    foreach ($auxiliaryFiles as $type => $filePatterns) {
        file_put_contents($debugLog, "Searching for $type files with patterns: " . implode(", ", $filePatterns) . "\n", FILE_APPEND);
        
        foreach ($filePatterns as $pattern) {
            // Try direct paths first
            $possiblePaths = [
                // Path in original subdirectories
                "$inputFolder/4_5_stitch_output_segformer/$pattern",
                "$inputFolder/6_tme_seg/mask_ss1_512_postprocessed/$pattern",
                "$inputFolder/0_autoqc/maps_qc/$pattern",
            ];
            
            foreach ($possiblePaths as $path) {
                file_put_contents($debugLog, "Checking path: $path\n", FILE_APPEND);
                if (file_exists($path)) {
                    $foundFiles[$type] = $path;
                    file_put_contents($debugLog, "Found $type file at: $path\n", FILE_APPEND);
                    break 2; // Break out of both loops once found
                }
            }
        }
        
        if (!isset($foundFiles[$type])) {
            // If not found in direct paths, do a deep search for all patterns
            foreach ($filePatterns as $pattern) {
                exec("find " . escapeshellarg($inputFolder) . " -name " . escapeshellarg($pattern) . " -type f", $output);
                if (!empty($output) && file_exists($output[0])) {
                    $foundFiles[$type] = $output[0];
                    file_put_contents($debugLog, "Found $type file through deep search: {$output[0]}\n", FILE_APPEND);
                    break;
                }
            }
            
            if (!isset($foundFiles[$type])) {
                file_put_contents($debugLog, "Could not find $type file with any pattern\n", FILE_APPEND);
            }
        }
    }
    
    // Define the files we need to process
    $filesToProcess = [
        [
            'path' => $svsFilePath,
            'title' => 'Raw Image (' . $svsBaseName . ')'
        ]
    ];
    
    // Add auxiliary files if found
    if (isset($foundFiles['classification'])) {
        $filesToProcess[] = [
            'path' => $foundFiles['classification'],
            'title' => 'Cell Classification'
        ];
    }
    
    if (isset($foundFiles['tme'])) {
        $filesToProcess[] = [
            'path' => $foundFiles['tme'],
            'title' => 'TMESeg'
        ];
    }
    
    if (isset($foundFiles['qc'])) {
        $filesToProcess[] = [
            'path' => $foundFiles['qc'],
            'title' => 'QutoQC'
        ];
    }
    
    // Debug log all file paths to verify they're correct
    file_put_contents($logFile, "Files to process:\n", FILE_APPEND);
    foreach ($filesToProcess as $index => $fileInfo) {
        $exists = file_exists($fileInfo['path']) ? 'yes' : 'no';
        file_put_contents($logFile, "File $index: {$fileInfo['path']} (exists: $exists)\n", FILE_APPEND);
        file_put_contents($debugLog, "File $index: {$fileInfo['path']} (exists: $exists)\n", FILE_APPEND);
    }
    
    // Process each file
    $dziFiles = [];
    $processedFiles = [];
    
    foreach ($filesToProcess as $fileInfo) {
        $filePath = $fileInfo['path'];
        $fileTitle = $fileInfo['title'];
        
        // Check if file exists
        if (!file_exists($filePath)) {
            file_put_contents($logFile, "Warning: File not found: $filePath\n", FILE_APPEND);
            continue;
        }
        
        // Process the file
        file_put_contents($logFile, "Processing file: $filePath\n", FILE_APPEND);
        file_put_contents($debugLog, "Processing file: $filePath\n", FILE_APPEND);
        
        // Handle TIF files specially - some systems may have issues with them
        $convertOptions = [
            'tileSize' => $options['tileSize'],
            'overlap' => $options['overlap'],
            'quality' => $options['quality'],
            'debug' => true // Enable debug for all files to help troubleshoot
        ];
        
        // Add special handling for TIF files
        if (pathinfo($filePath, PATHINFO_EXTENSION) === 'tif' || 
            pathinfo($filePath, PATHINFO_EXTENSION) === 'tiff') {
            file_put_contents($logFile, "Special handling for TIF file: $filePath\n", FILE_APPEND);
        }
        
        $result = convertToDZI($filePath, $workingDir, $convertOptions);
        
        if ($result['status'] === 'success') {
            $dziFiles[] = $result['dziPath'];
            $processedFiles[] = [
                'title' => $fileTitle,
                'dziPath' => $result['dziPath'],
                'viewerPath' => $result['viewerPath']
            ];
            file_put_contents($logFile, "Successfully processed: $filePath\n", FILE_APPEND);
            file_put_contents($debugLog, "Successfully processed: $filePath\n", FILE_APPEND);
            file_put_contents($debugLog, "DZI path: {$result['dziPath']}\n", FILE_APPEND);
        } else {
            file_put_contents($logFile, "Failed to process: $filePath\n", FILE_APPEND);
            file_put_contents($logFile, "Error: " . ($result['error'] ?? 'Unknown error') . "\n", FILE_APPEND);
            file_put_contents($debugLog, "Failed to process: $filePath\n", FILE_APPEND);
            file_put_contents($debugLog, "Error: " . ($result['error'] ?? 'Unknown error') . "\n", FILE_APPEND);
        }
    }
    
    // Check if any DZI files were found in the output directory
    if (count($dziFiles) === 0) {
        // Search for any DZI files in the output directory as fallback
        $foundDziFiles = glob($workingDir . '/*/*.dzi');
        if (!empty($foundDziFiles)) {
            file_put_contents($debugLog, "No files were successfully processed to DZI format, but found existing DZI files:\n", FILE_APPEND);
            foreach ($foundDziFiles as $dziFile) {
                $dziFiles[] = $dziFile;
                $fileName = basename(dirname($dziFile));
                $processedFiles[] = [
                    'title' => "Found DZI: $fileName",
                    'dziPath' => $dziFile,
                    'viewerPath' => dirname($dziFile) . "/$fileName.html"
                ];
                file_put_contents($debugLog, "Found DZI: $dziFile\n", FILE_APPEND);
            }
        }
    }
    
    // Create the multizoom view if we have processed files
    if (count($dziFiles) > 0) {
        // Create multizoom directory
        $multizoomDir = $workingDir . '/multizoom';
        if (!file_exists($multizoomDir)) {
            mkdir($multizoomDir, 0755, true);
        }
        
        // Create the multizoom view
        $multizoomTitle = $options['multizoomTitle'] ?? $outputBaseName . ' - Slide Analysis';
        $multizoomResult = createMultizoom($dziFiles, $multizoomDir, [
            'title' => $multizoomTitle,
            'syncViews' => true
        ]);
        
        // Generate the access URL
        $multizoomUrl = "output/$outputBaseName/multizoom/index.html";
        $fullUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000') . "/$multizoomUrl";
        
        // Save result information
        $resultInfo = [
            'slideName' => $outputBaseName,
            'processedFiles' => $processedFiles,
            'multizoomPath' => $multizoomResult['htmlPath'],
            'multizoomUrl' => $multizoomUrl,
            'fullUrl' => $fullUrl,
            'completedAt' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($workingDir . '/result.json', json_encode($resultInfo, JSON_PRETTY_PRINT));
        file_put_contents($logFile, "Multizoom created successfully at: $multizoomDir\n", FILE_APPEND);
        file_put_contents($logFile, "Access URL: $fullUrl\n", FILE_APPEND);
        file_put_contents($debugLog, "Multizoom created successfully at: $multizoomDir\n", FILE_APPEND);
        
        return $resultInfo;
    } else {
        $errorMsg = "No files were successfully processed to DZI format.";
        file_put_contents($logFile, "Error: $errorMsg\n", FILE_APPEND);
        file_put_contents($debugLog, "Error: $errorMsg\n", FILE_APPEND);
        
        return [
            'status' => 'error',
            'error' => $errorMsg,
            'slideName' => $outputBaseName,
            'completedAt' => date('Y-m-d H:i:s')
        ];
    }
}
?>