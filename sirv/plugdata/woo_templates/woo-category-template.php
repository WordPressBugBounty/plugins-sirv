<?php
global $post;

$woo = new Woo($post->ID);

//echo $woo->get_woo_cat_gallery_html();
echo $woo->get_cached_woo_smv_html('_sirv_woo_cat_cache');
?>
