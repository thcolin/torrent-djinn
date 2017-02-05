<?php

namespace thcolin\TorrentDjinn;

use InvalidArgumentException;
use thcolin\TorrentDjinn\Config;
use thcolin\TorrentDjinn\Exceptions\LoginException;

class Djinn{

  protected $config;

  public function __construct(Config $config){
    $this->setConfig($config);
  }

  public static function invoke($destination, $trackers){
    $config = new Config($destination, $trackers);
    return new Djinn($config);
  }

  public function getConfig(){
    return $this->config;
  }

  public function setConfig(Config $config){
    $this->config = $config;
  }

  public function search($q, $trackers = null){
    $collection = new TorrentCollection();

    $trackers = (is_array($trackers) ? $trackers:(is_string($trackers) ? [$trackers]:[]));

    foreach($this->config->getTrackers() as $key => $tracker){
      if(in_array($key, $trackers)){
        $search = $tracker->search($q);
        foreach($search->torrents as $torrent){
          $relevance = $this->relevance($q, $torrent);
          $torrent->setRelevance($relevance);
          $collection->torrents[] = $torrent;
        }
      }
    }

    return $collection;
  }

  protected function relevance($q, Torrent $torrent){
    $string = $torrent->getName(true);

    $relevance = levenshtein($q, $string, 2, 2, 1);

    foreach(explode(' ', $string) as $word){
      $relevance -= (preg_match('#'.preg_quote($word).'#', $q) ? strlen($word):0);
    }

    $relevance -= ($torrent->getSeeders()/1000);

    return $relevance;
  }

  public function download(Torrent $torrent){
    $tmp = tempnam('/tmp', time());

    if(!isset($this->config->getTrackers()[$torrent->getTracker()])){
      throw new InvalidArgumentException("Unknown tracker '".$torrent->getTracker()."'");
    }

    $tracker = $this->config->getTrackers()[$torrent->getTracker()];
    $tracker->download($torrent, $tmp);

    $basename = $torrent->getName(true).'_'.$torrent->getTracker();
    $basename = str_replace(['"', "'", '&', '/', '\\', '?', '#'], '_', $basename);
    $basename = str_replace(' ', '.', $basename);

    copy($tmp, $this->config->getDestination().'/'.$basename.'.torrent');
    unlink($tmp);
  }

}

?>
