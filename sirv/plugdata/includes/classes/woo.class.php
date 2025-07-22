<?php

defined('ABSPATH') or die('No script kiddies please!');

class Woo
{
  protected $product_id;
  protected $viewer_config = array();
  protected $zoom_config = array(
    'wheel' => 'true',
    'mode' => 'deep'
  );
  protected $spin_config = array();
  protected $is_generated_thumb_html = false;
  protected $wc_cached_products = array();

  protected $cdn_url;

  function __construct($product_id = '')
  {
    $this->product_id = $product_id;
    add_action('add_meta_boxes', [$this, 'add_sirv_gallery_metabox']);
    add_action('add_meta_boxes', [$this, 'add_sirv_product_image_metabox']);
    add_action('add_meta_boxes', [$this, 'remove_wc_prod_image_metabox']);
    add_action('save_post', [$this, 'save_sirv_gallery_data']);

    $this->cdn_url = get_option('SIRV_CDN_URL');

    return $this;
  }


  public function update_smv_cache($product_id, $is_variation){
    $path = $this->get_product_path($product_id, $is_variation);

    if (false === $path) return array("error" => "Path does not exist", "view_path" => '', "cache" => array());

    $cache = $this->get_cache_woo_view_file_data($product_id, $is_variation, true);

    return array("cache" => $cache, "view_path" => parse_url($path, PHP_URL_PATH));
  }


  public static function get_pdp_template()
  {
    include SIRV_PLUGIN_SUBDIR_PATH . 'woo_templates/woo-product-template.php';
  }


  protected function get_variation_status_text($variation_value)
  {
    $status = '';
    switch ($variation_value) {
      case '1':
        $status = 'all';
        break;
      case '2':
        $status = 'byVariation';
        break;
      case '3':
        $status = 'allByVariation';
        break;

      default:
        $status = 'byVariation';
        break;
    }

    return $status;
  }


  protected static function isFIFUActive()
  {
    return is_plugin_active('featured-image-from-url/featured-image-from-url.php');
  }


  protected static function isFIFUProductImage($product_id)
  {
    $fifu_url = get_post_meta($product_id, 'fifu_image_url', true);
    return !empty($fifu_url);
  }


  public function add_frontend_assets()
  {
    $variation_status = $this->get_variation_status_text(get_option('SIRV_WOO_SHOW_VARIATIONS'));

    //wp_register_style('sirv-woo-style', plugins_url('css/wp-sirv-woo.css', __FILE__), array(), '1.0.0');
    //wp_enqueue_style('sirv-woo-style');

    wp_register_script('sirv-woo-js', plugins_url('../../js/wp-sirv-woo.js', __FILE__), array('jquery'), false);
    wp_localize_script('sirv-woo-js', 'sirv_woo_product', array(
      'ajaxurl' => admin_url('admin-ajax.php'),
      'mainID' => $this->product_id,
      'variationStatus' => $variation_status,
    ));

    wp_enqueue_script('sirv-woo-js');
  }


  public function add_admin_edit_scripts()
  {
    wp_register_style('sirv_toast_style', SIRV_PLUGIN_SUBDIR_URL_PATH . 'css/vendor/toastr.css');
    wp_enqueue_style('sirv_toast_style');
    wp_enqueue_script('sirv_toast_js', SIRV_PLUGIN_SUBDIR_URL_PATH . 'js/vendor/toastr.min.js', array('jquery'), false);

    wp_register_script('sirv-woo-admin-js', SIRV_PLUGIN_SUBDIR_URL_PATH . 'js/wp-sirv-woo-admin.js', array('jquery', 'sirv-shortcodes-page', 'sirv_toast_js'), false);
    wp_localize_script('sirv-woo-admin-js', 'sirv_woo_admin_data', array(
      'ajaxurl' => admin_url('admin-ajax.php'),
      'ajaxnonce' => wp_create_nonce('ajax_sirv_woo_admin_nonce'),
    ));
    wp_enqueue_script('sirv-woo-admin-js');

    wp_enqueue_style('sirv-woo-admin-style', SIRV_PLUGIN_SUBDIR_URL_PATH . 'css/wp-sirv-woo-admin.css');
  }


  public function remove_wc_prod_image_metabox()
  {
    global $post;

    if (!$post) return;

    $sirv_item_url = self::get_post_sirv_data($post->ID, 'sirv_woo_product_image', false, false);
    if ($sirv_item_url) {
      remove_meta_box('postimagediv', 'product', 'side');
      $this->add_fake_wc_product_metabox();
    }
  }


  public function add_fake_wc_product_metabox()
  {
    add_meta_box(
      'woo-sirv-fake_wc_product_image',
      __('Product image'),
      [$this, 'render_fake_wc_product_metabox'],
      'product',
      'side',
      'high',
    );
  }


  public function add_sirv_gallery_metabox()
  {
    if (get_post_type() === 'product' && get_option('SIRV_WOO_SHOW_SIRV_GALLERY') == 'show') {
      $this->add_admin_edit_scripts();
      add_meta_box(
        'woo-sirv-gallery',
        __('Sirv Gallery'),
        [$this, 'render_sirv_gallery_metabox'],
        'product',
        'side',
        'default'
      );
    }
  }


  public function add_sirv_product_image_metabox()
  {
    if (get_post_type() === 'product' && get_option('SIRV_WOO_SHOW_MAIN_IMAGE') == 'show') {
      $this->add_admin_edit_scripts();
      add_meta_box(
        'woo-sirv-product-image',
        __('Sirv Product image'),
        [$this, 'render_sirv_product_image_metabox'],
        'product',
        'side',
        'default'
      );
    }
  }


  public function render_fake_wc_product_metabox($post)
  {
      ?>
        <div>WooCommerce product image block has been disabled because Sirv product image has been chosen.</div>
      <?php
  }


  public function render_sirv_product_image_metabox($post)
  {
    $item_pattern = '?thumbnail=266&image';
    $product_id = absint($post->ID);

    self::render_sirv_product_image_html($product_id, $item_pattern);
  }


  public function render_sirv_gallery_metabox($post)
  {
    $item_pattern = '?thumbnail=78&image';
    $product_id = absint($post->ID);

    self::render_sirv_gallery_html($product_id, $item_pattern, 'gallery');
  }


  public static function render_variation_gallery($loop, $variation_data, $variation)
  {
    $item_pattern = '?thumbnail=78&image';
    $variation_id = absint($variation->ID);

    self::render_sirv_gallery_html($variation_id, $item_pattern, 'variation');
  }


  protected static function render_sirv_product_image_html($product_id, $item_pattern)
  {

    $saved_img_url = htmlentities(html_entity_decode(self::get_post_sirv_data($product_id, 'sirv_woo_product_image', false, false)));
    $attachment_id = self::get_post_sirv_data($product_id, 'sirv_woo_product_image_attachment_id', false, false);

    $no_image_placeholder = plugin_dir_url(__FILE__) . "../../assets/no_thumb.png";

    $img_url = $no_image_placeholder;

    if (!empty($saved_img_url)) {
      $img_url = $saved_img_url . $item_pattern;
    } else {
      $saved_img_url = '';
      $attachment_id = -1;
    }
  ?>
    <div id="sirv-woo-product-image-container_<?php echo $product_id; ?>" class="sirv-woo-product-image-container">
      <div class="sirv-woo-product-image">
        <img src="<?php echo $img_url ?>" />
      </div>
      <input type="hidden" id="sirv_woo_product_image_<?php echo $product_id; ?>" name="sirv_woo_product_image_<?php echo $product_id; ?>" value="<?php echo $saved_img_url; ?>">
      <input type="hidden" id="sirv_woo_product_previous_image_<?php echo $product_id; ?>" name="sirv_woo_product_previous_image_<?php echo $product_id; ?>" value="<?php echo $saved_img_url; ?>">
      <input type="hidden" id="sirv_woo_product_image_attachment_id_<?php echo $product_id; ?>" name="sirv_woo_product_image_attachment_id_<?php echo $product_id; ?>" value="<?php echo $attachment_id; ?>">
      <div class="sirv-woo-gallery-toolbar sirv-woo-product-image-toolbar hide-if-no-js">
        <button type="button" class="button button-primary button-large sirv-woo-add-product-image" data-id="<?php echo $product_id; ?>">Set product image</button>
        <button type="button" class="button button-primary button-large sirv-woo-delete-product-image" data-id="<?php echo $product_id; ?>" data-placeholder="<?php echo $no_image_placeholder; ?>">
          <span class="dashicons dashicons-trash"></span>
        </button>
      </div>
      <?php
        if (self::isFIFUActive() && self::isFIFUProductImage($product_id)) {
          echo Utils::showMessage("Choose either Sirv or FIFU for product image, not both.", 'warning');
        }
      ?>
    </div>
  <?php
  }


  protected static function get_gallery_item_url($type, $url, $pattern)
  {
    $model_placeholder = SIRV_PLUGIN_SUBDIR_URL_PATH . 'assets/model-plhldr.svg';
    $gallery_item_url = '';

    switch ($type) {
      case 'model':
        $gallery_item_url = $model_placeholder;
        break;
      case 'online-video':
        $gallery_item_url = $url;
        break;

      default:
        $gallery_item_url = $url . $pattern;
        break;
    }
    return $gallery_item_url;
  }


