# CPD API - Backend Documentation

## Setup Instructions

### 1. Install Dependencies

Install Composer dependencies (optional but recommended):

```bash
cd cpd-api
composer install
```

**Note:** The application works without Composer. The helper functions are loaded via `files` autoloading in `composer.json` and directly included in API files.

### 2. Configure Environment

Copy `.env.example` to `.env` and update with your database credentials:

```bash
cp .env.example .env
```

Edit `.env` file:

```env
DB_HOST=localhost
DB_NAME=mydb
DB_USER=dev
DB_PASS=your_password
DB_PORT=5432

APP_ENV=development
APP_DEBUG=true

CORS_ALLOWED_ORIGIN=http://localhost:4200
```

### 3. Database Setup

Ensure PostgreSQL is running with:
- Database created
- pgcrypto extension enabled: `CREATE EXTENSION IF NOT EXISTS pgcrypto;`

### 4. Start PHP Server

```bash
php -S localhost:8000
```

---

## Helper Functions Overview

The API now includes helper functions to reduce code duplication and improve maintainability.

### Authentication Helper (`helpers/auth_helper.php`)

**Session Management:**
- `ensureSessionStarted()` - Start session if not already started
- `isAuthenticated()` - Check if user is logged in
- `getCurrentUserId()` - Get current user's ID
- `getCurrentUserEmail()` - Get current user's email
- `getCurrentUserAccessLevel()` - Get user's access level
- `isAdmin()` - Check if current user is admin

**Authentication Guards:**
- `requireAuth($message)` - Require authentication, exits with 401 if not authenticated
- `requireAdmin($message)` - Require admin privileges, exits with 403 if not admin

**Session Operations:**
- `setUserSession($userId, $email, $firstName, $lastName, $accessLevel)` - Set user session data after login
- `clearUserSession()` - Clear session (logout)
- `getCurrentUserData()` - Get all current user data

**Example Usage:**

```php
// Require admin authentication
requireAdmin();

// Or check manually
if (!isAuthenticated()) {
    sendUnauthorized("Please log in");
}

if (!isAdmin()) {
    sendForbidden("Admin access required");
}

// Get current user info
$userId = getCurrentUserId();
$email = getCurrentUserEmail();
```

---

### Response Helper (`helpers/response_helper.php`)

**JSON Responses:**
- `sendJsonResponse($data, $statusCode, $exit)` - Send JSON response
- `sendSuccess($data, $statusCode)` - Send success response
- `sendError($message, $statusCode)` - Send error response

**HTTP Status Helpers:**
- `sendOk($data)` - 200 OK
- `sendCreated($data)` - 201 Created
- `sendNoContent()` - 204 No Content
- `sendBadRequest($message)` - 400 Bad Request
- `sendUnauthorized($message)` - 401 Unauthorized
- `sendForbidden($message)` - 403 Forbidden
- `sendNotFound($message)` - 404 Not Found
- `sendMethodNotAllowed($message)` - 405 Method Not Allowed
- `sendValidationError($errors, $message)` - 422 Unprocessable Entity
- `sendServerError($message)` - 500 Internal Server Error
- `sendServiceUnavailable($message)` - 503 Service Unavailable

**Request Helpers:**
- `requireMethod($allowedMethods)` - Ensure request method matches (sends 405 if not)
- `getJsonInput($assoc)` - Get JSON input from request body
- `handleCorsPrelight()` - Handle CORS preflight OPTIONS request

**Example Usage:**

```php
// Require POST method
requireMethod('POST');

// Or multiple methods
requireMethod(['POST', 'PUT']);

// Get JSON input
$data = getJsonInput();

// Send responses
sendOk(["message" => "Success"]);
sendCreated(["id" => 123, "message" => "User created"]);
sendBadRequest("Invalid input");
sendNotFound("Course not found");
sendValidationError(['email' => 'Invalid format'], "Validation failed");
```

---

### Validation Helper (`helpers/validation_helper.php`)

**Required Field Validation:**
- `validateRequired($data, $requiredFields)` - Returns array of missing fields
- `requireFields($data, $requiredFields)` - Require fields, exits with 422 if missing

**Email Validation:**
- `isValidEmail($email)` - Check if email is valid
- `requireValidEmail($email, $fieldName)` - Require valid email, exits with 400 if invalid
- `sanitizeEmail($email)` - Sanitize email

**String Validation:**
- `sanitizeString($string)` - Sanitize for XSS prevention
- `sanitizeHtml($html)` - Sanitize HTML (allow safe tags only)
- `isValidLength($string, $min, $max)` - Check string length

**Number Validation:**
- `isValidInt($value)` - Check if valid integer
- `isPositiveInt($value)` - Check if positive integer
- `getInt($value, $default)` - Get sanitized integer with default

**List Validation:**
- `isInList($value, $allowedValues)` - Check if value in allowed list
- `requireInList($value, $allowedValues, $fieldName)` - Require value in list

