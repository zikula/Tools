<?php

namespace Zikula\Tools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class RestructureModuleCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('module:restructure')
            ->setDescription('Creates and moves structure')
            ->addOption('dir', null, InputOption::VALUE_REQUIRED,
                        'Target directory is mandatory - should be current directory e.g. --dir=.'
        )
            ->addOption('vendor', null, InputOption::VALUE_REQUIRED,
                        'Vendor mandatory'
        )
            ->addOption('module-name', null, InputOption::VALUE_REQUIRED,
                        'Module name mandatory - should be intended module name'
        )
            ->addOption('force', null, InputOption::VALUE_NONE,
                        'Force - without this, nothing will be done.'
        )
            ->setHelp(<<<EOF
The <info>module:restructure</info> command migrates resources</info>

<info>zikula-tools module:restructure --vendor=Acme --dir=. --module-name=WidgetModule --force</info>
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pwd = getcwd();
        $cwdArray = explode('/', $pwd);
        $currentDirectory = array_pop($cwdArray);
        $dir = $input->getOption('dir');
        if (!$dir) {
            $output->writeln("<error>ERROR: --dir= is required.</error>");
            exit(1);
        }

        $vendor = $input->getOption('vendor');
        if (!$vendor) {
            $output->writeln("<error>ERROR: --vendor= is required</error>");
            exit(1);
        }

        $moduleDir = $input->getOption('module-name');
        if (!$moduleDir) {
            $output->writeln("<error>ERROR: --module-name= is required</error>");
            exit(1);
        }

        if (!is_dir($dir)) {
            $output->writeln("<error>ERROR: $dir does not exist</error>");
            exit(1);
        }

        if (file_exists("$dir/{$vendor}{$currentDirectory}Module.php")) {
            $output->writeln('<error>Looks like this module has already been restructured.</error>');
            exit(1);
        }

        $force = (bool) $input->getOption('force');
        if (false === $force) {
            $output->writeln("<error>Will not proceed without --force - Make sure this command is run from INSIDE the module repository as GIT is required.</error>");
            exit(1);
        }
        chdir($dir);

        if (!is_dir($dir.'/Resources/public')) {
            if (mkdir($dir.'/Resources/public', 0755, true)) {
                $output->writeln("<info>Created $dir/Resources/public</info>");
            } else {
                $output->writeln("<error>Failed to create $dir/Resources/public</error>");
                chdir($pwd);

                return;
            }
        }

        if (is_dir($dir.'/style')) {
            `git mv $dir/style $dir/Resources/public/css`;
            $output->writeln("<info>moved $dir/style to $dir/Resources/public/css</info>");
        }

        if (is_dir($dir.'/javascript')) {
            `git mv $dir/javascript $dir/Resources/public/js`;
            $output->writeln("<info>moved $dir/javascript to $dir/Resources/public/js</info>");
        }

        if (is_dir($dir.'/images')) {
            `git mv $dir/images $dir/Resources/public`;
            $output->writeln("<info>moved $dir/images to $dir/Resources/public/images</info>");
        }

        if (is_dir($dir.'/docs')) {
            `git mv $dir/docs $dir/Resources/docs`;
            $output->writeln("<info>moved $dir/docs to $dir/Resources/public/docs</info>");
        }

        if (is_dir($dir.'/locale')) {
            `git mv $dir/locale $dir/Resources`;
            $output->writeln("<info>moved $dir/locale to $dir/Resources/public/locale</info>");
        }

        if (is_dir($dir.'/templates')) {
            `git mv $dir/templates $dir/Resources/views`;
            $output->writeln("<info>moved $dir/templates to $dir/Resources/public/views</info>");
        }

        if (is_dir($dir.'/lib/'.$currentDirectory)) {
            `mv $dir/lib/$currentDirectory/* $dir`;
            `git add --ignore-removal $dir/*`;

            $output->writeln("<info>moved PHP files from $dir/lib/$currentDirectory/* to $dir</info>");
            if (is_dir("$dir/lib/vendor")) {
                `git mv $dir/lib/vendor $dir`;
                $output->writeln("<comment>Vendors have been moved from $dir/lib/vendor into $dir/vendor/</comment>");
            }
            rmdir("$dir/lib/$currentDirectory");
            @rmdir("$dir/lib"); // there might be a vendor dir here so suppress warnings
        }

        `git mv $dir/Version.php $dir/{$moduleDir}Version.php`;
        $output->writeln("<comment>renamed Version.php to {$moduleDir}Version.php</comment>");
        `git mv $dir/Installer.php $dir/{$moduleDir}Installer.php`;
        $output->writeln("<comment>renamed Installer.php to {$moduleDir}Installer.php</comment>");

        // autocommit changes
        `git commit -a -m "[zikula-tools] Restructured to Core1.4/psr-4 module specification."`;
        $output->writeln("<comment>Committed</comment>");

        $output->writeln("<info>Done.
Todo tasks:

  - Update {$moduleDir}Version.php core_min to 1.4.0
  - If there are any old calls to {pageaddvar} specifying js/css paths, these must be updated.
</info>");
        $output->writeln("<comment>Committed. Please now run the module:ns command.</comment>");
        chdir($pwd);
    }
}