  protected static function render_sirv_gallery_html($id, $item_pattern, $type)
  {
    $isVariation = $type == 'variation';
    $variation_class = $isVariation ? ' sirv-variation-container' : '';
    $variation_wrapper_class = $isVariation ? ' sirv-variation-wrapper' : '';
    $instance = new self($id);
    $is_view_parse_enable = get_option('SIRV_WOO_IS_USE_VIEW_FILE') == 'on' ? true : false;

    if ( $is_view_parse_enable ) {
      $remote_data = $instance->get_sirv_remote_data($id, $isVariation);
      $folder_path = $instance->get_relative_product_path($id, $isVariation);
    }
  ?>
    <div class="sirv-gallery-wrapper<?php echo $variation_wrapper_class ?>">
      <?php
        if ($type == 'variation') {
          echo '<div class="sirv-gallery-wrapper-title">Sirv variation gallery</div>' . PHP_EOL;
        }
      if ($is_view_parse_enable) {
      ?>
      <div class="sirv-view-gallery" id="sirv-view-gallery-<?php echo $id; ?>">
        <div class="sirv-view-gallery-header">
          <div class="sirv-view-gallery-header_title">Sirv folder media</div>
          <div class="sirv-view-gallery-header__tools">
            <?php if ( $folder_path ){ ?>
              <button type="button" class="button sirv-woo-admin-update-smv-cache" data-product-id="<?php echo $id; ?>" data-type="<?php echo $type ?>">Refresh</button>
            <?php } ?>
          </div>
        </div>
        <div class="sirv-view-gallery-header-path">
          <?php
            if( $folder_path ){
              echo '/' . $folder_path;
            }else{
              if( $isVariation ){
                //echo "Variation has no SKU. Add SKU to automatically show Sirv media";
              }else{
                //echo "Product has no SKU. Add SKU to automatically show Sirv media";
              }
            }
          ?>
        </div>
        <?php
        if ( !empty($remote_data->items) ) {
          $view_data = $remote_data->items;
        ?>
          <ul class="sirv-view-images" id="sirv-view-images-ul-<?php echo $id; ?>">
            <?php
            foreach ($view_data as $view_item) {
              $url = htmlentities(html_entity_decode($view_item->url));
              $thumb_url = self::get_gallery_item_url($view_item->type, $url, $item_pattern);
              echo '<li class="sirv-view-gallery-item" data-type="' . $view_item->type . '">
                <div class="sirv-view-gallery-item-img-wrap">
                                <img class="sirv-view-gallery-item-img" src="' . $thumb_url . '">
                              </div>
              </li>' . PHP_EOL;
            }
            ?>
          </ul>
        <?php
        } else {
          if ( !$folder_path ) {
            if ( $isVariation ) {
              //echo "Variation has no SKU. Add SKU to automatically show Sirv media";
              echo '<div class="sirv-view-gallery-empty"><span>Variation has no SKU. Add SKU to automatically show Sirv media</span></div>';
            } else {
              //echo "Product has no SKU. Add SKU to automatically show Sirv media";
              echo '<div class="sirv-view-gallery-empty"><span>Product has no SKU. Add SKU to automatically show Sirv media</span></div>';
            }
          } else {
            echo '<div class="sirv-view-gallery-empty"><span>No media found</span></div>';
          }
        }
        ?>
        <hr style="margin-right: 9px;" />
      </div>
      <?php } ?>
      <div id="sirv-woo-gallery_<?php echo $id; ?>" class="sirv-woo-gallery-container <?php echo $variation_class ?>" data-id="<?php echo $id; ?>">
        <?php if ($is_view_parse_enable) { ?>
        <div class="sirv-view-gallery-header">
          <div class="sirv-view-gallery-header_title">Extra media</div>
        </div>
        <?php } ?>
        <ul class="sirv-woo-images" id="sirv-woo-images_<?php echo $id; ?>" data-id="<?php echo $id; ?>">
          <?php
          $gallery_json_str = self::get_post_sirv_data($id, '_sirv_woo_gallery_data', false);

          if ($type == 'variation') {
            $saved_img_url = htmlentities(html_entity_decode(self::get_post_sirv_data($id, 'sirv_woo_product_image', false, false)));
            $variation_main_image_attachment_id = self::get_post_sirv_data($id, 'sirv_woo_product_image_attachment_id', false, false);

            if (empty($saved_img_url)) {
              $saved_img_url = '';
              $variation_main_image_attachment_id = -1;
            }
          }

          $data = (array) json_decode($gallery_json_str, true);
          if ($data && $data['items'] && !empty($data['items'])) {
            $items = $data['items'];
            $count = count($items);

            foreach ($items as $item) {
              $video_id = isset($item['videoID']) ? ' data-video-id="' . $item['videoID'] . '" ' : '';
              $video_link = isset($item['videoLink']) ? ' data-video-link="' . $item['videoLink'] . '" ' : '';
              $video_data  = $video_id . $video_link;
              //$thumb_url = empty($video_id) ?  $item['url'] . $item_pattern : $item['url'];
              $url = htmlentities(html_entity_decode($item['url']));
              $thumb_url = self::get_gallery_item_url($item['type'], $url, $item_pattern);
              $caption = isset($item['caption']) ? urldecode($item['caption']) : '';

              $item_id = isset($item['itemId']) ? $item['itemId'] : -1;
              $attachment_id = isset($item['attachmentId']) ? $item['attachmentId'] : -1;

              $delete_type = $item['type'] == 'online-video' ? 'online video' : $item['type'];

              echo '<li class="sirv-woo-gallery-item" data-order="' . $item['order'] . '" data-type="' . $item['type'] . '"data-provider="' . $item['provider'] . '" data-url-orig="' . $url . '"' . $video_data . ' data-view-id="' . $id . '" data-caption="' . $caption . '" data-item-id="' . $item_id . '" data-attachment-id="' . $attachment_id . '">
                              <div class="sirv-woo-gallery-item-img-wrap">
                                <img class="sirv-woo-gallery-item-img" src="' . $thumb_url . '">
                              </div>
                              <input type="text" class="sirv-woo-gallery-item-caption" placeholder="Caption" value="' . $caption . '"/>
                              <ul class="actions">
                                <li><a href="#" class="delete sirv-delete-item tips" data-id="' . $id . '" data-tip="' . esc_attr__('Delete ' . $delete_type, 'woocommerce') . '">' . __('Delete', 'woocommerce') . '</a></li>
                              </ul>
                            </li>' . PHP_EOL;
            }
          } else {
            $data = array('items' => array(), 'id' => $id);
            $gallery_json_str = json_encode(array('items' => array(), 'id' => $id));
          }
          ?>
        </ul>
        <?php if ($type == "gallery") { ?>
          <div class="sirv-modal"></div>
          <div id="sirv-delete-all-images-container_<?php echo $id; ?>" class="sirv-delete-all-images-container" <?php if (isset($count) && $count >= 5) echo 'style="display:block;"'; ?>>
            <a class="button button-primary button-large sirv-woo-delete-all" data-id="<?php echo $id; ?>">Delete all items</a>
          </div>
        <?php } ?>
        <input type="hidden" id="sirv_woo_gallery_data_<?php echo $id; ?>" name="sirv_woo_gallery_data_<?php echo $id; ?>" value="<?php echo htmlentities(html_entity_decode($gallery_json_str)); ?>" />
        <div class="sirv-woo-gallery-toolbar hide-if-no-js">
          <div class="sirv-woo-gallery-toolbar-main">
            <?php if ($type == 'variation') { ?>
              <a class="button button-primary button-large sirv-woo-add-variation-image" data-type="<?php echo $type; ?>" data-id="<?php echo $id; ?>">Set Sirv variation image</a>
              <input type="hidden" id="sirv_woo_product_image_<?php echo $id; ?>" name="sirv_woo_product_image_<?php echo $id; ?>" value="<?php echo $saved_img_url; ?>">
              <input type="hidden" id="sirv_woo_product_previous_image_<?php echo $id; ?>" name="sirv_woo_product_previous_image_<?php echo $id; ?>" value="<?php echo $saved_img_url; ?>">
              <input type="hidden" id="sirv_woo_product_image_attachment_id_<?php echo $id; ?>" name="sirv_woo_product_image_attachment_id_<?php echo $id; ?>" value="<?php echo $variation_main_image_attachment_id; ?>">
            <?php } ?>
            <a class="button button-primary button-large sirv-woo-add-media" data-type="<?php echo $type; ?>" data-id="<?php echo $id; ?>">Add Sirv media</a>
            <a class="button button-primary button-large sirv-woo-add-online-video" data-id="<?php echo $id; ?>">Add online video</a>
          </div>
          <div class="sirv-add-online-videos-container" id="sirv-add-online-videos-container_<?php echo $id; ?>">
            <textarea class="sirv-online-video-links" id="sirv-online-video-links_<?php echo $id; ?>" placeholder="Add links to YouTube or Vimeo videos. One per line..."></textarea>
            <a class="button button-primary button-large sirv-woo-cancel-add-online-videos" data-id="<?php echo $id; ?>">Cancel</a>
            <a class="button button-primary button-large sirv-woo-add-online-videos" data-id="<?php echo $id; ?>">Add video(s)</a>
          </div>
        </div>
      </div>
    </div>
<?php
  }


  public function save_sirv_gallery_data($product_id)
  {
    self::save_sirv_data($product_id);
  }


  public static function save_sirv_variation_data($variation_id, $loop)
  {
    self::save_sirv_data($variation_id, 'variation');
  }


