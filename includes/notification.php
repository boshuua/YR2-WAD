<div id="notification-modal" class="notification-modal">
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('notification-modal');
    // Use URLSearchParams to easily get query parameters
    const params = new URLSearchParams(window.location.search);

    let message = '';
    let type = '';

    // Check for success or error messages in the URL
    if (params.has('status')) {
        message = 'Record saved successfully!';
        type = 'success';
    } else if (params.has('error')) {
        message = 'Operation failed. Please try again.';
        type = 'error';
    }

    if (message) {
        // Set the message and type
        modal.textContent = message;
        modal.className = 'notification-modal'; // Reset classes
        modal.classList.add(type);

        // Show the modal
        modal.classList.add('show');

        // Hide the modal after 4 seconds
        setTimeout(() => {
            modal.classList.remove('show');
        }, 4000);
    }
});
</script>