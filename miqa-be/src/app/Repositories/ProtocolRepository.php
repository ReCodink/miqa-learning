<?php

namespace App\Repositories;

use App\Models\Protocols;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProtocolRepository
{
    /**
     * Get paginated protocols with class rooms count
     */
    public function getPaginated(array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return Protocols::select($fields)
            ->latest()
            ->withCount('classRooms') // Mengganti subjects menjadi classRooms
            ->paginate($perPage);
    }

    /**
     * Get all protocols without pagination
     */
    public function getAll(array $fields = ['*']): Collection
    {
        return Protocols::select($fields)
            ->latest()
            ->withCount('classRooms') // Mengganti subjects menjadi classRooms
            ->get();
    }

    /**
     * Find protocol by ID with its related class rooms
     */
    public function findWithClassRooms(string $id, array $fields = ['*']): Protocols
    {
        return Protocols::select($fields)
            ->with([
                // Disesuaikan dengan struktur field di fillable & model ClassRoom Anda
                'classRooms:id,name,photo,protocol_id'
            ])
            ->findOrFail($id);
    }

    /**
     * Search protocols by name and description
     */
    public function searchByNameAndDescription(string $query, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return Protocols::select($fields)
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%") // Mengubah dari 'about' menjadi kolom deskripsi milik protocol Anda
            ->latest()
            ->withCount('classRooms')
            ->paginate($perPage);
    }

    /**
     * Search protocols with pagination for frontend modal
     */
    public function searchWithPagination(string $query, array $fields = ['*'], int $page = 1, int $perPage = 10): array
    {
        $queryBuilder = Protocols::select($fields)
            ->withCount('classRooms')
            ->latest();

        if (!empty($query)) {
            $queryBuilder->where('name', 'LIKE', "%{$query}%")
                         ->orWhere('description', 'LIKE', "%{$query}%");
        }

        $total = $queryBuilder->count();

        $protocols = $queryBuilder->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return [
            'data'         => $protocols,
            'total'        => $total,
            'current_page' => $page,
            'per_page'     => $perPage,
            'has_more'     => $total > ($page * $perPage)
        ];
    }

    /**
     * Search protocols for frontend modal (limit default 6)
     */
    public function searchForModal(string $query, array $fields = ['*'], int $limit = 6): Collection
    {
        $queryBuilder = Protocols::select($fields)
            ->withCount('classRooms')
            ->latest()
            ->limit($limit);

        if (!empty($query)) {
            $queryBuilder->where('name', 'LIKE', "%{$query}%")
                         ->orWhere('description', 'LIKE', "%{$query}%");
        }

        return $queryBuilder->get();
    }

    /**
     * Find multiple protocols by IDs
     */
    public function findManyByIds(array $ids, array $fields = ['*']): Collection
    {
        return Protocols::select($fields)
            ->whereIn('id', $ids)
            ->with('classRooms')
            ->withCount('classRooms')
            ->get();
    }

    /**
     * Create a new protocol
     */
    public function create(array $data): Protocols
    {
        return Protocols::create($data);
    }

    /**
     * Update protocol by ID
     */
    public function update(string $id, array $data): Protocols
    {
        $protocol = Protocols::findOrFail($id);
        $protocol->update($data);
        return $protocol->fresh();
    }

    /**
     * Delete protocol by ID
     */
    public function delete(string $id): bool
    {
        $protocol = Protocols::findOrFail($id);
        return $protocol->delete();
    }
}
