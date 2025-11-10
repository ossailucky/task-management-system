# Task Management API

A RESTful API for managing tasks with user authentication built with Laravel 12 and Sanctum.

## Features

-  User registration and authentication (Laravel Sanctum)
-  Complete CRUD operations for tasks
-  Task ownership validation
-  Filter tasks by status
-  Pagination support
-  Comprehensive validation and error handling
-  Feature and unit tests included

## Requirements

- PHP >= 8.2
- Composer
- MySQL
- Laravel 12.x

## Installation Steps

### 1. Create New Laravel 12 Project

```bash
composer create-project laravel/laravel task-management-api
cd task-management-api
```

### 2. Install Laravel Sanctum

```bash
composer require laravel/sanctum
php artisan install:api
```

### 3. Environment Setup

Edit `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_management
DB_USERNAME=root
DB_PASSWORD=your_password
```



### 4. Create Database Tables

```bash
# Create the tasks migration
php artisan make:migration create_tasks_table

# Run migrations
php artisan migrate
```

### 5. Create Models and Controllers

```bash
# Create Task model with migration and factory
php artisan make:model Task -mf

# Create controllers
php artisan make:controller Api/AuthController
php artisan make:controller Api/TaskController --api
```

### 6. Start Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## API Documentation

### Base URL
```
http://localhost:8000/api
```

### Authentication Endpoints

#### Register User
```http
POST /api/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response (201):**
```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    },
    "token": "1|xxxxxxxxxxxx"
}
```

#### Login
```http
POST /api/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response (200):**
```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    },
    "token": "2|xxxxxxxxxxxx"
}
```

#### Logout
```http
POST /api/logout
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "message": "Logged out successfully"
}
```

### Task Endpoints

All task endpoints require authentication. Include the token in the Authorization header:
```
Authorization: Bearer {your-token}
```

#### Get All Tasks (with pagination and filtering)
```http
GET /api/tasks?status=pending&page=1&per_page=10
Authorization: Bearer {token}
```

**Query Parameters:**
- `status` (optional): Filter by status (pending, in-progress, completed)
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 15)

**Response (200):**
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "title": "Complete project",
            "description": "Finish the Laravel API",
            "status": "pending",
            "user_id": 1,
            "created_at": "2024-01-01T10:00:00.000000Z",
            "updated_at": "2024-01-01T10:00:00.000000Z"
        }
    ],
    "first_page_url": "http://localhost:8000/api/tasks?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost:8000/api/tasks?page=1",
    "links": [
        {
            "url": null,
            "label": "&laquo; Previous",
            "active": false
        },
        {
            "url": "http://localhost:8000/api/tasks?page=1",
            "label": "1",
            "active": true
        },
        {
            "url": null,
            "label": "Next &raquo;",
            "active": false
        }
    ],
    "next_page_url": null,
    "path": "http://localhost:8000/api/tasks",
    "per_page": 15,
    "prev_page_url": null,
    "to": 1,
    "total": 1
}
```

#### Create Task
```http
POST /api/tasks
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "New Task",
    "description": "Task description",
    "status": "pending"
}
```

**Response (201):**
```json
{
    "id": 1,
    "title": "New Task",
    "description": "Task description",
    "status": "pending",
    "user_id": 1,
    "created_at": "2024-01-01T10:00:00.000000Z",
    "updated_at": "2024-01-01T10:00:00.000000Z"
}
```

#### Get Single Task
```http
GET /api/tasks/{id}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "id": 1,
    "title": "New Task",
    "description": "Task description",
    "status": "pending",
    "user_id": 1,
    "created_at": "2024-01-01T10:00:00.000000Z",
    "updated_at": "2024-01-01T10:00:00.000000Z"
}
```

#### Update Task
```http
PUT /api/tasks/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "Updated Task",
    "description": "Updated description",
    "status": "in-progress"
}
```

**Response (200):**
```json
{
    "id": 1,
    "title": "Updated Task",
    "description": "Updated description",
    "status": "in-progress",
    "user_id": 1,
    "created_at": "2024-01-01T10:00:00.000000Z",
    "updated_at": "2024-01-01T10:30:00.000000Z"
}
```

#### Delete Task
```http
DELETE /api/tasks/{id}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "message": "Task deleted successfully"
}
```

## Error Responses

### Validation Error (422)
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": [
            "The email field is required."
        ]
    }
}
```

### Unauthorized (401)
```json
{
    "message": "Unauthenticated."
}
```

### Forbidden (403)
```json
{
    "message": "This action is unauthorized."
}
```

### Not Found (404)
```json
{
    "message": "Task not found"
}
```

## Testing

Run the test suite:

```bash
php artisan test
```

Run specific test:

```bash
php artisan test --filter TaskControllerTest
```

Generate code coverage:

```bash
php artisan test --coverage
```



## Design Decisions Made

### 1. **Authentication with Laravel Sanctum**
- Native Laravel 12 support with `php artisan install:api`
- Lightweight token-based authentication
- Perfect for SPAs and mobile applications
- Simpler than Passport for API-only authentication

### 2. **Laravel 12 Features**
- Utilizes improved type safety with PHP 8.2+
- Enhanced testing capabilities
- Better performance and security
- Streamlined API installation process

### 3. **Controller Organization**
- Controllers placed in `App\Http\Controllers\Api` namespace
- Clean separation of API logic
- RESTful resource controller for tasks

### 4. **Form Request Validation**
- Inline validation in controllers for simplicity
- Can be extracted to Form Request classes for larger projects
- Clear and maintainable validation rules

### 5. **Authorization**
- Direct ownership checks in controller methods
- Can be enhanced with Laravel Policies for complex scenarios
- Ensures users only access their own tasks

### 6. **Status Enum**
- Uses Laravel's native enum validation
- Type-safe status values
- Easy to extend with additional statuses

### 7. **Pagination**
- Laravel's built-in pagination
- Customizable per-page limits
- Includes metadata for frontend implementation

### 8. **Error Handling**
- Consistent JSON responses
- Proper HTTP status codes
- Descriptive error messages

## Laravel 12 Specific Features Used

-  `php artisan install:api` - Sanctum setup
-  Improved type declarations
-  Enhanced testing framework
-  Better error handling
-  Streamlined middleware configuration

## Security Considerations

-  Password hashing with bcrypt
-  CSRF protection (API uses token auth)
-  SQL injection protection (Eloquent ORM)
-  Mass assignment protection
-  Authorization checks
-  Rate limiting (configurable)
-  API token management
