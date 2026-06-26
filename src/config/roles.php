<?php

namespace App\Config;

/**
 * Role Definitions using Delight Auth built-in Role bitmasks.
 * These map directly to Delight\Auth\Role constants to avoid
 * bitmask overlap while giving us meaningful names.
 */
class Roles {
    public const ADMIN = \Delight\Auth\Role::ADMIN;
    public const TEACHER = \Delight\Auth\Role::MANAGER;
    public const STUDENT = \Delight\Auth\Role::CONSUMER;
}

// Global helper constants for legacy procedural code
define('ROLE_ADMIN', Roles::ADMIN);
define('ROLE_TEACHER', Roles::TEACHER);
define('ROLE_STUDENT', Roles::STUDENT);
