<?php

namespace Zikula\Tools\Console\Command\Helper;

class CreateComposerHelper
{
    public function getTemplate($vendor, $name, $suffix)
    {
        $vendorL = strtolower($vendor);
        if (strcasecmp(substr($name, -6), 'module') === 0) {
            $nameL = strtolower(substr($name, 0, -6));
        } else if (strcasecmp(substr($name, -5), 'theme') === 0) {
            $nameL = strtolower(substr($name, 0 -5));
        } else {
            $nameL = strtolower($name);
            $name = ucfirst($name) . ucfirst($suffix);
        }
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
        "psr-0": { "$vendor\\\\$suffix\\\\$name\\\\": "" }
    },
    "require": {
        "php": ">5.3.3"
    },
    "extra": {
        "zikula": {
            "class": "$vendor\\\\$suffix\\\\$name\\\\{$vendor}{$name}"
        }
    }
}
EOF;

        return $template;
    }
}