  protected static function save_sirv_product_image($product_image, $product_id, $previous_attachment_id)
  {
    $attachment_id = -1;

    if ( $product_image == '' && $previous_attachment_id == -1 ) return;

    $prev_attach_id = (int) $previous_attachment_id !== -1 ? $previous_attachment_id : null;

    if (!empty($product_image)) {
      $attachment_id = SirvProdImageHelper::insert_attachment($product_image, $product_id, $prev_attach_id);
      SirvProdImageHelper::insert_prod_image($product_id, $attachment_id);
    } else {
      if (!empty($prev_attach_id)) {
        SirvProdImageHelper::remove_attachment($prev_attach_id);
        SirvProdImageHelper::remove_prod_image($product_id);
      }
    }

    self::set_post_sirv_data($product_id, 'sirv_woo_product_image', $product_image, false);
    self::set_post_sirv_data($product_id, 'sirv_woo_product_image_attachment_id', $attachment_id, false);
  }


  protected static function save_sirv_data($product_id, $post_type = 'product')
  {
    if( (isset($_POST['post_type']) && $_POST['post_type'] == 'product') || isset($_POST['product-type']) ){
      $isVariation = $post_type == 'variation' ? true : false;

      $product_id = (isset($_POST['post_ID']) &&  $post_type == 'product') ? $_POST['post_ID'] : $product_id;

      if (!empty($_REQUEST['action']) && ($_REQUEST['action'] == 'editpost' || $_REQUEST['action'] == 'woocommerce_save_variations')) {
        $gallery_data = isset($_POST['sirv_woo_gallery_data_' . $product_id]) ? $_POST['sirv_woo_gallery_data_' . $product_id] : '';
        $product_image = isset($_POST['sirv_woo_product_image_' . $product_id]) ? $_POST['sirv_woo_product_image_' . $product_id] : '';
        $previous_attachment_id = isset($_POST['sirv_woo_product_image_attachment_id_' . $product_id]) ? $_POST['sirv_woo_product_image_attachment_id_' . $product_id] : -1;
        self::set_post_sirv_data($product_id, '_sirv_woo_gallery_data', $gallery_data);
        self::save_sirv_product_image($product_image, $product_id, $previous_attachment_id);

        $instance = new self($product_id);
        if ( $isVariation ) {
          $instance->get_sirv_remote_data($product_id, $isVariation, true);
        } else {
          $instance->update_woo_smv_cache($product_id);
        }
      }
    }
  }


  public function get_woo_product_gallery_html($product_id)
  {
    $html = '';

    $order = get_option('SIRV_WOO_CONTENT_ORDER');

    $media = $this->parse_media_data($product_id, false, array('sirv_local', 'sirv_remote', 'wc_gallery', 'sirv_variations', 'main_image'));

    $sirv_data = $this->merge_object_data($media['sirv_local'], $media['sirv_remote'], true);
    $main_product_image_data = $media['main_image'];
    $wc_gallery = $media['wc_gallery'];
    $sirv_variations = $media['sirv_variations'];

    $all_images = $this->get_all_images_data($main_product_image_data, $sirv_data, $wc_gallery, $sirv_variations, $order);

    if ($all_images) {
      $html = $this->get_pdp_gallery_html($all_images);
    }

    return $html;
  }


  public function update_woo_smv_cache($product_id){
    global $sirv_gbl_woo_cat_is_enable;
    $is_use_view_files = get_option('SIRV_WOO_IS_USE_VIEW_FILE') == 'on' ? true : false;

    $cache_keys = array('_sirv_woo_pdp_cache');

    if ( $sirv_gbl_woo_cat_is_enable ) {
      $cache_keys[] = '_sirv_woo_cat_cache';
    }

    if ($is_use_view_files) {
      $ttl = $this->get_view_file_ttl();

      if ($ttl == 1) {
        return '';
      }
    }

    foreach ($cache_keys as $cache_key) {
      $cache = $this->get_woo_cache_row($product_id, $cache_key);
      if ( !empty($cache) ) {
        require_once(SIRV_PLUGIN_SUBDIR_PATH . 'includes/jobs.php');

        sirv_update_smv_cache_html(array("cache" => $cache, "ttl" => $ttl));
      } else {
        $html = $this->get_woo_smv_html_by_key($product_id, $cache_key);
        $this->get_product_media_and_create_record_in_db($product_id, $cache_key, $html, $ttl);
      }
    }
  }


  public function get_cached_woo_smv_html($cache_key){
    $html = '';
    $ttl = null;

    if ( empty($cache_key) || !$this->product_id ) return $html;

    $cache_option_status = sirv_is_enable_option('SIRV_WOO_SMV_CACHE_IS_ENABLE', 'on');
    if ( ! $cache_option_status ) return $this->get_woo_smv_html_by_key($this->product_id, $cache_key);


    $is_use_view_files = sirv_is_enable_option('SIRV_WOO_IS_USE_VIEW_FILE', 'on');

    if ( $is_use_view_files ) {
      $ttl = $this->get_view_file_ttl();

      if ( $ttl == 1 ) {
        return $this->get_woo_smv_html_by_key($this->product_id, $cache_key);
      }
    }

    $cache = $this->get_woo_cache_row($this->product_id, $cache_key);

    if ( !empty($cache) ) {
      $html = $cache['cache_value'];
      //sirv_qdebug('In cache. Cache key: ' . $cache_key . ' for product ID: ' . $this->product_id . ' with status: ' . $cache['cache_status']);

      if ( !is_null($cache['expired_at']) && time() > strtotime($cache['expired_at']) ) {
        //sirv_qdebug('Cache expired. Cache key: ' . $cache_key . ' for product ID: ' . $this->product_id);

        $background_option_status = sirv_is_enable_option('SIRV_WOO_SMV_CACHE_BACKGROUND_IS_ENABLE', 'on');

        if ( $background_option_status  &&  !in_array($cache['cache_status'], array('EXPIRED', 'DELETED'))) {
          //sirv_qdebug('Background job. Cache key: ' . $cache_key . ' for product ID: ' . $this->product_id);
          $GLOBALS['sirv_jobs']['sirv_update_smv_cache_html'][] = array("cache" => $cache, "ttl" => $ttl);
        } else  {
          //sirv_qdebug('Foreground job. Cache key: ' . $cache_key . ' for product ID: ' . $this->product_id);
          $html = $this->get_woo_smv_html_by_key($this->product_id, $cache_key);

          $cache['cache_value'] = $html;
          $cache['cache_status'] = $html ? 'SUCCESS' : 'EMPTY';
          $cache['expired_at'] = is_null($ttl) ? null : date("Y-m-d H:i:s", time() + $ttl);

          $result = $this->save_data_to_cache($cache);
        }

      }
    } else {
      //sirv_qdebug('Not in cache. Cache key: ' . $cache_key . ' for product ID: ' . $this->product_id);
      //TODO?: add action to store in db object with all images too
      $html = $this->get_woo_smv_html_by_key($this->product_id, $cache_key);

      $this->get_product_media_and_create_record_in_db($this->product_id, $cache_key, $html, $ttl);
    }

    return $html;
  }


  public function get_product_media_and_create_record_in_db($product_id, $cache_key, $cache_value, $ttl=null){
    $data = array(
      'post_id' => $product_id,
      'cache_key' => $cache_key,
      'cache_value' => $cache_value ? $cache_value : '',
      'cache_status' => $cache_value ? 'SUCCESS' : 'EMPTY',
      'created_at' => date("Y-m-d H:i:s"),
      'expired_at' => is_null($ttl) ? null : date("Y-m-d H:i:s", time() + $ttl),
    );

    $result = $this->save_data_to_cache($data);
  }


  public function get_woo_cache_row($post_id, $cache_key){
    global $wpdb;

    $sirv_cache_table = $wpdb->prefix . 'sirv_cache';

    return $wpdb->get_row("SELECT * FROM $sirv_cache_table WHERE `post_id` = $post_id AND `cache_key` = '$cache_key'", ARRAY_A);
  }


  //insert or update data in cache table
  public function save_data_to_cache($data_object)
  {
    global $wpdb;

    $sirv_table_cache = $wpdb->prefix . 'sirv_cache';
    $error = null;

    $post_type = get_post_type($data_object['post_id']);
    if ( $post_type ) $data_object['post_type'] = $post_type;

    //$result = $wpdb->replace($wpdb->postmeta, $data_object, array('%d', '%s', '%s'));
    $result = $wpdb->replace($sirv_table_cache, $data_object);

    if ($result === false) {
      $error = $wpdb->last_error;
    }

    return array("result" => $result, "error" => $error);
  }


  public function get_woo_smv_html_by_key($product_id, $key)
  {
    $html = '';

    switch ($key) {
      case '_sirv_woo_pdp_cache':
        $html = $this->get_woo_product_gallery_html($product_id);
        break;

      case '_sirv_woo_cat_cache':
        $html = $this->get_woo_cat_gallery_html($product_id);
        break;
    }

    return $html;
  }


  public function get_all_pdp_sirv_images_urls()
  {
    $all_urls = array();

    /* $sirv_local_data = (object) $this->get_sirv_local_data($this->product_id);
    $sirv_remote_data = (object) $this->get_sirv_remote_data($this->product_id, false);

    if ( !isset($sirv_local_data->items) ) $sirv_local_data->items = array();
    if ( !isset($sirv_remote_data->items) ) $sirv_remote_data->items = array();

    $sirv_data = $this->merge_object_data($sirv_local_data->items, $sirv_remote_data->items, true);

    $sirv_variations = $this->parse_variations($this->product_id);

    $sirv_data = $this->merge_object_data($sirv_data, $sirv_variations); */

    $main_product_image = array();
    if ($this->isSirvMainImage($this->product_id)) {
      $main_product_image[] = (object) $this->parse_sirv_main_image_as_item($this->product_id);

      if ( !empty($main_product_image) ) {
        $all_urls[] = $main_product_image[0]->url;
      }
    }

    //$all_items = $this->merge_object_data($main_product_image, $sirv_data);



    /* foreach ($all_items as $item) {
      if($item->type == 'image'){
        $all_urls[] = $item->url;
      }
    }*/

    return $all_urls;
  }


