<?php

defined('ABSPATH') or die('No script kiddies please!');

class SessionHelper{

  protected $session_id;
  protected $session_key;


  public function __construct($session_id=NULL, $session_key=NULL){
    if($session_id) $this->session_id = $session_id;
    if($session_key) $this->session_key = $session_key;
  }


  public function show_params(){
    sirv_qdebug($this->session_id, '$session_id');
    sirv_qdebug($this->session_key, '$session_key');
  }


  protected function close_another_session(){
    if( session_id() || session_name() ){
      session_write_close();
    }
  }


  public function init($session_id, $session_key){
    $this->session_id = $session_id;
    $this->session_key = $session_key;
  }


  public function read(){
    $this->close_another_session();

    session_id($this->session_id);
    session_start();

    $data = self::is_exists() ? $_SESSION[$this->session_key] : null;

    session_write_close();

    return $data;
  }


  public function write($data){
    $this->close_another_session();

    session_id($this->session_id);
    session_start();

    $_SESSION[$this->session_key] = $data;

    session_write_close();
  }


  public function delete(){
    $this->close_another_session();

    session_id($this->session_id);
    session_start();

    unset($_SESSION[$this->session_key]);

    session_write_close();
  }


  public function destroy(){
    $this->close_another_session();

    session_id($this->session_id);
    session_start();

    session_destroy();

  }


  public function is_exists(){
    return isset($_SESSION[$this->session_key]);
  }
}


?>
