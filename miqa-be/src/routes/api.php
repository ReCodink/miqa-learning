<?php

use App\Http\Controllers\Api\{AuthController, UserController, TeacherController, StudentController, ClassRoomController, SubjectController, TopicController, ClassStudentController, ClassSubjectController, SubjectExamController, ExamQuestionController, QuestionOptionController, ExamAttemptController, QuestionAnswerController, StatisticsController, PresenceSessionController, PresenceQrController, PresenceCheckInController, PresenceReportController, PresenceSecurityController, StudentExamController};
use App\Http\Controllers\Api\ProtocolController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1. PUBLIC & AUTHENTICATION ROUTES
|--------------------------------------------------------------------------
*/

Route::post('login', [AuthController::class, 'login']); //
Route::post('token-login', [AuthController::class, 'tokenLogin']); //

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']); //
    Route::get('user', [AuthController::class, 'user']); //
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED CORE API
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    /*
    |----------------------------------------------------------------------
    | BACK-OFFICE / SERVER-SIDE MANAGEMENT (Manager & Admin Ops)
    |----------------------------------------------------------------------
    | Endpoints dedicated to data control, structural setups, and configurations.
    */
    Route::middleware('role:manager')->group(function () {
        // Academic Structure
        Route::apiResource('subjects', SubjectController::class)->except(['show', 'update']);
        Route::get('subjects/search', [SubjectController::class, 'search']);

        Route::apiResource('class-rooms', ClassRoomController::class)->except(['show']);
        Route::get('class-rooms/search', [ClassRoomController::class, 'search']);

        Route::apiResource('students', StudentController::class)->except(['show']);
        Route::get('students/search', [StudentController::class, 'search']);

        // Topics Management
        Route::post('topics', [TopicController::class, 'store']);
        Route::match(['put', 'patch'], 'topics/{id}', [TopicController::class, 'update']);
        Route::delete('topics/{id}', [TopicController::class, 'destroy']);

        // Protocols Management
        Route::get('protocols', [ProtocolController::class, 'index']);
        Route::post('protocols', [ProtocolController::class, 'store']);
        Route::match(['put', 'patch'], 'protocols/{id}', [ProtocolController::class, 'update']);
        Route::delete('protocols/{id}', [ProtocolController::class, 'destroy']);

        // Users Infrastructure
        Route::post('users', [UserController::class, 'store']);
        Route::match(['put', 'patch'], 'users/{id}', [UserController::class, 'update']);
        Route::delete('users/{id}', [UserController::class, 'destroy']);

        // Teacher Infrastructure
        Route::post('teachers', [TeacherController::class, 'store']);
        Route::match(['put', 'patch'], 'teachers/{id}', [TeacherController::class, 'update']);
        Route::delete('teachers/{id}', [TeacherController::class, 'destroy']);

        // Enrollments & Assignments
        Route::apiResource('class-students', ClassStudentController::class);
        Route::get('class-students/search', [ClassStudentController::class, 'search']);
        Route::post('class-rooms/{classRoomId}/bulk-enroll', [ClassStudentController::class, 'bulkEnroll']);
        Route::put('students/{studentId}/class-rooms/{classRoomId}/status', [ClassStudentController::class, 'updateStatus']);
        Route::post('students/{studentId}/class-rooms/{classRoomId}/rapport', [ClassStudentController::class, 'uploadRapport']);
        Route::delete('students/{studentId}/class-rooms/{classRoomId}/rapport', [ClassStudentController::class, 'deleteRapport']);

        Route::apiResource('class-subjects', ClassSubjectController::class);
        Route::get('class-subjects/search', [ClassSubjectController::class, 'search']);
        Route::get('class-rooms/{classRoomId}/available-subjects', [ClassSubjectController::class, 'availableSubjects']);
        Route::get('subjects/{subjectId}/available-classrooms', [ClassSubjectController::class, 'availableClassRooms']);
        Route::post('class-rooms/{classRoomId}/bulk-assign-subject', [ClassSubjectController::class, 'bulkAssignToClassRoom']);

        // Advanced Exam Management
        Route::get('subject-exams/search', [SubjectExamController::class, 'search']);
        Route::post('subject-exams/{id}/duplicate', [SubjectExamController::class, 'duplicate']);
    });

    /*
    |----------------------------------------------------------------------
    | MIXED ADMINISTRATIVE PORTAL (Manager | Teacher)
    |----------------------------------------------------------------------
    | Data overview, Grading Engines, and Item-Banking.
    */
    Route::middleware('role:manager|teacher')->group(function () {
        Route::get('statistics', [StatisticsController::class, 'index']);
        Route::apiResource('subject-exams', SubjectExamController::class)->except(['show']);
        Route::match(['put', 'patch'], 'subjects/{id}', [SubjectController::class, 'update']);

        // User Directory
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/search', [UserController::class, 'search']);
        Route::get('users/{id}', [UserController::class, 'show']);

        // Teacher Directory Read-Access
        Route::get('teachers', [TeacherController::class, 'index']);
        Route::get('teachers/search', [TeacherController::class, 'search']);
        Route::get('teachers/{id}', [TeacherController::class, 'show']);
        Route::get('topics', [TopicController::class, 'index']);

        // Exam Question & Option Utilities (Item Banking)
        Route::prefix('exam-questions')->group(function () {
            Route::get('/', [ExamQuestionController::class, 'index']);
            Route::get('/search', [ExamQuestionController::class, 'search']);
            Route::post('/', [ExamQuestionController::class, 'store']);
            Route::post('/bulk-create', [ExamQuestionController::class, 'bulkStore']);
            Route::get('/{id}', [ExamQuestionController::class, 'show']);
            Route::put('/{id}', [ExamQuestionController::class, 'update']);
            Route::delete('/{id}', [ExamQuestionController::class, 'destroy']);
            Route::post('/{id}/duplicate', [ExamQuestionController::class, 'duplicate']);
        });

        Route::prefix('question-options')->group(function () {
            Route::get('/', [QuestionOptionController::class, 'index']);
            Route::get('/search', [QuestionOptionController::class, 'search']);
            Route::post('/', [QuestionOptionController::class, 'store']);
            Route::put('/bulk-update', [QuestionOptionController::class, 'bulkUpdate']);
            Route::get('/{id}', [QuestionOptionController::class, 'show']);
            Route::put('/{id}', [QuestionOptionController::class, 'update']);
            Route::delete('/{id}', [QuestionOptionController::class, 'destroy']);
        });

        // Contextual Mapping for Questions/Options
        Route::get('subject-exams/{subjectExamId}/questions', [ExamQuestionController::class, 'getBySubjectExam']);
        Route::get('subject-exams/{subjectExamId}/questions/statistics', [ExamQuestionController::class, 'statistics']);
        Route::get('subject-exams/{subjectExamId}/questions/for-grading', [ExamQuestionController::class, 'forGrading']);
        Route::get('exam-questions/{examQuestionId}/options', [QuestionOptionController::class, 'getByExamQuestion']);
        Route::get('exam-questions/{examQuestionId}/options/correct', [QuestionOptionController::class, 'getCorrectOptions']);
        Route::get('exam-questions/{examQuestionId}/options/statistics', [QuestionOptionController::class, 'statistics']);
        Route::post('exam-questions/{examQuestionId}/options', [QuestionOptionController::class, 'bulkStore']);
        Route::post('exam-questions/{examQuestionId}/options/set-correct', [QuestionOptionController::class, 'setCorrectOption']);
        Route::post('exam-questions/{examQuestionId}/options/reorder', [QuestionOptionController::class, 'reorder']);

        // Evaluation & Processing Systems
        Route::apiResource('exam-attempts', ExamAttemptController::class);
        Route::get('exam-attempts/statistics', [ExamAttemptController::class, 'statistics']);
        Route::get('students/{studentId}/exams/{examId}/attempt', [ExamAttemptController::class, 'getStudentAttempt']);

        Route::get('question-answers', [QuestionAnswerController::class, 'index']);
        Route::get('question-answers/needs-grading', [QuestionAnswerController::class, 'needsGrading']);
        Route::get('question-answers/{id}', [QuestionAnswerController::class, 'show']);
        Route::get('students/{studentId}/exams/{examId}/answers', [QuestionAnswerController::class, 'getStudentAnswers']);
    });

    /*
    |----------------------------------------------------------------------
    | TEACHER PORTAL (Client-Side for Teachers)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:teacher')->group(function () {
        Route::prefix('teacher')->group(function () {
            Route::get('profile', [TeacherController::class, 'profile']);
            Route::get('classrooms', [ClassSubjectController::class, 'teacherClassRooms']);
            Route::get('students/{studentId}/profile', [StudentController::class, 'teacherStudentProfile']);

            // Handled subjects
            Route::get('subjects', [SubjectController::class, 'teacherSubjects']); //
            Route::get('subjects/search', [SubjectController::class, 'teacherSubjectsSearch']); //

            // Exam Control & Evaluation Rooms
            Route::get('subject-exams', [SubjectExamController::class, 'teacherExams']);
            Route::post('subject-exams', [SubjectExamController::class, 'createTeacherExam']);
            Route::put('subject-exams/{id}', [SubjectExamController::class, 'updateTeacherExam']);
            Route::delete('subject-exams/{id}', [SubjectExamController::class, 'deleteTeacherExam']);
            Route::get('subject-exams/{id}/answers', [SubjectExamController::class, 'getExamAnswers']);
            Route::get('exams/{id}/students', [SubjectExamController::class, 'getExamStudentsStatus']);
            Route::get('exams/{examId}/students/{studentId}', [SubjectExamController::class, 'getStudentExamDetails']);
        });

        // Grading Engine Execution
        Route::post('question-answers/{id}/grade', [QuestionAnswerController::class, 'gradeAnswer']);
        Route::post('question-answers/bulk-grade', [QuestionAnswerController::class, 'bulkGrade']);
    });

    /*
    |----------------------------------------------------------------------
    | STUDENT PORTAL (Client-Side for Students)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:student')->group(function () {
        Route::prefix('student')->group(function () {
            Route::get('profile', [StudentController::class, 'profile']);
            Route::get('classrooms', [ClassStudentController::class, 'studentClassRooms']);

            // Academic Workload & Exam Engine
            Route::get('exams', [SubjectExamController::class, 'studentExams']);
            Route::get('subjects/{subjectId}/exams', [SubjectExamController::class, 'getSubjectExams']);

            Route::prefix('exams/{examId}')->group(function () {
                Route::get('/', [StudentExamController::class, 'show']);
                Route::post('start', [StudentExamController::class, 'startExam']);
                Route::post('questions/{questionId}/answer', [StudentExamController::class, 'submitAnswer']);
                Route::post('complete', [StudentExamController::class, 'completeExam']);
                Route::get('progress', [StudentExamController::class, 'getProgress']);
                Route::get('results', [StudentExamController::class, 'getResults']);
            });

            // Reports / Student View
            Route::get('class-rooms/{classRoomId}/rapport/info', [ClassStudentController::class, 'getStudentRapportInfo']);
            Route::get('class-rooms/{classRoomId}/rapport/download', [ClassStudentController::class, 'downloadStudentRapport']);
        });
    });

    /*
    |----------------------------------------------------------------------
    | COMMON ACADEMIC READS (Shared Client/Server View Layer)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:manager|teacher|student')->group(function () {
        Route::get('class-rooms/{id}', [ClassRoomController::class, 'show']);
        Route::get('class-rooms/{id}/students', [ClassRoomController::class, 'students']);
        Route::get('class-rooms/{id}/subjects', [ClassRoomController::class, 'subjects']);
        Route::get('class-rooms/{id}/statistics', [ClassRoomController::class, 'statistics']);

        Route::get('subjects/{id}', [SubjectController::class, 'show']);
        Route::get('students/{id}', [StudentController::class, 'show']);
        Route::get('topics/search', [TopicController::class, 'search']);
        Route::get('topics/{id}', [TopicController::class, 'show']);

        Route::get('protocols/search', [ProtocolController::class, 'search']);
        Route::get('protocols/{id}', [ProtocolController::class, 'show']);

        Route::get('subject-exams/{id}', [SubjectExamController::class, 'show']);
        Route::get('subject-exams/{id}/statistics', [SubjectExamController::class, 'statistics']);
        Route::get('subject-exams/{id}/status', [SubjectExamController::class, 'status']);

        Route::get('students/{studentId}/class-rooms/{classRoomId}/rapport/info', [ClassStudentController::class, 'getRapportInfo']);
        Route::get('students/{studentId}/class-rooms/{classRoomId}/rapport/download', [ClassStudentController::class, 'downloadRapport']);
    });

    /*
    |----------------------------------------------------------------------
    | QR ATTENDANCE SUBSYSTEM
    |----------------------------------------------------------------------
    */
    Route::prefix('presence')->group(function () {

        // Administrative Presence Control (Server/Management)
        Route::middleware('role:manager|teacher')->group(function () {

            // Presence Sessions Management (SINKRON DENGAN CONTROLLER BARU)
            Route::prefix('sessions')->group(function () {
                Route::get('/{id}', [PresenceSessionController::class, 'show']);
                Route::post('/', [PresenceSessionController::class, 'store'])->can('create-presence-session');
                Route::put('/{id}', [PresenceSessionController::class, 'update'])->can('update-presence-session');
                Route::post('/{id}/activate', [PresenceSessionController::class, 'activate'])->can('activate-presence-session');
                Route::post('/{id}/deactivate', [PresenceSessionController::class, 'deactivate'])->can('deactivate-presence-session');
                Route::get('/{id}/report', [PresenceSessionController::class, 'report']);
                Route::get('/{id}/attendances', [PresenceSessionController::class, 'attendances']);
            });

            Route::prefix('qr')->group(function () {
                Route::post('/generate', [PresenceQrController::class, 'generateToken'])->can('generate-qr-token');
                Route::get('/{token}', [PresenceQrController::class, 'show']);
                Route::delete('/{token}/revoke', [PresenceQrController::class, 'revokeToken'])->can('revoke-qr-token');
            });
        });

        // Client-Side Entry / Active Check-in (Sudah disatukan, tidak duplikat)
        Route::prefix('attendance')->group(function () {
            Route::post('/check-in', [PresenceCheckInController::class, 'checkIn']);
            Route::post('/{presence}/check-out', [PresenceCheckInController::class, 'checkOut']);
            Route::get('/my-attendance', [PresenceCheckInController::class, 'myAttendance']);
        });

        // Intelligence / Audits & System Security
        Route::prefix('reports')->group(function () {
            Route::get('/user-stats', [PresenceReportController::class, 'userStats']);
            Route::get('/session/{session}', [PresenceReportController::class, 'sessionReport']);
            Route::get('/summary', [PresenceReportController::class, 'summary']);
        });
    });
});
