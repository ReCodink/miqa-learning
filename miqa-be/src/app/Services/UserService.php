<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getPaginated(array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return $this->userRepository->getPaginated($fields, $perPage);
    }

    public function getAll(array $fields = ['*']): Collection
    {
        return $this->userRepository->getAll($fields);
    }

    public function findUser(string $id, array $fields = ['*']): User
    {
        return $this->userRepository->getById($id, $fields);
    }

    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $data['photo'] = $this->uploadPhoto($data['photo']);
            }
            return $this->userRepository->create($data);
        });
    }

    public function updateUser(string $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {
            $user = $this->userRepository->getById($id, ['id', 'photo']);
            $oldPhoto = $user->getRawOriginal('photo');

            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $data['photo'] = $this->uploadPhoto($data['photo']);
            }

            $updatedUser = $this->userRepository->update($id, $data);

            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile && $oldPhoto) {
                $this->deletePhoto($oldPhoto);
            }

            return $updatedUser;
        });
    }

    public function deleteUser(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $user = $this->userRepository->getById($id, ['id', 'photo']);

            if (method_exists($user, 'subjects') && $user->subjects()->count() > 0) {
                throw new \InvalidArgumentException("Cannot delete user. This user has subjects assigned to it.");
            }

            $photoPath = $user->getRawOriginal('photo');
            if ($photoPath) {
                $this->deletePhoto($photoPath);
            }

            return $this->userRepository->delete($id);
        });
    }

    public function searchUsers(string $query, array $fields = ['*'], int $perPage = 10, int $page = null): LengthAwarePaginator
    {
        return $this->userRepository->search($query, $fields, $perPage, $page);
    }

    public function findUsersByCode(string $code, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return $this->userRepository->findByCode($code, $fields, $perPage);
    }

    public function findUsersByGender(string $gender, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return $this->userRepository->findByGender($gender, $fields, $perPage);
    }

    public function findUsersByRole(string $role, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return $this->userRepository->findByRole($role, $fields, $perPage);
    }

    public function searchUsersForModal(string $query = '', array $fields = ['*'], int $limit = 6): Collection
    {
        return $this->userRepository->searchForModal($query, $fields, $limit);
    }

    private function uploadPhoto(UploadedFile $photo): string
    {
        return $photo->store('users', 'public');
    }

    private function deletePhoto(string $photoPath): void
    {
        if (Storage::disk('public')->exists($photoPath)) {
            Storage::disk('public')->delete($photoPath);
        }
    }
}
