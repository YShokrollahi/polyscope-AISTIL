<?php
/**
 * AI-Polyscope - Entry Point
 *
 * This file serves as the main entry point for the AI-Polyscope application.
 * It handles routing to different pages and initializes the application.
 */

// Initialize session
session_start();

// Load configuration
$configPath = __DIR__ . '/../config/config.json';
$config = [];
if (file_exists($configPath)) {
    $config = json_decode(file_get_contents($configPath), true);
}

// Include utility functions
if (file_exists(__DIR__ . '/../src/utils/logger.php')) {
    require_once __DIR__ . '/../src/utils/logger.php';
}

if (file_exists(__DIR__ . '/../src/utils/file_helper.php')) {
    require_once __DIR__ . '/../src/utils/file_helper.php';
}

// Include core modules
require_once __DIR__ . '/../src/core/slide_manager.php';

// Initialize the slide manager
$slideManager = new SlideManager();

// Define application paths
define('BASE_PATH', dirname(__DIR__));
define('WEB_ROOT', __DIR__);
define('INPUT_DIR', BASE_PATH . '/input');
define('OUTPUT_DIR', WEB_ROOT . '/output');
define('TEMPLATE_DIR', BASE_PATH . '/templates');

// Handle view routing
$view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';

// Validate view to prevent directory traversal
$allowedViews = ['dashboard', 'slides', 'multizoom', 'settings'];
if (!in_array($view, $allowedViews)) {
    $view = 'dashboard';
}

// Process flash messages
$flashMessage = null;
if (isset($_SESSION['message'])) {
    $flashMessage = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Get current page title
$pageTitles = [
    'dashboard' => 'Dashboard',
    'slides' => 'Slide Manager',
    'multizoom' => 'Multi-Zoom Views',
    'settings' => 'Settings'
];
$pageTitle = $pageTitles[$view] ?? 'Dashboard';

// Get data for page if needed
switch ($view) {
    case 'slides':
        $slides = $slideManager->getAllSlides();
        break;
    case 'multizoom':
        $multizoomViews = $slideManager->getMultizoomViews();
        break;
    default:
        // Default data for dashboard
        $stats = $slideManager->getStats();
        $recentInputFiles = $slideManager->getRecentInputFiles(5);
        $recentProcessedSlides = $slideManager->getRecentProcessedSlides(5);
        break;
}

// Include the header
require_once TEMPLATE_DIR . '/layout/header.php';

// Include the sidebar
require_once TEMPLATE_DIR . '/layout/sidebar.php';

// Include the page content
require_once TEMPLATE_DIR . "/pages/{$view}.php";

// Include the footer
require_once TEMPLATE_DIR . '/layout/footer.php';