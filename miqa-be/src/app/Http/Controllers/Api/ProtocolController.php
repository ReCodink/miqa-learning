<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProtocolRequest;
use App\Http\Requests\ProtocolSearchRequest;
use App\Http\Resources\Api\ProtocolResource;
use App\Services\ProtocolService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProtocolController extends Controller
{
    private ProtocolService $protocolService;

    public function __construct(ProtocolService $protocolService)
    {
        $this->protocolService = $protocolService;
    }

    /**
     * Display a listing of protocols
     */
    public function index(Request $request)
    {
        try {
            // Field disesuaikan dengan struktur model Protocols (tanpa photo, menggunakan description)
            $fields = ['id', 'code', 'name', 'description'];
            $perPage = $request->integer('per_page', 6);

            // Handle search
            if ($request->filled('search')) {
                $protocols = $this->protocolService->searchProtocols(
                    $request->string('search'),
                    $fields,
                    $perPage
                );
                return ProtocolResource::collection($protocols);
            }

            // Handle all parameter
            if ($request->boolean('all')) {
                $protocols = $this->protocolService->getAll($fields);
                return ProtocolResource::collection($protocols);
            }

            // Default paginated response
            $protocols = $this->protocolService->getPaginated($fields, $perPage);
            return ProtocolResource::collection($protocols);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve protocols',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified protocol
     */
    public function show(String $id)
    {
        try {
            $protocol = $this->protocolService->findProtocol($id, ['*']);
            return response()->json([
                'success' => true,
                'data' => new ProtocolResource($protocol)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Protocol not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve protocol',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created protocol
     */
    public function store(ProtocolRequest $request)
    {
        try {
            $protocol = $this->protocolService->createProtocol($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Protocol created successfully',
                'data' => new ProtocolResource($protocol)
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create protocol',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update the specified protocol
     */
    public function update(ProtocolRequest $request, String $id)
    {
        try {
            $protocol = $this->protocolService->updateProtocol($id, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Protocol updated successfully',
                'data' => new ProtocolResource($protocol)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Protocol not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update protocol',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified protocol
     */
    public function destroy(String $id)
    {
        try {
            $this->protocolService->deleteProtocol($id);
            return response()->json([
                'success' => true,
                'message' => 'Protocol deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Protocol not found'
            ], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete protocol',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Search protocols with pagination for frontend modal
     */
    public function search(ProtocolSearchRequest $request)
    {
        try {
            $search = $request->get('q', '') ?? '';
            $page = $request->get('page', 1);
            $perPage = $request->get('limit', 6);
            $fields = ['id', 'code', 'name', 'description'];

            $result = $this->protocolService->searchWithPagination($search, $fields, $page, $perPage);

            return response()->json([
                'success' => true,
                'data' => ProtocolResource::collection($result['data']),
                'total' => $result['total'],
                'current_page' => $result['current_page'],
                'per_page' => $result['per_page'],
                'has_more' => $result['has_more']
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search protocols',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
