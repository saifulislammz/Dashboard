<?php

declare(strict_types=1);

namespace App\Services\Sessions;

use App\Exceptions\TimeSlotConflictException;
use App\Exceptions\ScheduleConflictException;
use App\Repositories\ClassSessionRepository;
use App\Repositories\SessionMeetingRepository;
use App\Repositories\MeetingJobRepository;
use App\Repositories\ProviderAccountRepository;
use App\Services\Meetings\MeetingService;
use Exception;

/**
 * ClassSessionService
 *
 * Handles creating, editing, cancelling class sessions.
 * Manages bulk session generation workflow:
 *
 * Bulk flow:
 * 1. Validate all input dates
 * 2. Create meeting_generation_jobs record
 * 3. Bulk insert class_sessions rows
 * 4. Bulk insert meeting_generation_job_items
 * 5. Process synchronously (small batches) OR mark for cron
 * 6. Track success/failure per session
 */
class ClassSessionService
{
    private ClassSessionRepository   $sessionRepo;
    private SessionMeetingRepository $meetingRepo;
    private MeetingJobRepository     $jobRepo;
    private MeetingService           $meetingService;
    private ProviderAccountRepository $providerRepo;

    public function __construct(
        ClassSessionRepository    $sessionRepo,
        SessionMeetingRepository  $meetingRepo,
        MeetingJobRepository      $jobRepo,
        MeetingService            $meetingService,
        ProviderAccountRepository $providerRepo
    ) {
        $this->sessionRepo    = $sessionRepo;
        $this->meetingRepo    = $meetingRepo;
        $this->jobRepo        = $jobRepo;
        $this->meetingService = $meetingService;
        $this->providerRepo   = $providerRepo;
    }

    // -------------------------------------------------------
    // SINGLE SESSION
    // -------------------------------------------------------

    public function createSingleSession(array $data, int $adminId): array
    {
        $this->validateSessionData($data);

        // -------------------------------------------------------
        // PRE-FLIGHT: Verify a provider account is available for
        // this exact time slot BEFORE writing anything to the DB.
        // A specific account was manually chosen — trust it.
        // Auto-selection: query must confirm availability.
        // -------------------------------------------------------
        $explicitAccountId = !empty($data['provider_account_id'])
            ? (int) $data['provider_account_id']
            : null;

        // Check teacher / student schedule overlap FIRST (before any DB write)
        $this->checkScheduleConflict(
            (int) $data['classroom_id'],
            $data['session_date'],
            $data['start_time'],
            $data['end_time']
        );

        if (!$explicitAccountId) {
            $availableAccount = $this->providerRepo->findAvailableAccount(
                $data['provider'],
                $data['session_date'],
                $data['start_time'],
                $data['end_time']
            );

            if (!$availableAccount) {
                throw new TimeSlotConflictException(
                    $data['provider'],
                    $data['session_date'],
                    $data['start_time'],
                    $data['end_time']
                );
            }
        }

        // Create job record (single session = job with 1 item)
        $jobId = $this->jobRepo->createJob(
            (int) $data['classroom_id'],
            $data['provider'],
            1,
            $adminId
        );

        $sessionId = $this->sessionRepo->create([
            'classroom_id'        => (int) $data['classroom_id'],
            'session_date'        => $data['session_date'],
            'start_time'          => $data['start_time'],
            'end_time'            => $data['end_time'],
            'timezone'            => $data['timezone'] ?? 'Asia/Dhaka',
            'topic'               => htmlspecialchars(trim($data['topic'] ?? '')),
            'agenda'              => htmlspecialchars(trim($data['agenda'] ?? '')),
            'session_number'      => $data['session_number'] ?? null,
            'provider'            => $data['provider'],
            'provider_account_id' => $explicitAccountId,
            'job_id'              => $jobId,
            'created_by'          => $adminId,
        ]);

        $this->jobRepo->bulkCreateItems($jobId, [$sessionId]);
        $this->jobRepo->markJobStarted($jobId);

        // Synchronous meeting generation for single session
        $result = $this->meetingService->generateForSession($sessionId);

        // Update job counters
        $succeeded = $result->success ? 1 : 0;
        $failed    = $result->success ? 0 : 1;
        $this->jobRepo->updateJobProgress($jobId, 1, $succeeded, $failed);
        $this->jobRepo->markJobCompleted($jobId, $failed);
        $this->jobRepo->markItemSuccess($jobId, $sessionId);

        if (!$result->success) {
            $this->jobRepo->markItemFailed($jobId, $sessionId, $result->errorMessage ?? '');
        }

        return [
            'session_id' => $sessionId,
            'job_id'     => $jobId,
            'meeting_ok' => $result->success,
            'meet_link'  => $result->joinUrl,
            'error'      => $result->errorMessage,
        ];
    }

