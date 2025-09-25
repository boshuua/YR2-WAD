# Staff CPD & Training Enrolment System

This project is a bespoke web-based system for staff Continuing Professional Development (CPD) and training enrolment, built for the fictional client "Logical View Solutions LTD". It provides a secure, intuitive, and responsive platform for employees to browse and enrol in training courses, and for administrators to manage users, courses, and enrolments.

## Features

The platform is divided into two distinct user roles with a secure login system.

### 👤 Administrator Features
* **User Management (CRUD):** Full control to add, list, edit, and delete staff user accounts.
* **Course Management (CRUD):** Full control to add, list, edit, and delete training courses.
* **Advanced Course Creation:**
    * Set course start times and durations (e.g., in hours and minutes), with the end time calculated automatically.
    * Create recurring courses with monthly or yearly repetitions.
* **Grouped Course View:** The main course list groups recurring courses into a single, easy-to-manage entry.
* **View Series Details:** Ability to view all individual instances of a recurring course series.
* **Course History:** View a complete history of all past courses for archival purposes.
* **Activity Log:** Access a text-based log file that records all user enrolments and cancellations.

### 👥 Standard User Features
* **Interactive Course Calendar:** An intuitive calendar interface (powered by FullCalendar) displays all available courses.
* **Course Details:** Click on any course to view full details, including the start time, duration, description, and assigned trainer.
* **Tooltip Descriptions:** Hovering over a course in the calendar instantly shows the course description in a tooltip.
* **Course Enrolment:** Securely enrol in available courses. The system prevents enrolment in full courses.
* **My Enrolments:** A personal dashboard area to view all upcoming and previously attended courses.
* **Cancel Bookings:** Users can cancel their enrolment for any upcoming course.
* **Email Notifications:** Receive an automatic email confirmation (powered by PHPMailer) upon successfully enrolling in a course.

---

## 🛠️ Technology Stack

* **Backend:** PHP
* **Database:** MySQL
* **Frontend:** HTML5, CSS3, JavaScript
* **Key Libraries:**
    * [FullCalendar](https://fullcalendar.io/) for the interactive course calendar.
    * [PHPMailer](https://github.com/PHPMailer/PHPMailer) for sending reliable SMTP emails.
* **Server Environment:** Designed to be hosted on a standard web server like Apache (via Plesk).

---

## 🚀 Setup and Installation

To get this project running, follow these steps:

1.  **Server Environment:**
    Ensure you have a web server environment (like XAMPP, WAMP, or a Plesk-hosted server) with PHP and MySQL.

2.  **Clone the Repository:**
    Download or clone this repository into your web server's root directory (e.g., `htdocs` or `httpdocs`).

3.  **Database Setup:**
    * Create a new MySQL database.
    * Run the following SQL commands to create the necessary tables:
        ```sql
        CREATE TABLE `users` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `email` varchar(255) NOT NULL,
          `password` varchar(255) NOT NULL,
          `first_name` varchar(100) NOT NULL,
          `last_name` varchar(100) NOT NULL,
          `job_title` varchar(100) NOT NULL,
          `access_level` enum('admin','user') NOT NULL DEFAULT 'user',
          PRIMARY KEY (`id`),
          UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB;

        CREATE TABLE `courses` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `series_id` varchar(255) NULL DEFAULT NULL,
          `title` varchar(255) NOT NULL,
          `course_date` datetime NOT NULL,
          `end_date` datetime NOT NULL,
          `max_attendees` int(11) NOT NULL,
          `description` text NOT NULL,
          `trainer_id` int(11) NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          FOREIGN KEY (`trainer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB;

        CREATE TABLE `enrolments` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `course_id` int(11) NOT NULL,
          PRIMARY KEY (`id`),
          FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
          FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB;
        ```

4.  **Configuration:**
    * **Database Connection:** Open `/includes/db_connect.php` and enter your database name, username, and password.
    * **Email Sending:** Open `/user/enrol.php` and configure your SMTP settings (Host, Username, Password, Port) inside the PHPMailer section.

5.  **Dependencies:**
    * Ensure the **PHPMailer** library files are placed inside the `/includes/phpmailer/` directory.

6.  **Create Initial Users:**
    * It is recommended to create an initial admin and user account directly in the database using hashed passwords. Use a temporary PHP script with `password_hash()` to generate the password strings.

---

## 🔐 Security Practices

* **Password Hashing:** All user passwords are securely hashed using PHP's `password_hash()` BCRYPT algorithm.
* **SQL Injection Prevention:** All database queries are executed using PDO with prepared statements.
* **Role-Based Access Control:** Server-side checks on every secure page ensure users can only access the functionality designated to their role.
* **Session Security:** Sessions are regenerated upon login to prevent session fixation attacks.
