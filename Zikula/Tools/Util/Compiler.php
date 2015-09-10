<?php

namespace Zikula\Tools\Util;

use Symfony\Component\Finder\Finder;

class Compiler
{
    public function compile($pharFile = 'zikula-tools.phar')
    {
        if (file_exists($pharFile)) {
            unlink($pharFile);
        }

        $phar = new \Phar($pharFile, 0, 'zikula-tools.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        // CLI Component files
        foreach ($this->getFiles() as $file) {
            $path = str_replace(__DIR__ . '/', '', $file);
            $phar->addFromString($path, file_get_contents($file));
        }
        $this->addPhpCsFixer($phar);

        // Stubs
        $phar->setStub($this->getStub());

        $phar->stopBuffering();

        // $phar->compressFiles(\Phar::GZ);

        unset($phar);

        chmod($pharFile, 0755);
    }

    protected function getFiles()
    {
        $iterator = Finder::create()->files()->exclude('Tests')->name('*.php')->in(array('vendor', 'Zikula'));

        return array_merge(array('LICENSE'), iterator_to_array($iterator));
    }

    /**
     * Remove the shebang from the file before add it to the PHAR file.
     *
     * @param \Phar $phar PHAR instance
     */
    protected function addPhpCsFixer(\Phar $phar)
    {
        $content = file_get_contents(__DIR__ . '/../../../zikula-tools');
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);

        $phar->addFromString('php-cs-fixer', $content);
    }

    protected function getStub()
    {
        return "#!/usr/bin/env php\n<?php Phar::mapPhar('zikula-tools.phar'); require 'phar://zikula-tools.phar/php-cs-fixer'; __HALT_COMPILER();";
    }

    protected function getLicense()
    {
        return '
/**
 * This file is part of Zikula Tools.
 *
 * (c) Zikula Foundation <cto@zikula.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */';
    }
}