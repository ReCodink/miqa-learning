# Backend Error Handling Analysis

## Overview

The MIQA Learning backend project is a Laravel application with error handling implemented across multiple layers. The error handling follows a distributed pattern with try-catch blocks in controllers and exception throwing in services.

## Error Handling Locations

### 1. **API Controllers** - Primary Error Handling Layer

**Path:** `src/app/Http/Controllers/Api/`

All API controllers implement comprehensive error handling with try-catch blocks:

#### Controllers Implementing Error Handling:

- **[AuthController.php](src/app/Http/Controllers/Api/AuthController.php)** - Authentication error handling
- **[TopicController.php](src/app/Http/Controllers/Api/TopicController.php)** - Topic resource operations (CRUD)
- **[SubjectController.php](src/app/Http/Controllers/Api/SubjectController.php)** - Subject management
- **[ClassRoomController.php](src/app/Http/Controllers/Api/ClassRoomController.php)** - Classroom management
- **[TeacherController.php](src/app/Http/Controllers/Api/TeacherController.php)** - Teacher CRUD operations
- **[StudentController.php](src/app/Http/Controllers/Api/StudentController.php)** - Student management
- **[SubjectExamController.php](src/app/Http/Controllers/Api/SubjectExamController.php)** - Exam creation and management
- **[ExamQuestionController.php](src/app/Http/Controllers/Api/ExamQuestionController.php)** - Question management
- **[QuestionOptionController.php](src/app/Http/Controllers/Api/QuestionOptionController.php)** - Multiple choice options
- **[ExamAttemptController.php](src/app/Http/Controllers/Api/ExamAttemptController.php)** - Exam attempt tracking
- **[ClassStudentController.php](src/app/Http/Controllers/Api/ClassStudentController.php)** - Student enrollment
- **[ClassSubjectController.php](src/app/Http/Controllers/Api/ClassSubjectController.php)** - Subject assignment
- **[QuestionAnswerController.php](src/app/Http/Controllers/Api/QuestionAnswerController.php)** - Answer grading
- **[StudentExamController.php](src/app/Http/Controllers/Api/StudentExamController.php)** - Student exam operations
- **[StatisticsController.php](src/app/Http/Controllers/Api/StatisticsController.php)** - Statistics collection
- **[UserController.php](src/app/Http/Controllers/Api/UserController.php)** - User management

#### Error Handling Pattern in Controllers:

```php
try {
    // Business logic
    return response()->json([...], 200);
} catch (ModelNotFoundException $e) {
    return response()->json([
        'success' => false,
        'message' => 'Resource not found'
    ], 404);
} catch (ValidationException $e) {
    return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $e->errors()
    ], 422);
} catch (\Exception $e) {
    return response()->json([
        'success' => false,
        'message' => 'Operation failed',
        'error' => config('app.debug') ? $e->getMessage() : null
    ], 500);
}
```

### 2. **Service Layer** - Business Logic Error Handling

**Path:** `src/app/Services/`

Services throw custom exceptions for validation and business logic errors:

#### Key Services with Error Handling:

- **QuestionAnswerService.php** - Validates grading logic
  - Throws: Answer already exists, auto-grading restrictions, points validation
  - Example: `throw new \Exception('Points earned cannot exceed maximum points');`

- **QuestionOptionService.php** - Validates question options
  - Throws: Question type validation, option count validation
  - Example: `throw new \Exception('Options can only be added to multiple choice questions');`

- **ExamAttemptService.php** - Validates exam attempts
- **ExamQuestionService.php** - Question validation
- **SubjectExamService.php** - Exam management validation
- **Other CRUD Services** - Standard validation errors

### 3. **Middleware** - Request/Access Error Handling

**Path:** `src/app/Http/Middleware/`

#### [CheckAccessPermission.php](src/app/Http/Middleware/CheckAccessPermission.php)

Handles authorization errors:

```php
// Denies access if user has no valid role
return response()->json([
    'success' => false,
    'message' => 'Access denied'
], 403);
```

#### Authorization Levels:

- **Manager** - Full access to all resources
- **Teacher** - Access to own subjects and students
- **Student** - Access to enrolled exams and their answers
- **Default** - 403 Forbidden

### 4. **Request Validation** - Input Error Handling

**Path:** `src/app/Http/Requests/`

All API endpoint requests have validation classes that automatically handle:

