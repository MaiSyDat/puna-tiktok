<?php
echo '<main id="primary" class="site-main">';
while ( have_posts() ) : the_post();
    echo '<article id="post-' . get_the_ID() . '" ' . get_post_class('') . '>';
    the_title('<h1 class="entry-title">','</h1>');
    echo '<div class="entry-content">';
    the_content();
    echo '</div></article>';
endwhile;
echo '</main>';


