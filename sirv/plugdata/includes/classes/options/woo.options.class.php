<?php
defined('ABSPATH') or die('No script kiddies please!');

include_once "option.generator.class.php";

class Woo_options extends Options_generator{

  protected static function render_sirv_content_cache($option){
    $without_content = 0;
    $with_content = 0;

    $old_cache_msg = '';

    if (isset($option['data_provider']) && !empty($option['data_provider'])) {
      $sync_data = call_user_func($option['data_provider']);
      $content = (int) $sync_data['view_cache']['SUCCESS'];
      $no_content = (int) $sync_data['view_cache']['EMPTY'] + (int) $sync_data['view_cache']['FAILED'];
      $total = (int) $sync_data['total'];
      $unsynced = $total - (int) $sync_data['synced'];

      if ( $unsynced < 0 ){
        $unsynced  = 0;

        $old_cache_msg = Utils::showMessage('<span style="font-size: 14px;">The plugin detected cached URLs from old products. Consider clearing them:</span> <button class="button-primary sirv-clean-old-view-cache" style="margin-top: 8px;">Clear old cache</button>', 'warning');
      }


      $option['values'][0]['label'] = $option['values'][0]['label'] . ' (<span class="' . $option['option_name'] . '-' . $option['values'][0]['attrs']['value'] . '">' . $with_content . '</span>)';
      $option['values'][1]['label'] = $option['values'][1]['label'] . ' (<span class="' . $option['option_name'] . '-' . $option['values'][1]['attrs']['value'] . '">' . $without_content . '</span>)';
    }

    $view_file_otion_status = get_option('SIRV_WOO_IS_USE_VIEW_FILE') == 'on' ? true : false;

    $disable_action = $view_file_otion_status ? '' : ' disabled ';


    $html = '
    <tr>
      ' . self::render_option_title($option['label']) . '
      <td>
        <div class="sirv-show-view-cache-messages">'. $old_cache_msg .'</div>
        <div class="sirv-show-view-cache-table">
          <div class="sirv-show-view-cache-row">
            <div class="sirv-show-view-cache-col">Products with content</div>
            <div class="sirv-show-view-cache-col sirv-view-data-content">'. $content . '</div>
            <div class="sirv-show-view-cache-col">
              <a href="#" class="sirv-clear-view-cache" data-type="content">Clear cache</a>
              <span class="sirv-traffic-loading-ico" style="display: none;"></span>
              </div>
          </div>
          <div class="sirv-show-view-cache-row">
            <div class="sirv-show-view-cache-col">Products without content</div>
            <div class="sirv-show-view-cache-col sirv-view-data-no-content">'. $no_content .'</div>
            <div class="sirv-show-view-cache-col">
              <a href="#" class="sirv-clear-view-cache" data-type="no-content">Clear cache</a>
              <span class="sirv-traffic-loading-ico" style="display: none;"></span>
            </div>
          </div>
          <div class="sirv-show-view-cache-row">
            <div class="sirv-show-view-cache-col">Unsynced</div>
            <div class="sirv-show-view-cache-col sirv-view-data-content-unsynced">'. $unsynced .'</div>
          </div>
          <div class="sirv-show-view-cache-row">
            <div class="sirv-show-view-cache-col">Total</div>
            <div class="sirv-show-view-cache-col sirv-view-data-content-total">'. $total . '</div>
          </div>
        </div>
        <div class="sirv-view-cache-option-toolbar">
          <button type="button" class="button button-primary sync-all-view-data-show-dialog"'. $disable_action .'>Sync all products</button>
        </div>
      </td>
    </tr>
    <tr>
    <th></th>
      <td style="color: #666666;">
          The URLs of Sirv content are cached. If you see outdated content, clear this cache, then your page cache.
      </td>
    </tr>';

    return $html;
  }


