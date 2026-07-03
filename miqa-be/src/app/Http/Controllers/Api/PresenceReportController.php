<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PresenceSession;
use App\Models\PresenceSecurityFlag;
use App\Models\PresenceAuditLog;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PresenceReportController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Get user attendance statistics
     */
    public function userStats(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Allow managers to view any user's stats
        if ($request->has('user_id') && auth()->user()->can('view-all-attendance')) {
            $user = User::findOrFail($request->input('user_id'));
        }

        $stats = $this->attendanceService->getUserAttendanceStats($user);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get session attendance report
     */
    public function sessionReport(PresenceSession $session): JsonResponse
    {
        $report = $this->attendanceService->getSessionReport($session);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }
}
