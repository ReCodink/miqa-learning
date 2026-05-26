<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TopicController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\ClassRoomController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ClassStudentController;
use App\Http\Controllers\Api\ClassSubjectController;
use App\Http\Controllers\Api\SubjectExamController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentExamController;
use App\Http\Controllers\Api\ExamAttemptController;
use App\Http\Controllers\Api\QuestionAnswerController;
use App\Http\Controllers\Api\ExamQuestionController;
use App\Http\Controllers\Api\QuestionOptionController;
use App\Http\Controllers\Api\StatisticsController;
use App\Http\Controllers\Api\PresenceSessionController;
use App\Http\Controllers\Api\PresenceQrController;
use App\Http\Controllers\Api\PresenceCheckInController;
use App\Http\Controllers\Api\PresenceReportController;
use App\Http\Controllers\Api\PresenceSecurityController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('token-login', [AuthController::class, 'tokenLogin']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
});


Route::middleware(['auth:sanctum', 'role:manager'])->group(function () {
    Route::post('topics', [TopicController::class, 'store']);
    Route::put('topics/{id}', [TopicController::class, 'update']);
    Route::patch('topics/{id}', [TopicController::class, 'update']);
    Route::delete('topics/{id}', [TopicController::class, 'destroy']);

    // Subjects
    Route::get('subjects/search', [SubjectController::class, 'search']);
    Route::apiResource('subjects', SubjectController::class)->except(['show', 'update']);

    // ClassRooms
    Route::get('class-rooms/search', [ClassRoomController::class, 'search']);
    Route::get('class-rooms/{id}/students', [ClassRoomController::class, 'students']);
    Route::get('class-rooms/{id}/subjects', [ClassRoomController::class, 'subjects']);
    Route::post('class-rooms/{id}/enroll-student', [ClassRoomController::class, 'enrollStudent']);
    Route::delete('class-rooms/{id}/students/{studentId}', [ClassRoomController::class, 'unenrollStudent']);
    Route::post('class-rooms/{id}/assign-subject', [ClassRoomController::class, 'assignSubject']);
    Route::delete('class-rooms/{id}/subjects/{subjectId}', [ClassRoomController::class, 'unassignSubject']);
    Route::apiResource('class-rooms', ClassRoomController::class)->except(['show']);
    Route::post('teachers', [TeacherController::class, 'store']);
    Route::put('teachers/{id}', [TeacherController::class, 'update']);
    Route::patch('teachers/{id}', [TeacherController::class, 'update']);
    Route::delete('teachers/{id}', [TeacherController::class, 'destroy']);

    // Students
    Route::get('students/search', [StudentController::class, 'search']);
    Route::get('students/{id}/statistics', [StudentController::class, 'statistics']);
    Route::apiResource('students', StudentController::class);

    // Class Students (Enrollments)
    Route::get('class-students/search', [ClassStudentController::class, 'search']);
    Route::post('classrooms/{classRoomId}/bulk-enroll', [ClassStudentController::class, 'bulkEnroll']);
    Route::put('students/{studentId}/classrooms/{classRoomId}/status', [ClassStudentController::class, 'updateStatus']);

    // Rapport PDF Management (Manager only - upload/delete)
    Route::post('students/{studentId}/classrooms/{classRoomId}/rapport', [ClassStudentController::class, 'uploadRapport']);
    Route::delete('students/{studentId}/classrooms/{classRoomId}/rapport', [ClassStudentController::class, 'deleteRapport']);

    Route::apiResource('class-students', ClassStudentController::class);

    // Class Subjects (Subject-Classroom Assignments)
    Route::get('class-subjects/search', [ClassSubjectController::class, 'search']);
    Route::get('classrooms/{classRoomId}/available-subjects', [ClassSubjectController::class, 'availableSubjects']);
    Route::get('subjects/{subjectId}/available-classrooms', [ClassSubjectController::class, 'availableClassRooms']);
    Route::post('classrooms/{classRoomId}/bulk-assign-subjects', [ClassSubjectController::class, 'bulkAssignToClassRoom']);
    Route::apiResource('class-subjects', ClassSubjectController::class);

    // Subject Exams (Manager only operations)
    Route::get('subject-exams/search', [SubjectExamController::class, 'search']);
    Route::post('subject-exams/{id}/duplicate', [SubjectExamController::class, 'duplicate']);
});

