<?php
/**
 * CRON SCRIPT: Process Meeting Generation Jobs
 * 
 * Run this via cron every minute:
 * * * * * * php /path/to/Dashboard/cron/process_meeting_jobs.php
 * 
 * Description:
 * Processes meeting generation for bulk sessions (jobs with status='processing' or 'queued').
 */

declare(strict_types=1);

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.");
}

require_once __DIR__ . '/../src/config/meetings_bootstrap.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting meeting job processor...\n";

// Use the existing jobRepo and meetingService from bootstrap
try {
    // Find jobs that are queued or processing
    $stmt = $db->query("
        SELECT id, total_sessions, processed, succeeded, failed 
        FROM meeting_generation_jobs 
        WHERE status IN ('queued', 'processing') 
        ORDER BY created_at ASC
    ");
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($jobs)) {
        echo "No pending jobs found.\n";
        exit(0);
    }

    foreach ($jobs as $job) {
        $jobId = (int) $job['id'];
        
        // Mark as processing if queued
        $jobRepo->markJobStarted($jobId);
        
        echo "Processing Job #{$jobId}...\n";

        // Get up to 50 pending items for this job to process in this batch
        $stmtItems = $db->prepare("
            SELECT session_id FROM meeting_generation_job_items 
            WHERE job_id = :job_id AND status = 'pending' 
            LIMIT 50
        ");
        $stmtItems->execute(['job_id' => $jobId]);
        $items = $stmtItems->fetchAll(PDO::FETCH_COLUMN);

        if (empty($items)) {
            // No pending items left, but job wasn't marked completed?
            // Ensure counters are accurate
            $stmtCount = $db->prepare("SELECT status, COUNT(*) as cnt FROM meeting_generation_job_items WHERE job_id = :job_id GROUP BY status");
            $stmtCount->execute(['job_id' => $jobId]);
            $counts = ['success' => 0, 'failed' => 0];
            foreach ($stmtCount->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $counts[$row['status']] = (int)$row['cnt'];
            }
            
            $jobRepo->updateJobProgress($jobId, $counts['success'] + $counts['failed'], $counts['success'], $counts['failed']);
            $jobRepo->markJobCompleted($jobId, $counts['failed']);
            echo "Job #{$jobId} marked as completed (no pending items).\n";
            continue;
        }

        $succeededThisBatch = 0;
        $failedThisBatch    = 0;

        foreach ($items as $sessionId) {
            $sessionId = (int) $sessionId;
            
            try {
                $result = $meetingService->generateForSession($sessionId);
                
                if ($result->success) {
                    $jobRepo->markItemSuccess($jobId, $sessionId);
                    $succeededThisBatch++;
                } else {
                    $jobRepo->markItemFailed($jobId, $sessionId, $result->errorMessage ?? 'Unknown error');
                    $failedThisBatch++;
                }
            } catch (\Exception $e) {
                $jobRepo->markItemFailed($jobId, $sessionId, $e->getMessage());
                $failedThisBatch++;
            }
            
            // Small sleep to avoid hitting API rate limits too hard (e.g. Google Calendar quota)
            usleep(250000); // 250ms
        }

        // Update job stats
        $processed = $job['processed'] + $succeededThisBatch + $failedThisBatch;
        $succeeded = $job['succeeded'] + $succeededThisBatch;
        $failed    = $job['failed'] + $failedThisBatch;
        
        $jobRepo->updateJobProgress($jobId, $processed, $succeeded, $failed);

        // Check if job is fully complete
        if ($processed >= $job['total_sessions']) {
            $jobRepo->markJobCompleted($jobId, $failed);
            echo "Job #{$jobId} finished processing.\n";
        } else {
            echo "Job #{$jobId} processed batch (Success: {$succeededThisBatch}, Failed: {$failedThisBatch}). Continuing in next cron run...\n";
        }
    }

} catch (\Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Processor finished.\n";
exit(0);