  protected static function render_pin_gallery($option){
    $values = array(
      array(
        'label' => 'Unpinned',
        'check_data_type' => 'checked',
        'attrs' => array(
          'type' => 'radio',
          'value' => 'no',
        ),
      ),
      array(
        'label' => 'Left',
        'check_data_type' => 'checked',
        'attrs' => array(
          'type' => 'radio',
          'value' => 'left',
        ),
      ),
      array(
        'label' => 'Right',
        'check_data_type' => 'checked',
        'attrs' => array(
          'type' => 'radio',
          'value' => 'right',
        ),
      ),
    );

    $radio_data = array(
      'Pin video(s)' => array(
        'option_name' => 'sirv-woo-pin-video',
        'value' => '',
        'check_value' => 'video',
        'values' => $values,
    ),
      'Pin spin(s)' => array(
        'option_name' => 'sirv-woo-pin-spin',
        'value' => '',
        'check_value' => 'spin',
        'values' => $values,
      ),
      'Pin model(s)' => array(
        'option_name' => 'sirv-woo-pin-model',
        'value' => '',
        'check_value' => 'model',
        'values' => $values,
      ),
      'Pin images by file mask' => array(
        'option_name' => 'sirv-woo-pin-image',
        'value' => '',
        'check_value' => 'image',
        'values' => $values,
      ),
    );

    $option_data = json_decode($option['value'], true);
    //$option['attrs']['value'] = esc_attr($option['value']);

    $input_data = array(
      'attrs' => array(
        'type' => 'text',
        'placeholder' => 'e.g. *-hero.jpg ',
        'value' => $option_data['image_template'],
        'id' => 'sirv-woo-pin-input-template',
      ),
    );

    $radio_html = '<table class="sirv-woo-pin-table-radio"><tbody>';
    foreach ($radio_data as $radio_name => $radio_item) {
      foreach ($radio_item['values'] as $index => $sub_option) {
        //cheking if option checked, readonly, disabled etc for multiple options like radio and added param to attrs.
        $radio_item['values'][$index] = self::check_option($sub_option, $option_data[$radio_item['check_value']]);

        if (!isset($sub_option['attrs']['name'])) {
          $radio_item['values'][$index]['attrs']['name'] = $radio_item['option_name'];
        }
      }

      $radio_html .= "<tr><th>$radio_name</th><td>" . self::render_radio_component($radio_item) . '</td></tr>' . PHP_EOL;
    }
    $radio_html .= '</tbody></table>';

    $above_text = (isset($option['above_text']) && $option['above_text']) ? self::render_above_text($option['above_text']) : '';
    $is_img_input_hide = $option_data['image'] == 'no' ? 'sirv-block-hide ' : '';

    $html = '
      <tr>
        ' . self::render_option_title($option['label']) .'
        <td>
        ' . $above_text . '<br>
        '. $radio_html . '
        <div class="'. $is_img_input_hide .'sirv-woo-pin-input-wrapper">
          '. self::render_text_component($input_data) .'
          '. self::render_below_text('Filenames matching this pattern will be pinned. Use * as a wildcard.') .'
        </div>
        '. self::render_hidden_component($option) .'
        </td>
      </tr>';

    return $html;
  }


  protected static function render_sirv_smv_order_content($option){
    //$above_text = (isset($option['above_text']) && $option['above_text']) ? self::render_above_text($option['above_text']) : '';
    $option_data = json_decode($option['value']);
    //$option['attrs']['value'] = json_encode($option_data, JSON_HEX_APOS | JSON_HEX_QUOT);
    //$option['attrs']['value'] = htmlspecialchars(json_encode($option_data), ENT_QUOTES, 'UTF-8');
    $select_items = array('spin' => 'Spin', 'video' => 'Video', 'zoom' => 'Zoom', 'image' => 'Image', 'model' => 'Model');
    $order_html = '';

    if(!empty($option_data)){
      foreach ($option_data as $item_type) {
        $order_html .= '
          <li class="sirv-smv-order-item sirv-smv-order-item-changeble sirv-no-select-text" data-item-type="'. $item_type .'">
          <div class="sirv-smv-order-item-dots">⠿</div>
          <div class="sirv-smv-order-item-title"><span>'. $select_items[$item_type] . '</span></div>
          <div class="sirv-smv-order-item-delete"><span class="dashicons dashicons-trash"></span></div>
        </li>
        ';
      }
    }

    $html =
    '<tr>
    ' . self::render_option_title($option['label']) . '
      <td>
        <div class="sirv-smv-order-content-wrapper">
          <ul id="sirv-smv-order-items">
            '. $order_html . '
            <li class="sirv-smv-order-item sirv-smv-order-item-add sirv-no-select-text">
              <div class="sirv-smv-order-select">
                <ul class="sirv-smv-order-select-items">
                  <li class="sirv-smv-order-select-items-title">Add new:</li>
                  '. self::render_sirv_smv_order_content_select_options($select_items) . '
                </ul>
              </div>
              <div class="sirv-smv-order-item-title sirv-smv-order-title-add"><span class="dashicons dashicons-plus"></span></div>
            </li>
          </ul>
        </div>
        ' . self::render_hidden_component($option) . '
      </td>
    </tr>';

    return $html;
  }


