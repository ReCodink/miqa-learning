<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\Api\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle Session-based Login.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->login($request->validated());

        return response()->json([
            'message' => 'Login successful',
            'user'    => new UserResource($user),
        ]);
    }

    /**
     * Handle Token-based Login.
     */
    public function tokenLogin(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->tokenLogin($request->validated());

        return response()->json([
            'message' => 'Login successful',
            'token'   => $result['token'],
            'user'    => new UserResource($result['user']),
        ]);
    }

    /**
     * Handle Logout.
     */
    public function logout(Request $request): JsonResponse
    {
        // Jika menggunakan token (Sanctum), hapus token saat ini
        if ($request->user() && method_exists($request->user(), 'currentAccessToken')) {
            $request->user()->currentAccessToken()->delete();
        }

        // Jika menggunakan web session, invalidate session-nya
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get Authenticated User Profile.
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json(
            new UserResource($request->user()->load(['roles']))
        );
    }
}
