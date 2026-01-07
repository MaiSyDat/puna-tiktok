/**
 * Video Admin JavaScript
 * Handles video upload in admin area
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        const dropzone = $('#videoUploadDropzone');
        const fileInput = $('#videoFileInput');
        const selectBtn = $('#selectVideoBtn');
        const videoPreview = $('#videoPreview');
        const videoPreviewContainer = $('#videoPreviewContainer');
        const videoInfo = $('#videoInfo');
        const uploadProgress = $('#uploadProgress');
        const progressFill = $('#progressFill');
        const progressText = $('#progressText');
        const youtubeUrlInput = $('#youtube_url_input');
        
        // Local upload elements
        const localFileInput = $('#puna_video_file');
        const videoPreviewLocal = $('#videoPreviewLocal');
        const videoPreviewContainerLocal = $('#videoPreviewContainerLocal');
        const videoInfoLocal = $('#videoInfoLocal');
        
        let selectedFile = null;

        // Tab switching
        $('.video-upload-tab-link').on('click', function(e) {
            e.preventDefault();
            const targetTab = $(this).data('tab');
            
            // Update active tab link
            $('.video-upload-tab-link').removeClass('active');
            $(this).addClass('active');
            
            // Update active tab content
            $('.video-upload-tab-content').removeClass('active');
            $('#tab-' + targetTab).addClass('active');
        });

        // Select video button
        selectBtn.on('click', function(e) {
            e.preventDefault();
            fileInput.click();
        });

        // File input change
        fileInput.on('change', function(e) {
            handleFileSelect(e.target.files[0]);
        });

        // Drag and drop
        dropzone.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.addClass('dragover');
        });

        dropzone.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.removeClass('dragover');
        });

        dropzone.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.removeClass('dragover');
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0 && files[0].type.startsWith('video/')) {
                handleFileSelect(files[0]);
            }
        });

        function handleFileSelect(file) {
            if (!file || !file.type.startsWith('video/')) {
                return;
            }

            selectedFile = file;
            
            // Show preview
            const videoURL = URL.createObjectURL(file);
            videoPreview.attr('src', videoURL);
            videoPreviewContainer.show();
            
            // Show file info
            const fileSize = formatFileSize(file.size);
            
            const strings = puna_tiktok_video_admin?.strings || {};
            const fileInfoLabel = strings.file_information || 'File Information:';
            const nameLabel = strings.name || 'Name:';
            const sizeLabel = strings.size || 'Size:';
            const typeLabel = strings.type || 'Type:';
            
            videoInfo.html(`
                <strong>${fileInfoLabel}</strong>
                <div>${nameLabel} ${file.name}</div>
                <div>${sizeLabel} ${fileSize}</div>
                <div>${typeLabel} ${file.type}</div>
            `);
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // YouTube URL validation and preview
        function extractYouTubeId(url) {
            if (!url) return '';
            
            url = url.trim();
            
            // Patterns for YouTube URLs
            const patterns = [
                /youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/,
                /youtu\.be\/([a-zA-Z0-9_-]+)/,
                /youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/
            ];
            
            for (let pattern of patterns) {
                const match = url.match(pattern);
                if (match) {
                    return match[1];
                }
            }
            
            return '';
        }

        // Show/hide YouTube preview
        function updateYouTubePreview() {
            const url = youtubeUrlInput.val();
            const youtubeId = extractYouTubeId(url);
            const preview = $('.youtube-preview');
            
            if (youtubeId && url) {
                if (!preview.length) {
                    // Create preview if it doesn't exist
                    const previewHtml = `
                        <div class="youtube-preview active">
                            <h4>${puna_tiktok_video_admin?.strings?.current_youtube || 'Current YouTube Video:'}</h4>
                            <p><strong>${puna_tiktok_video_admin?.strings?.video_id || 'Video ID:'}</strong> ${youtubeId}</p>
                            <p><strong>${puna_tiktok_video_admin?.strings?.preview || 'Preview:'}</strong> <a href="${url}" target="_blank">${url}</a></p>
                        </div>
                    `;
                    youtubeUrlInput.closest('.youtube-input-section').append(previewHtml);
                } else {
                    preview.find('p').eq(0).html(`<strong>${puna_tiktok_video_admin?.strings?.video_id || 'Video ID:'}</strong> ${youtubeId}`);
                    preview.find('p').eq(1).html(`<strong>${puna_tiktok_video_admin?.strings?.preview || 'Preview:'}</strong> <a href="${url}" target="_blank">${url}</a>`);
                    preview.addClass('active');
                }
            } else if (url && !youtubeId) {
                // Invalid URL
                if (preview.length) {
                    preview.removeClass('active');
                }
            } else if (!url) {
                // Empty URL
                if (preview.length && !preview.hasClass('active')) {
                    // Only hide if it was dynamically created
                    const existingPreview = $('.youtube-preview').not('.active');
                    if (existingPreview.length) {
                        existingPreview.remove();
                    }
                }
            }
        }

        // YouTube URL input change handler
        youtubeUrlInput.on('input', function() {
            updateYouTubePreview();
        });

        // Initial YouTube preview update
        if (youtubeUrlInput.val()) {
            updateYouTubePreview();
        }

        // Local upload dropzone
        const localDropzone = $('#videoUploadDropzoneLocal');
        
        // Local file input change handler
        if (localFileInput.length) {
            localFileInput.on('change', function(e) {
                const file = e.target.files[0];
                if (file && file.type.startsWith('video/')) {
                    handleLocalFileSelect(file);
                }
            });
            
            // Click on dropzone to trigger file input
            if (localDropzone.length) {
                localDropzone.on('click', function(e) {
                    // Don't trigger if clicking the button
                    if (!$(e.target).closest('label.button').length) {
                        localFileInput.click();
                    }
                });
                
                // Drag and drop for local upload
                localDropzone.on('dragover', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    localDropzone.addClass('dragover');
                });
                
                localDropzone.on('dragleave', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    localDropzone.removeClass('dragover');
                });
                
                localDropzone.on('drop', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    localDropzone.removeClass('dragover');
                    
                    const files = e.originalEvent.dataTransfer.files;
                    if (files.length > 0 && files[0].type.startsWith('video/')) {
                        // Create a FileList-like object and set it to the input
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(files[0]);
                        localFileInput[0].files = dataTransfer.files;
                        handleLocalFileSelect(files[0]);
                    }
                });
            }
        }

        function handleLocalFileSelect(file) {
            if (!file || !file.type.startsWith('video/')) {
                return;
            }

            // Show preview using blob URL
            const videoURL = URL.createObjectURL(file);
            videoPreviewLocal.attr('src', videoURL);
            videoPreviewContainerLocal.addClass('active').show();
            
            // Show file info
            const fileSize = formatFileSize(file.size);
            
            const strings = puna_tiktok_video_admin?.strings || {};
            const fileInfoLabel = strings.file_information || 'File Information:';
            const nameLabel = strings.name || 'Name:';
            const sizeLabel = strings.size || 'Size:';
            const typeLabel = strings.type || 'Type:';
            
            videoInfoLocal.html(`
                <strong>${fileInfoLabel}</strong>
                <div>${nameLabel} ${file.name}</div>
                <div>${sizeLabel} ${fileSize}</div>
                <div>${typeLabel} ${file.type}</div>
            `);
        }

        // Handle form submit - ensure video is uploaded or YouTube URL is provided
        $('#post').on('submit', function(e) {
            const activeTab = $('.video-upload-tab-link.active').data('tab');
            
            if (activeTab === 'upload') {
                // Check local file upload
                const localFile = localFileInput[0]?.files[0];
                const existingVideoUrl = $('input[name="video_url"]').val();
                
                if (!localFile && !existingVideoUrl) {
                    const confirmMessage = puna_tiktok_video_admin?.strings?.video_not_uploaded 
                        ? puna_tiktok_video_admin.strings.video_not_uploaded 
                        : 'No video file selected. Do you want to continue saving the post? Video will not be displayed until a file is uploaded.';
                    if (!confirm(confirmMessage)) {
                        e.preventDefault();
                        return false;
                    }
                }
            } else if (activeTab === 'youtube') {
                // Check YouTube URL
                const youtubeUrl = youtubeUrlInput.val();
                if (youtubeUrl) {
                    const youtubeId = extractYouTubeId(youtubeUrl);
                    if (!youtubeId) {
                        const confirmMessage = puna_tiktok_video_admin?.strings?.youtube_url_invalid 
                            ? puna_tiktok_video_admin.strings.youtube_url_invalid 
                            : 'Invalid YouTube URL. Do you want to continue saving the post?';
                        if (!confirm(confirmMessage)) {
                            e.preventDefault();
                            return false;
                        }
                    }
                }
            }
        });
    });

})(jQuery);

