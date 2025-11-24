/**
 * Top Header functionality
 */

document.addEventListener("DOMContentLoaded", function() {
    const contactButton = document.getElementById('top-contact-button');
    const topHeader = document.querySelector('.fixed-top-container');
    
    if (contactButton) {
        contactButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const phoneNumber = contactButton.getAttribute('data-phone') || '0889736889';
            
            // Open phone dialer
            window.location.href = 'tel:' + phoneNumber;
        });
    }
    
    // Hide top header when comments panel is open
    if (topHeader) {
        function toggleTopHeader() {
            const commentsOverlay = document.querySelector('.comments-overlay.show');
            if (commentsOverlay) {
                topHeader.style.display = 'none';
            } else {
                topHeader.style.display = '';
            }
        }
        
        // Check on initial load
        toggleTopHeader();
        
        // Watch for changes in comments overlay
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    toggleTopHeader();
                }
            });
        });
        
        // Observe all comments overlays
        const commentsOverlays = document.querySelectorAll('.comments-overlay');
        commentsOverlays.forEach(function(overlay) {
            observer.observe(overlay, {
                attributes: true,
                attributeFilter: ['class']
            });
        });
        
        // Also observe when new overlays are added
        const overlayObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.classList && node.classList.contains('comments-overlay')) {
                        observer.observe(node, {
                            attributes: true,
                            attributeFilter: ['class']
                        });
                    }
                });
            });
        });
        
        overlayObserver.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
});

