<!-- Main Content -->
<div class="main-content">
    <div class="dashboard-header">
        <h1><i class="fas fa-microscope"></i> Slide Manager</h1>
        <div>
            <button class="btn btn-success" data-toggle="modal" data-target="#uploadModal">
                <i class="fas fa-upload"></i> Upload Slides
            </button>
            <button id="refresh-btn" class="btn btn-primary">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Tabs for Input Files and Processed Slides -->
    <div class="tabs">
        <ul class="tab-nav">
            <li class="<?php echo !isset($_GET['filter']) || $_GET['filter'] === 'all' ? 'active' : ''; ?>">
                <a href="index.php?view=slides&filter=all">All Slides</a>
            </li>
            <li class="<?php echo isset($_GET['filter']) && $_GET['filter'] === 'input' ? 'active' : ''; ?>">
                <a href="index.php?view=slides&filter=input">Input Files</a>
            </li>
            <li class="<?php echo isset($_GET['filter']) && $_GET['filter'] === 'processed' ? 'active' : ''; ?>">
                <a href="index.php?view=slides&filter=processed">Processed Slides</a>
            </li>
            <li class="<?php echo isset($_GET['filter']) && $_GET['filter'] === 'collections' ? 'active' : ''; ?>">
                <a href="index.php?view=slides&filter=collections">Collections</a>
            </li>
        </ul>
        
        <div class="tab-content">
            <?php 
            $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
            
            // All Slides
            if ($filter === 'all' || $filter === 'input'): 
                $showInput = true;
            ?>
            
            <!-- Input Files Section -->
            <?php if ($showInput): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-file-upload"></i> Input Files</h3>
                    <div>
                        <button class="btn btn-primary" onclick="processSelectedFiles()">
                            <i class="fas fa-cogs"></i> Process Selected
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($slides['input'])): ?>
                        <div class="empty-state">
                            <i class="fas fa-folder-open fa-3x"></i>
                            <p>No files found in input directory.</p>
                            <p>Please add files to the 'input' folder or upload files.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="select-all-input"></th>
                                        <th>Filename</th>
                                        <th>Size</th>
                                        <th>Type</th>
                                        <th>Last Modified</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($slides['input'] as $file): ?>
                                        <tr>
                                            <td><input type="checkbox" name="input-files[]" value="<?php echo $file['path']; ?>"></td>
                                            <td><?php echo $file['filename']; ?></td>
                                            <td><?php echo $file['formatted_size']; ?></td>
                                            <td><?php echo $file['type']; ?></td>
                                            <td><?php echo $file['formatted_date']; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="processFile('<?php echo $file['path']; ?>')">
                                                    <i class="fas fa-cogs"></i> Process
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteFile('<?php echo $file['path']; ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php endif; ?>
            
            <?php if ($filter === 'all' || $filter === 'processed'): ?>
            <!-- Processed Slides Section -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-microscope"></i> Processed Slides</h3>
                    <div>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#createCollectionModal">
                            <i class="fas fa-object-group"></i> Create Collection
                        </button>
                        <button class="btn btn-success" onclick="createMultizoomFromSelected()">
                            <i class="fas fa-layer-group"></i> Create Multi-Zoom View
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($slides['processed'])): ?>
                        <div class="empty-state">
                            <i class="fas fa-microscope fa-3x"></i>
                            <p>No processed slides found.</p>
                            <p>Process slides to see them here.</p>
                        </div>
                    <?php else: ?>
                        <!-- Grid View Toggle -->
                        <div class="view-toggle">
                            <button class="btn btn-sm active" data-view="grid">
                                <i class="fas fa-th"></i> Grid
                            </button>
                            <button class="btn btn-sm" data-view="list">
                                <i class="fas fa-list"></i> List
                            </button>
                        </div>
                        
                        <!-- Grid View (Default) -->
                        <div class="slide-grid" id="grid-view">
                            <?php foreach ($slides['processed'] as $slide): ?>
                                <div class="slide-card">
                                    <div class="slide-thumbnail">
                                        <?php if (!empty($slide['thumbnailPath']) && file_exists($slide['thumbnailPath'])): ?>
                                            <img src="<?php echo str_replace(WEB_ROOT, '', $slide['thumbnailPath']); ?>" alt="<?php echo $slide['name']; ?>">
                                        <?php else: ?>
                                            <div class="no-thumbnail">
                                                <i class="fas fa-microscope"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="status-indicator status-<?php echo $slide['status']; ?>"></div>
                                    </div>
                                    <div class="slide-info">
                                        <h4><?php echo $slide['name']; ?></h4>
                                        <p>Processed: <?php echo $slide['formatted_date']; ?></p>
                                        <div class="slide-actions">
                                            <input type="checkbox" name="processed-slides[]" value="<?php echo $slide['name']; ?>" class="slide-select">
                                            <?php if ($slide['status'] === 'success' && !empty($slide['viewerPath'])): ?>
                                                <a href="<?php echo str_replace(WEB_ROOT, '', $slide['viewerPath']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-sm" onclick="slideDetails('<?php echo $slide['name']; ?>')">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteSlide('<?php echo $slide['name']; ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- List View (Hidden by default) -->
                        <div class="table-responsive" id="list-view" style="display: none;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="select-all-processed"></th>
                                        <th>Slide Name</th>
                                        <th>Status</th>
                                        <th>Processing Time</th>
                                        <th>Date Processed</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($slides['processed'] as $slide): ?>
                                        <tr>
                                        <td><input type="checkbox" name="processed-slides[]" value="<?php echo $slide['name']; ?>"></td>
                                            <td><?php echo $slide['name']; ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $slide['status']; ?>">
                                                    <?php echo ucfirst($slide['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $slide['processingTime']; ?></td>
                                            <td><?php echo $slide['formatted_date']; ?></td>
                                            <td>
                                                <?php if ($slide['status'] === 'success' && !empty($slide['viewerPath'])): ?>
                                                    <a href="<?php echo str_replace(WEB_ROOT, '', $slide['viewerPath']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($slide['hasLog']): ?>
                                                    <button class="btn btn-sm" onclick="viewLog('<?php echo $slide['logPath']; ?>')">
                                                        <i class="fas fa-list-alt"></i> Log
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-sm" onclick="slideDetails('<?php echo $slide['name']; ?>')">
                                                    <i class="fas fa-info-circle"></i> Details
                                                </button>
                                                
                                                <button class="btn btn-sm btn-danger" onclick="deleteSlide('<?php echo $slide['name']; ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($filter === 'collections'): ?>
            <!-- Collections Section -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-object-group"></i> Slide Collections</h3>
                    <div>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#createCollectionModal">
                            <i class="fas fa-plus"></i> Create Collection
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php 
                    // Get all collections
                    $collections = Collection::getAll();
                    
                    if (empty($collections)): 
                    ?>
                        <div class="empty-state">
                            <i class="fas fa-object-group fa-3x"></i>
                            <p>No collections found.</p>
                            <p>Create a collection to organize your slides.</p>
                        </div>
                    <?php else: ?>
                        <div class="collections-grid">
                            <?php foreach ($collections as $collection): ?>
                                <div class="collection-card">
                                    <div class="collection-header">
                                        <h4><?php echo $collection->name; ?></h4>
                                        <span class="slide-count"><?php echo count($collection->slides); ?> slides</span>
                                    </div>
                                    <div class="collection-body">
                                        <p><?php echo $collection->description; ?></p>
                                        
                                        <div class="tag-list">
                                            <?php foreach ($collection->tags as $tag): ?>
                                                <span class="tag"><?php echo $tag; ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="collection-footer">
                                        <button class="btn btn-sm" onclick="viewCollection('<?php echo $collection->id; ?>')">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        
                                        <?php if ($collection->multizoomPath): ?>
                                            <a href="<?php echo $collection->multizoomPath; ?>" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="fas fa-layer-group"></i> Multi-Zoom
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success" onclick="createMultizoomForCollection('<?php echo $collection->id; ?>')">
                                                <i class="fas fa-layer-group"></i> Create Multi-Zoom
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-sm btn-danger" onclick="deleteCollection('<?php echo $collection->id; ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Upload Modal -->
<div class="modal" id="uploadModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-upload"></i> Upload Slides</h3>
            <span class="close" data-dismiss="modal">&times;</span>
        </div>
        <div class="modal-body">
            <form action="api/slides/import.php" method="post" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-area" id="uploadArea">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <p>Drag & drop slide files here or click to browse</p>
                    <p class="small">Supported formats: SVS, TIF, TIFF, NDPI, SCN, BIF</p>
                    <input type="file" id="fileInput" name="files[]" multiple style="display: none;">
                </div>
                
                <div class="file-list" id="fileList"></div>
                
                <div class="form-group">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="process_after_upload" checked>
                            Process slides after upload
                        </label>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" id="uploadBtn">Upload</button>
        </div>
    </div>
</div>

<!-- Create Collection Modal -->
<div class="modal" id="createCollectionModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-object-group"></i> Create Collection</h3>
            <span class="close" data-dismiss="modal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="collectionForm">
                <div class="form-group">
                    <label for="collectionName">Collection Name</label>
                    <input type="text" id="collectionName" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="collectionDescription">Description</label>
                    <textarea id="collectionDescription" name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Select Slides</label>
                    <div class="slide-selection-area">
                        <?php foreach ($slides['processed'] as $slide): ?>
                            <?php if ($slide['status'] === 'success'): ?>
                                <div class="slide-selection-item">
                                    <input type="checkbox" name="collection_slides[]" value="<?php echo $slide['name']; ?>" id="col-slide-<?php echo $slide['name']; ?>">
                                    <label for="col-slide-<?php echo $slide['name']; ?>">
                                        <?php if (!empty($slide['thumbnailPath']) && file_exists($slide['thumbnailPath'])): ?>
                                            <img src="<?php echo str_replace(WEB_ROOT, '', $slide['thumbnailPath']); ?>" alt="<?php echo $slide['name']; ?>">
                                        <?php endif; ?>
                                        <span><?php echo $slide['name']; ?></span>
                                    </label>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="collectionTags">Tags (comma separated)</label>
                    <input type="text" id="collectionTags" name="tags" class="form-control">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal">Cancel</button>
            <button class="btn btn-primary" id="createCollectionBtn">Create</button>
        </div>
    </div>
</div>

<!-- Slide Details Modal -->
<div class="modal" id="slideDetailsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-info-circle"></i> Slide Details</h3>
            <span class="close" data-dismiss="modal">&times;</span>
        </div>
        <div class="modal-body" id="slideDetailsContent">
            <!-- Dynamically populated by JavaScript -->
        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal">Close</button>
        </div>
    </div>
</div>

<!-- Log Viewer Modal -->
<div class="modal" id="logViewerModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-list-alt"></i> Process Log</h3>
            <span class="close" data-dismiss="modal">&times;</span>
        </div>
        <div class="modal-body">
            <pre id="logContent" class="log-content"></pre>
        </div>
        <div class="modal-footer">
            <button class="btn" data-dismiss="modal">Close</button>
        </div>
    </div>
</div>

<!-- Processing Progress Modal -->
<div class="modal" id="processingModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-cogs"></i> Processing Slides</h3>
            <span class="close" data-dismiss="modal">&times;</span>
        </div>
        <div class="modal-body" id="processingContent">
            <!-- Dynamically populated by JavaScript -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" data-dismiss="modal">Done</button>
        </div>
    </div>
</div>