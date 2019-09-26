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

class AnnotToYamlCommand extends Command
{
    protected static $defaultName = 'annot2yaml';

    protected function configure()
    {
        $this
            ->setDescription('TODO')
            ->addArgument('search_dir', InputArgument::REQUIRED, 'TODO')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'TODO')
            ;
    }

    /* Puts the results in $this->files */
    private function scanDotPhpRecur(string $rootDir)
    {
        $children = scandir($rootDir);
        array_shift($children);array_shift($children); //yolo (remove '.' & '..')
        foreach($children as $child) {
            $child = $rootDir . '/' . $child;
            if (is_dir($child)) {
                $this->scanDotPhpRecur($child);
            }
            else if (substr_compare($child, '.php', -4) === 0) {
                $this->files[] = $child;
            }
        }
    }

    private function extractAnnot(string $filePath)
    {
        // $annotation = preg_grep(
        //     '/\/[*]+$\s[^\/]*?@Route\(.*?\)$.*?\*\//s',
        //     array($filePath)
        // );
        echo file_get_contents($filePath) . "\n";

        $annotation = preg_grep(
            '/\/[*]+$\s[^\/]*?@Route\(.*?\)$.*?\*\//s',
            array(file_get_contents($filePath))
        );

        if ($annotation !== []) {
            print_r($annotation);
            print_r("\n\n");
        }

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $searchDir = $input->getArgument('search_dir');
        $outputFile = $input->getOption('output');

        $this->scanDotPhpRecur($searchDir);
        foreach($this->files as $f) {
            $this->extractAnnot($f);
        }
        // print_r($this->files);
    }
}
