<?php

namespace thcolin\TorrentDjinn;

use InvalidArgumentException;
use thcolin\TorrentDjinn\Exceptions\JSONUnvalidException;
use thcolin\TorrentDjinn\Exceptions\LoginException;

class Djinn{

  public $trackers = [];

  public function __construct($path){
    if(!is_file($path)){
      throw new JSONUnvalidException("JSON config file doesn't exist : ".$path);
    }

    $this->path = $path;
    $raw = file_get_contents($this->path);
    $this->config = json_decode($raw, true);

    if(!$this->config){
      throw new JSONUnvalidException("Error in JSON config file (check the config.default.json) : ".$path."");
    }

    if(!is_dir($this->config['destination'])){
      throw new InvalidArgumentException("JSON destintation doesn't exist");
    }

    // config
    $this->setDestination($this->config['destination']);
    $this->setStrict(isset($this->config['strict']) ? $this->config['strict']:true);

    // trackers
    foreach($this->config['trackers'] as $tracker => $options){
      $class = 'thcolin\TorrentDjinn\Trackers\\'.$tracker;

      if(isset($options['enabled']) && !$options['enabled']){
        continue;
      }

      if(!class_exists($class)){
        throw new InvalidArgumentException("JSON tracker '".$tracker."' isn't supported");
      }

      // retry login
      try{
        $this->trackers[$tracker] = new $class($options);
      } catch(LoginException $e){
        $this->trackers[$tracker] = new $class($options);
      }
    }
  }

  public function search($q, $trackers = null, $strict = true, $filters = null, $order = null){
    $collection = new TorrentCollection();

    $trackers = (is_array($trackers) ? $trackers:(is_string($trackers) ? [$trackers]:[]));

    foreach($this->getTrackers() as $key => $tracker){
      if(!$trackers || in_array($key, $trackers)){
        $search = $tracker->search($q);
        foreach($search->torrents as $torrent){
          $collection->torrents[] = $torrent;
        }
      }
    }

    return $collection;
    return $collection->filter($filters ? $filters:$strict, $order);
  }

  public function download(Torrent $torrent){
    $tmp = tempnam('/tmp', time());
    $this->trackers[$torrent->getTracker()]->download($torrent, $tmp);
    rename($tmp, $this->getDestination().'/'.$torrent->getName().'_'.$torrent->getTracker().'.torrent');
  }

  public function save(){
    file_put_contents($this->path, json_encode($this->config, JSON_PRETTY_PRINT));
  }

  public function getDestination(){
    return realpath($this->destination);
  }

  public function setDestination($destination){
    if(!is_dir($destination)){
      throw new InvalidArgumentException();
    }
    $this->destination = $destination;

    foreach($this->trackers as $key => $tracker){
      $tracker->setDestination($destination);
    }
  }

  public function getTrackers(){
    return $this->trackers;
  }

  public function getStrict(){
    return $this->strict;
  }

  public function setStrict($strict){
    $this->strict = $strict;
  }

}

?>
