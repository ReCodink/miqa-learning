<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PresenceSessionResource;
use App\Models\PresenceSession;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PresenceSessionController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Create a new presence session
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_room_id' => 'required|exists:class_rooms,id',
            'session_name' => 'required|string|max:255',
            'session_type' => 'required|in:class,event,exam_preparation',
            'scheduled_start_at' => 'nullable|date',
            'scheduled_end_at' => 'nullable|date|after:scheduled_start_at',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'gps_radius_meters' => 'nullable|integer|min:10|max:500',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by_user_id'] = auth()->id();

        $session = PresenceSession::create($validated);

        return response()->json([
            'success' => true,
            'data' => new PresenceSessionResource($session->load('createdBy'))
        ], 201);
    }

    /**
     * Get session details
     */
    public function show(PresenceSession $session): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new PresenceSessionResource($session->load(['createdBy', 'qrTokens', 'presences']))
        ]);
    }

    /**
     * Update session details
     */
    public function update(Request $request, PresenceSession $session): JsonResponse
    {
        $validated = $request->validate([
            'session_name' => 'nullable|string|max:255',
            'session_type' => 'nullable|in:class,event,exam_preparation',
            'scheduled_start_at' => 'nullable|date',
            'scheduled_end_at' => 'nullable|date|after:scheduled_start_at',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'gps_radius_meters' => 'nullable|integer|min:10|max:500',
            'notes' => 'nullable|string',
        ]);

        $session->update($validated);

        return response()->json([
            'success' => true,
            'data' => new PresenceSessionResource($session->refresh()->load(['createdBy', 'qrTokens', 'presences']))
        ]);
    }

    /**
     * Activate session (start attendance)
     */
    public function activate(PresenceSession $session): JsonResponse
    {
        if ($session->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Session is already active',
            ], 400);
        }

        $session->activate();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $session->id,
                'is_active' => $session->is_active,
                'actual_start_at' => $session->actual_start_at,
            ],
        ]);
    }

    /**
     * Deactivate session (end attendance)
     */
    public function deactivate(PresenceSession $session): JsonResponse
    {
        if (!$session->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Session is not active',
            ], 400);
        }

        $session->deactivate();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $session->id,
                'is_active' => $session->is_active,
                'actual_end_at' => $session->actual_end_at,
            ],
        ]);
    }

    /**
     * Get session attendance report
     */
    public function report(PresenceSession $session): JsonResponse
    {
        $report = $this->attendanceService->getSessionReport($session);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get list of attendances for session
     */
    public function attendances(Request $request, PresenceSession $session): JsonResponse
    {
        $query = $session->presences()
            ->with(['user', 'securityFlags'])
            ->orderBy('checked_in_at', 'desc');

        // Filter by validation status
        if ($request->has('is_valid')) {
            $query->where('is_valid', filter_var($request->input('is_valid'), FILTER_VALIDATE_BOOLEAN));
        }

        $attendances = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $attendances->items(),
            'pagination' => [
                'total' => $attendances->total(),
                'per_page' => $attendances->perPage(),
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
            ],
        ]);
    }
}