- **Field validation** - Type checking, required fields
- **Business rule validation** - Custom validation logic
- **Validation exceptions** - Automatically caught in controllers

#### Exception Types Handled:

- `Illuminate\Validation\ValidationException` - Returns 422 Unprocessable Entity
- `Illuminate\Database\Eloquent\ModelNotFoundException` - Returns 404 Not Found
- `\Exception` - Generic fallback returns 500 Internal Server Error

### 5. **Global Exception Handling** - Application Level

**Path:** `src/bootstrap/app.php`

#### Exception Configuration:

```php
->withExceptions(function (Exceptions $exceptions): void {
    // Currently empty - using default Laravel exception handling
}
```

**Note:** The global exception handler is configured but currently empty. Laravel's default exception handler processes all unhandled exceptions.

### 6. **Route Protection** - Route-Level Error Handling

**Path:** `src/routes/api.php`

Routes are protected by:

- **Authentication middleware** (`auth:sanctum`) - Returns 401 if not authenticated
- **Role middleware** (`role:manager`, `role:teacher`, `role:student`) - Returns 403 if unauthorized
- **Permission middleware** - Spatie Permission package integration

---

## Error Response Format

### Success Response

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    /* resource data */
  }
}
```

### Error Response Patterns

#### 400 Bad Request (Business Logic Error)

```json
{
  "success": false,
  "message": "Error message",
  "error": "Detailed error (debug mode only)"
}
```

#### 403 Forbidden (Authorization Failed)

```json
{
  "success": false,
  "message": "Access denied"
}
```

#### 404 Not Found (Resource Missing)

```json
{
  "success": false,
  "message": "Resource not found"
}
```

#### 409 Conflict (Business Logic Conflict)

```json
{
  "success": false,
  "message": "Student already has an attempt for this exam"
}
```

#### 422 Unprocessable Entity (Validation Failed)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message 1", "Error message 2"]
  }
}
```

#### 500 Internal Server Error

```json
{
  "success": false,
  "message": "Operation failed",
  "error": "Specific error (debug mode only)"
}
```

---

## Error Handling Flow Diagram

```
HTTP Request
    ↓
Route Matching → [Authentication Check] → 401 if failed
    ↓
Role/Permission Middleware → 403 if unauthorized
    ↓
Middleware (CheckAccessPermission) → 403 if access denied
    ↓
Request Validation → 422 if invalid
    ↓
Controller (try block)
    ↓
Service Layer (throws exceptions)
    ↓
Controller (catch blocks)
    ├─→ ModelNotFoundException → 404
    ├─→ ValidationException → 422
    └─→ Generic \Exception → 500 (or custom status)
    ↓
JSON Response
```

---

## Key Features

### 1. **Debug Mode Support**

- Detailed error messages only shown when `config('app.debug')` is true
- Production environment hides sensitive error details

### 2. **Consistent Response Structure**

- All responses follow a standard JSON format
- Includes `success` boolean for easy client-side handling

### 3. **Multiple Exception Types**

- `ModelNotFoundException` - Specific handling for missing records
- `ValidationException` - Specific handling for validation failures
- Generic `\Exception` - Catchall for unexpected errors

### 4. **HTTP Status Codes**

- 200/201 - Success responses
- 400 - Business logic errors
- 401 - Authentication required
- 403 - Authorization denied
- 404 - Resource not found
- 409 - Conflict (duplicate entries)
- 422 - Validation errors
- 500 - Server errors

### 5. **Resource-Specific Error Handling**

- Each resource type has tailored error messages
- Authorization checks at multiple levels (route, middleware, service)
- Transaction support for data consistency

---

## Summary

| Layer                  | Location                | Responsibility                      | Exception Types |
| ---------------------- | ----------------------- | ----------------------------------- | --------------- |
| **Route Protection**   | `routes/api.php`        | Authentication & Authorization      | 401, 403        |
| **Middleware**         | `Http/Middleware/`      | Access Control                      | 403             |
| **Request Validation** | `Http/Requests/`        | Input Validation                    | 422             |
| **Controller**         | `Http/Controllers/Api/` | Response Handling & Error Catching  | All types       |
| **Service**            | `Services/`             | Business Logic & Exception Throwing | Custom          |
| **Global Handler**     | `bootstrap/app.php`     | Unhandled Exceptions                | All             |

This architecture ensures comprehensive error handling at every stage of request processing, with clear separation of concerns and consistent error response formatting.
