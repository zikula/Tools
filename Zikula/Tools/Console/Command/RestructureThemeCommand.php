<?php

namespace Zikula\Tools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class RestructureThemeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('theme:restructure')
            ->setDescription('Creates and moves structure')
            ->addOption('dir', null, InputOption::VALUE_REQUIRED,
                        'Target directory is mandatory - should be theme directory'
        )
            ->addOption('module-name', null, InputOption::VALUE_REQUIRED,
                        'Theme name mandatory - should be theme directory name'
        )
            ->addOption('force', null, InputOption::VALUE_NONE,
                        'Force - without this, nothing will be done.'
        )
            ->setHelp(<<<EOF
The <info>theme:restructure</info> command migrates resources</info>

<info>zikula-tools theme:restructure --dir=theme/MyTheme --theme=MyTheme</info>
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pwd = getcwd();
        $dir = $input->getOption('dir');
        if (!$dir) {
            $output->writeln("<error>ERROR: --dir= is required.</error>");
            exit(1);
        }
        $themeDir = $input->getOption('theme-name');
        if (!$themeDir) {
            $output->writeln("<error>ERROR: --theme-name= is required</error>");
            exit(1);
        }

        if (!is_dir($dir)) {
            $output->writeln("<error>ERROR: $dir does not exist</error>");
            exit(1);
        }

        $force = (bool) $input->getOption('force');
        if (false === $force && file_exists("$dir/{$themeDir}Theme.php")) {
            $output->writeln('<error>Looks like this theme has already been restructured.</error>');
            exit(1);
        }

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

        if (is_dir($dir.'/lib/'.$themeDir)) {
            `mv $dir/lib/$themeDir/* $dir`;
            `git add $dir/*`;

            $output->writeln("<info>moved PHP files from $dir/lib/$themeDir/* to $dir</info>");
            if (is_dir("$dir/lib/vendor")) {
                `git mv $dir/lib/vendor $dir`;
                $output->writeln("<comment>Vendors have been moved from $dir/lib/vendor into $dir/vendor/</comment>");
            }
            rmdir("$dir/lib/$themeDir");
            @rmdir("$dir/lib"); // there might be a vendor dir here so suppress warnings
        }

//        `git mv $dir/Version.php $dir/{$themeDir}Version.php`;
//        $output->writeln("<comment>renamed Version.php to {$themeDir}Version.php</comment>");

        // write theme file required for Kernel
        $helper = new Helper\CreateThemeHelper();
        file_put_contents("$dir/{$themeDir}Theme.php", $helper->getTemplate($themeDir));
        `git add {$themeDir}Theme.php`;

        // autocommit changes
        `git commit -a -m "[zikula-tools] Restructured to new theme specification."`;
        $output->writeln("<comment>Committed</comment>");

        $output->writeln("<info>Done.
Todo tasks:

  - Update {$themeDir}Version.php core_min to 1.3.6
  - If there are any old calls to {pageaddvar} specifying js/css paths, these must be tweaked
</info>");
        $output->writeln("<comment>Committed.</comment>");
        chdir($pwd);
    }
}