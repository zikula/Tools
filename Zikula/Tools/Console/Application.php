<?php

namespace Zikula\Tools\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Zikula\Tools\Version;

class Application extends BaseApplication
{
    public function __construct()
    {
        error_reporting(-1);

        parent::__construct('Zikula Tools', Version::VERSION);

        $this->add(new Command\ControllerActionCommand());
        $this->add(new Command\NamespaceCommand());
        $this->add(new Command\MigrateResourceStructureCommand());
        $this->add(new Command\CompileCommand());
    }

    public function getLongVersion()
    {
        return parent::getLongVersion().' by <comment>Drak</comment>';
    }
}