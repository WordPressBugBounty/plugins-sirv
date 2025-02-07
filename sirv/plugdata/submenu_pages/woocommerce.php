<?php

if ($storageInfo && $storageInfo['plan']['name'] == 'Free') {
?>
  <div class="sirv-message error-message">
    <span style="font-size: 15px;font-weight: 800;">Upgrade your plan</span><br>
    <p>Your Free Sirv plan cannot use <a target="_blank" href=" https://sirv.com/help/articles/smart-gallery/">Sirv Media Viewer smart galleries.</a></p>
    <p>
      Upgrade to a paid plan to automatically add image zooms, 360 spins and videos to your product galleries.
    </p>
    <a class="sirv-plan-upgrade-btn sirv-no-blank-link-icon" href="https://my.sirv.com/#/account/billing/plan" target="_blank">Choose a plan</a>
  </div>
<?php }

require_once(dirname(__FILE__) . '/../includes/classes/options/woo.options.class.php');
$options = include(dirname(__FILE__) . '/../data/options/woo.options.data.php');
echo Woo_options::render_options($options);

$view_data = sirv_get_view_cache_info();
?>

<div class="sirv-modal-window-backdrop" id="sirv-sync-view-files" style="display: none;">
  <div class="sirv-modal-window">
    <button type="button" class="sirv-modal-window-close-button sync-all-view-data-hide-dialog-action">✕</button>
    <div class="sirv-modal-window-content">
      <div class="sirv-sync-view-files-messages"></div>
      <div class="sirv-sync-view-files-description">
        <h3>Sync product folders</h3>
        <p>
          Update the WordPress plugin cache with the current contents of your Sirv product folders.
        </p>
      </div>
      <div class="sirv-progress-bar-component">
        <div class="sirv-progress-bar-component-text">
          <div class="sirv-progress-bar-component-text__percents"><?php echo $view_data['progress']; ?>%</div>
          <div class="sirv-progress-bar-component-text__complited">
            <span><?php echo $view_data['synced'] ?> of <?php echo $view_data['total'] ?></span> folders
          </div>
        </div>
        <div class="sirv-progress-bar-component-lines">
          <div class="sirv-progress-bar-component-line__complited" style="width: <?php echo $view_data['progress']; ?>%"></div>
          <div class="sirv-progress-bar-component-line__failed"></div>
        </div>
      </div>
      <div class="sirv-sync-view-files-status" style="display: none;">
        <span class="sirv-traffic-loading-ico"></span><span class="sirv-sync-view-files-show-status">Processing: syncing...</span>
      </div>
      <div class="sirv-sync-view-files-controls">
        <button type="button" class="button-primary sirv-sync-view-files-action sirv-sync-view-files-action__start">Sync Sirv folders</button>
        <button type="button" class="button-primary sync-all-view-data-hide-dialog-action">Cancel</button>
      </div>
    </div>
  </div>
</div>
