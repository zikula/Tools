<?php

namespace Zikula\Tools\Console\Command\Helper;

class CreateComposerHelper
{
    public function getTemplate($vendor, $name, $suffix)
    {
        $vendorL = strtolower($vendor);
        $nameL = strtolower($name);
        $suffixL = strtolower($suffix);

        $template = <<<EOF
{
    "name": "$vendorL/$nameL-$suffixL",
    "description": "Change me description $suffix",
    "type": "zikula-$suffixL",
    "license": "LGPL-3.0+",
    "authors": [
        {
            "name": "Your name",
            "homepage": "http://example.com/",
            "email": "example@example.com"
        }
    ],
    "autoload": {
        "psr-0": { "$vendor\\\\$name\\\\": "" }
    },
    "require": {
        "php": ">5.3.3"
    },
    "extra": {
        "zikula": {
            "class": "$vendor\\\\$name\\\\{$vendor}{$name}{$suffix}"
        }
    }
}
EOF;

        return $template;
    }
}
