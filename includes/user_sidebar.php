<aside class="app-sidebar">
    <h3>CPD Portal</h3>
    <nav class="sidebar-nav">
        <a href="/dashboard">Dashboard</a>
        <a href="/courses">Available Courses</a>
        <a href="/my-courses">My Enrolments</a>
        <a href="/profile">My Profile</a>
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
    document.addEventListener('DOMContentLoaded', function () {
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
        document.addEventListener('DOMContentLoaded', function () {
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
            document.body.addEventListener('click', function (event) {
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
                });
            });
</script>