<?php
// src/Command/ParamsToEnvCommand.php
namespace Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use \in_array;
use \is_array;

class ParamsToEnvCommand extends Command
{
    protected static $defaultName = 'params2env';

    protected function configure()
    {
        $this
            ->setDescription("TODO")
            ->addOption('input', 'i', InputOption::VALUE_OPTIONAL, 'The file to translate', 'php://stdin')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'The file to output to', 'php://stdout')
            ->addOption('exclusions', 'e', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The parameters to exclude (lowercase)', array())
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paramsFile = $input->getOption('input');
        $envFile = $input->getOption('output');
        $exclusions = $input->getOption('exclusions');

        $yamlData = Yaml::parseFile($paramsFile);
        $data = '';
        foreach(($yamlData['parameters']) as $keyYml => $param) {
            $keyEnv = str_replace('.', '_', strtoupper($keyYml));
            if (
                !in_array($keyYml, $exclusions)
                && !in_array($keyEnv, $exclusions)
            ) {
                if (is_array($param)) {
                    $param = json_encode($param);
                }
                $data .= sprintf("%s=%s\n", $keyEnv, $param);
            }
        }
        $fileHandle = fopen($envFile, 'w');
        fputs($fileHandle, $data);
        fclose($fileHandle);
    }
}
