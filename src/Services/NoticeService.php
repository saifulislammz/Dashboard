<?php

namespace App\Services;

use App\Repositories\NoticeRepository;
use Exception;

class NoticeService
{
    private NoticeRepository $repository;

    public function __construct(NoticeRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createNotice(array $data, int $adminId): bool
    {
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $status = ($data['status'] ?? '') === 'inactive' ? 'inactive' : 'active';
        
        $audienceStudent = !empty($data['audience_student']);
        $audienceTeacher = !empty($data['audience_teacher']);

        if (empty($title) || empty($content)) {
            throw new Exception("Title and Description are required.");
        }

        if (!$audienceStudent && !$audienceTeacher) {
            throw new Exception("Please select at least one target audience.");
        }

        $targetAudience = 'student';
        if ($audienceStudent && $audienceTeacher) {
            $targetAudience = 'both';
        } elseif ($audienceTeacher) {
            $targetAudience = 'teacher';
        }

        $noticeData = [
            'title' => $title,
            'content' => $content,
            'target_audience' => $targetAudience,
            'status' => $status,
            'created_by' => $adminId
        ];

        return $this->repository->create($noticeData);
    }

    public function updateNotice(int $id, array $data): bool
    {
        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $status = ($data['status'] ?? '') === 'inactive' ? 'inactive' : 'active';
        
        $audienceStudent = !empty($data['audience_student']);
        $audienceTeacher = !empty($data['audience_teacher']);

        if (empty($title) || empty($content)) {
            throw new Exception("Title and Description are required.");
        }

        if (!$audienceStudent && !$audienceTeacher) {
            throw new Exception("Please select at least one target audience.");
        }

        $targetAudience = 'student';
        if ($audienceStudent && $audienceTeacher) {
            $targetAudience = 'both';
        } elseif ($audienceTeacher) {
            $targetAudience = 'teacher';
        }

        $noticeData = [
            'title' => $title,
            'content' => $content,
            'target_audience' => $targetAudience,
            'status' => $status
        ];

        return $this->repository->update($id, $noticeData);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $status = $status === 'inactive' ? 'inactive' : 'active';
        return $this->repository->updateStatus($id, $status);
    }

    public function deleteNotice(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function duplicateNotice(int $id, int $adminId): bool
    {
        $notice = $this->repository->findById($id);
        if (!$notice) {
            throw new Exception("Notice not found.");
        }

        $noticeData = [
            'title' => $notice['title'] . ' (Copy)',
            'content' => $notice['content'],
            'target_audience' => $notice['target_audience'],
            'status' => 'inactive',
            'created_by' => $adminId
        ];

        return $this->repository->create($noticeData);
    }

    public function getNoticeDetails(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function getPaginatedNotices(int $page, int $limit, string $search = '', string $sortField = 'created_at', string $sortOrder = 'DESC'): array
    {
        $offset = ($page - 1) * $limit;
        $notices = $this->repository->getPaginatedNotices($limit, $offset, $search, $sortField, $sortOrder);
        $total = $this->repository->countTotalNotices($search);

        return [
            'data' => $notices,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }

    /**
     * Get active notices for a specific role audience (student|teacher).
     * Used by Student & Teacher dashboard controllers.
     */
    public function getActiveNoticesForAudience(string $audience, int $limit = 10): array
    {
        return $this->repository->getActiveNoticesByAudience($audience, $limit);
    }
}
