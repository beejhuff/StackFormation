<?php

namespace StackFormation\Command;

use Aws\CloudFormation\Exception\CloudFormationException;
use StackFormation\Helper;
use StackFormation\StackManager;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\ChoiceQuestion;

abstract class AbstractCommand extends Command
{

    /**
     * @var StackManager
     */
    protected $stackManager;

    public function __construct($name = null)
    {
        $this->stackManager = new StackManager();

        parent::__construct($name);
    }


    protected function interactAskForConfigStack(InputInterface $input, OutputInterface $output)
    {
        $stack = $input->getArgument('stack');
        if (empty($stack)) {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion('Please select a stack', $this->stackManager->getConfig()->getStackLabels());

            $question->setErrorMessage('Stack %s is invalid.');

            $stack = $helper->ask($input, $output, $question);
            $output->writeln('Selected Stack: ' . $stack);

            list($stackName) = explode(' ', $stack);
            $input->setArgument('stack', $stackName);
        }

        return $stack;
    }

    protected function getRemoteStacks($statusFilter='/.*/')
    {
        $stacks = [];
        foreach ($this->stackManager->getStacksFromApi() as $stackName => $info) {
            if (preg_match($statusFilter, $info['Status'])) {
                $stacks[] = $stackName;
            }
        }
        return $stacks;
    }

    public function interactAskForLiveStack(InputInterface $input, OutputInterface $output, $remoteStackFilter='/.*/')
    {
        $stack = $input->getArgument('stack');
        if (empty($stack)) {
            $choices = $this->getRemoteStacks($remoteStackFilter);

            if (count($choices) == 0) {
                throw new \Exception('No valid stacks found.');
            }
            if (count($choices) == 1) {
                $stack = end($choices);
            } else {

                $helper = $this->getHelper('question');
                $question = new ChoiceQuestion('Please select a stack', $choices);

                $question->setErrorMessage('Stack %s is invalid.');

                $stack = $helper->ask($input, $output, $question);
            }
            $output->writeln('Selected Stack: ' . $stack);

            $input->setArgument('stack', $stack);
        }

        return $stack;
    }

    protected function extractMessage(CloudFormationException $exception)
    {
        $message = (string)$exception->getResponse()->getBody();
        $xml = simplexml_load_string($message);
        if ($xml !== false && $xml->Error->Message) {
            return $xml->Error->Message;
        }

        return $exception->getMessage();
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        try {
            return parent::run($input, $output);
        } catch (CloudFormationException $exception) {
            $message = $this->extractMessage($exception);
            if (strpos($message, 'No updates are to be performed.') !== false) {
                $output->writeln('No updates are to be performed.');

                return 0; // exit code
            } else {
                $formatter = new FormatterHelper();
                $formattedBlock = $formatter->formatBlock(['[CloudFormationException]', '', $message], 'error', true);
                $output->writeln("\n\n$formattedBlock\n\n");

                if ($output->isVerbose()) {
                    $output->writeln($exception->getTraceAsString());
                }

                return 1; // exit code
            }
        }
    }
}
