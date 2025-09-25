<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';
$stmt_upcoming = $pdo->prepare("SELECT * FROM courses WHERE course_date >= CURDATE() ORDER BY course_date ASC LIMIT 2");
$stmt_upcoming->execute();
$upcoming_courses = $stmt_upcoming->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Course Dates</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.13/index.global.min.js'></script>
    <style>
        :root { --fc-border-color: var(--border-grey); --fc-today-bg-color: rgba(52, 152, 219, 0.15); }
        .fc-event { cursor: pointer; }
        /* Style for the description tooltip */
        #tooltip { position: absolute; z-index: 100; background: #333; color: white; padding: 10px 15px; border-radius: 5px; font-size: 14px; max-width: 300px; display: none; }
    </style>
</head>
<body>
    <div id="tooltip"></div>
    <div class="app-container">
        <?php include '../includes/user_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header"><h1>Course Dates</h1></header>
            <div class="app-content">
                <div class="course-page-container">
                    <div class="upcoming-courses-list">
                        <p>We have training courses running throughout the year. Please see below for our upcoming courses and register with us to join a course here.</p>
                        <h2>Upcoming courses</h2>
                        <?php foreach($upcoming_courses as $course): ?>
                        <div class="upcoming-card"><div class="card-date-banner"><?php echo date('d - m F Y', strtotime($course['course_date'])); ?></div><h3><?php echo htmlspecialchars($course['title']); ?></h3><a href="course_details.php?id=<?php echo $course['id']; ?>" class="btn-outline">View Detail</a></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="calendar-container card"><div id='calendar'></div></div>
                </div>
            </div>
        </main>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var tooltipEl = document.getElementById('tooltip');

        var calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'dayGridMonth',
          headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
          height: 'auto', 
          events: '/api/get_courses.php',

         
          displayEventTime: true, // This will show the start/end times on events
          eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
          
          eventMouseEnter: function(info) {
            tooltipEl.innerHTML = info.event.extendedProps.description;
            tooltipEl.style.display = 'block';
            tooltipEl.style.left = info.jsEvent.pageX + 10 + 'px';
            tooltipEl.style.top = info.jsEvent.pageY + 10 + 'px';
          },

          eventMouseLeave: function(info) {
            tooltipEl.style.display = 'none';
          },
          // --- END NEW OPTIONS ---

          eventClick: function(info) {
            window.location.href = `course_details.php?id=${info.event.id}`;
          }
        });
        calendar.render();
      });
    </script>
</body>
</html>