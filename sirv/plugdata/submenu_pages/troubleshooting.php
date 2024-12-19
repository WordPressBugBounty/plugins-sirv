<?php

defined('ABSPATH') or die('No script kiddies please!');

require_once(SIRV_PLUGIN_SUBDIR_PATH . 'includes/classes/conflict.plugins.class.php');

$data  = ConflictPlugins::get_issues();

if (empty($data)) {
?>
  <div class="sirv-troubleshooting-container" style="display: flex; width: 100%; height: 100vh; align-content: center; justify-content: center; align-items: center;">
    <h2>No issues found.</h2>
  </div>
<?php } else {
  wp_register_style('sirv_troubleshooting_style', SIRV_PLUGIN_SUBDIR_URL_PATH . 'css/wp-sirv-troubleshooting.css');
  wp_enqueue_style('sirv_troubleshooting_style');

  wp_register_style('sirv_toast_style', SIRV_PLUGIN_SUBDIR_URL_PATH . 'css/vendor/toastr.css');
  wp_enqueue_style('sirv_toast_style');
  wp_enqueue_script('sirv_toast_js', SIRV_PLUGIN_SUBDIR_URL_PATH . 'js/vendor/toastr.min.js', array('jquery'), false);

  wp_register_script('sirv_troubleshooting', SIRV_PLUGIN_SUBDIR_URL_PATH . 'js/wp-sirv-troubleshooting-page.js', array('jquery', 'sirv_toast_js'), false, true);
  wp_localize_script('sirv_troubleshooting', 'sirv_options_data', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'ajaxnonce' => wp_create_nonce('ajax_validation_nonce'),
  ));
  wp_enqueue_script('sirv_troubleshooting');

  ?>
  <div class="sirv-backdrop">
    <div class="sirv-loading"></div>
  </div>
  <div class="sirv-troubleshooting-container">
    <h2>Sirv has detected a possible conflict with the following:</h2>
    <ul>
      <?php
      foreach ($data as $id => $issue) {
        $checked = $issue['status'] == 'ignore' ? 'checked' : '';
      ?>
        <li>
          <p><?php echo sprintf('<strong>%s</strong> %s is activated.<br>%s', $issue['name'], $issue['type'], $issue['desc']); ?></p>
          <label><input type="checkbox" name="sirv_troubleshooting_ignore_issue" id="<?php echo $id ?>" <?php echo  $checked; ?>>Ignore</label>
        </li>
      <?php } ?>
    </ul>
    <button class="button-primary sirv-troubleshooting-save-issues-status" disabled>Save your choose</button>
    <p>If you have any questions or need further help with the Sirv plugin, please <a href="https://sirv.com/help/support/#support">contact our support team</a>.</p>
  </div>
<?php } ?>