    // -------------------------------------------------------
    // BULK SESSION GENERATION
    // -------------------------------------------------------

    /**
     * Create multiple sessions at once.
     *
     * $dates = array of ['session_date' => '2026-07-01', 'session_number' => 1]
     *
     * Strategy: Synchronous processing up to 30 sessions.
     * For >30, we create the records and let cron handle generation.
     */
    public function createBulkSessions(array $data, array $dates, int $adminId): array
    {
        if (empty($dates)) {
            throw new Exception('No dates provided for bulk session creation.');
        }

        $this->validateSessionData($data, isBulk: true);

        $totalSessions = count($dates);
        $classroomId   = (int) $data['classroom_id'];
        $provider      = $data['provider'];

        // 1. Create the job record
        $jobId = $this->jobRepo->createJob($classroomId, $provider, $totalSessions, $adminId);

        // 2. Prepare session rows — skip dates with schedule conflicts
        $sessionRows    = [];
        $conflictSkips  = []; // Dates skipped due to teacher/student overlap

        foreach ($dates as $i => $dateInfo) {
            $sessionDate = $dateInfo['session_date'];
            try {
                $this->checkScheduleConflict(
                    $classroomId,
                    $sessionDate,
                    $data['start_time'],
                    $data['end_time']
                );
            } catch (ScheduleConflictException $e) {
                // Skip this date and record the reason
                $conflictSkips[] = [
                    'date'          => $sessionDate,
                    'conflict_type' => $e->getConflictType(),
                    'person_name'   => $e->getPersonName(),
                    'class_name'    => $e->getConflictClassName(),
                ];
                continue;
            }

            $sessionRows[] = [
                'classroom_id'   => $classroomId,
                'session_date'   => $sessionDate,
                'start_time'     => $data['start_time'],
                'end_time'       => $data['end_time'],
                'timezone'       => $data['timezone'] ?? 'Asia/Dhaka',
                'topic'          => htmlspecialchars(trim($data['topic'] ?? '')),
                'agenda'         => htmlspecialchars(trim($data['agenda'] ?? '')),
                'session_number' => $dateInfo['session_number'] ?? ($i + 1),
                'provider'       => $provider,
                'provider_account_id' => !empty($data['provider_account_id']) ? (int)$data['provider_account_id'] : null,
                'job_id'         => $jobId,
                'created_by'     => $adminId,
            ];
        }

        // 3. Bulk insert sessions
        $sessionIds = $this->sessionRepo->bulkCreate($sessionRows);
        $totalCreated = count($sessionIds);
        $skippedDueToDuplicate = $totalSessions - $totalCreated;

        // 4. Bulk insert job items (only for newly created sessions)
        if ($totalCreated > 0) {
            $this->jobRepo->bulkCreateItems($jobId, $sessionIds);
        }
        $this->jobRepo->markJobStarted($jobId);

        // 5. Synchronous generation (for ≤5 sessions)
        //    For >5, leave for cron worker (status = queued)
        $results     = ['total' => $totalSessions, 'succeeded' => 0, 'failed' => 0, 'skipped' => $skippedDueToDuplicate];
        $processSyncLimit = 5;

        if ($totalCreated <= $processSyncLimit && $totalCreated > 0) {
            foreach ($sessionIds as $sessionId) {
                try {
                    $result = $this->meetingService->generateForSession($sessionId);

                    if ($result->success) {
                        $results['succeeded']++;
                        $this->jobRepo->markItemSuccess($jobId, $sessionId);
                    } else {
                        $results['failed']++;
                        $this->jobRepo->markItemFailed($jobId, $sessionId, $result->errorMessage ?? '');
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $this->jobRepo->markItemFailed($jobId, $sessionId, $e->getMessage());
                }
            }

            // Update processed to be $results['succeeded'] + $results['failed'] + $skippedDueToDuplicate
            // So that processed == total
            $this->jobRepo->updateJobProgress($jobId, $totalSessions, $results['succeeded'], $results['failed']);
            $this->jobRepo->markJobCompleted($jobId, $results['failed']);
        } elseif ($totalCreated > $processSyncLimit) {
            // Large batch: let cron handle (job stays in 'processing' state)
            $results['skipped'] = $skippedDueToDuplicate + $totalCreated; // The $totalCreated are pending via Cron
            // Since some might be skipped due to duplicates, we update the processed count immediately for those
            if ($skippedDueToDuplicate > 0) {
                $this->jobRepo->updateJobProgress($jobId, $skippedDueToDuplicate, 0, 0);
            }
        } else {
            // $totalCreated == 0
            $this->jobRepo->updateJobProgress($jobId, $totalSessions, 0, 0);
            $this->jobRepo->markJobCompleted($jobId, 0);
        }

        return array_merge($results, [
            'job_id'         => $jobId,
            'session_ids'    => $sessionIds,
            'conflict_skips' => $conflictSkips ?? [],
        ]);
    }

    // -------------------------------------------------------
    // EDIT SESSION
    // -------------------------------------------------------

    public function updateSession(int $sessionId, array $data): bool
    {
        $this->validateSessionData($data);

        $ok = $this->sessionRepo->update($sessionId, [
            'session_date' => $data['session_date'],
            'start_time'   => $data['start_time'],
            'end_time'     => $data['end_time'],
            'timezone'     => $data['timezone'] ?? 'Asia/Dhaka',
            'topic'        => htmlspecialchars(trim($data['topic'] ?? '')),
            'agenda'       => htmlspecialchars(trim($data['agenda'] ?? '')),
        ]);

        if ($ok) {
            // Also update the meeting on the provider side
            $this->meetingService->updateMeeting($sessionId);
        }

        return $ok;
    }

    // -------------------------------------------------------
    // CANCEL SESSION
    // -------------------------------------------------------

    public function cancelSession(int $sessionId, int $cancelledBy, string $reason = ''): bool
    {
        $this->meetingService->deleteMeeting($sessionId);
        return $this->sessionRepo->cancel($sessionId, $cancelledBy, $reason);
    }

    // -------------------------------------------------------
    // RETRY FAILED
    // -------------------------------------------------------

    public function retryFailedSession(int $sessionId): bool
    {
        $result = $this->meetingService->retryGeneration($sessionId);
        return $result->success;
    }

    // -------------------------------------------------------
    // QUERY
    // -------------------------------------------------------

    public function getPaginatedSessions(int $classroomId, int $page, int $limit, string $status = ''): array
    {
        $offset   = ($page - 1) * $limit;
        $sessions = $this->sessionRepo->getPaginatedByClassroom($classroomId, $limit, $offset, $status);
        $total    = $this->sessionRepo->countByClassroom($classroomId, $status);

        return [
            'data'         => $sessions,
            'total'        => $total,
            'pages'        => (int) ceil($total / $limit),
            'current_page' => $page,
        ];
    }

    // -------------------------------------------------------
    // VALIDATION
    // -------------------------------------------------------

    // -------------------------------------------------------
    // SCHEDULE CONFLICT HELPER
    // -------------------------------------------------------

    /**
     * Throws ScheduleConflictException if the teacher or student
     * of the given classroom already has an overlapping session
     * in another classroom on the same date.
     */
    private function checkScheduleConflict(
        int    $classroomId,
        string $date,
        string $startTime,
        string $endTime,
        ?int   $excludeSessionId = null
    ): void {
        // Teacher conflict
        $tc = $this->sessionRepo->findTeacherConflict($classroomId, $date, $startTime, $endTime, $excludeSessionId);
        if ($tc) {
            throw new ScheduleConflictException(
                'teacher',
                $tc['teacher_name'],
                $date,
                $startTime,
                $endTime,
                $tc['conflict_class_name']
            );
        }

        // Student conflict
        $sc = $this->sessionRepo->findStudentConflict($classroomId, $date, $startTime, $endTime, $excludeSessionId);
        if ($sc) {
            throw new ScheduleConflictException(
                'student',
                $sc['student_name'],
                $date,
                $startTime,
                $endTime,
                $sc['conflict_class_name']
            );
        }
    }

    // -------------------------------------------------------
    // VALIDATION
    // -------------------------------------------------------

    private function validateSessionData(array $data, bool $isBulk = false): void
    {
        if (empty($data['classroom_id'])) {
            throw new Exception('Classroom ID is required.');
        }
        // Bulk sessions carry dates in a separate $dates array, not in $data.
        if (!$isBulk && empty($data['session_date'])) {
            throw new Exception('Session date is required.');
        }
        if (empty($data['start_time']) || empty($data['end_time'])) {
            throw new Exception('Start and end time are required.');
        }
        if (empty($data['provider']) || !in_array($data['provider'], ['google_meet', 'zoom'], true)) {
            throw new Exception('Invalid meeting provider selected.');
        }
        if (strtotime($data['start_time']) >= strtotime($data['end_time'])) {
            throw new Exception('End time must be after start time.');
        }
    }
}
