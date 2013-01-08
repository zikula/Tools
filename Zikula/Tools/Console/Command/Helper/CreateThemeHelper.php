<?php

namespace Zikula\Tools\Console\Command\Helper;

class CreateThemeHelper
{
    public function getTemplate($themeName)
    {
        $template = <<<EOF
<?php

namespace $themeName;

use Zikula\Bundle\CoreBundle\AbstractTheme;

class {$themeName}Theme extends AbstractTheme
{
}
EOF;

        return $template;
    }
}
