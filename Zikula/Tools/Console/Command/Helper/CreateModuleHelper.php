<?php

namespace Zikula\Tools\Console\Command\Helper;

class CreateModuleHelper
{
    public function getTemplate($vendor, $moduleName)
    {
        $template = <<<EOF
<?php

namespace $vendor\\$moduleName;

use Zikula\Core\AbstractModule;

class {$vendor}{$moduleName} extends AbstractModule
{
}
EOF;

        return $template;
    }
}
