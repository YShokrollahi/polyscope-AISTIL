// dashboard.js - place in www/js/ directory
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar on mobile
    const menuToggle = document.querySelector('.menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('sidebar-active');
        });
    }
    
    // Handle file selection
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="files[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    // Setup refresh button
    const refreshBtn = document.getElementById('refresh-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            location.reload();
        });
    }
    
    // Track active processes
    window.activeProcesses = {};
    
    // Process selected files
    window.processSelected = function() {
        const checkboxes = document.querySelectorAll('input[name="files[]"]:checked');
        const files = Array.from(checkboxes).map(cb => cb.value);
        
        if (files.length === 0) {
            showAlert('Please select at least one file to process.', 'warning');
            return;
        }
        
        // Create processing modal with progress bars
        createProcessingModal(files);
        
        // Process files one by one
        processFiles(files);
    };
    
    // Create processing modal with progress bars
    function createProcessingModal(files) {
        // Remove any existing modal
        const existingModal = document.getElementById('processing-modal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Create modal element directly
        const modal = document.createElement('div');
        modal.id = 'processing-modal';
        modal.className = 'modal';
        
        // Add modal content
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-cogs"></i> Processing Slides</h3>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    ${files.map(file => {
                        const filename = file.split('/').pop();
                        const baseFilename = filename.substring(0, filename.lastIndexOf('.'));
                        return `
                            <div class="processing-item" id="processing-${baseFilename}">
                                <div class="processing-header">
                                    <span class="filename">${filename}</span>
                                    <span class="status">Waiting...</span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: 0%"></div>
                                </div>
                                <div class="progress-details">
                                    <span class="progress-message">Preparing to process...</span>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
                <div class="modal-footer">
                    <button id="processing-done-btn" class="btn btn-primary">Done</button>
                </div>
            </div>
        `;
        
        // Append modal directly to body
        document.body.appendChild(modal);
        
        // Show modal
        modal.style.display = 'block';
        
        // Add event listeners
        document.querySelector('#processing-modal .close').addEventListener('click', () => {
            modal.style.display = 'none';
        });
        
        document.getElementById('processing-done-btn').addEventListener('click', () => {
            modal.style.display = 'none';
        });
    }
    
    // Process files and track progress
    function processFiles(files) {
        files.forEach(file => {
            const filename = file.split('/').pop();
            const baseFilename = filename.substring(0, filename.lastIndexOf('.'));
            
            // Init tracking for this file
            window.activeProcesses[baseFilename] = {
                file: file,
                status: 'starting',
                percent: 0
            };
            
            // Update UI to show starting
            updateProgressUI(baseFilename, 'starting', 0, 'Starting process...');
            
            // Make API request to process the file
            fetch(`api/process.php?action=process&files=${encodeURIComponent(JSON.stringify([file]))}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Processing started:', data);
                    
                    // Start progress monitoring
                    startProgressMonitoring(baseFilename, file);
                })
                .catch(error => {
                    console.error('Error starting process:', error);
                    updateProgressUI(baseFilename, 'error', 0, 'Failed to start processing');
                });
        });
    }
    
    // Monitor progress for a file
    function startProgressMonitoring(baseFilename, file) {
        // Set interval to check progress
        const interval = setInterval(() => {
            // Check if process is still being tracked
            if (!window.activeProcesses[baseFilename]) {
                clearInterval(interval);
                return;
            }
            
            // Fetch progress update
            fetch(`api/progress.php?file=${encodeURIComponent(file)}`)
                .then(response => response.json())
                .then(data => {
                    // Update the UI with current progress
                    updateProgressUI(
                        baseFilename, 
                        data.status, 
                        data.percent, 
                        data.message || data.stage
                    );
                    
                    // If complete or error, stop monitoring
                    if (data.status === 'complete' || data.status === 'error') {
                        clearInterval(interval);
                        
                        // Add view button if complete
                        if (data.status === 'complete') {
                            addViewButton(baseFilename);
                        }
                        
                        // If this was the last process, reload page after a delay
                        const activeCount = Object.values(window.activeProcesses)
                            .filter(p => p.status !== 'complete' && p.status !== 'error')
                            .length;
                            
                        if (activeCount === 0) {
                            setTimeout(() => {
                                location.reload();
                            }, 5000);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking progress:', error);
                    
                    // Increment error count
                    window.activeProcesses[baseFilename].errorCount = 
                        (window.activeProcesses[baseFilename].errorCount || 0) + 1;
                    
                    // If too many errors, stop monitoring
                    if (window.activeProcesses[baseFilename].errorCount > 5) {
                        clearInterval(interval);
                        updateProgressUI(baseFilename, 'error', 0, 'Failed to monitor progress');
                    }
                });
        }, 2000); // Check every 2 seconds
    }
    
    // Update progress UI for a file
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
        
        // Update stored status
        if (window.activeProcesses[baseFilename]) {
            window.activeProcesses[baseFilename].status = status;
            window.activeProcesses[baseFilename].percent = percent;
        }
    }
    
    // Add view button when processing is complete
    function addViewButton(baseFilename) {
        const container = document.getElementById(`processing-${baseFilename}`);
        if (!container) return;
        
        // Check if button already exists
        if (container.querySelector('.view-btn')) return;
        
        // Create view button
        const viewBtn = document.createElement('a');
        viewBtn.href = `output/${baseFilename}/${baseFilename}.html`;
        viewBtn.className = 'btn btn-primary view-btn';
        viewBtn.innerHTML = '<i class="fas fa-eye"></i> View';
        viewBtn.target = '_blank';
        
        // Add to container
        const detailsElem = container.querySelector('.progress-details');
        if (detailsElem) {
            detailsElem.appendChild(viewBtn);
        }
    }
    
    // Create multi-zoom view
    window.createMultizoom = function() {
        if (confirm('Create a multi-zoom view of all processed slides?')) {
            showProgress('Creating multi-zoom view... Please wait.');
            
            // Create a form for POST submission
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'api/process.php';
            form.style.display = 'none';
            
            // Add action as input
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'multizoom';
            form.appendChild(actionInput);
            
            // Append form to body and submit
            document.body.appendChild(form);
            form.submit();
        }
    };
    
    // Helper functions
    function showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = message;
        
        const container = document.querySelector('.main-content');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    }
    
    function showProgress(message) {
        // Create and show progress overlay
        const overlay = document.createElement('div');
        overlay.className = 'progress-overlay';
        overlay.innerHTML = `
            <div class="progress-container">
                <h3>${message}</h3>
                <div class="loader"></div>
            </div>
        `;
        
        document.body.appendChild(overlay);
    }
    
    // Initialize any statistics or charts
    initializeStats();
});

function initializeStats() {
    // Count total files
    const totalFiles = document.querySelectorAll('.file-item').length;
    const statsTotal = document.getElementById('stats-total');
    if (statsTotal) {
        statsTotal.textContent = totalFiles;
    }
    
    // Count processed files
    const processedFiles = document.querySelectorAll('.status-success').length;
    const statsProcessed = document.getElementById('stats-processed');
    if (statsProcessed) {
        statsProcessed.textContent = processedFiles;
    }
    
    // Count error files
    const errorFiles = document.querySelectorAll('.status-error').length;
    const statsErrors = document.getElementById('stats-errors');
    if (statsErrors) {
        statsErrors.textContent = errorFiles;
    }
}