  protected function get_sirv_items_data($isVariation, $isEnableCheck)
  {
    $sirv_local_data = (object) $this->get_sirv_local_data($this->product_id);

    if ( $isEnableCheck ) {
      $sirv_remote_data = (object) $this->get_sirv_remote_data($this->product_id, $isVariation);
    } else {
      $sirv_remote_data = (object) self::get_post_sirv_data($this->product_id, '_sirv_woo_viewf_data');
    }

    if (!isset($sirv_local_data->items)) $sirv_local_data->items = array();
    if (!isset($sirv_remote_data->items)) $sirv_remote_data->items = array();

    return $this->merge_object_data($sirv_local_data->items, $sirv_remote_data->items, true);
  }


  public function get_export_data_to_csv_column($isVariation, $isEnableCheck=false)
  {
    $sirv_gallery = $this->get_sirv_items_data($isVariation, $isEnableCheck);
    $sirv_item_urls = array();

    $sirv_main_image_url = self::get_post_sirv_data($this->product_id, 'sirv_woo_product_image', false, false);

    if ($sirv_main_image_url) {
      $sirv_item_urls[] = $sirv_main_image_url;
    }

    foreach ($sirv_gallery as $sirv_item) {
      $sirv_item_urls[] = $sirv_item->url;
    }

    return implode(", ", $sirv_item_urls);
  }


  public function get_woo_cat_gallery_html($product_id)
  {
    $items_source = get_option("SIRV_WOO_CAT_SOURCE");
    $order = $this->get_cat_items_provider_order($items_source);
    $sirv_data = array();
    $wc_gallery = array();
    $sirv_variations = array();

    $is_not_sirv_only = $items_source !== 'sirv_only';
    $is_not_wc_only = $items_source !== 'wc_only';

    $should_load = array('main_image');

    if ( $is_not_wc_only ) {
      $should_load[] = 'sirv_local';
      $should_load[] = 'sirv_remote';
    }

    if ( $is_not_sirv_only ) {
      $should_load[] = 'wc_gallery';
    }

    $media = $this->parse_media_data($product_id, false, $should_load);

    if ( $is_not_wc_only ) {
      $sirv_data = $this->merge_object_data($media['sirv_local'], $media['sirv_remote'], true);
    }

    if ( $is_not_sirv_only ) {
      $wc_gallery = $media['wc_gallery'];
    }

    $main_product_image_data = $media['main_image'];

    if (isset($main_product_image_data->url)) {
      $main_product_image_data->url = htmlentities(html_entity_decode($main_product_image_data->url));
    }

    $all_images = $this->get_all_cat_images_data($main_product_image_data, $sirv_data, $wc_gallery, $sirv_variations, $order);

    return $this->get_cat_gallery_html($all_images);
  }

  //loaded keys: sirv_local, sirv_remote, wc_gallery, sirv_variations, main_image
  protected function parse_media_data($product_id, $is_variation, $should_load = array()){
    global $sirv_gbl_smv_media;

    if ( in_array('sirv_local', $should_load) ) {
      if ( !isset($sirv_gbl_smv_media[$product_id]['sirv_local']) ) {
        $sirv_local_data = (object) $this->get_sirv_local_data($product_id);
        $sirv_gbl_smv_media[$product_id]['sirv_local'] = isset($sirv_local_data->items) ? $sirv_local_data->items : array();
      }
    }

    if ( in_array('sirv_remote', $should_load) ) {
      if ( !isset($sirv_gbl_smv_media[$product_id]['sirv_remote']) ) {
        $sirv_remote_data = (object) $this->get_sirv_remote_data($product_id, $is_variation, true);
        $sirv_gbl_smv_media[$product_id]['sirv_remote'] = isset($sirv_remote_data->items) ? $sirv_remote_data->items : array();
      }
    }

    if ( in_array('wc_gallery', $should_load) ) {
      if ( !isset($sirv_gbl_smv_media[$product_id]['wc_gallery']) ) {
        $sirv_gbl_smv_media[$product_id]['wc_gallery'] = $this->parse_wc_gallery($product_id);
      }
    }

    if ( in_array('sirv_variations', $should_load) ) {
      if ( !isset($sirv_gbl_smv_media[$product_id]['sirv_variations']) ) {
        $sirv_gbl_smv_media[$product_id]['sirv_variations'] = $this->parse_variations($product_id);
      }
    }

    if ( in_array('main_image', $should_load) ) {
      if ( !isset($sirv_gbl_smv_media[$product_id]['main_image']) ) {
        $sirv_gbl_smv_media[$product_id]['main_image'] = $this->get_main_image($product_id);
      }
    }

    return $sirv_gbl_smv_media[$product_id];
  }


  protected function get_cat_items_provider_order($source)
  {
    $order = '1';
    switch ($source) {
      case 'sirv_first':
      case 'sirv_only':
        $order = '1';
        break;

      case 'wc_first':
      case 'wc_only':
        $order = '2';
        break;
    }
    return $order;
  }


  public function get_sirv_local_data($product_id)
  {
    return self::get_post_sirv_data($product_id, '_sirv_woo_gallery_data');
  }


  public function get_sirv_remote_data($product_id, $isVariation, $force_update = false)
  {
    $status = get_option('SIRV_WOO_IS_USE_VIEW_FILE') == 'on' ? true : false;

    if ( $status ) {
      return $this->get_cache_woo_view_file_data($product_id, $isVariation, $force_update);
    }

    return array();
  }


  protected function merge_object_data($first_data, $second_data, $isReverse = false)
  {
    $sirv_data = array();
    if (!$isReverse) {
      $sirv_data = array_merge((array) $first_data, (array) $second_data);
    } else {
      $sirv_data = array_merge((array) $second_data, (array) $first_data);
    }

    return $sirv_data;
  }


  protected function get_cache_woo_view_file_data($product_id, $is_variation, $is_force_update = false)
  {
    $cache_key = '_sirv_woo_view_file_cache';
    //$data = self::get_post_sirv_data($product_id, '_sirv_woo_viewf_data');
    //$status = self::get_post_sirv_data($product_id, '_sirv_woo_viewf_status', false);
    $view_file_data = array('items' => array(), 'is_main_image_from_view_file' => false);

    $view_path = $this->get_product_path($product_id, $is_variation);

    if ( false === $view_path && ! $is_force_update ) {
      //TODO: save status to DB?
      return (object) array(
        'items' => array(),
        'is_main_image_from_view_file' => false,
      );
    }

    $ttl = $this->get_view_file_ttl();
    if ( $ttl == 1 ) {
      $view_file = $this->load_view_file_data($product_id, $view_path);
      return $view_file['data'];
    }

    $cache = $this->get_woo_cache_row($product_id, $cache_key);
    if ( empty($cache) ) {
      $view_file = $this->load_view_file_data($product_id, $view_path);
      $view_file_data = $view_file['data'];

      $cache['post_id'] = $product_id;
      $cache['cache_key'] = $cache_key;
      $cache['cache_value'] = json_encode($view_file['data']);
      $cache['cache_status'] = $view_file['status'];
      $cache['created_at'] = date("Y-m-d H:i:s");
      $cache['expired_at'] = is_null($ttl) ? null : date("Y-m-d H:i:s", time() + $ttl);

      $result = $this->save_data_to_cache($cache);

    } else if ( $is_force_update || in_array($cache['cache_status'], array('EXPIRED', 'DELETED')) ) {

      $view_file_data = (object) json_decode($cache['cache_value']);

      $mtime = isset($view_file_data->mtime) ? $view_file_data->mtime : 0;

      $check_data = $this->check_view_file_changes($view_path, $mtime);

      if ( $check_data['is_view_file_changed'] || in_array($cache['cache_status'], array('EXPIRED', 'DELETED')) ) {
        $view_file = $this->load_view_file_data($product_id, $view_path);
        $view_file["data"]->mtime = $check_data['mtime']; // no file version if check_view_file_changes return false cuz $view_path == false

        $view_file_data = $view_file["data"];

        $cache['cache_value'] = json_encode($view_file["data"]);
        $cache['cache_status'] = $view_file["status"];
      }

      $cache['created_at'] = date("Y-m-d H:i:s");
      $cache['expired_at'] = is_null($ttl) ? null : date("Y-m-d H:i:s", time() + $ttl);

      $result = $this->save_data_to_cache($cache);

    } else {
      $view_file_data = (object) json_decode($cache['cache_value']);
      // $status: EMPTY, SUCCESS, FAILED, EXPIRED, DELETED, NOCACHE

      if ( ! is_null($cache['expired_at']) && time() > strtotime($cache['expired_at']) ) {
        global $sirv_gbl_background_mode;

        if ( ! $sirv_gbl_background_mode ) {
          $GLOBALS['sirv_jobs']['sirv_update_view_file_cache'][] = array("cache" => $cache, "ttl" => $ttl, "view_file_path" => $view_path);
        } else {
          require_once(SIRV_PLUGIN_SUBDIR_PATH . 'includes/jobs.php');

          sirv_update_view_file_cache(array("cache" => $cache, "ttl" => $ttl, "view_file_path" => $view_path));
        }
      }
    }

    return $view_file_data;
  }


