<?php
$pageTitle = 'Profile';
$activeMenu = 'profile';

// Capture and clear session flash messages for SweetAlert2 popups
$flashSuccess = $_SESSION['profile_success'] ?? null;
$flashError   = $_SESSION['profile_error'] ?? null;
unset($_SESSION['profile_success'], $_SESSION['profile_error']);

// Determine correct sidebar based on role
require __DIR__ . '/layouts/header.php';
if ($auth->hasRole(ROLE_ADMIN) || $auth->hasRole(\Delight\Auth\Role::SUPER_ADMIN)) {
    require __DIR__ . '/layouts/sidebar_admin.php';
} elseif ($auth->hasRole(ROLE_TEACHER)) {
    require __DIR__ . '/layouts/sidebar_teacher.php';
} else {
    require __DIR__ . '/layouts/sidebar_student.php';
}
?>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-[#f8fafc]">
    <div class="max-w-5xl mx-auto space-y-8">
        <!-- Top Header Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">My Profile</h1>
                <p class="mt-1 text-sm text-gray-500">View and update your personal details and profile credentials.</p>
            </div>
            <a href="change_password.php" class="inline-flex items-center px-4 py-2 border border-green-200 shadow-xs text-sm font-medium rounded-xl text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                <svg class="mr-2 -ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Change Password
            </a>
        </div>

        <!-- Main Profile Card -->
        <div class="bg-white shadow-xs border border-gray-100 rounded-2xl overflow-hidden divide-y divide-gray-100">
            <!-- Avatar & Header Summary Section -->
            <div class="p-6 sm:p-8 flex flex-col sm:flex-row items-center sm:items-start gap-6 bg-gradient-to-b from-gray-50/50 to-white">
                <div class="relative group shrink-0">
                    <div class="h-28 w-28 rounded-full bg-emerald-50 flex items-center justify-center overflow-hidden border-4 border-white shadow-md ring-1 ring-gray-100">
                        <?php if (!empty($profilePic)): ?>
                            <img src="/uploads/avatars/<?php echo htmlspecialchars($profilePic, ENT_QUOTES, 'UTF-8'); ?>" alt="Profile Picture" class="h-full w-full object-cover">
                        <?php else: ?>
                            <span class="text-3xl font-extrabold text-primary uppercase">
                                <?php echo htmlspecialchars(mb_substr($username ?: $email, 0, 1), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex-1 text-center sm:text-left space-y-2">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo e($username ?: 'Not Set'); ?></h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100 self-center sm:self-auto">
                            <?php echo e($role); ?>
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?php echo $statusInt === 0 ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100'; ?> self-center sm:self-auto">
                            <?php echo e($statusText); ?>
                        </span>
                    </div>
                    <p class="text-sm text-gray-500"><?php echo e($email); ?></p>

                    <!-- Avatar Upload / Remove Actions (Aligned strictly to left) -->
                    <div class="pt-2 flex flex-wrap items-center justify-center sm:justify-start gap-3">
                        <form id="profilePicForm" action="profile.php" method="POST" enctype="multipart/form-data" class="inline">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="upload_picture">
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/webp" class="hidden">
                            <button type="button" id="uploadPicBtn" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 border border-gray-200 shadow-xs text-xs font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all">
                                <i class="ph ph-camera text-sm text-primary"></i>
                                <?php echo !empty($profilePic) ? 'Change Picture' : 'Upload Picture'; ?>
                            </button>
                        </form>

                        <?php if (!empty($profilePic)): ?>
                        <form id="removePicForm" action="profile.php" method="POST" class="inline">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="remove_picture">
                            <button type="button" id="removePicBtn" class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-red-200 text-xs font-semibold rounded-lg text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all">
                                <i class="ph ph-trash text-sm"></i>
                                Remove
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-gray-400 pt-1">Recommended: 500x500 px. Supported formats: JPG, PNG, WEBP. Maximum file size: 2MB.</p>
                </div>
            </div>

            <!-- Profile Edit Form -->
            <form action="profile.php" method="POST" class="p-6 sm:p-8 space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="update_profile">

                <div class="border-b border-gray-100 pb-4">
                    <h3 class="text-lg font-bold text-gray-900">Personal Information</h3>
                    <p class="text-sm text-gray-500">Update your personal contact details, birthday, and location.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Full Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-user text-lg"></i>
                            </div>
                            <input type="text" id="name" name="name" required maxlength="100"
                                   value="<?php echo htmlspecialchars($username ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   class="pl-10 block w-full rounded-xl border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                        </div>
                    </div>

                    <!-- Email Address (Read-only for security) -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">
                            Email Address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-envelope-simple text-lg"></i>
                            </div>
                            <input type="email" id="email" disabled
                                   value="<?php echo htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   class="pl-10 block w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3 text-sm text-gray-500 cursor-not-allowed">
                        </div>
                        <p class="mt-1 text-xs text-gray-400">Primary login email address is protected. Contact an administrator to change.</p>
                    </div>

                    <!-- Birthday / Date of Birth (Split Day, Month, Year) -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            Birthday (Date of Birth)
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <!-- Day -->
                            <div>
                                <select id="dob_day" name="dob_day" class="block w-full rounded-xl border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors bg-white">
                                    <option value="">Day</option>
                                    <?php for ($d = 1; $d <= 31; $d++): ?>
                                        <option value="<?php echo sprintf('%02d', $d); ?>" <?php echo ((int) ($dobDay ?? 0) === $d) ? 'selected' : ''; ?>>
                                            <?php echo sprintf('%02d', $d); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <!-- Month -->
                            <div>
                                <select id="dob_month" name="dob_month" class="block w-full rounded-xl border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors bg-white">
                                    <option value="">Month</option>
                                    <?php
                                    $months = [
                                        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                                        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                                        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
                                    ];
                                    foreach ($months as $num => $label): ?>
                                        <option value="<?php echo sprintf('%02d', $num); ?>" <?php echo ((int) ($dobMonth ?? 0) === $num) ? 'selected' : ''; ?>>
                                            <?php echo sprintf('%02d - %s', $num, $label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Year -->
                            <div>
                                <select id="dob_year" name="dob_year" class="block w-full rounded-xl border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors bg-white">
                                    <option value="">Year</option>
                                    <?php
                                    $currYear = (int) date('Y');
                                    for ($y = $currYear; $y >= $currYear - 100; $y--): ?>
                                        <option value="<?php echo $y; ?>" <?php echo ((int) ($dobYear ?? 0) === $y) ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div id="calculatedAgeContainer" class="<?php echo empty($profile['age']) ? 'hidden' : ''; ?> mt-1 text-xs text-gray-500">
                            Calculated age: <strong id="calculatedAgeVal" class="text-gray-700"><?php echo (int) ($profile['age'] ?? 0); ?> years</strong>
                        </div>
                    </div>

                    <!-- Gender (Male and Female only) -->
                    <div>
                        <label for="gender" class="block text-sm font-semibold text-gray-700 mb-1">
                            Gender
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-gender-intersex text-lg"></i>
                            </div>
                            <select id="gender" name="gender" class="pl-10 block w-full rounded-xl border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors bg-white">
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo ($profile['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($profile['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                    </div>

                    <!-- Phone Number (Dial Code + Local Number) -->
                    <div>
                        <label for="phone_number" class="block text-sm font-semibold text-gray-700 mb-1">
                            Phone Number
                        </label>
                        <div class="flex rounded-xl overflow-hidden border border-gray-200 focus-within:ring-2 focus-within:ring-primary focus-within:border-transparent transition-all">
                            <select id="country_code" name="country_code" class="w-28 sm:w-32 bg-gray-50 border-0 border-r border-gray-200 py-2.5 pl-3 pr-2 text-xs sm:text-sm font-medium text-gray-700 focus:outline-none focus:ring-0">
                                <?php foreach ($countries as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['dial_code'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($phoneDialCode === $c['dial_code']) ? 'selected' : ''; ?>>
                                        <?php echo $c['flag'] . ' ' . htmlspecialchars($c['dial_code'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="tel" id="phone_number" name="phone_number" maxlength="25" placeholder="1XXX-XXXXXX"
                                   value="<?php echo htmlspecialchars($phoneLocalNumber ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   class="flex-1 border-0 py-2.5 px-3 text-sm focus:outline-none focus:ring-0">
                        </div>
                    </div>

                    <!-- Country (195 Countries with Flags) -->
                    <div>
                        <label for="country" class="block text-sm font-semibold text-gray-700 mb-1">
                            Country
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-globe-hemisphere-west text-lg"></i>
                            </div>
                            <select id="country" name="country" class="pl-10 block w-full rounded-xl border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors bg-white">
                                <option value="">Select Country</option>
                                <?php 
                                $userCountry = $profile['country'] ?? '';
                                foreach ($countries as $c): 
                                    $isSelected = (strcasecmp($userCountry, $c['name']) === 0 || strcasecmp($userCountry, $c['code']) === 0);
                                ?>
                                    <option value="<?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?>" data-code="<?php echo $c['code']; ?>" data-dial="<?php echo htmlspecialchars($c['dial_code'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isSelected ? 'selected' : ''; ?>>
                                        <?php echo $c['flag'] . ' ' . htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- City -->
                    <div>
                        <label for="city" class="block text-sm font-semibold text-gray-700 mb-1">
                            City / District
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="ph ph-buildings text-lg"></i>
                            </div>
                            <input type="text" id="city" name="city" maxlength="100" placeholder="e.g. Dhaka"
                                   value="<?php echo htmlspecialchars($profile['city'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                   class="pl-10 block w-full rounded-xl border border-gray-200 py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                        </div>
                    </div>

                    <!-- Address (Full width) -->
                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-semibold text-gray-700 mb-1">
                            Full Address / Street Location
                        </label>
                        <div class="relative">
                            <textarea id="address" name="address" rows="3" maxlength="1000" placeholder="Enter house no, street, postal code..."
                                      class="block w-full rounded-xl border border-gray-200 p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"><?php echo htmlspecialchars($profile['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl shadow-xs text-sm font-semibold text-white bg-primary hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all">
                        <i class="ph ph-floppy-disk text-lg"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script nonce="<?php echo csp_nonce(); ?>">
document.addEventListener('DOMContentLoaded', function() {
    // 1. SweetAlert2 Flash Popups
    <?php if (!empty($flashSuccess)): ?>
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: <?php echo json_encode($flashSuccess, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
        confirmButtonColor: '#0d9488',
        timer: 3500,
        timerProgressBar: true
    });
    <?php endif; ?>

    <?php if (!empty($flashError)): ?>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: <?php echo json_encode($flashError, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
        confirmButtonColor: '#0d9488'
    });
    <?php endif; ?>

    // 2. Avatar Upload Trigger & Client-side Validation
    var uploadBtn = document.getElementById('uploadPicBtn');
    var fileInput = document.getElementById('profile_picture');

    if (uploadBtn && fileInput) {
        uploadBtn.addEventListener('click', function() {
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            if (fileInput.files && fileInput.files[0]) {
                var file = fileInput.files[0];

                // Size limit: 2MB (2,097,152 bytes)
                if (file.size > 2097152) {
                    Swal.fire({
                        title: 'File Too Large',
                        text: 'Profile picture must be less than 2MB in size.',
                        icon: 'error',
                        confirmButtonColor: '#0d9488'
                    });
                    fileInput.value = '';
                    return;
                }

                // Format validation
                var allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    Swal.fire({
                        title: 'Invalid Format',
                        text: 'Only JPG, PNG, and WEBP image formats are supported.',
                        icon: 'error',
                        confirmButtonColor: '#0d9488'
                    });
                    fileInput.value = '';
                    return;
                }

                document.getElementById('profilePicForm').submit();
            }
        });
    }

    // 3. Remove Avatar SweetAlert2 Confirmation
    var removeBtn = document.getElementById('removePicBtn');
    if (removeBtn) {
        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Remove Profile Picture?',
                text: 'Are you sure you want to remove your profile picture?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, remove it'
            }).then(function(result) {
                if (result.isConfirmed) {
                    document.getElementById('removePicForm').submit();
                }
            });
        });
    }

    // 4. Country Auto-Detection & Phone Code Sync
    var countrySelect = document.getElementById('country');
    var countryCodeSelect = document.getElementById('country_code');

    if (countrySelect && !countrySelect.value) {
        var userTz = '';
        try {
            userTz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
        } catch (e) {}

        var detectedCountry = 'Bangladesh'; // default fallback
        if (userTz.indexOf('Dhaka') !== -1) {
            detectedCountry = 'Bangladesh';
        } else if (userTz.indexOf('Riyadh') !== -1) {
            detectedCountry = 'Saudi Arabia';
        } else if (userTz.indexOf('Dubai') !== -1) {
            detectedCountry = 'United Arab Emirates';
        } else if (userTz.indexOf('London') !== -1) {
            detectedCountry = 'United Kingdom';
        } else if (userTz.indexOf('New_York') !== -1 || userTz.indexOf('Los_Angeles') !== -1 || userTz.indexOf('Chicago') !== -1) {
            detectedCountry = 'United States';
        } else if (userTz.indexOf('Kolkata') !== -1) {
            detectedCountry = 'India';
        }

        for (var i = 0; i < countrySelect.options.length; i++) {
            if (countrySelect.options[i].value === detectedCountry) {
                countrySelect.selectedIndex = i;
                var dial = countrySelect.options[i].getAttribute('data-dial');
                if (dial && countryCodeSelect && (!countryCodeSelect.value || countryCodeSelect.value === '+880')) {
                    countryCodeSelect.value = dial;
                }
                break;
            }
        }
    }

    // Sync phone dialing code when country is selected
    if (countrySelect && countryCodeSelect) {
        countrySelect.addEventListener('change', function() {
            var selectedOption = countrySelect.options[countrySelect.selectedIndex];
            var dial = selectedOption ? selectedOption.getAttribute('data-dial') : null;
            if (dial) {
                countryCodeSelect.value = dial;
            }
        });
    }

    // 5. Dynamic Age Calculation on Birthday Dropdowns Change
    var daySelect   = document.getElementById('dob_day');
    var monthSelect = document.getElementById('dob_month');
    var yearSelect  = document.getElementById('dob_year');
    var ageContainer = document.getElementById('calculatedAgeContainer');
    var ageVal       = document.getElementById('calculatedAgeVal');

    function calculateDynamicAge() {
        if (!daySelect || !monthSelect || !yearSelect) return;
        var d = parseInt(daySelect.value, 10);
        var m = parseInt(monthSelect.value, 10);
        var y = parseInt(yearSelect.value, 10);

        if (d && m && y) {
            var birthDate = new Date(y, m - 1, d);
            var today = new Date();
            var age = today.getFullYear() - birthDate.getFullYear();
            var mDiff = today.getMonth() - birthDate.getMonth();
            if (mDiff < 0 || (mDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            if (age >= 0 && age <= 120) {
                if (ageVal) ageVal.textContent = age + ' years';
                if (ageContainer) ageContainer.classList.remove('hidden');
            }
        }
    }

    if (daySelect && monthSelect && yearSelect) {
        daySelect.addEventListener('change', calculateDynamicAge);
        monthSelect.addEventListener('change', calculateDynamicAge);
        yearSelect.addEventListener('change', calculateDynamicAge);
    }
});
</script>

<?php require __DIR__ . '/layouts/footer.php'; ?>
