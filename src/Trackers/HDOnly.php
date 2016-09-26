<?php

namespace thcolin\TorrentDjinn\Trackers;

use thcolin\TorrentDjinn\Tracker;
use thcolin\TorrentDjinn\Torrent;
use thcolin\TorrentDjinn\TorrentCollection;
use thcolin\TorrentDjinn\Exceptions\LoginException;
use thcolin\SceneReleaseParser\Release;
use jyggen\Curl\Request;
use LogicException;
use Exception;

class HDOnly extends Tracker{

  const DOMAIN = 'https://hd-only.org';
  const COOKIE_FILE = __DIR__.'/../../tmp/hdonly.cookie';

  protected function login($credentials, $anonymous = false){
    $this->defaults[CURLOPT_SSL_VERIFYPEER] = false;

    if(!is_file(self::COOKIE_FILE)){
      $request = new Request(self::DOMAIN.'/login.php');

      $request->setOption(CURLOPT_POST, true);
      $request->setOption(CURLOPT_COOKIEJAR, self::COOKIE_FILE);
      $request->setOption(CURLOPT_POSTFIELDS, [
        'username' => $credentials['username'],
        'password' => $credentials['password'],
        'keeplogged' => 1,
        'login' => "M'identifier"
      ]);

      $request = $this->call($request, Tracker::CALLBACK_REQUEST);
      curl_close($request->getHandle());
    }

    if(!is_file(self::COOKIE_FILE)){
      throw new LoginException(__CLASS__, 'No cookie file');
    } else if(!preg_match('#\#HttpOnly\_hd-only\.org\s+FALSE\s+\/\s+TRUE\s+\d+\s+session\s+(.+)\s+#', file_get_contents(self::COOKIE_FILE))){
      throw new LoginException(__CLASS__, 'No session found in cookie file');
    }

    $this->defaults[CURLOPT_COOKIEFILE] = self::COOKIE_FILE;

    // test
    $request = new Request(self::DOMAIN.'/');
    $request = $this->call($request, Tracker::CALLBACK_REQUEST);

    switch($request->getInfo(CURLINFO_HTTP_CODE)){
      case 200:
        return;
      break;
      default:
        throw new LoginException(__CLASS__, 'Login test failed');
      break;
    }
  }

  public function search($q){
    $collection = new TorrentCollection();
    $page = 1;
    $end = false;

    do{
      $request = new Request(self::DOMAIN.'/torrents.php?searchstr='.urlencode($q).'&page='.$page++);
      $document = $this->call($request, Tracker::CALLBACK_DOCUMENT);

      foreach($document->find('.torrent_table tr') as $element){
        $class = explode(' ', $element->attr('class'));

        if(in_array('group', $class)){
          $td = $element->find('td');
          $name = trim($td[2]->find('a::text')[0]);
          $name = str_replace(' ', '.', $name);
          continue;
        } else if(in_array('edition', $class)){
          $source = substr(trim($element->text()), 4);
          $source = str_replace('Blu-Ray Rip', 'BLURAY', $source);
          $source = str_replace('Blu-Ray Remux', 'BLURAY', $source);
          $source = str_replace('Blu-Ray', 'BLURAY', $source);
          $source = str_replace('Web-DL/Rip', 'WEB-DL', $source);
          $source = str_replace(' ', '', $source);
          continue;
        } else if(in_array('colhead', $class)){
          continue;
        }

        $td = $element->find('td');

        try{
          $torrent = new Torrent($this->class);

          $params = explode('&', $td[0]->find('span a::attr(href)')[0]);
          $uuid = [
            'id' => explode('=', $params[1])[1],
            'key' => explode('=', $params[2])[1],
            'pass' => explode('=', $params[3])[1]
          ];

          $group = ($td[0]->find('a strong::text') ? trim($td[0]->find('a strong::text')[0]):null);
          $tags = $td[0]->find('a::text')[2];
          $tags = ($group ? substr($tags, 0, -3):$tags);

          $tags = str_replace('AVC / ', '', $tags);
          $tags = str_replace('VC-1 / ', '', $tags);
          $tags = str_replace('Ép ', 'EP', $tags);
          $tags = preg_replace('#S (\d+)#', 'S$1', $tags);
          $tags = str_replace('VO / stFR', 'VOSTFR', $tags);
          $explode = explode(' / ', $tags);

          $torrent->setUUID($uuid);
          $torrent->setName($name.'.'.implode('.', $explode).'.'.$source.'-'.($group ? $group:'HDO'));
          $torrent->setSeeders($td[5]->text());
          $torrent->setLeechers($td[6]->text());
          $torrent->setSize(intval((explode(' ', $td[3]->text())[0] * pow(1024, array_search(explode(' ', $td[3]->text())[1], ['B', 'KB', 'MB', 'GB', 'TB'])))));

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

  public function download(Torrent $torrent, $tmp){
    $request = new Request(self::DOMAIN.'/torrents.php?action=download&id='.$torrent->getUUID()['id'].'&authkey='.$torrent->getUUID()['key'].'&torrent_pass='.$torrent->getUUID()['pass']);
    $this->call($request, Tracker::CALLBACK_FILE, [
      CURLOPT_FILE => $tmp
    ]);
  }

}
