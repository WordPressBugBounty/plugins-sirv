<?php

defined('ABSPATH') or die('No script kiddies please!');

class ViewFilePathFilters{
  protected $filters_str;
  protected $allowed_symblols = array('_', '-', '.', '~');
  protected $is_chars_executed = false;

    public function __construct($filters_str = ''){
        $this->filters_str = $filters_str;
    }


    public function run_filters($value){
      $filters = $this->parse_filter_str('|', $this->filters_str);

      foreach ($filters as $filter_str) {
        $value = $this->run_filter($filter_str, $value);
      }

      return $value;
    }


    protected function run_filter($filter_str, $value){
      $filter_data = $this->parse_filter_str(':', $filter_str);
      $filter_name = $filter_data[0];
      $filter_args = isset($filter_data[1]) ? array_slice($filter_data, 1) : array();

      if (method_exists($this, $filter_name) && $this->validate($filter_name, $filter_args)) {
        $value = call_user_func_array(array($this, $filter_name), array_merge(array($value), $filter_args));
      }

      return $value;
    }


    protected function parse_filter_str($separator, $filter_str){
      return explode($separator, $filter_str);
    }


    protected function validate($filter_name, $filter_args){
      return call_user_func(array($this, $filter_name . '_validate'), $filter_args);
    }


    protected function calc_char_offset($offset){
      if ( $offset > 0 ) return $offset - 1;
      if ( $offset <= 0 ) return $offset;
    }


    protected function chars($value, $start, $len){

      if ( $this->is_chars_executed ) return $value;

      $this->is_chars_executed = true;

      $start = (int) $start;
      $len = (int) $len;

      $start = $this->calc_char_offset($start);

      $filtered_value = substr($value, $start, $len);

      return $filtered_value ? $filtered_value : $value;
    }


    protected function chars_validate($args){
      $args_len = count($args);

      if ( $args_len != 2 ) return false;

      if ( !is_numeric($args[0]) || !is_numeric($args[1]) ) return false;

      if ( (int) $args[1] < 1 ) return false;

      return true;
    }


    protected function first($value, $len){
      if ($this->is_chars_executed) return $value;

      return $this->chars($value, 0, $len);
    }


    protected function first_validate($args){
      $args_len = count($args);

      if ( $args_len != 1 ) return false;

      if ( !is_numeric($args[0]) ) return false;

      if ( (int) $args[0] < 1 ) return false;

      return true;
    }


    protected function last($value, $len){
      if ($this->is_chars_executed) return $value;

      $this->is_chars_executed = true;

      return substr($value, -$len);
    }


    protected function last_validate($args){
      $args_len = count($args);

      if ($args_len != 1) return false;

      if (!is_numeric($args[0])) return false;

      if ((int) $args[0] < 1) return false;

      return true;
    }


    protected function after($value, $char){
      if ( !in_array($char, $this->allowed_symblols) ) return $value;

      return $value . $char;
    }


    protected function after_validate($args){
      $args_len = count($args);

      if ($args_len != 1) return false;

      if (strlen($args[0]) != 1) return false;

      return true;
    }


    protected function before($value, $char){
      if ( !in_array($char, $this->allowed_symblols) ) return $value;

      return $char . $value;
    }


    protected function before_validate($args){
      $args_len = count($args);

      if ($args_len != 1) return false;

      if (strlen($args[0]) != 1) return false;

      return true;
    }

}

?>
