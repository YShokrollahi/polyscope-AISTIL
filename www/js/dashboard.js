/**
 * AI-Polyscope Dashboard JavaScript
 * Enhanced version with modern features
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize select all functionality
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
        refreshBtn.addEventListener('click', function(e) {
            e.preventDefault();
            location.reload();
        });
    }

    // Setup refresh slides button
    const refreshSlidesBtn = document.getElementById('refresh-slides');
    if (refreshSlidesBtn) {
        refreshSlidesBtn.addEventListener('click', function() {
            location.reload();
        });
    }

    // Initialize modal close buttons
    const modal = document.getElementById('processing-modal');
    if (modal) {
        const closeButtons = modal.querySelectorAll('.close, #modal-close-btn');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                modal.style.display = 'none';
            });
        });

        // Close modal when clicking outside of it
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }

    // Make file items more interactive
    const fileItems = document.querySelectorAll('.file-item');
    fileItems.forEach(item => {
        // Make clicking on the item (except for links and checkboxes) toggle the checkbox
        item.addEventListener('click', function(e) {
            if (e.target.tagName !== 'A' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'BUTTON') {
                const checkbox = this.querySelector('input[type="checkbox"]');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                }
            }
        });
    });
});

/**
 * Process selected files
 */
function processSelected() {
    const checkboxes = document.querySelectorAll('input[name="files[]"]:checked');
    const files = Array.from(checkboxes).map(cb => cb.value);
    
    if (files.length === 0) {
        showAlert('Please select at least one file to process.', 'warning');
        return;
    }
    
    // Show processing modal
    showProcessingModal(files);
    
    // Create a form for POST submission
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/process.php';
    form.style.display = 'none';
    
    // Add files as input
    const filesInput = document.createElement('input');
    filesInput.type = 'hidden';
    filesInput.name = 'files';
    filesInput.value = JSON.stringify(files);
    form.appendChild(filesInput);
    
    // Add action as input
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'process';
    form.appendChild(actionInput);
    
    // Append form to body and submit
    document.body.appendChild(form);
    form.submit();
}

/**
 * Create multizoom view
 */
function createMultizoom() {
    if (confirm('Create a multi-zoom view of all processed slides?')) {
        // Show processing message
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
}

/**
 * Show a processing modal with the list of files
 * @param {Array} files - Array of file paths to process
 */
function showProcessingModal(files) {
    const modal = document.getElementById('processing-modal');
    const container = document.getElementById('processing-items-container');
    
    // Clear any existing content
    container.innerHTML = '';
    
    // Add each file to the modal
    files.forEach(file => {
        const filename = file.split('/').pop();
        const baseFilename = filename.substring(0, filename.lastIndexOf('.'));
        
        const item = document.createElement('div');
        item.className = 'processing-item';
        item.id = `processing-${baseFilename}`;
        
        item.innerHTML = `
            <div class="processing-header">
                <span class="filename">${filename}</span>
                <span class="status-badge status-pending">Waiting...</span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: 0%"></div>
            </div>
            <div class="progress-details">
                <span class="progress-message">Preparing to process...</span>
            </div>
        `;
        
        container.appendChild(item);
    });
    
    // Show the modal
    modal.style.display = 'block';
}

/**
 * Show progress overlay
 * @param {string} message - Message to display
 */
function showProgress(message) {
    // Remove any existing overlay
    const existingOverlay = document.querySelector('.progress-overlay');
    if (existingOverlay) {
        existingOverlay.remove();
    }
    
    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'progress-overlay';
    
    // Create container
    const container = document.createElement('div');
    container.className = 'progress-container';
    
    // Add message
    const messageElem = document.createElement('h3');
    messageElem.textContent = message;
    
    // Add spinner
    const loader = document.createElement('div');
    loader.className = 'loader';
    
    // Build and append to document
    container.appendChild(messageElem);
    container.appendChild(loader);
    overlay.appendChild(container);
    document.body.appendChild(overlay);
}

/**
 * Show alert message
 * @param {string} message - Message to display
 * @param {string} type - Alert type: success, error, warning, info
 */
function showAlert(message, type = 'info') {
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span>${message}</span>
            <button type="button" class="alert-close">&times;</button>
        </div>
    `;
    
    // Add close functionality
    const closeBtn = alert.querySelector('.alert-close');
    closeBtn.addEventListener('click', function() {
        alert.remove();
    });
    
    // Insert at the top of the container
    const container = document.querySelector('.container');
    container.insertBefore(alert, container.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}