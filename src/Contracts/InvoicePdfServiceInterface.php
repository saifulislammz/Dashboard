<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * InvoicePdfServiceInterface
 *
 * Contract for generating invoice PDF documents.
 * Enforces clean separation between business logic and PDF rendering technology.
 */
interface InvoicePdfServiceInterface
{
    /**
     * Generate in-memory PDF binary string for a given invoice.
     *
     * @param array $invoice Master invoice record from DB
     * @param array $items Line items for the invoice
     * @param array $settings Institution / invoice settings
     * @param string|null $creatorName Name of creator/admin for audit bar
     * @return string Raw PDF binary string
     */
    public function generatePdf(array $invoice, array $items, array $settings, ?string $creatorName = null): string;
}

