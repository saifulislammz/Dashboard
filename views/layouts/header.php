<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle ?? 'Dashboard'); ?> - Rahen Azat Institute</title>
    <link rel="icon" type="image/png" href="/images/favicon.png">
    <link href="/css/app.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="/css/universal.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Phosphor Icons for UI Icons (Local version to fix CDN loading issues) -->
    <script src="/phosphor-icons/index.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    <!-- Alpine.js for dynamic UI components (like quiz builder) -->
    <script defer src="/js/alpine.min.js"></script>
    
    <!-- SweetAlert2 for Universal Modals -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Global function to handle form submission confirmations
        function handleConfirm(event, message) {
            event.preventDefault();
            const form = event.target || event.currentTarget;
            Swal.fire({
                title: 'Confirmation',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0F766E', // var(--color-primary-green)
                cancelButtonColor: '#EF4444', // var(--color-red)
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel',
                background: '#ffffff',
                color: '#1f2937'
            }).then((result) => {
                if (result.isConfirmed && form) {
                    form.submit();
                }
            });
            return false;
        }

        // Global async function for custom logic confirmations (e.g. Alpine.js)
        function confirmAsync(message) {
            return Swal.fire({
                title: 'Confirmation',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0F766E',
                cancelButtonColor: '#EF4444',
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel',
                background: '#ffffff',
                color: '#1f2937'
            }).then(result => result.isConfirmed);
        }
    </script>
</head>

<body class="bg-bgLight text-brandText flex h-screen overflow-hidden">