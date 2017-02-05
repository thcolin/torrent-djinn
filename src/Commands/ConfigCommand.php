<?php

namespace thcolin\TorrentDjinn\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use thcolin\TorrentDjinn\Djinn;
use thcolin\TorrentDjinn\Config;
use thcolin\TorrentDjinn\Exceptions\LoginException;
use RuntimeException;
use Exception;

class ConfigCommand extends CommandAbstract{

  protected function configure(){
    $this
      ->setName('config')
      ->setDescription('Configure the djinn for your needs');
  }

  protected function execute(InputInterface $input, OutputInterface $output){
    $this->init($input, $output);
    $this->hello();

    $this->configs = [
      'default' => Config::unserialize(__CONFIG_DEFAULT_FILE__)
    ];

    // get current config, or set default
    try{
      $this->configs['current'] = Config::unserialize(__CONFIG_FILE__);
    } catch(Exception $e){
      $this->configs['current'] = $this->configs['default'];
    }

    // add unimplemented trackers in current config
    if(count($this->configs['current']->getTrackers(true)) < count($this->configs['default']->getTrackers(true))){
      foreach($this->configs['default']->getTrackers(true) as $key => $tracker){
        if(!isset($this->configs['current']->getTrackers(true)[$key])){
          $this->configs['current']->addTracker($key, [
            'enable' => $tracker->isEnable(),
            'credentials' => $tracker->getCredentials()
          ]);
        }
      }
    }

    do{
      $done = false;
      $answer = $this->config([
        'destination' => $this->configs['current']->getDestination(),
        'trackers' => implode(',', array_keys($this->configs['current']->getTrackers(true)))
      ]);

      switch($answer){
        case 'dest':
          $this->destination();
        break;

        case 'tks':
          $this->trackers();
        break;

        case 'clean':
          $this->clean();
        break;

        case 'done':
        default:
          $done = true;
        break;
      }
    } while(!$done);
  }

  private function config($context){
    $question = new ChoiceQuestion(
      'Which parameter do you want to configure :',
      [
        'dest' => $context['destination'],
        'tks' => $context['trackers'],
        'clean' => '<fg=yellow>clean</>',
        'done' => '<fg=yellow>done</>'
      ]
    );

    $this->output->writeln('');
    return $this->getHelper('question')->ask($this->input, $this->output, $question);
  }

  private function clean(){
    $question = new ConfirmationQuestion('<question>Clean all artworks saved as downloaded ?</question> [<info>y</info>/<comment>N</comment>] ', false);
    $answer = $this->getHelper('question')->ask($this->input, $this->output, $question);

    if($answer){
      $this->output->writeln('<fg=green>Confirmed</>');
      file_put_contents(__SAVE_FILE__, json_encode([]));
    } else{
      $this->output->writeln('<fg=red>Aborted</>');
    }

    $this->output->writeln('');
  }

  private function destination(){
    $this->output->writeln('');

    $this->output->writeln('<comment>Configurez le répertoire dans lequel les fichiers .torrent seront téléchargés</comment>');
    $question = new Question('<question>Indiquez le chemin complet du répertoire :</question> ');
    $question->setValidator(function($answer){
      try{
        $this->configs['current']->setDestination($answer);
      } catch(Exception $e){
        throw new RuntimeException($e->getMessage());
      }
    });

    $this->getHelper('question')->ask($this->input, $this->output, $question);
    $this->configs['current']->save(__CONFIG_FILE__);
  }

  private function trackers(){
    do{
      $this->output->writeln('');

      $choices = [];

      foreach($this->configs['current']->getTrackers(true) as $key => $tracker){
        $choices[$key] = $key;
      }

      $choices['done'] = '<fg=yellow>done</>';

      $question = new ChoiceQuestion(
        'Which tracker do you want to configure :',
        $choices
      );

      $answer = $this->getHelper('question')->ask($this->input, $this->output, $question);

      switch($answer){
        case 'done':
          return;
        break;
        default:
          $key = strip_tags($choices[$answer]);
          $this->tracker($key);
        break;
      }

      $this->configs['current']->save(__CONFIG_FILE__);
    } while(true);
  }

  private function tracker($key){
    do{
      $this->output->writeln('');

      $tracker = $this->configs['current']->getTrackers(true)[$key];

      $choices = [];

      foreach($tracker->getCredentials() as $option => $credential){
        if(in_array($option, ['password', 'secret'])){
          $value = str_repeat('*', strlen($credential));
        } else{
          $value = $credential;
        }

        $choices[$option] = $value;
      }

      $choices['test'] = '<fg=yellow>test</>';
      $choices[($tracker->isEnable() ? 'disable':'enable')] = '<fg='.($tracker->isEnable() ? 'red':'green').'>'.($tracker->isEnable() ? 'disable':'enable').'</>';
      $choices['done'] = '<fg=yellow>done</>';

      $question = new ChoiceQuestion(
        'Which parameter of <fg=green>"'.$key.'"</> do you want to configure :',
        $choices
      );

      $option = $this->getHelper('question')->ask($this->input, $this->output, $question);

      switch($option){
        case 'test':
          $class = 'thcolin\TorrentDjinn\Trackers\\'.$key;

          try{
            $tracker->test();
            $this->output->writeln('<fg=green>Success !</>');
          } catch(LoginException $e){
            $this->output->writeln('<fg=red>Erreur : Vérifiez vos identifiants !</>');
          }

          sleep(1);
        break;
        case 'enable':
          $tracker->setEnable(true);
        break;
        case 'disable':
          $tracker->setEnable(false);
        break;
        case 'done':
          return;
        break;
        // others values are 'credentials'
        default:
          $question = new Question('<question>Indiquez la clé de credential "'.$option.'" :</question> ');

          if(in_array($option, ['password', 'secret'])){
            $question->setHidden(true);
            $question->setHiddenFallback(false);
          }

          $answer = $this->getHelper('question')->ask($this->input, $this->output, $question);

          $credentials = $tracker->getCredentials();
          $credentials[$option] = $answer;
          $tracker->setCredentials($credentials);
        break;
      }

      $this->configs['current']->save(__CONFIG_FILE__);
    } while(true);
  }

}
