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
use Zikula\Tools\Converter\CommentConverter;
use Zikula\Tools\ConverterAbstract;

/**
 * @author sankara <sankar.suda@gmail.com>
 */
class CommentconverterTest extends \PHPUnit_Framework_TestCase
{
    protected $converter;

    public function setUp()
    {
        $this->converter = new CommentConverter();
    }

    /**
     * @covers       Zikula\Tools\Converter\CommentConverter::convert
     * @dataProvider Provider
     */
    public function testThatIfIsConverted($smarty, $twig)
    {

        // Test the above cases
        $this->assertSame($twig,
            $this->converter->convert($this->getFileMock(), $smarty)
        );

    }

    public function Provider()
    {
        return array(
            array(
                '{* foo *}',
                '{# foo #}'
            )
        );
    }

    /**
     * @covers Zikula\Tools\Converter\Commentconverter::getName
     */
    public function testThatHaveExpectedName()
    {
        $this->assertEquals('comment', $this->converter->getName());
    }

    /**
     * @covers Zikula\Tools\Converter\Commentconverter::getDescription
     */
    public function testThatHaveDescription()
    {
        $this->assertNotEmpty($this->converter->getDescription());
    }

    private function getFileMock()
    {
        return $this->getMockBuilder('\SplFileInfo')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
