/**
 * Slide Manager JavaScript
 * 
 * Handles all client-side functionality for the Slide Manager page.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize UI components
    initializeViewToggle();
    initializeUploadArea();
    initializeModals();
    setupSelectAllHandlers();
    
    // Setup buttons
    document.getElementById('uploadBtn')?.addEventListener('click', handleUpload);
    document.getElementById('createCollectionBtn')?.addEventListener('click', createCollection);
    
    // Refresh button
    document.getElementById('refresh-btn')?.addEventListener('click', function() {
        location.reload();
    });
});

/**
 * Initialize view toggle between grid and list views
 */
function initializeViewToggle() {
    const toggleButtons = document.querySelectorAll('.view-toggle button');
    const gridView = document.getElementById('grid-view');
    const listView = document.getElementById('list-view');
    
    if (!toggleButtons.length || !gridView || !listView) return;
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            toggleButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Show/hide appropriate view
            const viewType = this.getAttribute('data-view');
            if (viewType === 'grid') {
                gridView.style.display = 'grid';
                listView.style.display = 'none';
            } else {
                gridView.style.display = 'none';
                listView.style.display = 'block';
            }
        });
    });
}

/**
 * Initialize drag and drop upload functionality
 */
function initializeUploadArea() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');
    
    if (!uploadArea || !fileInput || !fileList) return;
    
    // Click to browse
    uploadArea.addEventListener('click', function() {
        fileInput.click();
    });
    
    // Handle file selection
    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });
    
    // Drag and drop functionality
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    // Highlight drop area when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, function() {
            uploadArea.classList.add('highlight');
        }, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, function() {
            uploadArea.classList.remove('highlight');
        }, false);
    });
    
    // Handle dropped files
    uploadArea.addEventListener('drop', function(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }, false);
    
    // Process the files for display
    function handleFiles(files) {
        fileList.innerHTML = '';
        
        if (files.length === 0) return;
        
        Array.from(files).forEach(file => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            
            // Get file size
            const fileSize = formatFileSize(file.size);
            
            // Get file extension
            const fileExt = file.name.split('.').pop().toUpperCase();
            
            fileItem.innerHTML = `
                <div class="file-info">
                    <span class="file-name">${file.name}</span>
                    <span class="file-meta">${fileExt} • ${fileSize}</span>
                </div>
                <button type="button" class="remove-file">&times;</button>
            `;
            
            fileList.appendChild(fileItem);
            
            // Setup remove button
            const removeButton = fileItem.querySelector('.remove-file');
            removeButton.addEventListener('click', function() {
                fileItem.remove();
            });
        });
    }
}

/**
 * Format file size for display
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Initialize modal functionality
 */
function initializeModals() {
    // Get all modal triggers
    const modalTriggers = document.querySelectorAll('[data-toggle="modal"]');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const targetModal = document.querySelector(this.getAttribute('data-target'));
            if (targetModal) {
                targetModal.style.display = 'block';
            }
        });
    });
    
    // Get all close buttons
    const closeButtons = document.querySelectorAll('[data-dismiss="modal"]');
    
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });
}

/**
 * Setup select all checkboxes
 */
