/**
 * Sidebar toggle functionality for mobile/tablet
 */

document.addEventListener("DOMContentLoaded", function() {
    // Function to add sidebar toggle to each video container
    function addSidebarToggleToVideo(videoTopControls) {
        // Check if button already exists in this container
        let sidebarToggle = videoTopControls.querySelector('.sidebar-toggle-btn');
        
        if (!sidebarToggle) {
            // Create sidebar toggle button
            sidebarToggle = document.createElement("button");
            sidebarToggle.className = "sidebar-toggle-btn";
            sidebarToggle.setAttribute('title', 'Menu');
            sidebarToggle.setAttribute('aria-label', 'Toggle Sidebar');
            
            // Add menu icon
            const themeUri = (typeof puna_tiktok_ajax !== 'undefined' && puna_tiktok_ajax.theme_uri) ? puna_tiktok_ajax.theme_uri : '/wp-content/themes/puna-tiktok';
            const iconUrl = themeUri + '/assets/images/icons/menu.svg';
            const icon = document.createElement('img');
            icon.src = iconUrl;
            icon.className = 'icon-svg';
            icon.alt = 'Menu';
            sidebarToggle.appendChild(icon);
            
            // Insert before volume control
            const volumeControl = videoTopControls.querySelector('.volume-control-wrapper');
            if (volumeControl) {
                videoTopControls.insertBefore(sidebarToggle, volumeControl);
            } else {
                videoTopControls.appendChild(sidebarToggle);
            }
        }
        
        return sidebarToggle;
    }
    
    // Add toggle button to all video containers
    const allVideoTopControls = document.querySelectorAll('.video-top-controls');
    let sidebarToggle = null;
    
    if (allVideoTopControls.length > 0) {
        allVideoTopControls.forEach(function(videoTopControls) {
            const toggle = addSidebarToggleToVideo(videoTopControls);
            if (!sidebarToggle) {
                sidebarToggle = toggle; // Use first one for event listeners
            }
        });
    }
    
    // If no video containers found, create a global toggle
    if (!sidebarToggle) {
        sidebarToggle = document.createElement("button");
        sidebarToggle.className = "sidebar-toggle-btn";
        sidebarToggle.setAttribute('title', 'Menu');
        sidebarToggle.setAttribute('aria-label', 'Toggle Sidebar');
        
        // Add menu icon
        const themeUri = (typeof puna_tiktok_ajax !== 'undefined' && puna_tiktok_ajax.theme_uri) ? puna_tiktok_ajax.theme_uri : '/wp-content/themes/puna-tiktok';
        const iconUrl = themeUri + '/assets/images/icons/menu.svg';
        const icon = document.createElement('img');
        icon.src = iconUrl;
        icon.className = 'icon-svg';
        icon.alt = 'Menu';
        sidebarToggle.appendChild(icon);
        
        // Try to add to taxonomy header first, otherwise add to body
        const taxonomyHeader = document.querySelector('.taxonomy-header');
        if (taxonomyHeader) {
            // Insert at the beginning of taxonomy header
            taxonomyHeader.insertBefore(sidebarToggle, taxonomyHeader.firstChild);
        } else {
        document.body.appendChild(sidebarToggle);
        }
    }
    
    // Use event delegation for all sidebar toggle buttons
    document.addEventListener("click", function(e) {
        const clickedToggle = e.target.closest('.sidebar-toggle-btn');
        if (clickedToggle) {
            e.preventDefault();
            e.stopPropagation();
            
            const sidebar = document.querySelector(".sidebar");
            if (sidebar) {
                sidebar.classList.toggle("collapsed");
                document.body.classList.toggle("sidebar-open");
            }
        }
    });
    
    // Close sidebar when clicking outside on mobile/tablet
    document.addEventListener("click", function(e) {
        const sidebar = document.querySelector(".sidebar");
        const allToggleButtons = document.querySelectorAll('.sidebar-toggle-btn');
        const clickedToggle = e.target.closest('.sidebar-toggle-btn');
        
        // Don't close if clicking on any toggle button
        if (clickedToggle) {
            return;
        }
        
        if (sidebar && !sidebar.contains(e.target)) {
            // Check if click is outside all toggle buttons
            let isOutsideAllToggles = true;
            allToggleButtons.forEach(function(btn) {
                if (btn.contains(e.target)) {
                    isOutsideAllToggles = false;
                }
            });
            
            if (isOutsideAllToggles && window.innerWidth <= 1024 && sidebar.classList.contains("collapsed")) {
                sidebar.classList.remove("collapsed");
                document.body.classList.remove("sidebar-open");
            }
        }
    });
    
    // Responsive check - apply to all toggle buttons
    function checkResponsive() {
        const sidebar = document.querySelector(".sidebar");
        const allToggleButtons = document.querySelectorAll('.sidebar-toggle-btn');
        
        // Show toggle button when sidebar is hidden (mobile/tablet)
        if (window.innerWidth <= 1024) {
            allToggleButtons.forEach(function(btn) {
                btn.style.display = "flex";
            });
            if (sidebar) {
                sidebar.classList.add("mobile-sidebar");
            }
        } else {
            allToggleButtons.forEach(function(btn) {
                btn.style.display = "none";
            });
            if (sidebar) {
                sidebar.classList.remove("mobile-sidebar", "collapsed");
            }
            document.body.classList.remove("sidebar-open");
        }
    }
    
    // Watch for new video containers being added (for infinite scroll)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    const videoTopControls = node.querySelector ? node.querySelector('.video-top-controls') : null;
                    if (videoTopControls) {
                        addSidebarToggleToVideo(videoTopControls);
                        checkResponsive();
                    }
                    // Also check if the node itself is a video-top-controls
                    if (node.classList && node.classList.contains('video-top-controls')) {
                        addSidebarToggleToVideo(node);
                        checkResponsive();
                    }
                }
            });
        });
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    window.addEventListener("resize", checkResponsive);
    // Run immediately
    setTimeout(function() {
        checkResponsive();
    }, 100);
});

