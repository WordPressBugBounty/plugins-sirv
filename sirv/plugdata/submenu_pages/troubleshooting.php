<?php

defined('ABSPATH') or die('No script kiddies please!');

require_once(SIRV_PLUGIN_SUBDIR_PATH . 'includes/classes/conflict.plugins.class.php');

$data  = ConflictPlugins::get_issues();

if (empty($data)) {
?>
  <div class="sirv-troubleshooting-container" style="display: flex; width: 100%; height: 100vh; align-content: center; justify-content: center; align-items: center;">
    <h2>No issues found.</h2>
  </div>
<?php } else { ?>
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
