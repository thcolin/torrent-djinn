<?php

namespace thcolin\TorrentDjinn\Commands;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use thcolin\TorrentDjinn\Djinn;
use thcolin\SensCritiqueAPI\Client;

abstract class CommandAbstract extends ContainerAwareCommand{

  protected function init(InputInterface $input, OutputInterface $output){
    $this->input = $input;
    $this->output = $output;

    $this->djinn = new Djinn(__CONFIG_FILE__);
    $this->senscritique = new Client();
    $this->saved = json_decode(file_get_contents(__SAVE_FILE__), true);
  }

  protected function command($name, $args = [], $options = []){
    $command = $this->getApplication()->find($name);
    $inputs = new ArrayInput(array_merge(['command' => $name], $args, $options));
    return $command->run($inputs, $this->output);
  }

  protected function hello(){
    switch($this->getName()){
      case 'torrent':
        $talent = 'You requested my talents for searching torrents on : <fg=cyan>Trackers</>';
      break;
      case 'senscritique':
        $talent = 'You requested my talents for searching artworks on : <fg=cyan>SensCritique</>';
      break;
      default:
        $talent = 'You requested my talents to <fg=green>configure</> myself !';
      break;
    }

    $this->output->writeln("
                 <fg=red>:::::</>
               <fg=red>::::</><fg=blue>?</><fg=red>::::</>                Hello fellow !
               <fg=red>:::</><fg=blue>???</><fg=red>:::,</>
                <fg=red>:</><fg=blue>?????</><fg=blue>:</>                 ".$talent."
                <fg=blue>,?????,</>
                <fg=blue>,?????,</>                 By the way, I'm configured like this :
           <fg=blue>:???=~,,,,,~=???=</>              -- The torrents will be downloaded to : <fg=yellow>".$this->djinn->getDestination()."</>
       <fg=blue>+????++++++????+===+?????</>          -- And I can search on all these trackers : <fg=magenta>".implode(', ', array_keys($this->djinn->getTrackers()))."</>
     <fg=blue>????????++++?????????????????</>
    <fg=blue>??????????????=++??????????????</>
     <fg=blue>???????????==???=????????????</>
        <fg=blue>=???===+???????===+???~</>
           <fg=blue>+???????????????++:</>
            <fg=blue>???????????????+</>
             <fg=blue>:????????????,</>
             <fg=blue>~:??????????</>
            <fg=red>::???+??????</>
         <fg=red>~::????????:~</>
        <fg=red>,+????+::~,</>
      <fg=blue>:+?????~</>
    <fg=blue>+???~???+</>
     <fg=blue>???+</>
    ");
  }

  protected function resume($content, $ln = true){
    $size = (is_array($content['results']) ? count($content['results']):$content['results']->length());
    $this->output->writeln('<'.($size ? 'info':'error').">I've found ".$size.' results for your search :</> <fg=yellow>"'.$content['search'].'"</>');

    if(isset($content['filters']['trackers'])){
      $this->output->writeln("The results are from : <fg=cyan>".implode(', ', $content['filters']['trackers']).'</>');
    }

    $this->output->writeln("I've ordered the results by : <fg=magenta>".$content['order'].'</>');

    if(isset($content['policy'])){
      switch($content['policy']){
        case 'flexible':
          $policy = 'you see all the torrents';
        break;
        case 'moderate':
          $policy = 'you only see torrents with a correct scene release name';
        break;
        case 'strict':
          $policy = 'you only see quality torrents, and those too far from your search are ignored';
        break;
      }

      $this->output->writeln("And I've applied the <fg=yellow>".$content['policy']."</> policy : <fg=yellow>".$policy.'</>');
    }

    if(count($content['filters'])){
      $this->output->writeln('With the filters : '.implode(', ', array_map(function($v, $k){ return '<fg=green>--'.$k.'='.(is_array($v) ? implode(',', $v):$v).'</>'; }, $content['filters'], array_keys($content['filters']))));
    }

    if($ln){
      $this->output->writeln('');
    }
  }

  protected function search(){
    $this->output->writeln('');
    $answer = $this->getHelper('question')->ask($this->input, $this->output, new Question('<question>What are you searching ?</question> '));
    $this->output->writeln('');
    return $answer;
  }

  protected function getFilters($keep = false){
    $filters = [];

    foreach($this->input->getOptions() as $option => $value){
      if(substr($option, 0, 6) == 'filter' && $value){
        $key = ($keep ? '--filter-':'').substr($option, 7);
        $filters[$key] = $this->getFilter($option);
      }
    }

    return $filters;
  }

  protected function getFilter($key){
    $value = $this->input->getOption($key);

    if(is_array($value)){
      $array = [];
      foreach($value as $string){
        $array = array_merge($array, explode(',', $string));
      }
      $value = $array;
    }

    return $value;
  }
}
