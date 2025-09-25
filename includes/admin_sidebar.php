<aside class="app-sidebar">
    <h3>CPD Admin</h3>
    <nav class="sidebar-nav">
        <a href="/admin/dashboard">Dashboard</a>
        <a href="/admin/courses">Manage Courses</a>
        <a href="/admin/users">Manage Users</a>
        <a href="/admin/logs">Activity Log</a>
        <a href="/logout.php">Logout</a>
    </nav>
</aside>

<div id="notification-modal" class="notification-modal">
</div>

<div id="confirmationModal" class="confirm-modal">
    <div class="confirm-modal-content">
        <div class="confirm-modal-header">
            <h2 id="confirmModalTitle">Confirm Action</h2>
            <span class="close-button">&times;</span>
        </div>
        <div class="confirm-modal-body">
            <p id="confirmModalMessage"></p>
        </div>
        <div class="confirm-modal-footer" id="confirmModalFooter">
            </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Notification Pop-up Logic ---
    const notificationModal = document.getElementById('notification-modal');
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
        const error = params.get('error');
        if (error === 'selfdelete') {
            message = 'You cannot delete your own account.';
        } else {
            message = 'Operation failed. Please try again.';
        }
        type = 'error';
    }

    if (message) {
        notificationModal.textContent = message;
        notificationModal.className = 'notification-modal'; // Reset classes
        notificationModal.classList.add(type);
        notificationModal.classList.add('show');
        setTimeout(() => {
            notificationModal.classList.remove('show');
        }, 4000);
    }

    // --- Confirmation Modal Logic ---
    const modal = document.getElementById('confirmationModal');
    if (!modal) return;

    const modalTitle = document.getElementById('confirmModalTitle');
    const modalMessage = document.getElementById('confirmModalMessage');
    const modalFooter = document.getElementById('confirmModalFooter');
    const closeBtn = modal.querySelector('.close-button');

    function showModal() { modal.style.display = 'block'; }
    function hideModal() { modal.style.display = 'none'; modalFooter.innerHTML = ''; }

    closeBtn.onclick = hideModal;
    window.onclick = (event) => { if (event.target == modal) hideModal(); };
    
    // Listener for simple (single action) confirmation modals
    document.body.addEventListener('click', function(event) {
        if (event.target.matches('.open-confirm-modal')) {
            event.preventDefault();
            const link = event.target;
            const url = link.getAttribute('href');
            const message = link.dataset.message || 'Are you sure?';
            const title = link.dataset.title || 'Confirm Action';
            const btnText = link.dataset.btnText || 'Confirm';
            const btnClass = link.dataset.btnClass || 'btn-danger';

            modalTitle.textContent = title;
            modalMessage.textContent = message;

            modalFooter.innerHTML = `
                <button class="btn btn-secondary" id="modalCancelBtn">Cancel</button>
                <a href="${url}" class="btn ${btnClass}">${btnText}</a>
            `;
            
            modalFooter.querySelector('#modalCancelBtn').onclick = hideModal;
            showModal();
        }
    });

    // Listener for delete (multi-action) confirmation
    document.body.addEventListener('click', function(event) {
        if (event.target.matches('.open-delete-modal')) {
            event.preventDefault();
            const link = event.target;
            const title = link.dataset.title;
            const urlOne = link.dataset.urlOne;
            const urlSeries = link.dataset.urlSeries;

            modalTitle.textContent = `Delete Course: ${title}`;
            modalMessage.textContent = 'Please choose an option. This action cannot be undone.';
            
            let seriesButton = '';
            if (urlSeries) {
                seriesButton = `<a href="${urlSeries}" class="btn btn-danger">Delete Entire</a>`;
            }

            modalFooter.innerHTML = `
                <button class="btn btn-secondary" id="modalCancelBtn">Cancel</button>
                <a href="${urlOne}" class="btn btn-danger">${seriesButton ? 'Delete Single' : 'Delete Course'}</a>
                ${seriesButton}
            `;
            
            modalFooter.querySelector('#modalCancelBtn').onclick = hideModal;
            showModal();
        }
    });
});
</script>