function setupSelectAllHandlers() {
    // Input files select all
    const selectAllInput = document.getElementById('select-all-input');
    if (selectAllInput) {
        selectAllInput.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="input-files[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    // Processed slides select all
    const selectAllProcessed = document.getElementById('select-all-processed');
    if (selectAllProcessed) {
        selectAllProcessed.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="processed-slides[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
}

/**
 * Handle file upload
 */
function handleUpload() {
    const form = document.getElementById('uploadForm');
    const fileInput = document.getElementById('fileInput');
    
    if (!form || !fileInput || fileInput.files.length === 0) {
        showNotification('Please select files to upload', 'warning');
        return;
    }
    
    // Create FormData object
    const formData = new FormData(form);
    
    // Show loading state
    const uploadBtn = document.getElementById('uploadBtn');
    const originalText = uploadBtn.textContent;
    uploadBtn.textContent = 'Uploading...';
    uploadBtn.disabled = true;
    
    // Send AJAX request
    fetch('api/slides/import.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Reset button
        uploadBtn.textContent = originalText;
        uploadBtn.disabled = false;
        
        // Close modal
        document.getElementById('uploadModal').style.display = 'none';
        
        if (data.status === 'success') {
            showNotification(data.message || 'Files uploaded successfully');
            
            // Reload page if processing was requested
            if (form.elements['process_after_upload'].checked) {
                // Show processing modal
                showProcessingModal(data.files);
            } else {
                // Just reload the page
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        } else {
            showNotification(data.message || 'Error uploading files', 'error');
        }
    })
    .catch(error => {
        uploadBtn.textContent = originalText;
        uploadBtn.disabled = false;
        showNotification('Error: ' + error.message, 'error');
    });
}

/**
 * Create a new collection
 */
function createCollection() {
    const form = document.getElementById('collectionForm');
    
    if (!form) return;
    
    const name = form.elements['name'].value;
    if (!name) {
        showNotification('Please enter a collection name', 'warning');
        return;
    }
    
    // Get selected slides
    const selectedSlides = Array.from(form.elements['collection_slides[]'])
        .filter(checkbox => checkbox.checked)
        .map(checkbox => checkbox.value);
    
    if (selectedSlides.length === 0) {
        showNotification('Please select at least one slide', 'warning');
        return;
    }
    
    // Get tags
    const tags = form.elements['tags'].value
        .split(',')
        .map(tag => tag.trim())
        .filter(tag => tag);
    
    // Prepare data
    const data = {
        name: name,
        description: form.elements['description'].value,
        slides: selectedSlides,
        tags: tags
    };
    
    // Show loading state
    const createBtn = document.getElementById('createCollectionBtn');
    const originalText = createBtn.textContent;
    createBtn.textContent = 'Creating...';
    createBtn.disabled = true;
    
    // Send AJAX request
    fetch('api/collections/create.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        // Reset button
        createBtn.textContent = originalText;
        createBtn.disabled = false;
        
        // Close modal
        document.getElementById('createCollectionModal').style.display = 'none';
        
        if (data.status === 'success') {
            showNotification(data.message || 'Collection created successfully');
            
            // Reload page
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification(data.message || 'Error creating collection', 'error');
        }
    })
    .catch(error => {
        createBtn.textContent = originalText;
        createBtn.disabled = false;
        showNotification('Error: ' + error.message, 'error');
    });
}

/**
 * Process a single file
 */
function processFile(filePath) {
    // Show confirmation
    if (!confirm('Process this file?')) {
        return;
    }
    
    // Show processing modal with just this file
    showProcessingModal([filePath]);
    
    // Make API request
    fetch(`api/slides/process.php?file=${encodeURIComponent(filePath)}`)
        .then(response => response.json())
        .then(data => {
            console.log('Processing started:', data);
        })
        .catch(error => {
            console.error('Error starting process:', error);
            showNotification('Error starting process', 'error');
        });
}

/**
 * Process selected files
 */
function processSelectedFiles() {
    const selectedFiles = Array.from(document.querySelectorAll('input[name="input-files[]"]:checked'))
        .map(checkbox => checkbox.value);
    
    if (selectedFiles.length === 0) {
        showNotification('Please select at least one file to process', 'warning');
        return;
    }
    
    // Show confirmation
    if (!confirm(`Process ${selectedFiles.length} selected file(s)?`)) {
        return;
    }
    
    // Show processing modal
    showProcessingModal(selectedFiles);
    
    // Make API request
    fetch('api/slides/process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ files: selectedFiles })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Processing started:', data);
    })
    .catch(error => {
        console.error('Error starting process:', error);
        showNotification('Error starting process', 'error');
    });
}

/**
 * Create a multi-zoom view from selected slides
 */
function createMultizoomFromSelected() {
    const selectedSlides = Array.from(document.querySelectorAll('input[name="processed-slides[]"]:checked'))
        .map(checkbox => checkbox.value);
    
    if (selectedSlides.length === 0) {
        showNotification('Please select at least one slide for multi-zoom view', 'warning');
        return;
    }
    
    // Show confirmation
    if (!confirm(`Create a multi-zoom view with ${selectedSlides.length} selected slide(s)?`)) {
        return;
    }
    
    // Show loading notification
    showNotification('Creating multi-zoom view... Please wait.', 'info');
    
    // Make API request
    fetch('api/slides/create_multizoom.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ slides: selectedSlides })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification('Multi-zoom view created successfully');
            
            // Reload page after a delay
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showNotification(data.message || 'Error creating multi-zoom view', 'error');
        }
    })
    .catch(error => {
        console.error('Error creating multi-zoom view:', error);
        showNotification('Error creating multi-zoom view', 'error');
    });
}

