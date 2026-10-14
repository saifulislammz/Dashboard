<?php
/**
 * CRON SCRIPT: Process Meeting Generation Jobs
 *
 * Production-grade: Lock file prevents overlapping runs.
 * Smart early exit: if no pending jobs, exits with zero DB overhead.
 *
 * Recommended schedule: every 5 minutes (not every minute)
 *   * /5 * * * * php /path/to/Dashboard/cron/process_meeting_jobs.php
 *
 * Description:
 * Processes meeting generation for bulk sessions (>15) that were
 * deferred to the background queue during creation.
 */

declare(strict_types=1);

// -------------------------------------------------------
// 1. CLI-only guard
// -------------------------------------------------------
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// -------------------------------------------------------
// 2. Lock File — prevent overlapping cron executions
//    If a previous run is still processing, skip this run.
// -------------------------------------------------------
$lockFile   = sys_get_temp_dir() . '/meeting_job_processor.lock';
$lockHandle = fopen($lockFile, 'c');

if (!$lockHandle) {
    die("[" . date('Y-m-d H:i:s') . "] ERROR: Cannot open lock file. Aborting.\n");
}

if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    // Another instance is already running — exit silently (no log spam)
    fclose($lockHandle);
    exit(0);
}

// Release lock on exit (normal or crash)
register_shutdown_function(function () use ($lockHandle) {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
});

// -------------------------------------------------------
// 3. Bootstrap
// -------------------------------------------------------
require_once __DIR__ . '/../src/config/meetings_bootstrap.php';

$startTime = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] Meeting job processor started.\n";

// -------------------------------------------------------
// 4. Smart Early Exit — one lightweight COUNT query.
//    If no pending jobs exist, abort immediately.
//    This is the key optimization for low server load.
// -------------------------------------------------------
try {
    $stmtCheck    = $db->query("SELECT COUNT(*) FROM meeting_generation_jobs WHERE status IN ('queued', 'processing') LIMIT 1");
    $pendingCount = (int) $stmtCheck->fetchColumn();

    if ($pendingCount === 0) {
        echo "No pending jobs. Exiting early.\n";
        exit(0);
    }
} catch (\Exception $e) {
    echo "DB check error: " . $e->getMessage() . "\n";
    exit(1);
}

// -------------------------------------------------------
// 5. Process Jobs
// -------------------------------------------------------
try {
    $stmt = $db->query("
        SELECT id, total_sessions, processed, succeeded, failed
        FROM meeting_generation_jobs
        WHERE status IN ('queued', 'processing')
        ORDER BY created_at ASC
    ");
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($jobs as $job) {
        $jobId = (int) $job['id'];

        $jobRepo->markJobStarted($jobId);
        echo "Processing Job #{$jobId}...\n";

        // Process up to 50 pending items per batch per run
        $stmtItems = $db->prepare("
            SELECT session_id FROM meeting_generation_job_items
            WHERE job_id = :job_id AND status = 'pending'
            LIMIT 50
        ");
        $stmtItems->execute(['job_id' => $jobId]);
        $items = $stmtItems->fetchAll(PDO::FETCH_COLUMN);

        if (empty($items)) {
            // No pending items — finalize job
            $stmtCount = $db->prepare("SELECT status, COUNT(*) as cnt FROM meeting_generation_job_items WHERE job_id = :job_id GROUP BY status");
            $stmtCount->execute(['job_id' => $jobId]);
            $counts = ['success' => 0, 'failed' => 0];
            foreach ($stmtCount->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $counts[$row['status']] = (int) $row['cnt'];
            }
            $jobRepo->updateJobProgress($jobId, $counts['success'] + $counts['failed'], $counts['success'], $counts['failed']);
            $jobRepo->markJobCompleted($jobId, $counts['failed']);
            echo "Job #{$jobId} finalized (no pending items left).\n";
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
                    echo "  [OK]  Session #{$sessionId} — link generated.\n";
                } else {
                    $jobRepo->markItemFailed($jobId, $sessionId, $result->errorMessage ?? 'Unknown error');
                    $failedThisBatch++;
                    echo "  [FAIL] Session #{$sessionId} — " . ($result->errorMessage ?? 'Unknown error') . "\n";
                }
            } catch (\Exception $e) {
                $jobRepo->markItemFailed($jobId, $sessionId, $e->getMessage());
                $failedThisBatch++;
                echo "  [ERR]  Session #{$sessionId} — " . $e->getMessage() . "\n";
            }

            // 250ms gap between API calls to respect Google Calendar quota
            usleep(250000);
        }

        // Update job progress counters
        $processed = $job['processed'] + $succeededThisBatch + $failedThisBatch;
        $succeeded  = $job['succeeded'] + $succeededThisBatch;
        $failed     = $job['failed']    + $failedThisBatch;

        $jobRepo->updateJobProgress($jobId, $processed, $succeeded, $failed);

        if ($processed >= $job['total_sessions']) {
            $jobRepo->markJobCompleted($jobId, $failed);
            echo "Job #{$jobId} completed. Success: {$succeeded}, Failed: {$failed}.\n";
        } else {
            echo "Job #{$jobId} batch done (OK:{$succeededThisBatch} FAIL:{$failedThisBatch}). Remaining processed in next run.\n";
        }
    }

} catch (\Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

$elapsed = round(microtime(true) - $startTime, 2);
echo "[" . date('Y-m-d H:i:s') . "] Processor finished in {$elapsed}s.\n";
exit(0);