// Shared Detail Routes - Accessible by Manager, Teacher, and Student (with access control)
Route::middleware(['auth:sanctum', 'role:manager|teacher|student'])->group(function () {
    // Classroom Details
    Route::get('class-rooms/{id}', [ClassRoomController::class, 'show']);
    Route::get('class-rooms/{id}/students', [ClassRoomController::class, 'students']);
    Route::get('class-rooms/{id}/subjects', [ClassRoomController::class, 'subjects']);
    Route::get('class-rooms/{id}/statistics', [ClassRoomController::class, 'statistics']);

    // Subject Details
    Route::get('subjects/{id}', [SubjectController::class, 'show']);

    // Topic Details
    Route::get('topics/search', [TopicController::class, 'search']);
    Route::get('topics/{id}', [TopicController::class, 'show']);

    // Subject Exam Details
    Route::get('subject-exams/{id}', [SubjectExamController::class, 'show']);
    Route::get('subject-exams/{id}/statistics', [SubjectExamController::class, 'statistics']);
    Route::get('subject-exams/{id}/status', [SubjectExamController::class, 'status']);

    // Rapport PDF viewing (All roles with role-based authorization in controller)
    Route::get('students/{studentId}/classrooms/{classRoomId}/rapport/info', [ClassStudentController::class, 'getRapportInfo']);
    Route::get('students/{studentId}/classrooms/{classRoomId}/rapport/download', [ClassStudentController::class, 'downloadRapport']);
});

// Teacher-specific routes
Route::middleware(['auth:sanctum', 'role:teacher'])->group(function () {
    // Teacher Dashboard - View assigned subjects
    Route::get('teacher/subjects', [SubjectController::class, 'teacherSubjects']);
    Route::get('teacher/subjects/search', [SubjectController::class, 'teacherSubjectsSearch']);

    // Teacher Profile
    Route::get('teacher/profile', [TeacherController::class, 'profile']);

    // Teacher's Subject Exams
    Route::get('teacher/subject-exams', [SubjectExamController::class, 'teacherExams']);
    Route::post('teacher/subject-exams', [SubjectExamController::class, 'createTeacherExam']);
    Route::put('teacher/subject-exams/{id}', [SubjectExamController::class, 'updateTeacherExam']);
    Route::delete('teacher/subject-exams/{id}', [SubjectExamController::class, 'deleteTeacherExam']);
    Route::get('teacher/subject-exams/{id}/answers', [SubjectExamController::class, 'getExamAnswers']);

    // Teacher Dashboard - Student Status and Individual Answers
    Route::get('teacher/exams/{id}/students', [SubjectExamController::class, 'getExamStudentsStatus']);
    Route::get('teacher/exams/{examId}/students/{studentId}', [SubjectExamController::class, 'getStudentExamDetails']);

    // Teacher's ClassRoom assignments via subjects
    Route::get('teacher/classrooms', [ClassSubjectController::class, 'teacherClassRooms']);

    // Teacher-scoped student profile access
    Route::get('teacher/students/{studentId}/profile', [StudentController::class, 'teacherStudentProfile']);

});

// Student-specific routes
Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
    // Student Dashboard & Profile
    Route::get('student/profile', [StudentController::class, 'profile']);
    Route::get('student/classrooms', [ClassStudentController::class, 'studentClassRooms']);

    // Student Exams - List and Overview
    Route::get('student/exams', [SubjectExamController::class, 'studentExams']);
    Route::get('student/subjects/{subjectId}/exams', [SubjectExamController::class, 'getSubjectExams']);

    // Student Exam Taking
    Route::get('student/exams/{examId}', [StudentExamController::class, 'show']);
    Route::post('student/exams/{examId}/start', [StudentExamController::class, 'startExam']);
    Route::post('student/exams/{examId}/questions/{questionId}/answer', [StudentExamController::class, 'submitAnswer']);
    Route::post('student/exams/{examId}/complete', [StudentExamController::class, 'completeExam']);
    Route::get('student/exams/{examId}/progress', [StudentExamController::class, 'getProgress']);
    Route::get('student/exams/{examId}/results', [StudentExamController::class, 'getResults']);

    // Student rapport endpoints (no need to pass studentId - gets from auth)
    Route::get('student/classrooms/{classRoomId}/rapport/info', [ClassStudentController::class, 'getStudentRapportInfo']);
    Route::get('student/classrooms/{classRoomId}/rapport/download', [ClassStudentController::class, 'downloadStudentRapport']);
});

