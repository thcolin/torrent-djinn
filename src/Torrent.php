<?php

namespace thcolin\TorrentDjinn;

use thcolin\SceneReleaseParser\Release;

class Torrent{

  protected $release = null;

  public function __construct($tracker){
    $this->setTracker($tracker);
  }

  public function getTracker(){
    return $this->tracker;
  }

  public function setTracker($tracker){
    $this->tracker = $tracker;
  }

  public function getUUID(){
    return $this->uuid;
  }

  public function setUUID($uuid){
    $this->uuid = $uuid;
  }

  public function getName($cleanest = false){
    if($cleanest){
      return ($this->getRelease() ? $this->getRelease()->getRelease(Release::GENERATED_RELEASE):$this->getName());
    } else{
      return $this->name;
    }
  }

  public function setName($name){
    $this->name = $name;
  }

  public function getRelease(){
    return $this->release;
  }

  public function setRelease(Release $release){
    $this->release = $release;
  }

  public function getSeeders(){
    return $this->seeders;
  }

  public function setSeeders($seeders){
    $this->seeders = intval($seeders);
  }

  public function getLeechers(){
    return $this->leechers;
  }

  public function setLeechers($leechers){
    $this->leechers = intval($leechers);
  }

  public function getSize($parsed = true){
    if($parsed){
      $units = ['b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb', 'Eb', 'Zb', 'Yb'];
      $power = $this->size > 0 ? floor(log($this->size, 1000)):0;
      return number_format($this->size / pow(1000, $power), 2, '.', ',').' '.$units[$power];
    } else{
      return ($this->size > 0 ? :1);
    }
  }

  public function setSize($size){
    $this->size = floatval($size);
  }

  public function getRelevance(){
    return $this->relevance;
  }

  public function setRelevance($relevance){
    $this->relevance = floatval($relevance);
  }

}

?>
