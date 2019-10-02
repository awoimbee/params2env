<?php

// src/Command/AnnotToYamlCommand.php

namespace Commands;

use \in_array;
use \is_array;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ServiceRegCleanerCommand extends Command
{
    protected static $defaultName = 'serviceRegCleaner';

    protected function configure()
    {
        $this
            ->setDescription('Read a .yml parameter file and outputs the \'parameters:\' to a .env file (or stdout)')
            ->addArgument('input', InputArgument::REQUIRED, 'The file to translate')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'The file to output to', 'php://stdout')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $yamlFile =   $input->getArgument('input');
        $outFile =    $input->getOption('output');
        $outYamlText = "To put back in the original .yml:\n\n"
            . "services:\n"
            . "  _defaults:\n"
            . "    public: false\n"
            . "    autoconfigure: true\n"
            . "    autowire: true\n"
        ;
        $outDeprecText = "To put in ./app/config/deprecated.yml\n\n";
        $outTestText = "To put in ./src/Meero/ShootBundle/Controller/User/HomepageController.php:indexAction";

        $yamlData = Yaml::parseFile($yamlFile);
        if (!isset($yamlData['services'])) {
            fwrite(STDERR, "Could not find `parameters`.\n");
            return;
        }

        unset($yamlData['services']['_defaults']);
        foreach($yamlData['services'] as $sName => $sOpts) {
            $outTestText .= sprintf("dump(\$this->get('%s'));\n", $sName);
            if (!isset($sOpts['class'])) { // no ointed thing stuff thing // strncmp($sName, "Meero", 5)
                $outYamlText .= sprintf("  %s:", $sName);
            }
            else {
                $outYamlText .= '  ' . $sOpts['class'] . ':';
                $outDeprecText .= sprintf(
                    "  %s:\n    alias: %s\n    public: true\n",
                    $sName,
                    $sOpts['class']
                );
            }

            $args = null;
            foreach ($sOpts['arguments'] as $key => $a) {
                // $outYamlText .= $key . "\n\n";
                if (strncmp($a, '%', 1) === 0 || strncmp($key, '$', 1) === 0) {
                    if ($args == null) {
                        $args = "    arguments: THEY ARE FUCKED, CHECKED\n";
                    }
                    if (strncmp($a, '%', 1) === 0)
                        $args .= '    ' . $a . "\n";
                    else if (strncmp($key, '$', 1) === 0)
                        $args .=  '      ' . $key . ': ' . $a . "\n";
                }
            }
            if ($args != null) {
                $outYamlText .= "\n" . $args;
            } else {
                $outYamlText .= " ~\n";
            }


        }

        // Save
        $fileHandle = fopen($outFile, 'w');
        if ($fileHandle === FALSE) {
            fwrite(STDERR, "Could not find open output file.\n");
            return;
        }
        fputs($fileHandle, "====================\nFor YAML\n=================\n");
        fputs($fileHandle, $outYamlText);
        fputs($fileHandle, "\n====================\nFor DEPREC\n=================\n");
        fputs($fileHandle, $outDeprecText);
        fputs($fileHandle, "\n====================\nFor TESTING\n=================\n");
        fputs($fileHandle, $outTestText);
        fclose($fileHandle);
    }
}
