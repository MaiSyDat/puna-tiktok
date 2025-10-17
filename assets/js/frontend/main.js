document.addEventListener("DOMContentLoaded", function() {
    const videos = document.querySelectorAll('.tiktok-video');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const navPrevBtn = document.querySelector('.video-nav-btn.nav-prev');
    const navNextBtn = document.querySelector('.video-nav-btn.nav-next');

    const options = {
        root: mainContent,
        rootMargin: '0px',
        threshold: 0.8
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.play().catch(e => {
                    console.log('Trình duyệt chặn autoplay:', e);
                });
            } else {
                entry.target.pause();
            }
        });
    }, options);

    videos.forEach(video => {
        observer.observe(video);
        video.addEventListener('click', function() {
            if (this.paused) {
                this.play();
            } else {
                this.pause();
            }
        });
    });

    let isScrolling = false;
    if (mainContent) {
        mainContent.addEventListener('scroll', function() {
            if (!isScrolling) {
                window.requestAnimationFrame(function() {
                    isScrolling = false;
                });
                isScrolling = true;
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        const videos = document.querySelectorAll('.tiktok-video');
        const currentVideo = Array.from(videos).find(video => {
            const rect = video.getBoundingClientRect();
            return rect.top >= 0 && rect.top < window.innerHeight / 2;
        });

        if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
            e.preventDefault();
            const direction = e.key === 'ArrowUp' ? -1 : 1;
            const currentIndex = Array.from(videos).indexOf(currentVideo);
            const nextIndex = currentIndex + direction;

            if (nextIndex >= 0 && nextIndex < videos.length) {
                videos[nextIndex].scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });

    function scrollToSibling(direction) {
        const videos = document.querySelectorAll('.tiktok-video');
        if (!videos.length) return;
        const currentVideo = Array.from(videos).find(video => {
            const rect = video.getBoundingClientRect();
            return rect.top >= 0 && rect.top < window.innerHeight / 2;
        });
        const currentIndex = Array.from(videos).indexOf(currentVideo);
        const nextIndex = currentIndex + direction;
        if (nextIndex >= 0 && nextIndex < videos.length) {
            videos[nextIndex].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        updateNavDisabledState();
    }

    // Bắt sự kiện click nút điều hướng
    document.addEventListener('click', function(e) {
        const target = e.target.closest('.video-nav-btn');
        if (!target) return;
        e.preventDefault();
        if (target.classList.contains('nav-prev')) {
            scrollToSibling(-1);
        } else if (target.classList.contains('nav-next')) {
            scrollToSibling(1);
        }
    });

    // Cập nhật trạng thái vô hiệu nút
    function updateNavDisabledState() {
        const list = document.querySelectorAll('.tiktok-video');
        if (!list.length) return;
        const current = Array.from(list).find(video => {
            const rect = video.getBoundingClientRect();
            return rect.top >= 0 && rect.top < window.innerHeight / 2;
        });
        const idx = Array.from(list).indexOf(current);
        const atTop = idx <= 0;
        const atBottom = idx === list.length - 1 || idx < 0;
        if (navPrevBtn) navPrevBtn.classList.toggle('is-disabled', atTop);
        if (navNextBtn) navNextBtn.classList.toggle('is-disabled', atBottom);
    }

    // Khởi tạo trạng thái sau khi tải trang
    updateNavDisabledState();

    // Cập nhật khi scroll, nhấn phím, vuốt
    const updateEvents = ['scroll', 'keydown', 'touchend'];
    updateEvents.forEach(evt => {
        (evt === 'scroll' && mainContent ? mainContent : document).addEventListener(evt, function() {
            window.requestAnimationFrame(updateNavDisabledState);
        });
    });

    // Vuốt để chuyển video
    let startY = 0;
    let endY = 0;
    if (mainContent) {
        mainContent.addEventListener('touchstart', function(e) {
            startY = e.touches[0].clientY;
        });
        mainContent.addEventListener('touchend', function(e) {
            endY = e.changedTouches[0].clientY;
            const diff = startY - endY;
            if (Math.abs(diff) > 50) {
                const videos = document.querySelectorAll('.tiktok-video');
                const currentVideo = Array.from(videos).find(video => {
                    const rect = video.getBoundingClientRect();
                    return rect.top >= 0 && rect.top < window.innerHeight / 2;
                });
                const currentIndex = Array.from(videos).indexOf(currentVideo);
                const direction = diff > 0 ? 1 : -1;
                const nextIndex = currentIndex + direction;
                if (nextIndex >= 0 && nextIndex < videos.length) {
                    videos[nextIndex].scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    }
});

