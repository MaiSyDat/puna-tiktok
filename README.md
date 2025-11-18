# Puna TikTok – WordPress Theme

Giao diện WordPress mô phỏng TikTok (UI). Hỗ trợ đăng video (upload file), trang Khám phá, điều hướng sidebar, và auto tạo các trang cần thiết khi kích hoạt theme.

## Yêu cầu
- WordPress 6.x
- PHP 7.4+
- Quyền ghi thư mục `wp-content/uploads`

## Cài đặt
1. Clone repo vào thư mục theme:
```bash
cd wp-content/themes
git clone https://github.com/MaiSyDat/puna-tiktok.git
```
2. Kích hoạt theme trong Admin: Giao diện → Giao diện → Puna TikTok.
3. Sau khi kích hoạt, theme tự tạo các trang: Explore, Followed, Friends, Messages, Profile.

## Tính năng chính
- Đăng video bằng upload tệp (MP4/WebM/OGG/MOV/AVI)
- Trang Khám phá (Explore) dạng lưới
- Trang feed video có nút chuyển lên/xuống và cuộn mượt
- Sidebar điều hướng và highlight trang hiện tại

## Phát triển
- CSS/JS frontend:
  - `assets/css/frontend/components/` (CSS modules)
  - `assets/js/frontend/main.js`
- Template phần trang: `template-parts/pages/`
- Đăng ký post type: `inc/class-video-post-type.php`
- Nạp tài nguyên: `inc/class-assets.php`
- Cấu hình theme & auto tạo trang: `inc/class-setup.php`
- AJAX handlers: `inc/class-ajax-handlers.php`

## Góp ý / Issue
Mở issue trên GitHub hoặc tạo PR.

## Giấy phép
MIT
