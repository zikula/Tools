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

//        $importTraverser->addVisitor(new \PHPParser_NodeVisitor_NameResolver());
        $importTraverser->addVisitor($oc = new Visitor\ObjectVisitor());

//        $traverser->addVisitor(new \PHPParser_NodeVisitor_NameResolver());
        $traverser->addVisitor($nsc = new Visitor\NamespaceVisitor());
//        $traverser->addVisitor(new Visitor\ControllerActionVisitor());  // if controller only


        try {
            $code = file_get_contents($fileName);

            $stmts = $parser->parse($code);
//            var_dump($stmts);

            // traverse
            $stmts = $importTraverser->traverse($stmts);
            $nsc->setImports($oc->getImports());
            $stmts = $traverser->traverse($stmts);

            // pretty print
            $code = '<?php '.$prettyPrinter->prettyPrint($stmts);
            echo "<pre>$code</pre>";
            // write the converted file to the target directory
//                file_put_contents(
//                    substr_replace($file->getPathname(), "$dir/out", 0, strlen($dir)),
//                    $code
//                );
        } catch (\PHPParser_Error $e) {
            echo 'Parse Error: ', $e->getMessage();
        }
    }
}
