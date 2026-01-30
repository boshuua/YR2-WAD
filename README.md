# CPD Portal & API

A Continuing Professional Development (CPD) management system with a PHP REST API backend and Angular frontend portal.

## Overview

This project consists of two main components:

- **cpd-api**: PHP-based REST API with PostgreSQL database support
- **cpd-portal**: Angular-based frontend application

The system provides secure user authentication, CSRF protection, and session management for managing CPD activities.

## Features

- 🔐 Session-based authentication with cookie support
- 🛡️ CSRF token protection for state-changing operations
- 📊 PostgreSQL database integration
- 🌐 CORS-enabled API with credential support
- 🎨 Angular-based responsive UI
- ⚙️ Environment-based configuration

## Prerequisites

- PHP 7.4+ with PDO PostgreSQL extension
- PostgreSQL 12+
- Node.js 14+ and npm (for Angular frontend)
- Web server (Apache/Nginx)

## Installation

### Backend Setup (cpd-api)

1. Clone the repository and navigate to the API directory:
