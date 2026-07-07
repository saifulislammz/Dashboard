<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\QuizRepository;

/**
 * QuizService — Arabic Quiz System Business Logic Layer
 *
 * Controller শুধু input নেয় এবং output পাঠায়।
 * সব validation, business rule, file handling এখানে।
 */
class QuizService
{
    // অনুমোদিত ভয়েস MIME types
    private const ALLOWED_AUDIO_MIMES = [
        'audio/webm',
        'audio/ogg',
        'audio/wav',
        'audio/mp4',
        'audio/mpeg',
        'audio/x-m4a',
    ];

    // সর্বোচ্চ ভয়েস ফাইল সাইজ (10MB)
    private const MAX_VOICE_BYTES = 10 * 1024 * 1024;

    // ভয়েস স্টোরেজ base path
    private const VOICE_STORAGE_BASE = __DIR__ . '/../../storage/quiz_voices';

    // প্রশ্নের ধরন — শুধু এই তিনটি valid
    private const VALID_QUESTION_TYPES = ['letter', 'pronunciation', 'voice'];

    public function __construct(private QuizRepository $repo) {}

    // ================================================================
    // ADMIN: QUIZ MANAGEMENT
    // ================================================================

    /**
     * নতুন কুইজ তৈরি (validation + DB save, transaction-এ)
     *
     * @throws \InvalidArgumentException validation ব্যর্থ হলে
     */
    public function createQuiz(array $input, int $adminId): int
    {
        $validated = $this->validateQuizInput($input);

        // Transaction: quiz + questions + options একসাথে
        $this->repo->beginTransaction();
        try {
            $quizId = $this->repo->createQuiz([
                'title'      => $validated['title'],
                'description' => $validated['description'],
                'status'     => $validated['status'],
                'created_by' => $adminId,
            ]);

            $this->saveQuestionsWithOptions($quizId, $validated['questions']);

            $this->repo->commit();
            return $quizId;
        } catch (\Throwable $e) {
            $this->repo->rollback();
            throw $e;
        }
    }

    /**
     * কুইজ আপডেট (সব প্রশ্ন replace করে)
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException কুইজ না পাওয়া গেলে
     */
    public function updateQuiz(int $quizId, array $input): void
    {
        $quiz = $this->repo->findQuizById($quizId);
        if (!$quiz) {
            throw new \RuntimeException('কুইজ পাওয়া যায়নি।');
        }

        $validated = $this->validateQuizInput($input);

        $this->repo->beginTransaction();
        try {
            $this->repo->updateQuiz($quizId, [
                'title'       => $validated['title'],
                'description' => $validated['description'],
                'status'      => $validated['status'],
            ]);

            // পুরানো সব প্রশ্ন মুছে নতুন সেভ
            $this->repo->deleteQuestionsByQuizId($quizId);
            $this->saveQuestionsWithOptions($quizId, $validated['questions']);

            $this->repo->commit();
        } catch (\Throwable $e) {
            $this->repo->rollback();
            throw $e;
        }
    }

    /**
     * কুইজ সফট ডিলিট
     *
     * @throws \RuntimeException
     */
    public function deleteQuiz(int $quizId): void
    {
        $quiz = $this->repo->findQuizById($quizId);
        if (!$quiz) {
            throw new \RuntimeException('কুইজ পাওয়া যায়নি।');
        }
        $this->repo->softDeleteQuiz($quizId);
    }

