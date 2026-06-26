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
        $emailInput = $data['email'] ?? '';
        $emailInput = trim($emailInput);
        $email = filter_var($emailInput, FILTER_SANITIZE_EMAIL);
        
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
}
