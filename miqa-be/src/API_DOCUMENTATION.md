# MIQA Learning Backend - API Documentation

## Table of Contents

- [Overview](#overview)
- [Installation](#installation)
- [Authentication](#authentication)
- [Roles & Permissions](#roles--permissions)
- [API Endpoints](#api-endpoints)
- [Models & Data Structures](#models--data-structures)
- [Error Handling](#error-handling)
- [Rate Limiting](#rate-limiting)
- [Best Practices](#best-practices)

---

## Overview

MIQA Learning Backend is a comprehensive educational platform API built with **Laravel 12** and **PHP 8.2+**. It provides a robust system for managing educational institutions, classrooms, subjects, exams, and student assessments.

### Key Features

- **User Management**: Support for multiple user roles (Manager, Teacher, Student)
- **Subject Management**: Organize subjects by topics with teacher assignments
- **Classroom Management**: Create and manage classrooms with student enrollments
- **Exam System**: Create, manage, and track exam attempts with question-answer relationships
- **Statistics & Analytics**: Track student progress and performance
- **File Management**: Upload and manage rapport documents
- **Role-Based Access Control**: Fine-grained permissions using Spatie Laravel Permission

### Technology Stack

- **Framework**: Laravel 12.x
- **PHP Version**: 8.2+
- **Authentication**: Laravel Sanctum
- **Permission Management**: Spatie Laravel Permission
- **Database**: SQLite (default), MySQL, PostgreSQL (configurable)
- **Package Manager**: Composer

---

## Installation

See [Installation Instructions](#installation-instructions) in the README.md

---

## Authentication

### Overview

MIQA Learning uses **Laravel Sanctum** for API authentication. Users can authenticate using email/password credentials, which generates a bearer token for subsequent requests.

### Authentication Endpoints

#### 1. Token Login

Authenticate and receive a bearer token.

```http
POST /api/token-login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response** (200 OK):

```json
{
    "token": "1|abcdefghijklmnopqrstuvwxyz...",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "roles": ["manager"]
    }
}
```

#### 2. Email/Password Login

Standard login endpoint.

```http
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response** (200 OK): Same as Token Login

#### 3. Get Current User

Retrieve authenticated user information.

```http
GET /api/user
Authorization: Bearer {token}
```

**Response** (200 OK):

```json
{
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "roles": ["manager"],
    "created_at": "2024-01-15T10:30:00Z"
}
```

#### 4. Logout

Revoke the current authentication token.

```http
POST /api/logout
Authorization: Bearer {token}
```

**Response** (200 OK):

```json
{
    "message": "Successfully logged out"
}
```

### Using Tokens

All authenticated requests must include the bearer token in the Authorization header:

```http
Authorization: Bearer {token}
```

Example with curl:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://api.example.com/api/user
```

---

## Roles & Permissions

### Available Roles

The system implements three primary roles with specific permissions:

#### 1. **Manager**

- Full administrative access
- Create and manage subjects, classrooms, teachers, and students
- Create exams and manage all exam-related operations
- Upload and delete rapport documents
- View statistics and user data
- Duplicate exams

#### 2. **Teacher**

- Access to assigned subjects and classrooms
- Create and manage exams for assigned subjects
- View student exam submissions and grades
- Access student profiles and performance
- View classroom statistics
- Download rapport documents

#### 3. **Student**

- Access to enrolled classrooms
- View available exams
- Take exams and submit answers
- View exam results and progress
- Download personal rapport documents
- View personal performance statistics

### Role-Based Route Access

```
Public Routes:
  POST   /api/token-login          (All)
  POST   /api/login                (All)

Manager Routes:
  POST   /api/topics               (Create topics)
  POST   /api/subjects             (Create subjects)
  POST   /api/class-rooms          (Create classrooms)

Teacher Routes:
  GET    /api/teacher/subjects     (View assigned subjects)
  POST   /api/teacher/subject-exams (Create exams)

Student Routes:
  GET    /api/student/profile      (View profile)
  GET    /api/student/exams        (List available exams)
  POST   /api/student/exams/{id}/start (Start exam)
```

---

## API Endpoints

### Authentication

| Method | Endpoint           | Role          | Description                        |
| ------ | ------------------ | ------------- | ---------------------------------- |
| POST   | `/api/token-login` | Public        | Authenticate with email & password |
| POST   | `/api/login`       | Public        | Alternative login endpoint         |
| GET    | `/api/user`        | Authenticated | Get current user info              |
| POST   | `/api/logout`      | Authenticated | Revoke token                       |

### Users Management

| Method | Endpoint     | Role             | Description                    |
| ------ | ------------ | ---------------- | ------------------------------ |
| GET    | `/api/users` | Manager, Teacher | List all users with pagination |

### Topics

| Method | Endpoint             | Role             | Description       |
| ------ | -------------------- | ---------------- | ----------------- |
| GET    | `/api/topics`        | Manager, Teacher | List all topics   |
| GET    | `/api/topics/search` | All              | Search topics     |
| GET    | `/api/topics/{id}`   | All              | Get topic details |
| POST   | `/api/topics`        | Manager          | Create new topic  |
| PUT    | `/api/topics/{id}`   | Manager          | Update topic      |
| PATCH  | `/api/topics/{id}`   | Manager          | Partial update    |
| DELETE | `/api/topics/{id}`   | Manager          | Delete topic      |

### Subjects

| Method | Endpoint               | Role             | Description         |
| ------ | ---------------------- | ---------------- | ------------------- |
| GET    | `/api/subjects`        | Manager, Teacher | List subjects       |
| GET    | `/api/subjects/search` | Manager          | Search subjects     |
| GET    | `/api/subjects/{id}`   | All              | Get subject details |
| POST   | `/api/subjects`        | Manager          | Create new subject  |
| PUT    | `/api/subjects/{id}`   | Manager, Teacher | Update subject      |
| DELETE | `/api/subjects`        | Manager          | Delete subject      |

#### Teacher-Specific Subject Routes

| Method | Endpoint                       | Role    | Description              |
| ------ | ------------------------------ | ------- | ------------------------ |
| GET    | `/api/teacher/subjects`        | Teacher | List assigned subjects   |
| GET    | `/api/teacher/subjects/search` | Teacher | Search assigned subjects |

### ClassRooms

| Method | Endpoint                                     | Role                      | Description                   |
| ------ | -------------------------------------------- | ------------------------- | ----------------------------- |
| GET    | `/api/class-rooms`                           | Manager                   | List classrooms               |
| GET    | `/api/class-rooms/search`                    | Manager                   | Search classrooms             |
| GET    | `/api/class-rooms/{id}`                      | Manager, Teacher, Student | Get classroom details         |
| POST   | `/api/class-rooms`                           | Manager                   | Create new classroom          |
| PUT    | `/api/class-rooms/{id}`                      | Manager                   | Update classroom              |
| DELETE | `/api/class-rooms/{id}`                      | Manager                   | Delete classroom              |
| GET    | `/api/class-rooms/{id}/students`             | All                       | Get classroom students        |
| GET    | `/api/class-rooms/{id}/subjects`             | All                       | Get classroom subjects        |
| GET    | `/api/class-rooms/{id}/statistics`           | All                       | Get classroom statistics      |
| POST   | `/api/class-rooms/{id}/enroll-student`       | Manager                   | Enroll student in classroom   |
| DELETE | `/api/class-rooms/{id}/students/{studentId}` | Manager                   | Remove student from classroom |
| POST   | `/api/class-rooms/{id}/assign-subject`       | Manager                   | Assign subject to classroom   |
| DELETE | `/api/class-rooms/{id}/subjects/{subjectId}` | Manager                   | Unassign subject              |

### Students

| Method | Endpoint                        | Role    | Description                   |
| ------ | ------------------------------- | ------- | ----------------------------- |
| GET    | `/api/students`                 | Manager | List all students             |
| GET    | `/api/students/search`          | Manager | Search students               |
| GET    | `/api/students/{id}`            | Manager | Get student details           |
| GET    | `/api/students/{id}/statistics` | Manager | Get student performance stats |
| POST   | `/api/students`                 | Manager | Create new student            |
| PUT    | `/api/students/{id}`            | Manager | Update student                |
| DELETE | `/api/students/{id}`            | Manager | Delete student                |

#### Student-Specific Routes

| Method | Endpoint                  | Role    | Description             |
| ------ | ------------------------- | ------- | ----------------------- |
| GET    | `/api/student/profile`    | Student | Get own profile         |
| GET    | `/api/student/classrooms` | Student | Get enrolled classrooms |

#### Teacher Routes

| Method | Endpoint                                    | Role    | Description         |
| ------ | ------------------------------------------- | ------- | ------------------- |
| GET    | `/api/teacher/students/{studentId}/profile` | Teacher | Get student profile |

### Teachers

| Method | Endpoint               | Role             | Description         |
| ------ | ---------------------- | ---------------- | ------------------- |
| GET    | `/api/teachers`        | Manager, Teacher | List all teachers   |
| GET    | `/api/teachers/search` | Manager, Teacher | Search teachers     |
| GET    | `/api/teachers/{id}`   | Manager, Teacher | Get teacher details |
| POST   | `/api/teachers`        | Manager          | Create new teacher  |
| PUT    | `/api/teachers/{id}`   | Manager          | Update teacher      |
| PATCH  | `/api/teachers/{id}`   | Manager          | Partial update      |
| DELETE | `/api/teachers/{id}`   | Manager          | Delete teacher      |
| GET    | `/api/teacher/profile` | Teacher          | Get own profile     |

### Class Students (Enrollments)

| Method | Endpoint                                                              | Role    | Description                      |
| ------ | --------------------------------------------------------------------- | ------- | -------------------------------- |
| GET    | `/api/class-students`                                                 | Manager | List class-student relationships |
| GET    | `/api/class-students/search`                                          | Manager | Search enrollments               |
| POST   | `/api/class-students`                                                 | Manager | Create enrollment                |
| GET    | `/api/classrooms/{classRoomId}/bulk-enroll`                           | Manager | Bulk enroll students             |
| PUT    | `/api/students/{studentId}/classrooms/{classRoomId}/status`           | Manager | Update enrollment status         |
| POST   | `/api/students/{studentId}/classrooms/{classRoomId}/rapport`          | Manager | Upload rapport document          |
| DELETE | `/api/students/{studentId}/classrooms/{classRoomId}/rapport`          | Manager | Delete rapport                   |
| GET    | `/api/students/{studentId}/classrooms/{classRoomId}/rapport/info`     | All     | Get rapport info                 |
| GET    | `/api/students/{studentId}/classrooms/{classRoomId}/rapport/download` | All     | Download rapport                 |
| GET    | `/api/student/classrooms/{classRoomId}/rapport/info`                  | Student | Get own rapport info             |
| GET    | `/api/student/classrooms/{classRoomId}/rapport/download`              | Student | Download own rapport             |

### Class Subjects

| Method | Endpoint                                             | Role    | Description                      |
| ------ | ---------------------------------------------------- | ------- | -------------------------------- |
| GET    | `/api/class-subjects`                                | Manager | List class-subject relationships |
| GET    | `/api/class-subjects/search`                         | Manager | Search assignments               |
| POST   | `/api/class-subjects`                                | Manager | Create assignment                |
| GET    | `/api/classrooms/{classRoomId}/available-subjects`   | Manager | Get available subjects           |
| GET    | `/api/subjects/{subjectId}/available-classrooms`     | Manager | Get available classrooms         |
| POST   | `/api/classrooms/{classRoomId}/bulk-assign-subjects` | Manager | Bulk assign subjects             |
| GET    | `/api/teacher/classrooms`                            | Teacher | Get teacher's classrooms         |

### Subject Exams

| Method | Endpoint                                  | Role    | Description          |
| ------ | ----------------------------------------- | ------- | -------------------- |
| GET    | `/api/subject-exams`                      | Manager | List all exams       |
| GET    | `/api/subject-exams/search`               | Manager | Search exams         |
| GET    | `/api/subject-exams/{id}`                 | All     | Get exam details     |
| GET    | `/api/subject-exams/{id}/statistics`      | All     | Get exam statistics  |
| GET    | `/api/subject-exams/{id}/status`          | All     | Get exam status      |
| POST   | `/api/subject-exams/{id}/duplicate`       | Manager | Duplicate exam       |
| GET    | `/api/student/exams`                      | Student | List available exams |
| GET    | `/api/student/subjects/{subjectId}/exams` | Student | Get subject exams    |

#### Teacher Exam Routes

| Method | Endpoint                                           | Role    | Description              |
| ------ | -------------------------------------------------- | ------- | ------------------------ |
| GET    | `/api/teacher/subject-exams`                       | Teacher | List teacher's exams     |
| POST   | `/api/teacher/subject-exams`                       | Teacher | Create new exam          |
| PUT    | `/api/teacher/subject-exams/{id}`                  | Teacher | Update exam              |
| DELETE | `/api/teacher/subject-exams/{id}`                  | Teacher | Delete exam              |
| GET    | `/api/teacher/subject-exams/{id}/answers`          | Teacher | Get exam answers         |
| GET    | `/api/teacher/exams/{id}/students`                 | Teacher | Get student status       |
| GET    | `/api/teacher/exams/{examId}/students/{studentId}` | Teacher | Get student exam details |

### Student Exams

| Method | Endpoint                                                    | Role    | Description        |
| ------ | ----------------------------------------------------------- | ------- | ------------------ |
| GET    | `/api/student/exams/{examId}`                               | Student | Get exam details   |
| POST   | `/api/student/exams/{examId}/start`                         | Student | Start exam attempt |
| POST   | `/api/student/exams/{examId}/complete`                      | Student | Complete exam      |
| GET    | `/api/student/exams/{examId}/progress`                      | Student | Get exam progress  |
| GET    | `/api/student/exams/{examId}/results`                       | Student | Get exam results   |
| POST   | `/api/student/exams/{examId}/questions/{questionId}/answer` | Student | Submit answer      |

### Exam Attempts

| Method | Endpoint                                           | Role             | Description            |
| ------ | -------------------------------------------------- | ---------------- | ---------------------- |
| GET    | `/api/exam-attempts`                               | Manager, Teacher | List exam attempts     |
| GET    | `/api/exam-attempts/{id}`                          | Manager, Teacher | Get attempt details    |
| POST   | `/api/exam-attempts`                               | Manager, Teacher | Create attempt         |
| PUT    | `/api/exam-attempts/{id}`                          | Manager, Teacher | Update attempt         |
| DELETE | `/api/exam-attempts/{id}`                          | Manager, Teacher | Delete attempt         |
| GET    | `/api/exam-attempts/statistics`                    | Manager, Teacher | Get attempt statistics |
| GET    | `/api/students/{studentId}/exams/{examId}/attempt` | Manager, Teacher | Get student attempt    |

### Exam Questions

| Method | Endpoint                     | Role             | Description      |
| ------ | ---------------------------- | ---------------- | ---------------- |
| GET    | `/api/exam-questions`        | Manager, Teacher | List questions   |
| GET    | `/api/exam-questions/search` | Manager, Teacher | Search questions |
| POST   | `/api/exam-questions`        | Manager, Teacher | Create question  |
| PUT    | `/api/exam-questions/{id}`   | Manager, Teacher | Update question  |
| DELETE | `/api/exam-questions/{id}`   | Manager, Teacher | Delete question  |

### Question Answers

| Method | Endpoint                                           | Role             | Description          |
| ------ | -------------------------------------------------- | ---------------- | -------------------- |
| GET    | `/api/question-answers`                            | Manager, Teacher | List answers         |
| GET    | `/api/question-answers/{id}`                       | Manager, Teacher | Get answer details   |
| POST   | `/api/question-answers`                            | Manager, Teacher | Create answer        |
| GET    | `/api/question-answers/needs-grading`              | Manager, Teacher | Get ungraded answers |
| GET    | `/api/students/{studentId}/exams/{examId}/answers` | Manager, Teacher | Get student answers  |

### Question Options

| Method | Endpoint                     | Role             | Description   |
| ------ | ---------------------------- | ---------------- | ------------- |
| GET    | `/api/question-options`      | Manager, Teacher | List options  |
| POST   | `/api/question-options`      | Manager, Teacher | Create option |
| PUT    | `/api/question-options/{id}` | Manager, Teacher | Update option |
| DELETE | `/api/question-options/{id}` | Manager, Teacher | Delete option |

### Statistics

| Method | Endpoint          | Role             | Description            |
| ------ | ----------------- | ---------------- | ---------------------- |
| GET    | `/api/statistics` | Manager, Teacher | Get general statistics |

---

## Models & Data Structures

### User Model

```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": "2024-01-15T10:30:00Z",
    "photo": "https://example.com/storage/photos/user-1.jpg",
    "gender": "male",
    "roles": ["manager", "teacher"],
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
}
```

**Fillable Fields**: `name`, `email`, `password`, `photo`, `gender`

### Subject Model

```json
{
    "id": 1,
    "name": "Mathematics",
    "tagline": "Advanced Calculus",
    "photo": "https://example.com/storage/subjects/math.jpg",
    "content": "https://example.com/storage/content/math-content.pdf",
    "about": "Comprehensive mathematics course",
    "topic_id": 5,
    "teacher_id": 2,
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z",
    "topic": { "id": 5, "name": "Science" },
    "teacher": { "id": 2, "name": "Jane Smith" }
}
```

**Fillable Fields**: `name`, `tagline`, `photo`, `content`, `about`, `topic_id`, `teacher_id`

### ClassRoom Model

```json
{
    "id": 1,
    "name": "Grade 10 - Section A",
    "description": "Advanced mathematics students",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z",
    "students_count": 25,
    "subjects_count": 5
}
```

**Fillable Fields**: `name`, `description`

### Student Model

```json
{
    "id": 1,
    "user_id": 10,
    "rollNumber": "STU-001",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z",
    "user": {
        "id": 10,
        "name": "Ali Khan",
        "email": "ali@example.com",
        "photo": "https://example.com/storage/photos/user-10.jpg"
    }
}
```

**Fillable Fields**: `user_id`, `rollNumber`

### SubjectExam Model

```json
{
    "id": 1,
    "subject_id": 3,
    "title": "Midterm Exam",
    "description": "Midterm examination",
    "duration": 120,
    "total_marks": 100,
    "passing_marks": 50,
    "status": "active",
    "instructions": "Complete all questions",
    "start_date": "2024-02-15T10:00:00Z",
    "end_date": "2024-02-15T12:00:00Z",
    "shuffle_questions": true,
    "show_answers_after_submit": false,
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
}
```

**Fillable Fields**: `subject_id`, `title`, `description`, `duration`, `total_marks`, `passing_marks`, `status`, `instructions`, `start_date`, `end_date`, `shuffle_questions`, `show_answers_after_submit`

### ExamQuestion Model

```json
{
    "id": 1,
    "subject_exam_id": 1,
    "question_text": "What is 2 + 2?",
    "question_type": "multiple_choice",
    "marks": 5,
    "order": 1,
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
}
```

**Fillable Fields**: `subject_exam_id`, `question_text`, `question_type`, `marks`, `order`

### QuestionOption Model

```json
{
    "id": 1,
    "exam_question_id": 1,
    "option_text": "4",
    "is_correct": true,
    "order": 1,
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
}
```

**Fillable Fields**: `exam_question_id`, `option_text`, `is_correct`, `order`

### ExamAttempt Model

```json
{
    "id": 1,
    "student_id": 5,
    "subject_exam_id": 1,
    "start_time": "2024-02-15T10:00:00Z",
    "end_time": "2024-02-15T12:00:00Z",
    "status": "completed",
    "obtained_marks": 85,
    "created_at": "2024-02-15T10:00:00Z",
    "updated_at": "2024-02-15T12:00:00Z"
}
```

**Fillable Fields**: `student_id`, `subject_exam_id`, `start_time`, `end_time`, `status`, `obtained_marks`

### QuestionAnswer Model

```json
{
    "id": 1,
    "exam_attempt_id": 1,
    "exam_question_id": 1,
    "question_option_id": 1,
    "answer_text": "The answer is 4",
    "marks_obtained": 5,
    "is_correct": true,
    "created_at": "2024-02-15T10:30:00Z",
    "updated_at": "2024-02-15T10:30:00Z"
}
```

**Fillable Fields**: `exam_attempt_id`, `exam_question_id`, `question_option_id`, `answer_text`, `marks_obtained`, `is_correct`

### Topic Model

```json
{
    "id": 1,
    "name": "STEM",
    "description": "Science, Technology, Engineering, and Mathematics",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
}
```

**Fillable Fields**: `name`, `description`

---

## Error Handling

### Standard Error Response Format

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

### Common HTTP Status Codes

| Status | Meaning              | Description                               |
| ------ | -------------------- | ----------------------------------------- |
| 200    | OK                   | Successful request                        |
| 201    | Created              | Resource successfully created             |
| 204    | No Content           | Successful request with no content        |
| 400    | Bad Request          | Invalid request parameters                |
| 401    | Unauthorized         | Missing or invalid authentication token   |
| 403    | Forbidden            | Insufficient permissions for the resource |
| 404    | Not Found            | Resource not found                        |
| 422    | Unprocessable Entity | Validation error                          |
| 500    | Server Error         | Internal server error                     |

### Example Error Responses

#### Authentication Error (401)

```json
{
    "message": "Unauthenticated."
}
```

#### Authorization Error (403)

```json
{
    "message": "Unauthorized access to this resource"
}
```

#### Validation Error (422)

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "name": ["The name field is required."],
        "email": ["The email must be a valid email address."]
    }
}
```

#### Not Found Error (404)

```json
{
    "message": "Resource not found"
}
```

---

## Rate Limiting

The API implements rate limiting to prevent abuse and ensure fair resource usage.

### Default Rate Limits

- **Unauthenticated Requests**: 60 requests per minute per IP
- **Authenticated Requests**: 120 requests per minute per user
- **Login Attempts**: 5 attempts per 5 minutes

### Rate Limit Headers

The following headers are included in API responses:

```
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 115
X-RateLimit-Reset: 1639430400
```

### Handling Rate Limit Exceeded

When rate limit is exceeded, the API returns a `429 Too Many Requests` response:

```json
{
    "message": "Too many requests. Please try again later."
}
```

---

## Best Practices

### 1. Authentication

- **Always use HTTPS** in production
- **Store tokens securely** on the client side
- **Refresh tokens** regularly for security
- **Never expose tokens** in URLs or logs

Example:

```javascript
const token = localStorage.getItem("auth_token");
fetch("/api/user", {
    headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
    },
});
```

### 2. Error Handling

- Always check HTTP status codes
- Parse error messages and display to users
- Log errors for debugging
- Implement retry logic for network errors

Example:

```javascript
try {
    const response = await fetch("/api/subjects", {
        headers: { Authorization: `Bearer ${token}` },
    });

    if (!response.ok) {
        throw new Error(`API Error: ${response.status}`);
    }

    const data = await response.json();
} catch (error) {
    console.error("Request failed:", error);
}
```

### 3. Pagination

Use pagination for endpoints that return multiple records:

```http
GET /api/students?page=1&per_page=20
```

### 4. Searching

Use the search endpoints for filtering:

```http
GET /api/subjects/search?query=mathematics&topic_id=1
```

### 5. Request Headers

Always include required headers:

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### 6. Response Parsing

Handle both success and error responses:

```javascript
const response = await fetch("/api/subjects", {
    method: "POST",
    headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
    },
    body: JSON.stringify({
        name: "Physics",
        topic_id: 1,
        teacher_id: 5,
    }),
});

if (response.ok) {
    const subject = await response.json();
    console.log("Subject created:", subject);
} else if (response.status === 422) {
    const errors = await response.json();
    console.log("Validation errors:", errors.errors);
}
```

### 7. File Uploads

For rapport document uploads:

```javascript
const formData = new FormData();
formData.append("rapport", fileInput.files[0]);

const response = await fetch(
    `/api/students/${studentId}/classrooms/${classRoomId}/rapport`,
    {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
        },
        body: formData,
    },
);
```

### 8. Caching

Implement client-side caching to reduce API calls:

```javascript
const cache = new Map();

async function getCachedSubjects() {
    if (cache.has("subjects")) {
        return cache.get("subjects");
    }

    const response = await fetch("/api/subjects", {
        headers: { Authorization: `Bearer ${token}` },
    });

    const data = await response.json();
    cache.set("subjects", data);
    return data;
}
```

### 9. Batch Operations

Use bulk endpoints when available:

```http
POST /api/classrooms/{classRoomId}/bulk-enroll
Content-Type: application/json

{
  "student_ids": [1, 2, 3, 4, 5]
}
```

### 10. Timestamps

All timestamps are returned in ISO 8601 format (UTC):

```json
{
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T14:45:30Z"
}
```

---

## Support & Documentation

For additional information:

- **Laravel Documentation**: https://laravel.com/docs
- **Sanctum Documentation**: https://laravel.com/docs/sanctum
- **Spatie Permission**: https://spatie.be/docs/laravel-permission

---

## Version History

| Version | Date       | Changes                                |
| ------- | ---------- | -------------------------------------- |
| 1.0.0   | 2024-02-01 | Initial API release                    |
| 1.1.0   | 2024-02-15 | Added statistics endpoints             |
| 1.2.0   | Current    | Improved error handling and validation |

---

_Last Updated: 2024-02-20_
_API Version: 1.2.0_
