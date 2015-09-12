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
        $content = $this->replaceCheckPermission($content);
        $content = $this->replaceCheckPermissionBlock($content);
        $content = $this->replaceMisc($content);

        return $content;
    }

    private function replaceBlockPosition($content)
    {
        return preg_replace_callback(
            "/\{blockposition name=([\w]+)\}/i",
            function ($matches) {
                return "{{ showblockposition('$matches[1]') }}";
            },
            $content);
    }

    private function replaceModurl($content)
    {
        return preg_replace_callback(
            "/\{modurl[\s]+modname=['|\"]?([\w][^'|\"]+)['|\"]?[\s]+type=['|\"]?([\w][^'|\"]+)['|\"]?[\s]+func=['|\"]?([\w][^'|\"]+)['|\"]?\}/i",
            function ($matches) {
                $note = (false === stripos($matches[1], 'module')) ? '{# @todo probably an incorrect path #}' : '';
                return "{{ path('" . strtolower($matches[1]) . "_$matches[2]_$matches[3]') }}$note";
            },
            $content);
    }

    private function replacePageaddvar($content)
    {
        return preg_replace_callback(
            "/\{pageaddvar name=['|\"]?([\w]+)['|\"]?\svalue=['|\"]?([a-z0-9:_][^'|\"]+)['|\"]?\}/i",
            function ($matches) {
                return "{{ pageAddVar('$matches[1], '') }}{# @todo oldpath= $matches[2] #}";
            },
            $content);
    }

    private function replaceGettext($content)
    {
        // only replaces gt and does not accommodate string replacements or plurals or counts (__f(), __
        return preg_replace_callback(
            "/\{gt text=['|\"]?([\w][^'|\"]+)['|\"]?['|\"]?\}/i",
            function ($matches) {
                return "{{ __('$matches[1]') }}";
            },
            $content);
    }

    private function replaceCheckPermission($content)
    {
        return preg_replace_callback(
            "/\{checkpermission[\s]+component=['|\"]?([a-z0-9:_]+)['|\"]?[\s]+instance=['|\"]?([a-z0-9:_]+)['|\"]?[\s]+level=['|\"]?([a-z0-9:_]+)['|\"]?[\s]*(assign=['|\"]?([a-z]+)['|\"]?)?(\})/i",
            function ($matches) {
                $set = (!empty($matches[4])) ? "set $matches[4] = " : '';
                return "{{ {$set}hasPermission('$matches[1]', '$matches[2]', '$matches[3]') }}{# @todo consider `if hasPermission()` #}";
            },
            $content);
    }

    private function replaceCheckPermissionBlock($content)
    {
        return preg_replace_callback(
            "/\{checkpermissionblock[\s]+component=['|\"]?([a-z0-9:_]+)['|\"]?[\s]+instance=['|\"]?([a-z0-9:_]+)['|\"]?[\s]+level=(['|\"]?)([a-z0-9:_]+)['|\"]?\}/i",
            function ($matches) {
                return "{% if hasPermission('$matches[1]', '$matches[2]', '$matches[3]') %}";
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
            "{\/checkpermissionblock}" => "{% endif %}",
        ];
        foreach ($replacements as $k => $v) {
            $content = preg_replace('/' . $k . '/i', $v, $content);
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
