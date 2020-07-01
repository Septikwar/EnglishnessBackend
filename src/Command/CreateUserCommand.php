<?php


namespace App\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateUserCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the user')
            ->setName('app:create-user')
            ->setDescription('Creates a new user')
            ->setHelp('Help to create user');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('User '. $input->getArgument('username') .' created');
        $output->writeln('Test');

        return 1;
    }
}