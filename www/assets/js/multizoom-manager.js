/**
 * Multi-Zoom Manager JavaScript
 * 
 * Handles client-side functionality for the Multi-Zoom Views page.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize UI components
    initializeModals();
    
    // Setup refresh button
    document.getElementById('refresh-btn')?.addEventListener('click', function() {
        location.reload();
    });
    
    // Setup create multi-zoom button
    document.getElementById('create-multizoom-btn')?.addEventListener('click', function() {
        showCreateMultizoomModal();
    });
});

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
 * Show the create multi-zoom modal
 */
function showCreateMultizoomModal() {
    const modal = document.getElementById('createMultizoomModal');
    if (!modal) return;
    
    modal.style.display = 'block';
}

/**
 * Create a multi-zoom view
 */
function createMultizoom() {
    const form = document.getElementById('multizoomForm');
    if (!form) return;
    
    // Get name
    const name = form.elements['name'].value;
    if (!name) {
        showNotification('Please enter a name for the multi-zoom view', 'warning');
        return;
    }
    
    // Get selected slides
    const selectedSlides = Array.from(form.elements['slides[]'])
        .filter(checkbox => checkbox.checked)
        .map(checkbox => checkbox.value);
    
    if (selectedSlides.length === 0) {
        showNotification('Please select at least one slide', 'warning');
        return;
    }
    
    // Show loading state
    const createBtn = document.getElementById('createMultizoomBtn');
    const originalText = createBtn.textContent;
    createBtn.textContent = 'Creating...';
    createBtn.disabled = true;
    
    // Send request
    fetch('/api/slides/create_multizoom.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            name: name,
            slides: selectedSlides
        })
    })
    .then(response => response.json())
    .then(data => {
        // Reset button
        createBtn.textContent = originalText;
        createBtn.disabled = false;
        
        // Close modal
        document.getElementById('createMultizoomModal').style.display = 'none';
        
        if (data.status === 'success') {
            showNotification(data.message || 'Multi-zoom view created successfully');
            
            // Reload page after a delay
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showNotification(data.message || 'Error creating multi-zoom view', 'error');
        }
    })
    .catch(error => {
        // Reset button
        createBtn.textContent = originalText;
        createBtn.disabled = false;
        
        showNotification('Error: ' + error.message, 'error');
    });
}

/**
 * Delete a multi-zoom view
 */
function deleteMultizoom(filename) {
    if (!confirm('Are you sure you want to delete this multi-zoom view?')) {
        return;
    }
    
    fetch('/api/slides/delete_multizoom.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            filename: filename
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showNotification('Multi-zoom view deleted successfully');
            
            // Remove from DOM
            const row = document.querySelector(`tr[data-filename="${filename}"]`);
            if (row) {
                row.remove();
            }
        } else {
            showNotification(data.message || 'Error deleting multi-zoom view', 'error');
        }
    })
    .catch(error => {
        showNotification('Error: ' + error.message, 'error');
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