    /**
     * Admin-এর জন্য কুইজ তালিকা (paginated)
     */
    public function getQuizList(array $filters, int $page, int $perPage = 15): array
    {
        $quizzes = $this->repo->findAllQuizzes($filters, $page, $perPage);
        $total   = $this->repo->countAllQuizzes($filters);

        return [
            'quizzes'      => $quizzes,
            'total'        => $total,
            'current_page' => $page,
            'per_page'     => $perPage,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }

    /**
     * কুইজ edit ফর্মের জন্য — questions + options সহ
     */
    public function getQuizForEdit(int $quizId): array
    {
        $quiz = $this->repo->findQuizById($quizId);
        if (!$quiz) {
            throw new \RuntimeException('কুইজ পাওয়া যায়নি।');
        }

        $questions = $this->repo->getQuestionsByQuizId($quizId);
        foreach ($questions as &$q) {
            $q['options'] = $q['type'] !== 'voice'
                ? $this->repo->getOptionsForQuestion((int) $q['id'])
                : [];
        }
        unset($q);

        $quiz['questions'] = $questions;
        return $quiz;
    }

    // ================================================================
    // ADMIN: REPORT & STATS
    // ================================================================

    /**
     * Admin report page-এর সব ডেটা
     */
    public function getAdminReport(int $quizId, int $page, int $perPage = 20): array
    {
        $quiz = $this->repo->findQuizById($quizId);
        if (!$quiz) {
            throw new \RuntimeException('কুইজ পাওয়া যায়নি।');
        }

        $stats    = $this->repo->getDashboardStats($quizId);
        $attempts = $this->repo->getAttemptList($quizId, $page, $perPage);
        $total    = $this->repo->countAttempts($quizId);

        // Score percentage প্রতিটি attempt-এ যোগ করা
        foreach ($attempts as &$a) {
            $a['score_pct'] = $a['total_questions'] > 0
                ? round(($a['correct_answers'] / $a['total_questions']) * 100, 1)
                : 0;
        }
        unset($a);

        return [
            'quiz'         => $quiz,
            'stats'        => $stats,
            'attempts'     => $attempts,
            'total'        => $total,
            'current_page' => $page,
            'per_page'     => $perPage,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }

    /**
     * ভয়েস রিভিউ নোট সেভ
     */
    public function saveVoiceReviewNote(int $attemptId, string $note): void
    {
        $note = trim($note);
        if (mb_strlen($note) > 2000) {
            throw new \InvalidArgumentException('নোট সর্বোচ্চ ২০০০ অক্ষর হতে পারবে।');
        }
        $this->repo->saveVoiceReviewNote($attemptId, $note);
    }

    /**
     * Sidebar badge count
     */
    public function getUnreviewedVoiceCount(): int
    {
        return $this->repo->getGlobalUnreviewedVoiceCount();
    }

    // ================================================================
    // PUBLIC: GUEST FORM + QUIZ START
    // ================================================================

    /**
     * Guest form validation
     *
     * @return array validated & sanitized data
     * @throws \InvalidArgumentException
     */
    public function validateParticipant(array $input): array
    {
        $errors = [];

        // নাম
        $name = trim($input['participant_name'] ?? '');
        if ($name === '') {
            $errors[] = 'নাম আবশ্যক।';
        } elseif (mb_strlen($name) > 150) {
            $errors[] = 'নাম সর্বোচ্চ ১৫০ অক্ষর।';
        }

        // জেন্ডার — whitelist
        $gender = trim($input['gender'] ?? '');
        if (!in_array($gender, ['male', 'female'], true)) {
            $errors[] = 'জেন্ডার সঠিকভাবে নির্বাচন করুন।';
        }

        // WhatsApp নম্বর
        $whatsapp = trim($input['whatsapp_number'] ?? '');
        if ($whatsapp === '') {
            $errors[] = 'WhatsApp নম্বর আবশ্যক।';
        } elseif (!preg_match('/^\+?[0-9]{10,15}$/', $whatsapp)) {
            $errors[] = 'সঠিক WhatsApp নম্বর দিন (১০-১৫ সংখ্যা)।';
        }

        // Email — optional
        $email = trim($input['email'] ?? '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'সঠিক Email ঠিকানা দিন।';
        }
        if ($email !== '' && mb_strlen($email) > 150) {
            $errors[] = 'Email সর্বোচ্চ ১৫০ অক্ষর।';
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        return [
            'participant_name' => $name,
            'gender'           => $gender,
            'whatsapp_number'  => $whatsapp,
            'email'            => $email !== '' ? $email : null,
        ];
    }

    /**
     * কুইজ শুরু করা:
     * - চিরতরে block? → error
     * - আগের in_progress আছে? → রিসেট করে নতুন token দিয়ে শুরু
     * - নতুন participant → নতুন attempt তৈরি
     *
     * @return array ['token' => string, 'attempt_id' => int]
     * @throws \RuntimeException
     */
    public function startOrResumeAttempt(int $quizId, array $participant): array
    {
        // চিরতরে block চেক
        if ($this->repo->isPhoneBlockedForQuiz($quizId, $participant['whatsapp_number'])) {
            throw new \RuntimeException(
                'এই WhatsApp নম্বর দিয়ে আগেই কুইজ সম্পন্ন হয়েছে। আর অংশগ্রহণ সম্ভব নয়।'
            );
        }

        // প্রশ্নের সংখ্যা
        $questions   = $this->repo->getQuestionsByQuizId($quizId);
        $totalCount  = count($questions);
        $newToken    = bin2hex(random_bytes(32));

        // আগের in_progress attempt চেক
        $existing = $this->repo->findInProgressAttempt($quizId, $participant['whatsapp_number']);

        if ($existing) {
            // রিসেট করো — নতুন token দিয়ে পুরো নতুন করে শুরু
            $this->repo->deleteAnswersForAttempt((int) $existing['id']);
            $this->repo->resetAttempt((int) $existing['id'], $newToken, $totalCount);
            $attemptId = (int) $existing['id'];
        } else {
            // নতুন attempt তৈরি
            $attemptId = $this->repo->createAttempt([
                'quiz_id'          => $quizId,
                'participant_name' => $participant['participant_name'],
                'gender'           => $participant['gender'],
                'whatsapp_number'  => $participant['whatsapp_number'],
                'email'            => $participant['email'],
                'total_questions'  => $totalCount,
                'session_token'    => $newToken,
            ]);
        }

        return [
            'token'      => $newToken,
            'attempt_id' => $attemptId,
        ];
    }

    // ================================================================
    // PUBLIC: QUIZ PLAY
    // ================================================================

    /**
     * Quiz player-এর জন্য প্রশ্ন লোড করা
     * ক্রম: letter → pronunciation → voice
     * Options: প্রতিটি request-এ shuffle
     */
    public function getQuestionsForPlayer(int $quizId): array
    {
        $questions = $this->repo->getQuestionsByQuizId($quizId);

        // ধরন অনুযায়ী ক্রম নিশ্চিত করা
        $typeOrder = ['letter' => 0, 'pronunciation' => 1, 'voice' => 2];
        usort($questions, fn($a, $b) =>
            ($typeOrder[$a['type']] ?? 99) <=> ($typeOrder[$b['type']] ?? 99)
        );

        foreach ($questions as &$q) {
            if ($q['type'] !== 'voice') {
                $options = $this->repo->getOptionsForQuestion((int) $q['id']);
                shuffle($options); // প্রতিবার র‍্যান্ডম ক্রম
                $q['options'] = $options;
            } else {
                $q['options'] = [];
            }
        }
        unset($q);

        return $questions;
    }

    /**
     * MCQ উত্তর যাচাই ও সেভ করা
     *
     * @return array ['correct' => bool, 'correct_option_id' => int]
     * @throws \RuntimeException attempt বা question invalid হলে
     */
    public function submitMCQAnswer(
        int $attemptId,
        int $questionId,
        int $selectedOptionId,
        int $quizId
    ): array {
        // সঠিক উত্তর DB থেকে নেওয়া (client trust করা হবে না)
        $correctOption = $this->repo->getCorrectOptionForQuestion($questionId);
        if (!$correctOption) {
            throw new \RuntimeException('প্রশ্নটি পাওয়া যায়নি।');
        }

        $isCorrect = ((int) $correctOption['id'] === $selectedOptionId);

        $this->repo->recordAnswer($attemptId, $questionId, $selectedOptionId, $isCorrect);

        return [
            'correct'           => $isCorrect,
            'correct_option_id' => (int) $correctOption['id'],
        ];
    }

    /**
     * MCQ শেষ — স্কোর calculate করে attempt finalize
     */
    public function finalizeAttempt(int $attemptId): array
    {
        // DB থেকে সঠিক উত্তরের সংখ্যা গণনা
        $attempt = $this->repo->findAttemptById($attemptId);
        if (!$attempt) {
            throw new \RuntimeException('Attempt পাওয়া যায়নি।');
        }

        $stats = $this->repo->getAnswerStats($attemptId);
        $correct = (int) ($stats['correct_count'] ?? 0);
        $total   = (int) ($attempt['total_questions'] ?? 0);

        $this->repo->finalizeAttempt($attemptId, $correct, $total);

        return [
            'correct_answers' => $correct,
            'total_questions' => $total,
            'score_pct'       => $total > 0 ? round(($correct / $total) * 100, 1) : 0,
        ];
    }

    /**
     * ভয়েস রেকর্ডিং আপলোড, যাচাই, সেভ
     *
     * @throws \InvalidArgumentException ফাইল invalid হলে
     * @throws \RuntimeException save ব্যর্থ হলে
     */
    public function uploadVoiceRecording(int $attemptId, array $file): string
    {
        // ফাইল upload error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('ফাইল আপলোড ব্যর্থ হয়েছে। আবার চেষ্টা করুন।');
        }

        // সাইজ চেক
        if ($file['size'] > self::MAX_VOICE_BYTES) {
            throw new \InvalidArgumentException('ভয়েস ফাইল সর্বোচ্চ ১০ MB হতে পারবে।');
        }

        // MIME যাচাই (finfo দিয়ে — client header trust করা হয় না)
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, self::ALLOWED_AUDIO_MIMES, true)) {
            throw new \InvalidArgumentException('অনুমোদিত audio format নয়।');
        }

        // নিরাপদ random filename
        $ext      = $this->getExtensionFromMime($mimeType);
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;

        // মাসভিত্তিক directory তৈরি
        $dirPath = self::VOICE_STORAGE_BASE . '/' . date('Y/m');
        if (!is_dir($dirPath) && !mkdir($dirPath, 0755, true)) {
            throw new \RuntimeException('Storage directory তৈরি করা সম্ভব হয়নি।');
        }

        $fullPath = $dirPath . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new \RuntimeException('ফাইল সেভ করা সম্ভব হয়নি।');
        }

