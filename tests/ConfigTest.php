<?php

use thcolin\TorrentDjinn\Config;

class ConfigTest extends PHPUnit_Framework_TestCase{

  public static function setUpBeforeClass(){
    error_reporting(E_ALL^E_WARNING);
    ini_set('memory_limit', '-1');

    define('__CONFIG_DEFAULT_FILE__', __DIR__.'/../config.default.json');
    define('__CONFIG_FILE__', __DIR__.'/../config.json');

    if(is_dir('/private/tmp')){
      define('__TMP__', '/private/tmp');
    } else{
      define('__TMP__', '/tmp');
    }
  }

  public function testConstructWithTrackersSuccess(){
    $config = new Config(__TMP__, [
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
    $this->assertEquals(__TMP__, $config->getDestination());
  }

  public function testConstructWithoutTrackersSuccess(){
    $config = new Config(__TMP__, []);

    $this->assertInstanceOf('thcolin\TorrentDjinn\Config', $config);
    $this->assertCount(0, $config->getTrackers());
    $this->assertEquals(__TMP__, $config->getDestination());
  }

  public function testConstructWithUnknownPathFail(){
    $this->setExpectedException('InvalidArgumentException');
    $config = new Config('unknown_path', []);
  }

  public function testSerializeWithTrackersSuccess(){
    $array = [
      'destination' => __TMP__,
      'trackers' => [
        'ABN' => [
          'enable' => true,
          'credentials' => [
            'username' => 'username',
            'password' => 'password'
          ]
        ],
        'T411' => [
          'enable' => false,
          'credentials' => [
            'username' => 'username',
            'password' => 'password'
          ]
        ]
      ]
    ];

    $config = new Config($array['destination'], $array['trackers']);
    $this->assertJsonStringEqualsJsonString($config->serialize(), json_encode($array, JSON_PRETTY_PRINT));
  }

  public function testSerializeWithoutTrackersSuccess(){
    $array = [
      'destination' => __TMP__
    ];

    $config = new Config($array['destination'], []);
    $this->assertJsonStringEqualsJsonString($config->serialize(), json_encode($array, JSON_PRETTY_PRINT));
  }

  public function testUnserializeSuccess(){
    $config = Config::unserialize(__CONFIG_DEFAULT_FILE__);

    $this->assertInstanceOf('thcolin\TorrentDjinn\Config', $config);
    $this->assertCount(2, $config->getTrackers(true));
    $this->assertEquals(__TMP__, $config->getDestination());
  }

  public function testUnserializeFail(){
    $this->setExpectedException('thcolin\TorrentDjinn\Exceptions\JSONUnvalidException');
    $config = Config::unserialize('unknown_path');
  }

  public function testSaveSuccess(){
    $path = __DIR__.'/config.json';
    $config = Config::unserialize(__CONFIG_DEFAULT_FILE__);
    $config->save($path);
    $this->assertFileExists($path);
    $this->assertJsonStringEqualsJsonFile($path, $config->serialize());
    unlink($path);
  }

  public function testSaveFail(){
    $this->setExpectedException('InvalidArgumentException');
    $config = Config::unserialize(__CONFIG_DEFAULT_FILE__);
    $config->save('');
  }

  public function testSetAndGetDestinationSuccess(){
    $config = new Config(__TMP__, []);
    $this->assertEquals(__TMP__, $config->getDestination());
    $config->setDestination(__DIR__);
    $this->assertEquals(__DIR__, $config->getDestination());
  }

  public function testSetDestinationExistFail(){
    $this->setExpectedException('InvalidArgumentException');
    $config = new Config('unknown_path', []);
  }

  public function testSetDestinationWritableFail(){
    $this->setExpectedException('InvalidArgumentException');
    $config = new Config('/dev', []);
  }

  public function testSetAndGetTrackersSuccess(){
    $config = new Config(__TMP__, []);

    $this->assertEquals([], $config->getTrackers());

    $config->setTrackers([
      'ABN' => [
        'enable' => true,
        'credentials' => [
          'username' => 'username',
          'password' => 'password'
        ]
      ],
      'T411' => [
        'enable' => false,
        'credentials' => [
          'username' => 'username',
          'password' => 'password'
        ]
      ]
    ]);

    $this->assertArrayHasKey('ABN', $config->getTrackers());
    $this->assertInstanceOf('thcolin\TorrentDjinn\Trackers\ABN', $config->getTrackers()['ABN']);

    $this->assertArrayNotHasKey('T411', $config->getTrackers());
    $this->assertArrayHasKey('T411', $config->getTrackers(true));
    $this->assertInstanceOf('thcolin\TorrentDjinn\Trackers\T411', $config->getTrackers(true)['T411']);
  }

  public function testAddTrackerSuccess(){
    $config = new Config(__TMP__, []);

    $this->assertEquals([], $config->getTrackers());

    $config->addTracker('ABN', [
      'enable' => true,
      'credentials' => [
        'username' => 'username',
        'password' => 'password'
      ]
    ]);

    $this->assertArrayHasKey('ABN', $config->getTrackers());
    $this->assertInstanceOf('thcolin\TorrentDjinn\Trackers\ABN', $config->getTrackers()['ABN']);

    $config->addTracker('T411', [
      'enable' => false,
      'credentials' => [
        'username' => 'username',
        'password' => 'password'
      ]
    ]);

    $this->assertArrayNotHasKey('T411', $config->getTrackers());
    $this->assertArrayHasKey('T411', $config->getTrackers(true));
    $this->assertInstanceOf('thcolin\TorrentDjinn\Trackers\T411', $config->getTrackers(true)['T411']);
  }

  public function testAddTrackerNotSupportedFail(){
    $config = new Config(__TMP__, []);

    $this->assertEquals([], $config->getTrackers());

    $this->setExpectedException('InvalidArgumentException');
    $config->addTracker('SNOWTIGERS', [ // RIP
      'enable' => true,
      'credentials' => [
        'username' => 'username',
        'password' => 'password'
      ]
    ]);
  }

  public function testAddTrackerCredentialsFail(){
    $config = new Config(__TMP__, []);

    $this->assertEquals([], $config->getTrackers());

    $this->setExpectedException('InvalidArgumentException');
    $config->addTracker('SNOWTIGERS', [ // RIP
      'enable' => true
    ]);
  }

  public function testAddTrackerEnableFail(){
    $config = new Config(__TMP__, []);

    $this->assertEquals([], $config->getTrackers());

    $this->setExpectedException('InvalidArgumentException');
    $config->addTracker('SNOWTIGERS', [ // RIP
      'credentials' => [
        'username' => 'username',
        'password' => 'password'
      ]
    ]);
  }

  public function testRemoveTrackerSuccess(){
    $config = new Config(__TMP__, [
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

    $this->assertCount(2, $config->getTrackers());
    $config->removeTracker('ABN');
    $this->assertCount(1, $config->getTrackers());
    $config->removeTracker('ABN');
    $this->assertCount(1, $config->getTrackers());
  }

}

?>
