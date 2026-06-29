<?php

namespace App\Repositories;

use App\Models\ClassSubject;
use App\Models\Subject;
use App\Models\ClassRoom;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ClassSubjectRepository
{
    /**
     * Get paginated subject assignments
     */
    public function getPaginated(array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return ClassSubject::select($fields)
            ->with([
                'subject:id,name,photo,tagline,teacher_id,topic_id',
                'subject.teacher:id,name,email,photo',
                'subject.topic:id,name,photo',
            'classRoom:id,name,photo,protocol_id'
            ])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get all subject assignments without pagination
     */
    public function getAll(array $fields = ['*']): Collection
    {
        return ClassSubject::select($fields)
            ->with([
                'subject:id,name,photo,tagline,teacher_id,topic_id',
                'subject.teacher:id,name,email,photo',
                'subject.topic:id,name,photo',
            'classRoom:id,name,photo,protocol_id'
            ])
            ->latest()
            ->get();
    }

    /**
     * Find assignment by ID with full relationships
     */
    public function findWithRelations(int $id, array $fields = ['*']): ClassSubject
    {
        return ClassSubject::select($fields)
            ->with([
                'subject:id,name,photo,tagline,about,teacher_id,topic_id',
                'subject.teacher:id,name,email,photo',
                'subject.topic:id,name,photo',
            'classRoom:id,name,photo,protocol_id'
            ])
            ->findOrFail($id);
    }

    /**
     * Create a new subject assignment
     */
    public function create(array $data): ClassSubject
    {
        return ClassSubject::create($data);
    }

    /**
     * Update assignment by ID
     */
    public function update(int $id, array $data): ClassSubject
    {
        $assignment = ClassSubject::findOrFail($id);
        $assignment->update($data);
        return $assignment->fresh();
    }

    /**
     * Delete assignment by ID
     */
    public function delete(int $id): bool
    {
        $assignment = ClassSubject::findOrFail($id);
        return $assignment->delete();
    }

    /**
     * Find assignments by classroom ID
     */
    public function findByClassRoom(string $classRoomId, array $fields = ['*']): Collection
    {
        return ClassSubject::select($fields)
            ->where('class_room_id', $classRoomId)
            ->with([
                'subject:id,name,photo,tagline,teacher_id,topic_id',
                'subject.teacher:id,name,email,photo',
                'subject.topic:id,name,photo'
            ])
            ->latest()
            ->get();
    }

    /**
     * Find assignments by subject ID
     */
    public function findBySubject(string $subjectId, array $fields = ['*']): Collection
    {
        return ClassSubject::select($fields)
            ->where('subject_id', $subjectId)
            ->with(['classRoom:id,name,photo,protocol_id'])
            ->latest()
            ->get();
    }

    /**
     * Find assignments by teacher ID (through subject relationship)
     */
    public function findByTeacher(string $teacherId, array $fields = ['*']): Collection
    {
        return ClassSubject::select($fields)
            ->whereHas('subject', function($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->with([
                'subject:id,name,photo,tagline,teacher_id,topic_id',
                'subject.teacher:id,name,email,photo',
            'classRoom:id,name,photo,protocol_id'
            ])
            ->latest()
            ->get();
    }

    /**
     * Find assignments by topic ID (through subject relationship)
     */
    public function findByTopic(string $topicId, array $fields = ['*']): Collection
    {
        return ClassSubject::select($fields)
            ->whereHas('subject', function($query) use ($topicId) {
                $query->where('topic_id', $topicId);
            })
            ->with([
                'subject:id,name,photo,tagline,teacher_id,topic_id',
                'subject.teacher:id,name,email,photo',
                'subject.topic:id,name,photo',
            'classRoom:id,name,photo,protocol_id'
            ])
            ->latest()
            ->get();
    }

    /**
     * Find assignments by protocol level
     */
    public function findByProtocol(int $protocolId, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return ClassSubject::select($fields)
            ->whereHas('classRoom', function ($query) use ($protocolId) {
                $query->where('protocol_id', $protocolId);
            })
            ->with([
                'subject:id,name,photo,tagline,teacher_id,topic_id',
                'subject.teacher:id,name,email,photo',
                'subject.topic:id,name,photo',
            'classRoom:id,name,photo,protocol_id'
            ])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Check if subject is already assigned to classroom
     */
    public function isSubjectAssigned(string $classRoomId, string $subjectId): bool
    {
        return ClassSubject::where('class_room_id', $classRoomId)
            ->where('subject_id', $subjectId)
            ->exists();
    }

    /**
     * Find specific assignment by classroom and subject
     */
    public function findByClassRoomAndSubject(string $classRoomId, string $subjectId): ?ClassSubject
    {
        return ClassSubject::where('class_room_id', $classRoomId)
            ->where('subject_id', $subjectId)
            ->with([
                'subject:id,name,photo,tagline,teacher_id,topic_id',
                'subject.teacher:id,name,email,photo',
                'subject.topic:id,name,photo',
            'classRoom:id,name,photo,protocol_id'
            ])
            ->first();
    }

    /**
     * Search assignments by subject name or classroom name
     */
    public function searchBySubjectOrClassRoom(string $query, array $fields = ['*'], int $perPage = 10): LengthAwarePaginator
    {
        return ClassSubject::select($fields)
            ->whereHas('subject', function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('tagline', 'LIKE', "%{$query}%");
            })
            ->orWhereHas('classRoom', function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%");
            })
            ->with([
                'subject:id,name,photo,tagline,teacher_id,topic_id',
                'subject.teacher:id,name,email,photo',
                'subject.topic:id,name,photo',
            'classRoom:id,name,photo,protocol_id'
            ])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Search assignments with pagination for frontend modal
     */
    public function searchWithPagination(string $query, array $fields = ['*'], int $page = 1, int $perPage = 10): array
    {
        $queryBuilder = ClassSubject::select($fields)
            ->with([
                'subject:id,name,photo,tagline,teacher_id',
                'subject.teacher:id,name,photo',
            'classRoom:id,name,photo,protocol_id'
            ])
            ->latest();

        if (!empty($query)) {
            $queryBuilder->whereHas('subject', function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('tagline', 'LIKE', "%{$query}%");
            })
            ->orWhereHas('classRoom', function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%");
            });
        }

        $total = $queryBuilder->count();

        $assignments = $queryBuilder->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return [
            'data' => $assignments,
            'total' => $total,
            'current_page' => $page,
            'per_page' => $perPage,
            'has_more' => $total > ($page * $perPage)
        ];
    }

    /**
     * Find multiple assignments by IDs
     */
    public function findManyByIds(array $ids, array $fields = ['*']): Collection
    {
        return ClassSubject::select($fields)
            ->whereIn('id', $ids)
            ->with([
                'subject:id,name,photo,tagline,teacher_id,topic_id',
                'subject.teacher:id,name,email,photo',
                'subject.topic:id,name,photo',
            'classRoom:id,name,photo,protocol_id'
            ])
            ->get();
    }

    /**
     * Get available subjects for classroom (not yet assigned)
     */
    public function getAvailableSubjectsForClassRoom(string $classRoomId): Collection
    {
        return Subject::whereDoesntHave('classSubjects', function($query) use ($classRoomId) {
                $query->where('class_room_id', $classRoomId);
            })
            ->with(['teacher:id,name,email,photo', 'topic:id,name,photo'])
            // Memastikan 'code' dipilih agar tidak bernilai null di JSON Resource
            ->select(['id', 'code', 'name', 'photo', 'content', 'tagline', 'teacher_id', 'topic_id'])
            ->latest('created_at')
            ->get();
    }

    /**
     * Get available classrooms for subject (not yet assigned)
     */
    public function getAvailableClassRoomsForSubject(string $subjectId): Collection
    {
        return ClassRoom::whereDoesntHave('classSubjects', function($query) use ($subjectId) {
                $query->where('subject_id', $subjectId);
            })
            ->select(['id', 'name', 'photo', 'protocol_id'])
            ->latest()
            ->get();
    }

    /**
     * Bulk assign subjects to classroom
     */
    public function bulkAssignToClassRoom(string $classRoomId, array $subjectIds): array
    {
        // Get already assigned subjects
        $alreadyAssigned = ClassSubject::where('class_room_id', $classRoomId)
            ->whereIn('subject_id', $subjectIds)
            ->pluck('subject_id')
            ->toArray();

        // Filter out already assigned ones
        $newSubjectIds = array_diff($subjectIds, $alreadyAssigned);

        $assignments = [];

        foreach ($newSubjectIds as $subjectId) {
            $assignment = $this->create([
                'class_room_id' => $classRoomId,
                'subject_id' => $subjectId,
            ]);
            $assignment->load(['subject', 'classRoom']);
            $assignments[] = $assignment;
        }

        return $assignments;
    }

    /**
     * Bulk assign classrooms to subject
     */
    public function bulkAssignToSubject(string $subjectId, array $classRoomIds): array
    {
        $assignments = [];
        foreach ($classRoomIds as $classRoomId) {
            // Check if already assigned
            if (!$this->isSubjectAssigned($classRoomId, $subjectId)) {
                $assignments[] = $this->create([
                    'class_room_id' => $classRoomId,
                    'subject_id' => $subjectId,
                ]);
            }
        }
        return $assignments;
    }

    /**
     * Get assignment statistics for classroom
     */
    public function getClassRoomStatistics(string $classRoomId): array
    {
        $totalSubjects = ClassSubject::where('class_room_id', $classRoomId)->count();
        $uniqueTeachers = ClassSubject::where('class_room_id', $classRoomId)
            ->join('subjects', 'class_subjects.subject_id', '=', 'subjects.id')
            ->distinct('subjects.teacher_id')
            ->count('subjects.teacher_id');
        $uniqueTopics = ClassSubject::where('class_room_id', $classRoomId)
            ->join('subjects', 'class_subjects.subject_id', '=', 'subjects.id')
            ->distinct('subjects.topic_id')
            ->count('subjects.topic_id');

        return [
            'total_subjects' => $totalSubjects,
            'unique_teachers' => $uniqueTeachers,
            'unique_topics' => $uniqueTopics
        ];
    }

    /**
     * Get assignment statistics for subject
     */
    public function getSubjectStatistics(string $subjectId): array
    {
        $totalClassrooms = ClassSubject::where('subject_id', $subjectId)->count();
        $protocol_idDistribution = ClassSubject::where('subject_id', $subjectId)
            ->join('class_rooms', 'class_subjects.class_room_id', '=', 'class_rooms.id')
            ->groupBy('class_rooms.protocol_id')
            ->selectRaw('class_rooms.protocol_id, COUNT(*) as count')
            ->get()
            ->pluck('count', 'protocol_id')
            ->toArray();

        return [
            'total_classrooms' => $totalClassrooms,
            'protocol_id_distribution' => $protocol_idDistribution
        ];
    }

    /**
     * Get classrooms assigned to teacher via their subjects
     */
    public function getTeacherClassRooms(string $teacherId): Collection
    {
        return ClassRoom::select(['class_rooms.id', 'class_rooms.name', 'class_rooms.photo', 'class_rooms.protocol_id'])
            ->join('class_subjects', 'class_rooms.id', '=', 'class_subjects.class_room_id')
            ->join('subjects', 'class_subjects.subject_id', '=', 'subjects.id')
            ->where('subjects.teacher_id', $teacherId)
            ->distinct()
            ->withCount(['classStudents', 'classSubjects'])
            ->get();
    }
}
