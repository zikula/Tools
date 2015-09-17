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
class SafetextConverterTest extends \PHPUnit_Framework_TestCase
{
    protected $converter;

    public function setUp()
    {
        $this->converter = new Converter\SafetextConverter();
    }

    /**
     * @covers       \Zikula\Tools\Converter\SafetextConverter::convert
     * @dataProvider Provider
     */
    public function testThatSafetextIsConverted($smarty, $twig)
    {
        $this->assertSame($twig,
            $this->converter->convert($this->getFileMock(), $smarty)
        );

    }

    public function Provider()
    {
        return [
            ["{\$foo|safetext}", "{\$foo}"],
            ["{\$foo|bar|safetext}", "{\$foo|bar}"],
            ["{\$foo|safetext|bar}", "{\$foo|bar}"],
            ["{{ foo|safetext }}", "{{ foo }}"],
        ];
    }

    /**
     * @covers Zikula\Tools\Converter\ZikulaConverter::getName
     */
    public function testThatHaveExpectedName()
    {
        $this->assertEquals('safetext', $this->converter->getName());
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
