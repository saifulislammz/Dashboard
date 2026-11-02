<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * UserProfilePdfServiceInterface
 *
 * Contract for compiling and rendering vector PDF profile reports for users.
 */
interface UserProfilePdfServiceInterface
{
    /**
     * Generate in-memory PDF binary string for a comprehensive user profile report.
     *
     * @param array $profileData Complete 360° aggregated profile array
     * @param array $settings Optional institution settings (logo, name, etc.)
     * @param string|null $adminName Name of the administrator generating the report
     * @return string Raw PDF binary string
     */
    public function generateProfilePdf(array $profileData, array $settings = [], ?string $adminName = null): string;
}

