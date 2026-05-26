<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PresenceSecurityFlag;
use App\Models\PresenceDevice;
use App\Models\Presence;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PresenceSecurityController extends Controller
{
    /**
     * List unreviewed security flags
     */
    public function listFlags(Request $request): JsonResponse
    {
        $query = PresenceSecurityFlag::query();

        // Filter by severity
        if ($request->has('severity')) {
            $severities = explode(',', $request->input('severity'));
            $query->whereIn('flag_severity', $severities);
        }

        // Filter by review status (default: unreviewed)
        $isReviewed = $request->input('is_reviewed', false);
        $query->where('is_reviewed', filter_var($isReviewed, FILTER_VALIDATE_BOOLEAN));

        // Filter by flag type
        if ($request->has('flag_type')) {
            $query->where('flag_type', $request->input('flag_type'));
        }

        // Sort by severity (critical first)
        $severityOrder = ['critical' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
        $flags = $query
            ->with(['presence.user', 'presence.session', 'user'])
            ->orderByRaw("FIELD(flag_severity, 'critical', 'high', 'medium', 'low')")
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
     * Review a security flag
     */
    public function reviewFlag(Request $request, PresenceSecurityFlag $flag): JsonResponse
    {
        if ($flag->is_reviewed) {
            return response()->json([
                'success' => false,
                'message' => 'Flag already reviewed',
            ], 400);
        }

        $validated = $request->validate([
            'action' => 'required|in:approved,rejected,investigate',
            'review_notes' => 'nullable|string|max:1000',
        ]);

        $reviewer = auth()->user();
        $action = $validated['action'];
        $notes = $validated['review_notes'] ?? '';

        try {
            match ($action) {
                'approved' => $flag->approve($reviewer, $notes),
                'rejected' => $flag->reject($reviewer, $notes),
                'investigate' => $flag->investigate($reviewer, $notes),
            };

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $flag->id,
                    'is_reviewed' => $flag->is_reviewed,
                    'action_taken' => $flag->action_taken,
                    'reviewed_by' => $flag->reviewedBy->name,
                    'reviewed_at' => $flag->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trust a device (whitelist it)
     */
    public function trustDevice(Request $request, PresenceDevice $device): JsonResponse
    {
        if ($device->is_trusted) {
            return response()->json([
                'success' => false,
                'message' => 'Device is already trusted',
            ], 400);
        }

        $device->trust();

        \App\Models\PresenceAuditLog::log(
            action: "Device trusted: {$device->device_name}",
            actionType: \App\Models\PresenceAuditLog::ACTION_DEVICE_TRUSTED,
            user: $device->user,
            presence: null,
            actor: auth()->user(),
            actorRole: auth()->user()->hasRole('manager') ? 'manager' : 'teacher',
            ipAddress: $request->ip(),
            details: [
                'device_id' => $device->id,
                'device_fingerprint_hash' => $device->device_fingerprint_hash,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $device->id,
                'is_trusted' => $device->is_trusted,
                'device_name' => $device->device_name,
            ],
        ]);
    }

    /**
     * Revoke device (untrust it)
     */
    public function revokeDevice(Request $request, PresenceDevice $device): JsonResponse
    {
        if (!$device->is_trusted) {
            return response()->json([
                'success' => false,
                'message' => 'Device is not trusted',
            ], 400);
        }

        $device->untrust();

        \App\Models\PresenceAuditLog::log(
            action: "Device revoked: {$device->device_name}",
            actionType: \App\Models\PresenceAuditLog::ACTION_SESSION_ENDED,
            user: $device->user,
            presence: null,
            actor: auth()->user(),
            actorRole: auth()->user()->hasRole('manager') ? 'manager' : 'teacher',
            ipAddress: $request->ip(),
            details: [
                'device_id' => $device->id,
                'device_name' => $device->device_name,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $device->id,
                'is_trusted' => $device->is_trusted,
                'device_name' => $device->device_name,
            ],
        ]);
    }

    /**
     * Get user's registered devices
     */
    public function userDevices(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Allow managers to view any user's devices
        if ($request->has('user_id') && auth()->user()->can('manage-all-attendance')) {
            $user = \App\Models\User::findOrFail($request->input('user_id'));
        }

        $devices = $user->registeredDevices()
            ->orderBy('is_trusted', 'desc')
            ->orderBy('last_seen_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $devices->map(function ($device) {
                return [
                    'id' => $device->id,
                    'device_name' => $device->device_name,
                    'device_type' => $device->device_type,
                    'os_name' => $device->os_name,
                    'os_version' => $device->os_version,
                    'is_trusted' => $device->is_trusted,
                    'last_seen_at' => $device->last_seen_at,
                    'created_at' => $device->created_at,
                ];
            })->toArray(),
        ]);
    }
}
