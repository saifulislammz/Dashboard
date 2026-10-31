Password Reset Request - Rahe Nazat Institute
====================================================

Hello<?= !empty($userName) ? ' ' . $userName : '' ?>,

We received a request to reset the password for your account associated with <?= $userEmail ?? '' ?>.

To reset your password, please copy and paste the following link into your web browser:
<?= $resetUrl ?? '' ?>

Important Notice:
This link is valid for <?= (int) ($expiresMinutes ?? 30) ?> minutes only and can be used once.

If you did not request a password reset, you can safely ignore this email. Your account remains secure.

----------------------------------------------------
© <?= date('Y') ?> Rahe Nazat Institute. All rights reserved.

