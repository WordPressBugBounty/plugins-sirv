<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

function sirv_update_smv_cache_html($args){
  $cache = $args['cache'];
  $ttl = $args['ttl'];

  $woo = new Woo($cache['post_id']);

  $html = $woo->get_woo_smv_html_by_key($cache['post_id'], $cache['cache_key']);

  if ($html) {
    $cache['cache_value'] = $html;
    $cache['cache_status'] = 'SUCCESS';
  } else {
    $cache['cache_value'] = '';
    $cache['cache_status'] = 'EMPTY';
  }

  $cache['created_at'] = date("Y-m-d H:i:s");
  $cache['expired_at'] = is_null($ttl) ? null : date("Y-m-d H:i:s", time() + $ttl);



  $result = $woo->save_data_to_cache($cache);

  if ( $result['error'] ) {
    global $sirv_gbl_sirv_logger;

    $logname = 'caching_errors.log';

    $sirv_gbl_sirv_logger->error($cache['cache_key'], 'Job: sirv_update_smv_cache. Cache key')->filename($logname)->write();
    $sirv_gbl_sirv_logger->error($cache['post_id'], 'Product id')->filename($logname)->write();
    $sirv_gbl_sirv_logger->error($result['error'], 'error message')->filename($logname)->write();
    $sirv_gbl_sirv_logger->delimiter()->filename($logname)->write();
  }
}


function sirv_update_view_file_cache($args){
  $cache = $args['cache'];
  $view_file_data = json_decode($cache['cache_value']);
  $ttl = $args['ttl'];
  $view_file_path = $args['view_file_path'];
  $mtime = isset($view_file_data->mtime) ? $view_file_data->mtime : 0;

  $woo = new Woo($cache['post_id']);

  $check_data = $woo->check_view_file_changes($view_file_path, $mtime);

  if ( $check_data['is_view_file_changed'] || in_array($cache['cache_status'], array('EXPIRED', 'DELETED')) ) {
    $view_file = $woo->load_view_file_data($cache['post_id'], $view_file_path);
    $view_file["data"]->mtime = $check_data['mtime'];

    $cache['cache_value'] = json_encode($view_file["data"]);
    $cache['cache_status'] = $view_file["status"];
  }

  $cache['created_at'] = date("Y-m-d H:i:s");
  $cache['expired_at'] = is_null($ttl) ? null : date("Y-m-d H:i:s", time() + $ttl);

  $result = $woo->save_data_to_cache($cache);

  if ($result['error']) {
    global $sirv_gbl_sirv_logger;

    $logname = 'caching_errors.log';

    $sirv_gbl_sirv_logger->error($cache['cache_key'], 'Job: sirv_update_view_file_cache. Cache key')->filename($logname)->write();
    $sirv_gbl_sirv_logger->error($cache['post_id'], 'Product id')->filename($logname)->write();
    $sirv_gbl_sirv_logger->error($result['error'], 'error message')->filename($logname)->write();
    $sirv_gbl_sirv_logger->delimiter()->filename($logname)->write();
  }
}


function sirv_sync_images($fetch_queue_data){
  //code here
}

?>
