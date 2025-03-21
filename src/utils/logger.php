<?php
/**
 * Simple logging utility
 */

/**
 * Write a message to the log file
 * 
 * @param string $message Message to log
 * @param string $level Log level (info, warning, error)
 * @param string $logFile Path to the log file
 */
function logMessage($message, $level = 'info', $logFile = null) {
    // Default log file
    if ($logFile === null) {
        $logDir = __DIR__ . '/../../logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/app.log';
    }
    
    // Format the log message
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Write to log file
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

/**
 * Log an info message
 * 
 * @param string $message Message to log
 * @param string $logFile Path to the log file
 */
function logInfo($message, $logFile = null) {
    logMessage($message, 'info', $logFile);
}

/**
 * Log a warning message
 * 
 * @param string $message Message to log
 * @param string $logFile Path to the log file
 */
function logWarning($message, $logFile = null) {
    logMessage($message, 'warning', $logFile);
}

/**
 * Log an error message
 * 
 * @param string $message Message to log
 * @param string $logFile Path to the log file
 */
function logError($message, $logFile = null) {
    logMessage($message, 'error', $logFile);
}
?>