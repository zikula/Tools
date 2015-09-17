<?php

/**
 * This file is part of the PHP ST utility.
 *
 * (c) Sankar suda <sankar.suda@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Zikula\Tools\Converter;

use Zikula\Tools\ConverterAbstract;

/**
 * Class SafetextConverter
 * @package Zikula\Tools\Converter
 */
class SafetextConverter extends ConverterAbstract
{
    public function convert(\SplFileInfo $file, $content)
    {
        return preg_replace('/\|safetext/', '', $content);
    }

    public function getPriority()
    {
        return 101; // highest priority - happens first
    }

    public function getName()
    {
        return 'safetext';
    }

    public function getDescription()
    {
        return 'Remove Zikula safetext filter from templates.';
    }

}
