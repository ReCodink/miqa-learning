<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PresenceSession;
use App\Models\PresenceQrToken;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;

class PresenceQrController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Generate QR token for session
     */
    public function generateToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:presence_sessions,id',
            'expires_in_seconds' => 'nullable|integer|min:15|max:300',
        ]);

        $session = PresenceSession::findOrFail($validated['session_id']);

        if (!$session->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Session is not active',
            ], 400);
        }

        $expiresIn = $validated['expires_in_seconds'] ?? 30;
        $token = $this->attendanceService->generateQrToken($session, auth()->user(), $expiresIn);

        // Generate QR code image
        $renderer = new ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($token->uuid);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $token->id,
                'uuid' => $token->uuid,
                'session_id' => $token->presence_session_id,
                'expires_at' => $token->expires_at,
                'expires_in_seconds' => $expiresIn,
                'qr_code_svg' => $qrCodeSvg,
            ],
        ], 201);
    }

    /**
     * Get QR token details
     */
    public function show(PresenceQrToken $token): JsonResponse
    {
        $isExpired = $token->isExpired();
        $isValid = $token->isValid();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $token->id,
                'uuid' => $token->uuid,
                'session_id' => $token->presence_session_id,
                'is_used' => $token->is_used,
                'is_revoked' => $token->is_revoked,
                'is_expired' => $isExpired,
                'is_valid' => $isValid,
                'generated_at' => $token->generated_at,
                'expires_at' => $token->expires_at,
                'used_at' => $token->used_at,
                'used_by' => $token->usedBy ? $token->usedBy->name : null,
                'revoked_at' => $token->revoked_at,
                'revoke_reason' => $token->revoke_reason,
            ],
        ]);
    }

    /**
     * Revoke QR token before use
     */
    public function revokeToken(Request $request, PresenceQrToken $token): JsonResponse
    {
        if ($token->is_used) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot revoke an already used token',
            ], 400);
        }

        if ($token->is_revoked) {
            return response()->json([
                'success' => false,
                'message' => 'Token is already revoked',
            ], 400);
        }

        $reason = $request->input('reason', 'Manually revoked by ' . auth()->user()->name);
        $token->revoke($reason);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $token->id,
                'is_revoked' => $token->is_revoked,
                'revoked_at' => $token->revoked_at,
                'revoke_reason' => $token->revoke_reason,
            ],
        ]);
    }
}
