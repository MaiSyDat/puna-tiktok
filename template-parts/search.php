<?php
echo '<main id="primary" class="site-main">';
if ( have_posts() ) :
    printf('<header class="page-header"><h1 class="page-title">%s</h1></header>', esc_html__('Search results', 'puna-tiktok'));
    while ( have_posts() ) : the_post();
        echo '<article id="post-' . get_the_ID() . '" ' . get_post_class('') . '>';
        the_title('<h2 class="entry-title"><a href="' . esc_url(get_permalink()) . '">','</a></h2>');
        echo '<div class="entry-summary">';
        the_excerpt();
        echo '</div></article>';
    endwhile;
    the_posts_navigation();
else :
    echo '<p>' . esc_html__('No results found.', 'puna-tiktok') . '</p>';
endif;
echo '</main>';


