<?php

namespace thcolin\TorrentDjinn;
use DiDom\Document;

abstract class Tracker{

  const CALLBACK_REQUEST = 0;
  const CALLBACK_HTML = 1;
  const CALLBACK_DOCUMENT = 2;
  const CALLBACK_JSON = 4;
  const CALLBACK_FILE = 8;

  protected $defaults = [];

  public function __construct($credentials){
    $this->login($credentials);
    $explode = explode('\\', get_called_class());
    $this->class = end($explode);
  }

  abstract protected function login($credentials);

  abstract public function search($q);

  abstract public function download(Torrent $torrent, $tmp);

  protected function call(&$request, $callback = self::CALLBACK_HTML, $options = []){
    foreach($this->defaults as $option => $value){
      $request->setOption($option, $value);
    }

    foreach($options as $option => $value){
      switch($option){
        case CURLOPT_FILE:
          $destination = $value;
          $fp = fopen($destination, 'w+');
          $tmp = tmpfile();
          $value = $tmp;
        break;
      }

      $request->setOption($option, $value);
    }

    $request->execute();

    switch($callback){
      case self::CALLBACK_HTML :
        $raw = substr($request->getRawResponse(), $request->getInfo(CURLINFO_HEADER_SIZE));
        return $raw;
      break;
      case self::CALLBACK_DOCUMENT :
        $raw = substr($request->getRawResponse(), $request->getInfo(CURLINFO_HEADER_SIZE));
        $document = new Document($raw);
        return $document;
      break;
      case self::CALLBACK_JSON :
        $raw = substr($request->getRawResponse(), $request->getInfo(CURLINFO_HEADER_SIZE));
        $json = json_decode($raw, true);
        $json = $json ? $json:[];
        return $json;
      break;
      case self::CALLBACK_FILE :
        fseek($tmp, $request->getInfo(CURLINFO_HEADER_SIZE));

        while(!feof($tmp)){
          fwrite($fp, fread($tmp, 8192));
        }

        fclose($fp);
        fclose($tmp);

        return $destination;
      break;
      default :
        return $request;
      break;
    }
  }

}

?>
