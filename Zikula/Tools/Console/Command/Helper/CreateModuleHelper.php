<?php

namespace Zikula\Tools\Console\Command\Helper;

class CreateModuleHelper
{
    public function getTemplate($moduleName)
    {
        $template = <<<EOF
<?php

namespace $moduleName;

use Zikula\Bundle\CoreBundle\AbstractModule;

class $moduleName extends AbstractModule
{
}
EOF;

        return $template;
    }
}
