<?php
defined('ABSPATH') or die('No script kiddies please!');

include_once "html.form.components.class.php";

class Options_components extends HTML_form_components{

  protected static function render_radio_option($option){
    $below_text = isset($option['below_text']) ? $option['below_text'] : '';
    $id_selector = isset($option['id_selector']) ? 'id="'. $option['id_selector'] .'"' : '';
    $html = '
      <tr '. $id_selector .'>
        ' . self::render_option_title($option['label']) . '
        <td>
        ' . self::render_radio_component($option) . '
        ' . self::render_below_text($below_text) . '
        </td>
      ' . self::render_option_status($option) . '
      ' . PHP_EOL . self::render_tooltip($option) . '
      </tr>';

    return $html;
  }


  protected static function render_checkbox_group_option($option){
    $below_text = isset($option['below_text']) ? $option['below_text'] : '';
    $id_selector = isset($option['id_selector']) ? 'id="' . $option['id_selector'] . '"' : '';
    $html = '
      <tr ' . $id_selector . '>
        ' . self::render_option_title($option['label']) . '
        <td>
        ' . self::render_checkbox_group_component($option) . '
        ' . self::render_below_text($below_text) . '
        '. self::render_hidden_component($option) .'
        </td>
      ' . PHP_EOL . self::render_tooltip($option) . '
      </tr>';

    return $html;
  }


  protected static function render_text_option($option){
    $above_text = isset($option['above_text']) ? $option['above_text'] : '';
    $below_text = isset($option['below_text']) ? $option['below_text'] : '';
    $id_selector = isset($option['id_selector']) ? 'id="' . $option['id_selector'] . '"' : '';
    $html = '
      <tr ' . $id_selector . '>
        ' . self::render_option_title($option['label']) . '
        <td>
        '. self::render_above_text($above_text) .'
        ' . self::render_text_component($option) . '
        '. self::render_below_text($below_text) .'
        </td>
      ' . PHP_EOL . self::render_tooltip($option) . '
      </tr>';

    return $html;
  }


  protected static function render_select_option($option){
    $below_text = isset($option['below_text']) ? $option['below_text'] : '';
    $id_selector = isset($option['id_selector']) ? 'id="' . $option['id_selector'] . '"' : '';
    $is_muted = false;
    $expired_at_timestamp = 0;

    if ( isset($option['endpoint_name']) ){
      $is_muted = sirv_is_muted($option['endpoint_name']);
      $expired_at_timestamp = $is_muted ? sirv_get_mute_expired_at($option['endpoint_name']) : 0;
    }

    $html = '
      <tr ' . $id_selector . '>
        ' . self::render_option_title($option['label']) . '
        <td>
        '. self::render_message($option) . '
        '.  self::render_mute_message($is_muted, $expired_at_timestamp ) . '
        '. self::render_select_component($option, $is_muted) . '
        ' . self::render_below_text($below_text) . '
        </td>
        ' . PHP_EOL . self::render_tooltip($option) . '
    </tr>';

    return $html;
  }


  protected static function render_textarea_option($option){
    $above_text = (isset($option['above_text']) && $option['above_text']) ? self::render_above_text($option['above_text']) : '';
    $id_selector = isset($option['id_selector']) ? 'id="' . $option['id_selector'] . '"' : '';
    $html = '
      <tr ' . $id_selector . '>
        ' . self::render_option_title($option['label']) . '
        <td>
          ' . $above_text . '
          '. self::render_textarea_component($option) .'
        </td>
        ' . PHP_EOL . self::render_tooltip($option) . '
    </tr>';

    return $html;
  }


  protected static function render_editable_option($option)
  {
    $above_text = (isset($option['above_text']) && $option['above_text']) ? self::render_above_text($option['above_text']) : '';
    $below_text = (isset($option['below_text']) && $option['below_text']) ? self::render_below_text($option['below_text']) : '';
    $event_id = isset($option['event_id']) ? ' data-event-id="'. $option['event_id'] .'" ' : '';

    $option['attrs']['data-restore-value'] = $option['value'];

    $html = '
      <tr>
        ' . self::render_option_title($option['label']) . '
        <td colspan="2" style="padding-top:0;">
          <div class="sirv-editable-option-block">
            <div class="sirv-editable-option_above-text">' . $above_text . '</div>
            <div class="sirv-editable-option" id="'. $option['action_id'] . '">
              <div class="sirv-editable-option_full-path">
                <div class="sirv-editable-option_domain sirv--grey sirv-ellipsis-text" title="' . htmlspecialchars($option['const_text']) . '">
                  ' . htmlspecialchars($option['const_text']) . '/
                </div>
                <div class="sirv-editable-option_relative-path">
                  <span class="sirv-editable-option-text sirv-ellipsis-text" title="' . htmlspecialchars($option['value']) . '">' . htmlspecialchars($option['value']) . '</span>
                  ' . self::render_text_component($option) . '
                </div>
              </div>
              <div class="sirv-editable-option-actions">
                <button type="button" class="sirv-editable-option_edit" data-type="render" data-id="'. $option['action_id'] .'"'. $event_id .'>Change</button>
              </div>
            </div>
            <div class="sirv-editable-option_below-text">' . $below_text . '</div>
          </div>
        </td>
        ' . PHP_EOL . self::render_tooltip($option) . '
      </tr>
    ';

    return $html;
  }


  protected static function render_messages_block($selector){
    return '<th class="' . $selector . ' no-padding" colspan="2"></th>';
  }


  /* protected static function get_dependence($option){
    $dep_html = array('hide' => '', 'disable' => '');
    if (
      !isset($option['dependence']) || empty($option['dependence'])
    ) return $dep_html;

    $dep_value = self::$options[$option['dependence']['name']]['value'];

    if ($dep_value == $option['dependence']['value']) {
      $dep_type = $option['dependence']['type'];
      switch ($dep_type) {
        case 'disable':
          $dep_html['disable'] = 'disabled';
          break;
        case 'hide':
          $dep_html['hide'] = 'style="display: none;"';
          break;

        default:
          # code...
          break;
      }
    }

    return $dep_html;
  } */

}

?>
