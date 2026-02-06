<?php

defined('ABSPATH') or die('No script kiddies please!');

require_once(dirname(__FILE__) . '/includes/classes/options/options.helper.class.php');

$error = '';
$base_options = ['SIRV_FOLDER', 'SIRV_CDN_URL', 'SIRV_ENABLE_CDN', 'SIRV_SHORTCODES_PROFILES', 'SIRV_CDN_PROFILES', 'SIRV_USE_SIRV_RESPONSIVE', 'SIRV_CROP_SIZES', 'SIRV_JS', 'SIRV_JS_MODULES', 'SIRV_CUSTOM_CSS', 'SIRV_RESPONSIVE_PLACEHOLDER, SIRV_PARSE_STATIC_IMAGES', 'SIRV_PARSE_VIDEOS', 'SIRV_CSS_BACKGROUND_IMAGES', 'SIRV_EXCLUDE_FILES', 'SIRV_EXCLUDE_RESPONSIVE_FILES', 'SIRV_EXCLUDE_PAGES', 'SIRV_DELETE_FILE_ON_SIRV', 'SIRV_SYNC_ON_UPLOAD',  'SIRV_PREVENT_CREATE_WP_THUMBS', 'SIRV_PREVENTED_SIZES', 'SIRV_HTTP_AUTH_CHECK', 'SIRV_HTTP_AUTH_USER', 'SIRV_HTTP_AUTH_PASS', 'SIRV_CUSTOM_SMV_SH_OPTIONS', 'SIRV_WOO_SHOW_ADD_MEDIA_BUTTON'];
OptionsHelper::prepareOptionsData();
$options_names = array_merge($base_options, OptionsHelper::get_options_names_list());

function isWoocommerce()
{
  return is_plugin_active('woocommerce/woocommerce.php');
}


function sirv_getStatus()
{
  $status = get_option('SIRV_ENABLE_CDN');

  $class = $status == '1' ? 'sirv-status--enabled' : 'sirv-status--disabled';

  return $class;
}


function sirv_get_cache_count($isGarbage, $cacheInfo)
{
  $synced = $cacheInfo['total_count'];
  if ($isGarbage) {
    if ( $cacheInfo['SYNCED']['count'] - $cacheInfo['garbage_count'] > $cacheInfo['total_count'] ) {
      $synced = $cacheInfo['total_count'];
    } else {
      $synced = $cacheInfo['SYNCED']['count'] - $cacheInfo['garbage_count'];
    }
  }

  return $synced;
}


function sirv_get_sync_button_text($isAllSynced, $cacheInfo)
{
  $sync_button_text = 'Sync images';

  if ($isAllSynced) {
    if ( $cacheInfo['FAILED']['count'] == 0 && $cacheInfo['PROCESSING']['count'] == 0 ) {
      $sync_button_text = '100% synced';
    } else {
      $sync_button_text = 'Synced';
    }
  }

  return $sync_button_text;
}


//mute check
$sirvAPIClient = sirv_getAPIClient();
$sirvStatus = $sirvAPIClient->preOperationCheck();

if ($sirvStatus) {
  $isWoocommerce = isWoocommerce();

  $domains = array();
  $sirvCDNurl = get_option('SIRV_CDN_URL');

  $accountInfo = $sirvAPIClient->getAccountInfo();

  $domains = sirv_get_domains($accountInfo);

  $accountInfoEndpoint = 'v2/account';
  $is_accountInfo_muted = sirv_is_muted($accountInfoEndpoint);

  if ( ! $is_accountInfo_muted ) {
    update_option('SIRV_CUSTOM_DOMAINS', json_encode(array(
      "domains" => array_values($domains),
      "expired_at" => time() + 60 * 60 * 24,
    )));
  }


  $cacheInfo = sirv_getCacheInfo();
  $profiles = sirv_getProfilesList();
  $storageInfo = sirv_getStorageInfo();


  $isOverCache = $cacheInfo['SYNCED']['count'] >  $cacheInfo['total_count'] ? true : false;
  $isSynced = $cacheInfo['SYNCED']['count'] > 0 ? true : false;
  $isFailed = $cacheInfo['FAILED']['count'] > 0 ? true : false;
  $isGarbage = $cacheInfo['garbage_count'] > 0 ? true : false;

  if ($isOverCache) $cacheInfo['SYNCED']['count'] = sirv_get_cache_count($isGarbage, $cacheInfo);


  $isAllSynced = ($cacheInfo['SYNCED']['count'] + $cacheInfo['FAILED']['count'] + $cacheInfo['PROCESSING']['count']) == $cacheInfo['total_count'];
  $is_sync_button_disabled = $isAllSynced ? 'disabled' : '';
  $sync_button_text = sirv_get_sync_button_text($isAllSynced, $cacheInfo);
  $is_show_resync_block = $cacheInfo['SYNCED']['count'] > 0 || $cacheInfo['FAILED']['count'] > 0 ? '' : 'display: none';
  $is_show_failed_block = $cacheInfo['FAILED']['count'] > 0 ? '' : 'display: none';
} else {
  wp_safe_redirect(add_query_arg(array('page' => SIRV_PLUGIN_RELATIVE_SUBDIR_PATH . 'submenu_pages/account.php'), admin_url('admin.php')));
}
?>

