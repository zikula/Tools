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
            ->setName('module:controller_actions')
            ->setDescription('Adds "Action" suffix to all public controller methods in specified controller directory')
            ->addOption('dir', null, InputOption::VALUE_REQUIRED,
                        'Target directory is mandatory - should be the Controller folder of a module'
        )
            ->setHelp(<<<EOF
The <info>module:controller_actions</info> command refactors controller methods with Action suffix.

<info>refactor module:controller_actions --dir=modules/MyModule/Controller</info>
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

        $parser = new \PHPParser_Parser(new \PHPParser_Lexer());
        $traverser = new \PHPParser_NodeTraverser();
        $prettyPrinter = new \PHPParser_PrettyPrinter_Zend();
        $traverser->addVisitor(new Visitor\ControllerActionVisitor());  // if controller only

        $finder = new Finder();
        $finder->in($dir)
            ->files()
            ->depth(0)
            ->name('*.php');
        foreach ($finder as $file) {
            echo 'Processing '.$file->getRealPath()."\n";



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
            $content = file_get_contents($file->getRealPath());
            $content = preg_replace('/public function (\w+)\(/', 'public function $1Action(', $content);
            file_put_contents($file->getRealPath(), $content);
        }

        $output->writeln("<comment>Done.

Remember to update Version.php core_min to 1.3.6</comment>");
    }
}