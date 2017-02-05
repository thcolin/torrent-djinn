<?php

namespace thcolin\TorrentDjinn;

use thcolin\TorrentDjinn\Trackers\ABN;
use thcolin\TorrentDjinn\Trackers\T411;
use thcolin\TorrentDjinn\Exceptions\JSONUnvalidException;
use InvalidArgumentException;

class Config{

  protected $destination;
  protected $trackers;

  public function __construct($destination, $trackers){
    $this->setDestination($destination);
    $this->setTrackers($trackers);
  }

  public function serialize(){
    $array = [];

    $array['destination'] = $this->getDestination();

    foreach($this->getTrackers(true) as $key => $tracker){
      $array['trackers'][$key] = $tracker->serialize();
    }

    return json_encode($array, JSON_PRETTY_PRINT);
  }

  public static function unserialize($file){
    $json = file_get_contents($file);
    $array = json_decode($json, true);

    if(!$array){
      throw new JSONUnvalidException("Error in JSON config file : '".$file."'");
    }

    return new Config($array['destination'], $array['trackers']);
  }

  public function save($path){
    $return = file_put_contents($path, $this->serialize());

    if(!$return){
      throw new InvalidArgumentException("Cannot save the config to : '".$path."'");
    }
  }

  public function getDestination(){
    return $this->destination;
  }

  public function setDestination($destination){
    if(!is_dir($destination)){
      throw new InvalidArgumentException("Cannot found destination folder : '".$destination."'");
    } else if(!is_writable($destination)){
      throw new InvalidArgumentException("Destintation folder isn't writable : '".$destination."'");
    }

    $this->destination = realpath($destination);
  }

  public function getTrackers($all = false){
    $trackers = [];

    foreach($this->trackers as $key => $tracker){
      if($all || $tracker->isEnable()){
        $trackers[$key] = $tracker;
      }
    }

    return $trackers;
  }

  public function setTrackers($trackers){
    foreach($trackers as $tracker => $options){
      $this->addTracker($tracker, $options);
    }
  }

  public function addTracker($tracker, $options){
    $class = 'thcolin\TorrentDjinn\Trackers\\'.$tracker;

    if(!class_exists($class)){
      throw new InvalidArgumentException("Tracker '".$tracker."' isn't supported");
    }

    if(!isset($options['credentials'])){
      throw new InvalidArgumentException("The key 'credentials' for the tracker '".$tracker."' is missing");
    }

    if(!isset($options['enable'])){
      throw new InvalidArgumentException("The key 'enable' for the tracker '".$tracker."' is missing");
    }

    $this->trackers[$tracker] = new $class($options['credentials']);
    $this->trackers[$tracker]->setEnable($options['enable']);
  }

  public function removeTracker($tracker){
    if(isset($this->trackers[$tracker])){
      unset($this->trackers[$tracker]);
    }
  }

}
