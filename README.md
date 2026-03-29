# YR2-WAD: MOT Tester CPD Portal

This project is a full-stack **Continuing Professional Development (CPD) Management Portal**  designed for **UK MOT Testers**. It provides a platform for vehicle testers to complete their mandatory annual training and assessments as required by the **DVSA (Driver and Vehicle Standards Agency)**.

This is likely a "Year 2 Web Application Development" university project.

## Overview

The application consists of two main parts:

*   **`cpd-portal` (Frontend):** An **Angular v20** single-page application that provides the user interface for testers. Here, they can access their dashboard, browse and complete training courses, and take the final annual assessment.
*   **`cpd-api` (Backend):** A **PHP-based REST API** that powers the frontend. It handles user authentication, serves course and assessment content, and records user progress. It uses a **PostgreSQL** database to store all data.

## Key Features

-   **Specialized Content:** Training and assessment content is tailored for the **2025-2026 MOT training year**, including modules on Electric/Hybrid vehicles, DVSA regulations, and testing procedures.
-   **Role-Based Access:** The API supports different user access levels (e.g., 'admin', 'user').
-   **Course Management:** A structured system for courses, lessons, and multiple-choice questions.
-   **User Progress Tracking:** Records which lessons have been completed and the results of assessments.
-   **Modern Tech Stack:** Built with a recent version of Angular and a clean, helper-driven PHP backend.

## Prerequisites

-   **Backend:**
    -   PHP 8.0+ with the `pdo_pgsql` extension
    -   PostgreSQL 12+
    -   [Composer](https://getcomposer.org/) (optional, but recommended)
-   **Frontend:**
    -   Node.js v18+
    -   Angular CLI v20+

## Setup & Installation

### 1. Backend Setup (`cpd-api`)

The backend is a straightforward PHP application.

1.  **Navigate to the API directory:**
    ```bash
    cd YR2-WAD/cpd-api
    ```

2.  **Install Dependencies (Optional):**
    If you have Composer, you can install the dependencies. The app is configured to work without this step if needed.
    ```bash
    composer install
    ```

3.  **Configure Environment:**
    Copy the example `.env` file and edit it with your local database credentials.
    ```bash
    cp .env.example .env
    ```
    Update `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS`.

4.  **Database Setup:**
    -   Ensure your PostgreSQL server is running.
    -   Create the database specified in your `.env` file.
    -   Connect to the new database and enable the `pgcrypto` extension:
        ```sql
        CREATE EXTENSION IF NOT EXISTS pgcrypto;
        ```
    -   Run the SQL migration scripts located in `/cpd-api/migrations/` against your database to set up the schema and seed the initial training content. It is recommended to run them in numerical order.

5.  **Run the PHP Server:**
    You can use the built-in PHP web server for development. The server must be run from the `api` directory to ensure endpoints are resolved correctly.
    ```bash
    # Run from the /cpd-api/api directory
    cd cpd-api/api
    php -S localhost:8000
    ```

### 2. Frontend Setup (`cpd-portal`)

The frontend is an Angular application managed with the Angular CLI.

1.  **Navigate to the portal directory:**
    ```bash
    cd YR2-WAD/cpd-portal
    ```

2.  **Install Dependencies:**
    ```bash
    npm install
    ```

3.  **Run the Development Server:**
    ```bash
    ng serve
    ```
    The application will be available at `http://localhost:4200/`. The API is expected to be running on `http://localhost:8000`. If your API is on a different port, you will need to update the API base URL in the Angular application's environment files (`src/environments/`).

## Logging In and Testing

### Admin Access

The database is seeded with a default administrator account.

-   **Email:** `admin@test.com`
-   **Password:** `admin123`

### Note for Assessors

To test the standard user workflow (enrolling in courses, completing lessons, and taking assessments), you will need to **create a new user account** using the registration feature on the login page. The admin account should be used for administrative tasks only.
