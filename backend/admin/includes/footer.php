            </div> <!-- End admin-content -->
        </main> <!-- End admin-main -->
    </div> <!-- End admin-wrapper -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var sidebarToggle = document.getElementById('adminSidebarToggle') || document.querySelector('.mobile-sidebar-toggle');
            var adminSidebar = document.getElementById('adminSidebar');

            // Ensure there is an overlay for mobile sidebar interactions
            var adminOverlay = document.getElementById('adminSidebarOverlay');
            if (!adminOverlay) {
                adminOverlay = document.createElement('div');
                adminOverlay.id = 'adminSidebarOverlay';
                adminOverlay.style.position = 'fixed';
                adminOverlay.style.inset = '0';
                adminOverlay.style.background = 'rgba(0,0,0,0.35)';
                adminOverlay.style.opacity = '0';
                adminOverlay.style.visibility = 'hidden';
                adminOverlay.style.pointerEvents = 'none';
                adminOverlay.style.transition = 'opacity 0.25s ease, visibility 0.25s ease';
                adminOverlay.style.zIndex = '90';
                document.body.appendChild(adminOverlay);
            }

            function openSidebar() {
                if (adminSidebar) adminSidebar.classList.add('open');
                adminOverlay.style.opacity = '1';
                adminOverlay.style.visibility = 'visible';
                adminOverlay.style.pointerEvents = 'auto';
                document.body.style.overflow = 'hidden';
                if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'true');
            }

            function closeSidebar() {
                if (adminSidebar) adminSidebar.classList.remove('open');
                adminOverlay.style.opacity = '0';
                adminOverlay.style.visibility = 'hidden';
                adminOverlay.style.pointerEvents = 'none';
                document.body.style.overflow = '';
                if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'false');
            }

            if (sidebarToggle && adminSidebar) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (adminSidebar.classList.contains('open')) {
                        closeSidebar();
                    } else {
                        openSidebar();
                    }
                });

                // Close sidebar when clicking outside on tablets/mobiles
                document.addEventListener('click', function(e) {
                    if (window.innerWidth <= 992) {
                        if (!adminSidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                            closeSidebar();
                        }
                    }
                });

                // Close via overlay click
                adminOverlay.addEventListener('click', function() { closeSidebar(); });

                // Close on Escape key
                document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeSidebar(); });
            }
        });
    </script>
    <script src="js/ckeditor-config.js"></script>
    <script src="js/admin-export.js"></script>
    <script src="js/admin-instant-search.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            enableInstantSearch('productsSearchInput', 'productsTable');
            enableInstantSearch('quotesSearchInput', 'quotesTable');
            enableInstantSearch('messagesSearchInput', 'messagesTable');
        });
    </script>
</body>
</html>