  protected static function render_sirv_smv_order_content_select_options($items){
    $html = '';

    foreach ($items as $item_type => $item_title) {
      $html .= '<li class="sirv-smv-order-select-item" data-item-type="'. $item_type .'">'. $item_title .'</li>' . PHP_EOL;
    }

    return $html;
  }


  protected static function render_migrate_woo_additional_images($option){
    require_once(SIRV_PLUGIN_SUBDIR_PATH . 'includes/classes/woo.additional.images.migrate.class.php');

    $info = WooAdditionalImagesMigrate::get_wai_data_info();

    if($info->unsynced == 0){
      if( $info->all == 0 ){
        $html = '<p>This plugin was not detected.</p>';
      }else{
        $html = '<p>All images have been migrated. You may wish to uninstall the WooCommerce Additional Variation Images plugin.</p>';
      }
    }else{
      $html =
        '<div class="sirv-wai-container">
            <div class="sirv-wai-button">
              <button class="button-primary sirv-migrate-wai-data" type="button">Migrate</button>
            </div>
            <div class="sirv-progress">
            <div class="sirv-progress__text">
              <div class="sirv-wai-progress-text-persents">'. $info->synced_percent_text . '</div>
              <div class="sirv-progress-text-complited sirv-wai-progress-text-complited"><span>'. $info->synced .' out of '. $info->all .'</span> variations completed</div>
            </div>
            <div class="sirv-progress__bar">
              <div class="sirv-wai-bar-line-complited sirv-complited" style="width: '. $info->synced_percent_text .';"></div>
            </div>
          </div>
        </div>';
    }

    return '
      <tr>
        <th class="sirv-migrate-wai-data-messages no-padding" colspan="2">
        </th>
      </tr>
      <tr>
        <th>'. $option['label'] . '</th>
        <td colspan="2">
          <div class="migrate-woo-additional-images-wrapper">
          <span class="sirv-option-responsive-text">' . $option['description'] . '</span><br><br>
            '. $html .'
          </div>
        </td>
      </tr>';
  }


  protected static function render_sirv_smv_cache_management($option){
    global $wpdb;
    $cache_table = $wpdb->prefix . 'sirv_cache';

    $cache_count = $wpdb->get_var(
      "SELECT COUNT(*) FROM $cache_table
      WHERE cache_key IN ('_sirv_woo_pdp_cache', '_sirv_woo_cat_cache')
      AND cache_status IN ('SUCCESS', 'EMPTY', 'FAILED', 'EXPIRED')"
    );

    $cache_count = $cache_count ? $cache_count : 0;

    $disable_action = sirv_is_enable_option('SIRV_WOO_SMV_CACHE_IS_ENABLE', 'on') ? '' : ' disabled ';

    $html = '
      <tr>
        ' . self::render_option_title($option['label']) . '
        <td>
          <div class="sirv-smv-html-cache-management">
            <span class="sirv-smv-html-cache">Cached galleries: <span class="sirv-smv-html-cache-count">'. $cache_count . '</span></span>
            <div class="sirv-clean-smv-html-cache-container">
              <button type="button" class="button button-primary sirv-clean-smv-html-cache" '. $disable_action .'>Clear cache</button><span class="sirv-traffic-loading-ico" style="display: none;"></span>
            </div>
          </div>
        </td>
      </tr>';

    return $html;
  }
}

?>