/**
 * Show the processing modal and track progress
 */
function showProcessingModal(files) {
    const modal = document.getElementById('processingModal');
    const contentArea = document.getElementById('processingContent');
    
    if (!modal || !contentArea) return;
    
    // Clear the content area
    contentArea.innerHTML = '';
    
    // Create progress items for each file
    files.forEach(file => {
        const filename = file.split('/').pop();
        const baseFilename = filename.substring(0, filename.lastIndexOf('.'));
        
        const progressItem = document.createElement('div');
        progressItem.className = 'processing-item';
        progressItem.id = `processing-${baseFilename}`;
        
        progressItem.innerHTML = `
            <div class="processing-header">
                <span class="filename">${filename}</span>
                <span class="status status-waiting">Waiting...</span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: 0%"></div>
            </div>
            <div class="progress-details">
                <span class="progress-message">Preparing to process...</span>
            </div>
        `;
        
        contentArea.appendChild(progressItem);
    });
    
    // Show the modal
    modal.style.display = 'block';
    
    // Start monitoring progress for each file
    files.forEach(file => {
        startProgressMonitoring(file);
    });
}

/**
 * Monitor progress for a file
 */
function startProgressMonitoring(filePath) {
    const filename = filePath.split('/').pop();
    const baseFilename = filename.substring(0, filename.lastIndexOf('.'));
    
    // Set interval to check progress
    const interval = setInterval(() => {
        // Fetch progress update
        fetch(`api/slides/progress.php?file=${encodeURIComponent(filePath)}`)
            .then(response => response.json())
            .then(data => {
                // Update the UI with current progress
                updateProgressUI(
                    baseFilename, 
                    data.status, 
                    data.percent || 0, 
                    data.message || data.stage || 'Processing...'
                );
                
                // If complete or error, stop monitoring
                if (data.status === 'complete' || data.status === 'error') {
                    clearInterval(interval);
                    
                    // Reload page after all processes complete
                    // Check if all items are done
                    const allItems = document.querySelectorAll('.processing-item');
                    const completedItems = document.querySelectorAll('.status-complete, .status-error');
                    
                    if (allItems.length === completedItems.length) {
                        setTimeout(() => {
                            location.reload();
                        }, 3000);
                    }
                }
            })
            .catch(error => {
                console.error('Error checking progress:', error);
                
                // Increment error count on the element
                const progressItem = document.getElementById(`processing-${baseFilename}`);
                if (progressItem) {
                    progressItem.dataset.errorCount = (parseInt(progressItem.dataset.errorCount || '0') + 1).toString();
                    
                    // If too many errors, stop monitoring
                    if (parseInt(progressItem.dataset.errorCount) > 5) {
                        clearInterval(interval);
                        updateProgressUI(baseFilename, 'error', 0, 'Failed to monitor progress');
                    }
                }
            });
    }, 2000); // Check every 2 seconds
}

/**
 * Update progress UI for a file
 */
function updateProgressUI(baseFilename, status, percent, message) {
    const container = document.getElementById(`processing-${baseFilename}`);
    if (!container) return;
    
    // Update status text class
    const statusElem = container.querySelector('.status');
    if (statusElem) {
        statusElem.className = 'status status-' + status;
        
        // Update status text
        let statusText = 'Processing';
        switch(status) {
            case 'initializing':
            case 'starting':
            case 'checking':
            case 'preparing':
                statusText = 'Starting';
                break;
            case 'processing':
                statusText = 'Processing';
                break;
            case 'finishing':
            case 'creating_viewer':
                statusText = 'Finishing';
                break;
            case 'complete':
                statusText = 'Complete';
                break;
            case 'error':
                statusText = 'Error';
                break;
            case 'stalled':
                statusText = 'Stalled';
                break;
            default:
                statusText = status.charAt(0).toUpperCase() + status.slice(1);
        }
        statusElem.textContent = statusText;
    }
    
    // Update progress bar
    const progressBar = container.querySelector('.progress-bar');
    if (progressBar) {
        progressBar.style.width = `${percent}%`;
        
        // Update progress class based on status
        progressBar.className = 'progress-bar';
        if (status === 'complete') {
            progressBar.classList.add('progress-success');
        } else if (status === 'error') {
            progressBar.classList.add('progress-error');
        } else if (status === 'stalled') {
            progressBar.classList.add('progress-warning');
        }
    }
    
    // Update message
    const messageElem = container.querySelector('.progress-message');
    if (messageElem) {
        messageElem.textContent = message;
    }
}

