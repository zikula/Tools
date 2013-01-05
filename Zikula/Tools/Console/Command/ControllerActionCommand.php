<?php

namespace Zikula\Tools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ControllerActionCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('refactor:controller_actions')
            ->setDescription('Adds "Action" suffix to all public controller methods in specified controller directory')
            ->addOption('dir', null, InputOption::VALUE_REQUIRED,
                        'Target directory is mandatory - should be the Controller folder of a module')
            ->addOption('force', null, InputOption::VALUE_NONE,
                        'Target directory is mandatory - should be the Controller folder of a module'
        )
            ->setHelp(<<<EOF
The <info>refactor:controller_actions</info> command refactors controller methods with Action suffix.

<info>refactor refactor:controller_actions --dir=modules/MyModule/Controller</info>
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = $input->getOption('dir');
        if (!$dir) {
            $output->writeln("<error>ERROR: --dir= is required</error>");
            exit(1);
        }

        if (!is_dir($dir)) {
            $output->writeln("<error>$dir does not exist</error>");
            exit(1);
        }

        $force = (bool) $input->getOption('force');

        $parser = new \PHPParser_Parser(new \PHPParser_Lexer());
        $traverser = new \PHPParser_NodeTraverser();
        $prettyPrinter = new Helper\PrettyPrinter();
        $traverser->addVisitor(new Visitor\ControllerActionVisitor());

        $finder = new Finder();
        $finder->in($dir)
            ->files()
            ->depth(0)
            ->name('*.php');
        foreach ($finder as $file) {
            $output->writeln("<info>Processing {$file->getRealPath()}</info>");
            if (false === $force) {
                $output->writeln("<comment>Skipped {$file->getRealPath()} use --force to process</comment>");
                return;
            }
            try {
                $code = file_get_contents($file->getRealPath());

                $stmts = $parser->parse($code);
                $stmts = $traverser->traverse($stmts);

                $code = '<?php '."\n".$prettyPrinter->prettyPrint($stmts);
                file_put_contents($file->getRealPath(), $code);
            } catch (\PHPParser_Error $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");
            }
        }

        $output->writeln("<comment>Done.</comment>");
    }
}