<aside class="app-sidebar">
    <h3>CPD Admin</h3>
    <nav class="sidebar-nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="courses.php">Manage Courses</a>
        <a href="users.php">Manage Users</a>
        <a href="view_log.php">Activity Log</a>
        <a href="../logout.php">Logout</a>
    </nav>
</aside>

<div id="notification-modal" class="notification-modal">
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('notification-modal');
    const params = new URLSearchParams(window.location.search);
    let message = '';
    let type = '';

    if (params.has('status')) {
        const status = params.get('status');
        if (status === 'enrolled') {
            message = 'Successfully enrolled!';
        } else if (status === 'deleted' || status === 'cancelled') {
            message = 'Record successfully deleted!';
        } else {
            message = 'Record saved successfully!';
        }
        type = 'success';
    } else if (params.has('error')) {
        message = 'Operation failed. Please try again.';
        type = 'error';
    }

    if (message) {
        modal.textContent = message;
        modal.className = 'notification-modal'; // Reset classes
        modal.classList.add(type);
        modal.classList.add('show');
        setTimeout(() => {
            modal.classList.remove('show');
        }, 4000);
    }
});
</script>