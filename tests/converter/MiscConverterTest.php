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
use Zikula\Tools\Converter\MiscConverter;
use Zikula\Tools\ConverterAbstract;

/**
 * @author sankara <sankar.suda@gmail.com>
 */
class MiscConverterTest extends \PHPUnit_Framework_TestCase
{
    protected $converter;

    public function setUp()
    {
        $this->converter = new MiscConverter();
    }

    /**
     * @covers       Zikula\Tools\Converter\MiscConverter::convert
     * @dataProvider Provider
     */
    public function testThatMiscIsConverted($smarty, $twig)
    {
        $this->assertSame($twig,
            $this->converter->convert($this->getFileMock(), $smarty)
        );

    }

    public function Provider()
    {
        return array(
            array(
                '{ldelim}', ''
            ),
            array(
                '{rdelim}', ''
            ),
            array(
                '{literal}', '{# literal #}'
            ),
            array(
                '{/literal}', '{# /literal #}'
            )
        );
    }

    /**
     * @covers Zikula\Tools\Converter\Miscconverter::getName
     */
    public function testThatHaveExpectedName()
    {
        $this->assertEquals('misc', $this->converter->getName());
    }

    /**
     * @covers Zikula\Tools\Converter\Miscconverter::getDescription
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
