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
        $content = $this->replacePagesetvar($content);
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
                $params = $this->attributes($matches[1], true);
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
            "/\{route[\s]*([^}]*)?\}/i",
            function ($matches) {
                $params = $this->attributes($matches[1], true);
                $routeId = $params['name'];
                $set = !empty($params['assign']) ? "set $params[assign] = " : '';
                $delims = $this->delims[(int)!empty($set)];
                unset($params['name'], $params['assign']);
                $paramString = !empty($params) ? ', ' . json_encode($params) : '';
                return "$delims[0] {$set}path('$routeId'{$paramString}) $delims[1]";
            },
            $content);
    }

    /**
     * replace {pagesetvar name='title' value=$templatetitle}
     * WARNING! value set as variable, not string.
     * @param $content
     * @return mixed
     */
    private function replacePagesetvar($content)
    {
        return preg_replace_callback(
            "/\{pagesetvar[\s]*([^}]*)?\}/i",
            function ($matches) {
                $params = $this->attributes($matches[1], true);
                return "{{ pageSetVar('$params[name]', $params[value]) }}";
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
     * replace {gt text='Membership application' assign='templatetitle' domain="zikula"}
     * replace {gt text='Delete: %s' tag1=$group.name assign='strDeleteGroup'}
     * `assign` is optional parameter
     * `domain` is optional parameter
     * @param $content
     * @return mixed
     */
    private function replaceGettext($content)
    {
        $content = preg_replace_callback(
            "/\{gt[\s]*([^}]*)?\}/i",
            function ($matches) {
                $params = $this->attributes($matches[1], true);
                $set = !empty($params['assign']) ? "set $params[assign] = " : '';
                $domain = !empty($params['domain']) ? ", '$params[domain]'" : '';
                $delims = $this->delims[(int)!empty($set)];
                if (!empty($params['tag2'])) {
                    // @todo design multiple var replace method and accommodate plurals
                    return $matches[0]; // original text
                } elseif (!empty($params['tag1'])) {
                    $func = '__f';
                    $text = str_replace('%s', '%sub%', $params['text']);
                    $sub = ", {\"%sub%\": $params[tag1]}";
                } else {
                    $func = '__';
                    $text = $params['text'];
                    $sub = '';
                }
                return "$delims[0] {$set}{$func}('{$text}'{$sub}{$domain}) $delims[1]";
            },
            $content);

        return $content;
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
            "/(!)?empty\(([\w_$\[\]-]+)\)/i",
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
            "{nocache}" => "",
            "{\/nocache}" => "",
            "{pagerendertime}" => "",
            ".tpl" => ".html.twig",
            "\|safehtml" => "|safeHtml",
            "{adminheader}" => "{{ render(controller('ZikulaAdminModule:Admin:adminheader')) }}",
            "{adminfooter}" => "{{ render(controller('ZikulaAdminModule:Admin:adminfooter')) }}",
            "{insert name='getstatusmsg'}" => "{{ showflashes() }}",
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
