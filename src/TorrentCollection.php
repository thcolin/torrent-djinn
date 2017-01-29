<?php

namespace thcolin\TorrentDjinn;

class TorrentCollection{
  public $torrents = [];

  public function filter($strict = false, $filters = [], $order = 'seeders:desc'){
    $torrents = [];

    $torrents = array_filter($this->torrents, function($torrent) use($strict, $filters){
      if($strict && (!$torrent->getRelease() || !$torrent->getRelease()->getTitle())){
        return false;
      } else if(!$filters){
        return true;
      }

      $bool = 1;

      foreach($filters as $filter => $value){
        $function = 'get'.ucfirst($filter);

        if($torrent->getRelease() && method_exists($torrent->getRelease(), $function)){
          if(is_array($value) && !in_array($torrent->getRelease()->$function(), $value)){
            $bool *= 0;
          } else if(!is_array($value) && $torrent->getRelease()->$function() != $value){
            $bool *= 0;
          }
        } else if(method_exists($torrent, $function)){
          if(is_array($value) && !in_array($torrent->$function(), $value)){
            $bool *= 0;
          } else if(!is_array($value) && $torrent->$function() != $value){
            $bool *= 0;
          }
        }
      }

      return $bool;
    });

    // default: order by relevance:desc
    list($param, $order) = explode(':', $order);
    usort($torrents, function($a, $b) use ($param, $order){
      $function = method_exists($a, 'get'.ucfirst($param)) ? 'get'.ucfirst($param):'getRelevance';

      $ra = $a->$function(false);
      $rb = $b->$function(false);

      if($order == 'desc'){
        return($ra == $rb ? 0:($ra > $rb ? -1:1));
      } else{
        return($ra == $rb ? 0:($ra > $rb ? 1:-1));
      }
    });

    return $torrents;
  }
}
