<?php

/**
 * Sirv Media Viewer for Single Product Image
 *
 * This template for displaying Sirv Media Viewer in WC product pages
 *
 * @version 10.5.0
 */

defined('ABSPATH') or die('No script kiddies please!');
?>

<style>
  .sirv-skeleton-wrapper {
    position: relative;
    padding-top: 100%;
    height: auto;
    margin-top: 5px;
    width: 100%;
  }

  .sirv-smv-container {
    position: absolute;
    width: 100%;
    top: 0;
    padding: 0;
    height: 100%;
  }

  .sirv-skeleton {
    background-repeat: no-repeat;
    background-image:
      linear-gradient(#c7c6c6cc 100%, transparent 0),
      linear-gradient(#c7c6c6cc 100%, transparent 0),
      linear-gradient(#c7c6c6cc 100%, transparent 0),
      linear-gradient(#c7c6c6cc 100%, transparent 0),
      linear-gradient(#c7c6c6cc 100%, transparent 0),
      linear-gradient(#fdfdfdcc 100%, transparent 0);
    background-size:
      100% 70%,
      /* main image */
      20% 70px,
      /* selector 1 */
      20% 70px,
      /* selector 2 */
      20% 70px,
      /* selector 3 */
      20% 70px,
      /* selector 4 */
      100% 100%;
    /* container */
    background-position:
      0 0,
      /* main image */
      10% 95%,
      /* selector 1 */
      37% 95%,
      /* selector 2 */
      64% 95%,
      /* selector 3 */
      91% 95%,
      /* selector 4 */
      0 0;
    /* container */
  }

  .sirv-woo-wrapper {
    width: 100%;
    height: 100%;
    max-width: 100%;
    max-height: 100%;
  }

  .sirv-pdp-gallery-wrapper {
    height: inherit;
  }

  @media only screen and (max-width: 768px) {
    .sirv-woo-wrapper.sirv-media-size {
      width: 100%;
      height: 100%;
    }
  }

  /* @media only screen and (min-width: 420px) and (max-width: 768px) {
    .sirv-woo-wrapper {
      width: 50%;
    }
  } */

  .sirv-woo-smv-caption {
    display: none;
    width: 100%;
    min-height: 25px;
    margin: 5px 0 5px;
    font-size: 18px;
    line-height: initial;
    font-weight: bold;
    text-align: center;
  }

  .sirv-woo-opacity-zero {
    opacity: 0;
  }

  .sirv-woo-opacity {
    opacity: 1;
    /* transition: all 0.1s; */
  }

  /*---------------------------PDP placeholder image---------------------------------*/
  /* Sirv Media Viewer placeholder */
  .sirv-pdp-gallery-container {
    display: flex;
    height: inherit;
  }

  .sirv-thumbnail-position-top {
    flex-direction: column;
  }

  .sirv-thumbnail-position-bottom {
    flex-direction: column-reverse;
  }

  .sirv-thumbnail-position-right {
    height: 400px;
    flex-direction: row-reverse;
  }

  .sirv-thumbnail-position-left {
    height: 400px;
    flex-direction: row;
  }

  /* Set the size of thumbnails */
  .sirv-pdp-gallery-thumbnails {
    z-index: 1;
  }

  .sirv-pdp-gallery-thumbnails .smv-thumbnails.smv-v.smv-external .smv-selectors {
    min-width: unset !important;
  }

  .sirv-pdp-gallery-thumbnails .smv-selector>img {
    width: auto !important;
    height: auto !important;
  }

  .sirv-pdp-gallery-main {
    /* flex: 1 1; */
    position: relative;
    height: inherit;
  }

  .sirv-pdp-gallery-main-block {
    display: flex;
    flex-direction: column;
    justify-content: center;
    flex: 1 1;
    height: calc(100% - var(--smv-thumbs-size) - 14px);
  }

  .sirv-pdp-gallery-main .Sirv.sirv-mainimage {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
  }

  /* Non-first item should overlap placeholder */
  .sirv-pdp-gallery-main .Sirv .smv-slides-box .smv-slides .smv-slide.smv-shown:not(:first-child) {
    background-color: #fff;
  }

  /* Placeholder */
  .sirv-pdp-gallery-main .sirv-pdp-gallery-placeholder {
    width: 100%;
    height: 100%;
    max-height: 100%;
    object-fit: contain;
  }
</style>

<?php

if ( !function_exists("sirv_sanitize_custom_styles") ) {
  function sirv_sanitize_custom_styles($data) {
    $string = $data;
    $string = str_replace('\r', "", $string);
    $string = str_replace('\n', "", $string);
    $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');

    return $string;
  }
}


if ( !function_exists("sirv_get_thumbnail_position_class") ) {
  function sirv_get_thumbnail_position_class($thumbnail_position = 'bottom') {
    $thumbnail_position_class = '';

    switch ($thumbnail_position) {
      case 'top':
        $thumbnail_position_class = ' sirv-thumbnail-position-top';
        break;
      case 'bottom':
        $thumbnail_position_class = ' sirv-thumbnail-position-bottom';
        break;
      case 'left':
        $thumbnail_position_class = ' sirv-thumbnail-position-left';
        break;
      case 'right':
        $thumbnail_position_class = ' sirv-thumbnail-position-right';
        break;
    }

    return $thumbnail_position_class;
  }
}

require_once(dirname(__FILE__) . '/../includes/classes/woo.class.php');

global $post;

$woo = new Woo($post->ID);
$woo->add_frontend_assets();

$smv_html = $woo->get_cached_woo_smv_html('_sirv_woo_pdp_cache');


$custom_styles_data = get_option('SIRV_WOO_MV_CONTAINER_CUSTOM_CSS');
$gallery_placeholder_option = get_option('SIRV_WOO_MV_SKELETON');
$is_skeleton = $gallery_placeholder_option == '1' ? true : false;
$is_gallery_placeholder = $gallery_placeholder_option == '3' ? true : false;
$custom_styles = !empty($custom_styles_data) ? sirv_sanitize_custom_styles($custom_styles_data) : '';
$thumbs_pos_class = '';
$thumbs_block_size_style = '';

if ( $is_gallery_placeholder ) {
  $thumbs_count = substr_count($smv_html, 'data-view-id="');
  $smv_thumbnail_position = wp_is_mobile() ? "bottom" : get_option('SIRV_WOO_THUMBS_POSITION');
  $smv_thumbnail_size = (int) get_option('SIRV_WOO_THUMBS_SIZE');

  $css_var_thumbs_size  = '--smv-thumbs-size: ' . $smv_thumbnail_size . 'px;';

  $is_caption = stripos($smv_html, 'data-is-caption="1"') !== false;

  if ( $thumbs_count > 1 ) $thumbs_pos_class = sirv_get_thumbnail_position_class($smv_thumbnail_position);

  if ( in_array($smv_thumbnail_position, array('left', 'right')) && $thumbs_count > 1 ) {
    $thumbs_block_size_style = 'width: ' . $smv_thumbnail_size + 14 . 'px;';
  }

  if ( in_array($smv_thumbnail_position, array('top', 'bottom')) && $thumbs_count > 1 ) {
    $thumbs_block_size_style = 'height: ' . $smv_thumbnail_size + 14 . 'px;';
  }
}

$custom_classes_option = get_option("SIRV_WOO_CONTAINER_CLASSES");
$custom_classes_attr = !empty($custom_classes_option) ? $custom_classes_option : '';

$skeletonClass = $is_skeleton ? ' sirv-skeleton ' : '';
?>
<style>
  .sirv-woo-wrapper {
    <?php
      echo $custom_styles . PHP_EOL;
      echo $css_var_thumbs_size;
    ?>
  }
</style>
<div class="sirv-woo-wrapper sirv-media-size<?php echo $custom_classes_attr; ?>">
  <?php if ( $is_gallery_placeholder ) { ?>
    <div class="sirv-pdp-gallery-wrapper">
      <div class="sirv-pdp-gallery-container<?php echo $thumbs_pos_class; ?>" data-position="<?php echo $smv_thumbnail_position; ?>">
        <div class="sirv-pdp-gallery-thumbnails" style="<?php echo $thumbs_block_size_style; ?>"></div>
        <div class="sirv-pdp-gallery-main-block">
          <div class="sirv-pdp-gallery-main"><?php echo $smv_html; ?></div>
          <div class="sirv-pdp-gallery-caption sirv-woo-smv-caption sirv-woo-smv-caption_<?php echo $post->ID; ?>" <?php if ($is_caption) echo 'style="display:block;"'; ?>></div>
        </div>
      </div>
    </div>
  <?php } else { ?>
    <div class="sirv-skeleton-wrapper">
      <div class="sirv-smv-container <?php echo $skeletonClass; ?>">
        <?php echo $smv_html; ?>
      </div>
    </div>
  <?php } ?>

  <div class="sirv-after-product-smv-wrapper">
    <?php do_action('sirv_after_product_smv'); ?>
  </div>
</div>
