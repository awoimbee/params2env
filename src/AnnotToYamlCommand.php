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
        $fileContents = file_get_contents($filePath);

        // PCRE crashes because the default recursion limit is WAY too high (100 000)
        //   https://stackoverflow.com/questions/7620910/regexp-in-preg-match-function-returning-browser-error#answer-7627962
        ini_set("pcre.recursion_limit", "524");

        // Capture from '@Route' to '*/'
        preg_match_all(
            '/@Route(\s|.)*?(\*\/)/',
            $fileContents,
            $routeMatches,
            PREG_OFFSET_CAPTURE
        );
        // Discard capture groups and wathever
        $routeMatches = $routeMatches[0];
        if ($routeMatches !== array())
            printf(
                "\e[0;36m%s\n%s\e[0m\n",
                str_repeat("=", strlen($filePath)),
                $filePath);
        foreach($routeMatches as $routeMatch) {
            $capture = substr($fileContents, $routeMatch[1], strlen($routeMatch[0]));
            // Capture url, name, methods, requirements, default from symfony 4 route
            preg_match(
                '/@Route\(([\s*]*)("(?<url>(.)*?)")?,?([\s*]*)((name="(?<name>(.)*?)"|methods={(?<methods>((.|\s))*?)}|requirements={(?<requirements>((.|\s))*?)}|defaults={(?<defaults>((.|\s))*?)}),?([\s*]*))*/',
                $capture,
                $routeParams
            );

            printf(
                "Route:\n%s\n\nurl: %s\nname: %s\nmethods: %s\n%s\n",
                $capture,
                $routeParams['url'],
                $routeParams['name'],
                $routeParams['methods'],
                "-------------------------------------------\n"
            );
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
    }
}
