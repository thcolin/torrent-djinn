# Torrent Djinn

![torrent-djinn-logo](http://i.imgur.com/ZurEmq9.png)
![torrent-djinn-cli](http://i.imgur.com/4Us2D74.png)

PHP console application allowing you to search torrents on various trackers and filters them easily. Currently supporting only 3 french trackers : ABN, HDOnly & T411.

## Installation
First, you will need a local version of PHP, then clone the project and install dependencies with composer :
```
git clone https://github.com/thcolin/torrent-djinn.git
cd torrent-djinn
composer install
```

## Usage
The application got 3 commands :
- Config
- Torrent
- SensCritique

### Configuration
You can configure 3 parameters of the application :
- Torrent destination
- Mode (strict or soft)
- Trackers (username, password and state)

Launch the configuration of the application with the command ```config``` :
```
./bin/djinn config
```

Next, navigate through menus to configure each parameters of the application :
```
Which parameter do you want to configure :
  [dest] /root/downloads
  [mode] Strict
  [tks ] HDOnly,ABN,T411
  [done] done
 >
```

- For destination, use absolute path
- Strict mode only show torrents with a "valid" release name ([thcolin/scene-release-parser](https://github.com/thcolin/scene-release-parser))
- Once you configure all your trackers, you can test the connection of them and enable or disable them

### Torrent
Torrent command allow you to search through all enabled trackers and filters the results

Launch a torrent search with the command ```torrent``` and a ```search``` argument :
```
./bin/djinn torrent "Big Buck Bunny"
```

The command have some options :
- ```--soft``` : Don't show me the djinn !
- ```--order``` : Order the search by a parameter (seeders, leechers, size) and order (asc, desc), format : ```parameter:order```
- ```--policy``` : Apply a policy for your search : ```--policy=[flexible|moderate|strict]```
  - ```flexible``` : You see all the torrents
  - ```moderate``` : You only see torrents with a correct scene release name
  - ```strict``` : You only see quality torrents, and those too far from your search are ignored

And also some filters :
- ```--filter-tracker``` : Filter the search by tracker (ABN, HDOnly, T411...) (override config temporarily)
- ```--filter-type``` : Filter the search by the media type (movie, tvshow)
- ```--filter-source``` : Filter the search by the source (DVDRip, DVDScr, WEB-DL, BRRip, BDRip, DVD-R, R5, HDRip, BLURAY, PDTV, SDTV)
- ```--filter-encoding``` : Filter the search by the encoding (XviD, DivX, x264, x265, h264)
- ```--filter-resolution``` : Filter the search by the resolution (720p, 1080p)
- ```--filter-language``` : Filter the search by the language (FRENCH, MULTI...)
- ```--filter-season``` : Filter the search by the season (01, 02...)
- ```--filter-episode``` : Filter the search by the episode (01, 02...)

The djinn will resume your command and the results :
```
I've found 3 results for your search : "Big Buck Bunny"
I've ordered the results by : seeders:desc
With the filters : --tracker=HDOnly,ABN,T411
```

Next, select the id (0, 1, 2...) of the torrent you want to download :
```
  [X] TRACKER - RELEASE NAME X Gb (Seeders-Leechers)
  [0] T411 - Big.Buck.Bunny.FRENCH.BDRip.x264-NOTEAM 138.36 Mb (26-0)
  [1] T411 - Big.Buck.Bunny.DVDRip.AC3-NOTEAM 220.51 Mb (1-0)
  [2] T411 - Big.Buck.Bunny.BDRip.XviD-NOTEAM 183.57 Mb (0-0)
```
Torrents are dowloaded to configured destination

During the search, you can make some actions :
```
  [3] edit // edit search terms
  [4] display // edit display mode (full/soft)
  [5] order // re-order results
  [6] policy // redefine policy (flexible/moderate/strict)
  [7] filters // filters results
  [8] cancel
```

### SensCritique
With the senscritique command, you can check the collection and the lists of a SensCritique user, launch it with the ```username``` argument :
```
./bin/djinn senscritique "Plug_In_Papa"
```

Same as torrent command, this one got options too :
- ```--soft``` : Don't show me the djinn !
- ```--order``` : Order the search by the last time action (asc, desc)
- ```--anonymous``` : You will see all the movies or tv shows (including those you already downloaded with this djinn)

And also some filters :
- ```--filter-type``` : Filter the search by the media type (movie, tvshow)
- ```--filter-year``` : Filter the search by a year
- ```--filter-genres``` : Filter the search by genre(s) (Comédie, Romance, Drame...)

First, select the list of artworks you want to see :
```
  [0] Collection
  [1] List - T'as vu un épisode, tu les as tous vus
  [2] List - Et si je filmais le cul de mon actrice au lieu d'essayer de...
 >
```

Next, the djinn will resume the command and the results :
```
I've found 27 results for your search : "Plug_In_Papa - T'as vu un épisode, tu les as tous vus : Ces séries, pas forcément toutes mauvaises, qui abusent d'une formule unique recyclée d'épisode en épisode où seul le nom des protagonistes secondaires est amené à changer, sans doute pour ne pas bouleverser les repères de la ménagère. Parfois le talent d'écriture arrive à maintenir l'intérêt mais bien souvent on finit étouffé par la routine. L'astuce cache-misère consiste à faire 3/4 épisodes hors canevas (souvent en début et fin de saison) pour faire croire que la formule peut se renouveler. Technique procédurale ou simple paresse ?"
I've ordered the results by : desc
```

And show you each artwork of the list and ask you if you want to download it :
```
Dr House (House, M.D.) (2004) - David Shore
Avec Hugh Laurie, Robert Sean Leonard, Lisa Edelstein, Omar Epps, Jennifer Morrison, Jesse Spencer, Peter Jacobson, Olivia Wilde
Genres : Drame
La personnalité singulière de Dr House est très complexe. Il est spécialiste du diagnostic et passé maître dans l'art de soigner les maladies rares...
Download this artwork ? [Y/n]
```
If yes, you will be redirected to torrent command with the title of the artwork as ```search``` argument. You can cancel the command within it.

## TODO
* Update README with new features (clean, policy, relevance...)
* Set all message to english
* Do tell the djinn more things on `hello()` function
* Add logs & level (mainly debug)
* Order senscritique artworks (by others params than last action, like year...)
* Show only senscritique `wishes`
  * Add filter to senscritique-api
* Make tests on Djinn class mainly
* Add an option to override temporarily the destination config
* Show connection tries on trackers
* Suggest to disable trackers after X failed connection tries
* Add pagination to results
* Check [Kevin Deisz's good practices](http://eng.localytics.com/exploring-cli-best-practices/) on CLI app

## Bugs
* Up to date ! (Yes, I should use the issues..)

## Thanks
* [Icons8](https://icons8.com/) for Djinn icon !
