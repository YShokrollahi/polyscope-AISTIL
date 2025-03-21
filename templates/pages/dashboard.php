<!-- Main Content -->
<div class="main-content">
    <div class="dashboard-header">
        <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
        <div>
            <button id="refresh-btn" class="btn btn-primary"><i class="fas fa-sync-alt"></i> Refresh</button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="title">Total Slides</div>
            <div class="number" id="stats-total"><?php echo $stats['total_slides']; ?></div>
            <div class="description">Total slides in the system</div>
        </div>
        <div class="stat-card">
            <div class="title">Processed</div>
            <div class="number" id="stats-processed"><?php echo $stats['processed_slides']; ?></div>
            <div class="description">Successfully processed slides</div>
        </div>
        <div class="stat-card">
            <div class="title">Errors</div>
            <div class="number" id="stats-errors"><?php echo $stats['error_slides']; ?></div>
            <div class="description">Slides with processing errors</div>
        </div>
        <div class="stat-card">
            <div class="title">Multi-Zoom Views</div>
            <div class="number" id="stats-multizoom"><?php echo $stats['multizoom_views']; ?></div>
            <div class="description">Created multi-zoom views</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        <div class="action-buttons">
            <a href="index.php?view=slides" class="btn btn-primary">
                <i class="fas fa-microscope"></i> Manage Slides
            </a>
            <a href="index.php?view=slides&action=upload" class="btn btn-success">
                <i class="fas fa-upload"></i> Upload New Slides
            </a>
            <a href="index.php?view=multizoom&action=create" class="btn btn-primary">
                <i class="fas fa-layer-group"></i> Create Multi-Zoom View
            </a>
        </div>
    </div>

    <!-- Recent Items Section -->
    <div class="row">
        <!-- Recent Input Files -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-file-upload"></i> Recent Input Files</h3>
                    <div>
                        <a href="index.php?view=slides" class="btn btn-sm">View All</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($recentInputFiles)): ?>
                        <div class="empty-state">
                            <i class="fas fa-folder-open fa-3x"></i>
                            <p>No input files found.</p>
                            <p>Please add files to the 'input' folder.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recentInputFiles as $file): ?>
                                <div class="list-item">
                                    <div class="list-item-icon">
                                        <i class="fas fa-file"></i>
                                    </div>
                                    <div class="list-item-content">
                                        <div class="list-item-title"><?php echo $file['filename']; ?></div>
                                        <div class="list-item-meta">
                                            <?php echo $file['formatted_size']; ?> • 
                                            <?php echo $file['type']; ?> • 
                                            <?php echo $file['formatted_date']; ?>
                                        </div>
                                    </div>
                                    <div class="list-item-actions">
                                        <a href="api/slides/process.php?file=<?php echo urlencode($file['path']); ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-cogs"></i> Process
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Processed Slides -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-microscope"></i> Recent Processed Slides</h3>
                    <div>
                        <a href="index.php?view=slides&filter=processed" class="btn btn-sm">View All</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($recentProcessedSlides)): ?>
                        <div class="empty-state">
                            <i class="fas fa-microscope fa-3x"></i>
                            <p>No processed slides found.</p>
                            <p>Process slides to see them here.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recentProcessedSlides as $slide): ?>
                                <div class="list-item">
                                    <?php if (!empty($slide['thumbnailPath']) && file_exists($slide['thumbnailPath'])): ?>
                                        <div class="list-item-thumbnail">
                                            <img src="<?php echo str_replace(WEB_ROOT, '', $slide['thumbnailPath']); ?>" alt="<?php echo $slide['name']; ?>">
                                        </div>
                                    <?php else: ?>
                                        <div class="list-item-icon">
                                            <i class="fas fa-microscope"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="list-item-content">
                                        <div class="list-item-title"><?php echo $slide['name']; ?></div>
                                        <div class="list-item-meta">
                                            <?php echo $slide['status']; ?> • 
                                            <?php echo $slide['formatted_date']; ?>
                                        </div>
                                    </div>
                                    <div class="list-item-actions">
                                        <?php if ($slide['status'] === 'success' && !empty($slide['viewerPath'])): ?>
                                            <a href="<?php echo str_replace(WEB_ROOT, '', $slide['viewerPath']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>