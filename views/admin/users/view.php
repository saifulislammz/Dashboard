<?php
$pageTitle = 'User Profile - ' . htmlspecialchars($data['user']['username'] ?? 'User Details', ENT_QUOTES, 'UTF-8');
$activeMenu = $data['is_teacher'] ? 'teachers' : 'students';

require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$user           = $data['user'];
$profile        = $data['profile'];
$roleLabel      = $data['role_label'];
$statusLabel    = $data['status_label'];
$statusInt      = $data['status_int'];
$isTeacher      = $data['is_teacher'];
$classrooms     = $data['classrooms'];
$classroomCount = $data['classroom_count'];
$invoices       = $data['invoices'];
$invoiceSummary = $data['invoice_summary'];

$backUrl = $isTeacher ? '/admin/teachers.php' : '/admin/students.php';
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-[#f8fafc]">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Navigation Breadcrumbs & Top Action Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="<?php echo $backUrl; ?>" class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-gray-900 hover:bg-gray-50 shadow-2xs transition-colors" title="Go Back">
                    <i class="ph ph-arrow-left text-lg"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">User Profile Hub</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Comprehensive 360° overview of personal details, courses, and financial history.</p>
                </div>
            </div>

            <!-- Header Action Controls -->
            <div class="flex items-center gap-3">
                <a href="/admin/users/pdf.php?id=<?php echo (int) $user['id']; ?>" 
                   target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl text-white bg-primary hover:bg-emerald-700 shadow-xs focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all">
                    <i class="ph ph-file-pdf text-lg"></i>
                    <span>Download PDF Report</span>
                </a>
            </div>
        </div>

        <!-- Notification Alerts -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3.5 rounded-xl shadow-xs flex items-center justify-between" role="alert">
                <div class="flex items-center gap-3">
                    <i class="ph ph-warning-circle text-xl text-red-600"></i>
                    <span class="text-sm font-medium"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800 focus:outline-none">
                    <i class="ph ph-x text-lg"></i>
                </button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Primary Profile Banner Card -->
        <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
            <div class="h-28 bg-gradient-to-r from-teal-800 via-emerald-700 to-primary"></div>
            <div class="px-6 pb-6 sm:px-8 sm:pb-8 pt-0 relative flex flex-col sm:flex-row items-center sm:items-end justify-between gap-6 -mt-14">
                <div class="flex flex-col sm:flex-row items-center sm:items-end gap-5 text-center sm:text-left">
                    <div class="h-28 w-28 rounded-2xl bg-white p-1 shadow-md ring-1 ring-gray-100 shrink-0">
                        <div class="h-full w-full rounded-xl bg-emerald-50 flex items-center justify-center overflow-hidden">
                            <?php if (!empty($user['profile_picture'])): ?>
                                <img src="/uploads/avatars/<?php echo htmlspecialchars($user['profile_picture'], ENT_QUOTES, 'UTF-8'); ?>" 
                                     alt="<?php echo htmlspecialchars($user['username'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?>" 
                                     class="h-full w-full object-cover">
                            <?php else: ?>
                                <span class="text-3xl font-extrabold text-primary uppercase">
                                    <?php echo htmlspecialchars(mb_substr($user['username'] ?? $user['email'], 0, 1), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5">
                            <h2 class="text-2xl font-bold text-gray-900"><?php echo e($user['username'] ?: 'User #' . $user['id']); ?></h2>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                <?php echo e($roleLabel); ?>
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?php echo $statusInt === 0 ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100'; ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?php echo $statusInt === 0 ? 'bg-green-500' : 'bg-red-500'; ?> mr-1.5"></span>
                                <?php echo e($statusLabel); ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 flex flex-wrap items-center justify-center sm:justify-start gap-x-4 gap-y-1">
                            <span class="inline-flex items-center gap-1.5">
                                <i class="ph ph-envelope-simple text-gray-400"></i>
                                <?php echo e($user['email']); ?>
                            </span>
                            <?php if (!empty($profile['phone'])): ?>
                            <span class="inline-flex items-center gap-1.5">
                                <i class="ph ph-phone text-gray-400"></i>
                                <?php echo e($profile['phone']); ?>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($profile['country'])): ?>
                            <span class="inline-flex items-center gap-1.5">
                                <i class="ph ph-globe-hemisphere-west text-gray-400"></i>
                                <?php echo e($profile['country']); ?>
                            </span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="text-right sm:self-center shrink-0 space-y-1">
                    <p class="text-xs text-gray-400">Registered</p>
                    <p class="text-sm font-semibold text-gray-700">
                        <?php echo !empty($user['registered']) ? date('d M Y, h:i A', (int)$user['registered']) : 'N/A'; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- 360° Detail Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Column 1: Personal Details & Account Credentials -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <i class="ph ph-identification-card text-xl text-primary"></i>
                        <span>Personal Details</span>
                    </h3>
                </div>

                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Full Name</dt>
                        <dd class="mt-1 font-medium text-gray-900"><?php echo e($user['username'] ?: 'Not Provided'); ?></dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Primary Email</dt>
                        <dd class="mt-1 font-medium text-gray-900 break-all"><?php echo e($user['email']); ?></dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Phone Number</dt>
                        <dd class="mt-1 font-medium text-gray-900"><?php echo e($profile['phone'] ?: 'Not Provided'); ?></dd>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Birthday</dt>
                            <dd class="mt-1 font-medium text-gray-900">
                                <?php if (!empty($profile['date_of_birth'])): ?>
                                    <?php echo date('d M Y', strtotime($profile['date_of_birth'])); ?>
                                    <?php if (!empty($profile['age'])): ?>
                                        <span class="text-xs text-gray-400 block mt-0.5">(<?php echo (int) $profile['age']; ?> yrs)</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Not Set
                                <?php endif; ?>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Gender</dt>
                            <dd class="mt-1 font-medium text-gray-900 capitalize"><?php echo e($profile['gender'] ?: 'Not Set'); ?></dd>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Country</dt>
                            <dd class="mt-1 font-medium text-gray-900"><?php echo e($profile['country'] ?: 'Not Set'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">City / District</dt>
                            <dd class="mt-1 font-medium text-gray-900"><?php echo e($profile['city'] ?: 'Not Set'); ?></dd>
                        </div>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Full Address</dt>
                        <dd class="mt-1 font-medium text-gray-700 bg-gray-50/50 p-2.5 rounded-xl border border-gray-100 text-xs leading-relaxed">
                            <?php echo nl2br(e($profile['address'] ?: 'No address specified.')); ?>
                        </dd>
                    </div>

                    <div class="pt-2 border-t border-gray-100">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Last Login Activity</dt>
                        <dd class="mt-1 text-xs text-gray-600">
                            <?php echo !empty($user['last_login']) ? date('d M Y, h:i A', (int)$user['last_login']) : 'Never logged in'; ?>
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Column 2 & 3: Academic Classrooms & Financial Invoices -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Academic Course & Classroom Information -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                        <div class="flex items-center gap-2">
                            <i class="ph ph-chalkboard-teacher text-xl text-primary"></i>
                            <h3 class="text-base font-bold text-gray-900">
                                <?php echo $isTeacher ? 'Assigned Classrooms' : 'Enrolled Courses & Classrooms'; ?>
                            </h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                <?php echo (int) $classroomCount; ?>
                            </span>
                        </div>
                    </div>

                    <?php if (empty($classrooms)): ?>
                        <div class="text-center py-8">
                            <i class="ph ph-chalkboard text-4xl text-gray-300 mb-2"></i>
                            <p class="text-sm font-medium text-gray-500">No active classrooms or courses found for this user.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 text-sm">
                                <thead class="bg-gray-50/50 text-xs font-semibold text-gray-500 uppercase">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Code</th>
                                        <th class="px-4 py-3 text-left">Course / Classroom</th>
                                        <th class="px-4 py-3 text-left"><?php echo $isTeacher ? 'Student' : 'Teacher'; ?></th>
                                        <th class="px-4 py-3 text-left">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <?php foreach ($classrooms as $cls): ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-4 py-3 font-mono font-semibold text-primary">
                                            <?php echo e($cls['class_code']); ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-gray-900"><?php echo e($cls['class_name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo e($cls['class_title']); ?></div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">
                                            <?php echo e($isTeacher ? ($cls['student_name'] ?? 'N/A') : ($cls['teacher_name'] ?? 'N/A')); ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?php echo ($cls['status'] ?? 'Active') === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600'; ?>">
                                                <?php echo e($cls['status'] ?? 'Active'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Invoice History & Financial Overview -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 space-y-5">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                        <div class="flex items-center gap-2">
                            <i class="ph ph-receipt text-xl text-primary"></i>
                            <h3 class="text-base font-bold text-gray-900">Invoice History & Billing</h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                <?php echo (int) ($invoiceSummary['total_invoices'] ?? 0); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Financial Summary Stat Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <p class="text-xs font-semibold uppercase text-gray-400">Total Billed</p>
                            <p class="text-xl font-extrabold text-gray-900 mt-1">
                                <?php echo number_format((float) ($invoiceSummary['total_billed'] ?? 0), 2); ?>
                            </p>
                        </div>
                        <div class="bg-emerald-50/60 p-4 rounded-xl border border-emerald-100">
                            <p class="text-xs font-semibold uppercase text-emerald-600">Total Paid</p>
                            <p class="text-xl font-extrabold text-emerald-700 mt-1">
                                <?php echo number_format((float) ($invoiceSummary['total_paid'] ?? 0), 2); ?>
                            </p>
                        </div>
                        <div class="bg-rose-50/60 p-4 rounded-xl border border-rose-100">
                            <p class="text-xs font-semibold uppercase text-rose-600">Total Due</p>
                            <p class="text-xl font-extrabold text-rose-700 mt-1">
                                <?php echo number_format((float) ($invoiceSummary['total_due'] ?? 0), 2); ?>
                            </p>
                        </div>
                    </div>

                    <?php if (empty($invoices)): ?>
                        <div class="text-center py-8">
                            <i class="ph ph-receipt text-4xl text-gray-300 mb-2"></i>
                            <p class="text-sm font-medium text-gray-500">No invoice or billing records found for this account.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 text-sm">
                                <thead class="bg-gray-50/50 text-xs font-semibold text-gray-500 uppercase">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Invoice No</th>
                                        <th class="px-4 py-3 text-left">Date</th>
                                        <th class="px-4 py-3 text-right">Total</th>
                                        <th class="px-4 py-3 text-right">Paid</th>
                                        <th class="px-4 py-3 text-right">Due</th>
                                        <th class="px-4 py-3 text-center">Status</th>
                                        <th class="px-4 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <?php foreach ($invoices as $inv): ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-4 py-3 font-mono font-semibold text-primary">
                                            <?php echo e($inv['invoice_number']); ?>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-600">
                                            <?php echo date('d M Y', strtotime($inv['invoice_date'])); ?>
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900">
                                            <?php echo number_format((float) $inv['grand_total'], 2); ?>
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-emerald-600">
                                            <?php echo number_format((float) $inv['amount_paid'], 2); ?>
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium <?php echo (float)$inv['amount_due'] > 0 ? 'text-rose-600' : 'text-gray-400'; ?>">
                                            <?php echo number_format((float) $inv['amount_due'], 2); ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <?php
                                            $st = strtolower($inv['status']);
                                            $stClass = match($st) {
                                                'paid'   => 'bg-green-50 text-green-700 border-green-100',
                                                'unpaid' => 'bg-red-50 text-red-700 border-red-100',
                                                default  => 'bg-gray-100 text-gray-700 border-gray-200'
                                            };
                                            ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border <?php echo $stClass; ?> capitalize">
                                                <?php echo e($inv['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <a href="/admin/invoices/view.php?id=<?php echo (int) $inv['id']; ?>" class="text-primary hover:text-emerald-700 font-semibold text-xs inline-flex items-center gap-1">
                                                <span>View</span>
                                                <i class="ph ph-arrow-square-out text-sm"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </div>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>

