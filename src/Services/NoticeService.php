<?php

declare(strict_types=1);

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
        $title   = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $status  = ($data['status'] ?? '') === 'inactive' ? 'inactive' : 'active';
        $targetType = ($data['target_type'] ?? '') === 'all' ? 'all' : 'targeted';

        if (empty($title) || empty($content)) {
            throw new Exception("Title and Description are required.");
        }

        $targetRows = [];
        $targetAudience = 'both';

        if ($targetType === 'targeted') {
            $audienceStudent = !empty($data['audience_student']);
            $audienceTeacher = !empty($data['audience_teacher']);
            $targetClassroomIds = array_values(array_unique(array_filter(array_map('intval', (array) ($data['target_classroom_ids'] ?? [])))));
            $targetUserIds      = array_values(array_unique(array_filter(array_map('intval', (array) ($data['target_user_ids'] ?? [])))));

            if (!$audienceStudent && !$audienceTeacher && empty($targetClassroomIds) && empty($targetUserIds)) {
                throw new Exception("Please select at least one role, classroom, or user for targeted notice.");
            }

            if ($audienceStudent && $audienceTeacher) {
                $targetAudience = 'both';
            } elseif ($audienceTeacher) {
                $targetAudience = 'teacher';
            } elseif ($audienceStudent) {
                $targetAudience = 'student';
            } else {
                $targetAudience = 'both';
            }

            // Build relational targets for notice_targets
            if ($audienceStudent) {
                $targetRows[] = ['type' => 'role', 'id' => 4]; // ROLE_STUDENT
            }
            if ($audienceTeacher) {
                $targetRows[] = ['type' => 'role', 'id' => 2]; // ROLE_TEACHER
            }
            foreach ($targetClassroomIds as $cid) {
                $targetRows[] = ['type' => 'classroom', 'id' => $cid];
            }
            foreach ($targetUserIds as $uid) {
                $targetRows[] = ['type' => 'user', 'id' => $uid];
            }
        }

        $noticeData = [
            'title'           => $title,
            'content'         => $content,
            'target_audience' => $targetAudience,
            'target_type'     => $targetType,
            'status'          => $status,
            'created_by'      => $adminId
        ];

        $noticeId = $this->repository->create($noticeData);

        if ($noticeId) {
            $this->repository->syncTargets((int) $noticeId, $targetRows);

            if (!empty($data['attachments'])) {
                $this->handleAttachments((int) $noticeId, $data['attachments']);
            }
        }

        return (bool) $noticeId;
    }

    private function handleAttachments(int $noticeId, array $files): void
    {
        if (empty($files['name']) || !is_array($files['name'])) {
            return;
        }

        $allowedExtensions = ['txt', 'pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        $uploadDir = __DIR__ . '/../../storage/uploads/notices/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileCount = count($files['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            if (isset($files['error'][$i]) && $files['error'][$i] === UPLOAD_ERR_OK) {
                $originalName = basename($files['name'][$i]);
                $fileSize = $files['size'][$i];
                $tmpName = $files['tmp_name'][$i];
                $fileType = $files['type'][$i];

                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if (!in_array($ext, $allowedExtensions, true)) {
                    throw new Exception("Invalid file type: $originalName. Allowed: " . implode(', ', $allowedExtensions));
                }

                if ($fileSize > $maxFileSize) {
                    throw new Exception("File too large: $originalName. Maximum size is 10MB.");
                }

                $uniqueName = uniqid('notice_', true) . '.' . $ext;
                $destination = $uploadDir . $uniqueName;

                if (move_uploaded_file($tmpName, $destination)) {
                    $this->repository->createAttachment($noticeId, [
                        'file_name' => $originalName,
                        'file_path' => $uniqueName,
                        'file_type' => $fileType,
                        'file_size' => $fileSize
                    ]);
                }
            }
        }
    }

    public function updateNotice(int $id, array $data): bool
    {
        $title   = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $status  = ($data['status'] ?? '') === 'inactive' ? 'inactive' : 'active';
        $targetType = ($data['target_type'] ?? '') === 'all' ? 'all' : 'targeted';

        if (empty($title) || empty($content)) {
            throw new Exception("Title and Description are required.");
        }

        $targetRows = [];
        $targetAudience = 'both';

        if ($targetType === 'targeted') {
            $audienceStudent = !empty($data['audience_student']);
            $audienceTeacher = !empty($data['audience_teacher']);
            $targetClassroomIds = array_values(array_unique(array_filter(array_map('intval', (array) ($data['target_classroom_ids'] ?? [])))));
            $targetUserIds      = array_values(array_unique(array_filter(array_map('intval', (array) ($data['target_user_ids'] ?? [])))));

            if (!$audienceStudent && !$audienceTeacher && empty($targetClassroomIds) && empty($targetUserIds)) {
                throw new Exception("Please select at least one role, classroom, or user for targeted notice.");
            }

            if ($audienceStudent && $audienceTeacher) {
                $targetAudience = 'both';
            } elseif ($audienceTeacher) {
                $targetAudience = 'teacher';
            } elseif ($audienceStudent) {
                $targetAudience = 'student';
            } else {
                $targetAudience = 'both';
            }

            if ($audienceStudent) {
                $targetRows[] = ['type' => 'role', 'id' => 4];
            }
            if ($audienceTeacher) {
                $targetRows[] = ['type' => 'role', 'id' => 2];
            }
            foreach ($targetClassroomIds as $cid) {
                $targetRows[] = ['type' => 'classroom', 'id' => $cid];
            }
            foreach ($targetUserIds as $uid) {
                $targetRows[] = ['type' => 'user', 'id' => $uid];
            }
        }

        $noticeData = [
            'title'           => $title,
            'content'         => $content,
            'target_audience' => $targetAudience,
            'target_type'     => $targetType,
            'status'          => $status
        ];

        $updated = $this->repository->update($id, $noticeData);
        if ($updated) {
            $this->repository->syncTargets($id, $targetRows);

            if (!empty($data['attachments'])) {
                $this->handleAttachments($id, $data['attachments']);
            }
        }

        return $updated;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $status = $status === 'inactive' ? 'inactive' : 'active';
        return $this->repository->updateStatus($id, $status);
    }

    public function deleteNotice(int $id): bool
    {
        $attachments = $this->repository->getAttachmentsByNoticeId($id);
        $uploadDir = __DIR__ . '/../../storage/uploads/notices/';
        
        foreach ($attachments as $attachment) {
            $filePath = $uploadDir . $attachment['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        return $this->repository->delete($id);
    }

    public function duplicateNotice(int $id, int $adminId): bool
    {
        $notice = $this->repository->findById($id);
        if (!$notice) {
            throw new Exception("Notice not found.");
        }

        $noticeData = [
            'title'           => $notice['title'] . ' (Copy)',
            'content'         => $notice['content'],
            'target_audience' => $notice['target_audience'],
            'target_type'     => $notice['target_type'] ?? 'targeted',
            'status'          => 'inactive',
            'created_by'      => $adminId
        ];

        $newNoticeId = $this->repository->create($noticeData);
        if ($newNoticeId) {
            $this->repository->duplicateTargets($id, (int) $newNoticeId);
            return true;
        }

        return false;
    }

    public function getNoticeDetails(int $id): ?array
    {
        $notice = $this->repository->findById($id);
        if ($notice) {
            $notice['attachments'] = $this->repository->getAttachmentsByNoticeId($id);
            $notice['targets']     = $this->repository->getTargetsByNoticeId($id);
        }
        return $notice;
    }

    public function getPaginatedNotices(int $page, int $limit, string $search = '', string $sortField = 'created_at', string $sortOrder = 'DESC'): array
    {
        $offset = ($page - 1) * $limit;
        $notices = $this->repository->getPaginatedNotices($limit, $offset, $search, $sortField, $sortOrder);
        $total = $this->repository->countTotalNotices($search);

        return [
            'data'         => $notices,
            'total'        => $total,
            'pages'        => ceil($total / $limit),
            'current_page' => $page
        ];
    }

    /**
     * Get active notices for a specific role audience (student|teacher).
     * Preserved for backward compatibility.
     */
    public function getActiveNoticesForAudience(string $audience, int $limit = 10): array
    {
        return $this->repository->getActiveNoticesByAudience($audience, $limit);
    }

    /**
     * Master Targeted Notice Fetcher for User Dashboards.
     * Evaluates public broadcast, role audience, classroom memberships, and personal user targeting.
     */
    public function getNoticesForUser(int $userId, string $role, ?array $classroomIds = null, int $limit = 10): array
    {
        if ($classroomIds === null) {
            $classroomIds = $this->repository->getUserClassroomIds($userId, $role);
        }
        return $this->repository->getNoticesForUser($userId, $role, $classroomIds, $limit);
    }

    /**
     * IDOR Access Guard for Attachment Downloader.
     */
    public function canUserAccessNotice(int $userId, int $noticeId, string $role, ?array $classroomIds = null): bool
    {
        if ($classroomIds === null) {
            $classroomIds = $this->repository->getUserClassroomIds($userId, $role);
        }
        return $this->repository->canUserAccessNotice($userId, $noticeId, $role, $classroomIds);
    }
}
