# MIQA Backend - Database Entity Relationship Diagram

## Overview

MIQA is an educational exam management system built with Laravel. The database architecture supports classroom management, subject organization, exam creation, and student performance tracking.

---

## Core Entity Relationship Diagram

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                                    TOPICS                                        │
│  ┌──────────────┐                                                               │
│  │ id (PK)      │                                                               │
│  │ name         │ ◄─────── UNIQUE                                              │
│  │ about        │                                                               │
│  │ photo        │                                                               │
│  │ timestamps   │                                                               │
└──┼──────────────┤────────────────────────────────────────────────────────────────┘
   │              │
   │ 1:N          │
   │              │
   └──────────────►┌──────────────────────────────────────────────────────────────┐
                  │                     SUBJECTS                                  │
                  │  ┌────────────────────────────────────┐                       │
                  │  │ id (PK)                            │                       │
                  │  │ name (UNIQUE)                      │                       │
                  │  │ tagline                            │                       │
                  │  │ photo                              │                       │
                  │  │ content                            │                       │
                  │  │ about                              │                       │
                  │  │ topic_id (FK) ──► TOPICS          │                       │
                  │  │ teacher_id (FK) ──► USERS         │                       │
                  │  │ total_points                       │                       │
                  │  │ timestamps                         │                       │
                  │  └────────────────────────────────────┘                       │
                  └──────────────┬───────────────────────────────────────────────┬┘
                                 │ 1:N (Subject has many ExamAttempts)            │
                                 │                                                │
                      ┌──────────┤                                     ┌─────────┤
                      │          │                                     │
                      │          └────────────────────►┌──────────────────────────────┐
                      │                                │          SUBJECT_EXAMS       │
                      │                                │  ┌──────────────────────────┐
                      │                                │  │ id (PK)                  │
                      │                                │  │ subject_id (FK)          │
                      │                                │  │ name                     │
                      │                                │  │ about                    │
                      │                                │  │ started_at               │
                      │                                │  │ ended_at                 │
                      │                                │  │ total_points             │
                      │                                │  │ timestamps               │
                      │                                │  │ UNIQUE(subject_id,name)  │
                      │                                │  └──────────┬───────────────┘
                      │                                └────────────┤
                      │                                             │ 1:N
                      │                                             │
                      │                                  ┌──────────▼─────────────────┐
                      │                                  │    EXAM_QUESTIONS          │
                      │                                  │  ┌─────────────────────────┐
                      │                                  │  │ id (PK)                 │
                      │                                  │  │ subject_exam_id (FK)    │
                      │                                  │  │ name                    │
                      │                                  │  │ timer                   │
                      │                                  │  │ type (multiple choice)  │
                      │                                  │  │ points                  │
                      │                                  │  │ timestamps              │
                      │                                  │  └─────────────────────────┘
                      │                                  └──────────┬─────┬──────────┘
                      │                                             │    │
                      │                                  1:N        │    │       1:N
                      │                                             │    │
                      │                        ┌────────────────────┘    └─────────┐
                      │                        │                                   │
                      │                        │                    ┌──────────────▼──────────────┐
                      │                        │                    │   QUESTION_OPTIONS         │
                      │                        │                    │  ┌──────────────────────────┐
                      │                        │                    │  │ id (PK)                  │
                      │                        │                    │  │ exam_question_id (FK)    │
                      │                        │                    │  │ is_correct               │
                      │                        │                    │  │ name                     │
                      │                        │                    │  │ timestamps               │
                      │                        │                    │  └──────────────────────────┘
                      │                        │                    └──────────────────────────────┘
                      │                        │
                      │                        │ 1:N
                      │                        │
                      │            ┌───────────▼──────────────────┐
                      │            │   QUESTION_ANSWERS           │
                      │            │  ┌────────────────────────────┐
                      │            │  │ id (PK)                    │
                      │            │  │ exam_question_id (FK)      │
                      │            │  │ student_id (FK) ──► USERS  │
                      │            │  │ answer_text                │
                      │            │  │ has_passed                 │
                      │            │  │ points_earned              │
                      │            │  │ feedback                   │
                      │            │  │ timestamps                 │
                      │            │  │ UNIQUE(exam_q_id,student)  │
                      │            │  └────────────────────────────┘
                      │            └────────────────────────────────┘
                      │
         ┌────────────┴─────────────────────────────┐
         │                                          │
         │          ┌──────────────────────────────┤
         │          │ 1:N                          │
         │          │                              │
    ┌────▼──────────▼─────────────┐           ┌───▼───────────────────────┐
    │      USERS (Teachers)        │           │  EXAM_ATTEMPTS            │
    │  ┌─────────────────────────┐ │           │  ┌──────────────────────┐│
    │  │ id (PK)                 │ │           │  │ id (PK)              ││
    │  │ name                    │ │           │  │ student_id (FK)      ││
    │  │ email (UNIQUE)          │ │           │  │ subject_exam_id (FK) ││
    │  │ password                │ │           │  │ total_attempts       ││
    │  │ photo                   │ │           │  │ is_completed         ││
    │  │ gender                  │ │           │  │ total_questions      ││
    │  │ timestamps              │ │           │  │ answered_questions   ││
    │  │ email_verified_at       │ │           │  │ total_points         ││
    │  │ remember_token          │ │           │  │ points_earned        ││
    │  └─────────────────────────┘ │           │  │ has_passed           ││
    └────┬──────────────────────────┘           │  │ completed_at         ││
         │                                      │  │ timestamps           ││
         │ 1:N ◄─────────────────────────────────  │ UNIQUE(student_id,   ││
         │                                      │  │        subject_exam) ││
         │                                      │  └──────────────────────┘│
         │ (Students)                           └───────────────────────────┘
         │
         │                           ┌─────────────────────────────┐
         │                           │    CLASS_STUDENTS           │
         │                           │  ┌────────────────────────┐ │
         │                           │  │ id (PK)                │ │
         │                           │  │ student_id (FK)        │ │
         │                           │  │ class_room_id (FK)     │ │
         │                           │  │ has_passed             │ │
         │                           │  │ rapport                │ │
         │                           │  │ timestamps             │ │
         │                           │  │ UNIQUE(student_id,     │ │
         │                           │  │        class_room_id)  │ │
         │                           │  └────────────────────────┘ │
         │                           └────────┬────────────────────┘
         │                                    │ N:1
         │                                    │
         └────────────────────────────────────┘
                                              │
                                  ┌───────────▼────────────────────┐
                                  │      CLASS_ROOMS              │
                                  │  ┌───────────────────────────┐ │
                                  │  │ id (PK)                   │ │
                                  │  │ name (UNIQUE)             │ │
                                  │  │ photo                     │ │
                                  │  │ grade                     │ │
                                  │  │ timestamps                │ │
                                  │  └───────────┬───────────────┘ │
                                  └──────────────┼──────────────────┘
                                                 │ 1:N
                                                 │
                                    ┌────────────▼─────────────────┐
                                    │    CLASS_SUBJECTS            │
                                    │  ┌──────────────────────────┐│
                                    │  │ id (PK)                  ││
                                    │  │ class_room_id (FK)       ││
                                    │  │ subject_id (FK)          ││
                                    │  │ timestamps               ││
                                    │  │ UNIQUE(class_room_id,    ││
                                    │  │        subject_id)       ││
                                    │  └──────────────────────────┘│
                                    └──────────────────────────────┘
                                             │
                                             │ N:1
                                             │
                                             ◄──────(back to SUBJECTS)
