<?php

namespace thcolin\TorrentDjinn\Commands;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use thcolin\SensCritiqueAPI\Models\Artwork;
use thcolin\SensCritiqueAPI\Models\Movie;
use thcolin\SensCritiqueAPI\Models\TVShow;
use thcolin\SensCritiqueAPI\Exceptions\RedirectException;
use thcolin\SensCritiqueAPI\Exceptions\DocumentParsingException;

class SensCritiqueCommand extends CommandAbstract{

  protected function configure(){
    $this
      ->setName('senscritique')
      ->setDescription('Search artworks on SensCritique (from users\' collections & lists) and download them')
      ->addArgument('search', InputArgument::OPTIONAL, 'SensCritique search (user)')

      ->addOption('soft', null, InputOption::VALUE_NONE, "Don't show me the djinn !")
      ->addOption('order', null, InputOption::VALUE_REQUIRED, 'Order the search by the last time action (asc, desc)', 'desc')
      ->addOption('anonymous', null, null, "You will see all the movies or tv shows (including those you already downloaded)")

      ->addOption('filter-type', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter the search by the media type ('.Movie::TYPE.', '.TVShow::TYPE.')')
      ->addOption('filter-year', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter the search by a year')
      ->addOption('filter-genres', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Filter the search by genre(s) (Comédie, Romance, Drame...)');
  }

  protected function execute(InputInterface $input, OutputInterface $output){
    $this->init($input, $output);

    if(!$input->getOption('soft')){
      $this->hello();
    }

    $order = $input->getOption('order');
    $anonymous = $input->getOption('anonymous');
    $filters = $this->getFilters();

    if(!$search = $input->getArgument('search')){
      $search = $this->search();
    }

    do{
      $user = null;

      try{
        $user = $this->senscritique->getUser($search);
      } catch(RedirectException $e){
        $output->writeln('<error>Erreur : Aucun membre SensCritique du nom de "'.$search.'"</error>');
        $search = $this->search();
      }
    } while(!$user);

    $list = $this->artworks($user);

    $this->resume([
      'search' => $search.' - '.$list->getName().''.($list->getDescription() ? ' : '.$list->getDescription():''),
      'results' => $list,
      'order' => $order,
      'filters' => $filters
    ], false);

    $start = 0;
    $end = $list->length();

    for($i = ($order == 'desc' ? $start:($end-1)); ($order == 'desc' ? ($i < $end):($i >= $start)); ($order == 'desc' ? $i++:$i--)){
      $artwork = $list[$i];

      try{
        if(!$anonymous && in_array($artwork->getId(), $this->saved)){
          continue;
        } if(isset($filters['type']) && !in_array($artwork::TYPE, $filters['type'])){
          continue;
        } if(isset($filters['year']) && !in_array($artwork->getYear(), $filters['year'])){
          continue;
        } if(isset($filters['genres']) && !count(array_intersect($filters['genres'], $artwork->getGenres(true)))){
          continue;
        }
      } catch(DocumentParsingException $e){
        continue;
      }

      if($skip = !$this->confirm($artwork)){
        continue;
      }

      $return = $this->torrent($artwork->getTitle());

      if($return != -1 && !$anonymous){
        $this->save($artwork);
      }
    }

    $output->writeln("\n".'<info>Fin de la recherche !</info>');
  }

  private function artworks($user){
    $choices = [
      '<info>Collection</info>'
    ];

    // Only way to get the array content of the object (first attribute)
    foreach((array) $user->getLists() as $attribute){
      $lists = $attribute;
      break;
    }

    foreach($lists as $name => $list){
      $choices[] = '<info>List</info> - <comment>'.$name.'</comment>';
    }

    $question = new ChoiceQuestion(
      'Select the list you want to download',
      $choices
    );

    $answer = $this->getHelper('question')->ask($this->input, $this->output, $question);
    $this->output->writeln('');
    $key = array_search($answer, $choices);

    return($key == 0 ? $user->getCollection():$user->getLists()[array_keys($lists)[--$key]]);
  }

  private function confirm($artwork){
    $this->output->writeln('');
    $this->output->writeln('<comment>'.$artwork->getTitle().($artwork->getTitle(Artwork::TITLE_ORIGINAL) ? ' ('.$artwork->getTitle(Artwork::TITLE_ORIGINAL).')':'').'</comment> ('.$artwork->getYear().') - <info>'.$artwork->getDirectors().'</info>');
    $this->output->writeln('Avec <info>'.implode('</info>, <info>', $artwork->getActors(true)).'</info>');
    $this->output->writeln('Genres : <info>'.implode('</info>, <info>', $artwork->getGenres(true)).'</info>');
    $this->output->writeln($artwork->getStoryline());

    $question = new ConfirmationQuestion('<question>Download this artwork ?</question> [<info>Y</info>/<comment>n</comment>] ', true);
    return $this->getHelper('question')->ask($this->input, $this->output, $question);
  }

  private function torrent($search){
    $this->output->writeln('');
    return $this->command('torrent', ['--soft' => true, 'search' => $search]);
  }

  private function save($artwork){
    $this->saved[] = $artwork->getId();
    file_put_contents(__SAVE_FILE__, json_encode($this->saved));
  }

}
