<?php
require_once '../includes/auth_check.php';
require_admin();

// --- CONFIGURATION ---
$records_per_page = 10; // How many log entries to show per page

// --- ACTIVITY LOG PAGINATION & DATA FETCHING ---
$activity_page = isset($_GET['activity_page']) && is_numeric($_GET['activity_page']) ? (int)$_GET['activity_page'] : 1;
$activity_offset = ($activity_page - 1) * $records_per_page;
$activity_log_path = __DIR__ . '/../logs/user_activity.log';
$all_activity_logs = [];
if (file_exists($activity_log_path)) {
    $all_activity_logs = file($activity_log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $all_activity_logs = array_reverse($all_activity_logs);
}
$total_activity_records = count($all_activity_logs);
$total_activity_pages = ceil($total_activity_records / $records_per_page);
$activity_logs_for_page = array_slice($all_activity_logs, $activity_offset, $records_per_page);

// --- EMAIL LOG PAGINATION & DATA FETCHING ---
$email_page = isset($_GET['email_page']) && is_numeric($_GET['email_page']) ? (int)$_GET['email_page'] : 1;
$email_offset = ($email_page - 1) * $records_per_page;
$email_log_path = __DIR__ . '/../logs/email_log.json';
$all_email_logs = [];
if (file_exists($email_log_path)) {
    $all_email_logs = json_decode(file_get_contents($email_log_path), true);
    $all_email_logs = is_array($all_email_logs) ? array_reverse($all_email_logs) : [];
}
$total_email_records = count($all_email_logs);
$total_email_pages = ceil($total_email_records / $records_per_page);
$email_logs_for_page = array_slice($all_email_logs, $email_offset, $records_per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Activity & Email Logs</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 900px; position: relative; }
        .close-button { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .preview-iframe { width: 100%; height: 60vh; border: 1px solid #ccc; }
        .btn-sm { padding: 5px 10px; font-size: 13px; }
    </style>
</head>
<body>
    <div id="emailPreviewModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Email Preview</h2>
            <iframe id="preview-iframe" class="preview-iframe"></iframe>
        </div>
    </div>

    <div class="app-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header"><h1>System Logs</h1></header>
            <div class="app-content">

                <div class="card">
                    <h3>User Activity Feed</h3>
                    <table id="activity-log-table">
                        <thead>
                            <tr><th>Date & Time</th><th>User</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activity_logs_for_page)): ?>
                                <tr><td colspan="3">No activity has been logged.</td></tr>
                            <?php else: ?>
                                <?php foreach ($activity_logs_for_page as $line):
                                    $pattern = '/^\[(.*?)\]\s\[User:\s(.*?)\]\s(.*)$/';
                                    if (preg_match($pattern, $line, $matches)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($matches[1]); ?></td>
                                            <td><?php echo htmlspecialchars($matches[2]); ?></td>
                                            <td><?php echo htmlspecialchars($matches[3]); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $total_activity_pages; $i++): ?>
                            <a href="?activity_page=<?php echo $i; ?>&email_page=<?php echo $email_page; ?>" class="<?php if ($activity_page == $i) echo 'active'; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="card" style="margin-top: 30px;">
                    <h3>Sent Email History</h3>
                    <table id="email-log-table">
                        <thead>
                            <tr><th>Timestamp</th><th>Recipient</th><th>Subject</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($email_logs_for_page)): ?>
                                <tr><td colspan="4">No emails have been logged.</td></tr>
                            <?php else: ?>
                                <?php foreach ($email_logs_for_page as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                                        <td><?php echo htmlspecialchars($log['recipient']); ?></td>
                                        <td><?php echo htmlspecialchars($log['subject']); ?></td>
                                        <td>
                                            <button class="btn btn-sm preview-btn" data-email-body="<?php echo htmlspecialchars($log['body']); ?>">Preview</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                         <?php for ($i = 1; $i <= $total_email_pages; $i++): ?>
                            <a href="?activity_page=<?php echo $activity_page; ?>&email_page=<?php echo $i; ?>" class="<?php if ($email_page == $i) echo 'active'; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- EMAIL PREVIEW MODAL LOGIC ---
        const modal = document.getElementById('emailPreviewModal');
        const iframe = document.getElementById('preview-iframe');
        const closeBtn = document.querySelector('.close-button');

        const attachPreviewListeners = () => {
            document.querySelectorAll('.preview-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const emailBody = this.getAttribute('data-email-body');
                    iframe.srcdoc = emailBody;
                    modal.style.display = 'block';
                });
            });
        };
        
        attachPreviewListeners(); // Attach to initial buttons

        closeBtn.onclick = () => modal.style.display = 'none';
        window.onclick = (event) => {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        };

        // --- AUTO-UPDATE LOGIC ---
        const params = new URLSearchParams(window.location.search);
        const currentActivityPage = parseInt(params.get('activity_page') || '1', 10);
        const currentEmailPage = parseInt(params.get('email_page') || '1', 10);

        // Function to update the activity log table
        const fetchActivityLogs = async () => {
            if (currentActivityPage > 1) return;
            
            try {
                const response = await fetch('/api/get_activity_log.php');
                const logs = await response.json();
                const tableBody = document.querySelector('#activity-log-table tbody');
                tableBody.innerHTML = '';

                if (logs.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="3">No activity has been logged.</td></tr>';
                } else {
                    logs.forEach(log => {
                        const row = tableBody.insertRow();
                        row.insertCell(0).textContent = log.timestamp;
                        row.insertCell(1).textContent = log.user;
                        row.insertCell(2).textContent = log.action;
                    });
                }
            } catch (error) {
                console.error('Failed to fetch activity logs:', error);
            }
        };

        // Function to update the email log table
        const fetchEmailLogs = async () => {
            if (currentEmailPage > 1) return;

            try {
                const response = await fetch('/api/get_email_log.php');
                const logs = await response.json();
                const tableBody = document.querySelector('#email-log-table tbody');
                tableBody.innerHTML = '';

                if (logs.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="4">No emails have been logged.</td></tr>';
                } else {
                    logs.forEach(log => {
                        const row = tableBody.insertRow();
                        row.insertCell(0).textContent = log.timestamp;
                        row.insertCell(1).textContent = log.recipient;
                        row.insertCell(2).textContent = log.subject;
                        const actionCell = row.insertCell(3);
                        const button = document.createElement('button');
                        button.className = 'btn btn-sm preview-btn';
                        button.textContent = 'Preview';
                        button.dataset.emailBody = log.body;
                        actionCell.appendChild(button);
                    });
                    attachPreviewListeners(); // Re-attach listeners to new buttons
                }
            } catch (error) {
                console.error('Failed to fetch email logs:', error);
            }
        };

        // Set intervals if on the first page of the respective log
        if (currentActivityPage === 1) {
            setInterval(fetchActivityLogs, 5000);
        }
        if (currentEmailPage === 1) {
            setInterval(fetchEmailLogs, 5500);
        }
    });
    </script>
</body>
</html>