# 🎓 CPD Portal (Continuing Professional Development)

A full-stack web application for managing and tracking Continuing Professional Development (CPD). This platform provides a secure admin dashboard for managing users and courses, and a user-facing portal for employees to complete training and track their progress.

---

## 🛠 Tech Stack

This project is built using Angular, PHP, and PostgreSQL.

![Angular](https://img.shields.io/badge/Angular-DD0031?style=for-the-badge&logo=angular&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)

### Language Breakdown (by File Count)

This is a snapshot of the repository's language distribution.

![TypeScript: 25.5%](https://img.shields.io/badge/TypeScript-33%25-3178C6?style=flat&logo=typescript&logoColor=white)
![PHP: 31.2%](https://img.shields.io/badge/PHP-24%25-777BB4?style=flat&logo=php&logoColor=white)
![HTML: 14.7%](https://img.shields.io/badge/HTML-22%25-E34F26?style=flat&logo=html5&logoColor=white)
![CSS: 28.6%](https://img.shields.io/badge/CSS-20%25-1572B6?style=flat&logo=css3&logoColor=white)

---

## 🚀 Overview

This application is split into two main parts:

* **`cpd-portal` (Frontend):** An Angular standalone application that serves as the user-facing portal. It handles user login, course browsing, and progress tracking.
* **`cpd-api` (Backend):** A simple REST API built with PHP. It manages session-based authentication, provides CRUD operations for users and courses, and logs all major activities to the database.

---

## 🏁 Getting Started

To run this project locally, you will need to set up both the backend API and the frontend application.

### Prerequisites

* [Node.js and npm](https://nodejs.org/)
* [Angular CLI](https://angular.dev/tools/cli) (`npm install -g @angular/cli`)
* [PHP](https://www.php.net/downloads) (v8.0+ recommended)
* [PostgreSQL](https://www.postgresql.org/download/)

---

### 1. Backend API (`cpd-api`)

1.  **Database Setup:**
    * Start your PostgreSQL server.
    * Create a new database (e.g., `mydb`).
    * Create a user and password (e.g., `dev` / `pass`).
    * Update your connection credentials in `cpd-api/config/database.php`.
    * You will need to manually create the database tables (`users`, `courses`, `activity_log`, etc.) based on the queries in the PHP API files.

2.  **Run the PHP Server:**
    * The frontend expects the API to be running on `http://localhost:8000`.
    * Open a terminal in the root of the repository.
    * Run the following command to serve the `cpd-api` directory:
        ```bash
        php -S localhost:8000 -t cpd-api/
        ```

---

### 2. Frontend Portal (`cpd-portal`)

1.  **Navigate to the frontend directory:**
    ```bash
    cd cpd-portal
    ```

2.  **Install dependencies:**
    ```bash
    npm install
    ```

3.  **Run the Angular development server:**
    ```bash
    ng serve
    ```

4.  **Access the application:**
    * Open your browser and navigate to `http://localhost:4200/`.
    * The application will automatically reload if you change any of the source files.

---

### 💡 A Note on Auto-Updating Percentages

You asked if the language percentage badges can auto-update. The percentages in this file are a **static snapshot** based on the files I analyzed.

To make them update automatically, you would need to set up a CI/CD pipeline (like **GitHub Actions**). This pipeline could run a script on every push to:
1.  Analyze the repository's file extensions.
2.  Calculate the new percentages.
3.  Dynamically generate new badge URLs.
4.  Commit the updated `README.md` file back to your repository.
