<?php
// Simple feedback widget that can be embedded in other pages
// This creates a floating feedback button that opens the feedback form in a modal
?>

<!-- Feedback Widget -->
<div id="feedbackWidget" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
    <button type="button" class="btn btn-primary btn-lg rounded-circle shadow-lg" 
            data-bs-toggle="modal" data-bs-target="#feedbackModal"
            style="width: 60px; height: 60px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
        <i class="bi bi-chat-heart" style="font-size: 1.5rem;"></i>
    </button>
</div>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h5 class="modal-title" id="feedbackModalLabel">
                    <i class="bi bi-chat-heart"></i> Share Your Experience
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <iframe src="feedback/index.php" 
                        style="width: 100%; height: 600px; border: none; border-radius: 10px;"
                        id="feedbackIframe">
                </iframe>
            </div>
        </div>
    </div>
</div>

<!-- Include Bootstrap if not already included -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Handle feedback form submission
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('feedbackModal');
    const iframe = document.getElementById('feedbackIframe');
    
    // Listen for messages from the iframe
    window.addEventListener('message', function(event) {
        if (event.data.type === 'feedbackSubmitted') {
            // Close the modal
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
            
            // Show success message
            showNotification('Thank you for your feedback!', 'success');
        }
    });
    
    // Reset iframe when modal is closed
    modal.addEventListener('hidden.bs.modal', function() {
        iframe.src = iframe.src;
    });
});

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}
</script>
