<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePresenceSessionRequest; // Pastikan namespace ini sesuai
use App\Http\Resources\PresenceSessionResource;
use App\Models\PresenceSession;
use App\Services\AttendanceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Exception;

class PresenceSessionController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Get list of presence sessions with optional filtering
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 6);

            if ($request->boolean('all')) {
                $sessions = PresenceSession::with('createdBy')->get();
                return PresenceSessionResource::collection($sessions);
            }

            $query = PresenceSession::with('createdBy')->orderBy('created_at', 'desc');

            if ($request->filled('session_type')) {
                $query->where('session_type', $request->string('session_type'));
            }

            if ($request->filled('class_room_id')) {
                $query->where('class_room_id', $request->string('class_room_id'));
            }

            $sessions = $query->paginate($perPage);
            return PresenceSessionResource::collection($sessions);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve presence sessions.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Create a new presence session
     */
    public function store(StorePresenceSessionRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['created_by_user_id'] = auth()->id();

            $session = PresenceSession::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Presence session created successfully.',
                'data' => new PresenceSessionResource($session->load('createdBy'))
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create presence session.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get session details
     */
    public function show(string $id): JsonResponse
    {
        try {
            $session = PresenceSession::with(['createdBy', 'qrTokens', 'presences'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Presence session retrieved successfully.',
                'data' => new PresenceSessionResource($session)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Presence session not found.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve presence session.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update session details
     */
    public function update(StorePresenceSessionRequest $request, string $id): JsonResponse
    {
        try {
            $session = PresenceSession::findOrFail($id);

            $session->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Presence session updated successfully.',
                'data' => new PresenceSessionResource($session->refresh()->load(['createdBy', 'qrTokens', 'presences']))
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Presence session not found.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update presence session.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Activate session (start attendance)
     */
    public function activate(string $id): JsonResponse
    {
        try {
            $session = PresenceSession::findOrFail($id);

            if ($session->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session is already active',
                ], 400);
            }

            $session->activate();

            return response()->json([
                'success' => true,
                'message' => 'Presence session activated successfully.',
                'data' => [
                    'id' => $session->id,
                    'is_active' => $session->is_active,
                    'actual_start_at' => $session->actual_start_at,
                ],
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Presence session not found.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate the session.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Deactivate session (end attendance)
     */
    public function deactivate(string $id): JsonResponse
    {
        try {
            $session = PresenceSession::findOrFail($id);

            if (!$session->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session is not active',
                ], 400);
            }

            $session->deactivate();

            return response()->json([
                'success' => true,
                'message' => 'Presence session deactivated successfully.',
                'data' => [
                    'id' => $session->id,
                    'is_active' => $session->is_active,
                    'actual_end_at' => $session->actual_end_at,
                ],
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Presence session not found.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate the session.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get session attendance report
     */
    public function report(string $id): JsonResponse
    {
        try {
            $session = PresenceSession::findOrFail($id);
            $report = $this->attendanceService->getSessionReport($session);

            return response()->json([
                'success' => true,
                'message' => 'Session report generated successfully.',
                'data' => $report,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Presence session not found.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate session report.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get list of attendances for session
     */
    public function attendances(Request $request, string $id): JsonResponse
    {
        try {
            $session = PresenceSession::findOrFail($id);

            $query = $session->presences()
                ->with(['user', 'securityFlags'])
                ->orderBy('checked_in_at', 'desc');

            if ($request->has('is_valid')) {
                $query->where('is_valid', $request->boolean('is_valid'));
            }

            $perPage = $request->integer('limit', 20);
            $attendances = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $attendances->items(),
                'total' => $attendances->total(),
                'current_page' => $attendances->currentPage(),
                'per_page' => $attendances->perPage(),
                'has_more' => $attendances->hasMorePages()
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Presence session not found.'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load attendance list.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
