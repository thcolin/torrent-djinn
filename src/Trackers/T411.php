<?php

namespace thcolin\TorrentDjinn\Trackers;

use thcolin\TorrentDjinn\Browser;
use thcolin\TorrentDjinn\Tracker;
use thcolin\TorrentDjinn\Torrent;
use thcolin\TorrentDjinn\TorrentCollection;
use thcolin\TorrentDjinn\Exceptions\LoginException;
use thcolin\SceneReleaseParser\Release;
use jyggen\Curl\Request;
use Exception;

class T411{
  use Tracker;

  const DOMAIN = 'https://api.t411.li';
  const COOKIE_FILE = __DIR__.'/../../tmp/t411.cookie';
  const LIMIT = 50;

  protected function login(){
    if(!is_file(self::COOKIE_FILE)){
      $request = new Request(self::DOMAIN.'/auth');

      $request->setOption(CURLOPT_POST, true);
      $request->setOption(CURLOPT_POSTFIELDS, [
        'username' => $this->credentials['username'],
        'password' => $this->credentials['password']
      ]);

      $json = $this->browser->call($request, Browser::CALLBACK_JSON);
      file_put_contents(self::COOKIE_FILE, json_encode($json));
    } else{
      $content = file_get_contents(self::COOKIE_FILE);
      $json = json_decode($content, true);
    }

    if(!isset($json['token'])){
      throw new LoginException(__CLASS__, 'No token found in cookie file');
    }

    $this->browser->defaults[CURLOPT_HTTPHEADER] = [
      'Authorization: '.$json['token']
    ];
  }

  protected function test(){
    $request = new Request(self::DOMAIN.'/bookmarks');
    $json = $this->browser->call($request, Browser::CALLBACK_JSON);

    if(isset($json['error'])){
      throw new LoginException(__CLASS__, 'Login test failed');
    }
  }

  protected function search($q){
    $collection = new TorrentCollection();
    $offset = 0;

    do{
      $request = new Request(self::DOMAIN.'/torrents/search/'.urlencode($q).'?offset='.$offset.'&limit='.self::LIMIT);
      $json = $this->browser->call($request, Browser::CALLBACK_JSON);

      $json['torrents'] = (isset($json['torrents']) ? $json['torrents']:[]);
      $offset += count($json['torrents']);

      foreach($json['torrents'] as $info){
        try{
          $torrent = new Torrent($this->class);
          $torrent->setUUID($info['id']);
          $torrent->setName($info['name']);
          $torrent->setSeeders($info['seeders']);
          $torrent->setLeechers($info['leechers']);
          $torrent->setSize($info['size']);

          $release = new Release($torrent->getName());
          $torrent->setRelease($release);
          $collection->torrents[] = $torrent;
        } catch(Exception $e){
          $collection->torrents[] = $torrent;
        }
      }
    } while(count($json['torrents']));

    return $collection;
  }

  protected function download(Torrent $torrent, $tmp){
    $request = new Request(self::DOMAIN.'/torrents/download/'.$torrent->getUUID());
    $this->browser->call($request, Browser::CALLBACK_FILE, [
      CURLOPT_FILE => $tmp
    ]);
  }

}