**Date Validation:**
- `isValidDate($date)` - Validate YYYY-MM-DD format
- `isValidDateTime($datetime)` - Validate YYYY-MM-DD HH:MM:SS format

**Utility Functions:**
- `getValue($data, $key, $default)` - Extract value with default
- `validatePassword($password, $minLength)` - Validate password strength
- `getClientIp()` - Get client IP address

**Example Usage:**

```php
$data = getJsonInput();

// Validate required fields
requireFields($data, ['first_name', 'email', 'password']);

// Validate email
requireValidEmail($data->email);

// Validate allowed values
requireInList($data->access_level, ['admin', 'user'], 'access_level');

// Get optional fields with defaults
$last_name = getValue($data, 'last_name', '');
$job_title = getValue($data, 'job_title', '');

// Validate password
$validation = validatePassword($data->password, 8);
if (!$validation['valid']) {
    sendValidationError($validation['errors'], "Password validation failed");
}
```

---

### Environment Helper (`helpers/env_helper.php`)

**Functions:**
- `loadEnv()` - Load environment variables from `.env` file
- `env($key, $default)` - Get environment variable with default value

**Auto-loaded when including `config/database.php`**

**Example Usage:**

```php
$dbHost = env('DB_HOST', 'localhost');
$debug = env('APP_DEBUG', false);
```

---

## Updated API File Pattern

Here's the recommended pattern for API endpoints:

```php
<?php
// Load configuration and helpers
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/validation_helper.php';
include_once '../helpers/log_helper.php';

// Handle CORS preflight
handleCorsPrelight();

// Require HTTP method
requireMethod('POST');

// Require authentication
requireAdmin(); // or requireAuth()

// Get and validate input
$data = getJsonInput();
requireFields($data, ['field1', 'field2']);

// Get database connection
$database = new Database();
$db = $database->getConn();

// Execute query
$query = "...";
$stmt = $db->prepare($query);
$stmt->bindParam(':param', $data->field);

// Send response
if ($stmt->execute()) {
    log_activity($db, getCurrentUserId(), getCurrentUserEmail(), "Action", "Details");
    sendCreated(["message" => "Success"]);
} else {
    sendServiceUnavailable("Operation failed");
}
?>
```

---

## Migration Guide

### Before (Old Pattern):

```php
<?php
session_start();
include_once '../config/database.php';

if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Access Denied"]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->email) || !isset($data->password)) {
    http_response_code(400);
    echo json_encode(["message" => "Missing fields"]);
    exit();
}

// ... database operations ...

http_response_code(201);
echo json_encode(["message" => "Success"]);
?>
```

### After (New Pattern):

```php
<?php
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/validation_helper.php';

handleCorsPrelight();
requireMethod('POST');
requireAdmin();

$data = getJsonInput();
requireFields($data, ['email', 'password']);
requireValidEmail($data->email);

// ... database operations ...

sendCreated(["message" => "Success"]);
?>
```

---

## Benefits

1. **Less Code**: 10-15 lines reduced per file
2. **Consistency**: Standardized responses across all endpoints
3. **Security**: Centralized validation and sanitization
4. **Maintainability**: Update logic in one place
5. **Readability**: Clear, self-documenting code
6. **Type Safety**: Better parameter validation
7. **Error Handling**: Consistent error messages and status codes

---

## Next Steps

1. **Install Composer** (optional but recommended):
   ```bash
   composer install
   ```

2. **Update remaining API files** to use the new helper pattern (examples provided in `api/admin_create_user.php` and `api/get_courses.php`)

3. **Add additional helpers** as needed:
   - Database model classes
   - Query builders
   - Caching helpers
   - File upload helpers

4. **Run tests** to ensure everything works:
   - Test authentication flows
   - Test validation errors
   - Test CORS headers
   - Test all API endpoints

---

## File Structure

```
cpd-api/
├── api/                          # API endpoints
│   ├── user_login.php
│   ├── admin_create_user.php    # ✅ Updated with helpers
│   ├── get_courses.php          # ✅ Updated with helpers
│   └── ...
├── config/
│   └── database.php             # ✅ Uses environment variables
├── helpers/
│   ├── env_helper.php           # ✅ Environment configuration
│   ├── auth_helper.php          # ✅ Authentication & sessions
│   ├── response_helper.php      # ✅ JSON responses
│   ├── validation_helper.php    # ✅ Input validation
│   └── log_helper.php           # Activity logging
├── .env                         # ✅ Environment variables (git ignored)
├── .env.example                 # ✅ Environment template
├── .gitignore                   # ✅ Ignore sensitive files
├── composer.json                # ✅ Composer configuration
└── README.md                    # ✅ This file
```

---

## Support

For issues or questions, refer to the inline documentation in each helper file or check the example implementations in:
- `cpd-api/api/admin_create_user.php:3-59`
- `cpd-api/api/get_courses.php:1-51`
