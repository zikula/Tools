<?php

namespace Zikula\Tools\Console\Command;

use PhpParser\ParserFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ControllerActionCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('module:controller_actions')
            ->setDescription('Adds "Action" suffix to all public controller methods in specified controller directory')
            ->addOption('dir', null, InputOption::VALUE_REQUIRED,
                'Target directory is mandatory - should be the Controller folder of a module e.g. dir=./Controller')
            ->addOption('force', null, InputOption::VALUE_NONE,
                'must require --force'
            )
            ->setHelp(<<<EOF
The <info>module:controller_actions</info> command refactors controller methods with Action suffix.

<info>zikula-tools module:controller_actions --dir=./Controller --force</info>
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

        $force = (bool)$input->getOption('force');

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

                $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP5);
                $traverser = new \PhpParser\NodeTraverser();
                $prettyPrinter = new \PhpParser\PrettyPrinter\Standard;
                $traverser->addVisitor(new Visitor\ControllerActionVisitor());
                $stmts = $parser->parse($code);
                $stmts = $traverser->traverse($stmts);

                $code = '<?php' . "\n" . $prettyPrinter->prettyPrint($stmts);
                file_put_contents($file->getRealPath(), $code);
            } catch (\PhpParser\Error $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");
            }
        }
        $output->writeln('<comment>WARNING: Code has been reformatted.

But the main changes have simply been add \'Action\' to the end
of public method declarations. Use a diff tool to revert any
unwanted formatting.</comment>');

        $output->writeln("<comment>Done.</comment>");
    }
}