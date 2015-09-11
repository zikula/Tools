<?php

/**
 * This file is part of the PHP ST utility.
 *
 * (c) Zikula Team
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Zikula\Tools\Converter;

use Zikula\Tools\ConverterAbstract;

/**
 * Class ZikulaConverter
 * @package Zikula\Tools\Converter
 */
class ZikulaConverter extends ConverterAbstract
{
    public function convert(\SplFileInfo $file, $content)
    {
        $content = $this->replaceBlockPosition($content);
        $content = $this->replaceModurl($content);
        $content = $this->replacePageaddvar($content);
        $content = $this->replaceGettext($content);
        $content = $this->replaceMisc($content);

        return $content;
    }

    private function replaceBlockPosition($content)
    {
        return preg_replace_callback(
            "/(\{blockposition name=)([\w]+)(\})/",
            function ($matches) {
                return "{{ showblockposition('$matches[2]') }}";
            },
            $content);
    }

    private function replaceModurl($content)
    {
        return preg_replace_callback(
            "/(\{modurl)([\s]+)(modname=['|\"]?)([\w][^'|\"]+)(['|\"]?)([\s]+)(type=['|\"]?)([\w][^'|\"]+)(['|\"]?)([\s]+)(func=['|\"]?)([\w][^'|\"]+)(['|\"]?)(\})/",
            function ($matches) {
                return "{{ path('" . strtolower($matches[4]) . "_$matches[8]_$matches[12]') }}";
            },
            $content);
    }

    private function replacePageaddvar($content)
    {
        return preg_replace_callback(
            "/(\{pageaddvar name=)(['|\"]?)([\w]+)(['|\"]?)\s(value=)(['|\"]?)([\S][^'|\"]+)(['|\"]?)(\})/",
            function ($matches) {
                return "{{ pageAddVar('$matches[3], '') }}{# @todo oldpath= $matches[7] #}";
            },
            $content);
    }

    private function replaceGettext($content)
    {
        // only replaces gt and does not accommodate string replacements or plurals or counts (__f(), __
        return preg_replace_callback(
            "/(\{gt text=)(['|\"]?)([\w][^'|\"]+)(['|\"]?)(['|\"]?)(\})/",
            function ($matches) {
                return "{{ __('$matches[3]') }}";
            },
            $content);
    }

    private function replaceMisc($content)
    {
        $replacements = [
            "{pagegetvar name='title'}" => "{{ pagevars.title }}",
            "{{ metatags.description }}" => "{{ pagevars.meta.description }}",
            "{{ metatags.keywords }}" => "{{ pagevars.meta.keywords }}",
            "{lang}" => "{{ pagevars.lang }}",
            "{langdirection}" => "{{ pagevars.langdirection }}",
            "{charset}" => "{{ pagevars.meta.charset }}",
            "{homepage}" => "{{ app.request.baseUrl }}",
            "{{ modvars.ZConfig.sitename }}" => "{{ getModVar('ZConfig', 'sitename') }}",
            "{{ modvars.ZConfig.slogan }}" => "{{ getModVar('ZConfig', 'slogan') }}",
            "{adminpanelmenu}" => "{# adminpanelmenu #}",
            "{{ content }}" => "{{ content|raw }}",
            "{{ maincontent }}" => "{{ maincontent|raw }}",
        ];
        foreach ($replacements as $k => $v) {
            $content = preg_replace('/' . $k . '/', $v, $content);
        }

        return $content;
    }

    public function getPriority()
    {
        return 1; // lower priority means do later
    }

    public function getName()
    {
        return 'zikula';
    }

    public function getDescription()
    {
        return 'Convert zikula-specific smarty tags to compatible twig tags.';
    }
}
