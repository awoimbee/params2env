<?php

// src/Command/PrintEnvCommand.php

namespace Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;

class PrintEnvCommand extends Command
{
    protected static $defaultName = 'print_env';

    protected function configure()
    {
        $this->setDescription('Print env variables as read by symfonys Dotenv');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $a = new Dotenv();
        $a->loadEnv(__DIR__ . '/../.env');
        print_r($_ENV);
    }
}