<style type="text/css">
  a[href*="page=<?php echo SIRV_PLUGIN_RELATIVE_SUBDIR_PATH ?>options.php"] img {
    padding-top: 7px !important;
  }
</style>

<form action="options.php" method="post" id="sirv-save-options">
  <?php
  wp_nonce_field('sirv-settings-group-options');
  wp_nonce_field('options-options');

  $active_tab = (isset($_POST['active_tab'])) ? $_POST['active_tab'] : '#sirv-settings';
  ?>
  <div class="sirv-wrapped-nav">
    <div class="sirv-options-title-wrap">
      <div class="sirv-options-title">
        <h1 class="sirv-options-title-h1">Welcome to Sirv</h1>
      </div>
      <div class="sirv-options-logo">
        <img src="<?php echo plugin_dir_url(__FILE__) . "assets/logo.svg" ?>" alt="">
        <div class="sirv-options-version"><span>v<?php echo SIRV_PLUGIN_VERSION; ?></span></div>
      </div>
    </div>
    <nav class="nav-tab-wrapper">
      <?php if ($sirvStatus) { ?>
        <a class="nav-tab nav-tab-sirv-settings <?php echo ($active_tab == '#sirv-settings') ? 'nav-tab-active' : '' ?>" href="#sirv-settings" data-link="settings"><span class="dashicons dashicons-admin-generic"></span><span class="sirv-tab-txt">Settings</span></a>
        <?php if ($isWoocommerce) { ?>
          <a class="nav-tab nav-tab-sirv-woo <?php echo ($active_tab == '#sirv-woo') ? 'nav-tab-active' : '' ?>" href="#sirv-woo" data-link="woo"><span class="dashicons dashicons-cart"></span><span class="sirv-tab-txt">WooCommerce</span></a>
        <?php } ?>
        <a class="nav-tab nav-tab-sirv-cache <?php echo ($active_tab == '#sirv-cache') ? 'nav-tab-active' : '' ?>" href="#sirv-cache" data-link="cache"><span class="dashicons dashicons-update"></span><span class="sirv-tab-txt">Synchronization</span></a>
      <?php } ?>
    </nav>
  </div>
  <?php if ($sirvStatus) { ?>
    <div class="sirv-tab-content sirv-tab-content-active" id="sirv-settings">
      <?php include(dirname(__FILE__) . '/submenu_pages/settings.php'); ?>
    </div>

    <?php if ($isWoocommerce) { ?>
      <div class="sirv-tab-content" id="sirv-woo">
        <?php include(dirname(__FILE__) . '/submenu_pages/woocommerce.php'); ?>
      </div>
    <?php } ?>

    <div class="sirv-tab-content" id="sirv-cache">
      <?php include(dirname(__FILE__) . '/submenu_pages/sync.php'); ?>
    </div>
  <?php } ?>

  <input type="hidden" name="active_tab" id="active_tab" value="#settings" />
  <input type='hidden' name='option_page' value="options" />
  <input type="hidden" name="action" value="update" />
  <input type="hidden" name="page_options" value="<?php echo implode(', ', $options_names); ?>" />

</form>