  protected function get_view_file_ttl(){
    $ttl_time = (int) get_option('SIRV_WOO_TTL');
    $ttl_time = $ttl_time == 0 ? 24 * 60 * 60 : $ttl_time;

    return $ttl_time;
  }


  protected function get_HEAD_request($url)
  {

    $context = stream_context_create(
      array(
        'http' => array(
          'method' => 'HEAD'
        )
      )
    );

    $headers = get_headers($url, true, $context);

    return $headers;
  }


  protected  function get_product_path($product_id, $isVariation)
  {
    $prod_path = $this->get_relative_product_path($product_id, $isVariation);

    if ( false === $prod_path ) return $prod_path;

    $path = sirv_get_sirv_path($prod_path);

    return $path;
  }


  protected function get_relative_product_path($product_id, $isVariation)
  {
    $fodlers_structure = $isVariation ? get_option('SIRV_WOO_VIEW_FOLDER_VARIATION_STRUCTURE') : get_option('SIRV_WOO_VIEW_FOLDER_STRUCTURE');

    $prod_path = $this->replace_path_params($product_id, $fodlers_structure, $isVariation);

    return $prod_path;
  }


  public function check_view_file_changes($view_path, $mtime)
  {
    $response = array("is_view_file_changed" => false, "mtime" => $mtime);

    if ( $view_path === false) return $response;

    $headers = Utils::get_headers_curl($view_path . '.view');

    $header_mtime = isset($headers['last-modified']) ? strtotime($headers['last-modified']) : false;

    if ( ! $header_mtime || ($header_mtime !== $mtime) ) {
      $response['is_view_file_changed'] = true;
      $response['mtime'] = $header_mtime;
    }

    return $response;
  }


  public function load_view_file_data($product_id, $view_file_path)
  {
    //ini_set('realpath_cache_size', 0);
    $data = array("items" => array(), "is_main_image_from_view_file" => false);
    $sirv_view_data = array();

    if ( $view_file_path === false) return array("data" => (object) $data, "status" => 'FAILED');

    $is_skip_items_to_main_image = false;
    $is_parse_main_image = get_option('SIRV_WOO_MAIN_PRODUCT_IMAGE_FROM_VIEW_FILE') == 'on' ? true : false;
    $context = stream_context_create(array('http' => array('method' => "GET")));
    $json_data = @file_get_contents($view_file_path . '.view?info', false, $context);
    $view_data = @json_decode($json_data);

    if ( (is_object($view_data) && !isset($view_data->_isplaceholder)) && !empty($view_data->assets) && count($view_data->assets) ) {
      $sirv_view_data['status'] = 'SUCCESS';

      //added natural sort for the view file items
      //usort($view_data->assets, array($this, 'compare_assets'));

      $allow_items = ['image', 'video', 'spin', 'model'];

      foreach ($view_data->assets as $index => $asset) {
        if (!in_array($asset->type, $allow_items)) {
          continue;
        }

        if ( (!$is_skip_items_to_main_image && $asset->type !== 'model') && $is_parse_main_image ) {
          $item_url = $view_file_path . '/' . $asset->name;
          $previous_attachment_id = self::get_post_sirv_data($product_id, 'sirv_woo_product_image_attachment_id', false, false);
          $this->save_sirv_product_image($item_url, $product_id, $previous_attachment_id);

          $is_skip_items_to_main_image = true;
          continue;
        }

        $data['items'][] = $this->convert_view_data($product_id, $asset, $index, $view_file_path);
      }
    } else {
      $status = ( is_object($view_data) && !isset($view_data->_isplaceholder) ) ? 'EMPTY' : 'FAILED';
      $sirv_view_data['status'] = $status;
    }

    $data['is_main_image_from_view_file'] = $is_skip_items_to_main_image;
    $sirv_view_data['data'] = (object) $data;

    return $sirv_view_data;
  }


  protected function sortable_string($str)
  {
    $str = preg_replace('/\\.[^.\\s]+$/', '', $str);
    $str = strtolower($str);

    return preg_replace_callback(
      '/(\d+)/is',
      function ($d) {
        return str_pad($d[0], 6, '0', STR_PAD_LEFT);
      },
      $str
    );
  }


  protected function compare_assets($a, $b)
  {
    if ($a->type != 'image' && $b->type == 'image') {
      return  1;
    } else if ($a->type == 'image' && $b->type != 'image') {
      return -1;
    } else if ($a->type != 'image' && $b->type != 'image') {
      return 0;
    }

    $file_name_a = $this->sortable_string($a->name);
    $file_name_b = $this->sortable_string($b->name);

    return ($file_name_a < $file_name_b) ? -1 : 1;
  }


  protected function replace_path_params($product_id, $path, $isVariation)
  {

    $pattern = '/{(product-sku|variation-sku|product-id|variation-id|category-slug)\s*(.*?)}/ims';
    $old_pattern = '/{(.*?)}/mis';
    preg_match_all($pattern, $path, $vars, PREG_SET_ORDER);

    $vars_data = array();

    foreach ($vars as $var) {
      $vars_data[$var[0]] = $this->get_folder_var($var[1], $var[2], $product_id, $isVariation);
    }

    if(
      (isset($vars_data['{product-sku}']) && $vars_data['{product-sku}'] == '') ||
      (isset($vars_data['{category-slug}']) && $vars_data['{category-slug}'] == '') ||
      (isset($vars_data['{variation-sku}']) && $vars_data['{variation-sku}'] == '') ||
      ((isset($vars_data['{product-sku}']) && isset($vars_data['{variation-sku}'])) && $vars_data['{product-sku}'] === $vars_data['{variation-sku}'])
    ) return false;

    $keys = array_keys($vars_data);
    $values = array_values($vars_data);

    return trim(str_replace($keys, $values, $path), '/');
  }


  protected function get_folder_var($str_var, $filters_str, $product_id, $isVariation){
    $value = '';
    $main_product_id = $isVariation ? wp_get_post_parent_id($product_id) : $product_id;

    switch ($str_var) {
      case 'product-id':
        $value = $main_product_id;
        break;

      case 'product-sku':
        $value = $this->get_product_sku($main_product_id);

        if( !empty($filters_str) ) {
          require_once(SIRV_PLUGIN_SUBDIR_PATH . 'includes/classes/view.file.path.filters.class.php');
          $view_filter = new ViewFilePathFilters($filters_str);
          $value = $view_filter->run_filters($value);
        }
        break;

      case 'category-slug':
        $value = $this->get_category_slug($main_product_id);
        break;

      case 'variation-id':
        $value = $product_id;
        break;

      case 'variation-sku':
        $value = $this->get_variation_sku($product_id);

        if (!empty($filters_str)) {
          require_once(SIRV_PLUGIN_SUBDIR_PATH . 'includes/classes/view.file.path.filters.class.php');
          $view_filter = new ViewFilePathFilters($filters_str);
          $value = $view_filter->run_filters($value);
        }
        break;
    }
    return $value;
  }


  protected function get_category_slug($product_id)
  {
    $terms = get_the_terms($product_id, 'product_cat');
    $category = count($terms) ? $this->get_sub_category($terms) : $terms[0];

    return $category->slug;
  }


  protected function get_sub_category($categories)
  {
    $subcategory = '';
    foreach ($categories as $category) {
      if ($category->parent !== 0) {
        $subcategory = $category;
        break;
      }
    }

    return $subcategory;
  }



  protected function get_product_sku($product_id)
  {
    global $sirv_gbl_product_sku_cache;

    if (isset($sirv_gbl_product_sku_cache[$product_id])) {
      return $sirv_gbl_product_sku_cache[$product_id];
    }

    try {
      $product = new WC_Product($product_id);
      $sku = $product->get_sku();
      $sirv_gbl_product_sku_cache[$product_id] = $sku;
    } catch (Exception $e) {
      $sku = '';
      $sirv_gbl_product_sku_cache[$product_id] = $sku;
    }

    return $sku;
  }


  protected function get_variation_sku($product_id)
  {
    global $sirv_gbl_variation_sku_cache;

    if (isset($sirv_gbl_variation_sku_cache[$product_id])) {
      return $sirv_gbl_variation_sku_cache[$product_id];
    }

    try {
      $variation = new WC_Product_Variation($product_id);
      $sku = $variation->get_sku();
      $sirv_gbl_variation_sku_cache[$product_id] = $sku;
    } catch (Exception $e) {
      $sku = '';
      $sirv_gbl_variation_sku_cache[$product_id] = $sku;
    }
    return $sku;
  }


  protected function get_product_slug($product_id)
  {
    $product = new WC_Product($product_id);
    return $product->get_slug();
  }


  protected function convert_view_data($product_id, $asset, $index, $path)
  {
    return (object) array(
      'url' => $path . '/' . $asset->name,
      'type' => $asset->type,
      'provider' => 'sirv',
      'order' => $index,
      'viewId' => $product_id
    );
  }


