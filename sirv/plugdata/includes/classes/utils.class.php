<?php
  defined('ABSPATH') or die('No script kiddies please!');


  class Utils{

    protected static $headers;
    protected static $user_agent = 'Sirv/Wordpress';

    public static function getFormatedFileSize($bytes, $decimal = 2, $bytesInMM = 1000){
      $sign = ($bytes >= 0) ? '' : '-';
      $bytes = abs($bytes);

      if (is_numeric($bytes)) {
        $position = 0;
        $units = array(" Bytes", " KB", " MB", " GB", " TB");
        while ($bytes >= $bytesInMM && ($bytes / $bytesInMM) >= 1) {
          $bytes /= $bytesInMM;
          $position++;
        }
        return ($bytes == 0) ? '-' : $sign . round($bytes, $decimal) . $units[$position];
      } else {
        return "-";
      }
    }


    public static function startsWith($haystack, $needle){
      //func str_starts_with exists only in php8
      if (!function_exists('str_starts_with')) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
      } else {
        return str_starts_with($haystack, $needle);
      }
    }


    public static function endsWith($haystack, $needle){
      if (!function_exists('str_ends_with')) {
        return $needle !== '' && substr($haystack, -strlen($needle)) === (string)$needle;
      } else {
        return str_ends_with($haystack, $needle);
      }
    }

    public static function isJson($json_str) {
      if(!function_exists('json_validate')){
        json_decode($json_str);
        return (json_last_error() == JSON_ERROR_NONE);
      }else{
        return json_validate($json_str);
      }
    }


    public static function get_file_extensions(){
      return array(
        "image" => array("tif", "tiff", "bmp", "jpg", "jpeg", "gif", "png", "apng", "svg", "webp", "heif", "avif", "ico"),
        "video" => array("mp4", "mpg", "mpeg", "mov", "qt", "webm", "avi", "mp2", "mpe", "mpv", "ogg", "m4p", "m4v", "wmv"),
        "model" => array("glb", "gltf"),
        "spin" => array("spin"),
        "audio" => array("mp3", "wav", "ogg", "flac", "aac", "wma", "m4a"),
      );
    }


    public static function get_sirv_type_by_ext($ext){
      $extensions_by_type = self::get_file_extensions();
      foreach ($extensions_by_type as $type => $extensions) {
        if(in_array($ext, $extensions)){
          return $type;
        }
      }

      return false;
    }


    public static function clean_uri_params($url){
      if ( empty($url) ) return $url;

      return preg_replace('/\?.*/i', '', $url);
    }


    public static function get_mime_data($filepath){
      $mine_data = false;

      if ( function_exists('mime_content_type') ){
        $mime_str = mime_content_type($filepath);

        if( $mime_str ){
          $mime_arr = explode('/', $mime_str);
          $mine_data = array(
            'type' => $mime_arr[0],
            'subtype' => $mime_arr[1],
          );
        }
      }

      return $mine_data;
    }


    public static function get_mime_type($filepath){
      return self::get_mime_data($filepath)['type'];
    }


    public static function get_mime_subtype($filepath){
      return self::get_mime_data($filepath)['subtype'];
    }


    public static function get_file_extension($filepath){
      return pathinfo($filepath, PATHINFO_EXTENSION);
    }


    public static function parse_html_tag_attrs($html_data){
    $tag_metadata = array();

    if ( ! empty($html_data) ) {
      preg_match_all('/\s(\w*)=\"([^"]*)\"/ims', $html_data, $matches_tag_attrs, PREG_SET_ORDER);
      $tag_metadata = self::convert_matches_to_assoc_array($matches_tag_attrs);
    }

    return $tag_metadata;
  }


    public static function convert_matches_to_assoc_array($matches){
      $assoc_array = array();

      for ($i=0; $i < count($matches); $i++) {
        $assoc_array[$matches[$i][1]] = $matches[$i][2];
      }

      return $assoc_array;
    }


    public static function join_tag_attrs($tag_metadata, $skip_attrs = array()){
      $tag_attrs = array();

      foreach ($tag_metadata as $attr_name => $value) {
        if( in_array($attr_name, $skip_attrs) ) continue;

        $tag_attrs[] = $attr_name . '="' . $value . '"';
      }

      return implode(' ', $tag_attrs);
    }



    public static function showMessage($message, $type="error", $is_show_close_button=false){
      //type: error, info, warning, success
      $allowed_types_to_close = array('info','success');

      $close_button_html = (in_array($type, $allowed_types_to_close) || $is_show_close_button) ? '<div><button class="sirv-push-message-close" type="button">&times;</button></div>' : '';

      $html = '
        <div class="sirv-push-message-container sirv-push-message-' . $type . '">
          <div class="sirv-push-message sirv-push-message-' . $type . '-icon">
            ' . $message . '
          </div>
          ' . $close_button_html . '
        </div>
      ';

      return $html;

    }


    public static function get_minutes($timestamp){
      return round( ((int) $timestamp - time()) / 60 );
    }


    public static function get_site_referer(){
      return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : get_site_url();
    }


    public static function get_current_page_url(){
      //htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : get_site_url();
      $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

      return "https://$host$request_uri";
    }


    public static function get_sirv_item_info($sirv_url){
      $context = stream_context_create(
        array(
          'http' => array(
            'method' => "GET",
            'header' => "Accept: application/json\r\n" . "User-Agent: Sirv/Wordpress\r\n",
          )
        )
      );

      $sirv_item_metadata = @json_decode(@file_get_contents($sirv_url . '?info', false, $context));

      return empty($sirv_item_metadata) ? false : $sirv_item_metadata;
    }


    public static function build_html_tag($tag_name, $tag_metadata, $skip_attrs = array()){
      $tag_attrs = self::join_tag_attrs($tag_metadata, $skip_attrs);

      return '<' . $tag_name . ' ' . $tag_attrs . '>';
    }


    //this function return very aproximate file size (ofren much less that real size). We don't have ability to get exact file size without downloading file
    public static function get_remote_file_size($url, $item_type = null){
      $referer = self::get_site_referer();
      $current_page_url = self::get_current_page_url();

      $request_headers = array(
        "Accept" => 'Accept: application/json',
        "Referer" => "Referer: $referer",
        "X-SIRV-CURRENT-PAGE-URL" => "X-SIRV-CURRENT-PAGE-URL: $current_page_url",
        "X-SIRV-INITIATOR" => "X-SIRV-INITIATOR: get_remote_file_size",
      );

      if( !is_null($item_type) && $item_type == 'spin' ) {
        $url .= "?image";
      }

      $ch = curl_init();
      curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_NONE,
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $request_headers,
        CURLOPT_USERAGENT => self::$user_agent,
        CURLOPT_HEADER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_ACCEPT_ENCODING => "",
      ));

      $result = curl_exec($ch);
      $filesize = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
      $error = curl_error($ch);

      curl_close($ch);

      return array(
        "filesize" => $filesize > 0 ? $filesize : 0,
        "error" => $error
      );
    }


    public static function get_sirv_item_info_curl($url){

      $response = array(
        'result' => '',
        'headers' => array(),
        'error' => NULL,
      );

      if( empty($url) ){
        $response['error'] = 'Empty sirv url';
        return $response;
      }

      $headers = array();
      $error = NULL;

      $referer = self::get_site_referer();
      $current_page_url = self::get_current_page_url();

      $request_headers = array(
        "Accept" => 'Accept: application/json',
        "Referer" => "Referer: $referer",
        "X-SIRV-CURRENT-PAGE-URL" => "X-SIRV-CURRENT-PAGE-URL: $current_page_url",
        "X-SIRV-INITIATOR" => "X-SIRV-INITIATOR: get_sirv_item_info_curl",
      );

      $request_url = $url . '?info';

      $ch = curl_init();
      curl_setopt_array($ch, array(
        CURLOPT_URL => $request_url,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_NONE,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HTTPHEADER => $request_headers,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_USERAGENT => self::$user_agent,
        CURLOPT_ENCODING => "",
        CURLOPT_ACCEPT_ENCODING => "",
        //CURLOPT_MAXREDIRS => 5,
        //CURLOPT_CONNECTTIMEOUT => 2,
        //CURLOPT_TIMEOUT => 5,
        //CURLOPT_SSL_VERIFYPEER => false,
        //CURLOPT_VERBOSE => true,
        //CURLOPT_STDERR => $fp,
      ));

      $result = curl_exec($ch);
      $headers = curl_getinfo($ch);
      $error = curl_error($ch);

      curl_close($ch);

      if ($error) {
        global $sirv_gbl_sirv_logger;

        $sirv_gbl_sirv_logger->error($url, 'request url')->filename('network_errors.log')->write();
        $sirv_gbl_sirv_logger->error($error, 'error message')->filename('network_errors.log')->write();
        $sirv_gbl_sirv_logger->delimiter()->filename('network_errors.log')->write();
      }

      $response['result'] = $result;
      $response['headers'] = $headers;
      $response['error'] = $error;

      return $response;
    }


    public static function  get_headers_curl($url){
      self::$headers = array();
      $error = NULL;

      $referer = self::get_site_referer();
      $current_page_url = self::get_current_page_url();

      $request_headers = array(
        "Accept" => 'Accept: application/json',
        "Referer" => "Referer: $referer",
        "X-SIRV-CURRENT-PAGE-URL" => "X-SIRV-CURRENT-PAGE-URL: $current_page_url",
        "X-SIRV-INITIATOR" => "X-SIRV-INITIATOR: get_headers_curl",
      );

      $ch = curl_init();
      curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_NONE,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HTTPHEADER => $request_headers,
        CURLOPT_HEADERFUNCTION => [Utils::class, 'header_callback'],
        CURLOPT_NOBODY => 1,
        CURLOPT_CUSTOMREQUEST => 'HEAD',
        CURLOPT_USERAGENT => self::$user_agent,
        CURLOPT_ENCODING => "",
        CURLOPT_ACCEPT_ENCODING => "",
        //CURLOPT_MAXREDIRS => 5,
        //CURLOPT_CONNECTTIMEOUT => 2,
        //CURLOPT_TIMEOUT => 8,
      ));

      $result = curl_exec($ch);
      $error = curl_error($ch);

      curl_close($ch);

    if ($error) {
      global $sirv_gbl_sirv_logger;

      $sirv_gbl_sirv_logger->error($url, 'request url')->filename('network_errors.log')->write();
      $sirv_gbl_sirv_logger->error($error, 'error message')->filename('network_errors.log')->write();
      $sirv_gbl_sirv_logger->delimiter()->filename('network_errors.log')->write();

      self::$headers['error'] = $error;
    }

      return self::$headers;
    }


    protected static function header_callback($ch, $header){
      $len = strlen($header);

      if (self::startsWith($header, 'HTTP')) {
        $header_data = explode(' ', $header, 3);
        self::$headers['HTTP_protocol'] = $header_data[0];
        self::$headers['HTTP_code'] = $header_data[1];
        self::$headers['HTTP_code_text'] = trim($header_data[2]);

        return $len;
      }

      $header = explode(':', $header, 2);
      if (count($header) < 2) return $len;

      list($h_name, $h_value) = $header;
      $h_name = trim($h_name);
      $h_value = trim($h_value);


      if (isset(self::$headers[$h_name])) {
        if (is_array(self::$headers[$h_name])) {
          self::$headers[$h_name][] = $h_value;
        } else {
          self::$headers[$h_name] = array(
            self::$headers[$h_name],
            $h_value,
          );
        }
        return $len;
      }

      self::$headers[$h_name] = $h_value;

      return $len;
    }

  }
?>
