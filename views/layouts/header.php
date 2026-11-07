<?php
if (!function_exists('generateCsrfToken')) {
    require_once __DIR__ . '/../../src/middleware/CsrfMiddleware.php';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle ?? 'Dashboard'); ?> - Rahe nazat Institute</title>
    <link rel="icon" type="image/png" href="/images/favicon.png">
    <link href="/css/app.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="/css/universal.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="/css/sweetalert2.min.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Phosphor Icons for UI Icons (Local version to fix CDN loading issues) -->
    <script nonce="<?php echo csp_nonce(); ?>" src="/phosphor-icons/index.js"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    <!-- Alpine.js for dynamic UI components (like quiz builder) -->
    <script nonce="<?php echo csp_nonce(); ?>" defer src="/js/alpine.min.js"></script>

    <!-- SweetAlert2 for Universal Modals (Local assets for offline reliability & CSP compliance) -->
    <script nonce="<?php echo csp_nonce(); ?>" src="/js/sweetalert2.all.min.js"></script>
    <script nonce="<?php echo csp_nonce(); ?>">
        // Ensure Swal alias exists
        if (typeof window.Swal === 'undefined' && typeof window.Sweetalert2 !== 'undefined') {
            window.Swal = window.Sweetalert2;
        }

        // Global Modal Helper Functions
        window.openModal = function(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.remove('hidden');
            if (!el.classList.contains('flex')) {
                el.classList.add('flex');
            }
            el.style.display = 'flex';
        };

        window.closeModal = function(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.add('hidden');
            el.classList.remove('flex');
            el.style.display = 'none';
        };

        // Global function to handle form submission confirmations
        window.handleConfirm = function(event, message) {
            if (event && event.preventDefault) {
                event.preventDefault();
            }
            const target = event ? (event.target || event.currentTarget) : null;
            let form = null;
            if (target) {
                if (target.tagName === 'FORM') {
                    form = target;
                } else if (target.closest) {
                    form = target.closest('form');
                }
            }

            const swalInstance = (typeof window.Swal !== 'undefined' ? window.Swal : (typeof window.Sweetalert2 !== 'undefined' ? window.Sweetalert2 : null));

            if (!swalInstance) {
                if (confirm(message)) {
                    if (form) {
                        form.dataset.confirmed = 'true';
                        form.submit();
                    }
                }
                return false;
            }

            swalInstance.fire({
                title: 'Confirmation',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444', // Danger red for delete/destructive action
                cancelButtonColor: '#6B7280', // Neutral gray
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                focusCancel: true,
                background: '#ffffff',
                color: '#1f2937',
                backdrop: 'rgba(15, 23, 42, 0.6)'
            }).then((result) => {
                if (result.isConfirmed && form) {
                    form.dataset.confirmed = 'true';
                    form.submit();
                }
            });
            return false;
        };

        // Global async function for custom logic confirmations (e.g. Alpine.js)
        window.confirmAsync = function(message) {
            const swalInstance = (typeof window.Swal !== 'undefined' ? window.Swal : (typeof window.Sweetalert2 !== 'undefined' ? window.Sweetalert2 : null));
            if (!swalInstance) {
                return Promise.resolve(confirm(message));
            }
            return swalInstance.fire({
                title: 'Confirmation',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0F766E',
                cancelButtonColor: '#EF4444',
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                background: '#ffffff',
                color: '#1f2937',
                backdrop: 'rgba(15, 23, 42, 0.6)'
            }).then(result => result.isConfirmed);
        };

        // CSP-Compliant Universal Event Delegation (replaces blocked inline event handlers)
        document.addEventListener('DOMContentLoaded', function() {
            // Document click interception for toggles, modals, and tabs
            document.addEventListener('click', function(e) {
                // 1. Data-toggle attribute (e.g. data-toggle="createForm")
                const toggleBtn = e.target.closest('[data-toggle]');
                if (toggleBtn) {
                    const targetId = toggleBtn.getAttribute('data-toggle');
                    const targetEl = document.getElementById(targetId);
                    if (targetEl) {
                        e.preventDefault();
                        targetEl.classList.toggle('hidden');
                    }
                    return;
                }

                // 2. Data-modal-open attribute
                const modalOpenBtn = e.target.closest('[data-modal-open]');
                if (modalOpenBtn) {
                    e.preventDefault();
                    window.openModal(modalOpenBtn.getAttribute('data-modal-open'));
                    return;
                }

                // 3. Data-modal-close attribute
                const modalCloseBtn = e.target.closest('[data-modal-close]');
                if (modalCloseBtn) {
                    e.preventDefault();
                    window.closeModal(modalCloseBtn.getAttribute('data-modal-close'));
                    return;
                }

                // 4. Change Email Modal Trigger (.change-email-btn)
                const changeEmailBtn = e.target.closest('.change-email-btn');
                if (changeEmailBtn) {
                    e.preventDefault();
                    const id = changeEmailBtn.getAttribute('data-change-email-id') || '';
                    const name = changeEmailBtn.getAttribute('data-change-email-name') || '';
                    const currentEmail = changeEmailBtn.getAttribute('data-change-email-current') || '';

                    const idField = document.getElementById('changeEmailUserId');
                    const nameField = document.getElementById('changeEmailTargetName');
                    const emailField = document.getElementById('changeEmailCurrentEmail');
                    const avatarField = document.getElementById('changeEmailAvatar');
                    const inputField = document.getElementById('changeEmailNewInput');

                    if (idField) idField.value = id;
                    if (nameField) nameField.textContent = name;
                    if (emailField) emailField.textContent = currentEmail;
                    if (avatarField && name.length > 0) {
                        avatarField.textContent = name.trim().charAt(0).toUpperCase();
                    }
                    if (inputField) {
                        inputField.value = '';
                        setTimeout(function() { inputField.focus(); }, 120);
                    }
                    window.openModal('changeEmailModal');
                    return;
                }

                // 5. Click outside changeEmailModal dialog to close
                if (e.target && e.target.id === 'changeEmailModal') {
                    window.closeModal('changeEmailModal');
                    return;
                }

                // 6. Intercept legacy onclick buttons blocked by strict CSP
                const legacyBtn = e.target.closest('[onclick]');
                if (legacyBtn) {
                    const oc = legacyBtn.getAttribute('onclick') || '';
                    // Check for classList.toggle('hidden')
                    const toggleMatch = oc.match(/getElementById\(['"]([^'"]+)['"]\)\.classList\.toggle\(['"]hidden['"]\)/);
                    if (toggleMatch) {
                        e.preventDefault();
                        const el = document.getElementById(toggleMatch[1]);
                        if (el) el.classList.toggle('hidden');
                        return;
                    }
                    // Check for openModal('xyz')
                    const openMatch = oc.match(/openModal\(['"]([^'"]+)['"]\)/);
                    if (openMatch) {
                        e.preventDefault();
                        window.openModal(openMatch[1]);
                        return;
                    }
                    // Check for closeModal('xyz')
                    const closeMatch = oc.match(/closeModal\(['"]([^'"]+)['"]\)/);
                    if (closeMatch) {
                        e.preventDefault();
                        window.closeModal(closeMatch[1]);
                        return;
                    }
                    // Check for openAssignModal(id, name, roleId)
                    const assignMatch = oc.match(/openAssignModal\(\s*(\d+)\s*,\s*['"]([^'"]+)['"]\s*,\s*(\d+)\s*\)/);
                    if (assignMatch && typeof window.openAssignModal === 'function') {
                        e.preventDefault();
                        window.openAssignModal(parseInt(assignMatch[1]), assignMatch[2], parseInt(assignMatch[3]));
                        return;
                    }
                    // Check for confirmDeleteRole(id, name, count)
                    const delRoleMatch = oc.match(/confirmDeleteRole\(\s*(\d+)\s*,\s*['"]([^'"]+)['"]\s*,\s*(\d+)\s*\)/);
                    if (delRoleMatch && typeof window.confirmDeleteRole === 'function') {
                        e.preventDefault();
                        window.confirmDeleteRole(parseInt(delRoleMatch[1]), delRoleMatch[2], parseInt(delRoleMatch[3]));
                        return;
                    }
                    // Check for switchTab('xyz')
                    const tabMatch = oc.match(/switchTab\(['"]([^'"]+)['"]\)/);
                    if (tabMatch && typeof window.switchTab === 'function') {
                        e.preventDefault();
                        window.switchTab(tabMatch[1]);
                        return;
                    }
                }
            });

            // Form submit interception for confirmations
            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (!form || form.tagName !== 'FORM') return;
                if (form.dataset.confirmed === 'true') {
                    return;
                }

                const confirmMsg = form.getAttribute('data-confirm');
                if (confirmMsg) {
                    e.preventDefault();
                    window.handleConfirm(e, confirmMsg);
                    return;
                }

                const onsub = form.getAttribute('onsubmit') || '';
                const legacyConfirmMatch = onsub.match(/handleConfirm\([^,]+,\s*['"]([^'"]+)['"]\)/);
                if (legacyConfirmMatch) {
                    e.preventDefault();
                    window.handleConfirm(e, legacyConfirmMatch[1]);
                    return;
                }
            });

            // Input auto-submit on clear
            document.addEventListener('input', function(e) {
                const input = e.target;
                if ((input.hasAttribute('data-auto-submit') || (input.getAttribute('oninput') || '').includes('this.form.submit()')) && input.value.trim() === '') {
                    const form = input.closest('form');
                    if (form) form.submit();
                }
            });
        });
    </script>
</head>

<body class="bg-bgLight text-brandText flex h-screen overflow-hidden">
