<?php
namespace Zikula\Tools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zikula\Tools\Util\Compiler;

class CompileCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('compile')
            ->setDescription('Compiles this as a phar file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $compiler = new Compiler();
        $compiler->compile();
    }
}