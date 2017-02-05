<?php

namespace thcolin\TorrentDjinn;

trait Tracker{

  public $class;
  public $browser;

  protected $enable = true;
  protected $credentials;

  private $logged = false;
  private $methods = ['test', 'search', 'download'];

  public function __construct($credentials){
    $this->setCredentials($credentials);

    $explode = explode('\\', get_called_class());
    $this->class = end($explode);

    $this->browser = new Browser();
  }

  public function __call($method, $args){
    if(!in_array($method, $this->methods)){
      return;
    }

    if(!$this->logged){
      $this->login();
      $this->logged = true;
    }

    return call_user_func_array(array($this, $method), $args);
  }

  public function serialize(){
    return [
      'enable' => $this->enable,
      'credentials' => $this->credentials
    ];
  }

  public function isEnable(){
    return !!$this->enable;
  }

  public function setEnable($bool){
    $this->enable = !!$bool;
  }

  public function getCredentials(){
    return $this->credentials;
  }

  public function setCredentials($credentials){
    $this->credentials = $credentials;
  }

  abstract protected function login();

  abstract protected function test();

  abstract protected function search($q);

  abstract protected function download(Torrent $torrent, $tmp);

}

?>
