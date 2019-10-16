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
        $outYamlText = "==================== For YAML =================\n"
            . "(" . $yamlFile . ")\n"
            . "services:\n"
            . "  _defaults:\n"
            . "    public: false\n"
            . "    autoconfigure: true\n"
            . "    autowire: true\n"
        ;
        $outDeprecText =
            "\n==================== For DEPREC =================\n"
            . "(./app/config/deprecated.yml)\n"
            . "#  " . $yamlFile . "\n";

        $outTestText =
            "\n==================== For TESTING =================\n"
            . "(./src/Meero/ShootBundle/Controller/User/HomepageController.php:indexAction)\n";

        $yamlData = Yaml::parseFile($yamlFile);
        if (!isset($yamlData['services'])) {
            fwrite(STDERR, "Could not find `services`.\n");
            return;
        }

        ksort($yamlData['services']);

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
            if (isset($sOpts['arguments'])) {
                foreach ($sOpts['arguments'] as $key => $a) {

                    if (strncmp($a, '%', 1) === 0 || strncmp($key, '$', 1) === 0 || strncmp($a, '@', 1) !== 0) {
                        if ($args == null) {
                            $args = "    arguments:\n";
                        }
                        if (strncmp($key, '$', 1) === 0) {
                            $args .= '      ' . $key . ': ' . $a . "\n";
                        } else if (strncmp($a, '%', 1) === 0 || strncmp($a, '@', 1) !== 0) {
                            $args .= '      ' . $a . "\n";
                        }
                    }
                }
            }
            if (isset($sOpts['tags'])) {
                $outYamlText .= "\n\n";
                $tags = json_encode($sOpts['tags']);
                $outYamlText .= "    detected tags:\n      - " . $tags . "\n";

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
        fputs($fileHandle, $outYamlText);
        fputs($fileHandle, $outDeprecText);
        fputs($fileHandle, $outTestText);
        fclose($fileHandle);
    }
}
