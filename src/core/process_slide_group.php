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
        'multizoomTitle' => 'Multi-Zoom View'
    ];
    
    $options = array_merge($defaults, $options);
    
    // Create output directory if it doesn't exist
    if (!file_exists($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    // Get base name of the input folder
    $baseFolderName = basename($inputFolder);
    
    // Define working directory for output
    $workingDir = $outputDir . '/' . $baseFolderName;
    if (!file_exists($workingDir)) {
        mkdir($workingDir, 0755, true);
    }
    
    // Start logging
    $logFile = $workingDir . '/process.log';
    file_put_contents($logFile, "Processing slide group from folder: $inputFolder\n");
    file_put_contents($logFile, "Started: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    
    // Define the files we need to process
    $filesToProcess = [
        [
            'path' => $inputFolder . '/' . $baseFolderName,
            'title' => 'Raw Image (' . $baseFolderName . ')'
        ],
        [
            'path' => $inputFolder . '/4_5_stitch_output_segformer/' . $baseFolderName . '_classification_stitched.tif',
            'title' => 'Cell Classification'
        ],
        [
            'path' => $inputFolder . '/6_tme_seg/mask_ss1_512_postprocessed/' . $baseFolderName . '_Ss1.png',
            'title' => 'TMESeg'
        ],
        [
            'path' => $inputFolder . '/0_autoqc/maps_qc/' . $baseFolderName . '_map_QC.png',
            'title' => 'QutoQC'
        ]
    ];
    
    // Debug log all file paths to verify they're correct
    file_put_contents($logFile, "Files to process:\n", FILE_APPEND);
    foreach ($filesToProcess as $index => $fileInfo) {
        file_put_contents($logFile, "File $index: {$fileInfo['path']} (exists: " . (file_exists($fileInfo['path']) ? 'yes' : 'no') . ")\n", FILE_APPEND);
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
        
        // Handle TIF files specially - some systems may have issues with them
        $convertOptions = [
            'tileSize' => $options['tileSize'],
            'overlap' => $options['overlap'],
            'quality' => $options['quality']
        ];
        
        // Add debug flag for troubleshooting
        if (pathinfo($filePath, PATHINFO_EXTENSION) === 'tif' || 
            pathinfo($filePath, PATHINFO_EXTENSION) === 'tiff') {
            file_put_contents($logFile, "Special handling for TIF file: $filePath\n", FILE_APPEND);
            $convertOptions['debug'] = true;
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
        } else {
            file_put_contents($logFile, "Failed to process: $filePath\n", FILE_APPEND);
            file_put_contents($logFile, "Error: " . ($result['error'] ?? 'Unknown error') . "\n", FILE_APPEND);
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
        $multizoomTitle = $options['multizoomTitle'] ?? $baseFolderName . ' - Slide Analysis';
        $multizoomResult = createMultizoom($dziFiles, $multizoomDir, [
            'title' => $multizoomTitle,
            'syncViews' => true
        ]);
        
        // Generate the access URL
        $multizoomUrl = "output/$baseFolderName/multizoom/index.html";
        $fullUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000') . "/$multizoomUrl";
        
        // Save result information
        $resultInfo = [
            'slideName' => $baseFolderName,
            'processedFiles' => $processedFiles,
            'multizoomPath' => $multizoomResult['htmlPath'],
            'multizoomUrl' => $multizoomUrl,
            'fullUrl' => $fullUrl,
            'completedAt' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($workingDir . '/result.json', json_encode($resultInfo, JSON_PRETTY_PRINT));
        file_put_contents($logFile, "Multizoom created successfully at: $multizoomDir\n", FILE_APPEND);
        file_put_contents($logFile, "Access URL: $fullUrl\n", FILE_APPEND);
        
        return $resultInfo;
    } else {
        $errorMsg = "No files were successfully processed to DZI format.";
        file_put_contents($logFile, "Error: $errorMsg\n", FILE_APPEND);
        
        return [
            'status' => 'error',
            'error' => $errorMsg,
            'slideName' => $baseFolderName,
            'completedAt' => date('Y-m-d H:i:s')
        ];
    }
}
?>