/**
 * Show slide details in modal
 */
function slideDetails(slideName) {
    const modal = document.getElementById('slideDetailsModal');
    const contentArea = document.getElementById('slideDetailsContent');
    
    if (!modal || !contentArea) return;
    
    // Show loading state
    contentArea.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading slide details...</div>';
    modal.style.display = 'block';
    
    // Fetch slide details
    fetch(`api/slides/details.php?slide=${encodeURIComponent(slideName)}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const slide = data.slide;
                
                // Format content
                let content = `
                    <div class="slide-detail-header">
                        ${slide.thumbnailPath ? `<img src="${slide.thumbnailPath}" alt="${slide.name}" class="slide-thumbnail">` : ''}
                        <h4>${slide.name}</h4>
                    </div>
                    
                    <div class="slide-detail-info">
                        <table class="detail-table">
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="status-badge status-${slide.status}">
                                        ${slide.status.charAt(0).toUpperCase() + slide.status.slice(1)}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Processing Time:</th>
                                <td>${slide.processingTime}</td>
                            </tr>
                            <tr>
                                <th>Date Processed:</th>
                                <td>${slide.dateFormatted}</td>
                            </tr>
                `;
                
                // Add metadata if available
                if (slide.metadata && Object.keys(slide.metadata).length > 0) {
                    content += `
                        <tr>
                            <th>Metadata:</th>
                            <td>
                                <ul class="metadata-list">
                    `;
                    
                    for (const [key, value] of Object.entries(slide.metadata)) {
                        if (key === 'tags') continue;
                        content += `<li><strong>${key}:</strong> ${value}</li>`;
                    }
                    
                    content += `
                                </ul>
                            </td>
                        </tr>
                    `;
                }
                
                // Add tags if available
                if (slide.tags && slide.tags.length > 0) {
                    content += `
                        <tr>
                            <th>Tags:</th>
                            <td>
                                <div class="tag-list">
                    `;
                    
                    slide.tags.forEach(tag => {
                        content += `<span class="tag">${tag}</span>`;
                    });
                    
                    content += `
                                </div>
                            </td>
                        </tr>
                    `;
                }
                
                content += `
                        </table>
                    </div>
                    
                    <div class="slide-detail-actions">
                        <button class="btn btn-primary" onclick="editTags('${slide.name}')">
                            <i class="fas fa-tags"></i> Edit Tags
                        </button>
                        
                        ${slide.viewerPath ? `
                            <a href="${slide.viewerPath}" target="_blank" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View Slide
                            </a>
                        ` : ''}
                    </div>
                `;
                
                // Update content
                contentArea.innerHTML = content;
            } else {
                contentArea.innerHTML = `<div class="error">Error loading slide details: ${data.message || 'Unknown error'}</div>`;
            }
        })
        .catch(error => {
            console.error('Error fetching slide details:', error);
            contentArea.innerHTML = `<div class="error">Error loading slide details: ${error.message}</div>`;
        });
}

/**
 * Edit tags for a slide
 */
function editTags(slideName) {
    // First get current tags
    fetch(`api/slides/details.php?slide=${encodeURIComponent(slideName)}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const slide = data.slide;
                const currentTags = slide.tags || [];
                
                // Create a simple prompt for tags
                const tagsString = prompt('Enter tags (comma separated)', currentTags.join(', '));
                
                if (tagsString !== null) {
                    // Parse tags
                    const tags = tagsString.split(',')
                        .map(tag => tag.trim())
                        .filter(tag => tag);
                    
                    // Update tags
                    fetch('api/slides/update.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            slide: slideName,
                            action: 'update_tags',
                            tags: tags
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showNotification('Tags updated successfully');
                            
                            // Refresh slide details
                            slideDetails(slideName);
                        } else {
                            showNotification(data.message || 'Error updating tags', 'error');
                        }
                    })
                    .catch(error => {
                        showNotification('Error: ' + error.message, 'error');
                    });
                }
            } else {
                showNotification(data.message || 'Error loading slide details', 'error');
            }
        })
        .catch(error => {
            showNotification('Error: ' + error.message, 'error');
        });
}

