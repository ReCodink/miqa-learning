<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\Api\UserResource;
use App\Services\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $fields = ['id', 'code', 'name', 'email', 'photo', 'gender'];
            $perPage = $request->integer('per_page', 6);

            if ($request->boolean('all')) {
                return UserResource::collection($this->userService->getAll($fields));
            }

            if ($request->filled('search')) {
                $users = $this->userService->searchUsers($request->string('search'), $fields, $perPage);
            } elseif ($request->filled('code')) {
                $users = $this->userService->findUsersByCode($request->string('code'), $fields, $perPage);
            } elseif ($request->filled('gender')) {
                $users = $this->userService->findUsersByGender($request->string('gender'), $fields, $perPage);
            } elseif ($request->filled('role')) {
                $users = $this->userService->findUsersByRole($request->string('role'), $fields, $perPage);
            } else {
                $users = $this->userService->getPaginated($fields, $perPage);
            }

            return UserResource::collection($users);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve users.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $user = $this->userService->findUser($id, ['*']);
            return response()->json([
                'success' => true,
                'message' => 'User retrieved successfully.',
                'data' => new UserResource($user)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function store(UserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
                'data' => new UserResource($user)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function update(UserRequest $request, string $id): JsonResponse
    {
        try {
            $user = $this->userService->updateUser($id, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'data' => new UserResource($user)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->userService->deleteUser($id);
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Function Search With Pagination
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $search = $request->get('search', '');
            $page = $request->integer('page', 1);
            $perPage = $request->integer('limit', 6);
            $fields = ['id', 'code', 'name', 'photo'];

            $paginator = $this->userService->searchUsers($search, $fields, $perPage, $page);

            return response()->json([
                'success' => true,
                'data' => UserResource::collection($paginator->items()),
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'has_more' => $paginator->hasMorePages()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search users.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
