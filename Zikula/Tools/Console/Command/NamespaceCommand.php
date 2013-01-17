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

<info>zikula-tools module:ns --dir=modules/MyModule --module=MyModule</info>
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

        $finder = new Finder();
        $finder->in($dir)
            ->files()
            ->depth('< 3')
            ->exclude('vendor')
            ->notName('tables.php')
            ->name('*.php');
        foreach ($finder as $file) {
            /** @var \SplFileInfo $file */
            $output->writeln("<info>Processing {$file->getRealPath()}</info>");
            try {
                $parser = new \PHPParser_Parser(new \PHPParser_Lexer());
                $importTraverser = new \PHPParser_NodeTraverser();
                $traverser = new \PHPParser_NodeTraverser();
                $prettyPrinter = new Helper\PrettyPrinter();

                $importTraverser->addVisitor($oc = new Visitor\ObjectVisitor());

                // $traverser->addVisitor(new \PHPParser_NodeVisitor_NameResolver());
                $traverser->addVisitor($nsc = new Visitor\NamespaceVisitor());
                $nsc->setImports($oc->getImports());

                $code = file_get_contents($file->getRealPath());

                $stmts = $parser->parse($code);
                $stmts = $importTraverser->traverse($stmts);

                $nsc->setImports($oc->getImports());
                $stmts = $traverser->traverse($stmts);

                $code = '<?php'.$prettyPrinter->prettyPrint($stmts);
                $s = end($stmts);
                $output->writeln("<info>Writing {$file->getRealPath()}</info>");
                file_put_contents($file->getRealPath(), $code);
                $pos = strrpos($file->getRealPath(), DIRECTORY_SEPARATOR);
                $fileName = substr($file->getRealPath(), 0, $pos).DIRECTORY_SEPARATOR.$s->name;
                if ($file->getRealPath() !== "{$fileName}.php") {
                    `git mv {$file->getRealPath()} {$fileName}.php`;
                    $output->writeln("<comment>Renamed {$file->getRealPath()} to {$fileName}.php</comment>");
                }
            } catch (\PHPParser_Error $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");
            }
        }

        // write module file required for Kernel
        $helper = new Helper\CreateModuleHelper();
        file_put_contents("$dir/{$moduleDir}Module.php", $helper->getTemplate($moduleDir));
        `git add {$moduleDir}Module.php`;

        $output->writeln('<comment>WARNING: Code has been reformatted.

Some files have been renamed and added to GIT. Please git status/diff and commit.

But the main changes have simply been to the class envelope so you
can use a diff tool to revert the class innards.</comment>');
    }
}
