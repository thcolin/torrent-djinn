<?php

use thcolin\TorrentDjinn\Torrent;
use thcolin\SceneReleaseParser\Release;

class TorrentTest extends PHPUnit_Framework_TestCase{

  public static function setUpBeforeClass(){
    error_reporting(E_ALL^E_WARNING);
    ini_set('memory_limit', '-1');
  }

  public function testConstructSuccess(){
    $torrent = new Torrent('T411');

    $this->assertInstanceOf('thcolin\TorrentDjinn\Torrent', $torrent);
    $this->assertEquals('T411', $torrent->getTracker());
  }

  public function testSetAndGetTrackerSuccess(){
    $torrent = new Torrent('T411');

    $this->assertEquals('T411', $torrent->getTracker());
    $torrent->setTracker('ABN');
    $this->assertEquals('ABN', $torrent->getTracker());
  }

  public function testSetAndGetUUIDSuccess(){
    $torrent = new Torrent('T411');

    $torrent->setUUID('1234');
    $this->assertEquals('1234', $torrent->getUUID());
  }

  public function testSetAndGetReleaseSuccess(){
    $torrent = new Torrent('T411');

    $this->assertEquals(null, $torrent->getRelease());
    $release = new Release('Mr.Robot.S01E05.PROPER.VOSTFR.720p.WEB-DL.DD5.1.H264-ARK01');
    $torrent->setRelease($release);
    $this->assertEquals($release, $torrent->getRelease());
  }

  public function testSetAndGetNameSuccess(){
    $torrent = new Torrent('T411');

    $torrent->setName('Test');
    $this->assertEquals('Test', $torrent->getName());
    $torrent->setRelease(new Release('Mr.Robot.S01E05.PROPER.VOSTFR.720p.WEB-DL.DD5.1.H264-ARK01'));
    $this->assertEquals('Test', $torrent->getName());
    $this->assertEquals('Mr.Robot.S01E05.VOSTFR.720p.WEB-DL.h264-ARK01', $torrent->getName(true));
  }

  public function testSetAndGetSeedersSuccess(){
    $torrent = new Torrent('T411');

    $torrent->setSeeders(1234);
    $this->assertEquals(1234, $torrent->getSeeders());
    $torrent->setSeeders('4567@');
    $this->assertEquals(4567, $torrent->getSeeders());
  }

  public function testSetAndGetLeechersSuccess(){
    $torrent = new Torrent('T411');

    $torrent->setLeechers(1234);
    $this->assertEquals(1234, $torrent->getLeechers());
    $torrent->setLeechers('4567@');
    $this->assertEquals(4567, $torrent->getLeechers());
  }

  public function testSetAndGetSizeSuccess(){
    $torrent = new Torrent('T411');

    $torrent->setSize(13511221232);
    $this->assertEquals(13511221232, $torrent->getSize(false));
    $this->assertEquals('13.51 Gb', $torrent->getSize(true));
    $torrent->setSize('4567@');
    $this->assertEquals(4567.0, $torrent->getSize(false));
    $torrent->setSize(0);
    $this->assertEquals(1, $torrent->getSize(false));
  }

  public function testSetAndGetRelevanceSuccess(){
    $torrent = new Torrent('T411');

    $torrent->setRelevance(12.34);
    $this->assertEquals(12.34, $torrent->getRelevance());
    $torrent->setRelevance('4567@');
    $this->assertEquals(4567, $torrent->getRelevance());
  }

}

?>
