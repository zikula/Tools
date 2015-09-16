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
    private $delims = [['{{', '}}'], ['{%', '%}']];

    public function convert(\SplFileInfo $file, $content)
    {
        $content = $this->replaceBlockPosition($content);
        $content = $this->replaceModurl($content);
        $content = $this->replaceRoute($content);
        $content = $this->replacePageaddvar($content);
        $content = $this->replaceGettext($content);
        $content = $this->replaceCheckPermission($content);
        $content = $this->replaceCheckPermissionBlock($content);
        $content = $this->replaceEmptyTest($content);
        $content = $this->replaceAjaxHeader($content);
        $content = $this->replaceModvarLookup($content);
        $content = $this->replaceMisc($content);

        return $content;
    }

    /**
     * replace {blockposition name='topnav' assign='topnavblock'}
     * `assign` is optional parameter
     * @param $content
     * @return mixed
     */
    private function replaceBlockPosition($content)
    {
        return preg_replace_callback(
            "/\{blockposition name=['|\"]?([\w]+)['|\"]?[\s]*(assign=['|\"]?([a-z]+)['|\"]?)?\}/i",
            function ($matches) {
                $set = (!empty($matches[3])) ? "set $matches[3] = " : '';
                $delims = $this->delims[(int)!empty($matches[3])];
                return "$delims[0] {$set}showblockposition('$matches[1]') $delims[1]";
            },
            $content);
    }

    /**
     * replace {modurl modname='ZikulaSettingsModule' type='admin' func='index' aparam='value' assign='foo'}
     * @param $content
     * @return mixed
     */
    private function replaceModurl($content)
    {
        return preg_replace_callback(
            "/\{modurl[\s]*([^}]*)?\}/i",
            function ($matches) {
                $params = $this->createParamArray($matches[1]);
                $mod = $params['modname'];
                $type = $params['type'];
                $func = $params['func'];
                $set = !empty($params['assign']) ? "set $params[assign] = " : '';
                unset($params['modname'], $params['type'], $params['func'], $params['assign']);
                $paramString = !empty($params) ? ', ' . json_encode($params) : '';
                $note = (false === stripos($mod, 'module')) ? '{# @todo probably an incorrect path #}' : '';
                $delims = $this->delims[(int)!empty($set)];
                return "$delims[0] {$set}path('" . strtolower($mod) . "_{$type}_{$func}'{$paramString}) {$delims[1]}$note";
            },
            $content);
    }

    /**
     * replace {route name='zikulagroupsmodule_admin_delete' gid=$item.gid}
     * WARNING! does not accommodate twig variables (will enclose them in quotes)
     * @param $content
     * @return mixed
     */
    private function replaceRoute($content)
    {
        return preg_replace_callback(
            "/\{route name=['|\"]?([\w]+)['|\"]?[\s]*([\s\S]*)?\}/i",
            function ($matches) {
                $params = !empty($matches[2]) ? $this->createParamArray($matches[2]) : null;
                $set = !empty($params['assign']) ? "set $params[assign] = " : '';
                $delims = $this->delims[(int)!empty($set)];
                unset($params['assign']);
                $paramString = !empty($params) ? ', ' . json_encode($params) : '';
                return "$delims[0] {$set}path('$matches[1]'{$paramString}) $delims[1]";
            },
            $content);
    }

    /**
     * replace {pageaddvar name="stylesheet" value="$stylepath/style.css"}
     * @param $content
     * @return mixed
     */
    private function replacePageaddvar($content)
    {
        return preg_replace_callback(
            "/\{pageaddvar name=['|\"]?([\w]+)['|\"]?\svalue=['|\"]?([a-z0-9$:_][^'|\"]+)['|\"]?\}/i",
            function ($matches) {
                return "{{ pageAddVar('$matches[1]', '') }}{# @todo oldpath= $matches[2] to zasset('@VendorBundleTheme:path/from/Resources') #}";
            },
            $content);
    }

    /**
     * `assign` is optional parameter
     * @param $content
     * @return mixed
     */
    private function replaceGettext($content)
    {
        // replace {gt text='Membership application' assign='templatetitle'}
        $newContent[0] = preg_replace_callback(
            "/\{gt text=['|\"]?([\w\W][^'|\"]+)['|\"]?[\s]*(assign=['|\"]?([a-z]+)['|\"]?)?\}/i",
            function ($matches) {
                $set = (!empty($matches[3])) ? "set $matches[3] = " : '';
                $delims = $this->delims[(int)!empty($matches[3])];
                return "$delims[0] {$set}__('$matches[1]') $delims[1]";
            },
            $content);

        // replace {gt text='Delete: %s' tag1=$group.name assign='strDeleteGroup'}
        $newContent[1] = preg_replace_callback(
            "/\{gt text=['|\"]?([\w\W][^'|\"]+)['|\"]?[\s]*tag1=([\S]+)[\s]*(assign=['|\"]?([a-z]+)['|\"]?)?\}/i",
            function ($matches) {
                $set = (!empty($matches[4])) ? "set $matches[4] = " : '';
                $delims = $this->delims[(int)!empty($matches[4])];
                $matches[1] = str_replace('%s', '%sub%', $matches[1]);
                $sub = $this->variable($matches[2]);
                return "$delims[0] {$set}__f('$matches[1]', {\"%sub%\": $sub}) $delims[1]";
            },
            $newContent[0]);

        return end($newContent);
    }

    /**
     * replace {checkpermission component='Categories::category' instance="ID::"|cat:$category.category.id level='ACCESS_EDIT' assign='authcatedit'}
     * `assign` is optional parameter
     * @param $content
     * @return mixed
     */
    private function replaceCheckPermission($content)
    {
        return preg_replace_callback(
            "/\{checkpermission[\s]+component=['|\"]?([a-z0-9:_]+)['|\"]?[\s]+instance=['|\"]?([a-z0-9:_]+)['|\"]?[\s]+level=['|\"]?([a-z0-9:_]+)['|\"]?[\s]*(assign=['|\"]?([a-z]+)['|\"]?)?(\})/i",
            function ($matches) {
                $set = (!empty($matches[5])) ? "set $matches[5] = " : '';
                $delims = $this->delims[(int)!empty($matches[5])];
                return "$delims[0] {$set}hasPermission('$matches[1]', '$matches[2]', '$matches[3]') $delims[1]{# @todo consider `if hasPermission()` #}";
            },
            $content);
    }

    /**
     * replace {checkpermissionblock component='News::' instance=$item.cr_uid|cat:'::'|cat:$item.sid level='ACCESS_DELETE'}
     * @param $content
     * @return mixed
     */
    private function replaceCheckPermissionBlock($content)
    {
        return preg_replace_callback(
            "/\{checkpermissionblock[\s]+component=['|\"]?([a-z0-9:_]+)['|\"]?[\s]+instance=['|\"]?([a-z0-9:_]+)['|\"]?[\s]+level=['|\"]?([a-z0-9:_]+)['|\"]?\}/i",
            function ($matches) {
                return "{% if hasPermission('$matches[1]', '$matches[2]', '$matches[3]') %}";
            },
            $content);
    }

    /**
     * replace !empty(title)
     * @param $content
     * @return mixed
     */
    private function replaceEmptyTest($content)
    {
        return preg_replace_callback(
            "/(!)?empty\(([\w_$-]+)\)/i",
            function ($matches) {
                $not = !empty($matches[1]) ? 'not ' : '';
                return "$matches[2] is {$not}empty";
            },
            $content);
    }

    /**
     * replace {ajaxheader modname='Groups' filename='groups.js' ui=true}
     * @param $content
     * @return mixed
     */
    private function replaceAjaxHeader($content)
    {
        return preg_replace_callback(
            "/\{(ajaxheader[\s]*[\s\S]*?)\}/i",
            function ($matches) {
                return "{# $matches[1] #}";
            },
            $content);
    }

    /**
     * replace modvars.ZikulaGroupsModule.hideclosed
     * @param $content
     * @return mixed
     */
    private function replaceModvarLookup($content)
    {
        return preg_replace_callback(
            "/\{\{ modvars\.([\w]+)\.([\w]+) \}\}/", // case sensitive match
            function ($matches) {
                return "{{ getModVar('$matches[1]', '$matches[2]') }}";
            },
            $content);
    }

    /**
     * replace miscellaneous texts/tags
     * @param $content
     * @return mixed
     */
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
            "{adminpanelmenu}" => "{# adminpanelmenu #}",
            "{{ content }}" => "{{ content|raw }}",
            "{{ maincontent }}" => "{{ maincontent|raw }}",
            "{\/checkpermissionblock}" => "{% endif %}",
            "{nocache}" => "{# nocache #}",
            "{\/nocache}" => "{# \/nocache #}",
            "{pagerendertime}" => "{# pagerendertime #}",
            ".tpl" => ".html.twig",
            "{adminheader}" => "{{ render(controller('ZikulaAdminModule:Admin:adminheader')) }}",
            "{adminfooter}" => "{{ render(controller('ZikulaAdminModule:Admin:adminfooter')) }}",
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

    /**
     * Create array of parameters from string provided by Smarty template function
     * @param $string
     * @return array
     */
    private function createParamArray($string)
    {
        $rawParams = explode(' ', $string);
        $params = [];
        foreach($rawParams as $param) {
            list($k, $v) = explode('=', $param);
//            $params[trim($k)] = trim(str_replace(['\'','"'], '', $v));
            $params[trim($k)] = $this->variable($v);
        }

        return $params;
    }
}
