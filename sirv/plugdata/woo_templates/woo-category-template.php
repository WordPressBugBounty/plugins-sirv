<?php
global $post;

$woo = new Woo($post->ID);

echo $woo->get_woo_cat_gallery_html();
?>
