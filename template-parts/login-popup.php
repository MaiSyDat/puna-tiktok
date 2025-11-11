<?php
/**
 * Template part for login popup
 *
 * @package puna-tiktok
 */
?>

<!-- Popup login -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <button class="back-btn" onclick="closeModal()">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <h2 class="modal-title">Đăng nhập</h2>
            <button class="close-btn" onclick="closeModal()">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        
        <!-- Form đăng nhập -->
        <div id="email-login" class="login-form active">
            <div class="form-group">
                <input type="text" placeholder="Email hoặc tên người dùng" class="email-input">
            </div>
            
            <div class="form-group">
                <input type="password" placeholder="Mật khẩu" class="password-input">
            </div>
            
            <div class="form-group">
                <a href="#" class="forgot-password-link">Quên mật khẩu?</a>
            </div>
            
            <button class="login-submit-btn">Đăng nhập</button>
        </div>
        
        <div class="modal-footer">
            <p>Chưa có tài khoản? <a href="#" class="signup-link" onclick="switchToSignup()">Đăng ký</a></p>
        </div>
    </div>
</div>

<!-- Popup register -->
<div id="signupModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <button class="back-btn" onclick="closeModal()">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <h2 class="modal-title">Đăng ký</h2>
            <button class="close-btn" onclick="closeModal()">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        
        <!-- Form login email -->
        <div id="email-signup" class="signup-form active">
            <div class="form-group">
                <label class="form-label">Sinh nhật của bạn là khi nào?</label>
                <div class="birthday-inputs">
                    <select class="birthday-select" name="birthday-month">
                        <option value="">Tháng</option>
                        <option value="1">Tháng 1</option>
                        <option value="2">Tháng 2</option>
                        <option value="3">Tháng 3</option>
                        <option value="4">Tháng 4</option>
                        <option value="5">Tháng 5</option>
                        <option value="6">Tháng 6</option>
                        <option value="7">Tháng 7</option>
                        <option value="8">Tháng 8</option>
                        <option value="9">Tháng 9</option>
                        <option value="10">Tháng 10</option>
                        <option value="11">Tháng 11</option>
                        <option value="12">Tháng 12</option>
                    </select>
                    <select class="birthday-select" name="birthday-day">
                        <option value="">Ngày</option>
                        <?php for($i = 1; $i <= 31; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                    <select class="birthday-select" name="birthday-year">
                        <option value="">Năm</option>
                        <?php for($i = date('Y'); $i >= 1900; $i--): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <p class="birthday-note">Ngày sinh của bạn sẽ không được hiển thị công khai.</p>
            </div>
            
            <div class="form-group">
                <input type="email" placeholder="Email" class="email-input" name="email" required>
            </div>
            
            <div class="form-group">
                <input type="text" placeholder="Tên người dùng" class="username-input" name="username" required>
            </div>
            
            <div class="form-group">
                <input type="password" placeholder="Mật khẩu" class="password-input" name="password" required>
            </div>
            
            <button class="signup-submit-btn">Đăng ký</button>
        </div>
        
        <div class="legal-text">
            <p>Bằng cách tiếp tục, bạn đồng ý với <strong>Điều khoản dịch vụ</strong> của chúng tôi và xác nhận rằng bạn đã đọc <strong>Chính sách quyền riêng tư</strong> của chúng tôi.</p>
        </div>
        
        <div class="modal-footer">
            <p>Đã có tài khoản? <a href="#" class="login-link" onclick="switchToLogin()">Đăng nhập</a></p>
        </div>
    </div>
</div>
