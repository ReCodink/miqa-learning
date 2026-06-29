<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\TeacherRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TeacherService
{
    private TeacherRepository $teacherRepository;

    public function __construct(TeacherRepository $teacherRepository)
    {
        $this->teacherRepository = $teacherRepository;
    }

    public function getPaginated(array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return $this->teacherRepository->getPaginated($fields, $perPage);
    }

    public function getAll(array $fields = ['*']): Collection
    {
        return $this->teacherRepository->getAll($fields);
    }

    public function findTeacher(string $id, array $fields = ['*']): User
    {
        return $this->teacherRepository->findWithSubjects($id, $fields);
    }

    public function createTeacher(array $data): User
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $data['photo'] = $this->uploadPhoto($data['photo']);
            }
            return $this->teacherRepository->create($data);
        });
    }

    public function updateTeacher(string $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {
            $teacher = $this->teacherRepository->findWithSubjects($id, ['id', 'photo']);
            $oldPhoto = $teacher->getRawOriginal('photo');

            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $data['photo'] = $this->uploadPhoto($data['photo']);
            }

            $updatedTeacher = $this->teacherRepository->update($id, $data);

            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile && $oldPhoto) {
                $this->deletePhoto($oldPhoto);
            }

            return $updatedTeacher;
        });
    }

    public function deleteTeacher(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $teacher = $this->teacherRepository->findWithSubjects($id, ['id', 'photo']);

            // Guard validation mimicking the UserService pattern
            if (method_exists($teacher, 'subjects') && $teacher->subjects()->count() > 0) {
                throw new \InvalidArgumentException("Cannot delete teacher. This teacher has subjects assigned to them.");
            }

            $photoPath = $teacher->getRawOriginal('photo');
            if ($photoPath) {
                $this->deletePhoto($photoPath);
            }

            return $this->teacherRepository->delete($id);
        });
    }

    public function searchTeachers(string $query, array $fields = ['*'], int $perPage = 10, int $page = null): LengthAwarePaginator
    {
        return $this->teacherRepository->searchByNameAndEmail($query, $fields, $perPage, $page);
    }

    public function findTeachersByGender(string $gender, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return $this->teacherRepository->findByGender($gender, $fields, $perPage);
    }

    public function getUnassignedTeachers(array $fields = ['*']): Collection
    {
        return $this->teacherRepository->getUnassignedTeachers($fields);
    }

    public function searchWithPagination(string $query = '', array $fields = ['*'], int $page = 1, int $perPage = 10): array
    {
        return $this->teacherRepository->searchWithPagination($query, $fields, $page, $perPage);
    }

    public function searchForModal(string $query = '', array $fields = ['*'], int $limit = 6): Collection
    {
        return $this->teacherRepository->searchForModal($query, $fields, $limit);
    }

    private function uploadPhoto(UploadedFile $photo): string
    {
        return $photo->store('teachers', 'public');
    }

    private function deletePhoto(string $photoPath): void
    {
        if (Storage::disk('public')->exists($photoPath)) {
            Storage::disk('public')->delete($photoPath);
        }
    }
}