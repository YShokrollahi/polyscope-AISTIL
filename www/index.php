<?php
/**
 * AI-Polyscope Dashboard - Main Page
 * Enhanced UI version with modern styling
 */

// Initialize any variables
$flashMessage = null;

// Include header
include_once __DIR__ . '/templates/header.php';
?>

<div class="dashboard-content">
    <!-- Input Files Section -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-file-upload"></i> Input Files</h2>
            <div class="actions">
                <label for="select-all" class="button button-small">
                    <input type="checkbox" id="select-all"> Select All
                </label>
            </div>
        </div>
        <div class="card-body">
            <?php
            $inputDir = __DIR__ . '/../input';
            $files = glob($inputDir . '/*.*');
            
            if (empty($files)) {
                ?>
                <div class="empty-state">
                    <i class="fas fa-upload"></i>
                    <p>No files found in input directory.</p>
                    <p class="small">Looking in: <?php echo realpath($inputDir); ?></p>
                </div>
                <?php
            } else {
                echo '<ul class="file-list">';
                foreach ($files as $file) {
                    $filename = basename($file);
                    $fileExt = pathinfo($filename, PATHINFO_EXTENSION);
                    $fileIcon = 'file';
                    
                    // Set icon based on file type
                    if (in_array(strtolower($fileExt), ['jpg', 'jpeg', 'png', 'gif', 'tiff', 'bmp'])) {
                        $fileIcon = 'file-image';
                    } elseif (in_array(strtolower($fileExt), ['pdf'])) {
                        $fileIcon = 'file-pdf';
                    } elseif (in_array(strtolower($fileExt), ['doc', 'docx'])) {
                        $fileIcon = 'file-word';
                    }
                    
                    echo '<li class="file-item">';
                    echo '<input type="checkbox" name="files[]" value="' . $file . '" id="' . $filename . '">';
                    echo '<label for="' . $filename . '"><i class="fas fa-' . $fileIcon . '"></i> ' . $filename . '</label>';
                    echo '</li>';
                }
                echo '</ul>';
                echo '<div style="text-align: right; margin-top: 1rem;">';
                echo '<button class="button button-success" onclick="processSelected()"><i class="fas fa-cogs"></i> Process Selected Files</button>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Processed Slides Section -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-microscope"></i> Processed Slides</h2>
            <div class="actions">
                <button class="button button-small" id="refresh-slides">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php
            $outputDir = __DIR__ . '/output';
            $processedDirs = glob($outputDir . '/*', GLOB_ONLYDIR);
            
            if (empty($processedDirs)) {
                ?>
                <div class="empty-state">
                    <i class="fas fa-microscope"></i>
                    <p>No processed slides found.</p>
                    <p class="small">Process slides to see them here.</p>
                </div>
                <?php
            } else {
                echo '<ul class="file-list">';
                foreach ($processedDirs as $dir) {
                    $dirname = basename($dir);
                    $statusFile = "$dir/status.json";
                    $status = "unknown";
                    $viewerPath = "";
                    
                    if (file_exists($statusFile)) {
                        $statusData = json_decode(file_get_contents($statusFile), true);
                        $status = $statusData['status'] ?? 'unknown';
                        $viewerPath = $statusData['viewerPath'] ?? '';
                    }
                    
                    $statusClass = '';
                    $statusIcon = '';
                    switch ($status) {
                        case 'success':
                            $statusClass = 'status-success';
                            $statusIcon = 'check-circle';
                            break;
                        case 'error':
                            $statusClass = 'status-error';
                            $statusIcon = 'times-circle';
                            break;
                        default:
                            $statusClass = 'status-pending';
                            $statusIcon = 'clock';
                    }
                    
                    echo '<li class="file-item">';
                    echo '<i class="fas fa-microscope"></i> ' . $dirname;
                    echo ' <span class="status-badge ' . $statusClass . '"><i class="fas fa-' . $statusIcon . '"></i> ' . $status . '</span>';
                    
                    if ($status == 'success' && !empty($viewerPath)) {
                        $relativeViewerPath = str_replace(__DIR__ . '/', '', $viewerPath);
                        echo ' <a href="' . $relativeViewerPath . '" target="_blank" class="button button-small"><i class="fas fa-eye"></i> View</a>';
                    }
                    
                    echo '</li>';
                }
                echo '</ul>';
                
                echo '<div style="text-align: right; margin-top: 1rem;">';
                echo '<button class="button button-primary" onclick="createMultizoom()"><i class="fas fa-object-group"></i> Create Multi-Zoom View</button>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Multi-Zoom Views Section -->
    <div class="card full-width">
        <div class="card-header">
            <h2><i class="fas fa-layer-group"></i> Multi-Zoom Views</h2>
        </div>
        <div class="card-body">
            <?php
            $multizoomDir = __DIR__ . '/output/multizoom';
            
            if (!is_dir($multizoomDir) || empty(glob($multizoomDir . '/*.html'))) {
                ?>
                <div class="empty-state">
                    <i class="fas fa-object-group"></i>
                    <p>No multi-zoom views found.</p>
                    <p class="small">Create a multi-zoom view to see it here.</p>
                </div>
                <?php
            } else {
                $multizoomFiles = glob($multizoomDir . '/*.html');
                
                echo '<ul class="file-list">';
                foreach ($multizoomFiles as $file) {
                    $filename = basename($file);
                    $relativePath = str_replace(__DIR__ . '/', '', $file);
                    $creationTime = date('M j, Y H:i', filemtime($file));
                    
                    echo '<li class="file-item">';
                    echo '<i class="fas fa-file-code"></i> ' . $filename;
                    echo ' <span style="color: #7f8c8d; margin-left: 10px; font-size: 0.8rem;">Created: ' . $creationTime . '</span>';
                    echo ' <a href="' . $relativePath . '" target="_blank" class="button button-small"><i class="fas fa-external-link-alt"></i> Open</a>';
                    echo '</li>';
                }
                echo '</ul>';
            }
            ?>
        </div>
    </div>
</div>

<!-- Processing Modal -->
<div id="processing-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-cogs"></i> Processing Files</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body" id="processing-items-container">
            <!-- Processing items will be added here -->
        </div>
        <div class="modal-footer">
            <button id="modal-close-btn" class="button">Close</button>
        </div>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/templates/footer.php';
?>