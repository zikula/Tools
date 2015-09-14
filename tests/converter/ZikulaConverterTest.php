<?php

/**
 * This file is part of the PHP ST utility.
 *
 * (c) sankar suda <sankar.suda@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Zikula\Tools\Tests\Converter;

use Zikula\Tools\Converter;

/**
 * @author craig
 */
class ZikulaConverterTest extends \PHPUnit_Framework_TestCase
{
    protected $converter;

    public function setUp()
    {
        $this->converter = new Converter\ZikulaConverter();
    }

    /**
     * @covers       Zikula\Tools\Converter\ZikulaConverter::convert
     * @dataProvider Provider
     */
    public function testThatZikulaIsConverted($smarty, $twig)
    {
        $this->assertSame($twig,
            $this->converter->convert($this->getFileMock(), $smarty)
        );

    }

    public function Provider()
    {
        return [
            // test \Zikula\Tools\Converter\ZikulaConverter::replaceBlockPosition
            ["123{blockposition name=left}321", "123{{ showblockposition('left') }}321"],
            ["123{blockposition name='left'}321", "123{{ showblockposition('left') }}321"],
            ["123{blockposition name=\"left\"}321", "123{{ showblockposition('left') }}321"],
            ["123{blockposition name=topnav assign=topnavblock}321", "123{% set topnavblock = showblockposition('topnav') %}321"],
            ["123{blockposition name=topnav assign='topnavblock'}321", "123{% set topnavblock = showblockposition('topnav') %}321"],
            ["123{blockposition name=topnav assign=\"topnavblock\"}321", "123{% set topnavblock = showblockposition('topnav') %}321"],

            // test \Zikula\Tools\Converter\ZikulaConverter::replaceModurl
            ["123{modurl modname='ZikulaSettingsModule' type='admin' func='index'}321", "123{{ path('zikulasettingsmodule_admin_index') }}321"],
            [
                "123{modurl modname='ZikulaSettingsModule' type='admin' func='index' foo='bar'}321",
                "123{{ path('zikulasettingsmodule_admin_index', {\"foo\":\"bar\"}) }}321"
            ],
            [
                "123{modurl modname='ZikulaSettingsModule' type='admin' func='index' foo='bar' boo=far}321",
                "123{{ path('zikulasettingsmodule_admin_index', {\"foo\":\"bar\",\"boo\":\"far\"}) }}321"
            ],
            [
                "123{modurl modname='ZikulaSettingsModule' type='admin' func='index' foo='bar' boo=\$far}321",
                "123{{ path('zikulasettingsmodule_admin_index', {\"foo\":\"bar\",\"boo\":\"far\"}) }}321"
            ],
            [
                "123{modurl modname='ZikulaSettingsModule' type='admin' func='index' assign='foo'}321",
                "123{% set foo = path('zikulasettingsmodule_admin_index') %}321"
            ],

            // test \Zikula\Tools\Converter\ZikulaConverter::replaceRoute
            ["123{route name='zikulaadminmodule_admin_newcat'}321", "123{{ path('zikulaadminmodule_admin_newcat') }}321"],
            ["123{route name=\"zikulaadminmodule_admin_newcat\"}321", "123{{ path('zikulaadminmodule_admin_newcat') }}321"],
            ["123{route name='zikulaadminmodule_admin_newcat' foo='bar'}321", "123{{ path('zikulaadminmodule_admin_newcat', {\"foo\":\"bar\"}) }}321"],
            [
                "123{route name='zikulaadminmodule_admin_newcat' foo='bar' assign='foo'}321",
                "123{% set foo = path('zikulaadminmodule_admin_newcat', {\"foo\":\"bar\"}) %}321"
            ],

            // test \Zikula\Tools\Converter\ZikulaConverter::replacePageaddvar
            [
                "123{pageaddvar name=\"stylesheet\" value=\"\$stylepath/style.css\"}321",
                "123{{ pageAddVar('stylesheet', '') }}{# @todo oldpath= \$stylepath/style.css to @VendorBundleTheme:path/from/Resources #}321"
            ],
            [
                "123{pageaddvar name='stylesheet' value=\$stylepath/style.css}321",
                "123{{ pageAddVar('stylesheet', '') }}{# @todo oldpath= \$stylepath/style.css to @VendorBundleTheme:path/from/Resources #}321"
            ],

            // test \Zikula\Tools\Converter\ZikulaConverter::replaceGettext
            ["123{gt text='foo'}321", "123{{ __('foo') }}321"],
            [
                "123{gt text='Membership application' assign='templatetitle'}321",
                "123{% set templatetitle = __('Membership application') %}321"
            ],
            [
                "123{gt text='Delete: %s' tag1=\$group.name'}321",
                "123{{ __f('Delete: %sub%', {\"%sub%\": group.name}) }}321"
            ],
            [
                "123{gt text='Delete: %s' tag1=\$group.name assign='strDeleteGroup'}321",
                "123{% set strDeleteGroup = __f('Delete: %sub%', {\"%sub%\": group.name}) %}321"
            ],

            // test \Zikula\Tools\Converter\ZikulaConverter::replaceCheckPermission
            [
                "123{checkpermission component='ZikulaUsersModule::' instance=\"::\" level='ACCESS_MODERATE'}321",
                "123{{ hasPermission('ZikulaUsersModule::', '::', 'ACCESS_MODERATE') }}{# @todo consider `if hasPermission()` #}321"
            ],
            [
                "123{checkpermission component=ZikulaUsersModule:: instance='::' level=ACCESS_MODERATE}321",
                "123{{ hasPermission('ZikulaUsersModule::', '::', 'ACCESS_MODERATE') }}{# @todo consider `if hasPermission()` #}321"
            ],
            [
                "123{checkpermission component='ZikulaUsersModule::' instance=\"::\" level='ACCESS_MODERATE' assign='foo'}321",
                "123{% set foo = hasPermission('ZikulaUsersModule::', '::', 'ACCESS_MODERATE') %}{# @todo consider `if hasPermission()` #}321"
            ],

            // test \Zikula\Tools\Converter\ZikulaConverter::replaceCheckPermissionBlock
            [
                "123{checkpermissionblock component='ZikulaUsersModule::' instance=\"::\" level='ACCESS_MODERATE'}321",
                "123{% if hasPermission('ZikulaUsersModule::', '::', 'ACCESS_MODERATE') %}321"
            ],
            [
                "123{checkpermissionblock component=ZikulaUsersModule:: instance='::' level=ACCESS_MODERATE}321",
                "123{% if hasPermission('ZikulaUsersModule::', '::', 'ACCESS_MODERATE') %}321"
            ],

            // test \Zikula\Tools\Converter\ZikulaConverter::replaceEmptyTest
            ["123if !empty(title)321", "123if title is not empty321"],

            // test \Zikula\Tools\Converter\ZikulaConverter::replaceAjaxHeader
            ["123{ajaxheader modname='Groups' filename='groups.js' ui=true}321", "123{# ajaxheader modname='Groups' filename='groups.js' ui=true #}321"],

            // test \Zikula\Tools\Converter\ZikulaConverter::replaceModvarLookup
            ["123{{ modvars.ZikulaGroupsModule.hideclosed }}321", "123{{ getModVar('ZikulaGroupsModule', 'hideclosed') }}321"],
            ["123{{ modvars.ZConfig.sitename }}321", "123{{ getModVar('ZConfig', 'sitename') }}321"],
        ];
    }

    /**
     * @covers Zikula\Tools\Converter\ZikulaConverter::getName
     */
    public function testThatHaveExpectedName()
    {
        $this->assertEquals('zikula', $this->converter->getName());
    }

    /**
     * @covers Zikula\Tools\Converter\Zikulaconverter::getDescription
     */
    public function testThatHaveDescription()
    {
        $this->assertNotEmpty($this->converter->getDescription());
    }

    private function getFileMock()
    {
        return $this->getMockBuilder('\SplFileInfo')
            ->enableOriginalConstructor()
            ->setConstructorArgs(array('mockFile'))
            ->getMock();
    }
}
