<?php

namespace App\Services;

use App\Repositories\ProtocolRepository;
use App\Models\Protocols;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProtocolService
{
    private ProtocolRepository $protocolRepository;

    public function __construct(ProtocolRepository $protocolRepository)
    {
        $this->protocolRepository = $protocolRepository;
    }

    /**
     * Get paginated protocols
     */
    public function getPaginated(array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return $this->protocolRepository->getPaginated($fields, $perPage);
    }

    /**
     * Get all protocols without pagination
     */
    public function getAll(array $fields = ['*']): Collection
    {
        return $this->protocolRepository->getAll($fields);
    }

    /**
     * Find protocol by ID
     */
    public function findProtocol(string $id, array $fields = ['*']): Protocols
    {
        return $this->protocolRepository->findWithClassRooms($id, $fields);
    }

    /**
     * Search protocols by query
     */
    public function searchProtocols(string $query, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return $this->protocolRepository->searchByNameAndDescription($query, $fields, $perPage);
    }

    /**
     * Create a new protocol
     */
    public function createProtocol(array $data): Protocols
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $data['photo'] = $this->uploadPhoto($data['photo']);
            }

            return $this->protocolRepository->create($data);
        });
    }

    /**
     * Update protocol by ID
     */
    public function updateProtocol(string $id, array $data): Protocols
    {
        return DB::transaction(function () use ($id, $data) {
            $protocol = $this->protocolRepository->findWithClassRooms($id, ['*']);
            $oldPhoto = $protocol->getRawOriginal('photo'); // Mengambil path foto mentah dari DB

            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $data['photo'] = $this->uploadPhoto($data['photo']);
            }

            $updatedProtocol = $this->protocolRepository->update($id, $data);

            // Hapus foto lama jika ada upload foto baru
            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile && $oldPhoto) {
                $this->deletePhoto($oldPhoto);
            }

            return $updatedProtocol;
        });
    }

    /**
     * Delete protocol by ID
     */
    public function deleteProtocol(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $protocol = $this->protocolRepository->findWithClassRooms($id, ['id', 'photo']);

            // Validasi pengecekan relasi ke class rooms sebelum dihapus
            $classRoomsCount = $protocol->classRooms()->count();
            if ($classRoomsCount > 0) {
                throw new \InvalidArgumentException("Cannot delete protocol. This protocol has {$classRoomsCount} class rooms assigned to it.");
            }

            $photoPath = $protocol->getRawOriginal('photo'); // Mengambil path foto mentah

            if ($photoPath) {
                $this->deletePhoto($photoPath);
            }

            return $this->protocolRepository->delete($id);
        });
    }

    /**
     * Search protocols with pagination for modal
     */
    public function searchWithPagination(string $query = '', array $fields = ['*'], int $page = 1, int $perPage = 10): array
    {
        return $this->protocolRepository->searchWithPagination($query, $fields, $page, $perPage);
    }

    /**
     * Search protocols for modal
     */
    public function searchForModal(string $query = '', array $fields = ['*'], int $limit = 6): Collection
    {
        return $this->protocolRepository->searchForModal($query, $fields, $limit);
    }

    /**
     * Upload photo and return storage path
     */
    private function uploadPhoto(UploadedFile $photo): string
    {
        // Menyimpan foto di dalam folder public/protocols
        return $photo->store('protocols', 'public');
    }

    /**
     * Delete photo from storage
     */
    private function deletePhoto(string $photoPath): void
    {
        if (Storage::disk('public')->exists($photoPath)) {
            Storage::disk('public')->delete($photoPath);
        }
    }
}
