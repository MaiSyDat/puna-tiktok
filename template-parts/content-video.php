<?php
$video_url = puna_tiktok_get_video_url();
$likes = rand(100, 9999);
$comments = rand(10, 999);
$shares = rand(5, 999);
$views = rand(1000, 99999);
?>

<div class="video-row">
    <section class="video-container">
        <video class="tiktok-video" loop muted playsinline>
            <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
            Trình duyệt của bạn không hỗ trợ video.
        </video>

        <div class="video-overlay">
            <div class="video-details">
                <h4><?php the_author(); ?></h4>
                <p class="video-caption"><?php the_title(); ?></p>

                <?php
                $tags = get_the_tags();
                if ($tags) : ?>
                    <div class="video-tags">
                        <?php foreach ($tags as $tag) : ?>
                            <a href="<?php echo get_tag_link($tag->term_id); ?>" class="tag">#<?php echo $tag->name; ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <aside class="video-sidebar" aria-hidden="false">
        <div class="author-avatar-wrapper">
            <img src="<?php echo get_avatar_url(get_the_author_meta('ID'), array('size' => 50)); ?>" alt="<?php the_author(); ?>" class="author-avatar">
            <div class="follow-icon"><i class="fa-solid fa-plus"></i></div>
        </div>

        <div class="action-item" data-action="like">
            <i class="fa-solid fa-heart"></i>
            <span class="count"><?php echo '297K'; ?></span>
        </div>

        <div class="action-item" data-action="comment">
            <i class="fa-solid fa-comment"></i>
            <span class="count"><?php echo '1917'; ?></span>
        </div>

        <div class="action-item" data-action="save">
            <i class="fa-solid fa-bookmark"></i>
            <span class="count"><?php echo '10.9K'; ?></span>
        </div>

        <div class="action-item" data-action="share">
            <i class="fa-solid fa-share"></i>
            <span class="count"><?php echo '29.1K'; ?></span>
        </div>
    </aside>
</div>