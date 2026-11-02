<?php
/**
 * Invoice Delivery Plain-Text Email Fallback Template
 */

$invNo      = $invoice['invoice_number'];
$sName      = $invoice['student_name'] ?? 'Customer';
$curr       = $invoice['currency'] ?? 'BDT';
$status     = strtoupper($invoice['status'] ?? 'UNPAID');
$invDate    = !empty($invoice['invoice_date']) ? date('M d, Y', strtotime($invoice['invoice_date'])) : date('M d, Y');
$dueDate    = !empty($invoice['due_date']) ? date('M d, Y', strtotime($invoice['due_date'])) : '—';
$instName   = $settings['institution_name'] ?? 'Rahe Nazat Institute';

$grandTotal = (float) ($invoice['grand_total'] ?? 0);
$amountPaid = (float) ($invoice['amount_paid'] ?? 0);
$amountDue  = (float) ($invoice['amount_due'] ?? ($grandTotal - $amountPaid));
?>
INVOICE NOTIFICATION: #<?= $invNo ?> - <?= $instName ?>


Dear <?= $sName ?>,

Please find below the summary of your invoice. Your official print-ready PDF invoice is attached to this email.

=======================================================
INVOICE SUMMARY
=======================================================
Invoice Number : #<?= $invNo ?>

Invoice Date   : <?= $invDate ?>

Due Date       : <?= $dueDate ?>

Payment Status : <?= $status ?>

Grand Total    : <?= $curr ?> <?= number_format($grandTotal, 2) ?>

Amount Paid    : <?= $curr ?> <?= number_format($amountPaid, 2) ?>

Balance Due    : <?= $curr ?> <?= number_format($amountDue, 2) ?>


=======================================================
ATTACHED FILE
=======================================================
File Name      : Invoice-<?= $invNo ?>.pdf
Please check the attachments to download and view your official invoice.

<?php if (!empty($customNotes)): ?>
Note:
<?= $customNotes ?>

<?php endif; ?>

If you have any questions, please contact <?= $settings['institution_email'] ?? 'our support team' ?>.

Thank you,
<?= $instName ?>