// Manager & Teacher routes for managing exam attempts and answers
Route::middleware(['auth:sanctum', 'role:manager|teacher'])->group(function () {
    // Statistics - Get counts for requested entities
    Route::get('statistics', [StatisticsController::class, 'index']);

    // Users - Get all users with filtering and pagination
    Route::get('users', [UserController::class, 'index']);

    // Topics - Read access for both managers and teachers
    Route::get('topics', [TopicController::class, 'index']);

    // Teachers - Read access for both managers and teachers
    Route::get('teachers', [TeacherController::class, 'index']);
    Route::get('teachers/search', [TeacherController::class, 'search']);
    Route::get('teachers/{id}', [TeacherController::class, 'show']);

    // Subject Management (with authorization checks in controller)
    Route::put('subjects/{id}', [SubjectController::class, 'update']);
    Route::patch('subjects/{id}', [SubjectController::class, 'update']);

    // Exam Attempts Management
    Route::get('exam-attempts', [ExamAttemptController::class, 'index']);
    Route::get('exam-attempts/{id}', [ExamAttemptController::class, 'show']);
    Route::post('exam-attempts', [ExamAttemptController::class, 'store']);
    Route::put('exam-attempts/{id}', [ExamAttemptController::class, 'update']);
    Route::delete('exam-attempts/{id}', [ExamAttemptController::class, 'destroy']);
    Route::get('exam-attempts/statistics', [ExamAttemptController::class, 'statistics']);
    Route::get('students/{studentId}/exams/{examId}/attempt', [ExamAttemptController::class, 'getStudentAttempt']);

    // Question Answers Management
    Route::get('question-answers', [QuestionAnswerController::class, 'index']);
    Route::get('question-answers/{id}', [QuestionAnswerController::class, 'show']);
    Route::get('question-answers/needs-grading', [QuestionAnswerController::class, 'needsGrading']);
    Route::get('students/{studentId}/exams/{examId}/answers', [QuestionAnswerController::class, 'getStudentAnswers']);

    // Exam Questions Management
    Route::get('exam-questions', [ExamQuestionController::class, 'index']);
    Route::get('exam-questions/search', [ExamQuestionController::class, 'search']);
    Route::get('exam-questions/{id}', [ExamQuestionController::class, 'show']);
    Route::post('exam-questions', [ExamQuestionController::class, 'store']);
    Route::put('exam-questions/{id}', [ExamQuestionController::class, 'update']);
    Route::delete('exam-questions/{id}', [ExamQuestionController::class, 'destroy']);
    Route::post('exam-questions/bulk-create', [ExamQuestionController::class, 'bulkStore']);
    Route::post('exam-questions/{id}/duplicate', [ExamQuestionController::class, 'duplicate']);
    Route::get('subject-exams/{subjectExamId}/questions', [ExamQuestionController::class, 'getBySubjectExam']);
    Route::get('subject-exams/{subjectExamId}/questions/statistics', [ExamQuestionController::class, 'statistics']);
    Route::get('subject-exams/{subjectExamId}/questions/for-grading', [ExamQuestionController::class, 'forGrading']);

    // Question Options Management
    Route::get('question-options', [QuestionOptionController::class, 'index']);
    Route::get('question-options/search', [QuestionOptionController::class, 'search']);
    Route::get('question-options/{id}', [QuestionOptionController::class, 'show']);
    Route::post('question-options', [QuestionOptionController::class, 'store']);
    Route::put('question-options/{id}', [QuestionOptionController::class, 'update']);
    Route::delete('question-options/{id}', [QuestionOptionController::class, 'destroy']);
    Route::put('question-options/bulk-update', [QuestionOptionController::class, 'bulkUpdate']);
    Route::get('exam-questions/{examQuestionId}/options', [QuestionOptionController::class, 'getByExamQuestion']);
    Route::get('exam-questions/{examQuestionId}/options/correct', [QuestionOptionController::class, 'getCorrectOptions']);
    Route::get('exam-questions/{examQuestionId}/options/statistics', [QuestionOptionController::class, 'statistics']);
    Route::post('exam-questions/{examQuestionId}/options', [QuestionOptionController::class, 'bulkStore']);
    Route::post('exam-questions/{examQuestionId}/options/set-correct', [QuestionOptionController::class, 'setCorrectOption']);
    Route::post('exam-questions/{examQuestionId}/options/reorder', [QuestionOptionController::class, 'reorder']);

    // Subject Exams Management (Manager & Teacher)
    Route::apiResource('subject-exams', SubjectExamController::class)->except(['show']);
});

