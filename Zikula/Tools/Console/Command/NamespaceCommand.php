<?php

namespace Zikula\Tools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class NamespaceCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('module:ns')
            ->setDescription('Namespaces module')
            ->addOption('dir', null, InputOption::VALUE_REQUIRED,
                        'Target directory is mandatory - should be module directory'
        )
            ->addOption('module', null, InputOption::VALUE_REQUIRED,
                        'Module name mandatory - should be module directory name'
        )
            ->setHelp(<<<EOF
The <info>module:ns</info> command migrates resources</info>

<info>refactor module:ms --dir=modules/MyModule --module=MyModule</info>
EOF
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = $input->getOption('dir');
        if (!$dir) {
            $output->writeln("<error>ERROR: --dir= is required</error>");
            exit(1);
        }
        $moduleDir = $input->getOption('module');
        if (!$moduleDir) {
            $output->writeln("<error>ERROR: --module= is required</error>");
            exit(1);
        }

        $fileName = __DIR__."/src/system/Permissions/Controller/Admin.php";

        $parser = new \PHPParser_Parser(new \PHPParser_Lexer());
        $importTraverser = new \PHPParser_NodeTraverser();
        $traverser = new \PHPParser_NodeTraverser();
        $prettyPrinter = new \PHPParser_PrettyPrinter_Zend();

        $importTraverser->addVisitor($oc = new Visitor\ObjectVisitor());

        // $traverser->addVisitor(new \PHPParser_NodeVisitor_NameResolver());
        $traverser->addVisitor($nsc = new Visitor\NamespaceVisitor());

        $finder = new Finder();
        $finder->in($dir)
            ->files()
            ->depth(0)
            ->name('*.php');
        foreach ($finder as $file) {
            $output->writeln("<info>Processing {$file->getRealPath()}</info>");
            try {
                $code = file_get_contents($file->getRealPath());

                $stmts = $parser->parse($code);
                $stmts = $importTraverser->traverse($stmts);

                $nsc->setImports($oc->getImports());
                $stmts = $traverser->traverse($stmts);

                $code = '<?php '."\n".$prettyPrinter->prettyPrint($stmts);
                file_put_contents($file->getRealPath(), $code);
            } catch (\PHPParser_Error $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");
            }
        }
    }
}
