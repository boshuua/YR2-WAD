<?php
require_once '../includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses Calendar</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.13/index.global.min.js'></script>
    <style>
        /* A few tweaks to make the calendar look great with our theme */
        :root {
            --fc-border-color: var(--border-grey);
            --fc-today-bg-color: rgba(52, 152, 219, 0.15);
            --fc-button-bg-color: var(--primary-blue);
            --fc-button-border-color: var(--primary-blue);
            --fc-button-hover-bg-color: var(--dark-blue);
            --fc-button-hover-border-color: var(--dark-blue);
        }
        .fc-event {
             cursor: pointer; /* Makes events look clickable */
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include '../includes/user_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header"><h1>Courses Calendar</h1></header>
            <div class="app-content">
                <div class="card">
                    <div id='calendar'></div>
                </div>
            </div>
        </main>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'dayGridMonth',
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek'
          },
          height: 'auto', 
          events: '/api/get_courses.php',
          eventClick: function(info) {
            window.location.href = `course_details.php?id=${info.event.id}`;
          }
        });
        calendar.render();
      });
    </script>
</body>
</html>