<?php

namespace thcolin\TorrentDjinn\Commands;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use thcolin\TorrentDjinn\Commands\CommandAbstract;
use thcolin\SceneReleaseParser\Release;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class TorrentCommand extends CommandAbstract{

  const DISPLAY_SOFT = 'soft';
  const DISPLAY_FULL = 'full';

  protected function configure(){
    $this
      ->setName('torrent')
      ->setDescription('Search torrents and download them !')
      ->addArgument('search', InputArgument::OPTIONAL, 'Search terms')

      ->addOption('soft', null, InputOption::VALUE_NONE, "Don't show me the djinn !")
      ->addOption('display', null, InputOption::VALUE_REQUIRED, 'Display mode (soft or full), if full : show torrent name first', self::DISPLAY_SOFT)
      ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Order the search by a parameter (seeders, leechers, size) and order (asc, desc), format : parameter:order', 'seeders:desc')
      ->addOption('strict', null, InputOption::VALUE_REQUIRED, "Filter the search with or without the strict mode", null)

      ->addOption('filter-tracker', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter the search by tracker (ABN, HDOnly, T411...)')
      ->addOption('filter-type', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter the search by the media type (movie, tvshow)')
      ->addOption('filter-source', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter the search by the source (DVDRip, DVDScr, WEB-DL, BRRip, BDRip, DVD-R, R5, HDRip, BLURAY, PDTV, SDTV)')
      ->addOption('filter-encoding', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter the search by the encoding (XviD, DivX, x264, x265, h264)')
      ->addOption('filter-resolution', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter the search by the resolution (720p, 1080p)')
      ->addOption('filter-language', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter the search by the language (FRENCH, MULTI...)')
      ->addOption('filter-season', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter the search by the season')
      ->addOption('filter-episode', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter the search by the episode');
  }

  protected function execute(InputInterface $input, OutputInterface $output){
    $this->init($input, $output);

    if(!$input->getOption('soft')){
      $this->hello();
    }

    $display = $input->getOption('display');
    $order = $input->getOption('order');
    $filters = $this->getFilters();

    if(!isset($filters['tracker'])){
      $filters['tracker'] = array_keys($this->djinn->getTrackers());
    }

    if(!$search = $input->getArgument('search')){
      $search = $this->ask(CommandAbstract::ASK_SEARCH);
    }

    if($strict = $input->getOption('strict') != null){
      $this->djinn->setStrict($strict != 'false');
    }

    do{
      $selection = null;

      if(!isset($collection)){
        $collection = $this->djinn->search($search, $filters['tracker']);
      }

      $torrents = $collection->filter($this->djinn->getStrict(), $filters, $order);

      $this->resume([
        'search' => $search,
        'results' => $torrents,
        'order' => $order,
        'filters' => $filters
      ]);

      $answer = $this->results($torrents, $display);

      switch($answer){
        case 'edit':
          $search = $this->search();
          unset($collection);
        break;

        case 'display':
          $display = $this->display();
        break;

        case 'order':
          $order = $this->order();
        break;

        case 'filters':
          $filters = $this->filters($filters);
        break;

        case 'cancel':
          return -1;
        break;

        default:
          $selection = $answer;
        break;
      }
    } while(!$selection);

    // Download the selected torrent
    $this->djinn->download($selection);
    $output->writeln('Torrent file : <info>'.$selection->getName().'</info> downloaded !');
  }

  private function results($torrents, $display = self::DISPLAY_SOFT){
    $choices = [];

    foreach($torrents as $torrent){
      $tracker = $torrent->getTracker();

      switch($display){
        case self::DISPLAY_SOFT :
          $name = ($torrent->getRelease() ? '<info>'.$torrent->getRelease()->getRelease(Release::GENERATED_RELEASE).'</info>':'<fg=red>'.$torrent->getName().'</>');
        break;

        case self::DISPLAY_FULL:
          $name = '<comment>'.$torrent->getName().'</comment>'.($torrent->getRelease() ? ' - (<info>'.$torrent->getRelease()->getRelease(Release::GENERATED_RELEASE).'</info>)':'');
        break;
      }

      $size = $torrent->getSize();
      $seeders = $torrent->getSeeders();
      $leechers = $torrent->getLeechers();

      $choices[] = '<question>'.$tracker.'</question> - '.$name.' <comment>'.$size.'</comment> <error>('.$seeders.'-'.$leechers.')</error>';
    }

    $actions = ['edit', 'display', 'order', 'filters', 'cancel'];

    $question = new ChoiceQuestion(
      (!count($choices) ? 'Select the action you want to do :':'Select the torrent you want to download :'),
      array_merge($choices, $actions)
    );

    $answer = $this->getHelper('question')->ask($this->input, $this->output, $question);

    if(!in_array($answer, $actions)){
      return $torrents[array_search($answer, $choices)];
    }

    return $answer;
  }

  private function display(){
    $choices = [
      self::DISPLAY_FULL => 'TORRENT (RELEASE) - <comment>Alien Anthology 1080p Multi x264 DTS-HD DTS-HD</comment> (<info>Alien.MULTI.1080p.HDRip.x264-NOTEAM</info>)',
      self::DISPLAY_SOFT => 'RELEASE - <info>Alien.MULTI.1080p.HDRip.x264-NOTEAM</info>'
    ];

    $question = new ChoiceQuestion(
      'Select the display mode you want to use :',
      $choices
    );

    $this->output->writeln('');
    return $this->getHelper('question')->ask($this->input, $this->output, $question);
  }

  private function order(){
    // column
    $question = new ChoiceQuestion(
      'Select the column you want to use :',
      ['seeders', 'leechers', 'size']
    );

    $this->output->writeln('');
    $column = $this->getHelper('question')->ask($this->input, $this->output, $question);

    // way
    $question = new ChoiceQuestion(
      'Select the way you want to use :',
      ['asc', 'desc']
    );

    $this->output->writeln('');
    $way = $this->getHelper('question')->ask($this->input, $this->output, $question);

    return $column.':'.$way;
  }

  private function filters($context){
    do{
      $question = new ChoiceQuestion(
        'Which filter do you want to edit :',
        [
          'tracker' => (isset($context['tracker']) ? implode(',', $context['tracker']):'<fg=red>null</>'),
          'type' => (isset($context['type']) ? implode(',', $context['type']):'<fg=red>null</>'),
          'source' => (isset($context['source']) ? implode(',', $context['source']):'<fg=red>null</>'),
          'encoding' => (isset($context['encoding']) ? implode(',', $context['encoding']):'<fg=red>null</>'),
          'resolution' => (isset($context['resolution']) ? implode(',', $context['resolution']):'<fg=red>null</>'),
          'language' => (isset($context['language']) ? implode(',', $context['language']):'<fg=red>null</>'),
          'season' => (isset($context['season']) ? implode(',', $context['season']):'<fg=red>null</>'),
          'episode' => (isset($context['episode']) ? implode(',', $context['episode']):'<fg=red>null</>'),
          'done' => '<fg=yellow>done</>'
        ]
      );

      $this->output->writeln('');
      $option = $this->getHelper('question')->ask($this->input, $this->output, $question);

      switch($option){
        case 'tracker':
          $trackers = array_keys($this->djinn->getTrackers());
          $choices = array_combine($trackers, $trackers);
        break;

        case 'type':
          $choices = [
            'movie' => Release::MOVIE,
            'tvshow' => Release::TVSHOW,
          ];
        break;

        case 'source':
          $choices = [
            'dvdrip' => 'DVDRip',
            'dvdscr' => 'DVDScr',
            'webdl' => 'WEB-DL',
            'bdrip' => 'BDRip',
            'dvdr' => 'DVD-R',
            'r5' => 'R5',
            'hdrip' => 'HDRip',
            'bluray' => 'BLURAY',
            'pdtv' => 'PDTV',
            'sdtv' => 'SDTV',
          ];
        break;

        case 'encoding':
          $choices = [
            'xvid' => 'XviD',
            'divx' => 'DivX',
            'x264' => 'x264',
            'w265' => 'x265',
            'h264' => 'h264',
          ];
        break;

        case 'resolution':
          $choices = [
            '1080p' => '1080p',
            '720p' => '720p',
          ];
        break;

        case 'language':
        case 'season':
        case 'episode':
          $choices = [];
        break;

        // Break the while loop
        case 'done':
          $this->output->writeln('');
          return $context;
        break;
      }

      $answer = $this->filter($choices);

      if(is_int(array_search('delete', $answer))){
        if(isset($context[$option])){
          unset($context[$option]);
        }
      } else if(!is_int(array_search('cancel', $answer))){
        if(count($answer) == 1 && $answer[0] == ''){
          unset($context[$option]);
        } else{
          $context[$option] = $answer;
        }
      }
    } while(true);
  }

  private function filter($context){
    $this->output->writeln('');

    if(count($context)){
      $question = new ChoiceQuestion(
        'Which values :',
        array_merge($context, [
          'delete' => '<fg=red>delete</>',
          'cancel' => '<fg=yellow>cancel</>'
        ])
      );

      $question->setMultiselect(true);
      $answer = $this->getHelper('question')->ask($this->input, $this->output, $question);

      $return = [];
      foreach($answer as $value){
        $return[] = $context[$value];
      }
    } else{
      $answer = $this->getHelper('question')->ask($this->input, $this->output, new Question('Which value ? (separated by a comma) '));
      $return = explode(',', $answer);
    }

    return $return;
  }

}
