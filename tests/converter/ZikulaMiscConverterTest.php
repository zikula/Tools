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
class ZikulaMiscConverterTest extends \PHPUnit_Framework_TestCase
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
            ["{pagegetvar name='title'}", "{{ pagevars.title }}"],
            ["{{ metatags.description }}", "{{ pagevars.meta.description }}"],
            ["{{ metatags.keywords }}", "{{ pagevars.meta.keywords }}"],
            ["{lang}", "{{ pagevars.lang }}"],
            ["{langdirection}", "{{ pagevars.langdirection }}"],
            ["{charset}", "{{ pagevars.meta.charset }}"],
            ["{homepage}", "{{ pagevars.homepath }}"],
            ["{adminpanelmenu}", "{# adminpanelmenu #}"],
            ["{{ content }}", "{{ content|raw }}"],
            ["{{ maincontent }}", "{{ maincontent|raw }}"],
            ["{/checkpermissionblock}", "{% endif %}"],
            ["{nocache}", ""],
            ["{/nocache}", ""],
            ["{pagerendertime}", ""],
            ["foo.tpl", "foo.html.twig"],
            ["foo|safehtml", "foo|safeHtml"],
            ["{adminheader}", "{{ render(controller('ZikulaAdminModule:Admin:adminheader')) }}"],
            ["{adminfooter}", "{{ render(controller('ZikulaAdminModule:Admin:adminfooter')) }}"],
            ["{insert name='getstatusmsg'}", "{{ showflashes() }}"],
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