  //order == 3 - sirv content only
  protected function get_all_images_data($main_image, $sirv_images, $wc_images, $sirv_variations, $order)
  {
    $items = array();
    $is_show_all_items = get_option('SIRV_WOO_SHOW_VARIATIONS') !== '2' ? true : false;
    $is_empty_main_image = empty((array) $main_image);

    if ((empty($sirv_images) && empty($wc_images)) || (empty($sirv_images) && $order == '3')) {
      if ((!$is_show_all_items && $is_empty_main_image) || (empty($sirv_variations) && $is_empty_main_image) || $is_empty_main_image) {
        $sirv_images[] = $this->get_wc_placeholder_as_item();
      }
    }

    $items = (array) $this->merge_items($order, $sirv_images, $wc_images);

    $items = array_merge($items, (array) $sirv_variations);

    if (!$is_empty_main_image) {
      array_unshift($items, $main_image);
    }

    if ($is_show_all_items) {
      $items = (array) $this->get_filtered_duplicates($items);
    }

    $items = (object) apply_filters("sirv_pdp_gallery", $items, $this->product_id);

    return $this->fix_order($items);
  }


  protected function get_all_cat_images_data($main_image, $sirv_images, $wc_images, $order)
  {
    $items = (object) array();
    $is_empty_main_image = empty((array) $main_image);

    if ( $is_empty_main_image && empty($sirv_images) && empty($wc_images) ) return $items;

    $items = $this->merge_items($order, $sirv_images, $wc_images);

    if ( !$is_empty_main_image ) {
      $items = (array) $items;
      array_unshift($items, $main_image);
      $items = (object) $items;
    }

    $items = $this->get_filtered_duplicates($items);

    return $this->fix_order($items);
  }


  /*
  $items: object
  */
  protected function get_filtered_duplicates($items)
  {
    $unique_items = array();
    $arr_items = (array) $items;

    if (empty($arr_items)) return (object) $unique_items;

    $duplicates = array();
    for ($i = 0; $i < count($arr_items); $i++) {
      $item = $arr_items[$i];
      $ids_groups = array((int) $arr_items[$i]->viewId);
      $duplicate = $arr_items[$i]->url;
      $caption = isset($arr_items[$i]->caption) ? $arr_items[$i]->caption : '';
      for ($j = $i + 1; $j < count($arr_items); $j++) {
        if (in_array($duplicate, $duplicates)) continue;

        if ($arr_items[$i]->url === $arr_items[$j]->url) {
          if (empty($caption) && !empty($arr_items[$j]->caption)) {
            $caption = $arr_items[$j]->caption;
          }
          if (!in_array($arr_items[$j]->viewId, $ids_groups)) {
            $ids_groups[] = (int) $arr_items[$j]->viewId;
          }
        }
      }
      if (!in_array($duplicate, $duplicates)) {
        $item->groups = $ids_groups;
        $item->caption = $caption;
        $unique_items[] = $item;
        $duplicates[] = $duplicate;
      }
    }

    //array_unique($unique_items, SORT_REGULAR);

    return (object) $unique_items;
  }


  protected function fix_order($items)
  {
    $fixed_items = array();
    $order = 0;

    foreach ($items as $item) {
      if ( empty($item->url) ) continue;

      $item->order = $order;
      $fixed_items[] = $item;

      $order++;
    }

    return (object) $fixed_items;
  }


  protected function get_wc_placeholder_as_item()
  {
    return (object) array(
      'url' => wc_placeholder_img_src('full'),
      'type' => 'wc_placeholder_image',
      'provider' => 'woocommerce',
      'viewId' => $this->product_id,
    );
  }


  protected function merge_items($order, $sirv_items, $wc_items)
  {
    $items = (object) array();

    switch ($order) {
      case '1':
        $items = (object) array_merge((array) $sirv_items, (array) $wc_items);
        break;
      case '2':
        $items = (object) array_merge((array) $wc_items, (array) $sirv_items);
        break;
      case '3':

      default:
        $items = $sirv_items;
        break;
    }

    return $items;
  }


  protected function parse_variations($product_id)
  {
    $order = get_option('SIRV_WOO_CONTENT_ORDER');
    $variations_ids = $this->get_product_variations_ids($product_id);
    $all_items = array();

    foreach ($variations_ids as $variation_id) {
      $items = (object) array();
      //$sirv_variation = (object) array();
      $sirv_variation = $this->get_variation_data($variation_id);
      $sirv_main_variation_image = '';

      if ($this->isSirvMainImage($variation_id)) {
        $sirv_main_variation_image = array($this->parse_sirv_main_image_as_item($variation_id));
      }

      if (!empty($sirv_variation)) {
        if ($sirv_main_variation_image) {
          $sirv_variation = $this->merge_items(1, $sirv_main_variation_image, $sirv_variation);
        }
        $items = (object) array_merge((array) $items, (array) $sirv_variation);
      }


      if ($order !== '3' && !$sirv_main_variation_image) {
        $wc_variation = $this->parse_wc_variation($variation_id, $product_id);
        if (!empty($wc_variation)) {
          $items = $this->merge_items($order, $items, $wc_variation);
        }
      }

      $all_items = array_merge((array) $all_items, (array) $items);
    }

    return $all_items;
  }


  public function get_variation_data($variation_id)
  {
    $variation_data = (object) array();

    $sirv_local_variation = $this->get_sirv_local_data($variation_id);
    $sirv_remote_variation = $this->get_sirv_remote_data($variation_id, true, true);

    $sirv_local_variation = $this->fix_item_viewIds($variation_id, $sirv_local_variation);


    if (!empty($sirv_local_variation->items) && !empty($sirv_remote_variation)) {
      $variation_data = $this->merge_object_data($sirv_local_variation->items, $sirv_remote_variation->items, true);
    } else {
      if (empty($sirv_local_variation->items) && empty($sirv_remote_variation->items)) {
        $variation_data = (object) array();
      } else {
        if (empty($sirv_local_variation->items)) {
          $variation_data = $sirv_remote_variation->items;
        } else {
          $variation_data = $sirv_local_variation->items;
        }
      }
    }

    return $variation_data;
  }


  function fix_item_viewIds($currnet_item_id, $sirv_variation_data)
  {
    if (!empty($sirv_variation_data->items) && $sirv_variation_data->items[0]->viewId !== $currnet_item_id) {
      foreach ($sirv_variation_data->items as $item) {
        $item->viewId = $currnet_item_id;
      }
      $sirv_variation_data->id = $currnet_item_id;
    }

    return $sirv_variation_data;
  }


  public function parse_wc_variation($variation_id, $product_id, $is_product_id_check = false)
  {
    $variation_img_id = $this->get_variation_img_id($variation_id);

    if ($variation_img_id === -1) return array();

    if (get_option('SIRV_WOO_SHOW_MAIN_VARIATION_IMAGE') == 2 || $is_product_id_check) {
      $main_product_img_id = $this->get_product_image_id($product_id);

      if ($variation_img_id == $main_product_img_id) return array();
    }

    return array($this->get_sirv_item_data($variation_img_id, $variation_id));
  }


  protected function get_variation_img_id($variation_id)
  {
    if (empty($variation_id) || $variation_id == 0) return -1;

    $variation = new WC_Product_Variation($variation_id);
    return $variation->get_image_id();
  }


  protected function get_product_image_id($product_id)
  {
    $product = new WC_Product($product_id);
    return $product->get_image_id();
  }


  protected function get_product_variations_ids($product_id)
  {
    $product = new WC_Product_Variable($product_id);
    $variations = $product->get_available_variations();
    $variations_ids = array();
    foreach ($variations as $variation) {
      $variations_ids[] = $variation['variation_id'];
    }

    return $variations_ids;
  }


  protected function is_default_variation()
  {
    $product  = new WC_Product($this->product_id);
    return !empty($product->get_default_attributes());
  }


  protected function get_default_variation_id()
  {
    $default_variation_id = -1;
    $product  = new WC_Product($this->product_id);
    $default_attributes = $product->get_default_attributes();
    if (!empty($default_attributes)) {
      $product_variable = new WC_Product_Variable($this->product_id);
      foreach ($product_variable->get_available_variations() as $variation_values) {
        foreach ($variation_values['attributes'] as $key => $attribute_value) {
          $attribute_name = str_replace('attribute_', '', $key);
          $default_value = $product_variable->get_variation_default_attribute($attribute_name);
          if ($default_value == $attribute_value) {
            $is_default_variation = true;
          } else {
            $is_default_variation = false;
            break; // Stop this loop to start next main loop
          }
        }
        if ($is_default_variation) {
          $default_variation_id = $variation_values['variation_id'];
          break; // Stop the main loop
        }
      }
    }

    return $default_variation_id;
  }


  protected function get_main_image($product_id)
  {
    $main_image_item = (object) array();

    /* if (!empty($main_image_id) && !$this->isSirvMainImage($product_id)) {
      //$items[] = $this->parse_wc_main_image_as_item($main_image_id, $product_id);
      array_unshift($gallery, $main_image_id);
    } */

    //$items[] = $this->get_sirv_item_data($image_id, $product_id);

    if ($this->isSirvMainImage($product_id)) {
      //$sirv_local_data->items[] = $this->parse_sirv_main_image_as_item($this->product_id);
      $main_image_item = $this->parse_sirv_main_image_as_item($this->product_id);
    } else {
      $product = $this->get_cached_wc_product($product_id);
      $main_image_id = $product->get_image_id();

      if (!empty($main_image_id)) {
        $main_image_item = $this->get_sirv_item_data($main_image_id, $product_id);
      }
    }

    return $main_image_item;
  }


  protected function parse_wc_gallery($product_id)
  {
    $items = array();
    //$product = new WC_product($product_id);
    $product = $this->get_cached_wc_product($product_id);
    //$main_image_id = $product->get_image_id();
    $gallery = $product->get_gallery_image_ids();

    /* if (!empty($main_image_id)) array_unshift($gallery, $main_image_id); */
    /* if (!empty($main_image_id) && !$this->isSirvMainImage($product_id)) {
      //$items[] = $this->parse_wc_main_image_as_item($main_image_id, $product_id);
      array_unshift($gallery, $main_image_id);
    } */

    foreach ($gallery as $image_id) {
      $items[] = $this->get_sirv_item_data($image_id, $product_id);
    }

    return $items;
  }


