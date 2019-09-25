<?php

// src/Command/ParamsToEnvCommand.php

namespace Commands;

use \in_array;
use \is_array;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ParamsToEnvCommand extends Command
{
    protected static $defaultName = 'params2env';

    protected function configure()
    {
        $this
            ->setDescription('Read a .yml parameter file and outputs the \'parameters:\' to a .env file (or stdout)')
            ->addArgument('input', InputArgument::REQUIRED, 'The file to translate')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'The file to output to', 'php://stdout')
            ->addOption('exclusions', 'e', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The parameters to exclude (lowercase)', [])
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $yamlFile =   $input->getArgument('input');
        $envFile =    $input->getOption('output');
        $exclusions = $input->getOption('exclusions');

        $yamlData = Yaml::parseFile($yamlFile);
        $data = '';
        if (!isset($yamlData['parameters'])) {
            fwrite(STDERR, "Could not find `parameters`.\n");
            return;
        }
        foreach ($yamlData['parameters'] as $keyYml => $param) {
            $keyEnv = str_replace('.', '_', strtoupper($keyYml));
            if (!in_array($keyYml, $exclusions)
                && !in_array($keyEnv, $exclusions)) {
                if (is_array($param)) {
                    $param = json_encode($param);
                }
                $data .= sprintf("%s=%s\n", $keyEnv, $param);
            }
        }
        $fileHandle = fopen($envFile, 'w');
        if ($fileHandle === FALSE) {
            fwrite(STDERR, "Could not find open output file.\n");
            return;
        }
        fputs($fileHandle, $data);
        fclose($fileHandle);
    }
}