/**
 * Delete an input file
 */
function deleteFile(filePath) {
    if (!confirm('Are you sure you want to delete this file? This cannot be undone.')) {
        return;
    }
    
    fetch('api/slides/delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            file: filePath,
            type: 'input'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification('File deleted successfully');
            
            // Remove from DOM
            const row = document.querySelector(`input[value="${filePath}"]`).closest('tr');
            if (row) {
                row.remove();
            }
        } else {
            showNotification(data.message || 'Error deleting file', 'error');
        }
    })
    .catch(error => {
        showNotification('Error: ' + error.message, 'error');
    });
}

/**
 * Delete a processed slide
 */
function deleteSlide(slideName) {
    if (!confirm('Are you sure you want to delete this processed slide? This cannot be undone.')) {
        return;
    }
    
    fetch('api/slides/delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            slide: slideName,
            type: 'processed'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification('Slide deleted successfully');
            
            // Remove from DOM - handle both grid and list views
            const card = document.querySelector(`.slide-card h4:contains('${slideName}')`).closest('.slide-card');
            if (card) {
                card.remove();
            }
            
            const row = document.querySelector(`tr td:contains('${slideName}')`).closest('tr');
            if (row) {
                row.remove();
            }
        } else {
            showNotification(data.message || 'Error deleting slide', 'error');
        }
    })
    .catch(error => {
        showNotification('Error: ' + error.message, 'error');
    });
}

/**
 * Delete a collection
 */
function deleteCollection(collectionId) {
    if (!confirm('Are you sure you want to delete this collection? This will not delete the slides themselves.')) {
        return;
    }
    
    fetch('api/collections/delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: collectionId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification('Collection deleted successfully');
            
            // Remove from DOM
            const card = document.querySelector(`.collection-card button[onclick*="${collectionId}"]`).closest('.collection-card');
            if (card) {
                card.remove();
            }
        } else {
            showNotification(data.message || 'Error deleting collection', 'error');
        }
    })
    .catch(error => {
        showNotification('Error: ' + error.message, 'error');
    });
}

/**
 * View a collection's details
 */
function viewCollection(collectionId) {
    window.location.href = `index.php?view=slides&filter=collections&collection=${collectionId}`;
}

/**
 * Create a multi-zoom view for a collection
 */
function createMultizoomForCollection(collectionId) {
    if (!confirm('Create a multi-zoom view for this collection?')) {
        return;
    }
    
    // Show loading notification
    showNotification('Creating multi-zoom view... Please wait.', 'info');
    
    fetch('api/collections/create_multizoom.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: collectionId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification('Multi-zoom view created successfully');
            
            // Reload page after a delay
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showNotification(data.message || 'Error creating multi-zoom view', 'error');
        }
    })
    .catch(error => {
        showNotification('Error: ' + error.message, 'error');
    });
}

/**
 * View the process log
 */
function viewLog(logPath) {
    const modal = document.getElementById('logViewerModal');
    const contentArea = document.getElementById('logContent');
    
    if (!modal || !contentArea) return;
    
    // Show loading state
    contentArea.innerHTML = 'Loading log...';
    modal.style.display = 'block';
    
    // Fetch log content
    fetch(`api/slides/view_log.php?log=${encodeURIComponent(logPath)}`)
        .then(response => response.text())
        .then(content => {
            contentArea.innerHTML = content;
        })
        .catch(error => {
            contentArea.innerHTML = `Error loading log: ${error.message}`;
        });
}

/**
 * Show a notification
 */
function showNotification(message, type = 'success') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            ${message}
        </div>
        <button class="notification-close">&times;</button>
    `;
    
    // Add to document
    document.body.appendChild(notification);
    
    // Add event listener for close button
    notification.querySelector('.notification-close').addEventListener('click', function() {
        notification.remove();
    });
    
    // Auto-remove after a delay
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => {
            notification.remove();
        }, 500);
    }, 5000);
}

/**
 * Custom selector for text content
 */
Element.prototype.contains = function(text) {
    return this.textContent.includes(text);
};