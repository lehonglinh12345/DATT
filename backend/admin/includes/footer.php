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

        // Admin Image Lightbox (Phóng to ảnh)
        document.addEventListener('DOMContentLoaded', function() {
            const adminImages = document.querySelectorAll('.admin-table img, .preview-image');
            if (adminImages.length > 0) {
                const lightbox = document.createElement('div');
                lightbox.id = 'adminLightbox';
                lightbox.style.position = 'fixed';
                lightbox.style.inset = '0';
                lightbox.style.backgroundColor = 'rgba(0,0,0,0.85)';
                lightbox.style.zIndex = '9999';
                lightbox.style.display = 'flex';
                lightbox.style.alignItems = 'center';
                lightbox.style.justifyContent = 'center';
                lightbox.style.opacity = '0';
                lightbox.style.visibility = 'hidden';
                lightbox.style.transition = 'opacity 0.3s ease';
                lightbox.style.cursor = 'zoom-out';
                
                const lightboxImg = document.createElement('img');
                lightboxImg.style.maxWidth = '90%';
                lightboxImg.style.maxHeight = '90%';
                lightboxImg.style.objectFit = 'contain';
                lightboxImg.style.borderRadius = '8px';
                lightboxImg.style.boxShadow = '0 10px 30px rgba(0,0,0,0.5)';
                lightbox.appendChild(lightboxImg);
                document.body.appendChild(lightbox);
                
                adminImages.forEach(img => {
                    img.style.cursor = 'zoom-in';
                    img.addEventListener('click', function(e) {
                        e.stopPropagation();
                        lightboxImg.src = this.src;
                        lightbox.style.opacity = '1';
                        lightbox.style.visibility = 'visible';
                    });
                });
                
                lightbox.addEventListener('click', function() {
                    lightbox.style.opacity = '0';
                    setTimeout(() => { lightbox.style.visibility = 'hidden'; }, 300);
                });
            }
        });
    </script>
</body>
</html>
