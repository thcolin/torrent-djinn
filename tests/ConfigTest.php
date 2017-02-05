<?php

use thcolin\TorrentDjinn\Config;

class ConfigTest extends PHPUnit_Framework_TestCase{

  public function setUp(){
    error_reporting(E_ALL^E_WARNING);
    ini_set('memory_limit', '-1');
  }

  public function testConstructWithTrackersSuccess(){
    $config = new Config('/tmp', [
      'ABN' => [
        'enable' => true,
        'credentials' => [
          'username' => 'username',
          'password' => 'password'
        ]
      ],
      'T411' => [
        'enable' => true,
        'credentials' => [
          'username' => 'username',
          'password' => 'password'
        ]
      ]
    ]);
    $this->assertInstanceOf('thcolin\TorrentDjinn\Config', $config);
    $this->assertCount(2, $config->getTrackers());
  }

  public function testConstructWithoutTrackersSuccess(){
    $config = new Config('/tmp', []);
    $this->assertInstanceOf('thcolin\TorrentDjinn\Config', $config);
    $this->assertCount(0, $config->getTrackers());
  }

  public function testConstructWithUnknownPathFail(){
    $this->setExpectedException('InvalidArgumentException');
    $config = new Config('unknown_path', []);
  }

}

?>