```

---

## Database Schema Details

### 1. **USERS** (Core User Management)

| Column            | Type      | Constraints               | Description                  |
| ----------------- | --------- | ------------------------- | ---------------------------- |
| id                | BIGINT    | PK, AUTO_INCREMENT        | User unique identifier       |
| name              | VARCHAR   | NOT NULL                  | User full name               |
| email             | VARCHAR   | UNIQUE, NOT NULL          | User email address           |
| email_verified_at | TIMESTAMP | NULLABLE                  | Email verification timestamp |
| password          | VARCHAR   | NOT NULL                  | Encrypted password           |
| photo             | VARCHAR   | NOT NULL                  | Profile photo URL            |
| gender            | VARCHAR   | NOT NULL                  | User gender                  |
| remember_token    | VARCHAR   | NULLABLE                  | Remember me token            |
| created_at        | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time         |
| updated_at        | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Last update time             |

**Relationships:**

- 1:N with SUBJECTS (as teacher_id)
- 1:N with CLASS_STUDENTS (as student_id)
- 1:N with QUESTION_ANSWERS (as student_id)
- 1:N with EXAM_ATTEMPTS (as student_id)

---

### 2. **TOPICS** (Subject Topic Categories)

| Column     | Type      | Constraints               | Description             |
| ---------- | --------- | ------------------------- | ----------------------- |
| id         | BIGINT    | PK, AUTO_INCREMENT        | Topic unique identifier |
| name       | VARCHAR   | UNIQUE, NOT NULL          | Topic name              |
| about      | TEXT      | NOT NULL                  | Topic description       |
| photo      | VARCHAR   | NOT NULL                  | Topic image URL         |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time    |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Last update time        |

**Relationships:**

- 1:N with SUBJECTS

---

### 3. **SUBJECTS** (Course Subjects)

| Column       | Type      | Constraints               | Description                   |
| ------------ | --------- | ------------------------- | ----------------------------- |
| id           | BIGINT    | PK, AUTO_INCREMENT        | Subject unique identifier     |
| name         | VARCHAR   | UNIQUE, NOT NULL          | Subject name                  |
| tagline      | VARCHAR   | NOT NULL                  | Subject tagline/motto         |
| photo        | VARCHAR   | NOT NULL                  | Subject image URL             |
| content      | VARCHAR   | NULLABLE                  | Subject content/materials URL |
| about        | TEXT      | NOT NULL                  | Subject description           |
| topic_id     | BIGINT    | FK → TOPICS               | Associated topic              |
| teacher_id   | BIGINT    | FK → USERS                | Subject instructor            |
| total_points | INT       | DEFAULT 0                 | Maximum points for all exams  |
| created_at   | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time          |
| updated_at   | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Last update time              |

**Constraints:**

- UNIQUE(topic_id, name)
- ON DELETE CASCADE on topic_id

**Relationships:**

- N:1 with TOPICS
- N:1 with USERS (teacher)
- 1:N with SUBJECT_EXAMS
- 1:N with CLASS_SUBJECTS

---

### 4. **CLASS_ROOMS** (Classroom Groups)

| Column     | Type      | Constraints               | Description                 |
| ---------- | --------- | ------------------------- | --------------------------- |
| id         | BIGINT    | PK, AUTO_INCREMENT        | Classroom unique identifier |
| name       | VARCHAR   | UNIQUE, NOT NULL          | Classroom name/code         |
| photo      | VARCHAR   | NOT NULL                  | Classroom image URL         |
| grade      | INT       | NOT NULL                  | Grade level                 |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time        |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Last update time            |

**Relationships:**

- 1:N with CLASS_STUDENTS
- 1:N with CLASS_SUBJECTS

---

### 5. **CLASS_STUDENTS** (Student Classroom Enrollment)

| Column        | Type      | Constraints               | Description                  |
| ------------- | --------- | ------------------------- | ---------------------------- |
| id            | BIGINT    | PK, AUTO_INCREMENT        | Enrollment unique identifier |
| student_id    | BIGINT    | FK → USERS                | Student reference            |
| class_room_id | BIGINT    | FK → CLASS_ROOMS          | Classroom reference          |
| has_passed    | BOOLEAN   | DEFAULT FALSE             | Student pass status          |
| rapport       | VARCHAR   | NULLABLE                  | Student report/notes         |
| created_at    | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time         |
| updated_at    | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Last update time             |

**Constraints:**

- UNIQUE(student_id, class_room_id)
- ON DELETE CASCADE on both FKs

**Relationships:**

- N:1 with USERS (student)
- N:1 with CLASS_ROOMS

---

### 6. **CLASS_SUBJECTS** (Subject Assignment to Classrooms)

| Column        | Type      | Constraints               | Description                  |
| ------------- | --------- | ------------------------- | ---------------------------- |
| id            | BIGINT    | PK, AUTO_INCREMENT        | Assignment unique identifier |
| class_room_id | BIGINT    | FK → CLASS_ROOMS          | Classroom reference          |
| subject_id    | BIGINT    | FK → SUBJECTS             | Subject reference            |
| created_at    | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time         |
| updated_at    | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Last update time             |

**Constraints:**

- UNIQUE(class_room_id, subject_id)
- ON DELETE CASCADE on both FKs

**Relationships:**

- N:1 with CLASS_ROOMS
- N:1 with SUBJECTS

---

### 7. **SUBJECT_EXAMS** (Exam Instances)

| Column       | Type      | Constraints               | Description             |
| ------------ | --------- | ------------------------- | ----------------------- |
| id           | BIGINT    | PK, AUTO_INCREMENT        | Exam unique identifier  |
| subject_id   | BIGINT    | FK → SUBJECTS             | Subject reference       |
| name         | VARCHAR   | NOT NULL                  | Exam name/title         |
| about        | TEXT      | NOT NULL                  | Exam description        |
| started_at   | DATE      | NOT NULL                  | Exam start date         |
| ended_at     | DATE      | NOT NULL                  | Exam end date           |
| total_points | INT       | DEFAULT 0                 | Maximum points for exam |
| created_at   | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time    |
| updated_at   | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Last update time        |

**Constraints:**

- UNIQUE(subject_id, name)
- ON DELETE CASCADE on subject_id

**Relationships:**

- N:1 with SUBJECTS
- 1:N with EXAM_QUESTIONS
- 1:N with EXAM_ATTEMPTS

---

### 8. **EXAM_QUESTIONS** (Question Bank)

| Column          | Type      | Constraints               | Description                                  |
| --------------- | --------- | ------------------------- | -------------------------------------------- |
| id              | BIGINT    | PK, AUTO_INCREMENT        | Question unique identifier                   |
| subject_exam_id | BIGINT    | FK → SUBJECT_EXAMS        | Exam reference                               |
| name            | VARCHAR   | NOT NULL                  | Question text/prompt                         |
| timer           | INT       | NOT NULL                  | Time limit in seconds                        |
| type            | VARCHAR   | NOT NULL                  | Question type (e.g., multiple_choice, essay) |
| points          | INT       | NOT NULL                  | Points awarded for correct answer            |
| created_at      | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time                         |
| updated_at      | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Last update time                             |

**Relationships:**

- N:1 with SUBJECT_EXAMS
- 1:N with QUESTION_OPTIONS
- 1:N with QUESTION_ANSWERS

---

### 9. **QUESTION_OPTIONS** (Multiple Choice Options)

| Column           | Type      | Constraints               | Description              |
| ---------------- | --------- | ------------------------- | ------------------------ |
| id               | BIGINT    | PK, AUTO_INCREMENT        | Option unique identifier |
| exam_question_id | BIGINT    | FK → EXAM_QUESTIONS       | Question reference       |
| is_correct       | BOOLEAN   | DEFAULT FALSE             | Marks correct answer     |
| name             | VARCHAR   | NOT NULL                  | Option text              |
| created_at       | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time     |
| updated_at       | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Last update time         |

**Relationships:**

- N:1 with EXAM_QUESTIONS

---

### 10. **QUESTION_ANSWERS** (Student Responses)

| Column           | Type      | Constraints               | Description                    |
| ---------------- | --------- | ------------------------- | ------------------------------ |
| id               | BIGINT    | PK, AUTO_INCREMENT        | Answer unique identifier       |
| exam_question_id | BIGINT    | FK → EXAM_QUESTIONS       | Question reference             |
| student_id       | BIGINT    | FK → USERS                | Student reference              |
| answer_text      | TEXT      | NULLABLE                  | Student's answer text (essays) |
| has_passed       | BOOLEAN   | NOT NULL                  | Answer correctness             |
| points_earned    | INT       | DEFAULT 0                 | Points awarded                 |
| feedback         | TEXT      | NULLABLE                  | Instructor feedback            |
| created_at       | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time           |
| updated_at       | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Last update time               |

**Constraints:**

- UNIQUE(exam_question_id, student_id)
- ON DELETE CASCADE on both FKs

**Relationships:**

- N:1 with EXAM_QUESTIONS
- N:1 with USERS (student)

---

### 11. **EXAM_ATTEMPTS** (Student Exam Attempts)

| Column             | Type      | Constraints               | Description                       |
| ------------------ | --------- | ------------------------- | --------------------------------- |
| id                 | BIGINT    | PK, AUTO_INCREMENT        | Attempt unique identifier         |
| student_id         | BIGINT    | FK → USERS                | Student reference                 |
| subject_exam_id    | BIGINT    | FK → SUBJECT_EXAMS        | Exam reference                    |
| total_attempts     | INT       | DEFAULT 1                 | Number of times student attempted |
| is_completed       | BOOLEAN   | DEFAULT FALSE             | Exam completion status            |
| total_questions    | INT       | DEFAULT 0                 | Total questions in exam           |
| answered_questions | INT       | DEFAULT 0                 | Questions answered by student     |
| total_points       | INT       | DEFAULT 0                 | Maximum points for exam           |
| points_earned      | INT       | DEFAULT 0                 | Points earned by student          |
| has_passed         | BOOLEAN   | DEFAULT FALSE             | Overall pass status               |
| completed_at       | TIMESTAMP | NULLABLE                  | Exam completion timestamp         |
| created_at         | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time              |
| updated_at         | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Last update time                  |

**Constraints:**

- UNIQUE(student_id, subject_exam_id)
- ON DELETE CASCADE on both FKs

**Relationships:**

- N:1 with USERS (student)
- N:1 with SUBJECT_EXAMS

---

## Supporting System Tables

### 12. **PASSWORD_RESET_TOKENS**

Token management for password recovery process.

### 13. **SESSIONS**

User session tracking for authentication.

### 14. **PERSONAL_ACCESS_TOKENS**

API token management for Sanctum authentication.

### 15. **PERMISSIONS & ROLES**

Role-based access control (RBAC) tables:

- `permissions` - Define system permissions
- `roles` - Define user roles
- `model_has_permissions` - Assign permissions to users
- `model_has_roles` - Assign roles to users
- `role_has_permissions` - Assign permissions to roles

### 16. **CACHE Tables**

- `cache` - Cache storage
- `cache_locks` - Cache locking mechanism

### 17. **JOBS Tables**

- `jobs` - Queue job storage
- `job_batches` - Batch job tracking

---

## Data Flow Diagrams

### Exam Creation & Assignment Flow

```
SUBJECT
  ↓ (creates)
