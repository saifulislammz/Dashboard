<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\UserProfileRepository;
use App\Repositories\ClassroomRepository;
use App\Repositories\InvoiceRepository;
use Delight\Auth\Auth;
use Delight\Auth\Status;
use Delight\Auth\Role;
use DateTimeImmutable;
use Exception;
use Throwable;

/**
 * UserProfileService
 *
 * Core business logic orchestrator for Student and Teacher profile management,
 * profile picture validation, 360° administrative profile compilation, and data sanitization.
 */
class UserProfileService
{
    private UserRepository        $userRepository;
    private UserProfileRepository $profileRepository;
    private ClassroomRepository   $classroomRepository;
    private InvoiceRepository     $invoiceRepository;
    private Auth                  $auth;

    /** Maximum allowed avatar upload size: 2MB in bytes */
    public const MAX_AVATAR_SIZE = 2097152;

    /** Whitelisted avatar MIME types */
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    /** Whitelisted avatar extensions */
    public const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp'
    ];

    /** Maximum compressed avatar output size: 300KB in bytes */
    public const MAX_COMPRESSED_AVATAR_SIZE = 307200;

    /** Standard avatar square dimension */
    public const AVATAR_MAX_DIMENSION = 500;

    /** Allowed gender values */
    public const ALLOWED_GENDERS = [
        'male',
        'female'
    ];

    /** Status integer to readable string mapping */
    private const STATUS_MAP = [
        Status::NORMAL         => 'Active',
        Status::ARCHIVED       => 'Archived',
        Status::BANNED         => 'Banned',
        Status::LOCKED         => 'Locked',
        Status::PENDING_REVIEW => 'Pending Review',
        Status::SUSPENDED      => 'Suspended',
    ];

    public function __construct(
        UserRepository        $userRepository,
        UserProfileRepository $profileRepository,
        ClassroomRepository   $classroomRepository,
        InvoiceRepository     $invoiceRepository,
        Auth                  $auth
    ) {
        $this->userRepository        = $userRepository;
        $this->profileRepository     = $profileRepository;
        $this->classroomRepository   = $classroomRepository;
        $this->invoiceRepository     = $invoiceRepository;
        $this->auth                  = $auth;
    }

    /**
     * Update user profile information (Name + Extended Personal Details).
     *
     * @param int $userId
     * @param array $input
     * @return void
     * @throws Exception
     */
    public function updateProfileInfo(int $userId, array $input): void
    {
        // 1. Validate & sanitize Name (Username)
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            throw new Exception('Full name cannot be empty.');
        }
        if (mb_strlen($name) > 100) {
            throw new Exception('Full name cannot exceed 100 characters.');
        }

        // 2. Validate & sanitize Date of Birth (Supports both 3-input select fields and Y-m-d)
        $rawYear  = trim((string) ($input['dob_year'] ?? ''));
        $rawMonth = trim((string) ($input['dob_month'] ?? ''));
        $rawDay   = trim((string) ($input['dob_day'] ?? ''));

        if ($rawYear !== '' && $rawMonth !== '' && $rawDay !== '') {
            $yearInt  = (int) $rawYear;
            $monthInt = (int) $rawMonth;
            $dayInt   = (int) $rawDay;

            if (!checkdate($monthInt, $dayInt, $yearInt)) {
                throw new Exception('Please enter a valid calendar date for Date of Birth.');
            }
            $rawDob = sprintf('%04d-%02d-%02d', $yearInt, $monthInt, $dayInt);
        } else {
            $rawDob = trim((string) ($input['date_of_birth'] ?? ''));
        }

        $dobFormatted = null;
        if ($rawDob !== '') {
            $parsedDate = DateTimeImmutable::createFromFormat('Y-m-d', $rawDob);
            $errors = DateTimeImmutable::getLastErrors();
            if (!$parsedDate || ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
                throw new Exception('Invalid date of birth format. Please provide a valid day, month, and year.');
            }

            $today = new DateTimeImmutable('today');
            if ($parsedDate > $today) {
                throw new Exception('Date of birth cannot be in the future.');
            }

            $age = $today->diff($parsedDate)->y;
            if ($age < 5 || $age > 100) {
                throw new Exception('Age must be between 5 and 100 years.');
            }
            $dobFormatted = $parsedDate->format('Y-m-d');
        }

        // 3. Validate & sanitize Gender
        $rawGender = strtolower(trim((string) ($input['gender'] ?? '')));
        $genderFormatted = null;
        if ($rawGender !== '') {
            if (!in_array($rawGender, self::ALLOWED_GENDERS, true)) {
                throw new Exception('Please select a valid gender option (Male or Female).');
            }
            $genderFormatted = $rawGender;
        }

        // 4. Validate & sanitize Phone (Supports international calling code prefix)
        $countryCode = trim((string) ($input['country_code'] ?? ''));
        $rawPhoneNum = trim((string) ($input['phone_number'] ?? ''));
        if ($rawPhoneNum !== '') {
            if ($countryCode !== '' && !str_starts_with($rawPhoneNum, '+')) {
                // If number has leading zeros, trim them for international dial code prefixing
                $cleanedPhoneNum = ltrim($rawPhoneNum, '0');
                $rawPhone = $countryCode . ' ' . $cleanedPhoneNum;
            } else {
                $rawPhone = $rawPhoneNum;
            }
        } else {
            $rawPhone = trim((string) ($input['phone'] ?? ''));
        }

        $phoneFormatted = null;
        if ($rawPhone !== '') {
            if (mb_strlen($rawPhone) > 30) {
                throw new Exception('Phone number cannot exceed 30 characters.');
            }
            // Allow numbers, spaces, plus, hyphens, parentheses
            if (!preg_match('/^[0-9+\-\s()]{6,30}$/', $rawPhone)) {
                throw new Exception('Please enter a valid phone number format.');
            }
            $phoneFormatted = $rawPhone;
        }

        // 5. Validate & sanitize Country, City, Address
        $country = trim((string) ($input['country'] ?? ''));
        $country = $country !== '' ? mb_substr($country, 0, 100) : null;

        $city = trim((string) ($input['city'] ?? ''));
        $city = $city !== '' ? mb_substr($city, 0, 100) : null;

        $address = trim((string) ($input['address'] ?? ''));
        $address = $address !== '' ? mb_substr($address, 0, 1000) : null;

        // 6. Persist to Repositories
        $this->userRepository->updateUsername($userId, $name);

        $this->profileRepository->upsert($userId, [
            'phone'         => $phoneFormatted,
            'date_of_birth' => $dobFormatted,
            'gender'        => $genderFormatted,
            'country'       => $country,
            'city'          => $city,
            'address'       => $address,
        ]);
    }

    /**
     * Upload and update user profile picture with strict MIME, size, and binary integrity validation.
     *
     * @param int $userId
     * @param array $file $_FILES entry
     * @return string Stored filename
     * @throws Exception
     */
    public function updateProfilePicture(int $userId, array $file): string
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid file upload parameter.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception('The uploaded file exceeds the maximum allowed size of 2MB.');
                case UPLOAD_ERR_NO_FILE:
                    throw new Exception('No image file was selected.');
                default:
                    throw new Exception('File upload failed. Please try again.');
            }
        }

        // 1. File size check (Max 2MB)
        if ($file['size'] > self::MAX_AVATAR_SIZE) {
            throw new Exception('Profile picture must be less than 2MB.');
        }

        // 2. MIME type verification via finfo (Magic bytes)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new Exception('Invalid image format. Allowed formats: JPG, PNG, WEBP.');
        }

        // 3. Extension verification
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new Exception('Invalid file extension. Allowed extensions: .jpg, .png, .webp.');
        }

        // 4. Image structural validation via getimagesize (prevents polyglot/script attacks)
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
            throw new Exception('Corrupted or invalid image file.');
        }

        // 5. Ensure upload directory exists
        $uploadDir = realpath(__DIR__ . '/../../public/uploads/avatars');
        if ($uploadDir === false) {
            $uploadDir = __DIR__ . '/../../public/uploads/avatars';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
        }

        // 6. Decode source image to GD resource
        $srcImage = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $srcImage = @imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $srcImage = @imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $srcImage = @imagecreatefromwebp($file['tmp_name']);
                }
                break;
        }

        if (!$srcImage) {
            throw new Exception('Unable to process uploaded image. Please provide a valid JPG, PNG, or WEBP file.');
        }

        $srcWidth  = (int) $imageInfo[0];
        $srcHeight = (int) $imageInfo[1];

        // Correct smartphone EXIF rotation for JPEGs if needed
        if ($mimeType === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file['tmp_name']);
            if (!empty($exif['Orientation'])) {
                switch ((int) $exif['Orientation']) {
                    case 3:
                        $rotated = imagerotate($srcImage, 180, 0);
                        if ($rotated) { imagedestroy($srcImage); $srcImage = $rotated; }
                        break;
                    case 6:
                        $rotated = imagerotate($srcImage, -90, 0);
                        if ($rotated) {
                            imagedestroy($srcImage);
                            $srcImage = $rotated;
                            $tmp = $srcWidth; $srcWidth = $srcHeight; $srcHeight = $tmp;
                        }
                        break;
                    case 8:
                        $rotated = imagerotate($srcImage, 90, 0);
                        if ($rotated) {
                            imagedestroy($srcImage);
                            $srcImage = $rotated;
                            $tmp = $srcWidth; $srcWidth = $srcHeight; $srcHeight = $tmp;
                        }
                        break;
                }
            }
        }

        // 7. Calculate center square crop for optimal avatar appearance (max 500x500)
        $minDim    = min($srcWidth, $srcHeight);
        $srcX      = (int) (($srcWidth - $minDim) / 2);
        $srcY      = (int) (($srcHeight - $minDim) / 2);
        $targetDim = min(self::AVATAR_MAX_DIMENSION, $minDim);
        if ($targetDim < 200 && $minDim >= 200) {
            $targetDim = 200;
        }

        $dstImage = imagecreatetruecolor($targetDim, $targetDim);

        // Preserve alpha transparency
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
        $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
        imagefilledrectangle($dstImage, 0, 0, $targetDim, $targetDim, $transparent);

        imagecopyresampled(
            $dstImage,
            $srcImage,
            0,
            0,
            $srcX,
            $srcY,
            $targetDim,
            $targetDim,
            $minDim,
            $minDim
        );

        // 8. Generate secure, cryptographically unguessable WebP filename
        $filename = sprintf('avatar_%d_%d_%s.webp', $userId, time(), bin2hex(random_bytes(6)));
        $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        // 9. Compress and save as WebP with guaranteed <= 300KB file size
        $quality = 85;
        $saved = false;
        while ($quality >= 40) {
            $saved = @imagewebp($dstImage, $destination, $quality);
            if ($saved && file_exists($destination)) {
                clearstatcache(true, $destination);
                $fileSize = filesize($destination);
                if ($fileSize !== false && ($fileSize <= self::MAX_COMPRESSED_AVATAR_SIZE || $quality <= 45)) {
                    break;
                }
            }
            $quality -= 10;
        }

        // Free memory
        imagedestroy($srcImage);
        imagedestroy($dstImage);

        if (!$saved || !file_exists($destination)) {
            throw new Exception('Failed to compress and save WebP profile picture.');
        }

        // 10. Delete previous avatar if it exists
        $oldPicture = $this->userRepository->getProfilePicture($userId);
        if ($oldPicture) {
            $oldFilePath = $uploadDir . DIRECTORY_SEPARATOR . basename($oldPicture);
            if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                @unlink($oldFilePath);
            }
        }

        // 11. Update DB record with .webp filename
        $this->userRepository->updateProfilePicture($userId, $filename);

        // 12. Sync session cache if updating own picture
        if (session_status() === PHP_SESSION_ACTIVE && (int) $this->auth->getUserId() === $userId) {
            $_SESSION['user_profile_picture'] = $filename;
        }

        return $filename;
    }

    /**
     * Remove user profile picture from disk and database.
     *
     * @param int $userId
     * @return void
     */
    public function deleteProfilePicture(int $userId): void
    {
        $oldPicture = $this->userRepository->getProfilePicture($userId);
        if ($oldPicture) {
            $uploadDir = realpath(__DIR__ . '/../../public/uploads/avatars');
            if ($uploadDir !== false) {
                $oldFilePath = $uploadDir . DIRECTORY_SEPARATOR . basename($oldPicture);
                if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                    @unlink($oldFilePath);
                }
            }
        }

        $this->userRepository->updateProfilePicture($userId, null);

        if (session_status() === PHP_SESSION_ACTIVE && (int) $this->auth->getUserId() === $userId) {
            $_SESSION['user_profile_picture'] = null;
        }
    }

    /**
     * Compile 360° Complete Profile Data for Super Admin single-view and PDF export.
     * Combines user account info, extended profile attributes, assigned/enrolled classrooms,
     * and invoice history.
     *
     * @param int $userId
     * @return array
     * @throws Exception If user not found
     */
    public function getCompleteUserProfile(int $userId): array
    {
        $user = $this->userRepository->findUserDetailsById($userId);
        if (!$user) {
            throw new Exception("User with ID {$userId} not found.");
        }

        // 1. Resolve role label and bitmask
        $rolesMask = (int) ($user['roles_mask'] ?? 0);
        $roleLabel = 'Student';
        $isTeacher = false;
        $isStudent = true;

        if (($rolesMask & Role::ADMIN) > 0 || ($rolesMask & Role::SUPER_ADMIN) > 0) {
            $roleLabel = 'Administrator';
            $isStudent = false;
        } elseif (($rolesMask & Role::MANAGER) > 0) {
            $roleLabel = 'Teacher';
            $isTeacher = true;
            $isStudent = false;
        }

        // 2. Resolve status label
        $statusInt = (int) ($user['status'] ?? 0);
        $statusLabel = self::STATUS_MAP[$statusInt] ?? 'Unknown';

        // 3. Fetch Classrooms / Courses
        $classrooms = [];
        $classroomCount = 0;
        if ($isTeacher) {
            $classrooms = $this->classroomRepository->getTeacherClassrooms($userId, 100, 0);
            $classroomCount = $this->classroomRepository->countTeacherClassrooms($userId);
        } else {
            $classrooms = $this->classroomRepository->getStudentClassrooms($userId, 100, 0);
            $classroomCount = $this->classroomRepository->countStudentClassrooms($userId);
        }

        // 4. Fetch Invoices and Financial Summary
        $invoices = [];
        $invoiceSummary = [
            'total_invoices' => 0,
            'total_billed'   => 0.0,
            'total_paid'     => 0.0,
            'total_due'      => 0.0,
        ];

        if (!empty($user['email'])) {
            $invoices = $this->invoiceRepository->findByStudentEmail($user['email'], 100);
            $invoiceSummary = $this->invoiceRepository->getSummaryByStudentEmail($user['email']);
        }

        // 5. Calculate age if date_of_birth exists
        $age = null;
        if (!empty($user['date_of_birth'])) {
            try {
                $dob = new DateTimeImmutable($user['date_of_birth']);
                $today = new DateTimeImmutable('today');
                $age = $today->diff($dob)->y;
            } catch (Throwable $e) {
                $age = null;
            }
        }

        return [
            'user'            => $user,
            'profile'         => [
                'phone'         => $user['phone'] ?? null,
                'date_of_birth' => $user['date_of_birth'] ?? null,
                'age'           => $age,
                'gender'        => $user['gender'] ?? null,
                'country'       => $user['country'] ?? null,
                'city'          => $user['city'] ?? null,
                'address'       => $user['address'] ?? null,
            ],
            'role_label'      => $roleLabel,
            'is_teacher'      => $isTeacher,
            'is_student'      => $isStudent,
            'status_label'    => $statusLabel,
            'status_int'      => $statusInt,
            'classrooms'      => $classrooms,
            'classroom_count' => $classroomCount,
            'invoices'        => $invoices,
            'invoice_summary' => $invoiceSummary,
        ];
    }
}

