<?php
/**
 * Upload Form Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$video_categories = get_terms(array(
    'taxonomy'   => 'category',
    'hide_empty' => false,
));

if (is_wp_error($video_categories)) {
    $video_categories = array();
}
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
        <?php echo puna_tiktok_get_avatar_html($current_user->ID, 32, 'user-avatar'); ?>
    </div>
</div>

<!-- Upload Content -->
<div class="upload-content-wrapper">
    <!-- Step 1: Select Video -->
    <div class="upload-step upload-step-1 active" id="uploadStep1">
        <div class="upload-step-card">
            <div class="upload-step-header">
                <div class="step-badge">Bước 1</div>
                <div class="step-texts">
                    <h2 class="step-title">Tải video lên</h2>
                    <p class="step-subtitle">Kéo thả video của bạn hoặc chọn file từ máy tính</p>
                </div>
            </div>

            <div class="upload-step-body">
                <div class="upload-drop-zone" id="uploadDropZone">
                    <div class="drop-zone-content">
                        <div class="upload-icon">
                            <i class="fa-solid fa-video"></i>
                            <i class="fa-solid fa-arrow-up"></i>
                        </div>
                        <h3 class="drop-zone-title">Chọn video để tải lên</h3>
                        <p class="drop-zone-subtitle">MP4, tối đa 30 GB, tối đa 60 phút</p>
                        <button type="button" class="select-video-btn" id="selectVideoBtn">Chọn video</button>
                    </div>
                    <input type="file" id="videoFileInput" accept="video/*">
                </div>

                <!-- Upload Guidelines -->
                <div class="upload-guidelines">
                    <div class="guideline-item">
                        <i class="fa-solid fa-video"></i>
                        <div class="guideline-text">
                            <strong>Kích thước & thời lượng</strong>
                            <span>Kích thước tối đa: 30 GB, thời lượng video tối đa 60 phút.</span>
                        </div>
                    </div>
                    <div class="guideline-item">
                        <i class="fa-solid fa-folder"></i>
                        <div class="guideline-text">
                            <strong>Định dạng file</strong>
                            <span>Khuyến nghị sử dụng .mp4. Các định dạng video phổ biến khác cũng được hỗ trợ.</span>
                        </div>
                    </div>
                    <div class="guideline-item">
                        <i class="fa-solid fa-display"></i>
                        <div class="guideline-text">
                            <strong>Độ phân giải</strong>
                            <span>Ưu tiên video có độ phân giải cao: 1080p, 1440p hoặc 4K.</span>
                        </div>
                    </div>
                    <div class="guideline-item">
                        <i class="fa-solid fa-question"></i>
                        <div class="guideline-text">
                            <strong>Tỷ lệ khung hình</strong>
                            <span>16:9 cho video ngang, 9:16 cho video dọc để hiển thị tốt nhất.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Edit Video Details -->
    <div class="upload-step upload-step-2" id="uploadStep2">
        <div class="upload-step-card upload-step-card--details">
            <div class="upload-step-header">
                <div class="step-badge">Bước 2</div>
                <div class="step-texts">
                    <h2 class="step-title">Chi tiết video</h2>
                    <p class="step-subtitle">Thêm mô tả, hashtag và danh mục để video dễ được tìm thấy hơn</p>
                </div>
            </div>

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
                        <h3 class="section-title">Thông tin cơ bản</h3>
                        
                        <!-- Description -->
                        <div class="form-group">
                            <div class="form-label-row">
                                <label class="form-label">Mô tả</label>
                                <span class="form-label-hint">Giới thiệu ngắn gọn về nội dung video của bạn</span>
                            </div>
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
                            </div>
                            <div class="char-counter">
                                <span id="charCount">0</span>/4000
                            </div>
                        </div>

                        <!-- Categories -->
                        <div class="form-group">
                            <div class="form-label-row">
                                <label class="form-label" for="videoCategory"><?php _e('Danh mục', 'puna-tiktok'); ?></label>
                                <span class="form-label-hint">Chọn chủ đề phù hợp nhất với video</span>
                            </div>
                            <select id="videoCategory" class="form-input">
                                <option value=""><?php _e('Chọn danh mục', 'puna-tiktok'); ?></option>
                                <?php foreach ($video_categories as $category) : ?>
                                    <option value="<?php echo esc_attr($category->term_id); ?>">
                                        <?php echo esc_html($category->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Right Panel: Preview -->
                <div class="upload-preview-panel">
                    <div class="video-preview-container">
                        <div class="video-preview-header">
                            <span class="video-preview-label">Xem trước</span>
                            <span class="video-preview-hint">Video như khi hiển thị trên trang xem</span>
                        </div>
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
</div>

<!-- Loading Overlay -->
<div class="upload-loading-overlay" id="uploadLoadingOverlay">
    <div class="loading-spinner"></div>
    <p class="loading-text">Đang tải video lên...</p>
</div>

