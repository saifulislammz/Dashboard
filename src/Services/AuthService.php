<?php

namespace App\Services;

use Delight\Auth\Auth;
use Exception;

class AuthService
{
    private Auth $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function login(array $data): void
    {
        // ── 1. Raw input extraction ────────────────────────────────────────
        $rawEmail    = $data['email']    ?? '';
        $rawPassword = $data['password'] ?? '';

        // ── 2. Email: strip null-bytes + control chars, trim, lowercase ────
        $email = preg_replace('/[\x00-\x1F\x7F]/', '', $rawEmail); // strip control chars
        $email = trim($email);
        $email = mb_strtolower($email, 'UTF-8');
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);         // strip illegal email chars

        // ── 3. Email validation ────────────────────────────────────────────
        if (empty($email)) {
            throw new Exception('Email address is required.');
        }
        if (strlen($email) > 254) {
            throw new Exception('Email address is too long (max 254 characters).');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }

        // ── 4. Password: strip null-bytes & non-printable chars ────────────
        $password = preg_replace('/[\x00-\x1F\x7F]/', '', $rawPassword); // strip control chars

        // Allow only printable ASCII (space 0x20 through tilde 0x7E)
        if (preg_match('/[^\x20-\x7E]/', $password)) {
            throw new Exception('Password contains invalid characters.');
        }

        // ── 5. Password length bounds ──────────────────────────────────────
        if (strlen($password) < 1) {
            throw new Exception('Password is required.');
        }
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long.');
        }
        if (strlen($password) > 128) {
            throw new Exception('Password must not exceed 128 characters.');
        }

        // ── 6. Remember-me ────────────────────────────────────────────────
        $rememberDuration = !empty($data['remember_me']) ? (60 * 60 * 24 * 30) : null; // 30 days

        // ── 7. Attempt login via Delight Auth ─────────────────────────────
        try {
            $this->auth->login($email, $password, $rememberDuration);

            // Anti Session Fixation — always regenerate on successful login
            session_regenerate_id(true);

        } catch (\Delight\Auth\InvalidEmailException $e) {
            throw new Exception('Wrong email address or password');
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            throw new Exception('Wrong email address or password');
        } catch (\Delight\Auth\EmailNotVerifiedException $e) {
            throw new Exception('Email not verified. Please check your inbox.');
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            throw new Exception('Too many login attempts. Please try again later.');
        }
    }

    /**
     * Change the currently-logged-in user's password.
     * Validates fields, enforces complexity, delegates to Delight Auth.
     *
     * @throws \Exception                              On validation failure
     * @throws \Delight\Auth\InvalidPasswordException  If current password is wrong
     * @throws \Delight\Auth\TooManyRequestsException  On rate-limit
     */
    public function changePassword(string $currentPassword, string $newPassword, string $confirmPassword): void
    {
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            throw new \Exception('All fields are required.');
        }

        if ($newPassword !== $confirmPassword) {
            throw new \Exception('New password and confirm password do not match.');
        }

        // Password strength validation
        $hasUpper   = (bool) preg_match('@[A-Z]@', $newPassword);
        $hasLower   = (bool) preg_match('@[a-z]@', $newPassword);
        $hasNumber  = (bool) preg_match('@[0-9]@', $newPassword);
        $hasSpecial = (bool) preg_match('@[\W]@',  $newPassword);

        if (!$hasUpper || !$hasLower || !$hasNumber || !$hasSpecial || strlen($newPassword) < 8) {
            throw new \Exception(
                'New password must be at least 8 characters long and include ' .
                'an uppercase letter, a lowercase letter, a number, and a special character.'
            );
        }

        // Delegate to Delight Auth — throws InvalidPasswordException or TooManyRequestsException
        $this->auth->changePassword($currentPassword, $newPassword);

        // Regenerate session ID after credential change (anti-session-fixation)
        session_regenerate_id(true);
    }
}
