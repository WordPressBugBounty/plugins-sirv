<?php

defined('ABSPATH') or die('No script kiddies please!');

class ConflictPlugins{

  protected static  $active_issues;
  protected static $conflicted_plugs_data;
  protected static $conflicted_themes_data;

  protected static function init(){
    if ( !function_exists('is_plugin_active') ) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    self::$active_issues = array();
    self::$conflicted_plugs_data = self::get_conflicted_plugs_data();
    self::$conflicted_themes_data = self::get_conflicted_themes_data();
  }


  public static function check(){
    self::init();

    $issues_status = (array) json_decode(stripslashes(get_option('SIRV_TROUBLESHOOTING_ISSUES_STATUS')));

    foreach (self::$conflicted_plugs_data as $plugin_path => $plugin_data) {
      if ( is_plugin_active($plugin_path) ) {
        self::$active_issues[$plugin_path] = $plugin_data;

        $issue_status = isset($issues_status[$plugin_path]) ? $issues_status[$plugin_path] : "active";
        self::$active_issues[$plugin_path]['status'] = $issue_status;
      }
    }

    foreach (self::$conflicted_themes_data as $theme_name => $theme_data) {
      if( self::is_theme_active($theme_name) ){
        self::$active_issues[$theme_name] = $theme_data;

      $issue_status = isset($issues_status[$theme_name]) ? $issues_status[$theme_name] : "active";
      self::$active_issues[$theme_name]['status'] = $issue_status;
      }
    }
  }


  public static function get_issues_count(){
    self::check();

    $filtered_issues = array_filter(self::$active_issues, function($issue){
      return $issue['status'] == 'active';
    });
    return count($filtered_issues);
  }


  public static function get_issues(){
    self::check();

    return self::$active_issues;
  }


  public static function show_conflicts_notice(){
    self::check();

    if( !empty(self::$active_issues) ){
      self::show_notice();
    }
  }


  protected static function is_theme_active($theme_name){

    $current_theme = wp_get_theme();
    return ($current_theme->name == $theme_name || $current_theme->parent_theme == $theme_name);
  }


  protected static function show_notice(){
    $notice_id = 'sirv-conflict-plugins';
    $notice = "<h1>Plugin conflict</h1><p>Sirv has detected a possible conflict with the following:</p><ul>";

    foreach (self::$active_issues as $issue_data){
      $notice .= sprintf('<li><p><strong>%s %s</strong></p>%s</li>', $issue_data['name'], $issue_data['type'], $issue_data['desc']);
    }
    $notice .= "</ul>";
    $notice .= '<p>If you have any questions or need further help with the Sirv plugin, please <a target="_blank" href="https://sirv.com/help/support/#support">contact our support team</a>.</p>';

    $notice .= '<p><button class="button-primary sirv-plugin-issues-noticed" data-sirv-dismiss-type="noticed" data-sirv-notice-id="sirv-conflict-plugins">Don\'t show this again</button></p>';

    echo sirv_get_wp_notice($notice, $notice_id, 'warning', true);
  }



  protected static function get_conflicted_plugs_data(){
    return array(
      "smart-image-resize/plugpix-smart-image-resize.php" => array(
        "name" => "Smart Image Resize for WooCommerce",
        "title" => "",
        "type" => "plugin",
        "desc" => 'To fix this, go to <b>Plugins > Installed Plugins</b>, locate Smart Image Resize for WooCommerce, and click <b>Deactivate</b>.',
      ),
      "imagify/imagify.php" => array(
        "name" => "Imagify",
        "title" => "",
        "type" => "plugin",
        "desc" => 'To fix this, go to <b>Plugins > Installed Plugins</b>, locate <i>Imagify</i>, and click <b>Deactivate</b>.',
      ),
      "wprocket/wprocket.php" => array(
        "name" => "WP Rocket",
        "title" => "",
        "type" => "plugin",
        "desc" => 'To resolve this issue:
        <ol>
          <li>Go to your <b>WP Rocket settings</b> and navigate to the <b>File Optimization</b> section.</li>
          <li>If <b>"Load JavaScript deferred"</b> and <b>"Delay JavaScript execution"</b> are enabled, <br>add the following URL to the <b>"Excluded JavaScript Files"</b> field for both options:<br>
          <a target="_blank" href="https://scripts.sirv.com/sirvjs/v3/sirv.js">https://scripts.sirv.com/sirvjs/v3/sirv.js</a></li>
          <li>Save the changes.</li>
          <li>Refresh your browser and check if Sirv is working as expected.</li>
        </ol>
          Once the changes are made, you can dismiss this warning.',
      ),
      "cloudflare/cloudflare.php" => array(
        "name" => "Cloudflare / Rocket Loader",
        "title" => "",
        "type" => "plugin",
        "desc" => 'To resolve this issue:
        <ol>
          <li>Log in to your <b>Cloudflare dashboard</b>.</li>
          <li>Navigate to <b>Speed > Optimization</b> and go to the <b>Content Optimization</b> tab.</li>
          <li>Scroll down to find <b>Rocket Loader</b> and disable it.</li>
          <li>Refresh your browser to ensure Sirv is working as expected.</li>
        </ol>
          If Rocket Loader is already disabled, you can dismiss this warning.',
      ),
      "tenweb-speed-optimizer/tenweb_speed_optimizer.php" => array(
        "name" => "10Web Booster (I-Tul Page Booster)",
        "title" => "",
        "type" => "plugin",
        "desc" => 'The page booster plugin might have settings the conflict with your Sirv plugin settings. If you have a problem, please uninstall the 10Web (I-Tul) page booster plugin and <a target="_blank" href="https://sirv.com/help/support/#support">contact our support team</a> to discuss the issue.',
      ),
    );
  }


  protected static function get_conflicted_themes_data(){
    return array(
      "Woostify" => array(
        "name" => "Woostify",
        "title" => "",
        "type" => "theme",
        "desc" => 'To resolve this issue:
          <ol>
          <li>In your WordPress dashboard, go to <b>Appearance > Customize</b>.</li>
          <li>Navigate to <b>WooCommerce > Product Single > Product Images</b>.</li>
          <li>Under Gallery Layout settings, disable <b>Gallery Lightbox Effect</b>.</li>
          <li>Change Gallery Style to <b>Woocommerce Default</b>.</li>
          <li>Click <b>Publish</b> to save the changes.</li>
          <li>Refresh your browser and check if Sirv is working as expected.</li>
          </ol>
          Once the changes are made, you can dismiss this warning.',
      ),
    );
  }


}

?>
