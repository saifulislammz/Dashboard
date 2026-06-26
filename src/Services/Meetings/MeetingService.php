<?php

declare(strict_types=1);

namespace App\Services\Meetings;

use App\DTOs\SessionDTO;
use App\DTOs\MeetingResultDTO;
use App\Repositories\ClassSessionRepository;
use App\Repositories\SessionMeetingRepository;
use App\Repositories\ProviderAccountRepository;

/**
 * MeetingService
 *
 * Orchestrates meeting creation, update, and deletion
 * using the provider factory. Provider-agnostic core.
 */
class MeetingService
{
    private MeetingProviderFactory  $factory;
    private ClassSessionRepository  $sessionRepo;
    private SessionMeetingRepository $meetingRepo;
    private ProviderAccountRepository $providerRepo;

    public function __construct(
        MeetingProviderFactory   $factory,
        ClassSessionRepository   $sessionRepo,
        SessionMeetingRepository $meetingRepo,
        ProviderAccountRepository $providerRepo
    ) {
        $this->factory      = $factory;
        $this->sessionRepo  = $sessionRepo;
        $this->meetingRepo  = $meetingRepo;
        $this->providerRepo = $providerRepo;
    }

    /**
     * Generate a meeting for a single session.
     * Creates/updates session_meetings record with result.
     */
    public function generateForSession(int $sessionId): MeetingResultDTO
    {
        $sessionData = $this->sessionRepo->findById($sessionId);

        if (!$sessionData) {
            return MeetingResultDTO::failure('unknown', "Session #{$sessionId} not found.");
        }

        // Build DTO from session row
        $dto = new SessionDTO(
            sessionId:     $sessionData['id'],
            classroomId:   $sessionData['classroom_id'],
            classroomName: $sessionData['class_name'],
            classTitle:    $sessionData['class_title'],
            topic:         $sessionData['topic'] ?? '',
            agenda:        $sessionData['agenda'] ?? '',
            sessionDate:   $sessionData['session_date'],
            startTime:     $sessionData['start_time'],
            endTime:       $sessionData['end_time'],
            timezone:      $sessionData['timezone'],
            provider:      $sessionData['provider'],
            teacherEmail:  $sessionData['teacher_email'],
            teacherName:   $sessionData['teacher_name'],
            studentEmail:  $sessionData['student_email'] ?? null,
            studentName:   $sessionData['student_name'] ?? null,
        );

        // Ensure pending meeting record exists
        $this->meetingRepo->createPending($sessionId, $dto->provider);

        try {
            $provider = $this->factory->make($dto->provider);
            $result   = $provider->createMeeting($dto);
        } catch (\Exception $e) {
            $result = MeetingResultDTO::failure($dto->provider, $e->getMessage());
        }

        // Persist result
        if ($result->success) {
            $this->meetingRepo->saveSuccess($sessionId, [
                'provider_meeting_id' => $result->providerMeetingId,
                'provider_event_id'   => $result->providerMeetingId,
                'join_url'            => $result->joinUrl,
                'start_url'           => $result->startUrl,
                'meet_link'           => $result->meetLink,
                'passcode'            => $result->passcode,
                'raw_response'        => $result->rawResponse,
            ]);
            $this->sessionRepo->updateStatus($sessionId, 'active');
        } else {
            $this->meetingRepo->saveFailed($sessionId, $result->errorMessage ?? 'Unknown error');
            $this->sessionRepo->updateStatus($sessionId, 'failed');
        }

        return $result;
    }

    /**
     * Update the provider meeting when session time/topic is edited.
     */
    public function updateMeeting(int $sessionId): bool
    {
        $sessionData = $this->sessionRepo->findById($sessionId);
        $meeting     = $this->meetingRepo->findBySessionId($sessionId);

        if (!$sessionData || !$meeting || $meeting['generation_status'] !== 'success') {
            return false;
        }

        $dto = new SessionDTO(
            sessionId:     $sessionData['id'],
            classroomId:   $sessionData['classroom_id'],
            classroomName: $sessionData['class_name'],
            classTitle:    $sessionData['class_title'],
            topic:         $sessionData['topic'] ?? '',
            agenda:        $sessionData['agenda'] ?? '',
            sessionDate:   $sessionData['session_date'],
            startTime:     $sessionData['start_time'],
            endTime:       $sessionData['end_time'],
            timezone:      $sessionData['timezone'],
            provider:      $sessionData['provider'],
            teacherEmail:  $sessionData['teacher_email'],
            teacherName:   $sessionData['teacher_name'],
            studentEmail:  $sessionData['student_email'] ?? null,
            studentName:   $sessionData['student_name'] ?? null,
        );

        try {
            $provider = $this->factory->make($dto->provider);
            return $provider->updateMeeting($meeting['provider_meeting_id'], $dto);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Cancel/delete provider meeting when session is cancelled.
     */
    public function deleteMeeting(int $sessionId): bool
    {
        $sessionData = $this->sessionRepo->findById($sessionId);
        $meeting     = $this->meetingRepo->findBySessionId($sessionId);

        if (!$meeting || !$meeting['provider_meeting_id']) {
            return true; // Nothing to delete on provider side
        }

        try {
            $provider = $this->factory->make($sessionData['provider']);
            $result   = $provider->deleteMeeting($meeting['provider_meeting_id']);
            if ($result) {
                $this->meetingRepo->markCancelled($sessionId);
            }
            return $result;
        } catch (\Exception $e) {
            // Even if provider delete fails, mark as cancelled in our system
            $this->meetingRepo->markCancelled($sessionId);
            return false;
        }
    }

    /**
     * Retry a failed meeting generation.
     */
    public function retryGeneration(int $sessionId): MeetingResultDTO
    {
        $this->meetingRepo->resetForRetry($sessionId);
        $this->sessionRepo->updateStatus($sessionId, 'scheduled');
        return $this->generateForSession($sessionId);
    }
}
