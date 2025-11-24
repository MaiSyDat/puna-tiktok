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
        
        let selectedFile = null;
        let megaUploader = null;

        // Initialize Mega Uploader if available
        if (typeof window.PunaTikTokMegaUploader !== 'undefined' && puna_tiktok_video_admin?.mega) {
            try {
                megaUploader = new window.PunaTikTokMegaUploader(puna_tiktok_video_admin.mega);
            } catch (e) {
                // Failed to initialize Mega uploader
            }
        }

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
            const fileDuration = 'N/A'; // Can be extracted if needed
            
            videoInfo.html(`
                <strong>File Information:</strong>
                <div>Name: ${file.name}</div>
                <div>Size: ${fileSize}</div>
                <div>Type: ${file.type}</div>
            `);
            
            // Auto upload to MEGA if available
            if (megaUploader && puna_tiktok_video_admin?.mega) {
                uploadToMega(file);
            } else {
                // Show message that upload will happen on save
                videoInfo.append('<div style="margin-top: 10px; color: #d63638;"><strong>Note:</strong> Video will be uploaded when you save the post.</div>');
            }
        }

        async function uploadToMega(file) {
            if (!megaUploader) return;
            
            uploadProgress.show();
            progressFill.css('width', '0%');
            progressText.text('0%');
            
            try {
                const megaResult = await megaUploader.uploadFile(file, (uploaded, total) => {
                    const percent = Math.round((uploaded / total) * 100);
                    progressFill.css('width', percent + '%');
                    progressText.text(percent + '%');
                });
                
                // Store MEGA link in hidden fields
                if (megaResult?.link) {
                    $('input[name="mega_link"]').remove();
                    $('input[name="mega_node_id"]').remove();
                    $('input[name="video_url"]').remove();
                    
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'mega_link',
                        value: megaResult.link
                    }).appendTo('.puna-video-upload-admin');
                    
                    if (megaResult.nodeId) {
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'mega_node_id',
                            value: megaResult.nodeId
                        }).appendTo('.puna-video-upload-admin');
                    }
                    
                    videoInfo.append(`
                        <div style="margin-top: 10px; color: #00a32a;">
                            <strong>✓ Uploaded to MEGA successfully!</strong><br>
                            <a href="${megaResult.link}" target="_blank">${megaResult.link}</a>
                        </div>
                    `);
                }
                
                uploadProgress.hide();
            } catch (error) {
                uploadProgress.hide();
                // Error handling silently
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // Handle form submit - ensure video is uploaded
        $('#post').on('submit', function(e) {
            if (selectedFile && !$('input[name="mega_link"]').val() && !$('input[name="video_url"]').val()) {
                if (!confirm('Video has not been uploaded. Do you want to continue saving the post? Video will not be displayed until it is uploaded.')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    });

})(jQuery);

