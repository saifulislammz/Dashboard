<?php 
// Global auth/db used by components
global $auth, $db; 

// Include the universal student sidebar
require __DIR__ . '/components/student_sidebar.php';
?>

<!-- Main Content Area Wrapper (to match structure) -->
<div class="flex-1 flex flex-col h-full overflow-hidden w-full bg-bgLight">

    <?php 
    // Include the universal student navbar
    require __DIR__ . '/components/student_navbar.php'; 
    ?>

    <!-- Script for interactivity -->
    <script nonce="<?php echo csp_nonce(); ?>">
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

        // Global Function to Toggle Sub-menus in Sidebar
        window.toggleMenu = function(menuId, iconId) {
            const menu = document.getElementById(menuId);
            const icon = iconId ? document.getElementById(iconId) : null;
            if (!menu) return;

            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                menu.classList.add('flex');
                if (icon) icon.classList.add('rotate-180');
            } else {
                menu.classList.add('hidden');
                menu.classList.remove('flex');
                if (icon) icon.classList.remove('rotate-180');
            }
        };

        // Event delegation for any submenus
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-toggle-menu]');
            if (!btn) return;

            const menuId = btn.getAttribute('data-toggle-menu');
            const iconId = btn.getAttribute('data-toggle-icon');
            if (menuId) {
                e.preventDefault();
                window.toggleMenu(menuId, iconId);
            }
        });
    </script>