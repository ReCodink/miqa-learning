# Mentorship-Driven Learning Community - Database ERD

## Advanced Relational Database Schema Design

**Document Type:** Database Architecture
**Design Pattern:** Event-Driven, Role-Based Access Control (RBAC)
**Target System:** Mentorship Platform with Process Learning & Certification Tracking
**Framework:** Laravel 11 + PostgreSQL
**Date:** 2026-04-29

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Visual ERD Diagram](#visual-erd-diagram)
3. [Core Domain Entities](#core-domain-entities)
4. [RBAC &amp; Role Management](#rbac--role-management)
5. [Process Learning Tracking](#process-learning-tracking)
6. [Certification &amp; Assessment](#certification--assessment)
7. [Presence &amp; QR System](#presence--qr-system)
8. [Temporal &amp; Visibility Logic](#temporal--visibility-logic)
9. [Data Workflows](#data-workflows)
10. [Migration Strategy](#migration-strategy)

---

## Executive Summary

This schema supports a **non-hierarchical mentorship platform** where:

- **Private Mentors** provide 1:1 guidance (process-focused)
- **Public Mentors** manage group expertise and curriculum
- **Speakers** deliver transient modules
- **Mentees** learn through process engagement and certification
- **Alumni** maintain community access after graduation

**Key Innovations:**

- ✅ Dual-track assessment (Process + Certification)
- ✅ Result visibility control with graduation triggers
- ✅ Unified QR-based presence system with GPS geofencing
- ✅ Automatic role transition on graduation
- ✅ Comprehensive audit trail for compliance

---

## Visual ERD Diagram

```
╔════════════════════════════════════════════════════════════════════════════════╗
║                        CORE USERS & ROLES STRUCTURE                            ║
╚════════════════════════════════════════════════════════════════════════════════╝

                              ┌──────────────────┐
                              │  USERS (Base)    │
                              │  ┌──────────────┐│
                              │  │ id (PK)      ││
                              │  │ email        ││
                              │  │ name         ││
                              │  │ password     ││
                              │  │ avatar_url   ││
                              │  │ timestamps   ││
                              │  └──────────────┘│
                              └────────┬─────────┘
                                       │
                    ┌──────────────────┼──────────────────┐
                    │                  │                  │
          ┌─────────▼──────┐  ┌────────▼────────┐  ┌────▼────────────┐
          │  ROLE_USERS    │  │  ROLE_USERS     │  │  ROLE_USERS     │
          │(Mentors:Private)  │  (Mentors:Public)   │  (Speakers)      │
          │┌────────────────┤  │┌────────────────┤  │┌───────────────┐
          ││ id (PK)        │  ││ id (PK)        │  ││ id (PK)       │
          ││ user_id (FK)   │  ││ user_id (FK)   │  ││ user_id (FK)  │
          ││ role_type      │  ││ role_type      │  ││ role_type     │
          ││ expertise_bio  │  ││ expertise_area │  ││ modules_taught│
          ││ max_mentees    │  ││ mentee_groups  │  ││ availability  │
          ││ is_active      │  ││ is_active      │  ││ is_active     │
          ││ created_at     │  ││ created_at     │  ││ created_at    │
          │└────────────────┘  │└────────────────┘  │└───────────────┘
          └────────┬───────────┘                    └──────────────────┘
                   │ 1:N                                      │ 1:N
                   │ (Private Mentor)                         │ (Speaker)
                   │ guides 1+ Mentees                        │ teaches Modules
                   │
        ┌──────────▼─────────────────────────────────────┐
        │                                                │
        │ MENTEE_GROUPS (Learning Cohorts)              │
        │┌─────────────────────────────────────┐        │
        ││ id (PK)                             │        │
        ││ group_name                          │        │
        ││ description                         │        │
        ││ public_mentor_id (FK) ──► ROLE_USERS       │
        ││ max_capacity                        │        │
        ││ start_date                          │        │
        ││ expected_graduation_date            │        │
        ││ curriculum_focus                    │        │
        ││ is_active                           │        │
        ││ created_at                          │        │
        │└──────────┬───────────────────────────┘       │
        └───────────┼────────────────────────────────────┘
                    │ 1:N
                    │
        ┌───────────▼──────────────────────────────────┐
        │                                              │
        │ MENTEES (Role Instances for Mentee Users)   │
        │┌────────────────────────────────────────────┐
        ││ id (PK)                                    │
        ││ user_id (FK) ──► USERS                   │
        ││ mentee_group_id (FK) ──► MENTEE_GROUPS   │
        ││ private_mentor_id (FK) ──► ROLE_USERS    │
        ││ current_status (Active/Graduated/Paused)  │
        ││ graduation_date (NULLABLE - set trigger)  │
        ││ role_transitioned_to_alumnus (BOOLEAN)    │
        ││ enrolled_at                                │
        ││ created_at                                 │
        │└────────┬───────────────────────────────────┘
        └─────────┼──────────────────────────────────────────────────────┐
                  │                                                      │
                  │ ON graduation_date:                                  │
                  │ - Create ALUMNI record                              │
                  │ - Set role_transitioned_to_alumnus = true           │
                  │ - Unlock hidden results                             │
                  │ - Grant persistent community access                 │
                  │
        ┌─────────▼───────────────────────────────────────────────────┐
        │                                                             │
        │ ALUMNI (Post-Graduation Community Access)                  │
        │┌──────────────────────────────────────────────────────────┐
        ││ id (PK)                                                  │
        ││ user_id (FK) ──► USERS                                │
        ││ mentee_id (FK) ──► MENTEES                            │
        ││ mentee_group_id (FK) ──► MENTEE_GROUPS               │
        ││ graduation_date                                        │
        ││ final_grade                                            │
        ││ community_access_level                                 │
        ││ can_mentor_new_mentees (BOOLEAN)                       │
        ││ created_at                                             │
        │└──────────────────────────────────────────────────────────┘
        └──────────────────────────────────────────────────────────────┘


╔════════════════════════════════════════════════════════════════════════════════╗
║                    PROCESS LEARNING TRACKING STRUCTURE                          ║
╚════════════════════════════════════════════════════════════════════════════════╝

        ┌─────────────────────────────────────────────────┐
        │                                                 │
        │ LEARNING_MODULES (Curriculum Units)             │
        │┌───────────────────────────────────────────────┐
        ││ id (PK)                                       │
        ││ module_name                                   │
        ││ description                                   │
        ││ speaker_id (FK) ──► ROLE_USERS              │
        ││ learning_objectives                          │
        ││ estimated_duration_hours                     │
        ││ content_url                                  │
        ││ module_sequence_order                        │
        ││ created_at                                   │
        │└──────────────┬────────────────────────────────┘
        └───────────────┼───────────────────────────────────┐
                        │ 1:N                               │
                        │                                   │
        ┌───────────────▼───────────────────┐ ┌────────────▼──────────────────┐
        │                                   │ │                               │
        │ LEARNING_DISCUSSIONS              │ │ PRACTICE_SESSIONS             │
        │(Group Discussions & Peer Learning)  │ (Hands-On Engagement)         │
        │┌─────────────────────────────────┐ │┌─────────────────────────────┐
        ││ id (PK)                         │ ││ id (PK)                     │
        ││ module_id (FK)                  │ ││ module_id (FK)              │
        ││ mentee_group_id (FK)            │ ││ mentee_group_id (FK)        │
        ││ private_mentor_id (FK)          │ ││ private_mentor_id (FK)      │
        ││ discussion_topic                │ ││ practice_type               │
        ││ discussion_notes                │ ││ assignment_details          │
        ││ participation_count             │ ││ submission_deadline         │
        ││ discussion_date                 │ ││ created_at                  │
        ││ created_at                      │ │└────────────┬────────────────┘
        │└────────────┬────────────────────┘ └────────────┤ 1:N
        └─────────────┼──────────────────────────────────┤
                      │ 1:N                              │
                      │                                  │
        ┌─────────────▼──────────────────────────────┐  │
        │                                            │  │
        │ DISCUSSION_PARTICIPATION                  │  │
        │(Individual Engagement Records)             │  │
        │┌──────────────────────────────────────────┐│  │
        ││ id (PK)                                  ││  │
        ││ discussion_id (FK)                       ││  │
        ││ mentee_id (FK)                           ││  │
        ││ contribution_text                        ││  │
        ││ engagement_score (0-100)                 ││  │
        ││ mentor_feedback                          ││  │
        ││ posted_at                                ││  │
        │└──────────────────────────────────────────┘│  │
        └────────────────────────────────────────────┘  │
                                                         │
        ┌────────────────────────────────────────────────▼──────┐
        │                                                       │
        │ PRACTICE_SUBMISSIONS                                 │
        │(Student Exercise Submissions)                        │
        │┌──────────────────────────────────────────────────────┐
        ││ id (PK)                                            │
        ││ practice_session_id (FK)                           │
        ││ mentee_id (FK)                                     │
        ││ submission_content_url                             │
        ││ submission_notes                                   │
        ││ submitted_at                                       │
        ││ mentor_feedback                                    │
        ││ practice_score (0-100)                            │
        ││ feedback_date                                      │
        │└────────────────────────────────────────────────────────┘
        └──────────────────────────────────────────────────────────┘


        ┌─────────────────────────────────────────────────────┐
        │                                                     │
        │ ENGAGEMENT_MILESTONES (Progress Tracking)           │
        │┌───────────────────────────────────────────────────┐
        ││ id (PK)                                           │
        ││ mentee_id (FK)                                    │
        ││ milestone_type (attendance_threshold/module_comp) │
        ││ milestone_description                             │
        ││ achievement_date                                  │
        ││ points_earned                                     │
        ││ created_at                                        │
        │└───────────────────────────────────────────────────┘
        └─────────────────────────────────────────────────────┘


╔════════════════════════════════════════════════════════════════════════════════╗
║                  CERTIFICATION & GRADING STRUCTURE                              ║
╚════════════════════════════════════════════════════════════════════════════════╝

        ┌────────────────────────────────────────────────────────┐
        │                                                        │
        │ CERTIFICATION_EXAMS (Formal Assessment)                │
        │┌──────────────────────────────────────────────────────┐
        ││ id (PK)                                              │
        ││ exam_name                                            │
        ││ description                                          │
        ││ learning_module_id (FK)                              │
        ││ passing_score_percentage                             │
        ││ total_questions                                      │
        ││ duration_minutes                                     │
        ││ available_from_date                                  │
        ││ available_until_date                                 │
        ││ is_active                                            │
        ││ created_at                                           │
        │└────────────┬─────────────────────────────────────────┘
        └─────────────┼─────────────────────────────────────────────┐
                      │ 1:N                                         │
                      │                                             │
        ┌─────────────▼──────────────────┐   ┌───────────────────┤
        │                                │   │                   │
        │ EXAM_QUESTIONS                 │   │ EXAM_ATTEMPTS     │
        │(Question Bank)                 │   │(Student Exams)    │
        │┌────────────────────────────────┐   │┌──────────────────┐
        ││ id (PK)                        │   ││ id (PK)          │
        ││ exam_id (FK)                   │   ││ exam_id (FK)     │
        ││ question_text                  │   ││ mentee_id (FK)   │
        ││ question_type (MCQ/Essay/code) │   ││ attempt_number   │
        ││ points                         │   ││ started_at       │
        ││ sequence_order                 │   ││ completed_at     │
        ││ created_at                     │   ││ total_score      │
        │└────────────┬─────────────────────  ││ passing_status   │
        └─────────────┼──────────────────────→ ││ is_results_visible│
                      │ 1:N                   ││ visibility_unlocked_at
                      │                       ││ created_at       │
        ┌─────────────▼───────────────────────┤└──────────────────┘
        │                                     └───────────┬────────┐
        │ QUESTION_OPTIONS                                │        │
        │(MCQ Answer Choices)                            │ 1:N    │
        │┌──────────────────────────────────┐            │        │
        ││ id (PK)                          │            │        │
        ││ question_id (FK)                 │            │        │
        ││ option_text                      │            │        │
        ││ is_correct_answer                │            │        │
        ││ sequence_order                   │            │        │
        │└──────────────────────────────────┘            │        │
        └─────────────────────────────────────────────────┼────────┤
                                                          │        │
        ┌─────────────────────────────────────────────────▼──────┐ │
        │                                                        │ │
        │ EXAM_ANSWERS (Answer Tracking)                        │ │
        │┌────────────────────────────────────────────────────┐ │ │
        ││ id (PK)                                            │ │ │
        ││ exam_attempt_id (FK)                               │ │ │
        ││ question_id (FK)                                   │ │ │
        ││ answer_text                                        │ │ │
        ││ selected_option_id (FK, NULLABLE)                  │ │ │
        ││ points_awarded                                     │ │ │
        ││ is_correct                                         │ │ │
        ││ answered_at                                        │ │ │
        │└────────────────────────────────────────────────────┘ │ │
        └──────────────────────────────────────────────────────┘ │ │
                                                                  │ │
        ┌─────────────────────────────────────────────────────────▼─┘
        │
        │ CERTIFICATION_RESULTS (Hidden Until Graduation)
        │┌──────────────────────────────────────────────────────┐
        ││ id (PK)                                              │
        ││ exam_attempt_id (FK)                                 │
        ││ mentee_id (FK)                                       │
        ││ exam_id (FK)                                         │
        ││ total_score                                          │
        ││ passing_status (Passed/Failed)                       │
        ││ is_results_visible (BOOLEAN - HIDDEN BY DEFAULT)    │
        ││ visibility_unlock_date (Graduation Date Trigger)     │
        ││ mentor_feedback                                      │
        ││ recorded_at                                          │
        ││ visibility_changed_at (NULLABLE - when unlocked)     │
        │└──────────────────────────────────────────────────────┘
        └──────────────────────────────────────────────────────────┘


        ┌──────────────────────────────────────────────────────┐
        │                                                      │
        │ PROCESS_LEARNING_GRADES (Engagement Assessment)     │
        │┌────────────────────────────────────────────────────┐
        ││ id (PK)                                            │
        ││ mentee_id (FK)                                     │
        ││ mentee_group_id (FK)                               │
        ││ grading_period_start_date                          │
        ││ grading_period_end_date                            │
        ││ attendance_score (0-100) [from PRESENCE data]      │
        ││ discussion_participation_score (0-100)             │
        ││ practice_submission_score (0-100)                  │
        ││ engagement_milestone_score (0-100)                 │
        ││ process_learning_grade (Weighted Avg)              │
        ││ mentor_comments                                    │
        ││ recorded_date                                      │
        ││ created_at                                         │
        │└────────────────────────────────────────────────────┘
        └──────────────────────────────────────────────────────┘


        ┌──────────────────────────────────────────────────────────┐
        │                                                          │
        │ FINAL_GRADES (Composite Assessment - Process + Cert)    │
        │┌──────────────────────────────────────────────────────┐ │
        ││ id (PK)                                              │ │
        ││ mentee_id (FK)                                       │ │
        ││ mentee_group_id (FK)                                 │ │
        ││ assessment_period_start_date                         │ │
        ││ assessment_period_end_date                           │ │
        ││ process_learning_weight (%) [default: 40%]          │ │
        ││ certification_exam_weight (%) [default: 60%]         │ │
        ││ process_learning_score (0-100)                       │ │
        ││ certification_exam_score (0-100)                     │ │
        ││ final_composite_grade (Weighted Calculation)         │ │
        ││ is_results_visible (BOOLEAN - INHERIT from CERT)    │ │
        ││ recorded_date                                        │ │
        ││ created_at                                           │ │
        │└──────────────────────────────────────────────────────┘ │
        └──────────────────────────────────────────────────────────┘

        ┌────────────────────────────────────────────────────┐
        │ Weighted Grade Calculation Formula:                │
        │ Final Grade = (Process × 0.40) + (Certification × 0.60) │
        │ (Configurable per institution)                     │
        └────────────────────────────────────────────────────┘


╔════════════════════════════════════════════════════════════════════════════════╗
║                   QR PRESENCE & ATTENDANCE SYSTEM                               ║
╚════════════════════════════════════════════════════════════════════════════════╝

        ┌─────────────────────────────────────────────┐
        │                                             │
        │ QR_CODES (Dynamic UUID-Based Codes)         │
        │┌───────────────────────────────────────────┐
        ││ id (PK)                                   │
        ││ uuid_v4 (UNIQUE, NOT NULL)                │
        ││ event_id (FK) ──► PRESENCE_EVENTS        │
        ││ created_by_user_id (FK) ──► USERS        │
        ││ created_at (Generation Timestamp)         │
        ││ expires_at (30 sec expiration window)     │
        ││ is_expired (BOOLEAN)                      │
        ││ is_revoked (BOOLEAN - for manual cancel)  │
        │└───────────────────────────────────────────┘
        └─────────────┬───────────────────────────────┘
                      │ 1:N
                      │
        ┌─────────────▼───────────────────────────────────┐
        │                                                 │
        │ PRESENCE_EVENTS (Session/Event Registry)        │
        │┌───────────────────────────────────────────────┐
        ││ id (PK)                                       │
        ││ event_name                                    │
        ││ event_type (discussion/practice/module)       │
        ││ mentee_group_id (FK)                          │
        ││ hosted_by_user_id (FK) ──► USERS            │
        ││ scheduled_start_time                         │
        ││ scheduled_end_time                           │
        ││ venue_name                                    │
        ││ gps_latitude                                  │
        ││ gps_longitude                                 │
        ││ gps_radius_meters (geofence boundary)         │
        ││ is_event_active (BOOLEAN)                    │
        ││ created_at                                    │
        │└────────────┬──────────────────────────────────┘
        └─────────────┼────────────────────────────────────┐
                      │ 1:N                                │
                      │                                    │
        ┌─────────────▼──────────────────────────────────┐ │
        │                                                │ │
        │ PRESENCE (Unified Attendance Table)           │ │
        │ ✓ Works for Mentors, Speakers, Mentees        │ │
        │┌────────────────────────────────────────────┐ │ │
        ││ id (PK)                                    │ │ │
        ││ qr_code_id (FK) ──► QR_CODES              │ │ │
        ││ event_id (FK)                              │ │ │
        ││ user_id (FK) ──► USERS (all roles)        │ │ │
        ││ user_role_type (mentor/speaker/mentee)    │ │ │
        ││ check_in_time                              │ │ │
        ││ check_in_gps_latitude                       │ │ │
        ││ check_in_gps_longitude                      │ │ │
        ││ is_within_geofence (BOOLEAN)               │ │ │
        ││ geofence_distance_meters                    │ │ │
        ││ is_gps_verified (BOOLEAN)                  │ │ │
        ││ device_fingerprint (NULLABLE)              │ │ │
        ││ check_out_time (NULLABLE)                  │ │ │
        ││ total_duration_minutes                      │ │ │
        ││ is_valid_attendance (BOOLEAN)              │ │ │
        ││ fraud_flags (JSON - suspicious activity)   │ │ │
        ││ created_at                                  │ │ │
        │└────────────────────────────────────────────┘ │ │
        └──────────────────────────────────────────────┘ │ │
                                                          │ │
        ┌────────────────────────────────────────────────▼─┘
        │
        │ QR Validation Rules:
        │ - UUID expires in 30 seconds after generation
        │ - GPS geofence check: within gps_radius_meters
        │ - Distance calculation: Haversine formula
        │ - Device fingerprint: prevent replay attacks
        │ - Duplicate check: one check-in per user per event
        └────────────────────────────────────────────────┘
```

---

## Core Domain Entities

### 1. **USERS** (Base User Table)

| Column            | Type         | Constraints        | Description            |
| ----------------- | ------------ | ------------------ | ---------------------- |
| id                | BIGINT       | PK, AUTO_INCREMENT | User unique identifier |
| email             | VARCHAR(255) | UNIQUE, NOT NULL   | Email address          |
| name              | VARCHAR(255) | NOT NULL           | Full name              |
| password          | VARCHAR(255) | NOT NULL           | Encrypted password     |
| avatar_url        | VARCHAR(255) | NULLABLE           | Profile picture        |
| email_verified_at | TIMESTAMP    | NULLABLE           | Email verification     |
| created_at        | TIMESTAMP    | DEFAULT NOW        | Registration time      |
| updated_at        | TIMESTAMP    | DEFAULT NOW        | Last update            |

**Indexes:** `email (UNIQUE)`, `created_at`

---

### 2. **ROLE_USERS** (Polymorphic Role Management)

| Column            | Type         | Constraints        | Description                                        |
| ----------------- | ------------ | ------------------ | -------------------------------------------------- |
| id                | BIGINT       | PK, AUTO_INCREMENT | Role instance ID                                   |
| user_id           | BIGINT       | FK → USERS        | Associated user                                    |
| role_type         | ENUM         | NOT NULL           | `private_mentor`, `public_mentor`, `speaker` |
| expertise_bio     | TEXT         | NULLABLE           | Professional background                            |
| expertise_area    | VARCHAR(255) | NULLABLE           | Subject expertise                                  |
| modules_taught    | VARCHAR(255) | NULLABLE           | Comma-separated module IDs                         |
| max_mentees       | INT          | NULLABLE           | Capacity for private mentors                       |
| mentee_groups     | VARCHAR(255) | NULLABLE           | Assigned groups for public mentors                 |
| availability_json | JSON         | NULLABLE           | Available time slots                               |
| is_active         | BOOLEAN      | DEFAULT TRUE       | Active role status                                 |
| verified_by_admin | BOOLEAN      | DEFAULT FALSE      | Admin verification                                 |
| created_at        | TIMESTAMP    | DEFAULT NOW        | Role creation                                      |
| updated_at        | TIMESTAMP    | DEFAULT NOW        | Last update                                        |

**Constraints:**

- UNIQUE(user_id, role_type) - one role per type per user
- Foreign Key: user_id → users(id) ON DELETE CASCADE

**Indexes:** `(user_id, role_type)`, `role_type`, `is_active`

---

### 3. **MENTEE_GROUPS** (Learning Cohorts)

| Column                   | Type         | Constraints        | Description              |
| ------------------------ | ------------ | ------------------ | ------------------------ |
| id                       | BIGINT       | PK, AUTO_INCREMENT | Group ID                 |
| group_name               | VARCHAR(255) | NOT NULL           | Cohort name              |
| description              | TEXT         | NULLABLE           | Group purpose/focus      |
| public_mentor_id         | BIGINT       | FK → ROLE_USERS   | Group facilitator        |
| max_capacity             | INT          | DEFAULT 30         | Max students per group   |
| current_enrollment       | INT          | DEFAULT 0          | Current enrollment count |
| start_date               | DATE         | NOT NULL           | Program start date       |
| expected_graduation_date | DATE         | NOT NULL           | Expected completion date |
| curriculum_focus         | VARCHAR(255) | NULLABLE           | Subject focus area       |
| is_active                | BOOLEAN      | DEFAULT TRUE       | Group status             |
| created_at               | TIMESTAMP    | DEFAULT NOW        | Creation time            |
| updated_at               | TIMESTAMP    | DEFAULT NOW        | Last update              |

**Constraints:**

- Foreign Key: public_mentor_id → role_users(id) ON DELETE SET NULL
- CHECK: max_capacity > 0

**Indexes:** `public_mentor_id`, `is_active`, `expected_graduation_date`

---

### 4. **MENTEES** (Learner Instances - Process Trigger Point)

| Column                       | Type      | Constraints         | Description                                               |
| ---------------------------- | --------- | ------------------- | --------------------------------------------------------- |
| id                           | BIGINT    | PK, AUTO_INCREMENT  | Mentee instance ID                                        |
| user_id                      | BIGINT    | FK → USERS         | Associated user                                           |
| mentee_group_id              | BIGINT    | FK → MENTEE_GROUPS | Assigned cohort                                           |
| private_mentor_id            | BIGINT    | FK → ROLE_USERS    | 1:1 facilitator                                           |
| current_status               | ENUM      | DEFAULT 'active'    | `active`, `graduated`, `paused`, `withdrawn`      |
| enrolled_at                  | TIMESTAMP | DEFAULT NOW         | Enrollment time                                           |
| graduation_date              | TIMESTAMP | NULLABLE            | **KEY TRIGGER**: When set, triggers role transition |
| role_transitioned_to_alumnus | BOOLEAN   | DEFAULT FALSE       | Tracks transition completion                              |
| transitioned_at              | TIMESTAMP | NULLABLE            | Timestamp of role transition                              |
| created_at                   | TIMESTAMP | DEFAULT NOW         | Record creation                                           |
| updated_at                   | TIMESTAMP | DEFAULT NOW         | Last update                                               |

**Constraints:**

- UNIQUE(user_id, mentee_group_id) - one instance per group per user
- Foreign Keys:
  - user_id → users(id) ON DELETE CASCADE
  - mentee_group_id → mentee_groups(id) ON DELETE CASCADE
  - private_mentor_id → role_users(id) ON DELETE SET NULL
- CHECK: graduation_date IS NULL OR graduation_date >= enrolled_at

**Indexes:** `user_id`, `mentee_group_id`, `private_mentor_id`, `graduation_date`, `current_status`

**Database Trigger Logic:**

```sql
-- Pseudo-trigger: When graduation_date reaches TODAY and role_transitioned_to_alumnus = FALSE:
-- 1. Create corresponding ALUMNI record
-- 2. Set role_transitioned_to_alumnus = TRUE
-- 3. Unlock all certification results (is_results_visible = TRUE)
-- 4. Update CERTIFICATION_RESULTS.visibility_unlocked_at = NOW()
-- 5. Log event for audit trail
```

---

### 5. **ALUMNI** (Post-Graduation Community Access)

| Column                   | Type         | Constraints         | Description                         |
| ------------------------ | ------------ | ------------------- | ----------------------------------- |
| id                       | BIGINT       | PK, AUTO_INCREMENT  | Alumni record ID                    |
| user_id                  | BIGINT       | FK → USERS         | Graduated user                      |
| mentee_id                | BIGINT       | FK → MENTEES       | Reference to completion             |
| mentee_group_id          | BIGINT       | FK → MENTEE_GROUPS | Original cohort                     |
| graduation_date          | DATE         | NOT NULL            | Graduation date                     |
| final_grade              | DECIMAL(5,2) | NULLABLE            | Final composite grade               |
| gpa_equivalent           | DECIMAL(4,3) | NULLABLE            | GPA conversion                      |
| community_access_level   | ENUM         | DEFAULT 'member'    | `member`, `mentor`, `speaker` |
| can_mentor_new_mentees   | BOOLEAN      | DEFAULT FALSE       | Eligibility to mentor               |
| permanent_access_granted | BOOLEAN      | DEFAULT TRUE        | Perpetual platform access           |
| created_at               | TIMESTAMP    | DEFAULT NOW         | Record creation                     |
| updated_at               | TIMESTAMP    | DEFAULT NOW         | Last update                         |

**Constraints:**

- Foreign Keys:
  - user_id → users(id) ON DELETE CASCADE
  - mentee_id → mentees(id) ON DELETE CASCADE
  - mentee_group_id → mentee_groups(id) ON DELETE SET NULL
- UNIQUE(user_id) - one alumni record per user

**Indexes:** `user_id`, `graduation_date`, `community_access_level`

---

## RBAC & Role Management

### Role Hierarchy & Permissions

```
┌─────────────────────────────────────────────────────────┐
│              ROLE-BASED ACCESS CONTROL                  │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ PRIVATE MENTOR (1:1 Facilitator)                        │
│ ├─ Can view assigned mentees' data                     │
│ ├─ Can provide feedback on practice submissions        │
│ ├─ Can mark discussion attendance                      │
│ ├─ Can view (but NOT edit) certification results      │
│ ├─ Cannot create or modify exams                       │
│ └─ Can generate QR codes for sessions                 │
│                                                         │
│ PUBLIC MENTOR (Group Facilitator)                       │
│ ├─ Manages mentee group curriculum                     │
│ ├─ Creates learning modules & discussions              │
│ ├─ Manages group-level attendance                      │
│ ├─ Views aggregated group performance                  │
│ ├─ Cannot create certification exams                   │
│ ├─ Can assign private mentors                          │
│ └─ Can generate QR codes for group sessions            │
│                                                         │
│ SPEAKER (Transient Contributor)                         │
│ ├─ Creates learning modules (one-time contributors)    │
│ ├─ Delivers specific content                           │
│ ├─ Cannot assign grades                                │
│ ├─ Can check attendance via QR                         │
│ ├─ No access to mentee personal data                   │
│ └─ Limited platform access (time-bound)                │
│                                                         │
│ MENTEE (Learner)                                        │
│ ├─ Access own learning materials                       │
│ ├─ Submit practice assignments                         │
│ ├─ Participate in discussions                          │
│ ├─ Take certification exams                            │
│ ├─ Check personal progress (process metrics only)      │
│ ├─ CANNOT access certification results until grad date │
│ ├─ Check-in via QR codes                               │
│ └─ Limited peer visibility (group members only)         │
│                                                         │
│ ALUMNUS (Post-Graduation)                               │
│ ├─ View own permanent grades & certificates            │
│ ├─ Access alumni-only community resources              │
│ ├─ Optional: Mentor new mentees (if approved)          │
│ ├─ Perpetual platform access                           │
│ ├─ Career development resources                        │
│ └─ Can participate in alumni events                    │
│                                                         │
│ ADMIN (System Administrator)                            │
│ ├─ Full access to all data                             │
│ ├─ Manage users & roles                                │
│ ├─ Create/edit certification exams                     │
│ ├─ Unlock results manually (audit trail)               │
│ ├─ View audit logs & fraud flags                       │
│ ├─ Manage graduation transitions                       │
│ └─ System configuration                                │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## Process Learning Tracking

### 6. **LEARNING_MODULES** (Curriculum Units)

| Column                   | Type         | Constraints        | Description             |
| ------------------------ | ------------ | ------------------ | ----------------------- |
| id                       | BIGINT       | PK, AUTO_INCREMENT | Module ID               |
| module_name              | VARCHAR(255) | NOT NULL           | Topic/unit name         |
| description              | TEXT         | NULLABLE           | Module overview         |
| speaker_id               | BIGINT       | FK → ROLE_USERS   | Content creator         |
| learning_objectives      | JSON         | NULLABLE           | Learning outcomes array |
| estimated_duration_hours | INT          | DEFAULT 1          | Estimated time          |
| content_url              | VARCHAR(255) | NULLABLE           | Resource link           |
| module_sequence_order    | INT          | NULLABLE           | Position in curriculum  |
| is_published             | BOOLEAN      | DEFAULT FALSE      | Release status          |
| published_date           | TIMESTAMP    | NULLABLE           | When made available     |
| created_at               | TIMESTAMP    | DEFAULT NOW        | Creation time           |
| updated_at               | TIMESTAMP    | DEFAULT NOW        | Last update             |

**Indexes:** `speaker_id`, `is_published`, `module_sequence_order`

---

### 7. **LEARNING_DISCUSSIONS** (Collaborative Learning)

| Column              | Type         | Constraints            | Description            |
| ------------------- | ------------ | ---------------------- | ---------------------- |
| id                  | BIGINT       | PK, AUTO_INCREMENT     | Discussion ID          |
| module_id           | BIGINT       | FK → LEARNING_MODULES | Related module         |
| mentee_group_id     | BIGINT       | FK → MENTEE_GROUPS    | Participating group    |
| private_mentor_id   | BIGINT       | FK → ROLE_USERS       | Facilitator            |
| discussion_topic    | VARCHAR(255) | NOT NULL               | Topic title            |
| discussion_notes    | TEXT         | NULLABLE               | Mentor notes           |
| participation_count | INT          | DEFAULT 0              | Number of participants |
| discussion_date     | TIMESTAMP    | NOT NULL               | When held              |
| created_at          | TIMESTAMP    | DEFAULT NOW            | Record creation        |

**Indexes:** `mentee_group_id`, `private_mentor_id`, `discussion_date`

---

### 8. **DISCUSSION_PARTICIPATION** (Engagement Records)

| Column            | Type      | Constraints                | Description               |
| ----------------- | --------- | -------------------------- | ------------------------- |
| id                | BIGINT    | PK, AUTO_INCREMENT         | Participation ID          |
| discussion_id     | BIGINT    | FK → LEARNING_DISCUSSIONS | Discussion ref            |
| mentee_id         | BIGINT    | FK → MENTEES              | Participant               |
| contribution_text | TEXT      | NOT NULL                   | Student input             |
| engagement_score  | INT       | DEFAULT 0                  | Participation score 0-100 |
| mentor_feedback   | TEXT      | NULLABLE                   | Qualitative feedback      |
| posted_at         | TIMESTAMP | DEFAULT NOW                | Submission time           |

**Constraints:**

- UNIQUE(discussion_id, mentee_id) - max 1 contribution per person per discussion
- CHECK: engagement_score BETWEEN 0 AND 100

**Indexes:** `discussion_id`, `mentee_id`, `posted_at`

---

### 9. **PRACTICE_SESSIONS** (Hands-On Engagement)

| Column              | Type         | Constraints            | Description                             |
| ------------------- | ------------ | ---------------------- | --------------------------------------- |
| id                  | BIGINT       | PK, AUTO_INCREMENT     | Session ID                              |
| module_id           | BIGINT       | FK → LEARNING_MODULES | Related module                          |
| mentee_group_id     | BIGINT       | FK → MENTEE_GROUPS    | Target group                            |
| private_mentor_id   | BIGINT       | FK → ROLE_USERS       | Facilitator                             |
| practice_type       | VARCHAR(255) | NOT NULL               | e.g., "coding", "writing", "case-study" |
| assignment_details  | TEXT         | NOT NULL               | Instructions/rubric                     |
| submission_deadline | TIMESTAMP    | NOT NULL               | Due date                                |
| created_at          | TIMESTAMP    | DEFAULT NOW            | Session creation                        |

**Indexes:** `mentee_group_id`, `submission_deadline`

---

### 10. **PRACTICE_SUBMISSIONS** (Student Deliverables)

| Column                 | Type         | Constraints             | Description               |
| ---------------------- | ------------ | ----------------------- | ------------------------- |
| id                     | BIGINT       | PK, AUTO_INCREMENT      | Submission ID             |
| practice_session_id    | BIGINT       | FK → PRACTICE_SESSIONS | Assignment ref            |
| mentee_id              | BIGINT       | FK → MENTEES           | Submitter                 |
| submission_content_url | VARCHAR(255) | NOT NULL                | File/code repository link |
| submission_notes       | TEXT         | NULLABLE                | Student comments          |
| submitted_at           | TIMESTAMP    | NOT NULL                | Submission time           |
| mentor_feedback        | TEXT         | NULLABLE                | Evaluator comments        |
| practice_score         | INT          | DEFAULT 0               | Grade 0-100               |
| feedback_date          | TIMESTAMP    | NULLABLE                | When feedback given       |

**Constraints:**

- UNIQUE(practice_session_id, mentee_id) - one submission per student
- CHECK: practice_score BETWEEN 0 AND 100
- CHECK: feedback_date IS NULL OR feedback_date >= submitted_at

**Indexes:** `practice_session_id`, `mentee_id`, `submitted_at`, `feedback_date`

---

### 11. **ENGAGEMENT_MILESTONES** (Progress Tracking)

| Column                | Type         | Constraints        | Description                                                              |
| --------------------- | ------------ | ------------------ | ------------------------------------------------------------------------ |
| id                    | BIGINT       | PK, AUTO_INCREMENT | Milestone ID                                                             |
| mentee_id             | BIGINT       | FK → MENTEES      | Achiever                                                                 |
| milestone_type        | VARCHAR(255) | NOT NULL           | e.g., "attended_3_sessions", "completed_module", "100_discussion_points" |
| milestone_description | VARCHAR(255) | NOT NULL           | Human-readable text                                                      |
| achievement_date      | TIMESTAMP    | DEFAULT NOW        | When achieved                                                            |
| points_earned         | INT          | DEFAULT 0          | Gamification points                                                      |
| is_badge_worthy       | BOOLEAN      | DEFAULT FALSE      | Achievement badge eligibility                                            |
| created_at            | TIMESTAMP    | DEFAULT NOW        | Record creation                                                          |

**Indexes:** `mentee_id`, `achievement_date`, `milestone_type`

---

## Certification & Assessment

### 12. **CERTIFICATION_EXAMS** (Formal Assessments)

| Column                   | Type         | Constraints            | Description               |
| ------------------------ | ------------ | ---------------------- | ------------------------- |
| id                       | BIGINT       | PK, AUTO_INCREMENT     | Exam ID                   |
| exam_name                | VARCHAR(255) | NOT NULL               | Official title            |
| description              | TEXT         | NULLABLE               | Exam overview             |
| learning_module_id       | BIGINT       | FK → LEARNING_MODULES | Related module            |
| passing_score_percentage | INT          | DEFAULT 70             | Pass threshold (0-100)    |
| total_questions          | INT          | NOT NULL               | Question count            |
| duration_minutes         | INT          | DEFAULT 60             | Time limit                |
| available_from_date      | TIMESTAMP    | NOT NULL               | Availability window start |
| available_until_date     | TIMESTAMP    | NOT NULL               | Availability window end   |
| is_active                | BOOLEAN      | DEFAULT TRUE           | Release status            |
| created_at               | TIMESTAMP    | DEFAULT NOW            | Creation time             |

**Constraints:**

- CHECK: passing_score_percentage BETWEEN 0 AND 100
- CHECK: duration_minutes > 0
- CHECK: available_until_date > available_from_date

**Indexes:** `learning_module_id`, `is_active`, `available_from_date`

---

### 13. **EXAM_QUESTIONS** (Question Bank)

| Column         | Type      | Constraints               | Description                                |
| -------------- | --------- | ------------------------- | ------------------------------------------ |
| id             | BIGINT    | PK, AUTO_INCREMENT        | Question ID                                |
| exam_id        | BIGINT    | FK → CERTIFICATION_EXAMS | Associated exam                            |
| question_text  | TEXT      | NOT NULL                  | Question prompt                            |
| question_type  | ENUM      | NOT NULL                  | `mcq`, `essay`, `code`, `matching` |
| points         | INT       | NOT NULL                  | Points for correct answer                  |
| sequence_order | INT       | NOT NULL                  | Display order                              |
| created_at     | TIMESTAMP | DEFAULT NOW               | Creation time                              |

**Constraints:**

- CHECK: points > 0
- Foreign Key: exam_id → certification_exams(id) ON DELETE CASCADE
- UNIQUE(exam_id, sequence_order)

**Indexes:** `exam_id`, `sequence_order`

---

### 14. **QUESTION_OPTIONS** (MCQ Choices)

| Column            | Type         | Constraints          | Description         |
| ----------------- | ------------ | -------------------- | ------------------- |
| id                | BIGINT       | PK, AUTO_INCREMENT   | Option ID           |
| question_id       | BIGINT       | FK → EXAM_QUESTIONS | Parent question     |
| option_text       | VARCHAR(255) | NOT NULL             | Choice text         |
| is_correct_answer | BOOLEAN      | DEFAULT FALSE        | Correct answer flag |
| sequence_order    | INT          | NOT NULL             | Display order       |

**Indexes:** `question_id`, `sequence_order`

---

### 15. **EXAM_ATTEMPTS** (Student Test Sessions)

| Column                 | Type      | Constraints               | Description                         |
| ---------------------- | --------- | ------------------------- | ----------------------------------- |
| id                     | BIGINT    | PK, AUTO_INCREMENT        | Attempt ID                          |
| exam_id                | BIGINT    | FK → CERTIFICATION_EXAMS | Exam taken                          |
| mentee_id              | BIGINT    | FK → MENTEES             | Test taker                          |
| attempt_number         | INT       | DEFAULT 1                 | Attempt count                       |
| started_at             | TIMESTAMP | DEFAULT NOW               | Start time                          |
| completed_at           | TIMESTAMP | NULLABLE                  | Finish time                         |
| total_score            | INT       | NULLABLE                  | Total points earned                 |
| passing_status         | ENUM      | NULLABLE                  | `passed`, `failed` (calculated) |
| is_results_visible     | BOOLEAN   | DEFAULT FALSE             | **VISIBILITY CONTROL**        |
| visibility_unlocked_at | TIMESTAMP | NULLABLE                  | When results became visible         |

**Constraints:**

- UNIQUE(exam_id, mentee_id, attempt_number)
- CHECK: completed_at IS NULL OR completed_at > started_at
- Foreign Keys:
  - exam_id → certification_exams(id) ON DELETE CASCADE
  - mentee_id → mentees(id) ON DELETE CASCADE

**Indexes:** `mentee_id`, `exam_id`, `completed_at`, `is_results_visible`

---

### 16. **EXAM_ANSWERS** (Answer Tracking)

| Column             | Type      | Constraints            | Description                |
| ------------------ | --------- | ---------------------- | -------------------------- |
| id                 | BIGINT    | PK, AUTO_INCREMENT     | Answer ID                  |
| exam_attempt_id    | BIGINT    | FK → EXAM_ATTEMPTS    | Test ref                   |
| question_id        | BIGINT    | FK → EXAM_QUESTIONS   | Question ref               |
| answer_text        | TEXT      | NULLABLE               | Student's text response    |
| selected_option_id | BIGINT    | FK → QUESTION_OPTIONS | MCQ choice (if applicable) |
| points_awarded     | INT       | DEFAULT 0              | Points earned              |
| is_correct         | BOOLEAN   | DEFAULT FALSE          | Correctness flag           |
| answered_at        | TIMESTAMP | DEFAULT NOW            | Response time              |

**Constraints:**

- Foreign Keys:
  - exam_attempt_id → exam_attempts(id) ON DELETE CASCADE
  - question_id → exam_questions(id) ON DELETE CASCADE
  - selected_option_id → question_options(id) ON DELETE SET NULL

**Indexes:** `exam_attempt_id`, `question_id`

---

### 17. **CERTIFICATION_RESULTS** (Hidden Until Graduation)

**THIS IS THE CRITICAL TABLE FOR RESULT VISIBILITY CONTROL**

| Column                           | Type      | Constraints               | Description             |
| -------------------------------- | --------- | ------------------------- | ----------------------- |
| id                               | BIGINT    | PK, AUTO_INCREMENT        | Result ID               |
| exam_attempt_id                  | BIGINT    | FK → EXAM_ATTEMPTS       | Test reference          |
| mentee_id                        | BIGINT    | FK → MENTEES             | Student reference       |
| exam_id                          | BIGINT    | FK → CERTIFICATION_EXAMS | Exam reference          |
| total_score                      | INT       | NOT NULL                  | Final score             |
| passing_status                   | ENUM      | NOT NULL                  | `passed` / `failed` |
| **is_results_visible**     | BOOLEAN   | DEFAULT**FALSE**    | 🔐 HIDDEN BY DEFAULT    |
| **visibility_unlock_date** | TIMESTAMP | NULLABLE                  | Graduation date trigger |
| **visibility_unlocked_at** | TIMESTAMP | NULLABLE                  | When actually unlocked  |
| mentor_feedback                  | TEXT      | NULLABLE                  | Evaluator comments      |
| recorded_at                      | TIMESTAMP | DEFAULT NOW               | When recorded           |
| created_at                       | TIMESTAMP | DEFAULT NOW               | Record creation         |
| updated_at                       | TIMESTAMP | DEFAULT NOW               | Last update             |

**Constraints:**

- Foreign Keys (all ON DELETE CASCADE):
  - exam_attempt_id → exam_attempts(id)
  - mentee_id → mentees(id)
  - exam_id → certification_exams(id)
- UNIQUE(exam_attempt_id, mentee_id)

**Indexes:** `mentee_id`, `is_results_visible`, `visibility_unlock_date`, `visibility_unlocked_at`

**Visibility Control Logic:**

```sql
-- Scheduled Job: runs daily/hourly
-- For each CERTIFICATION_RESULTS record:
-- IF visibility_unlock_date IS NOT NULL
--    AND visibility_unlock_date <= NOW()
--    AND is_results_visible = FALSE
-- THEN:
--   1. UPDATE is_results_visible = TRUE
--   2. UPDATE visibility_unlocked_at = NOW()
--   3. Trigger MENTEE → ALUMNI transition
--   4. Grant ALUMNI role permissions
--   5. Log audit event
```

---

### 18. **PROCESS_LEARNING_GRADES** (Engagement Assessment)

| Column                           | Type         | Constraints         | Description                  |
| -------------------------------- | ------------ | ------------------- | ---------------------------- |
| id                               | BIGINT       | PK, AUTO_INCREMENT  | Grade record ID              |
| mentee_id                        | BIGINT       | FK → MENTEES       | Graded student               |
| mentee_group_id                  | BIGINT       | FK → MENTEE_GROUPS | Cohort                       |
| grading_period_start_date        | DATE         | NOT NULL            | Period start                 |
| grading_period_end_date          | DATE         | NOT NULL            | Period end                   |
| attendance_score                 | INT          | DEFAULT 0           | Attendance grade 0-100       |
| discussion_participation_score   | INT          | DEFAULT 0           | Discussion engagement 0-100  |
| practice_submission_score        | INT          | DEFAULT 0           | Assignment average 0-100     |
| engagement_milestone_score       | INT          | DEFAULT 0           | Milestone achievements 0-100 |
| **process_learning_grade** | DECIMAL(5,2) | DEFAULT 0           | **Weighted Average**   |
| mentor_comments                  | TEXT         | NULLABLE            | Qualitative feedback         |
| recorded_date                    | TIMESTAMP    | DEFAULT NOW         | Grade submission date        |
| created_at                       | TIMESTAMP    | DEFAULT NOW         | Record creation              |

**Calculation Formula:**

```
process_learning_grade =
  (attendance_score × 0.25) +
  (discussion_participation_score × 0.25) +
  (practice_submission_score × 0.30) +
  (engagement_milestone_score × 0.20)
```

**Indexes:** `mentee_id`, `grading_period_start_date`, `grading_period_end_date`

---

### 19. **FINAL_GRADES** (Composite Assessment)

| Column                          | Type         | Constraints         | Description                         |
| ------------------------------- | ------------ | ------------------- | ----------------------------------- |
| id                              | BIGINT       | PK, AUTO_INCREMENT  | Grade ID                            |
| mentee_id                       | BIGINT       | FK → MENTEES       | Student                             |
| mentee_group_id                 | BIGINT       | FK → MENTEE_GROUPS | Cohort                              |
| assessment_period_start_date    | DATE         | NOT NULL            | Assessment start                    |
| assessment_period_end_date      | DATE         | NOT NULL            | Assessment end                      |
| process_learning_weight         | INT          | DEFAULT 40          | Weight % for process                |
| certification_exam_weight       | INT          | DEFAULT 60          | Weight % for exam                   |
| process_learning_score          | INT          | NOT NULL            | From PROCESS_LEARNING_GRADES        |
| certification_exam_score        | INT          | NOT NULL            | From CERTIFICATION_RESULTS          |
| **final_composite_grade** | DECIMAL(5,2) | NOT NULL            | **Calculated**                |
| **is_results_visible**    | BOOLEAN      | DEFAULT FALSE       | Inherits from CERTIFICATION_RESULTS |
| recorded_date                   | TIMESTAMP    | DEFAULT NOW         | Grade finalization date             |
| created_at                      | TIMESTAMP    | DEFAULT NOW         | Record creation                     |

**Calculation Formula:**

```
final_composite_grade =
  (process_learning_score × (process_learning_weight / 100)) +
  (certification_exam_score × (certification_exam_weight / 100))

Example (40/60 split):
= (85 × 0.40) + (75 × 0.60)
= 34 + 45
= 79.0
```

**Visibility Rules:**

- If ANY certification result for mentee is hidden → final grade is hidden
- When certification results unlock → final grade unlocks
- Admin can override (with audit trail)

**Indexes:** `mentee_id`, `is_results_visible`, `recorded_date`

---

## Presence & QR System

### 20. **QR_CODES** (Dynamic UUID-Based Codes)

| Column             | Type        | Constraints           | Description                                   |
| ------------------ | ----------- | --------------------- | --------------------------------------------- |
| id                 | BIGINT      | PK, AUTO_INCREMENT    | Record ID                                     |
| uuid_v4            | VARCHAR(36) | UNIQUE, NOT NULL      | **30-second expiration token**          |
| event_id           | BIGINT      | FK → PRESENCE_EVENTS | Associated event                              |
| created_by_user_id | BIGINT      | FK → USERS           | QR generator (mentor/speaker)                 |
| created_at         | TIMESTAMP   | DEFAULT NOW           | Generation time                               |
| expires_at         | TIMESTAMP   | NOT NULL              | **Exactly 30 seconds after created_at** |
| is_expired         | BOOLEAN     | DEFAULT FALSE         | Expiration flag                               |
| is_revoked         | BOOLEAN     | DEFAULT FALSE         | Manual cancellation flag                      |

**Constraints:**

- CHECK: expires_at = created_at + INTERVAL 30 SECOND
- Foreign Keys (ON DELETE CASCADE):
  - event_id → presence_events(id)
  - created_by_user_id → users(id)

**Indexes:** `uuid_v4 (UNIQUE)`, `event_id`, `expires_at`, `created_at`

**Pre-Expiration Logic:**

```sql
-- Trigger: When expires_at <= NOW()
-- UPDATE QR_CODES SET is_expired = TRUE
-- WHERE id = triggered_record
```

---

### 21. **PRESENCE_EVENTS** (Session Registry)

| Column                      | Type          | Constraints         | Description                                                      |
| --------------------------- | ------------- | ------------------- | ---------------------------------------------------------------- |
| id                          | BIGINT        | PK, AUTO_INCREMENT  | Event ID                                                         |
| event_name                  | VARCHAR(255)  | NOT NULL            | Session title                                                    |
| event_type                  | ENUM          | NOT NULL            | `discussion`, `practice`, `module_delivery`, `exam_prep` |
| mentee_group_id             | BIGINT        | FK → MENTEE_GROUPS | Target group                                                     |
| hosted_by_user_id           | BIGINT        | FK → USERS         | Session host                                                     |
| scheduled_start_time        | TIMESTAMP     | NOT NULL            | Session start                                                    |
| scheduled_end_time          | TIMESTAMP     | NOT NULL            | Session end                                                      |
| venue_name                  | VARCHAR(255)  | NULLABLE            | Physical location                                                |
| **gps_latitude**      | DECIMAL(10,8) | NOT NULL            | Geofence center                                                  |
| **gps_longitude**     | DECIMAL(11,8) | NOT NULL            | Geofence center                                                  |
| **gps_radius_meters** | INT           | DEFAULT 50          | Geofence boundary (50m default)                                  |
| is_event_active             | BOOLEAN       | DEFAULT FALSE       | Currently accepting check-ins                                    |
| created_at                  | TIMESTAMP     | DEFAULT NOW         | Record creation                                                  |
| updated_at                  | TIMESTAMP     | DEFAULT NOW         | Last update                                                      |

**Constraints:**

- CHECK: scheduled_end_time > scheduled_start_time
- CHECK: gps_radius_meters BETWEEN 10 AND 500
- Foreign Keys (ON DELETE SET NULL):
  - mentee_group_id → mentee_groups(id)
  - hosted_by_user_id → users(id)

**Indexes:** `hosted_by_user_id`, `scheduled_start_time`, `is_event_active`

---

### 22. **PRESENCE** (Unified Attendance Table)

**Single table for ALL role types - no discrimination**

| Column                             | Type          | Constraints           | Description                                                    |
| ---------------------------------- | ------------- | --------------------- | -------------------------------------------------------------- |
| id                                 | BIGINT        | PK, AUTO_INCREMENT    | Attendance record ID                                           |
| qr_code_id                         | BIGINT        | FK → QR_CODES        | QR scanned                                                     |
| event_id                           | BIGINT        | FK → PRESENCE_EVENTS | Event attended                                                 |
| user_id                            | BIGINT        | FK → USERS           | Any role user                                                  |
| user_role_type                     | VARCHAR(50)   | NOT NULL              | `private_mentor`, `public_mentor`, `speaker`, `mentee` |
| check_in_time                      | TIMESTAMP     | DEFAULT NOW           | When checked in                                                |
| **check_in_gps_latitude**    | DECIMAL(10,8) | NOT NULL              | Actual location                                                |
| **check_in_gps_longitude**   | DECIMAL(11,8) | NOT NULL              | Actual location                                                |
| **is_within_geofence**       | BOOLEAN       | NOT NULL              | GPS validation result                                          |
| **geofence_distance_meters** | DECIMAL(10,2) | NOT NULL              | Distance from center                                           |
| **is_gps_verified**          | BOOLEAN       | DEFAULT FALSE         | Verification status                                            |
| device_fingerprint                 | VARCHAR(255)  | NULLABLE              | Device ID (prevent replay)                                     |
| check_out_time                     | TIMESTAMP     | NULLABLE              | When left                                                      |
| total_duration_minutes             | INT           | NULLABLE              | Session duration                                               |
| **is_valid_attendance**      | BOOLEAN       | DEFAULT FALSE         | Final validation (GPS + QR + time checks)                      |
| **fraud_flags**              | JSON          | NULLABLE              | Suspicious activity indicators                                 |
| created_at                         | TIMESTAMP     | DEFAULT NOW           | Record creation                                                |

**Fraud Flags JSON Schema:**

```json
{
  "flags": [
    "multiple_devices_same_event",
    "impossible_gps_jump",
    "qr_reuse_detected",
    "outside_geofence",
    "expired_qr_used",
    "duplicate_checkin"
  ],
  "severity": "high|medium|low",
  "flagged_at": "timestamp",
  "reviewer_notes": "text"
}
```

**Constraints:**

- Foreign Keys (ON DELETE CASCADE):
  - qr_code_id → qr_codes(id)
  - event_id → presence_events(id)
  - user_id → users(id)
- UNIQUE(qr_code_id, user_id) - One check-in per QR per user
- CHECK: check_out_time IS NULL OR check_out_time > check_in_time
- CHECK: total_duration_minutes IS NULL OR total_duration_minutes > 0

**Indexes:**

- `(user_id, event_id)` - Find user's attendance at event
- `(event_id, check_in_time)` - Event attendance timeline
- `qr_code_id` - QR to attendance mapping
- `is_valid_attendance` - For reporting
- `created_at` - Recent attendance queries

**GPS Validation Rules (Haversine Formula):**

```
distance_meters = 6371000 × 2 × asin(
  sqrt(
    sin²((lat2 - lat1) / 2) +
    cos(lat1) × cos(lat2) × sin²((lon2 - lon1) / 2)
  )
)

is_within_geofence = distance_meters <= gps_radius_meters
is_gps_verified = is_within_geofence AND distance_meters <= 100m
```

**QR Validation Rules:**

```
1. Check QR_CODES record exists for provided UUID
2. Verify: is_expired = FALSE
3. Verify: is_revoked = FALSE
4. Verify: NOW() < expires_at
5. Ensure: One per user per event (prevent duplicates)
```

**Attendance Validity Rules:**

```
is_valid_attendance = (
  qr_code_valid AND
  is_gps_verified AND
  is_within_event_time_window AND
  device_fingerprint_matches AND
  NO_fraud_flags
)

Event time window check:
  check_in_time >= event.scheduled_start_time - 5 minutes
  check_in_time <= event.scheduled_end_time + 5 minutes
```

---

## Temporal & Visibility Logic

### Result Visibility Control Mechanism

**State Diagram:**

```
┌──────────────────┐
│    RECORDED      │  ← CERTIFICATION_RESULTS created
│ is_results_      │     is_results_visible = FALSE
│ visible = FALSE  │     visibility_unlock_date = mentee.graduation_date
└────────┬─────────┘
         │
         │ [Wait for graduation_date to pass]
         │
         ▼
┌──────────────────────────────────┐
│ SCHEDULED JOB (Graduation Trigger)│  ← Cron: */15 * * * * (every 15 min)
│                                  │
│ SELECT * FROM certification_     │
│   results WHERE                  │
│   visibility_unlock_date <= NOW()│
│   AND is_results_visible = FALSE │
│   AND mentee.graduation_date <= NOW()
│                                  │
│ FOR EACH result:                 │
│  1. UPDATE is_results_visible=T  │
│  2. SET visibility_unlocked_at   │
│  3. CREATE alumni record         │
│  4. UPDATE mentee.status         │
│  5. LOG audit event              │
└────────┬───────────────────────────┘
         │
         ▼
┌──────────────────────────────────┐
│     UNLOCKED & VISIBLE           │
│ is_results_visible = TRUE        │  ← Mentee can now view results
│ visibility_unlocked_at = timestamp  via dedicated API endpoint
│ mentee → alumni role granted     │
└──────────────────────────────────┘
```

### Audit Trail Table

| Column        | Type        | Description                                                                         |
| ------------- | ----------- | ----------------------------------------------------------------------------------- |
| id            | BIGINT      | Record ID                                                                           |
| event_type    | VARCHAR(50) | `result_recorded`, `result_unlocked`, `role_transitioned`, `fraud_detected` |
| entity_type   | VARCHAR(50) | `certification_result`, `mentee`, `alumni`                                    |
| entity_id     | BIGINT      | Related record ID                                                                   |
| actor_user_id | BIGINT      | Who triggered event (system/admin)                                                  |
| actor_role    | VARCHAR(50) | Actor's role                                                                        |
| changes_json  | JSON        | Before/after state                                                                  |
| reason        | TEXT        | Why (for admin actions)                                                             |
| created_at    | TIMESTAMP   | Event timestamp                                                                     |

---

## Data Workflows

### Workflow 1: Student Journey (Mentee → Alumni)

```
1. ENROLLMENT
   - User creates account → USERS table
   - Registers for mentee_group → MENTEES record created
   - Assigned private_mentor_id → ROLE_USERS (private mentor)
   - mentee.current_status = 'active'
   - mentee.graduation_date = calculated from group.expected_graduation_date

2. PROCESS LEARNING
   - Public mentor creates LEARNING_MODULES
   - Mentor facilitates LEARNING_DISCUSSIONS
   - Mentee participates → DISCUSSION_PARTICIPATION records
   - Mentee receives engagement_scores & mentor_feedback
   - Mentee completes PRACTICE_SESSIONS → PRACTICE_SUBMISSIONS
   - Milestone achievements → ENGAGEMENT_MILESTONES

3. PROCESS ASSESSMENT (Every 2-4 weeks)
   - System calculates PROCESS_LEARNING_GRADES
   - Aggregates: attendance + discussion + practice + milestones
   - Private mentor reviews & adds comments
   - Mentee views progress (but NOT final grade yet)

4. CERTIFICATION EXAM
   - Exam becomes available after module completion
   - Mentee takes CERTIFICATION_EXAMS → EXAM_ATTEMPTS
   - Answers submitted → EXAM_ANSWERS
   - Results recorded in CERTIFICATION_RESULTS
   - is_results_visible = FALSE ← HIDDEN FROM MENTEE
   - visibility_unlock_date = mentee.graduation_date

5. GRADUATION TRIGGER (On graduation_date)
   - Scheduled job detects mentee.graduation_date <= NOW()
   - Certification results unlock → is_results_visible = TRUE
   - visibility_unlocked_at = NOW()
   - FINAL_GRADES calculated: (process × 0.40) + (exam × 0.60)
   - ALUMNI record created
   - mentee.role_transitioned_to_alumnus = TRUE
   - User role changed from mentee → alumni
   - Alumni community access granted
   - Audit event logged

6. ALUMNI STATUS
   - Permanent access to learning materials
   - Can view final grades & certificate
   - Optional: Become mentor (admin approval)
   - Access alumni-only events & resources
   - Career development portal available
```

### Workflow 2: QR Attendance Check-In

```
1. EVENT SETUP
   - Mentor/Speaker creates PRESENCE_EVENT
   - Sets: time window, GPS coordinates, geofence radius

2. QR GENERATION (5 min before event)
   - System generates unique UUID v4
   - Creates QR_CODE record
   - created_at = NOW()
   - expires_at = NOW() + 30 seconds
   - Mentor displays QR code on screen

3. STUDENT CHECK-IN
   - Mentee opens app → scans QR code
   - Device captures GPS location
   - App sends: uuid, user_id, gps_lat, gps_lon, device_fingerprint

4. VALIDATION (Backend)
   - Verify QR_CODE: uuid exists & is_expired = FALSE
   - Calculate distance: Haversine(event_gps, check_in_gps)
   - Check: distance <= gps_radius_meters (geofence)
   - Check: NOW() < event.scheduled_end_time + 5 min
   - Check: No duplicate check-in for this user/event
   - Verify device_fingerprint doesn't match other users

5. FRAUD DETECTION
   - If distance > gps_radius_meters:
     - Set fraud_flag: "outside_geofence"
     - is_valid_attendance = FALSE
     - Flag for manual review
   - If same device used by multiple users:
     - Set fraud_flag: "shared_device"
   - If GPS jump detected (10km in 1 min):
     - Set fraud_flag: "impossible_gps_jump"

6. ATTENDANCE RECORD
   - Create PRESENCE record
   - is_valid_attendance = TRUE (if all checks pass)
   - Add to event_attendance_count
   - Update ENGAGEMENT_MILESTONES if thresholds met

7. CHECK-OUT
   - Student clicks "leave event"
   - check_out_time = NOW()
   - total_duration_minutes = (check_out - check_in) / 60
   - Calculate attendance_score contribution
```

---

## Migration Strategy

### Phase 1: Foundation (Week 1)

```sql
-- Create base user & role tables
CREATE TABLE users (...)
CREATE TABLE role_users (...)
CREATE TABLE mentee_groups (...)
CREATE TABLE mentees (...)
CREATE TABLE alumni (...)
```

### Phase 2: Learning Modules (Week 2)

```sql
-- Create learning process tracking
CREATE TABLE learning_modules (...)
CREATE TABLE learning_discussions (...)
CREATE TABLE discussion_participation (...)
CREATE TABLE practice_sessions (...)
CREATE TABLE practice_submissions (...)
CREATE TABLE engagement_milestones (...)
```

### Phase 3: Certification & Grading (Week 3)

```sql
-- Create assessment system
CREATE TABLE certification_exams (...)
CREATE TABLE exam_questions (...)
CREATE TABLE question_options (...)
CREATE TABLE exam_attempts (...)
CREATE TABLE exam_answers (...)
CREATE TABLE certification_results (...)
CREATE TABLE process_learning_grades (...)
CREATE TABLE final_grades (...)
```

### Phase 4: Presence & QR (Week 4)

```sql
-- Create attendance system
CREATE TABLE qr_codes (...)
CREATE TABLE presence_events (...)
CREATE TABLE presence (...)
CREATE TABLE audit_trail (...)
```

### Indexes & Performance

```sql
-- Critical indexes for performance
CREATE INDEX idx_mentees_graduation_date ON mentees(graduation_date);
CREATE INDEX idx_certification_results_visibility ON certification_results(is_results_visible, visibility_unlock_date);
CREATE INDEX idx_presence_event_checkin ON presence(event_id, check_in_time);
CREATE INDEX idx_qr_uuid ON qr_codes(uuid_v4);
```

### Scheduled Jobs (Cron)

| Job                               | Frequency             | Purpose                              |
| --------------------------------- | --------------------- | ------------------------------------ |
| Process Graduation Triggers       | Every 15 minutes      | Unlock results, transition to alumni |
| Calculate Process Learning Grades | Weekly (Monday 9 AM)  | Aggregate attendance & engagement    |
| Calculate Final Composite Grades  | Weekly (Tuesday 9 AM) | Process + Certification blend        |
| Expire Old QR Codes               | Every minute          | Clean up expired 30-sec tokens       |
| Detect Fraud Patterns             | Daily (2 AM)          | Analyze presence patterns            |
| Archive Old Sessions              | Monthly               | Move to cold storage                 |

---

## Security & Compliance

### Encryption

- Student grades in transit: TLS 1.3
- Sensitive data at rest: AES-256
- Passwords: bcrypt (rounds: 12)

### GDPR Compliance

- Data retention: 7 years (after graduation)
- Right to be forgotten: GDPR delete requests handled
- Audit trail: All data modifications logged

### Audit Trail

```sql
-- Log all result visibility changes
INSERT INTO audit_trail (event_type, entity_type, entity_id, actor_user_id, changes_json, created_at)
VALUES ('result_unlocked', 'certification_result', 123, 1, '{...}', NOW());
```

---

## Summary

**Total Tables:** 22 core tables
**Relationships:** 35+ foreign keys
**Key Innovation:** Dual-track assessment (Process 40% + Certification 60%)
**Visibility Control:** Hidden results with automatic graduation trigger
**Attendance System:** Unified QR + GPS geofence validation
**Role Transition:** Automatic Mentee → Alumni on graduation_date

This schema supports a **flexible, non-hierarchical mentorship ecosystem** while maintaining rigorous process tracking and secure certification management.
