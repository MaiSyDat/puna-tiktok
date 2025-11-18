/**
 * Upload page functionality
 */

document.addEventListener("DOMContentLoaded", function() {
    if (!document.querySelector('.upload-page-wrapper')) return;

    const dropZone = document.getElementById('uploadDropZone');
    const fileInput = document.getElementById('videoFileInput');
    const selectBtn = document.getElementById('selectVideoBtn');
    const step1 = document.getElementById('uploadStep1');
    const step2 = document.getElementById('uploadStep2');
    const videoPreview = document.getElementById('videoPreview');
    const previewPlaceholder = document.getElementById('previewPlaceholder');
    const descriptionInput = document.getElementById('videoDescription');
    const charCount = document.getElementById('charCount');
    const videoCategorySelect = document.getElementById('videoCategory');
    const publishBtn = document.getElementById('publishVideoBtn');
    const backToStep1Btn = document.getElementById('backToStep1Btn');
    const uploadFileInfo = document.getElementById('uploadFileInfo');
    const fileName = document.getElementById('fileName');
    const uploadProgressFill = document.getElementById('uploadProgressFill');
    const uploadProgressText = document.getElementById('uploadProgressText');
    const uploadPercentage = document.getElementById('uploadPercentage');
    const uploadDuration = document.getElementById('uploadDuration');
    const cancelUploadBtn = document.getElementById('cancelUploadBtn');
    const uploadLoadingOverlay = document.getElementById('uploadLoadingOverlay');
    const uploadFileInfoElement = document.getElementById('uploadFileInfo');

    const megaUploader = (typeof window.PunaTikTokMegaUploader === 'function' && puna_tiktok_ajax?.mega)
        ? new window.PunaTikTokMegaUploader(puna_tiktok_ajax.mega)
        : null;

    let selectedVideoFile = null;
    let videoDuration = 0;
    let isUploading = false;

    if (selectBtn) {
        selectBtn.addEventListener('click', () => {
            fileInput?.click();
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            handleFileSelect(e.target.files[0]);
        });
    }

    if (dropZone) {
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0 && files[0].type.startsWith('video/')) {
                handleFileSelect(files[0]);
            }
        });
    }

    function handleFileSelect(file) {
        if (!file || !file.type.startsWith('video/')) {
            showToast('Vui lòng chọn file video hợp lệ', 'warning');
            return;
        }

        selectedVideoFile = file;
        
        if (fileName) {
            fileName.textContent = file.name;
        }
        
        if (uploadFileInfoElement) {
            uploadFileInfoElement.style.display = 'none';
        }

        const video = document.createElement('video');
        video.preload = 'metadata';
        video.onloadedmetadata = () => {
            window.webkitURL = window.webkitURL || window.URL;
            videoDuration = video.duration;
            if (uploadDuration) {
                uploadDuration.textContent = `Thời lượng: ${formatDuration(videoDuration)}`;
            }
        };
        video.src = URL.createObjectURL(file);

        const videoURL = URL.createObjectURL(file);
        if (videoPreview) {
            videoPreview.src = videoURL;
            videoPreview.style.display = 'block';
        }
        if (previewPlaceholder) {
            previewPlaceholder.style.display = 'none';
        }

        if (step1) step1.classList.remove('active');
        if (step2) step2.classList.add('active');
        
        if (publishBtn) {
            publishBtn.disabled = false;
        }
    }

    function formatDuration(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        if (mins > 0) {
            return `${mins} phút ${secs} giây`;
        }
        return `${secs} giây`;
    }

    if (descriptionInput && charCount) {
        descriptionInput.addEventListener('input', () => {
            charCount.textContent = descriptionInput.value.length;
        });
    }

    if (backToStep1Btn) {
        backToStep1Btn.addEventListener('click', () => {
            if (step2) step2.classList.remove('active');
            if (step1) step1.classList.add('active');
        });
    }

    if (publishBtn) {
        publishBtn.addEventListener('click', () => {
            if (!selectedVideoFile) {
                showToast('Vui lòng chọn video', 'warning');
                return;
            }

            publishVideo();
        });
    }

    if (cancelUploadBtn) {
        cancelUploadBtn.addEventListener('click', () => {
            if (isUploading) {
                showToast('Đang tải lên Mega, vui lòng đợi hoàn tất.', 'warning');
            }
            resetUploadProgress();
        });
    }

    async function publishVideo() {
        if (!selectedVideoFile) return;
        if (!megaUploader) {
            showToast('Mega uploader chưa sẵn sàng.', 'error');
            return;
        }
        if (isUploading) return;

        isUploading = true;
        if (publishBtn) {
            publishBtn.disabled = true;
        }

        updateUploadProgress(0, selectedVideoFile.size);
        if (uploadLoadingOverlay) {
            uploadLoadingOverlay.classList.add('show');
        }

        try {
            const megaResult = await megaUploader.uploadFile(selectedVideoFile, (uploaded, total) => {
                updateUploadProgress(uploaded, total);
            });
            await finalizePost(megaResult);
        } catch (error) {
            showToast(error?.message || 'Không thể tải video lên Mega.nz.', 'error');
            if (uploadLoadingOverlay) {
                uploadLoadingOverlay.classList.remove('show');
            }
            resetUploadProgress();
        }
    }

    async function finalizePost(megaResult) {
        const formData = new FormData();
        formData.append('action', 'puna_tiktok_upload_video');
        formData.append('nonce', puna_tiktok_ajax?.nonce || '');
        formData.append('mega_link', megaResult?.link || '');
        formData.append('mega_node_id', megaResult?.nodeId || '');
        formData.append('video_name', megaResult?.name || selectedVideoFile.name);
        formData.append('video_size', megaResult?.size || selectedVideoFile.size || 0);
        formData.append('description', descriptionInput?.value || '');
        formData.append('category_id', videoCategorySelect?.value || '');

        try {
            const response = await fetch(puna_tiktok_ajax?.ajax_url || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            });
            const text = await response.text();

            let payload = null;
            try {
                payload = JSON.parse(text);
            } catch (parseError) {
                throw new Error('Máy chủ trả về dữ liệu không hợp lệ. Vui lòng đăng nhập lại và thử lại.');
            }

            if (payload.success) {
                const redirectUrl = payload.data?.redirect_url;
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                } else if (puna_tiktok_ajax?.current_user?.user_id) {
                    window.location.href = `/author/${puna_tiktok_ajax.current_user.user_id}/`;
                } else {
                    window.location.href = '/';
                }
            } else {
                showToast(payload.data?.message || 'Có lỗi xảy ra khi lưu video.', 'error');
                resetUploadProgress();
            }
        } catch (error) {
            showToast('Không thể lưu thông tin video. Vui lòng thử lại.', 'error');
            resetUploadProgress();
        }
    }

    function updateUploadProgress(uploaded, total) {
        if (uploadFileInfoElement) {
            uploadFileInfoElement.style.display = 'block';
        }
        if (fileName) {
            fileName.textContent = selectedVideoFile?.name || 'video.mp4';
        }

        const safeTotal = total || selectedVideoFile?.size || 0;
        const percentComplete = safeTotal > 0 ? (uploaded / safeTotal) * 100 : 0;

        if (uploadProgressFill) {
            uploadProgressFill.style.width = percentComplete + '%';
        }
        if (uploadProgressText) {
            uploadProgressText.textContent = `${formatFileSize(uploaded)} / ${formatFileSize(safeTotal)}`;
        }
        if (uploadPercentage) {
            uploadPercentage.textContent = Math.round(percentComplete) + '%';
        }
        if (uploadDuration && videoDuration > 0) {
            uploadDuration.textContent = `Thời lượng: ${formatDuration(videoDuration)}`;
        }
    }

    function resetUploadProgress() {
        isUploading = false;
        if (uploadProgressFill) uploadProgressFill.style.width = '0%';
        if (uploadProgressText) uploadProgressText.textContent = '0MB / 0MB';
        if (uploadPercentage) uploadPercentage.textContent = '0%';
        if (publishBtn) publishBtn.disabled = false;
        if (uploadFileInfoElement) {
            uploadFileInfoElement.style.display = 'none';
        }
        if (uploadLoadingOverlay) {
            uploadLoadingOverlay.classList.remove('show');
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    // Hashtag functionality
    const hashtagPlaceholder = document.getElementById('hashtagPlaceholder');
    const hashtagDropdown = document.getElementById('hashtagDropdown');
    const hashtagList = document.getElementById('hashtagList');
    const hashtagCloseBtn = document.getElementById('hashtagCloseBtn');
    let allHashtags = [];

    function loadPopularHashtags() {
        if (!hashtagList) return Promise.resolve();
        
        hashtagList.innerHTML = '<div class="hashtag-loading">Đang tải...</div>';
        
        return sendAjaxRequest('puna_tiktok_get_popular_hashtags', { limit: 100 })
            .then(data => {
                if (data.success && data.data.hashtags) {
                    allHashtags = data.data.hashtags;
                    return allHashtags;
                } else {
                    hashtagList.innerHTML = '<div class="hashtag-empty">Không có hashtag nào</div>';
                    return [];
                }
            })
            .catch(error => {
                hashtagList.innerHTML = '<div class="hashtag-error">Có lỗi xảy ra khi tải hashtag</div>';
                return [];
            });
    }

    function renderHashtags(hashtags) {
        if (!hashtagList) return;
        
        if (hashtags.length === 0) {
            hashtagList.innerHTML = '<div class="hashtag-empty">Không tìm thấy hashtag nào</div>';
            return;
        }
        
        hashtagList.innerHTML = hashtags.map(hashtag => `
            <div class="hashtag-item" data-hashtag="${hashtag.name}">
                <span class="hashtag-name">#${hashtag.name}</span>
                <span class="hashtag-count">${hashtag.count} video</span>
            </div>
        `).join('');
        
        hashtagList.querySelectorAll('.hashtag-item').forEach(item => {
            item.addEventListener('click', () => {
                const hashtagName = item.dataset.hashtag;
                insertHashtag(hashtagName);
            });
        });
    }

    function insertHashtag(hashtagName) {
        if (!descriptionInput) return;
        
        const cursorPos = descriptionInput.selectionStart;
        const textBefore = descriptionInput.value.substring(0, cursorPos);
        const textAfter = descriptionInput.value.substring(cursorPos);
        
        const lastHashIndex = textBefore.lastIndexOf('#');
        
        if (lastHashIndex === -1) {
            let hashtagText = `#${hashtagName}`;
            if (textBefore && !textBefore.match(/\s$/)) {
                hashtagText = ' ' + hashtagText;
            }
            if (textAfter && !textAfter.match(/^\s/)) {
                hashtagText = hashtagText + ' ';
            }
            const newText = textBefore + hashtagText + textAfter;
            descriptionInput.value = newText;
            
            if (charCount) {
                charCount.textContent = newText.length;
            }
            
            const newCursorPos = cursorPos + hashtagText.length;
            descriptionInput.setSelectionRange(newCursorPos, newCursorPos);
        } else {
            const textBeforeHash = textBefore.substring(0, lastHashIndex);
            const hashtagText = `#${hashtagName}`;
            const spaceAfter = (textAfter && !textAfter.match(/^\s/)) ? ' ' : '';
            const newText = textBeforeHash + hashtagText + spaceAfter + textAfter;
            descriptionInput.value = newText;
            
            if (charCount) {
                charCount.textContent = newText.length;
            }
            
            const newCursorPos = textBeforeHash.length + hashtagText.length + spaceAfter.length;
            descriptionInput.setSelectionRange(newCursorPos, newCursorPos);
        }
        
        descriptionInput.focus();
        
        if (hashtagDropdown) {
            hashtagDropdown.classList.remove('show');
        }
    }

    if (hashtagPlaceholder) {
        hashtagPlaceholder.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (!descriptionInput) return;
            
            const cursorPos = descriptionInput.selectionStart;
            const textBefore = descriptionInput.value.substring(0, cursorPos);
            const textAfter = descriptionInput.value.substring(cursorPos);
            
            let hashtagPrefix = '#';
            if (textBefore && !textBefore.match(/\s$/)) {
                hashtagPrefix = ' #';
            }
            
            const newText = textBefore + hashtagPrefix + textAfter;
            descriptionInput.value = newText;
            
            if (charCount) {
                charCount.textContent = newText.length;
            }
            
            const newCursorPos = cursorPos + hashtagPrefix.length;
            descriptionInput.setSelectionRange(newCursorPos, newCursorPos);
            descriptionInput.focus();
        });
    }
    
    if (descriptionInput) {
        descriptionInput.addEventListener('input', (e) => {
            checkAndShowHashtagSuggestions();
        });
        
        descriptionInput.addEventListener('keyup', (e) => {
            checkAndShowHashtagSuggestions();
        });
        
        descriptionInput.addEventListener('click', () => {
            checkAndShowHashtagSuggestions();
        });
    }
    
    function checkAndShowHashtagSuggestions() {
        if (!descriptionInput || !hashtagDropdown) return;
        
        const cursorPos = descriptionInput.selectionStart;
        const textBefore = descriptionInput.value.substring(0, cursorPos);
        
        const lastHashIndex = textBefore.lastIndexOf('#');
        
        if (lastHashIndex === -1) {
            hashtagDropdown.classList.remove('show');
            return;
        }
        
        const textAfterHash = textBefore.substring(lastHashIndex + 1);
        if (textAfterHash.match(/[\s\n]/)) {
            hashtagDropdown.classList.remove('show');
            return;
        }
        
        const searchTerm = textAfterHash.toLowerCase().trim();
        
        if (allHashtags.length === 0) {
            loadPopularHashtags().then(() => {
                filterAndShowHashtags(searchTerm);
            });
        } else {
            filterAndShowHashtags(searchTerm);
        }
    }
    
    function filterAndShowHashtags(searchTerm) {
        if (!hashtagDropdown || !hashtagList) return;
        
        let filtered = allHashtags;
        
        if (searchTerm) {
            filtered = allHashtags.filter(hashtag => 
                hashtag.name.toLowerCase().startsWith(searchTerm)
            );
        }
        
        hashtagDropdown.classList.add('show');
        renderHashtags(filtered);
    }

    if (hashtagCloseBtn) {
        hashtagCloseBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (hashtagDropdown) {
                hashtagDropdown.classList.remove('show');
            }
        });
    }

    document.addEventListener('click', (e) => {
        if (hashtagDropdown && hashtagDropdown.classList.contains('show')) {
            if (!hashtagDropdown.contains(e.target) && !hashtagPlaceholder.contains(e.target)) {
                hashtagDropdown.classList.remove('show');
            }
        }
    });
});

