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
        $email = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        
        $password = $data['password'] ?? '';
        $rememberDuration = !empty($data['remember_me']) ? (int) (60 * 60 * 24 * 30) : null; // 30 days

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }

        if (strlen($password) < 1 || strlen($password) > 255) {
            throw new Exception('Invalid password length.');
        }

        try {
            $this->auth->login($email, $password, $rememberDuration);
            
            // Anti Session Fixation
            session_regenerate_id(true);
            
        } catch (\Delight\Auth\InvalidEmailException $e) {
            throw new Exception('Wrong email address or password'); 
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            throw new Exception('Wrong email address or password');
        } catch (\Delight\Auth\EmailNotVerifiedException $e) {
            throw new Exception('Email not verified');
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            throw new Exception('Too many requests. Please try again later.');
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
