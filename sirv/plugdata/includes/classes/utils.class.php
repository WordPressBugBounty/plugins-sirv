<?php
  defined('ABSPATH') or die('No script kiddies please!');


  class Utils{

    protected static $headers;

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



    public static function get_sirv_item_info($sirv_url){
      $context = stream_context_create(array('http' => array('method' => "GET")));
      $sirv_item_metadata = @json_decode(@file_get_contents($sirv_url . '?info', false, $context));

      return empty($sirv_item_metadata) ? false : $sirv_item_metadata;
    }


    public static function build_html_tag($tag_name, $tag_metadata, $skip_attrs = array()){
      $tag_attrs = self::join_tag_attrs($tag_metadata, $skip_attrs);

      return '<' . $tag_name . ' ' . $tag_attrs . '>';
    }


    public static function get_head_request($url, $protocol_version = 1){
      $headers = array();
      $error = NULL;

      $site_url = get_site_url();
      $request_headers = array(
        "Referer" => "Referer: $site_url",
      );

      $ch = curl_init();
      curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_HTTP_VERSION => $protocol_version === 1 ? CURL_HTTP_VERSION_1_1 : CURL_HTTP_VERSION_2_0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HTTPHEADER => $request_headers,
        CURLOPT_NOBODY => 1,
        CURLOPT_CUSTOMREQUEST => 'HEAD',
        //CURLOPT_HEADER => 1, //get headers in result
        //CURLINFO_HEADER_OUT => true,
        //CURLOPT_HEADERFUNCTION => [Utils::class, 'header_callback'],
        //CURLOPT_HEADER => 1,
        //CURLOPT_NOBODY => 0,
        //CURLOPT_CONNECTTIMEOUT => 1,
        //CURLOPT_TIMEOUT => 1,
        //CURLOPT_CUSTOMREQUEST => 'HEAD',
        //CURLOPT_ENCODING => "",
        //CURLOPT_MAXREDIRS => 10,
        //CURLOPT_USERAGENT => $userAgent,
        //CURLOPT_POSTFIELDS => $data,
        //CURLOPT_SSL_VERIFYPEER => false,
        //CURLOPT_VERBOSE => true,
        //CURLOPT_STDERR => $fp,
      ));

      $result = curl_exec($ch);
      $headers = curl_getinfo($ch);
      $error = curl_error($ch);

      curl_close($ch);

      if( $error ) $headers['error'] = $error;

      return $headers;
    }

  }
?>