  protected function get_cached_wc_product($product_id)
  {
    if (!isset($this->wc_cached_products[$product_id])) {
      $this->wc_cached_products[$product_id] = $this->get_wc_product($product_id);
    }

    return $this->wc_cached_products[$product_id];
  }


  protected function get_wc_product($product_id)
  {
    $product = new WC_product($product_id);
    return $product;
  }


  protected function parse_wc_main_image_as_item($main_image_id, $product_id)
  {
    /*     $product = new WC_product($product_id);
    $main_image_id = $product->get_image_id(); */

    return $this->get_sirv_item_data($main_image_id, $product_id);
  }


  protected function parse_sirv_main_image_as_item($product_id)
  {
    $main_item = (object) array();
    $sirv_main_item = self::get_post_sirv_data($product_id, 'sirv_woo_product_image', false, false);

    if (!empty($sirv_main_item)) {
      $attachment_id = get_post_thumbnail_id($product_id);
      $att_metadata = wp_get_attachment_metadata($attachment_id);
      $item_type = '';

      if (empty($att_metadata['sirv_type'])) {
        $ext = pathinfo(Utils::clean_uri_params($sirv_main_item), PATHINFO_EXTENSION);
        $item_type = Utils::get_sirv_type_by_ext($ext);
      } else {
        $item_type = $att_metadata['sirv_type'];
      }

      $main_item = (object) array("url" => $sirv_main_item, "type" => $item_type, "provider" => "sirv", "viewId" => $product_id);
    }

    return $main_item;
  }


  protected function isSirvMainImage($product_id)
  {
    $sirv_main_item = self::get_post_sirv_data($product_id, 'sirv_woo_product_image', false, false);

    return (!empty($sirv_main_item));
  }


  protected function get_sirv_item_data($image_id, $product_id)
  {
    $url = '';
    $provider = 'woocommerce';

    $wp_image_arr = wp_get_attachment_image_src($image_id, 'full');

    if ( !empty($wp_image_arr) ) {
      $url = Utils::clean_uri_params($wp_image_arr[0]);
      $provider = $this->is_sirv_item($url) ? 'sirv' : 'woocommerce';
    }

    return (object) array('url' => $url, 'type' => 'image', 'provider' => $provider, 'viewId' => $product_id);
  }


  protected function is_sirv_item($url)
  {
    if (empty($url)) return false;

    $sirv_url = empty($this->cdn_url) ? 'sirv.com' : $this->cdn_url;

    return stripos($url, $sirv_url) !== false;
  }


  protected function add_profile($src, $type)
  {
    $url = $src;
    if (in_array($type, array('image', 'spin'))) {
      $profile = wp_is_mobile() ? get_option('SIRV_WOO_PRODUCTS_MOBILE_PROFILE') : get_option('SIRV_WOO_PRODUCTS_PROFILE');
      if (!empty($profile)) {
        $url .= '?profile=' . $profile;
      }
    }
    return $url;
  }

  public function remove_script_tag($string)
  {
    return preg_replace('/<(\/)*script.*?>/im', '', $string);
  }


  protected function manage_disable_state($items)
  {
    $items_count = count((array) $items);
    $dis_items_count = 0;

    $is_all_items_disabled = false;

    $variation_option = get_option('SIRV_WOO_SHOW_VARIATIONS');
    $is_all_variations = $variation_option !== '2' ? true : false;
    $variation_status = $this->get_variation_status_text($variation_option);
    $default_variation_id = $this->get_default_variation_id();
    $default_shows_id = $default_variation_id != -1 ? $default_variation_id : $this->product_id;

    foreach ($items as $item) {
      $is_item_disabled = $this->is_item_disabled($item, $items_count, $is_all_variations, $default_variation_id, $default_shows_id);
      $item->isDisabled = $is_item_disabled;

      if ($is_item_disabled) $dis_items_count++;
    }

    if ($items_count == $dis_items_count) $is_all_items_disabled = true;

    return array($items, $is_all_items_disabled, $is_all_variations, $variation_status);
  }


  protected function is_item_disabled($item, $i_count, $is_all_variations, $default_variation_id, $default_shows_id)
  {
    if (($is_all_variations && $default_variation_id == -1) || $i_count == 1) return false;
    else if ((int) $item->viewId !== (int) $default_shows_id) return true;
    else return false;
  }


  protected function is_disable_item_str($item, $is_all_items_disabled)
  {
    $disable_item = '';

    if ($item->isDisabled) {
      if ($is_all_items_disabled) {
        if ($item->viewId == $this->product_id) {
          $disable_item = '';
        } else {
          $disable_item = 'data-disabled';
        }
      } else {
        $disable_item = 'data-disabled';
      }
    }
    return $disable_item;
  }


  protected function get_filtered_cat_content($json_str)
  {
    $filtered_items = array();

    if (!isset($json_str) || empty($json_str)) return $filtered_items;

    $data = json_decode($json_str, true);

    foreach ($data as $item => $itemStatus) {
      if ($itemStatus == 'yes') $filtered_items[] = $item;
    }

    return $filtered_items;
  }


  protected function get_cat_gallery_html($items)
  {

    if ( empty((array) $items) ) return $this->get_empty_gallery_html();

    $filteredContent = $this->get_filtered_cat_content(get_option('SIRV_WOO_CAT_CONTENT'));
    $swap = json_decode(get_option('SIRV_WOO_CAT_SWAP_METHOD'), true);
    $isHoverZoom = get_option('SIRV_WOO_CAT_ZOOM_ON_HOVER') == 'yes' ? true : false;
    $showing_method = get_option("SIRV_WOO_CAT_SHOWING_METHOD");
    $gallery_cat_html = '';
    $viewer_options = array(
      "arrows" => $swap['arrows'] == 'yes' ? true : false,
      "thumbnails.type" => "bullets",
      "thumbnails.enable" => $swap['bullets'] == 'yes' ? true : false,
      "thumbnails.position" => "bottom",
      "fullscreen.enable" => false,
      "zoom.mode" => "inner",
      "zoom.trigger" => "hover",
      "zoom.hint.enable" => false,
      "video.autoplay" => true,
      "video.loop" => true,
      "video.volume" => 0,
      "video.controls.enable" => false,
      /* "zoom.ratio" => 1, */

    );
    $items_count = (int) get_option('SIRV_WOO_CAT_ITEMS');
    $item_count = 1;
    $saved_profile = get_option('SIRV_WOO_CAT_PROFILE');
    $profile = !empty($saved_profile) ? "profile=$saved_profile" : "";


    if ($items_count == 2) {
      $image_items = self::filter_object_by_item_type($items, 'image');
      $image_items_count = count($image_items);

      if ($image_items_count == 0) {
        return $this->get_empty_gallery_html();
      }

      if ($image_items_count == 1) {
        $src = $image_items[0]->provider == 'sirv' ? $image_items[0]->url . '?w=10&colorize.color=efefef"' : $image_items[0]->url;
        $data_src = $image_items[0]->provider == 'sirv' ? $image_items[0]->url . '?' . $profile : $image_items[0]->url;

        $gallery_cat_html = '
          <div class="sirv-figure">
            <img class="Sirv image-main" src="'. $src .'" data-src="' . $data_src . '">
          </div>
        ' . PHP_EOL;
      }

      if ($image_items_count >= 2) {
        $hover_styles = '
          <style>
              .sirv-figure {
                  position: relative;
                  width: 360px; /* can be omitted for a regular non-lazy image */
                  max-width: 100%;
              }
              .sirv-figure img.image-hover {
                position: absolute;
                top: 0;
                right: 0;
                left: 0;
                bottom: 0;
                object-fit: contain;
                opacity: 0;
                transition: opacity .2s;
              }
              .sirv-figure:hover img.image-hover {
                opacity: 1;
              }
          </style>
        ' . PHP_EOL;

        $src = $image_items[0]->provider == 'sirv' ? $image_items[0]->url . '?w=10&colorize.color=efefef"' : $image_items[0]->url;
        $data_src = $image_items[0]->provider == 'sirv' ? $image_items[0]->url . '?' . $profile : $image_items[0]->url;
        $secont_data_src = $image_items[1]->provider == 'sirv' ? $image_items[1]->url . '?' . $profile : $image_items[1]->url;

        $gallery_cat_html  = $hover_styles;
        $gallery_cat_html .= '
          <div class="sirv-figure">
            <img class="Sirv image-main" src="' . $src . '" data-src="' .  $data_src . '">
            <img class="Sirv image-hover" data-src="' . $secont_data_src . '">
          </div>
        ' . PHP_EOL;
      }
    } else {
      $gallery_cat_html = '<div class="Sirv" id="sirv-woo-cat-gallery_' . $this->product_id . '" ' . $this->render_viewer_options($viewer_options) . '>' . PHP_EOL;

      foreach ($items as $item) {
        if ( !in_array($item->type, $filteredContent) ) continue;

        if ( $item->provider == "sirv" ) {
          $zoom = $isHoverZoom ? self::get_zoom_class($item->type) : '';
          if ( $item->type === 'spin' ) {
            $showing_method_pattern = $showing_method == "static" ? "?thumb" : "?image";
            $gallery_cat_html .= '<img data-src="' . $item->url . $showing_method_pattern  . '&' . $profile . '"' . '/>' . PHP_EOL;
          } else {
            $gallery_cat_html .= '<div data-src="' . $item->url  . '?' . $profile . '" ' . $zoom . '></div>' . PHP_EOL;
          }
        } else {
          $gallery_cat_html .= '<img data-type="static" data-src="' . $item->url . '">' . PHP_EOL;
        }

        if ( $item_count >= $items_count ) {
          $item_count += 1;
          break;
        }

        $item_count += 1;
      }

      if ( $item_count - 1 == 0 ) {
        $wc_placeholder_item = $this->get_wc_placeholder_as_item();
        $static = $wc_placeholder_item->provider == 'woocommerce' ? 'data-type="static"' : '';
        $gallery_cat_html .= '<img ' . $static . ' data-src="' . $wc_placeholder_item->url . '">' . PHP_EOL;
      }

      $gallery_cat_html .= "</div>" . PHP_EOL;
    }

    if ( $gallery_cat_html == '' ) return $this->get_empty_gallery_html();

    return $gallery_cat_html;
  }


