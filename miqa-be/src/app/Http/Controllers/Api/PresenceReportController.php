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

    /**
     * Get security flags for review
     */
    public function securityFlags(Request $request): JsonResponse
    {
        $query = PresenceSecurityFlag::query();

        // Filter by severity
        if ($request->has('severity')) {
            $severities = explode(',', $request->input('severity'));
            $query->whereIn('flag_severity', $severities);
        }

        // Filter by review status
        if ($request->has('is_reviewed')) {
            $query->where('is_reviewed', filter_var($request->input('is_reviewed'), FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by flag type
        if ($request->has('flag_type')) {
            $query->where('flag_type', $request->input('flag_type'));
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        $flags = $query
            ->with(['presence.user', 'presence.session'])
            ->orderBy('flag_severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $flags->items(),
            'pagination' => [
                'total' => $flags->total(),
                'per_page' => $flags->perPage(),
                'current_page' => $flags->currentPage(),
                'last_page' => $flags->lastPage(),
            ],
        ]);
    }

    /**
     * Get audit logs
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $query = PresenceAuditLog::query();

        // Filter by action type
        if ($request->has('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter by actor (who performed the action)
        if ($request->has('actor_id')) {
            $query->where('actor_user_id', $request->input('actor_id'));
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        $logs = $query
            ->with(['user', 'actor', 'presence'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

    /**
     * Get attendance summary statistics
     */
    public function summary(Request $request): JsonResponse
    {
        $fromDate = $request->input('from_date', now()->subDays(7)->format('Y-m-d'));
        $toDate = $request->input('to_date', now()->format('Y-m-d'));

        // Total attendance records
        $totalRecords = \App\Models\Presence::whereBetween('checked_in_at', [$fromDate, $toDate])->count();

        // Valid vs invalid
        $validRecords = \App\Models\Presence::whereBetween('checked_in_at', [$fromDate, $toDate])
            ->where('is_valid', true)
            ->count();

        $invalidRecords = $totalRecords - $validRecords;

        // Flagged records
        $flaggedRecords = PresenceSecurityFlag::whereBetween('created_at', [$fromDate, $toDate])->count();

        // By severity
        $bySeverity = PresenceSecurityFlag::whereBetween('created_at', [$fromDate, $toDate])
            ->selectRaw('flag_severity, COUNT(*) as count')
            ->groupBy('flag_severity')
            ->pluck('count', 'flag_severity')
            ->toArray();

        // By flag type
        $byFlagType = PresenceSecurityFlag::whereBetween('created_at', [$fromDate, $toDate])
            ->selectRaw('flag_type, COUNT(*) as count')
            ->groupBy('flag_type')
            ->pluck('count', 'flag_type')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'date_range' => [
                    'from' => $fromDate,
                    'to' => $toDate,
                ],
                'total_records' => $totalRecords,
                'valid_records' => $validRecords,
                'invalid_records' => $invalidRecords,
                'validity_rate' => $totalRecords > 0 ? round(($validRecords / $totalRecords) * 100, 2) : 0,
                'flagged_records' => $flaggedRecords,
                'by_severity' => $bySeverity,
                'by_flag_type' => $byFlagType,
            ],
        ]);
    }
}
