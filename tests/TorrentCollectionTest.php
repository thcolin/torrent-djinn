<?php

use thcolin\TorrentDjinn\Torrent;
use thcolin\TorrentDjinn\TorrentCollection;
use thcolin\SceneReleaseParser\Release;

class TorrentCollectionTest extends PHPUnit_Framework_TestCase{

  public static function setUpBeforeClass(){
    error_reporting(E_ALL^E_WARNING);
    ini_set('memory_limit', '-1');
  }

  public function setUp(){
    if(!isset($this->torrents)){
      $this->torrents = [];

      $torrent = new Torrent('T411');
      $torrent->setUUID('3c9ad252-90f0-401c-b9ce-176c14e0ad24');
      $torrent->setName('Inception.2010.TRUEFRENCH.1080p.HDRip.x264-NOTEAM');
      $torrent->setSeeders(245);
      $torrent->setLeechers(1);
      $torrent->setSize(2480343613.44);
      $torrent->setRelease(new Release($torrent->getName()));
      $torrent->setRelevance(0);
      $this->torrents[] = $torrent;

      $torrent = new Torrent('ABN');
      $torrent->setUUID('5b120ea4-0d73-4a19-9efc-c3e851c3f1b4');
      $torrent->setName('Inception.2010.FRENCH.DVDRip.XviD.AC3-LiberTeam');
      $torrent->setSeeders(4);
      $torrent->setLeechers(0);
      $torrent->setSize(2619930050.56);
      $torrent->setRelease(new Release($torrent->getName()));
      $torrent->setRelevance(100);
      $this->torrents[] = $torrent;

      $torrent = new Torrent('SnowTigers');
      $torrent->setUUID('b75ce69c-18fe-48b5-96fc-4d681df77b1d');
      $torrent->setName('Inception.2010.VOSTFR.1080p.BLURAY.x265-NOTEAM');
      $torrent->setSeeders(66);
      $torrent->setLeechers(12);
      $torrent->setSize(3629247365.12);
      $torrent->setRelease(new Release($torrent->getName()));
      $torrent->setRelevance(0);
      $this->torrents[] = $torrent;

      $torrent = new Torrent('T411');
      $torrent->setUUID('20e32e25-8086-44aa-8c27-1d00ef97dfd0');
      $torrent->setName('Inception.2010.TRUEFRENCH.DVDRip.XviD.AC3-UTT');
      $torrent->setSeeders(44);
      $torrent->setLeechers(150);
      $torrent->setSize(5465345884.16);
      $torrent->setRelevance(100);
      $this->torrents[] = $torrent;

      $torrent = new Torrent('ABN');
      $torrent->setUUID('b8d5a09a-aafe-4b6f-9837-62ef07549bcc');
      $torrent->setName('Inception.2010.TRUEFRENCH.720p.HDRip.x264-BSD');
      $torrent->setSeeders(569);
      $torrent->setLeechers(30);
      $torrent->setSize(1900523028.48);
      $torrent->setRelevance(0);
      $this->torrents[] = $torrent;

      $torrent = new Torrent('SnowTigers');
      $torrent->setUUID('827e87ad-163a-4e95-a411-5a62ec237d95');
      $torrent->setName('Inception.2010.MULTI.720p.HDRip.x265-NOTEAM');
      $torrent->setSeeders(22);
      $torrent->setLeechers(0);
      $torrent->setSize(1524713390.08);
      $torrent->setRelevance(100);
      $this->torrents[] = $torrent;
    }
  }

  public function testConstructSuccess(){
    $collection = new TorrentCollection();
    $collection->torrents = $this->torrents;

    $this->assertEquals($this->torrents, $collection->torrents);
  }

  public function testFilterPolicyFlexibleSuccess(){
    $collection = new TorrentCollection();
    $collection->torrents = $this->torrents;

    $this->assertCount(6, $collection->filter('Inception', 'flexible', [], 'seeders:desc'));
  }

  public function testFilterPolicyModerateSuccess(){
    $collection = new TorrentCollection();
    $collection->torrents = $this->torrents;

    $this->assertCount(3, $collection->filter('Inception', 'moderate', [], 'seeders:desc'));
  }

  public function testFilterPolicyStrictSuccess(){
    $collection = new TorrentCollection();
    $collection->torrents = $this->torrents;

    $this->assertCount(3, $collection->filter('Inception', 'strict', [], 'seeders:desc'));
  }

}

?>
