<?php

namespace thcolin\TorrentDjinn\Exceptions;

use Exception;

class LoginException extends Exception{
  public function __construct($class, $reason = null){
    if(is_file($class::COOKIE_FILE)){
      unlink($class::COOKIE_FILE);
    }

    parent::__construct('Error during login on "'.$class.'", check your credentials'.($reason ? ' : '.$reason:''));
  }
}
