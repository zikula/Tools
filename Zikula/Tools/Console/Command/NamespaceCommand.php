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
                        'Target directory is mandatory - should be current directory e.g. dir=.'
        )
            ->addOption('vendor', null, InputOption::VALUE_REQUIRED,
                        'Vendor name mandatory'
        )
            ->addOption('module-name', null, InputOption::VALUE_REQUIRED,
                        'Module name mandatory - should be new module name'
        )
            ->setHelp(<<<EOF
The <info>module:ns</info> command converts to namespaced classes</info>

<info>zikula-tools module:ns --dir=. --vendor=Acme --module-name=WidgetModule</info>
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

        $vendor = $input->getOption('vendor');
        if (!$vendor) {
            $output->writeln("<error>ERROR: --vendor= is required</error>");
            exit(1);
        }

        $moduleDir = $input->getOption('module-name');
        if (!$moduleDir) {
            $output->writeln("<error>ERROR: --module= is required</error>");
            exit(1);
        }
        if (strcmp(substr($moduleDir, -6), 'Module') !== 0) {
            $moduleDir .= 'Module';
        }

        $finder = new Finder();
        $finder->in($dir)
            ->files()
            ->depth('< 3')
            ->exclude('vendor')
            ->notName('tables.php')
            ->notName('bootstrap.php')
            ->name('*.php');
        foreach ($finder as $file) {
            /** @var \SplFileInfo $file */
            $output->writeln("<info>Processing {$file->getRealPath()}</info>");
            try {
                $parser = new \PHPParser_Parser(new \PHPParser_Lexer());
                $importTraverser = new \PHPParser_NodeTraverser();
                $traverser = new \PHPParser_NodeTraverser();
                $prettyPrinter = new \PHPParserPSR2_Printer();

                $importTraverser->addVisitor($oc = new Visitor\ObjectVisitor());

                $traverser->addVisitor($nsc = new Visitor\NamespaceVisitor());
                $nsc->setVendor($vendor);
                $nsc->setModuleDirectory($moduleDir);
                $nsc->setImports($oc->getImports());

                $code = file_get_contents($file->getRealPath());

                $stmts = $parser->parse($code);
                $stmts = $importTraverser->traverse($stmts);

                $nsc->setImports($oc->getImports());
                $stmts = $traverser->traverse($stmts);

                $code = '<?php'."\r\n".$prettyPrinter->prettyPrint($stmts);
                $s = end($stmts);
                $output->writeln("<info>Writing {$file->getRealPath()}</info>");
                file_put_contents($file->getRealPath(), $code);
                $pos = strrpos($file->getRealPath(), DIRECTORY_SEPARATOR);
                $fileName = substr($file->getRealPath(), 0, $pos).DIRECTORY_SEPARATOR.$s->name;
                $isInstaller = substr($file->getFilename(), -19) == 'ModuleInstaller.php';
                $isVersion = substr($file->getFilename(), -17) == 'ModuleVersion.php';
                if (($file->getRealPath() !== "{$fileName}.php") && !$isInstaller && !$isVersion) {
                    `git mv {$file->getRealPath()} {$fileName}.php`;
                    $output->writeln("<comment>Renamed {$file->getRealPath()} to {$fileName}.php</comment>");
                }
            } catch (\PHPParser_Error $e) {
                $output->writeln("<error>{$e->getMessage()}</error>");
            }
        }

        // write module file required for Kernel
        $helper = new Helper\CreateModuleHelper();
        file_put_contents("$dir/{$vendor}{$moduleDir}.php", $helper->getTemplate($vendor, $moduleDir));
        `git add {$vendor}{$moduleDir}.php`;

        // write composer.json file required for Kernel
        $helper = new Helper\CreateComposerHelper();
        file_put_contents("$dir/composer.json", $helper->getTemplate($vendor, $moduleDir, 'Module'));
        `git add composer.json`;

        $output->writeln('<comment>WARNING: Code has been reformatted and moved.

Some files have been renamed and added to GIT. Please git status/diff and commit.

But the main changes have simply been to the class envelope so you
can use a diff tool to revert the class innards.</comment>');
    }
}
