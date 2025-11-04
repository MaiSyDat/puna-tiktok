<?php
/**
 * Search Panel Template
 * Hiển thị search panel ở tất cả các trang
 */
?>

<!-- Search Panel -->
<aside class="search-panel" id="search-panel">
    <div class="search-panel-header">
        <h3>Tìm kiếm</h3>
        <button id="close-search" class="close-search-btn">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <div class="search-panel-input-wrapper">
        <form method="get" action="<?php echo esc_url(home_url('/')); ?>" class="search-form" id="search-form">
            <input type="search" 
                   id="real-search-input" 
                   name="s" 
                   value="<?php echo esc_attr(get_search_query()); ?>" 
                   placeholder="Tìm kiếm..."
                   autocomplete="off">
        </form>
    </div>
    <!-- Search Suggestions & History Container -->
    <div class="search-suggestions-container" id="search-suggestions-container">
        <!-- Loading indicator -->
        <div class="search-loading" id="search-loading" style="display: none;">
            <p>Đang tải...</p>
        </div>
        
        <!-- Suggestions (shown when typing) -->
        <ul class="search-suggestions" id="search-suggestions-list" style="display: none;">
            <!-- Suggestions sẽ được load bằng AJAX -->
        </ul>
        
        <!-- Search History (shown when input is empty) -->
        <div class="search-history-section" id="search-history-section">
            <div class="search-history-header">
                <h4>Lịch sử tìm kiếm</h4>
                <button class="clear-history-btn" id="clear-history-btn" style="display: none;">
                    <i class="fa-solid fa-trash"></i> Xóa lịch sử
                </button>
            </div>
            <ul class="search-history-list" id="search-history-list">
                <!-- History sẽ được load bằng AJAX -->
                <li class="search-history-loading">Đang tải lịch sử...</li>
            </ul>
        </div>
        
        <!-- Popular Searches (loaded via AJAX) -->
        <div class="search-popular-section" id="search-popular-section">
            <h4>Những tìm kiếm phổ biến</h4>
            <ul class="search-popular-list" id="search-popular-list">
                <li class="search-popular-loading">Đang tải...</li>
            </ul>
        </div>
    </div>
</aside>

