<?php
/**
 * Template part for login popup
 *
 * @package puna-tiktok
 */
?>

<!-- Popup đăng nhập -->
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
        
        <div class="login-tabs">
            <button class="tab-btn active" onclick="switchTab('phone')">Điện thoại</button>
            <button class="tab-btn" onclick="switchTab('email')">Đăng nhập bằng email hoặc tên người dùng</button>
        </div>
        
        <!-- Form đăng nhập bằng điện thoại -->
        <div id="phone-login" class="login-form active">
            <div class="form-group">
                <div class="phone-input-group">
                    <select class="country-code">
                        <option value="+84">VN +84</option>
                        <option value="+1">US +1</option>
                        <option value="+86">CN +86</option>
                    </select>
                    <input type="tel" placeholder="Số điện thoại" class="phone-input">
                    <button class="contact-btn">
                        <i class="fa-solid fa-user"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <div class="code-input-group">
                    <input type="text" placeholder="Nhập mã 6 chữ số" class="code-input" maxlength="6">
                    <button class="send-code-btn">Gửi mã</button>
                </div>
            </div>
            
            <div class="form-group">
                <a href="#" class="password-login-link" onclick="switchToPasswordLogin()">Đăng nhập bằng mật khẩu</a>
            </div>
            
            <button class="login-submit-btn" disabled>Đăng nhập</button>
        </div>
        
        <!-- Form đăng nhập bằng email -->
        <div id="email-login" class="login-form">
            <div class="form-group">
                <input type="email" placeholder="Email hoặc tên người dùng" class="email-input">
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

<!-- Popup đăng ký -->
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
        
        <div class="form-group">
            <label class="form-label">Sinh nhật của bạn là khi nào?</label>
            <div class="birthday-inputs">
                <select class="birthday-select">
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
                <select class="birthday-select">
                    <option value="">Ngày</option>
                    <?php for($i = 1; $i <= 31; $i++): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
                <select class="birthday-select">
                    <option value="">Năm</option>
                    <?php for($i = date('Y'); $i >= 1900; $i--): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <p class="birthday-note">Ngày sinh của bạn sẽ không được hiển thị công khai.</p>
        </div>
        
        <div class="form-group">
            <div class="phone-signup-group">
                <label class="form-label">Điện thoại</label>
                <a href="#" class="email-signup-link" onclick="switchToEmailSignup()">Đăng ký bằng email</a>
            </div>
            <div class="phone-input-group">
                <select class="country-code">
                    <option value="+84">VN +84</option>
                    <option value="+1">US +1</option>
                    <option value="+86">CN +86</option>
                </select>
                <input type="tel" placeholder="Số điện thoại" class="phone-input">
            </div>
            <div class="code-input-group">
                <input type="text" placeholder="Nhập mã 6 chữ số" class="code-input" maxlength="6">
                <button class="send-code-btn">Gửi mã</button>
            </div>
        </div>
        
        <button class="next-btn">Tiếp theo</button>
        
        <div class="legal-text">
            <p>Bằng cách tiếp tục với một tài khoản đặt tại <strong>Việt Nam</strong>, bạn đồng ý với <strong>Điều khoản dịch vụ</strong> của chúng tôi và xác nhận rằng bạn đã đọc <strong>Chính sách quyền riêng tư</strong> của chúng tôi.</p>
        </div>
        
        <div class="modal-footer">
            <p>Đã có tài khoản? <a href="#" class="login-link" onclick="switchToLogin()">Đăng nhập</a></p>
        </div>
    </div>
</div>
