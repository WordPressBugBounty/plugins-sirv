<?php

defined('ABSPATH') or die('No script kiddies please!');

class Exclude{
  /*
  * $currentPath - string or array
  */
  public static function excludeSirvContent($currentPath, $excludeType){
    //$excludeType SIRV_EXCLUDE_FILES SIRV_EXCLUDE_PAGES, SIRV_EXCLUDE_RESPONSIVE_FILES

    $excludeInput = get_option($excludeType);

    if( !isset($excludeInput) || $excludeInput == '' ) return false;

    if ( $excludeType == 'SIRV_EXCLUDE_FILES' ) {
      $currentPath = self::clearCurrentPath($currentPath);
    } else if ( $excludeType == 'SIRV_EXCLUDE_RESPONSIVE_FILES' ) {
        $currentPath['src'] = self::clearCurrentPath($currentPath['src']);
    }

    $excludePaths = self::parseExcludePaths($excludeInput);

    return self::loop($excludePaths, $currentPath);

  }


  public static function parseExcludePaths($excludePaths){
    return preg_split('/\r\n|[\r\n]/', trim($excludePaths));
  }


  protected static function clearCurrentPath($currentPath){
    return preg_replace('/-[0-9]{1,}(?:x|&#215;)[0-9]{1,}/is', '', $currentPath);
  }


  protected static function convertExcludeStrToRegEx($excludeStr){
    return str_replace('\*', '.*', preg_quote($excludeStr, '/'));
  }

  protected static function loop($excludePaths, $currentPath){
    for ($i=0; $i < count($excludePaths); $i++) {
      if ( ! is_array($currentPath) ) {
        if ( self::singleCheck($excludePaths[$i], $currentPath) ) return true;
      } else {
        if( self::multipleCheck($excludePaths[$i], $currentPath) ) return true;
      }
    }

    return false;
  }


  protected static function singleCheck($excludePath, $currentPath){
    $expression = self::convertExcludeStrToRegEx($excludePath);

    if ( $excludePath == '/' ) return self::is_homepage($currentPath);

    return self::check($currentPath, $expression);
  }


  protected static function multipleCheck($excludePath, $currentPath){
    foreach ($currentPath as $attrName => $attrVal) {
      if ( $attrName == 'src' ) {
        $result = self::singleCheck($excludePath, $attrVal);
      } else if ( $attrName == 'class' ) {
        $explodedClasses = explode(" ", $attrVal);
        $result = in_array($excludePath, $explodedClasses);
      } else {
        $result = $excludePath == $attrVal;
      }

      if( $result ) return true;
    }

    return false;
  }


  protected static function check($path, $expression){
    return preg_match('/' . $expression . '/', $path) != false;
  }

  public static function is_homepage($currentPath){
    if ($currentPath == '/' || is_home() || is_front_page() ) return true;

    $home_url = get_home_url();

    if ( $home_url === "" || $home_url === false ) return false;

    $home_url .= Utils::endsWith($home_url, '/') ? '' : '/';

    $currentPathInfo = parse_url($currentPath);
    $home_url_info = parse_url($home_url);

    $checkCurrentPath = isset($currentPathInfo['query']) ? $currentPathInfo['path'] . '?' . $currentPathInfo['query'] : $currentPathInfo['path'];


    if ( $checkCurrentPath == $home_url_info['path'] ) return true;

    return false;
  }

}
