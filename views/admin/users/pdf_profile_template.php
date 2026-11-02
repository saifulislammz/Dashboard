<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile Report - <?php echo htmlspecialchars($user['username'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        @page {
            margin: 24px 30px 30px 30px;
            size: a4 portrait;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1f2937;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table {
            border-bottom: 2px solid #0d9488;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .brand-name {
            font-size: 18px;
            font-weight: bold;
            color: #0d9488;
            margin: 0;
        }

        .brand-subtitle {
            font-size: 10px;
            color: #6b7280;
            margin: 2px 0 0 0;
        }

        .doc-title {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .doc-meta {
            font-size: 9px;
            color: #6b7280;
            text-align: right;
            margin-top: 3px;
        }

        .section-header {
            background-color: #f0fdf4;
            border-left: 3px solid #0d9488;
            padding: 5px 8px;
            font-size: 11px;
            font-weight: bold;
            color: #065f46;
            margin-top: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .profile-box {
            border: 1px solid #e5e7eb;
            background-color: #ffffff;
            margin-bottom: 14px;
        }

        .profile-avatar-cell {
            width: 110px;
            text-align: center;
            vertical-align: top;
            padding: 12px;
            border-right: 1px solid #f3f4f6;
            background-color: #fafafa;
        }

        .avatar-img {
            width: 90px;
            height: 90px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #0d9488;
        }

        .avatar-placeholder {
            width: 86px;
            height: 86px;
            line-height: 86px;
            background-color: #ccfbf1;
            color: #0f766e;
            font-size: 32px;
            font-weight: bold;
            text-align: center;
            border-radius: 8px;
            margin: 0 auto;
            border: 2px solid #0d9488;
        }

        .profile-info-table td {
            padding: 4px 8px;
            vertical-align: top;
            font-size: 10.5px;
        }

        .field-label {
            color: #6b7280;
            font-size: 9.5px;
            text-transform: uppercase;
            font-weight: bold;
            width: 25%;
        }

        .field-val {
            color: #111827;
            font-weight: normal;
            width: 25%;
        }

        .badge {
            display: inline-block;
            padding: 2px 7px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 10px;
            text-transform: uppercase;
        }

        .badge-active { background-color: #d1fae5; color: #065f46; }
        .badge-inactive { background-color: #fee2e2; color: #991b1b; }
        .badge-role { background-color: #e0f2fe; color: #0369a1; }

        .data-table {
            border: 1px solid #e5e7eb;
            font-size: 10px;
            margin-bottom: 12px;
        }

        .data-table th {
            background-color: #f9fafb;
            color: #4b5563;
            font-weight: bold;
            text-align: left;
            padding: 5px 8px;
            border-bottom: 1px solid #e5e7eb;
            text-transform: uppercase;
            font-size: 9px;
        }

        .data-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .stat-card {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: center;
            background-color: #fafafa;
        }

        .stat-val {
            font-size: 13px;
            font-weight: bold;
            margin-top: 2px;
        }

        .footer {
            margin-top: 24px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            font-size: 8.5px;
            color: #9ca3af;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <h1 class="brand-name"><?php echo htmlspecialchars($settings['institution_name'] ?? 'Rahe Nazat Institute', ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="brand-subtitle"><?php echo htmlspecialchars($settings['institution_tagline'] ?? 'Excellence in Education', ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="brand-subtitle" style="font-size: 8.5px; margin-top: 3px;">
                    <?php echo htmlspecialchars($settings['institution_address'] ?? 'Dhaka, Bangladesh', ENT_QUOTES, 'UTF-8'); ?> | 
                    <?php echo htmlspecialchars($settings['institution_email'] ?? 'info@institute.edu', ENT_QUOTES, 'UTF-8'); ?>
                </p>
            </td>
            <td style="width: 40%; vertical-align: top;" class="text-right">
                <p class="doc-title">Comprehensive Profile Report</p>
                <p class="doc-meta">Generated: <?php echo htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="doc-meta">Audited By: <?php echo htmlspecialchars($adminName ?? 'Administrator', ENT_QUOTES, 'UTF-8'); ?></p>
            </td>
        </tr>
    </table>

    <!-- User Information Section -->
    <div class="section-header">1. User Identity & Personal Information</div>
    <div class="profile-box">
        <table>
            <tr>
                <!-- Avatar Column -->
                <td class="profile-avatar-cell">
                    <?php if (!empty($avatarBase64)): ?>
                        <img src="<?php echo $avatarBase64; ?>" alt="Avatar" class="avatar-img">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?php echo htmlspecialchars(strtoupper(mb_substr($user['username'] ?? $user['email'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                    <div style="margin-top: 6px; font-size: 8.5px; color: #6b7280; font-weight: bold;">
                        USER #<?php echo (int) $user['id']; ?>
                    </div>
                </td>

                <!-- Details Grid Column -->
                <td style="padding: 10px; vertical-align: top;">
                    <table class="profile-info-table">
                        <tr>
                            <td class="field-label">Full Name:</td>
                            <td class="field-val" style="font-weight: bold;"><?php echo htmlspecialchars($user['username'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="field-label">Role:</td>
                            <td class="field-val">
                                <span class="badge badge-role"><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td class="field-label">Email:</td>
                            <td class="field-val"><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="field-label">Status:</td>
                            <td class="field-val">
                                <span class="badge <?php echo ($user['status'] ?? 0) == 0 ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="field-label">Birthday:</td>
                            <td class="field-val">
                                <?php if (!empty($profile['date_of_birth'])): ?>
                                    <?php echo date('d M Y', strtotime($profile['date_of_birth'])); ?>
                                    <?php if (!empty($profile['age'])): ?>
                                        (<?php echo (int) $profile['age']; ?> yrs)
                                    <?php endif; ?>
                                <?php else: ?>
                                    Not Set
                                <?php endif; ?>
                            </td>
                            <td class="field-label">Gender:</td>
                            <td class="field-val" style="text-transform: capitalize;">
                                <?php echo htmlspecialchars($profile['gender'] ?? 'Not Set', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="field-label">Phone:</td>
                            <td class="field-val"><?php echo htmlspecialchars($profile['phone'] ?? 'Not Provided', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="field-label">Country:</td>
                            <td class="field-val"><?php echo htmlspecialchars($profile['country'] ?? 'Not Set', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <tr>
                            <td class="field-label">City:</td>
                            <td class="field-val"><?php echo htmlspecialchars($profile['city'] ?? 'Not Set', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="field-label">Registered:</td>
                            <td class="field-val">
                                <?php echo !empty($user['registered']) ? date('d M Y', (int)$user['registered']) : 'N/A'; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="field-label">Address:</td>
                            <td class="field-val" colspan="3" style="color: #4b5563;">
                                <?php echo htmlspecialchars($profile['address'] ?? 'No address provided.', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- Academic Classrooms Section -->
    <div class="section-header">2. Course & Classroom Enrollments</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Class Code</th>
                <th style="width: 35%;">Class Name / Title</th>
                <th style="width: 35%;"><?php echo !empty($profileData['is_teacher']) ? 'Student Name' : 'Teacher Name'; ?></th>
                <th style="width: 15%; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($classrooms)): ?>
                <tr>
                    <td colspan="4" class="text-center" style="color: #9ca3af; padding: 10px;">
                        No assigned classrooms or courses found for this user.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($classrooms as $cls): ?>
                <tr>
                    <td style="font-family: monospace; font-weight: bold; color: #0d9488;">
                        <?php echo htmlspecialchars($cls['class_code'], ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($cls['class_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <?php if (!empty($cls['class_title'])): ?>
                            <br><span style="color: #6b7280; font-size: 8.5px;"><?php echo htmlspecialchars($cls['class_title'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars(!empty($profileData['is_teacher']) ? ($cls['student_name'] ?? 'N/A') : ($cls['teacher_name'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                    <td class="text-center">
                        <span class="badge <?php echo ($cls['status'] ?? 'Active') === 'Active' ? 'badge-active' : 'badge-inactive'; ?>">
                            <?php echo htmlspecialchars($cls['status'] ?? 'Active', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Financial Billing & Invoice History Section -->
    <div class="section-header">3. Invoice History & Financial Billing</div>
    
    <!-- Summary Financial Strip -->
    <table style="margin-bottom: 10px;">
        <tr>
            <td style="width: 32%; padding-right: 5px;">
                <div class="stat-card">
                    <span style="font-size: 8.5px; color: #6b7280; text-transform: uppercase;">Total Billed</span>
                    <div class="stat-val" style="color: #111827;">
                        <?php echo number_format((float) ($invoiceSummary['total_billed'] ?? 0), 2); ?>
                    </div>
                </div>
            </td>
            <td style="width: 32%; padding-right: 5px; padding-left: 5px;">
                <div class="stat-card" style="border-color: #a7f3d0; background-color: #f0fdf4;">
                    <span style="font-size: 8.5px; color: #047857; text-transform: uppercase;">Total Paid</span>
                    <div class="stat-val" style="color: #065f46;">
                        <?php echo number_format((float) ($invoiceSummary['total_paid'] ?? 0), 2); ?>
                    </div>
                </div>
            </td>
            <td style="width: 32%; padding-left: 5px;">
                <div class="stat-card" style="border-color: #fecdd3; background-color: #fff1f2;">
                    <span style="font-size: 8.5px; color: #b91c1c; text-transform: uppercase;">Total Due</span>
                    <div class="stat-val" style="color: #991b1b;">
                        <?php echo number_format((float) ($invoiceSummary['total_due'] ?? 0), 2); ?>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 20%;">Invoice No</th>
                <th style="width: 15%;">Date</th>
                <th style="width: 20%; text-align: right;">Grand Total</th>
                <th style="width: 15%; text-align: right;">Amount Paid</th>
                <th style="width: 15%; text-align: right;">Amount Due</th>
                <th style="width: 15%; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($invoices)): ?>
                <tr>
                    <td colspan="6" class="text-center" style="color: #9ca3af; padding: 10px;">
                        No billing or invoice records found for this account.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td style="font-family: monospace; font-weight: bold; color: #0d9488;">
                        <?php echo htmlspecialchars($inv['invoice_number'], ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                    <td><?php echo date('d M Y', strtotime($inv['invoice_date'])); ?></td>
                    <td class="text-right font-medium">
                        <?php echo number_format((float) $inv['grand_total'], 2); ?>
                    </td>
                    <td class="text-right" style="color: #059669;">
                        <?php echo number_format((float) $inv['amount_paid'], 2); ?>
                    </td>
                    <td class="text-right" style="<?php echo (float)$inv['amount_due'] > 0 ? 'color: #dc2626; font-weight: bold;' : 'color: #6b7280;'; ?>">
                        <?php echo number_format((float) $inv['amount_due'], 2); ?>
                    </td>
                    <td class="text-center">
                        <span class="badge <?php echo strtolower($inv['status']) === 'paid' ? 'badge-active' : (strtolower($inv['status']) === 'unpaid' ? 'badge-inactive' : 'badge-role'); ?>">
                            <?php echo htmlspecialchars($inv['status'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Footer Security & Tracking Bar -->
    <table class="footer">
        <tr>
            <td style="width: 70%;">
                This document is a confidential academic and administrative record generated by the Rahe Nazat Institute LMS System.
                Any unauthorized alteration or distribution is strictly prohibited.
            </td>
            <td style="width: 30%;" class="text-right">
                Doc Ref: RNI-PRF-<?php echo (int) $user['id']; ?>-<?php echo time(); ?>
            </td>
        </tr>
    </table>

</body>
</html>