SUBJECT_EXAMS
  ↓ (contains)
EXAM_QUESTIONS
  ├─ (if multiple choice)
  └─→ QUESTION_OPTIONS

CLASS_SUBJECTS
  ↑ (links)
CLASS_ROOMS
```

### Student Exam Participation Flow

```
USERS (Student)
  ↓ (enrolled via)
CLASS_STUDENTS
  ↓ (takes exam from)
SUBJECT_EXAMS
  ↓ (tracked by)
EXAM_ATTEMPTS
  ↓ (answers)
EXAM_QUESTIONS
  ├─ (chooses from)
  │  QUESTION_OPTIONS
  └─ (records answer in)
     QUESTION_ANSWERS
```

### Grading & Assessment Flow

```
EXAM_ATTEMPTS
  ↓ (contains)
QUESTION_ANSWERS
  ├─ (references)
  │  EXAM_QUESTIONS (for points value)
  └─ (tracks)
     ├─ points_earned
     ├─ has_passed
     └─ feedback
```

---

## Key Design Patterns

### 1. **Cascading Deletes**

- Deleting a TOPIC cascades to SUBJECTS
- Deleting a SUBJECT cascades to SUBJECT_EXAMS and CLASS_SUBJECTS
- Deleting SUBJECT_EXAMS cascades to EXAM_QUESTIONS, EXAM_ATTEMPTS
- Deleting EXAM_QUESTIONS cascades to QUESTION_OPTIONS and QUESTION_ANSWERS

### 2. **Unique Constraints (Natural Keys)**

- `subjects.name` - Prevents duplicate subject names
- `topics.name` - Prevents duplicate topic names
- `class_rooms.name` - Prevents duplicate classroom names
- `users.email` - Ensures unique email addresses
- `subjects(topic_id, name)` - Prevents duplicate subjects within topics (logical)
- `subject_exams(subject_id, name)` - Prevents duplicate exam names within subjects
- `class_students(student_id, class_room_id)` - Prevents duplicate enrollments
- `class_subjects(class_room_id, subject_id)` - Prevents duplicate subject assignments
- `question_answers(exam_question_id, student_id)` - One answer per student per question
- `exam_attempts(student_id, subject_exam_id)` - Tracks student attempts per exam

### 3. **Cardinality Relationships**

- **1:N** - TOPICS → SUBJECTS, SUBJECTS → SUBJECT_EXAMS, etc.
- **M:N** - Implemented via junction tables (CLASS_STUDENTS, CLASS_SUBJECTS, etc.)
- **Soft Deletions** - Not explicitly used; cascading deletes implemented instead

### 4. **Temporal Tracking**

- All tables include `created_at` and `updated_at` timestamps
- Special temporal fields: `email_verified_at`, `completed_at`

### 5. **Nullable Fields**

Used strategically for:

- Optional content (`subjects.content`)
- Optional feedback (`question_answers.feedback`)
- Optional notes (`class_students.rapport`)
- Optional student answers in essay questions (`question_answers.answer_text`)

---

## Migration Timeline

### Phase 1: Core Tables (2025-08-02 to 2025-08-03)

- Users, Topics, Subjects
- ClassRooms, ClassStudents, ClassSubjects
- SubjectExams, ExamQuestions, QuestionOptions
- QuestionAnswers, ExamAttempts

### Phase 2: Authentication & Permissions (2025-08-04)

- Permission tables (Spatie Laravel Permission)
- Personal access tokens (Laravel Sanctum)

### Phase 3: Schema Refinements (2025-08-05 to 2025-08-11)

- Made `completed_at` nullable in ExamAttempts
- Added `total_attempts` to ExamAttempts
- Added `feedback` to QuestionAnswers
- Added `total_points` to SubjectExams
- Added scoring fields: `total_points`, `points_earned`, `has_passed` to ExamAttempts

---

## Index Strategy

**Indexes on Foreign Keys:**

- `USERS.id` (implicit from PK)
- `TOPICS.id` (implicit from PK)
- `SUBJECTS.topic_id`, `SUBJECTS.teacher_id`
- `CLASS_ROOMS.id` (implicit from PK)
- `CLASS_STUDENTS.student_id`, `CLASS_STUDENTS.class_room_id`
- `CLASS_SUBJECTS.class_room_id`, `CLASS_SUBJECTS.subject_id`
- `SUBJECT_EXAMS.subject_id`
- `EXAM_QUESTIONS.subject_exam_id`
- `QUESTION_OPTIONS.exam_question_id`
- `QUESTION_ANSWERS.exam_question_id`, `QUESTION_ANSWERS.student_id`
- `EXAM_ATTEMPTS.student_id`, `EXAM_ATTEMPTS.subject_exam_id`

**Indexes on Search Fields:**

- `USERS.email` (UNIQUE)
- `SUBJECTS.name` (UNIQUE)
- `TOPICS.name` (UNIQUE)
- `CLASS_ROOMS.name` (UNIQUE)

---

## Summary Statistics

| Metric                | Count |
| --------------------- | ----- |
| Total Tables          | 17    |
| Core Domain Tables    | 11    |
| System/Utility Tables | 6     |
| Relationships         | 15+   |
| Unique Constraints    | 8     |
| Foreign Keys          | 14    |
| Timestamps Tracked    | 17    |

---

## Migration Commands

View all migrations:

```bash
php artisan migrate:status
```

Run all migrations:

```bash
php artisan migrate
```

Rollback all migrations:

```bash
php artisan migrate:reset
```

Create a new migration:

```bash
php artisan make:migration create_table_name
```

---

**Last Updated:** 2025-08-11
**Database Type:** MySQL
**Framework:** Laravel 11
