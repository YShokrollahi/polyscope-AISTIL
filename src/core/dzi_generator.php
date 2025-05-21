<?php
/**
 * DZI Generator
 * Converts slides to Deep Zoom Image format using vips
 * Enhanced to handle problematic classification_stitched.tif files
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
        'quality' => 90,
        'debug' => true, // Set to true by default for better diagnostics
        'memory_limit' => 2000 // Increased memory limit for large files
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
    
    // If debug mode is enabled, log additional information
    if ($options['debug']) {
        file_put_contents($logFile, "Debug mode enabled\n", FILE_APPEND);
        file_put_contents($logFile, "File exists: " . (file_exists($inputFile) ? 'yes' : 'no') . "\n", FILE_APPEND);
        file_put_contents($logFile, "File size: " . (file_exists($inputFile) ? filesize($inputFile) . ' bytes' : 'N/A') . "\n", FILE_APPEND);
        file_put_contents($logFile, "File type: " . $extension . "\n", FILE_APPEND);
        
        // Check file details with tiffinfo for TIFF files if available
        if (strtolower($extension) === 'tif' || strtolower($extension) === 'tiff') {
            exec("tiffinfo \"$inputFile\" 2>&1", $tiffInfoOutput, $tiffInfoReturnCode);
            if ($tiffInfoReturnCode === 0) {
                file_put_contents($logFile, "TIFF Info: " . implode("\n", $tiffInfoOutput) . "\n", FILE_APPEND);
            } else {
                file_put_contents($logFile, "TIFF Info failed: " . implode("\n", $tiffInfoOutput) . "\n", FILE_APPEND);
            }
        }
    }
    
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
        createErrorViewer($workingDir, $baseFilename, $errorMsg);
        
        return $statusData;
    }
    
    // Log versions for debugging
    if ($options['debug']) {
        // Check vips version
        exec("vips --version", $vipsVersionOutput, $vipsVersionReturnCode);
        if ($vipsVersionReturnCode === 0) {
            file_put_contents($logFile, "VIPS Version: " . implode("\n", $vipsVersionOutput) . "\n", FILE_APPEND);
        }
        
        // Check vips lib features
        exec("vips --versionlib", $vipsLibOutput, $vipsLibReturnCode);
        if ($vipsLibReturnCode === 0) {
            file_put_contents($logFile, "VIPS Library Features: " . implode("\n", $vipsLibOutput) . "\n", FILE_APPEND);
        }
        
        // Check ImageMagick version if available
        exec("convert --version", $magickVersionOutput, $magickVersionReturnCode);
        if ($magickVersionReturnCode === 0) {
            file_put_contents($logFile, "ImageMagick Version: " . implode("\n", $magickVersionOutput) . "\n", FILE_APPEND);
        }
    }
    
    // For classification_stitched files, check if ImageMagick is installed
    $isClassificationFile = (strpos($filename, 'classification_qc_stitched') !== false);
    
    // Special handling for different file types
    $vipsCommand = "";
    $startTime = microtime(true);
    $conversionSuccess = false;
    
    // For TIFF files, try specialized approaches
    if (strtolower($extension) === 'tif' || strtolower($extension) === 'tiff') {
        file_put_contents($logFile, "Using special handling for TIFF file\n", FILE_APPEND);
        
        // Special handling for classification stitched files
        if ($isClassificationFile) {
            file_put_contents($logFile, "Detected classification_qc_stitched file\n", FILE_APPEND);
            
            // Try using direct ImageMagick convert first (bypassing the size limit issue)
            $tempPngFile = "$workingDir/{$baseFilename}_direct.png";
            
            // Try directly with ImageMagick convert command with resource limits
            $directConvertCommand = "convert -limit memory 2GiB -limit map 4GiB -limit width 128KP -limit height 128KP \"$inputFile\" \"$tempPngFile\" 2>> \"$logFile\"";
            
            file_put_contents($logFile, "Trying direct ImageMagick conversion: $directConvertCommand\n", FILE_APPEND);
            $startDirectConvert = microtime(true);
            exec($directConvertCommand, $directConvertOutput, $directConvertReturnCode);
            $endDirectConvert = microtime(true);
            file_put_contents($logFile, "Direct conversion time: " . round($endDirectConvert - $startDirectConvert, 2) . " seconds\n", FILE_APPEND);
            
            if ($directConvertReturnCode === 0 && file_exists($tempPngFile)) {
                file_put_contents($logFile, "Successfully converted TIFF using direct ImageMagick\n", FILE_APPEND);
                
                // Now create DZI from the PNG
                $vipsCommand = "vips --vips-concurrency=1 --vips-cache-max={$options['memory_limit']} dzsave \"$tempPngFile\" \"$workingDir/{$baseFilename}\" " .
                              "--tile-size={$options['tileSize']} " .
                              "--overlap={$options['overlap']} " .
                              "--suffix=.jpg[Q={$options['quality']}] " .
                              "2>> \"$logFile\"";
                
                file_put_contents($logFile, "Creating DZI from converted PNG: $vipsCommand\n", FILE_APPEND);
                $startDziCreate = microtime(true);
                exec($vipsCommand, $output, $returnCode);
                $endDziCreate = microtime(true);
                file_put_contents($logFile, "DZI creation time: " . round($endDziCreate - $startDziCreate, 2) . " seconds\n", FILE_APPEND);
                
                if ($returnCode === 0) {
                    $conversionSuccess = true;
                    
                    // Clean up temporary files
                    if (file_exists($tempPngFile)) {
                        unlink($tempPngFile);
                    }
                }
            } else {
                file_put_contents($logFile, "Direct ImageMagick conversion failed with code $directConvertReturnCode\n", FILE_APPEND);
                
                // Try with magickload using explicit flags
                $tempVFile = "$workingDir/{$baseFilename}_temp.v";
                $tempPngFile = "$workingDir/{$baseFilename}_temp.png";
                
                // Create an override policy file
                $tempPolicyDir = "$workingDir/policy";
                if (!file_exists($tempPolicyDir)) {
                    mkdir($tempPolicyDir, 0755, true);
                }
                
                $policyFile = "$tempPolicyDir/policy.xml";
                $policyContent = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE policymap [
<!ELEMENT policymap (policy)+>
<!ELEMENT policy EMPTY>
<!ATTLIST policy domain (resource) #REQUIRED>
<!ATTLIST policy name CDATA #REQUIRED>
<!ATTLIST policy value CDATA #REQUIRED>
]>
<policymap>
  <policy domain="resource" name="memory" value="2GiB"/>
  <policy domain="resource" name="map" value="4GiB"/>
  <policy domain="resource" name="width" value="128KP"/>
  <policy domain="resource" name="height" value="128KP"/>
  <policy domain="resource" name="area" value="2GiB"/>
  <policy domain="resource" name="disk" value="8GiB"/>
</policymap>';
                
                file_put_contents($policyFile, $policyContent);
                file_put_contents($logFile, "Created temporary ImageMagick policy file at $policyFile\n", FILE_APPEND);
                
                // Try magickload with the custom policy file
                $magickLoadCommand = "MAGICK_CONFIGURE_PATH=$tempPolicyDir vips magickload \"$inputFile\" \"$tempVFile\" 2>> \"$logFile\" && " .
                                   "vips copy \"$tempVFile\" \"$tempPngFile\" 2>> \"$logFile\"";
                
                file_put_contents($logFile, "Using magickload with custom policy: $magickLoadCommand\n", FILE_APPEND);
                $startMagickLoad = microtime(true);
                exec($magickLoadCommand, $magickLoadOutput, $magickLoadReturnCode);
                $endMagickLoad = microtime(true);
                file_put_contents($logFile, "Magickload time: " . round($endMagickLoad - $startMagickLoad, 2) . " seconds\n", FILE_APPEND);
                
                if ($magickLoadReturnCode === 0 && file_exists($tempPngFile)) {
                    file_put_contents($logFile, "Successfully converted TIFF using magickload with custom policy\n", FILE_APPEND);
                    
                    // Now create DZI from the PNG
                    $vipsCommand = "vips --vips-concurrency=1 --vips-cache-max={$options['memory_limit']} dzsave \"$tempPngFile\" \"$workingDir/{$baseFilename}\" " .
                                  "--tile-size={$options['tileSize']} " .
                                  "--overlap={$options['overlap']} " .
                                  "--suffix=.jpg[Q={$options['quality']}] " .
                                  "2>> \"$logFile\"";
                    
                    file_put_contents($logFile, "Creating DZI from converted PNG: $vipsCommand\n", FILE_APPEND);
                    $startDziCreate = microtime(true);
                    exec($vipsCommand, $output, $returnCode);
                    $endDziCreate = microtime(true);
                    file_put_contents($logFile, "DZI creation time: " . round($endDziCreate - $startDziCreate, 2) . " seconds\n", FILE_APPEND);
                    
                    if ($returnCode === 0) {
                        $conversionSuccess = true;
                        
                        // Clean up temporary files
                        if (file_exists($tempVFile)) {
                            unlink($tempVFile);
                        }
                        if (file_exists($tempPngFile)) {
                            unlink($tempPngFile);
                        }
                    }
                } else {
                    file_put_contents($logFile, "Magickload with custom policy failed with code $magickLoadReturnCode\n", FILE_APPEND);
                
                    // Try GDAL if available
                    exec('which gdal_translate', $gdalOutput, $gdalReturnCode);
                    
                    if ($gdalReturnCode === 0) {
                        file_put_contents($logFile, "Trying GDAL conversion approach\n", FILE_APPEND);
                        
                        $tempPngFile = "$workingDir/{$baseFilename}_gdal.png";
                        $gdalCommand = "gdal_translate -of PNG \"$inputFile\" \"$tempPngFile\" 2>> \"$logFile\"";
                        
                        file_put_contents($logFile, "GDAL command: $gdalCommand\n", FILE_APPEND);
                        $startGdalConvert = microtime(true);
                        exec($gdalCommand, $gdalOutput, $gdalReturnCode);
                        $endGdalConvert = microtime(true);
                        file_put_contents($logFile, "GDAL conversion time: " . round($endGdalConvert - $startGdalConvert, 2) . " seconds\n", FILE_APPEND);
                        
                        if ($gdalReturnCode === 0 && file_exists($tempPngFile)) {
                            file_put_contents($logFile, "GDAL conversion successful\n", FILE_APPEND);
                            
                            // Create DZI from the GDAL-converted PNG
                            $vipsCommand = "vips --vips-concurrency=1 --vips-cache-max={$options['memory_limit']} dzsave \"$tempPngFile\" \"$workingDir/{$baseFilename}\" " .
                                          "--tile-size={$options['tileSize']} " .
                                          "--overlap={$options['overlap']} " .
                                          "--suffix=.jpg[Q={$options['quality']}] " .
                                          "2>> \"$logFile\"";
                            
                            file_put_contents($logFile, "Creating DZI from GDAL-converted PNG: $vipsCommand\n", FILE_APPEND);
                            $startDziCreate = microtime(true);
                            exec($vipsCommand, $output, $returnCode);
                            $endDziCreate = microtime(true);
                            file_put_contents($logFile, "DZI creation time: " . round($endDziCreate - $startDziCreate, 2) . " seconds\n", FILE_APPEND);
                            
                            if ($returnCode === 0) {
                                $conversionSuccess = true;
                                
                                // Clean up
                                if (file_exists($tempPngFile)) {
                                    unlink($tempPngFile);
                                }
                            }
                        }
                    }
                }
            }
            
            // If all specialized approaches failed, try downscaling first
            if (!$conversionSuccess) {
                file_put_contents($logFile, "All specialized approaches failed. Trying to downscale first.\n", FILE_APPEND);
                
                // Try with ImageMagick to create a smaller version
                $tempScaledFile = "$workingDir/{$baseFilename}_scaled.png";
                $scaleCommand = "convert -resize 25% \"$inputFile\" \"$tempScaledFile\" 2>> \"$logFile\"";
                
                file_put_contents($logFile, "Scaling down image: $scaleCommand\n", FILE_APPEND);
                $startScaleConvert = microtime(true);
                exec($scaleCommand, $scaleOutput, $scaleReturnCode);
                $endScaleConvert = microtime(true);
                file_put_contents($logFile, "Scale conversion time: " . round($endScaleConvert - $startScaleConvert, 2) . " seconds\n", FILE_APPEND);
                
                if ($scaleReturnCode === 0 && file_exists($tempScaledFile)) {
                    file_put_contents($logFile, "Successfully scaled down TIFF\n", FILE_APPEND);
                    
                    // Now create DZI from the scaled PNG
                    $vipsCommand = "vips --vips-concurrency=1 --vips-cache-max={$options['memory_limit']} dzsave \"$tempScaledFile\" \"$workingDir/{$baseFilename}\" " .
                                  "--tile-size={$options['tileSize']} " .
                                  "--overlap={$options['overlap']} " .
                                  "--suffix=.jpg[Q={$options['quality']}] " .
                                  "2>> \"$logFile\"";
                    
                    file_put_contents($logFile, "Creating DZI from scaled PNG: $vipsCommand\n", FILE_APPEND);
                    $startDziCreate = microtime(true);
                    exec($vipsCommand, $output, $returnCode);
                    $endDziCreate = microtime(true);
                    file_put_contents($logFile, "DZI creation time: " . round($endDziCreate - $startDziCreate, 2) . " seconds\n", FILE_APPEND);
                    
                    if ($returnCode === 0) {
                        $conversionSuccess = true;
                        
                        // Log a note that this is a scaled version
                        file_put_contents($logFile, "NOTE: The DZI was created from a scaled-down version of the original file.\n", FILE_APPEND);
                        
                        // Clean up temporary files
                        if (file_exists($tempScaledFile)) {
                            unlink($tempScaledFile);
                        }
                    }
                }
            }
            
            // If all specialized approaches failed, try standard methods as a last resort
            if (!$conversionSuccess) {
                file_put_contents($logFile, "All specialized approaches failed. Trying standard TIFF handling as last resort.\n", FILE_APPEND);
                
                // Use standard TIFF approaches below
                $tryStandardApproaches = true;
            } else {
                $tryStandardApproaches = false;
            }
        } else {
            // Not a classification file, use standard TIFF handling
            $tryStandardApproaches = true;
        }
        
        // Standard TIFF handling if needed
        if (isset($tryStandardApproaches) && $tryStandardApproaches) {
            // First try with direct dzsave
            $vipsCommand = "vips --vips-concurrency=1 --vips-cache-max={$options['memory_limit']} dzsave \"$inputFile\" \"$workingDir/{$baseFilename}\" " .
                          "--tile-size={$options['tileSize']} " .
                          "--overlap={$options['overlap']} " .
                          "--suffix=.jpg[Q={$options['quality']}] " .
                          "2>> \"$logFile\"";
            
            file_put_contents($logFile, "First attempt command: $vipsCommand\n", FILE_APPEND);
            $startFirstAttempt = microtime(true);
            exec($vipsCommand, $output, $returnCode);
            $endFirstAttempt = microtime(true);
            file_put_contents($logFile, "First attempt time: " . round($endFirstAttempt - $startFirstAttempt, 2) . " seconds\n", FILE_APPEND);
            
            // If successful, set flag
            if ($returnCode === 0) {
                $conversionSuccess = true;
            } else {
                // If first approach fails, try converting to PNG first
                file_put_contents($logFile, "First attempt failed with code $returnCode. Trying alternative approach...\n", FILE_APPEND);
                
                // Create a temporary PNG file
                $tempPngFile = "$workingDir/{$baseFilename}_temp.png";
                $convertCommand = "vips copy \"$inputFile\" \"$tempPngFile\" 2>> \"$logFile\"";
                
                file_put_contents($logFile, "Converting to PNG first: $convertCommand\n", FILE_APPEND);
                $startPngConvert = microtime(true);
                exec($convertCommand, $convertOutput, $convertReturnCode);
                $endPngConvert = microtime(true);
                file_put_contents($logFile, "PNG conversion time: " . round($endPngConvert - $startPngConvert, 2) . " seconds\n", FILE_APPEND);
                
                if ($convertReturnCode === 0 && file_exists($tempPngFile)) {
                    // Now try to create DZI from the PNG
                    $vipsCommand = "vips --vips-concurrency=1 --vips-cache-max={$options['memory_limit']} dzsave \"$tempPngFile\" \"$workingDir/{$baseFilename}\" " .
                                  "--tile-size={$options['tileSize']} " .
                                  "--overlap={$options['overlap']} " .
                                  "--suffix=.jpg[Q={$options['quality']}] " .
                                  "2>> \"$logFile\"";
                    
                    file_put_contents($logFile, "Second attempt command: $vipsCommand\n", FILE_APPEND);
                    $startSecondAttempt = microtime(true);
                    exec($vipsCommand, $output, $returnCode);
                    $endSecondAttempt = microtime(true);
                    file_put_contents($logFile, "Second attempt time: " . round($endSecondAttempt - $startSecondAttempt, 2) . " seconds\n", FILE_APPEND);
                    
                    // If successful, set flag
                    if ($returnCode === 0) {
                        $conversionSuccess = true;
                    }
                    
                    // Clean up temporary file
                    if (file_exists($tempPngFile)) {
                        unlink($tempPngFile);
                    }
                }
                
                // If both standard approaches failed for a non-classification file, try magickload as a last resort
                if (!$conversionSuccess && !$isClassificationFile) {
                    file_put_contents($logFile, "Standard approaches failed. Trying magickload as last resort for this TIFF.\n", FILE_APPEND);
                    
                    // Create temporary files
                    $tempVFile = "$workingDir/{$baseFilename}_temp.v";
                    $tempPngFile = "$workingDir/{$baseFilename}_temp.png";
                    
                    // Try magickload
                    $startMagickLoad = microtime(true);
                    $convertCommand = "vips magickload \"$inputFile\" \"$tempVFile\" 2>> \"$logFile\" && " .
                                      "vips copy \"$tempVFile\" \"$tempPngFile\" 2>> \"$logFile\"";
                    
                    file_put_contents($logFile, "Using magickload approach as fallback: $convertCommand\n", FILE_APPEND);
                    exec($convertCommand, $convertOutput, $convertReturnCode);
                    $endMagickLoad = microtime(true);
                    file_put_contents($logFile, "Magickload time: " . round($endMagickLoad - $startMagickLoad, 2) . " seconds\n", FILE_APPEND);
                    
                    if ($convertReturnCode === 0 && file_exists($tempPngFile)) {
                        // Now create DZI from the PNG
                        $vipsCommand = "vips --vips-concurrency=1 --vips-cache-max={$options['memory_limit']} dzsave \"$tempPngFile\" \"$workingDir/{$baseFilename}\" " .
                                      "--tile-size={$options['tileSize']} " .
                                      "--overlap={$options['overlap']} " .
                                      "--suffix=.jpg[Q={$options['quality']}] " .
                                      "2>> \"$logFile\"";
                        
                        file_put_contents($logFile, "Creating DZI from magickload PNG: $vipsCommand\n", FILE_APPEND);
                        $startDziCreate = microtime(true);
                        exec($vipsCommand, $output, $returnCode);
                        $endDziCreate = microtime(true);
                        file_put_contents($logFile, "DZI creation time: " . round($endDziCreate - $startDziCreate, 2) . " seconds\n", FILE_APPEND);
                        
                        // If successful, set flag
                        if ($returnCode === 0) {
                            $conversionSuccess = true;
                        }
                        
                        // Clean up temporary files
                        if (file_exists($tempVFile)) {
                            unlink($tempVFile);
                        }
                        if (file_exists($tempPngFile)) {
                            unlink($tempPngFile);
                        }
                    }
                }
            }
        }
    } else {
        // Standard approach for other file types
        $vipsCommand = "vips dzsave \"$inputFile\" \"$workingDir/{$baseFilename}\" " .
                      "--tile-size={$options['tileSize']} " .
                      "--overlap={$options['overlap']} " .
                      "--suffix=.jpg[Q={$options['quality']}] " .
                      "2>> \"$logFile\"";
        
        file_put_contents($logFile, "Standard command for non-TIFF: $vipsCommand\n", FILE_APPEND);
        exec($vipsCommand, $output, $returnCode);
        
        // Set success flag based on return code
        $conversionSuccess = ($returnCode === 0);
    }
    
    $endTime = microtime(true);
    
    // Log the output and return code
    file_put_contents($logFile, "Return code: $returnCode\n", FILE_APPEND);
    if (isset($output) && is_array($output)) {
        file_put_contents($logFile, "Output: " . implode("\n", $output) . "\n", FILE_APPEND);
    }
    
    // Check if the command was successful
    if (!$conversionSuccess || $returnCode !== 0) {
        $errorMsg = "Error: vips dzsave failed with code $returnCode. All conversion approaches failed.";
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
            <li>Missing ImageMagick installation (required for some TIFF files)</li>
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