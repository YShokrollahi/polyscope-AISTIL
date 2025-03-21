<?php
/**
 * API Endpoint: View Process Log
 * 
 * Returns the content of a process log file.
 */

// Get log path from query parameter
$logPath = isset($_GET['log']) ? $_GET['log'] : null;

if (!$logPath) {
    header('Content-Type: text/plain');
    echo 'No log file specified';
    exit;
}

// Resolve full path
$fullPath = __DIR__ . '/../../' . $logPath;

// Security check - ensure path is within output directory
$outputDir = realpath(__DIR__ . '/../../output');
$realLogPath = realpath($fullPath);

if (!$realLogPath || strpos($realLogPath, $outputDir) !== 0) {
    header('Content-Type: text/plain');
    echo 'Invalid log file path';
    exit;
}

// Check if file exists
if (!file_exists($fullPath)) {
    header('Content-Type: text/plain');
    echo 'Log file not found';
    exit;
}

// Set content type
header('Content-Type: text/plain');

// Output file contents
readfile($fullPath);