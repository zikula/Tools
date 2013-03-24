<?php

namespace Zikula\Tools\Console\Command\Helper;

class CreateThemeHelper
{
    public function getTemplate($vendor, $themeName)
    {
        $template = <<<EOF
<?php

namespace $vendor\\$themeName;

use Zikula\Bundle\CoreBundle\AbstractTheme;

class {$vendor}{$themeName}Theme extends AbstractTheme
{
}
EOF;

        return $template;
    }
}
