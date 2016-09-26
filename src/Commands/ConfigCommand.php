<?php

namespace thcolin\TorrentDjinn\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Exception\LogicException;
use thcolin\TorrentDjinn\Djinn;

class ConfigCommand extends CommandAbstract{

  protected function configure(){
    $this
      ->setName('config')
      ->setDescription('Configure the djinn for your needs');
  }

  protected function execute(InputInterface $input, OutputInterface $output){
    $this->init($input, $output);
    $this->hello();

    do{
      $done = false;
      $answer = $this->config($this->djinn->config);

      switch($answer){
        case 'dest':
          $destination = $this->destination();
          $this->djinn->config['destination'] = $destination;
        break;

        case 'mode':
          $strict = $this->strict();
          $this->djinn->config['strict'] = $strict;
        break;

        case 'tks':
          $trackers = $this->trackers($this->djinn->config['trackers']);
          $this->djinn->config['trackers'] = $trackers;
        break;

        case 'done':
        default:
          $done = true;
        break;
      }

      $this->djinn->save();
    } while(!$done);
  }

  private function config($context){
    $question = new ChoiceQuestion(
      'Which parameter do you want to configure :',
      [
        'dest' => $context['destination'],
        'mode' => ($context['strict'] ? 'Strict':'Permissive'),
        'tks' => implode(',', array_keys($context['trackers'])),
        'done' => '<fg=yellow>done</>'
      ]
    );

    $this->output->writeln('');
    return $this->getHelper('question')->ask($this->input, $this->output, $question);
  }

  private function destination(){
    $this->output->writeln('');

    $this->output->writeln('<comment>Configurez le répertoire dans lequel les fichiers .torrent seront téléchargés</comment>');
    $question = new Question('<question>Indiquez le chemin complet du répertoire :</question> ');
    $question->setValidator(function($answer){
      if(!is_dir($answer)){
        throw new RuntimeException('Le répertoire "'.$answer.'" n\'existe pas !');
      }
    });

    return $this->getHelper('question')->ask($this->input, $this->output, $question);
  }

  private function strict(){
    $this->output->writeln('');
    $this->output->writeln('<comment>With the strict mode, you\'ll only see torrents with a correct scene release name</comment>');
    $question = new ConfirmationQuestion('<question>Enable strict mode ?</question> [<info>Y</info>/<comment>n</comment>] ', true);
    return $this->getHelper('question')->ask($this->input, $this->output, $question);
  }

  private function trackers($context){
    do{
      $this->output->writeln('');

      $choices = [];

      foreach($context as $key => $tracker){
        $choices[] = $key;
      }

      $choices['done'] = '<fg=yellow>done</>';

      $question = new ChoiceQuestion(
        'Which tracker do you want to configure :',
        $choices
      );

      $answer = $this->getHelper('question')->ask($this->input, $this->output, $question);

      switch($answer){
        case 'done':
          return $context;
        break;
        default:
          $key = strip_tags($choices[$answer]);
          $tracker = $this->tracker(array_merge($context[$key], ['tracker' => $key]));
          unset($tracker['tracker']);
          $context[$key] = $tracker;
        break;
      }
    } while(true);
  }

  private function tracker($context){
    do{
      $this->output->writeln('');

      $choices = [
        'user' => $context['username'],
        'pass' => str_repeat('*', strlen($context['password'])),
        'test' => '<fg=yellow>test</>',
        ($context['enabled'] ? 'disable':'enable') => '<fg='.($context['enabled'] ? 'red':'green').'>'.($context['enabled'] ? 'disable':'enable').'</>',
        'done' => '<fg=yellow>done</>'
      ];

      $question = new ChoiceQuestion(
        'Which parameter of <fg=green>"'.$context['tracker'].'"</> do you want to configure :',
        $choices
      );

      $answer = $this->getHelper('question')->ask($this->input, $this->output, $question);

      switch($answer){
        case 'user':
          $question = new Question('<question>Indiquez le nom d\'utilisateur :</question> ');
          $answer = $this->getHelper('question')->ask($this->input, $this->output, $question);
          $context['username'] = $answer;
        break;
        case 'pass':
          $question = new Question('<question>Indiquez le mot de passe :</question> ');
          $question->setHidden(true);
          $question->setHiddenFallback(false);
          $answer = $this->getHelper('question')->ask($this->input, $this->output, $question);
          $context['password'] = $answer;
        break;
        case 'test':
          $class = 'thcolin\TorrentDjinn\Trackers\\'.$context['tracker'];

          try{
            new $class($context);
            $this->output->writeln('<fg=green>Success !</>');
          } catch(LoginException $e){
            $this->output->writeln('<fg=red>Error : Vérifiez vos identifiants !</>');
          }

          sleep(1);
        break;
        case 'enable':
          $context['enabled'] = true;
        break;
        case 'disable':
          $context['enabled'] = false;
        break;
        case 'done':
          return $context;
        break;
      }
    } while(true);
  }

}
