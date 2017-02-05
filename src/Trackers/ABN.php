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

class ABN{
  use Tracker;

  const DOMAIN = 'https://abnormal.ws';
  const COOKIE_FILE = __DIR__.'/../../tmp/abn.cookie';

  protected function login($anonymous = false){
    if(!is_file(self::COOKIE_FILE)){
      $request = new Request(self::DOMAIN.'/login.php');

      $request->setOption(CURLOPT_POST, true);
      $request->setOption(CURLOPT_COOKIEJAR, self::COOKIE_FILE);
      $request->setOption(CURLOPT_POSTFIELDS, [
        'username' => $this->credentials['username'],
        'password' => $this->credentials['password'],
        'login' => 'Connexion'
      ]);

      $request = $this->browser->call($request, Browser::CALLBACK_REQUEST);
      curl_close($request->getHandle());
    }

    if(!is_file(self::COOKIE_FILE)){
      throw new LoginException(__CLASS__, 'No cookie file');
    } else if(!preg_match('#\#HttpOnly\_abnormal\.ws\s+FALSE\s+\/\s+TRUE\s+\d+\s+session\s+(.+)\s+#', file_get_contents(self::COOKIE_FILE))){
      throw new LoginException(__CLASS__, 'No session found in cookie file');
    }

    $this->browser->defaults[CURLOPT_COOKIEFILE] = self::COOKIE_FILE;
  }

  protected function test(){
    $request = new Request(self::DOMAIN.'/');
    $request = $this->browser->call($request, Browser::CALLBACK_REQUEST);

    switch($request->getInfo(CURLINFO_HTTP_CODE)){
      case 200:
        return;
      break;
      default:
        throw new LoginException(__CLASS__, 'Login test failed');
      break;
    }
  }

  protected function search($q){
    $collection = new TorrentCollection();
    $page = 1;
    $end = false;

    do{
      $request = new Request(self::DOMAIN.'/torrents.php?search='.urlencode($q).'&page='.$page++);
      $document = $this->browser->call($request, Browser::CALLBACK_DOCUMENT);

      foreach($document->find('.torrent_table tr') as $element){
        if(in_array('colhead', explode(' ', $element->attr('class')))){
          continue;
        }

        $td = $element->find('td');

        try{
          $torrent = new Torrent($this->class);

          $params = explode('&', $td[3]->find('a::attr(href)')[0]);
          $uuid = [
            'id' => explode('=', $params[1])[1],
            'key' => explode('=', $params[2])[1],
            'pass' => explode('=', $params[3])[1]
          ];

          $torrent->setUUID($uuid);
          $torrent->setName($td[1]->find('a::text')[0]);
          $torrent->setSeeders($td[5]->text());
          $torrent->setLeechers($td[6]->text());
          $torrent->setSize(explode(' ', $td[4]->text())[0] * pow(1024, array_search(explode(' ', $td[4]->text())[1], ['o', 'Ko', 'Mo', 'Go', 'To'])));

          $release = new Release($torrent->getName());
          $torrent->setRelease($release);
          $collection->torrents[] = $torrent;
        } catch(Exception $e){
          $collection->torrents[] = $torrent;
        }
      }

      if(!$document->find('.linkbox .pager_next')){
        $end = true;
      }
    } while(!$end);

    return $collection;
  }

  protected function download(Torrent $torrent, $tmp){
    $request = new Request(self::DOMAIN.'/torrents.php?action=download&id='.$torrent->getUUID()['id'].'&authkey='.$torrent->getUUID()['key'].'&torrent_pass='.$torrent->getUUID()['pass']);
    $this->browser->call($request, Browser::CALLBACK_FILE, [
      CURLOPT_FILE => $tmp
    ]);
  }

}
