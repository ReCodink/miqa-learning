<?php

namespace App\Services;

use App\Repositories\StudentExamRepository;
use App\Repositories\SubjectExamRepository;
use App\Repositories\ExamAttemptRepository;
use App\Repositories\QuestionAnswerRepository;
use App\Repositories\ExamQuestionRepository;
use App\Models\SubjectExam;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentExamService
{
    private StudentExamRepository $studentExamRepository;
    private SubjectExamRepository $subjectExamRepository;
    private ExamAttemptRepository $examAttemptRepository;
    private QuestionAnswerRepository $questionAnswerRepository;
    private ExamQuestionRepository $examQuestionRepository;

    public function __construct(
        StudentExamRepository $studentExamRepository,
        SubjectExamRepository $subjectExamRepository,
        ExamAttemptRepository $examAttemptRepository,
        QuestionAnswerRepository $questionAnswerRepository,
        ExamQuestionRepository $examQuestionRepository
    ) {
        $this->studentExamRepository = $studentExamRepository;
        $this->subjectExamRepository = $subjectExamRepository;
        $this->examAttemptRepository = $examAttemptRepository;
        $this->questionAnswerRepository = $questionAnswerRepository;
        $this->examQuestionRepository = $examQuestionRepository;
    }

    /**
     * Get exam details with questions for student
     */
    public function getExamForStudent(int $studentId, int $examId): array
    {
        // Check if student has access to this exam
        if (!$this->studentExamRepository->hasExamAccess($studentId, $examId)) {
            throw new \Exception('You do not have access to this exam');
        }

        $exam = $this->studentExamRepository->getExamWithQuestions($examId);

        // Check if exam is currently active
        $now = now();
        $isActive = $now->between($exam->started_at, $exam->ended_at);

        // Get student's attempt if exists
        $attempt = $this->studentExamRepository->getStudentAttempt($studentId, $examId);

        return [
            'exam' => $exam,
            'questions' => $exam->examQuestions,
            'is_active' => $isActive,
            'can_take' => $isActive && (!$attempt || !$attempt->is_completed),
            'attempt' => $attempt,
            'total_questions' => $exam->examQuestions->count(),
            'total_points' => $exam->examQuestions->sum('points')
        ];
    }

    /**
     * Start an exam attempt
     */
    public function startExam(int $studentId, int $examId): array
    {
        return DB::transaction(function () use ($studentId, $examId) {
            // Check if student has access to this exam
            if (!$this->studentExamRepository->hasExamAccess($studentId, $examId)) {
                throw new \Exception('You do not have access to this exam');
            }

            $exam = $this->studentExamRepository->getExamWithQuestions($examId);

            // Check if exam is currently active
            $now = now();
            if (!$now->between($exam->started_at, $exam->ended_at)) {
                $debugInfo = config('app.debug') ?
                    " (Current time: {$now->toDateTimeString()}, Exam start: {$exam->started_at->toDateTimeString()}, Exam end: {$exam->ended_at->toDateTimeString()})" : '';
                throw new \Exception('This exam is not currently active' . $debugInfo);
            }

            // Check if student already has a completed attempt
            $existingAttempt = $this->studentExamRepository->getStudentAttempt($studentId, $examId);

            if ($existingAttempt && $existingAttempt->is_completed) {
                throw new \Exception('You have already completed this exam');
            }

            // Create or update exam attempt
            if ($existingAttempt) {
                // Student is restarting the exam
                $existingAttempt->update([
                    'is_completed' => false,
                    'total_questions' => $exam->examQuestions->count(),
                    'answered_questions' => 0,
                    'total_points' => $exam->total_points,
                    'points_earned' => 0,
                    'has_passed' => false,
                    'completed_at' => null
                ]);
                $attempt = $existingAttempt;
            } else {
                // First time starting this exam
                $attempt = $this->examAttemptRepository->create([
                    'student_id' => $studentId,
                    'subject_exam_id' => $examId,
                    'total_attempts' => 1,
                    'is_completed' => false,
                    'total_questions' => $exam->examQuestions->count(),
                    'answered_questions' => 0,
                    'total_points' => $exam->total_points,
                    'points_earned' => 0,
                    'has_passed' => false,
                    'completed_at' => null
                ]);
            }

            return [
                'attempt' => $attempt,
                'exam' => $exam,
                'questions' => $exam->examQuestions,
                'time_remaining_minutes' => $this->getTimeRemaining($exam)
            ];
        });
    }

    /**
     * Submit answer to a question
     */
    public function submitAnswer(int $studentId, int $examId, int $questionId, array $data): array
    {
        return DB::transaction(function () use ($studentId, $examId, $questionId, $data) {
            // Check if student has access to this exam
            if (!$this->studentExamRepository->hasExamAccess($studentId, $examId)) {
                throw new \Exception('You do not have access to this exam');
            }

            $exam = $this->subjectExamRepository->findWithRelations($examId);
            $question = $this->examQuestionRepository->findWithRelations($questionId);
            
            // Verify question belongs to this exam
            if ($question->subject_exam_id !== $examId) {
                throw new \Exception('Question does not belong to this exam');
            }

            // Check if exam is currently active
            $now = now();
            if (!$now->between($exam->started_at, $exam->ended_at)) {
                throw new \Exception('This exam is not currently active');
            }

            // Check if student has an active attempt
            $attempt = $this->studentExamRepository->getStudentAttempt($studentId, $examId);

            if (!$attempt) {
                throw new \Exception('No active exam attempt found. Please start the exam first.');
            }

            if ($attempt->is_completed) {
                throw new \Exception('This exam attempt has already been completed');
            }

            // Process the answer
            $answerText = $data['answer_text'];
            $pointsEarned = 0;
            $hasPassed = false;

            if ($question->type === 'multiple_choice') {
                // Auto-grade multiple choice
                $correctOption = $question->questionOptions->where('is_correct', true)->first();
                if ($correctOption && trim($answerText) === trim($correctOption->name)) {
                    $pointsEarned = $question->points;
                    $hasPassed = true;
                }
            } else {
                // Essay questions: has_passed = true if answer submitted, false if empty
                $hasPassed = !empty(trim($answerText ?? ''));
            }

            // Save or update the answer
            // Check if answer exists
            $existingAnswer = $this->questionAnswerRepository->findByQuestionAndStudent($questionId, $studentId);

            if ($existingAnswer) {
                $answer = $this->questionAnswerRepository->update($existingAnswer->id, [
                    'answer_text' => $answerText,
                    'has_passed' => $hasPassed,
                    'points_earned' => $pointsEarned
                ]);
            } else {
                $answer = $this->questionAnswerRepository->create([
                    'exam_question_id' => $questionId,
                    'student_id' => $studentId,
                    'answer_text' => $answerText,
                    'has_passed' => $hasPassed,
                    'points_earned' => $pointsEarned
                ]);
            }

            // Update attempt totals (questions count and points earned)
            $this->updateAttemptTotals($studentId, $examId);

            // Get updated answer count for response
            $answeredCount = $this->studentExamRepository->getAnsweredCount($studentId, $examId);

            return [
                'answer' => $answer,
                'question_type' => $question->type,
                'auto_graded' => $question->type === 'multiple_choice',
                'points_earned' => $pointsEarned,
                'total_answered' => $answeredCount,
                'total_questions' => $attempt->total_questions
            ];
        });
    }

    /**
     * Complete and submit exam
     */
    public function completeExam(int $studentId, int $examId): array
    {
        return DB::transaction(function () use ($studentId, $examId) {
            // Check if student has access to this exam
            if (!$this->studentExamRepository->hasExamAccess($studentId, $examId)) {
                throw new \Exception('You do not have access to this exam');
            }

            $exam = $this->studentExamRepository->getExamWithQuestions($examId);

            // Get student's attempt
            $attempt = $this->studentExamRepository->getStudentAttempt($studentId, $examId);

            if (!$attempt) {
                throw new \Exception('No exam attempt found');
            }

            if ($attempt->is_completed) {
                throw new \Exception('Exam has already been completed');
            }

            // Create placeholder records for unanswered questions
            $this->createPlaceholderAnswers($studentId, $exam);

            // Mark attempt as completed
            $attempt->update([
                'is_completed' => true,
                'completed_at' => now()
            ]);

            // Calculate current score from all answers (including placeholders)
            $answers = $this->studentExamRepository->getStudentAnswers($studentId, $examId);

            $totalPointsEarned = $answers->sum('points_earned');
            $totalPossiblePoints = $exam->examQuestions->sum('points');
            $percentage = $totalPossiblePoints > 0 ? round(($totalPointsEarned / $totalPossiblePoints) * 100, 2) : 0;

            return [
                'attempt' => $attempt,
                'results' => [
                    'total_points_earned' => $totalPointsEarned,
                    'total_possible_points' => $totalPossiblePoints,
                    'percentage' => $percentage,
                    'answered_questions' => $answers->count(),
                    'total_questions' => $exam->examQuestions->count(),
                    'completed_at' => $attempt->completed_at,
                    'needs_manual_grading' => $answers->where('points_earned', 0)->count() > 0
                ]
            ];
        });
    }

    /**
     * Get current exam progress
     */
    public function getProgress(int $studentId, int $examId): array
    {
        // Check if student has access to this exam
        if (!$this->studentExamRepository->hasExamAccess($studentId, $examId)) {
            throw new \Exception('You do not have access to this exam');
        }

        $exam = $this->studentExamRepository->getExamWithQuestions($examId);

        // Get student's attempt
        $attempt = $this->studentExamRepository->getStudentAttempt($studentId, $examId);

        if (!$attempt) {
            throw new \Exception('No exam attempt found');
        }

        // Get submitted answers
        $answers = $this->studentExamRepository->getStudentAnswers($studentId, $examId);

        return [
            'attempt' => $attempt,
            'progress' => [
                'answered_questions' => $answers->count(),
                'total_questions' => $exam->examQuestions->count(),
                'percentage_complete' => $exam->examQuestions->count() > 0 ?
                    round(($answers->count() / $exam->examQuestions->count()) * 100, 2) : 0,
                'time_remaining_minutes' => $this->getTimeRemaining($exam),
                'is_completed' => $attempt->is_completed
            ],
            'answers' => $answers
        ];
    }

    /**
     * Get exam results (completed exams only)
     */
    public function getResults(int $studentId, int $examId): array
    {
        // Check if student has access to this exam
        if (!$this->studentExamRepository->hasExamAccess($studentId, $examId)) {
            throw new \Exception('You do not have access to this exam');
        }

        $exam = $this->studentExamRepository->getExamWithQuestions($examId);

        // Get student's completed attempt
        $attempt = $this->studentExamRepository->getCompletedAttempt($studentId, $examId);

        if (!$attempt) {
            throw new \Exception('No completed exam found');
        }

        // Get all answers with questions
        $answers = $this->studentExamRepository->getStudentAnswersWithOptions($studentId, $examId);

        $totalPointsEarned = $answers->sum('points_earned');
        $totalPossiblePoints = $exam->examQuestions->sum('points');
        $percentage = $totalPossiblePoints > 0 ? round(($totalPointsEarned / $totalPossiblePoints) * 100, 2) : 0;

        return [
            'exam' => [
                'id' => $exam->id,
                'name' => $exam->name,
                'subject' => $exam->subject->name,
                'started_at' => $exam->started_at,
                'ended_at' => $exam->ended_at
            ],
            'attempt' => $attempt,
            'results' => [
                'total_points_earned' => $totalPointsEarned,
                'total_possible_points' => $totalPossiblePoints,
                'percentage' => $percentage,
                'grade' => $this->getGrade($percentage),
                'passed' => $percentage >= 60, // Assuming 60% is passing
                'completed_at' => $attempt->completed_at
            ],
            'answers' => $answers
        ];
    }

    /**
     * Calculate time remaining for exam in minutes
     */
    private function getTimeRemaining(SubjectExam $exam): int
    {
        $now = now();
        $endTime = Carbon::parse($exam->ended_at);

        if ($now->gt($endTime)) {
            return 0;
        }

        return $now->diffInMinutes($endTime);
    }

    /**
     * Get letter grade based on percentage
     */
    private function getGrade(float $percentage): string
    {
        if ($percentage >= 90) return 'A';
        if ($percentage >= 80) return 'B';
        if ($percentage >= 70) return 'C';
        if ($percentage >= 60) return 'D';
        return 'F';
    }

    /**
     * Create placeholder records for unanswered questions
     */
    private function createPlaceholderAnswers(int $studentId, SubjectExam $exam): void
    {
        // Get all exam questions
        $allQuestions = $exam->examQuestions;

        // Get questions that already have answers
        $existingAnswers = $this->questionAnswerRepository->getAll([
            'student_id' => $studentId,
            'exam_id' => $exam->id
        ]);
        $answeredQuestionIds = $existingAnswers->pluck('exam_question_id')->toArray();

        // Find unanswered questions
        $unansweredQuestions = $allQuestions->whereNotIn('id', $answeredQuestionIds);

        // Create placeholder records for unanswered questions
        foreach ($unansweredQuestions as $question) {
            $this->questionAnswerRepository->create([
                'exam_question_id' => $question->id,
                'student_id' => $studentId,
                'answer_text' => null, // NULL indicates no answer provided
                'has_passed' => false,
                'points_earned' => 0,
                'feedback' => 'Question not answered within time limit'
            ]);
        }
    }

    /**
     * Update exam attempt totals including points and percentages
     */
    private function updateAttemptTotals(int $studentId, int $examId): void
    {
        $attempt = $this->studentExamRepository->getStudentAttempt($studentId, $examId);

        if ($attempt) {
            // Get all student answers for this exam
            $answers = $this->studentExamRepository->getStudentAnswers($studentId, $examId);

            // Calculate totals
            $answeredCount = $answers->count();
            $totalPointsEarned = $answers->sum('points_earned');

            // Get total possible points from exam questions
            $exam = $this->studentExamRepository->getExamWithQuestions($examId);
            $totalPossiblePoints = $exam->examQuestions->sum('points');

            // Calculate percentage and pass status
            $scorePercentage = $totalPossiblePoints > 0 ?
                round(($totalPointsEarned / $totalPossiblePoints) * 100, 2) : 0;
            $hasPassed = $scorePercentage >= 60; // Assuming 60% is passing

            $attempt->update([
                'answered_questions' => $answeredCount,
                'points_earned' => $totalPointsEarned,
                'score_percentage' => $scorePercentage,
                'has_passed' => $hasPassed
            ]);
        }
    }
}
