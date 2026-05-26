<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        try {
            $fields = ['id', 'name', 'email', 'photo', 'gender'];
            $perPage = $request->integer('per_page', 6);

            // Handle search
            if ($request->filled('search')) {
                $users = $this->userService->searchUsers(
                    $request->string('search'), 
                    $fields, 
                    $perPage
                );
                return UserResource::collection($users);
            }

            // Filter by gender
            if ($request->filled('gender')) {
                $users = $this->userService->findUsersByGender(
                    $request->string('gender'), 
                    $fields, 
                    $perPage
                );
                return UserResource::collection($users);
            }

            // Filter by role
            if ($request->filled('role')) {
                $users = $this->userService->findUsersByRole(
                    $request->string('role'), 
                    $fields, 
                    $perPage
                );
                return UserResource::collection($users);
            }

            // Handle all parameter
            if ($request->boolean('all')) {
                $users = $this->userService->getAll($fields);
                return UserResource::collection($users);
            }

            // Default paginated response
            $users = $this->userService->getPaginated($fields, $perPage);
            return UserResource::collection($users);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve users',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}