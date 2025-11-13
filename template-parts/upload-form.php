<?php
/**
 * Upload Form Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
?>

<!-- Upload Studio Header -->
<div class="upload-studio-header">
    <div class="studio-header-left">
        <a href="<?php echo home_url('/'); ?>" class="studio-back-btn" title="Về trang chủ">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div class="studio-logo">
            <span class="studio-icon">♪</span>
            <span class="studio-text">TikTok Studio</span>
        </div>
    </div>
    <div class="studio-user">
        <img src="<?php echo get_avatar_url($current_user->ID, array('size' => 32)); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>" class="user-avatar">
    </div>
</div>

<!-- Upload Content -->
<div class="upload-content-wrapper">
    <!-- Step 1: Select Video -->
    <div class="upload-step upload-step-1 active" id="uploadStep1">
        <div class="upload-drop-zone" id="uploadDropZone">
            <div class="drop-zone-content">
                <div class="upload-icon">
                    <i class="fa-solid fa-video"></i>
                    <i class="fa-solid fa-arrow-up"></i>
                </div>
                <h2 class="drop-zone-title">Chọn video để tải lên</h2>
                <p class="drop-zone-subtitle">Hoặc kéo thả video vào đây</p>
                <button type="button" class="select-video-btn" id="selectVideoBtn">Chọn video</button>
            </div>
            <input type="file" id="videoFileInput" accept="video/*">
        </div>

        <!-- Upload Guidelines -->
        <div class="upload-guidelines">
            <div class="guideline-item">
                <i class="fa-solid fa-video"></i>
                <div class="guideline-text">
                    <strong>Kích thước và thời lượng:</strong>
                    Kích thước tối đa: 30 GB, thời lượng video: 60 phút.
                </div>
            </div>
            <div class="guideline-item">
                <i class="fa-solid fa-folder"></i>
                <div class="guideline-text">
                    <strong>Định dạng file:</strong>
                    Khuyến nghị: ".mp4". Các định dạng video phổ biến khác cũng được hỗ trợ.
                </div>
            </div>
            <div class="guideline-item">
                <i class="fa-solid fa-display"></i>
                <div class="guideline-text">
                    <strong>Độ phân giải video:</strong>
                    Khuyến nghị độ phân giải cao: 1080p, 1440p, 4K.
                </div>
            </div>
            <div class="guideline-item">
                <i class="fa-solid fa-question"></i>
                <div class="guideline-text">
                    <strong>Tỷ lệ khung hình:</strong>
                    Khuyến nghị: 16:9 cho video ngang, 9:16 cho video dọc.
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Edit Video Details -->
    <div class="upload-step upload-step-2" id="uploadStep2">
        <div class="upload-form-container">
            <!-- Left Panel: Form -->
            <div class="upload-form-panel">
                <!-- File Info & Progress -->
                <div class="upload-file-info" id="uploadFileInfo">
                    <div class="file-name" id="fileName"></div>
                    <div class="upload-progress-container">
                        <div class="upload-progress-bar">
                            <div class="upload-progress-fill" id="uploadProgressFill"></div>
                        </div>
                        <div class="upload-progress-text">
                            <span id="uploadProgressText">0MB / 0MB</span>
                            <span class="upload-percentage" id="uploadPercentage">0%</span>
                            <button type="button" class="cancel-upload-btn" id="cancelUploadBtn">Hủy bỏ</button>
                        </div>
                        <div class="upload-duration" id="uploadDuration"></div>
                    </div>
                </div>

                <!-- Details Section -->
                <div class="upload-section">
                    <h3 class="section-title">Chi tiết</h3>
                    
                    <!-- Description -->
                    <div class="form-group">
                        <label class="form-label">Mô tả</label>
                        <textarea 
                            id="videoDescription" 
                            class="form-textarea description-input" 
                            placeholder="Viết mô tả cho video..."
                            rows="4"
                            maxlength="4000"
                        ></textarea>
                        <div class="input-helpers">
                            <div class="hashtag-wrapper">
                                <span class="hashtag-placeholder" id="hashtagPlaceholder"># Hashtag</span>
                                <div class="hashtag-dropdown" id="hashtagDropdown">
                                    <div class="hashtag-dropdown-header">
                                        <h4>Gợi ý hashtag</h4>
                                        <button type="button" class="hashtag-close-btn" id="hashtagCloseBtn">
                                            <i class="fa-solid fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="hashtag-list" id="hashtagList">
                                        <div class="hashtag-loading">Đang tải...</div>
                                    </div>
                                </div>
                            </div>
                            <span class="mention-placeholder">@ Nhắc đến</span>
                        </div>
                        <div class="char-counter">
                            <span id="charCount">0</span>/4000
                        </div>
                    </div>

                    <!-- Cover -->
                    <div class="form-group">
                        <label class="form-label">Ảnh bìa</label>
                        <div class="cover-preview-container">
                            <div class="cover-preview" id="coverPreview">
                                <img id="coverPreviewImg" src="" alt="Xem trước ảnh bìa">
                                <div class="cover-placeholder">
                                    <i class="fa-solid fa-image"></i>
                                </div>
                            </div>
                            <button type="button" class="edit-cover-btn" id="editCoverBtn">Chỉnh sửa ảnh bìa</button>
                        </div>
                        <input type="file" id="coverImageInput" accept="image/*">
                    </div>

                    <!-- Location -->
                    <div class="form-group">
                        <label class="form-label">Vị trí</label>
                        <input 
                            type="text" 
                            id="videoLocation" 
                            class="form-input location-input" 
                            placeholder="Tìm kiếm vị trí"
                        >
                        <div class="location-suggestions" id="locationSuggestions">
                            <!-- Suggested locations will be populated here -->
                        </div>
                    </div>
                </div>

                <!-- Settings Section -->
                <div class="upload-section">
                    <h3 class="section-title">Cài đặt</h3>
                    
                    <!-- When to post -->
                    <div class="form-group">
                        <label class="form-label">Khi nào đăng</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="postSchedule" value="now" checked>
                                <span>Ngay bây giờ</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="postSchedule" value="schedule">
                                <span>Lên lịch</span>
                            </label>
                        </div>
                        <div class="schedule-datetime" id="scheduleDateTime">
                            <input type="datetime-local" id="scheduleDateInput" class="form-input">
                        </div>
                    </div>

                    <!-- Who can watch -->
                    <div class="form-group">
                        <label class="form-label">Ai có thể xem video này</label>
                        <select id="videoPrivacy" class="form-select">
                            <option value="public">Mọi người</option>
                            <option value="friends">Chỉ bạn bè</option>
                            <option value="private">Riêng tư</option>
                        </select>
                    </div>

                </div>

                <!-- Checks Section -->
                <div class="upload-section">
                    <h3 class="section-title">Kiểm tra</h3>
                    <p class="checks-note">Kiểm tra chỉ có thể bắt đầu sau khi file được tải lên.</p>
                    
                    <div class="form-group">
                        <label class="toggle-label">
                            <input type="checkbox" id="musicCopyrightCheck" class="toggle-checkbox" checked>
                            <span class="toggle-switch"></span>
                            <div class="toggle-description">
                                <strong>Kiểm tra bản quyền nhạc</strong>
                                <p>Chúng tôi sẽ kiểm tra xem video của bạn có nhạc trái phép có thể khiến video bị tắt tiếng không.</p>
                            </div>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="toggle-label">
                            <input type="checkbox" id="contentCheckLite" class="toggle-checkbox" checked>
                            <span class="toggle-switch"></span>
                            <div class="toggle-description">
                                <strong>Kiểm tra nội dung</strong>
                                <p>Chúng tôi sẽ kiểm tra nội dung của bạn để đảm bảo đủ điều kiện cho trang Dành cho bạn.</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Preview -->
            <div class="upload-preview-panel">
                <div class="video-preview-container">
                    <div class="video-preview-wrapper">
                        <video id="videoPreview" class="video-preview" controls></video>
                        <div class="preview-placeholder" id="previewPlaceholder">
                            <i class="fa-solid fa-video"></i>
                            <p>Xem trước video</p>
                        </div>
                    </div>
                    <button type="button" class="edit-video-btn" id="editVideoBtn">
                        <i class="fa-solid fa-scissors"></i>
                        Chỉnh sửa video
                    </button>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="upload-actions">
            <button type="button" class="btn-secondary" id="backToStep1Btn">Quay lại</button>
            <button type="button" class="btn-primary" id="publishVideoBtn" disabled>Đăng</button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="upload-loading-overlay" id="uploadLoadingOverlay">
    <div class="loading-spinner"></div>
    <p class="loading-text">Đang tải video lên...</p>
</div>

