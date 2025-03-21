<?php
/**
 * File Helper Utility
 * 
 * Provides utility functions for file manipulation.
 */

/**
 * Format file size for display
 * 
 * @param int $bytes File size in bytes
 * @param int $precision Decimal precision
 * @return string Formatted file size
 */
function formatFileSize($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Sanitize filename to make it safe for filesystem
 * 
 * @param string $filename Original filename
 * @return string Sanitized filename
 */
function sanitizeFilename($filename) {
    // Remove invalid characters
    $filename = preg_replace('/[^\w\.-]/', '_', $filename);
    
    // Remove multiple underscores
    $filename = preg_replace('/_+/', '_', $filename);
    
    return $filename;
}

/**
 * Recursively delete a directory
 * 
 * @param string $dir Directory to delete
 * @return bool Success or failure
 */
function recursiveDeleteDir($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            recursiveDeleteDir($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
}

/**
 * Create a directory if it doesn't exist
 * 
 * @param string $dir Directory path
 * @param int $mode Permissions
 * @param bool $recursive Create parent directories if needed
 * @return bool Success or failure
 */
function createDirIfNotExists($dir, $mode = 0755, $recursive = true) {
    if (is_dir($dir)) {
        return true;
    }
    
    return mkdir($dir, $mode, $recursive);
}