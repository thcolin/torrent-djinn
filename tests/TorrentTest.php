<?php

use thcolin\TorrentDjinn\Torrent;

class TorrentTest extends PHPUnit_Framework_TestCase{

  public static function setUpBeforeClass(){
    error_reporting(E_ALL^E_WARNING);
    ini_set('memory_limit', '-1');
  }

  public function testConstructWithTrackerSuccess(){
    $torrent = new Torrent('T411');

    $this->assertInstanceOf('thcolin\TorrentDjinn\Torrent', $torrent);
    $this->assertEquals('T411', $torrent->getTracker());
  }

}

?>
