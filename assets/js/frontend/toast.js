/**
 * Toast Notification System
 * Core functions for displaying user notifications
 */

(function() {
    'use strict';

    // Toast container
    let toastContainer = null;
    
    // Toast messages (loaded from PHP)
    let toastMessages = {};

    /**
     * Initialize toast container
     */
    function initToastContainer() {
        if (toastContainer) return;
        
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        toastContainer.setAttribute('aria-live', 'polite');
        toastContainer.setAttribute('aria-atomic', 'true');
        document.body.appendChild(toastContainer);
    }
    
    /**
     * Set toast messages (called from PHP via localized script)
     */
    function setToastMessages(messages) {
        toastMessages = messages || {};
    }
    
    /**
     * Get toast message by key
     */
    function getToastMessage(key, defaultMessage = '') {
        return toastMessages[key] || defaultMessage || key;
    }

    /**
     * Show toast notification
     * @param {string} messageOrKey - Message to display or message key from toastMessages
     * @param {string} type - Type: 'success', 'error', 'info', 'warning'
     * @param {number} duration - Duration in milliseconds (default: 3000)
     * @param {object} options - Additional options
     */
    function showToast(messageOrKey, type = 'info', duration = 3000, options = {}) {
        // If messageOrKey is a key in toastMessages, use it; otherwise use as direct message
        const message = getToastMessage(messageOrKey, messageOrKey);
        
        if (!message) return;
        
        initToastContainer();
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.setAttribute('role', 'alert');
        
        // Close button
        const closeBtn = document.createElement('button');
        closeBtn.className = 'toast-close';
        const closeText = (typeof puna_tiktok_ajax !== 'undefined' && puna_tiktok_ajax.i18n && puna_tiktok_ajax.i18n.close) 
            ? puna_tiktok_ajax.i18n.close 
            : 'Close';
        closeBtn.setAttribute('aria-label', closeText);
        closeBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 4L4 12M4 4l8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
        closeBtn.onclick = () => removeToast(toast);
        
        // Content
        const content = document.createElement('div');
        content.className = 'toast-content';
        content.textContent = message;
        
        // Build toast
        toast.appendChild(content);
        toast.appendChild(closeBtn);
        
        // Add to container
        toastContainer.appendChild(toast);
        
        // Auto remove
        if (duration > 0) {
            setTimeout(() => {
                removeToast(toast);
            }, duration);
        }
        
        return toast;
    }

    /**
     * Remove toast
     */
    function removeToast(toast) {
        if (!toast?.parentNode) return;
        
        toast.classList.add('slide-out');
        setTimeout(() => toast.remove(), 300);
    }

    /**
     * Show success toast
     */
    function showSuccess(message, duration = 3000) {
        return showToast(message, 'success', duration);
    }

    /**
     * Show error toast
     */
    function showError(message, duration = 4000) {
        return showToast(message, 'error', duration);
    }

    /**
     * Show info toast
     */
    function showInfo(message, duration = 3000) {
        return showToast(message, 'info', duration);
    }

    /**
     * Show warning toast
     */
    function showWarning(message, duration = 3500) {
        return showToast(message, 'warning', duration);
    }

    // Export to global scope
    window.Toast = {
        show: showToast,
        success: showSuccess,
        error: showError,
        info: showInfo,
        warning: showWarning,
        setMessages: setToastMessages,
        getMessage: getToastMessage
    };

    // Initialize toast container and load messages
    function initOnReady() {
        initToastContainer();
        loadMessages();
    }
    
    // Load messages from localized script
    function loadMessages() {
        if (typeof puna_tiktok_ajax !== 'undefined' && puna_tiktok_ajax.toast_messages) {
            setToastMessages(puna_tiktok_ajax.toast_messages);
        }
    }

    // Initialize immediately if DOM is ready, otherwise wait
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initOnReady);
    } else {
        initOnReady();
    }
    
    // Retry loading messages after a short delay (in case core.js loads after)
    setTimeout(loadMessages, 100);
})();

