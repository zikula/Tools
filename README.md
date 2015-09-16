[![Build Status](https://travis-ci.org/zikula/Tools.svg)](https://travis-ci.org/zikula/Tools)

Zikula Tools
============

Migration tools for Zikula

Usage:
```Shell
    zikula-tool module:controller_actions --dir=./Controller --force
    zikula-tool module:ns --dir=. --vendor=Acme --module-name=WidgetModule
    zikula-tool module:restructure --vendor=Acme --dir=. --module-name=WidgetModule --force
    zikula-tool theme:restructure --dir=theme/MyTheme --theme=MyTheme
    zikula-tool toTwig:convert path/to/smarty/templates
```

Warning
-------

These tools are designed to be used with GIT version control and the use of
a diff tools is mandatory.

Code is lex-parsed and then output using a formatter which will change some
formatting. You are advised to used a diff tool to make sure things are done
as you like.


Please Note:
------------

This tool now includes parts of the to-Twig PHP Smarty to Twig Converter developed by sankar (sankar.suda@gmail.com)
and published under an MIT license at https://github.com/2stech/to-twig .