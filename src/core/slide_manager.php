<?php
/**
 * Class SlideManager
 * 
 * A comprehensive class to manage all slide-related operations including:
 * - Listing available slides (input and processed)
 * - Processing slides (converting to DZI)
 * - Creating and managing collections of slides
 * - Managing multi-zoom views
 */
class SlideManager {
    private $inputDir;
    private $outputDir;
    private $supportedFormats = ['svs', 'tif', 'tiff', 'ndpi', 'scn', 'bif'];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->inputDir = defined('INPUT_DIR') ? INPUT_DIR : dirname(dirname(__DIR__)) . '/input';
        $this->outputDir = defined('OUTPUT_DIR') ? OUTPUT_DIR : __DIR__ . '/../../www/output';
    }
    
    /**
     * Get all input files
     * 
     * @param bool $filterBySupported Only return supported file types
     * @return array List of input files with metadata
     */
    public function getInputFiles($filterBySupported = true) {
        $files = glob($this->inputDir . '/*.*');
        $result = [];
        
        foreach ($files as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            // Skip unsupported formats if filtering is enabled
            if ($filterBySupported && !in_array($extension, $this->supportedFormats)) {
                continue;
            }
            
            $result[] = [
                'path' => $file,
                'filename' => basename($file),
                'size' => filesize($file),
                'formatted_size' => $this->formatFileSize(filesize($file)),
                'type' => strtoupper($extension),
                'modified' => filemtime($file),
                'formatted_date' => date("Y-m-d H:i:s", filemtime($file))
            ];
        }
        
        // Sort files by most recently modified
        usort($result, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        
        return $result;
    }
    
    /**
     * Get recent input files
     * 
     * @param int $limit Number of files to return
     * @return array List of recent input files
     */
    public function getRecentInputFiles($limit = 5) {
        $files = $this->getInputFiles();
        return array_slice($files, 0, $limit);
    }
    
    /**
     * Get all processed slides
     * 
     * @return array List of processed slides with metadata
     */
    public function getProcessedSlides() {
        $processedDirs = glob($this->outputDir . '/*', GLOB_ONLYDIR);
        $result = [];
        
        foreach ($processedDirs as $dir) {
            if (basename($dir) === 'multizoom') continue; // Skip multizoom directory
            
            $dirname = basename($dir);
            $statusFile = "$dir/status.json";
            $status = "unknown";
            $viewerPath = "";
            $processingTime = "N/A";
            $dateProcessed = null;
            
            if (file_exists($statusFile)) {
                $statusData = json_decode(file_get_contents($statusFile), true);
                $status = $statusData['status'] ?? 'unknown';
                $viewerPath = $statusData['viewerPath'] ?? '';
                $processingTime = $statusData['processingTime'] ?? 'N/A';
                
                if (isset($statusData['endTime'])) {
                    $dateProcessed = (int)$statusData['endTime'];
                }
            }
            
            $logFile = "$dir/process.log";
            $hasLog = file_exists($logFile);
            
            $result[] = [
                'name' => $dirname,
                'path' => $dir,
                'status' => $status,
                'viewerPath' => $viewerPath,
                'processingTime' => $processingTime,
                'dateProcessed' => $dateProcessed,
                'formatted_date' => $dateProcessed ? date("Y-m-d H:i:s", $dateProcessed) : 'N/A',
                'hasLog' => $hasLog,
                'logPath' => $hasLog ? $logFile : '',
                'thumbnailPath' => "$dir/thumbnail.jpg"
            ];
        }
        
        // Sort slides by processing date (most recent first)
        usort($result, function($a, $b) {
            if ($a['dateProcessed'] === null && $b['dateProcessed'] === null) return 0;
            if ($a['dateProcessed'] === null) return 1;
            if ($b['dateProcessed'] === null) return -1;
            return $b['dateProcessed'] - $a['dateProcessed'];
        });
        
        return $result;
    }
    
    /**
     * Get recent processed slides
     * 
     * @param int $limit Number of slides to return
     * @return array List of recent processed slides
     */
    public function getRecentProcessedSlides($limit = 5) {
        $slides = $this->getProcessedSlides();
        return array_slice($slides, 0, $limit);
    }
    
    /**
     * Get all slides (both input and processed)
     * 
     * @return array Combined list of all slides
     */
    public function getAllSlides() {
        $inputFiles = $this->getInputFiles();
        $processedSlides = $this->getProcessedSlides();
        
        // Add type indicator to each array
        foreach ($inputFiles as &$file) {
            $file['type'] = 'input';
        }
        
        foreach ($processedSlides as &$slide) {
            $slide['type'] = 'processed';
        }
        
        // Combine arrays
        return [
            'input' => $inputFiles,
            'processed' => $processedSlides
        ];
    }
    
    /**
     * Get all multi-zoom views
     * 
     * @return array List of multi-zoom views
     */
    public function getMultizoomViews() {
        $multizoomDir = $this->outputDir . '/multizoom';
        $result = [];
        
        if (is_dir($multizoomDir)) {
            $multizoomFiles = glob($multizoomDir . '/*.html');
            
            foreach ($multizoomFiles as $file) {
                $result[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'created' => filemtime($file),
                    'formatted_date' => date("Y-m-d H:i:s", filemtime($file)),
                    'relativePath' => str_replace($this->outputDir . '/', '', $file)
                ];
            }
        }
        
        // Sort by creation date (newest first)
        usort($result, function($a, $b) {
            return $b['created'] - $a['created'];
        });
        
        return $result;
    }
    
    /**
     * Get system statistics
     * 
     * @return array System statistics
     */
    public function getStats() {
        $inputFiles = $this->getInputFiles(false);
        $processedSlides = $this->getProcessedSlides();
        $multizoomViews = $this->getMultizoomViews();
        
        $successfulSlides = array_filter($processedSlides, function($slide) {
            return $slide['status'] === 'success';
        });
        
        $errorSlides = array_filter($processedSlides, function($slide) {
            return $slide['status'] === 'error';
        });
        
        return [
            'total_slides' => count($inputFiles),
            'processed_slides' => count($successfulSlides),
            'error_slides' => count($errorSlides),
            'multizoom_views' => count($multizoomViews)
        ];
    }
    
    /**
     * Process a slide file
     * 
     * @param string $filePath Path to the slide file
     * @return array Process result
     */
    public function processSlide($filePath) {
        // Include required processing function
        require_once dirname(__FILE__) . '/dzi_generator.php';
        
        try {
            // Process the file
            $result = convertToDZI($filePath, $this->outputDir);
            return [
                'status' => 'success',
                'result' => $result
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create a multi-zoom view from selected slides
     * 
     * @param array $slideIds IDs (names) of slides to include
     * @param string $viewName Optional name for the view
     * @return array Result of operation
     */
    public function createMultizoomView($slideIds, $viewName = '') {
        // Include required multizoom function
        require_once dirname(__FILE__) . '/multizoom.php';
        
        try {
            $dziFiles = [];
            foreach ($slideIds as $slideId) {
                $slideDir = $this->outputDir . '/' . $slideId;
                $dziFile = glob($slideDir . '/*_deepzoom.dzi');
                if (!empty($dziFile)) {
                    $dziFiles[] = $dziFile[0];
                }
            }
            
            if (empty($dziFiles)) {
                return [
                    'status' => 'error',
                    'message' => 'No DZI files found for the selected slides'
                ];
            }
            
            // Create multizoom directory if it doesn't exist
            $multizoomDir = $this->outputDir . '/multizoom';
            if (!is_dir($multizoomDir)) {
                mkdir($multizoomDir, 0755, true);
            }
            
            // Generate a default view name if not provided
            if (empty($viewName)) {
                $viewName = 'multizoom_' . date('Ymd_His');
            }
            
            // Create multizoom view
            $result = createMultizoom($dziFiles, $multizoomDir, $viewName);
            
            return [
                'status' => 'success',
                'result' => $result
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete a processed slide
     * 
     * @param string $slideName Name of the slide to delete
     * @return bool Success or failure
     */
    public function deleteProcessedSlide($slideName) {
        $slideDir = $this->outputDir . '/' . $slideName;
        
        if (!is_dir($slideDir)) {
            return false;
        }
        
        // Delete the directory recursively
        $this->deleteDirectory($slideDir);
        
        return true;
    }
    
    /**
     * Delete a multi-zoom view
     * 
     * @param string $viewName Name of the view to delete
     * @return bool Success or failure
     */
    public function deleteMultizoomView($viewName) {
        $viewPath = $this->outputDir . '/multizoom/' . $viewName;
        
        if (!file_exists($viewPath)) {
            return false;
        }
        
        return unlink($viewPath);
    }
    
    /**
     * Add tags or labels to a slide
     * 
     * @param string $slideName Name of the slide
     * @param array $tags Tags to add
     * @return bool Success or failure
     */
    public function addSlideTags($slideName, $tags) {
        $slideDir = $this->outputDir . '/' . $slideName;
        $metadataFile = $slideDir . '/metadata.json';
        
        $metadata = [];
        if (file_exists($metadataFile)) {
            $metadata = json_decode(file_get_contents($metadataFile), true);
        }
        
        $metadata['tags'] = array_unique(array_merge($metadata['tags'] ?? [], $tags));
        
        return file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT)) !== false;
    }
    
    /**
     * Get slide metadata
     * 
     * @param string $slideName Name of the slide
     * @return array Slide metadata
     */
    public function getSlideMetadata($slideName) {
        $slideDir = $this->outputDir . '/' . $slideName;
        $metadataFile = $slideDir . '/metadata.json';
        
        if (file_exists($metadataFile)) {
            return json_decode(file_get_contents($metadataFile), true);
        }
        
        return [];
    }
    
    /**
     * Format file size for display
     * 
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Recursively delete a directory
     * 
     * @param string $dir Directory to delete
     * @return bool Success or failure
     */
    private function deleteDirectory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
}