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

use Zikula\Tools\Converter\AssignConverter;

/**
 * @author sankara <sankar.suda@gmail.com>
 */
class AssignConverterTest extends \PHPUnit_Framework_TestCase
{
    protected $converter;

    public function setUp()
    {
        $this->converter = new AssignConverter();
    }

    /**
     * @covers       Zikula\Tools\Converter\AssignConverter::convert
     * @dataProvider Provider
     */
    public function testThatAssignIsConverted($smarty, $twig)
    {
        $this->assertSame($twig,
            $this->converter->convert($this->getFileMock(), $smarty)
        );
    }

    public function Provider()
    {
        return array(
            array(
                '{assign var="name" value="Bob"}',
                '{% set name = \'Bob\' %}'
            ),
            array(
                '{assign var="name" value=$bob}',
                '{% set name = bob %}'
            ),
            array(
                '{assign "name" "Bob"}',
                '{% set name = \'Bob\' %}'
            ),
            array(
                '{assign var="foo" "bar" scope="global"}',
                '{% set foo = \'bar\' %}'
            )
        );
    }

    /**
     * @covers Zikula\Tools\Converter\AssignConverter::getName
     */
    public function testThatHaveExpectedName()
    {
        $this->assertEquals('assign', $this->converter->getName());
    }

    /**
     * @covers Zikula\Tools\Converter\AssignConverter::getDescription
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