  protected function get_empty_gallery_html()
  {
    //TODO cache this?
    $wc_placeholder_item = $this->get_wc_placeholder_as_item();
    $src = $wc_placeholder_item->provider == 'sirv' ? $wc_placeholder_item->url . '?w=10&colorize.color=efefef"' : $wc_placeholder_item->url;

    return
      '<div class="sirv-figure" id="sirv-woo-cat-gallery_' . $this->product_id . '">
            <img class="Sirv image-main" src="'. $src .'" data-src="'. $wc_placeholder_item->url .'">
          </div>
        ' . PHP_EOL;
  }


  protected function filter_object_by_item_type($items, $item_type)
  {
    $filtered_arr = array();
    foreach ($items as $item) {
      if ( $item->type === $item_type ) {
        $filtered_arr[] = $item;
      }
    }

    return $filtered_arr;
  }


  protected function get_pdp_gallery_html($items)
  {
    $items_html = '';
    $isCaption = false;
    $skeleton_option = get_option('SIRV_WOO_MV_SKELETON');
    $isSkeleton = $skeleton_option == '1' ? true : false;

    /* $mv_custom_options = $this->remove_script_tag(get_option('SIRV_WOO_MV_CUSTOM_OPTIONS'));
    $mv_custom_options_block = !empty($mv_custom_options) ? '<script nowprocket>' . $mv_custom_options . '</script>' . PHP_EOL : ''; */

    $mv_custom_css = get_option('SIRV_WOO_MV_CUSTOM_CSS');
    $mv_custom_css = !empty($mv_custom_css) ? '<style>' . $mv_custom_css . '</style>' . PHP_EOL : '';

    $max_height = get_option("SIRV_WOO_MAX_HEIGHT");
    $max_height_style = empty($max_height) ? '' : '<style>.sirv-woo-wrapper .Sirv > .smv{ max-height: ' . $max_height . 'px; }</style>';

    list($items, $is_all_items_disabled, $is_all_variations, $variation_status) = $this->manage_disable_state($items);

    $viewer_options = array();
    $smv_order_content = get_option('SIRV_WOO_SMV_CONTENT_ORDER');
    if (!empty(json_decode($smv_order_content))) $viewer_options['itemsOrder'] = '[\'' . implode("','", json_decode($smv_order_content)) . '\']';

    if ( $isSkeleton ) $viewer_options['autostart'] = 'created';

    //$ids_data = array();

    $pin_data = json_decode(get_option('SIRV_WOO_PIN'), true);

    $existings_ids = array();
    $item_by_variation_id = array();
    $unique_ids = array();

    foreach ($items as $item) {
      if (empty($item->url)) continue;

      $is_item_disabled = $this->is_disable_item_str($item, $is_all_items_disabled);
      $src = $item->type == 'online-video' ? $item->videoLink : htmlentities(html_entity_decode($item->url));
      $zoom = self::get_zoom_class($item->type);
      $caption = isset($item->caption) ? urldecode($item->caption) : '';
      if ($caption) $isCaption = true;

      $existings_ids[] = isset($item->groups) ? $item->groups : (int) $item->viewId;

      if ($variation_status === 'allByVariation' && isset($item->groups)) {
        foreach ($item->groups as $product_id) {
          if ($product_id != $this->product_id &&  !in_array($product_id, $unique_ids)) {
            $unique_ids[] = $product_id;
            $item_by_variation_id[$product_id] = (int) $item->order;
          }
        }
      }

      if ($item->provider !== 'woocommerce') {
        $items_html .= '<div' . $this->get_data_group($item, $is_all_variations) . $this->pin_item($pin_data, $item->type, $src) . 'data-src="' . $this->add_profile($src, $item->type) . '"' . $zoom . ' data-view-id="' . $item->viewId . '" data-order="' . $item->order . '" data-slide-caption="' . $caption . '" ' . $is_item_disabled . '></div>' . PHP_EOL;
      } else {
        $items_html .= '<img' . $this->get_data_group($item, $is_all_variations) . 'data-src="' . $src . '" data-type="static" data-view-id="' . $item->viewId . '" data-order="' . $item->order . '" data-slide-caption="' . $caption . '" ' . $is_item_disabled . ' />' . PHP_EOL;
      }

      //$ids_data[$item->viewId][] = (int) $item->order;
    }

    $opacityClass = $isSkeleton ? ' sirv-woo-opacity-zero' : '';


    $existings_ids = $variation_status === 'byVariation' ? $existings_ids : array_merge(...$existings_ids);
    $existings_ids = array_values(array_unique($existings_ids));
    $data_item_by_variation_id = 'data-item-by-variation-id="' . htmlspecialchars(json_encode($item_by_variation_id), ENT_QUOTES, 'UTF-8') . '" ';


    $json_data_block = '<div style="display: none;" ' . $data_item_by_variation_id . 'data-existings-ids="' . htmlspecialchars(json_encode($existings_ids), ENT_QUOTES, 'UTF-8') . '" id="sirv-woo-gallery_data_' . $this->product_id . '" data-is-caption="' . $isCaption . '"></div>' . PHP_EOL;

    return /* $mv_custom_options_block . PHP_EOL .  */ $json_data_block . '<div class="Sirv' . $opacityClass . '" id="sirv-woo-gallery_' . $this->product_id . '"' . $this->render_viewer_options($viewer_options) . '>' . PHP_EOL . $items_html . '</div>' . PHP_EOL . $mv_custom_css . $max_height_style;
  }


  protected function get_data_group($item, $is_all_variations)
  {
    $groups = isset($item->groups) ? $item->groups : array($item->viewId);
    if ($item->viewId == $this->product_id || $is_all_variations) array_unshift($groups, 'main');

    return ' data-group="' . implode(",", $groups) . '" ';
  }


  protected function render_viewer_options($options)
  {
    if (empty($options)) return '';
    $options_html = ' data-options="';
    foreach ($options as $key => $value) {
      $val = $this->bool_to_string($value);
      $options_html .= "{$key}:{$val};";
    }
    $options_html .= '"';

    return $options_html;
  }


  protected function bool_to_string($value)
  {
    if (is_bool($value)) {
      return $value ? 'true' : 'false';
    }
    return $value;
  }


  protected function pin_item($pin_data, $item_type, $src)
  {
    $start = ' data-pinned="start" ';
    $end = ' data-pinned="end" ';

    $pin = '';
    $pin_var = isset($pin_data[$item_type]) ? $pin_data[$item_type] : 'no';

    if ($pin_var !== 'no') {
      if ($item_type == 'image') {
        $expression = $this->convert_img_pattern_to_regex($pin_data['image_template']);
        if ($this->check($src, $expression)) {
          $pin = ($pin_var == 'left') ? $start : $end;
        }
      } else {
        $pin = ($pin_var == 'left') ? $start : $end;
      }
    }

    return $pin;
  }


  protected function convert_img_pattern_to_regex($img_pattern)
  {
    return str_replace('\*', '.*', preg_quote($img_pattern, '/'));
  }


  protected function check($src, $expression)
  {
    return preg_match('/' . $expression . '/', $src) != false;
  }


  protected static function get_zoom_class($type)
  {
    $isZoom = get_option('SIRV_WOO_ZOOM_IS_ENABLE');
    $zoom = '';

    if ($isZoom == '2') {
      $zoom = '';
    } else {
      $zoom = $type == 'image' ? ' data-type="zoom" ' : '';
    }

    return $zoom;
  }


  public static function get_post_sirv_data($product_id, $field_id, $isJson = true, $isAssociativeArray = false)
  {
    $data = $isJson ? (object) array() : null;

    if (metadata_exists('post', $product_id, $field_id)) {
      $post_meta_data = get_post_meta($product_id, $field_id, true);
      $data = $isJson ? json_decode($post_meta_data, $isAssociativeArray) : $post_meta_data;
    }

    return $data;
  }


  protected static function set_post_sirv_data($product_id, $field_id, $data, $isJson = true)
  {
    $saved_data = '';

    //$data_type = is_string($data) ? 'string' : 'array';
    $is_str = is_string($data) ? true : false;

    if ($is_str) {
      if (!empty($data)) {
        $saved_data = $data;
      }
    }
    $saved_data = ($isJson && !$is_str) ? json_encode($data) : $data;

    update_post_meta($product_id, $field_id, $saved_data);
  }


  public function set_config($type, $config) {}


  protected function generate_config($config)
  {
    return http_build_query($config, '', ';');
  }
}
?>