// Teacher-only routes for grading
Route::middleware(['auth:sanctum', 'role:teacher'])->group(function () {
    // Answer Grading (Teachers only)
    Route::post('question-answers/{id}/grade', [QuestionAnswerController::class, 'gradeAnswer']);
    Route::post('question-answers/bulk-grade', [QuestionAnswerController::class, 'bulkGrade']);
});

// ============================================================
// QR ATTENDANCE SYSTEM ROUTES
// ============================================================

// Presence Sessions (Manager/Teacher only - CRUD)
Route::middleware(['auth:sanctum', 'role:manager|teacher'])->group(function () {
    Route::prefix('presence/sessions')->group(function () {
        Route::post('/', [PresenceSessionController::class, 'store'])
            ->can('create-presence-session');
        Route::get('/{session}', [PresenceSessionController::class, 'show']);
        Route::put('/{session}', [PresenceSessionController::class, 'update'])
            ->can('update-presence-session');
        Route::post('/{session}/activate', [PresenceSessionController::class, 'activate'])
            ->can('activate-presence-session');
        Route::post('/{session}/deactivate', [PresenceSessionController::class, 'deactivate'])
            ->can('deactivate-presence-session');
        Route::get('/{session}/report', [PresenceSessionController::class, 'report']);
        Route::get('/{session}/attendances', [PresenceSessionController::class, 'attendances']);
    });

    // QR Token Generation (Manager/Teacher only)
    Route::prefix('presence/qr')->group(function () {
        Route::post('/generate', [PresenceQrController::class, 'generateToken'])
            ->can('generate-qr-token');
        Route::get('/{token}', [PresenceQrController::class, 'show']);
        Route::delete('/{token}/revoke', [PresenceQrController::class, 'revokeToken'])
            ->can('revoke-qr-token');
    });
});

// Attendance Check-In (All authenticated users)
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('presence/attendance')->group(function () {
        Route::post('/check-in', [PresenceCheckInController::class, 'checkIn']);
        Route::post('/{presence}/check-out', [PresenceCheckInController::class, 'checkOut']);
        Route::get('/my-attendance', [PresenceCheckInController::class, 'myAttendance']);
    });
});

// Reports & Analytics (Authenticated users with role restrictions)
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('presence/reports')->group(function () {
        Route::get('/user-stats', [PresenceReportController::class, 'userStats']);
        Route::get('/session/{session}', [PresenceReportController::class, 'sessionReport']);
        Route::get('/summary', [PresenceReportController::class, 'summary']);

        // Manager/Teacher only
        Route::middleware('role:manager|teacher')->group(function () {
            Route::get('/security-flags', [PresenceReportController::class, 'securityFlags'])
                ->can('view-security-flags');
            Route::get('/audit-logs', [PresenceReportController::class, 'auditLogs'])
                ->can('view-audit-logs');
        });
    });
});

// Security Management (Manager/Teacher only)
Route::middleware(['auth:sanctum', 'role:manager|teacher'])->group(function () {
    Route::prefix('presence/security')->group(function () {
        Route::get('/flags', [PresenceSecurityController::class, 'listFlags'])
            ->can('review-security-flags');
        Route::put('/flags/{flag}', [PresenceSecurityController::class, 'reviewFlag'])
            ->can('review-security-flags');
        Route::post('/devices/{device}/trust', [PresenceSecurityController::class, 'trustDevice'])
            ->can('trust-device');
        Route::delete('/devices/{device}', [PresenceSecurityController::class, 'revokeDevice'])
            ->can('revoke-device');
        Route::get('/devices', [PresenceSecurityController::class, 'userDevices']);
    });
});