        // DB-তে relative path সেভ (storage root থেকে)
        $relativePath = 'quiz_voices/' . date('Y/m') . '/' . $filename;
        $saved = $this->repo->saveVoiceSubmission($attemptId, $relativePath);

        if (!$saved) {
            // DB save ব্যর্থ হলে ফাইল মুছে ফেলো
            @unlink($fullPath);
            throw new \RuntimeException('তথ্য সংরক্ষণ করা সম্ভব হয়নি।');
        }

        return $relativePath;
    }

    /**
     * Result page-এর জন্য attempt + per-type breakdown
     */
    public function getResultData(string $token): array
    {
        $attempt = $this->repo->findAttemptByToken($token);
        if (!$attempt) {
            throw new \RuntimeException('রেজাল্ট পাওয়া যায়নি।');
        }

        // ধরন অনুযায়ী স্কোর breakdown
        $breakdown = $this->repo->getAnswerBreakdown((int) $attempt['id']);

        return [
            'attempt'   => $attempt,
            'breakdown' => $breakdown,
        ];
    }

    // ================================================================
    // PRIVATE HELPERS
    // ================================================================

    /**
     * Quiz input validation (create & update-এ ব্যবহার হয়)
     */
    private function validateQuizInput(array $input): array
    {
        $errors = [];

        $title = trim($input['title'] ?? '');
        if ($title === '') {
            $errors[] = 'শিরোনাম আবশ্যক।';
        } elseif (mb_strlen($title) > 255) {
            $errors[] = 'শিরোনাম সর্বোচ্চ ২৫৫ অক্ষর।';
        }

        $status = $input['status'] ?? 'active';
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $description = trim($input['description'] ?? '');
        if (mb_strlen($description) > 5000) {
            $errors[] = 'বিবরণ সর্বোচ্চ ৫০০০ অক্ষর।';
        }

        // প্রশ্ন যাচাই
        $questions = $input['questions'] ?? [];
        if (empty($questions) || !is_array($questions)) {
            $errors[] = 'কমপক্ষে একটি প্রশ্ন যোগ করুন।';
        }

        $hasVoice = false;
        foreach ($questions as $i => $q) {
            $no   = $i + 1;
            $type = $q['type'] ?? '';
            if (!in_array($type, self::VALID_QUESTION_TYPES, true)) {
                $errors[] = "প্রশ্ন #{$no}: অনুমোদিত type নয়।";
                continue;
            }
            if (empty(trim($q['question_text'] ?? ''))) {
                $errors[] = "প্রশ্ন #{$no}: বিষয়বস্তু আবশ্যক।";
            }
            if ($type === 'voice') {
                $hasVoice = true;
            } else {
                // MCQ: অন্তত একটি সঠিক অপশন এবং ৪টি অপশন
                $opts    = $q['options'] ?? [];
                $correct = array_filter($opts, fn($o) => !empty($o['is_correct']));
                if (count($opts) < 2) {
                    $errors[] = "প্রশ্ন #{$no}: কমপক্ষে ২টি অপশন লাগবে।";
                }
                if (count($correct) !== 1) {
                    $errors[] = "প্রশ্ন #{$no}: ঠিক একটি সঠিক অপশন চিহ্নিত করুন।";
                }
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        return [
            'title'       => $title,
            'description' => $description !== '' ? $description : null,
            'status'      => $status,
            'questions'   => $questions,
        ];
    }

    /**
     * প্রশ্ন ও অপশন DB-তে সেভ করা
     */
    private function saveQuestionsWithOptions(int $quizId, array $questions): void
    {
        foreach ($questions as $order => $q) {
            $questionId = $this->repo->saveQuestion([
                'quiz_id'       => $quizId,
                'type'          => $q['type'],
                'question_text' => trim($q['question_text']),
                'display_order' => $order,
            ]);

            if ($q['type'] !== 'voice' && !empty($q['options'])) {
                $this->repo->saveOptions($questionId, $q['options']);
            }
        }
    }

    /**
     * MIME type থেকে ফাইল extension নির্ণয়
     */
    private function getExtensionFromMime(string $mime): string
    {
        return match ($mime) {
            'audio/webm'  => 'webm',
            'audio/ogg'   => 'ogg',
            'audio/wav'   => 'wav',
            'audio/mp4'   => 'mp4',
            'audio/mpeg'  => 'mp3',
            'audio/x-m4a' => 'm4a',
            default       => 'webm',
        };
    }
}
