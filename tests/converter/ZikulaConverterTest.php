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
            ["{blockposition name=left}", "{{ showblockposition('left') }}"],
            ["{blockposition name='left'}", "{{ showblockposition('left') }}"],
            ["{blockposition name=\"left\"}", "{{ showblockposition('left') }}"],
            ["{blockposition name=topnav assign=topnavblock}", "{% set topnavblock = showblockposition('topnav') %}"],
            ["{blockposition name=topnav assign='topnavblock'}", "{% set topnavblock = showblockposition('topnav') %}"],
            ["{blockposition name=topnav assign=\"topnavblock\"}", "{% set topnavblock = showblockposition('topnav') %}"],

            // test \Zikula\Tools\Converter\ZikulaConverter::replaceModurl
            ["{modurl modname='ZikulaSettingsModule' type='admin' func='index'}", "{{ path('zikulasettingsmodule_admin_index') }}"],
            [
                "{modurl modname='ZikulaSettingsModule' type='admin' func='index' foo='bar'}",
                "{{ path('zikulasettingsmodule_admin_index', {\"foo\":\"bar\"}) }}"
            ],
            [
                "{modurl modname='ZikulaSettingsModule' type='admin' func='index' foo='bar' boo=far}",
                "{{ path('zikulasettingsmodule_admin_index', {\"foo\":\"bar\",\"boo\":\"far\"}) }}"
            ],
            [
                "{modurl modname='ZikulaSettingsModule' type='admin' func='index' foo='bar' boo=\$far}",
                "{{ path('zikulasettingsmodule_admin_index', {\"foo\":\"bar\",\"boo\":\"far\"}) }}"
            ],
            [
                "{modurl modname='ZikulaSettingsModule' type='admin' func='index' assign='foo'}",
                "{% set foo = path('zikulasettingsmodule_admin_index') %}"
            ],

            // test \Zikula\Tools\Converter\ZikulaConverter::replaceRoute
            ["{route name='zikulaadminmodule_admin_newcat'}", "{{ path('zikulaadminmodule_admin_newcat') }}"],
            ["{route name=\"zikulaadminmodule_admin_newcat\"}", "{{ path('zikulaadminmodule_admin_newcat') }}"],
            ["{route name='zikulaadminmodule_admin_newcat' foo='bar'}", "{{ path('zikulaadminmodule_admin_newcat', {\"foo\":\"bar\"}) }}"],
            [
                "{route name='zikulaadminmodule_admin_newcat' foo='bar' assign='foo'}",
                "{% set foo = path('zikulaadminmodule_admin_newcat', {\"foo\":\"bar\"}) %}"
            ],

            // test \Zikula\Tools\Converter\ZikulaConverter::replacePageaddvar
            [
                "{pageaddvar name=\"stylesheet\" value=\"\$stylepath/style.css\"}",
                "{{ pageAddVar('stylesheet', '') }}{# @todo oldpath= \$stylepath/style.css to @VendorBundleTheme:path/from/Resources #}"
            ],
            [
                "{pageaddvar name='stylesheet' value=\$stylepath/style.css}",
                "{{ pageAddVar('stylesheet', '') }}{# @todo oldpath= \$stylepath/style.css to @VendorBundleTheme:path/from/Resources #}"
            ],

            // test \Zikula\Tools\Converter\ZikulaConverter::replaceGettext
            ["{gt text='foo'}", "{{ __('foo') }}"],
            [
                "{gt text='Membership application' assign='templatetitle'}",
                "{% set templatetitle = __('Membership application') %}"
            ],
            [
                "{gt text='Delete: %s' tag1=\$group.name'}",
                "{{ __f('Delete: %sub%', {\"%sub%\": group.name}) }}"
            ],
            [
                "{gt text='Delete: %s' tag1=\$group.name assign='strDeleteGroup'}",
                "{% set strDeleteGroup = __f('Delete: %sub%', {\"%sub%\": group.name}) %}"
            ],

            // test \Zikula\Tools\Converter\ZikulaConverter::replaceCheckPermission
            [
                "{checkpermission component='ZikulaUsersModule::' instance=\"::\" level='ACCESS_MODERATE'}",
                "{{ hasPermission('ZikulaUsersModule::', '::', 'ACCESS_MODERATE') }}{# @todo consider `if hasPermission()` #}"
            ],
            [
                "{checkpermission component=ZikulaUsersModule:: instance='::' level=ACCESS_MODERATE}",
                "{{ hasPermission('ZikulaUsersModule::', '::', 'ACCESS_MODERATE') }}{# @todo consider `if hasPermission()` #}"
            ],
            [
                "{checkpermission component='ZikulaUsersModule::' instance=\"::\" level='ACCESS_MODERATE' assign='foo'}",
                "{% set foo = hasPermission('ZikulaUsersModule::', '::', 'ACCESS_MODERATE') %}{# @todo consider `if hasPermission()` #}"
            ],

            // test \Zikula\Tools\Converter\ZikulaConverter::replaceCheckPermissionBlock
            [
                "{checkpermissionblock component='ZikulaUsersModule::' instance=\"::\" level='ACCESS_MODERATE'}",
                "{% if hasPermission('ZikulaUsersModule::', '::', 'ACCESS_MODERATE') %}"
            ],
            [
                "{checkpermissionblock component=ZikulaUsersModule:: instance='::' level=ACCESS_MODERATE}",
                "{% if hasPermission('ZikulaUsersModule::', '::', 'ACCESS_MODERATE') %}"
            ],

            // test \Zikula\Tools\Converter\ZikulaConverter::replaceEmptyTest
            ["if !empty(title)", "if title is not empty"],

            // test \Zikula\Tools\Converter\ZikulaConverter::replaceAjaxHeader
            ["{ajaxheader modname='Groups' filename='groups.js' ui=true}", "{# ajaxheader modname='Groups' filename='groups.js' ui=true #}"],

            // test \Zikula\Tools\Converter\ZikulaConverter::replaceModvarLookup
            ["modvars.ZikulaGroupsModule.hideclosed", "{{ getModVar('ZikulaGroupsModule', 'hideclosed') }}"],
            ["modvars.ZConfig.sitename", "{{ getModVar('ZConfig', 'sitename') }}"],
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
