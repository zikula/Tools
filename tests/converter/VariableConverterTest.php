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
use Zikula\Tools\Converter\VariableConverter;
use Zikula\Tools\ConverterAbstract;

/**
 * @author sankara <sankar.suda@gmail.com>
 */
class VariableConverterTest extends \PHPUnit_Framework_TestCase
{
    protected $converter;

    public function setUp()
    {
        $this->converter = new VariableConverter();
    }

    /**
     * @covers       Zikula\Tools\Converter\VariableConverter::convert
     * @dataProvider Provider
     */
    public function testThatVariableIsConverted($smarty, $twig)
    {
        $this->assertSame($twig,
            $this->converter->convert($this->getFileMock(), $smarty)
        );

    }

    public function Provider()
    {
        return array(
            array(
                '{$var}', '{{ var }}'
            ),
            array(
                '{$contacts.fax}', '{{ contacts.fax }}'
            ),
            array(
                '{$contacts[0]}', '{{ contacts[0] }}'
            ),
            array(
                '{$contacts[2][0]}', '{{ contacts[2][0] }}'
            ),
            array(
                '{$person->name}', '{{ person.name }}'
            )
        );
    }

    /**
     * @covers Zikula\Tools\Converter\Variableconverter::getName
     */
    public function testThatHaveExpectedName()
    {
        $this->assertEquals('variable', $this->converter->getName());
    }

    /**
     * @covers Zikula\Tools\Converter\Variableconverter::getDescription
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
