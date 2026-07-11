<?php 
// Global auth/db used by components
global $auth, $db; 

// Include the universal teacher sidebar
require __DIR__ . '/components/teacher_sidebar.php';
?>

<!-- Main Content Area Wrapper (to match structure) -->
<div class="flex-1 flex flex-col h-full overflow-hidden w-full bg-bgLight">

    <?php 
    // Include the universal teacher navbar
    require __DIR__ . '/components/teacher_navbar.php'; 
    ?>

    <!-- Script for interactivity -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const openSidebarBtn = document.getElementById('openSidebar');
            const closeSidebarBtn = document.getElementById('closeSidebar');
            const overlay = document.getElementById('sidebarOverlay');

            // Handle Mobile Sidebar Open
            if (openSidebarBtn) {
                openSidebarBtn.addEventListener('click', () => {
                    sidebar.classList.remove('-translate-x-full');
                    overlay.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                });
            }

            // Handle Mobile Sidebar Close
            const closeSidebar = () => {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            };

            if (closeSidebarBtn) {
                closeSidebarBtn.addEventListener('click', closeSidebar);
            }
            if (overlay) {
                overlay.addEventListener('click', closeSidebar);
            }

            // Profile Dropdown Logic
            const profileBtn = document.getElementById('profileDropdownBtn');
            const profileMenu = document.getElementById('profileDropdownMenu');

            if (profileBtn && profileMenu) {
                profileBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    profileMenu.classList.toggle('hidden');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!profileMenu.contains(e.target) && !profileBtn.contains(e.target)) {
                        profileMenu.classList.add('hidden');
                    }
                });
            }
        });

        // Function to Toggle Sub-menus in Sidebar
        function toggleMenu(menuId, iconId) {
            const menu = document.getElementById(menuId);
            const icon = document.getElementById(iconId);

            if (menu.classList.contains('hidden')) {
                // Open menu
                menu.classList.remove('hidden');
                menu.classList.add('flex');
                icon.classList.add('rotate-180');
            } else {
                // Close menu
                menu.classList.add('hidden');
                menu.classList.remove('flex');
                icon.classList.remove('rotate-180');
            }
        }
    </script>