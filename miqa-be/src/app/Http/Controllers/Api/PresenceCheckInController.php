<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Presence;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PresenceCheckInController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Check-in with QR token
     */
    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qr_token' => 'required|string|uuid',
            'session_id' => 'required|exists:presence_sessions,id',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'device_fingerprint' => 'nullable|array',
            'device_fingerprint.user_agent' => 'nullable|string',
            'device_fingerprint.device_id' => 'nullable|string',
            'device_fingerprint.device_type' => 'nullable|in:mobile,tablet,desktop',
            'device_fingerprint.os_name' => 'nullable|string',
            'device_fingerprint.os_version' => 'nullable|string',
            'device_fingerprint.app_version' => 'nullable|string',
        ]);

        $user = auth()->user();
        $deviceFingerprint = $validated['device_fingerprint'] ?? [];

        // Add default user agent if not provided
        if (empty($deviceFingerprint['user_agent'])) {
            $deviceFingerprint['user_agent'] = $request->header('User-Agent');
        }

        $result = $this->attendanceService->checkIn(
            qrUuid: $validated['qr_token'],
            user: $user,
            gpsLatitude: $validated['gps_latitude'] ?? null,
            gpsLongitude: $validated['gps_longitude'] ?? null,
            deviceFingerprint: $deviceFingerprint,
            ipAddress: $request->ip(),
            userAgent: $request->header('User-Agent')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'validation_results' => $result['validation_results'] ?? [],
                'flags' => $result['flags'] ?? [],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'presence_id' => $result['presence_id'],
            'is_valid' => $result['is_valid'],
            'checked_in_at' => $result['checked_in_at'],
            'validation_results' => $result['validation_results'],
            'flags' => $result['flags'],
            'message' => $result['message'],
        ], 200);
    }

    /**
     * Check-out from session
     */
    public function checkOut(Request $request, Presence $presence): JsonResponse
    {
        if ($presence->user_id !== auth()->id() && !auth()->user()->can('manage-all-attendance')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($presence->checked_out_at) {
            return response()->json([
                'success' => false,
                'message' => 'Already checked out',
            ], 400);
        }

        $result = $this->attendanceService->checkOut($presence, auth()->user());

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'checked_out_at' => $result['checked_out_at'],
            'duration_minutes' => $result['duration_minutes'],
            'message' => $result['message'],
        ]);
    }

    /**
     * Get current user's attendance records
     */
    public function myAttendance(Request $request): JsonResponse
    {
        $query = auth()->user()->attendanceRecords()
            ->with(['session', 'securityFlags'])
            ->orderBy('checked_in_at', 'desc');

        // Filter by session
        if ($request->has('session_id')) {
            $query->where('presence_session_id', $request->input('session_id'));
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('checked_in_at', '>=', $request->input('from_date'));
        }

        if ($request->has('to_date')) {
            $query->whereDate('checked_in_at', '<=', $request->input('to_date'));
        }

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
