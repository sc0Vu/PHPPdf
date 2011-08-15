<?php

use PHPPdf\Document;
use PHPPdf\Glyph\Page;
use PHPPdf\Glyph\Container;
use PHPPdf\Formatter\ConvertAttributesFormatter;

class ConvertAttributesFormatterTest extends PHPUnit_Framework_TestCase
{
    private $formatter;
    private $document;

    public function setUp()
    {
        $this->formatter = new ConvertAttributesFormatter();
        $this->document = new Document();
    }

    /**
     * @test
     */
    public function percentageConvert()
    {
        $page = new Page();
        $glyph = new Container(array('width' => 200, 'height' => 100));

        $child = new Container(array('width' => '70%', 'height' => '50%'));
        $glyph->add($child);
        $page->add($glyph);

        $glyph->setHeight(100);
        $glyph->setWidth(200);

        $this->formatter->format($child, $this->document);

        $this->assertEquals(200*0.7, $child->getWidth());
        $this->assertEquals(100*0.5, $child->getHeight());
    }

    /**
     * @test
     * @dataProvider autoMarginConvertProvider
     */
    public function autoMarginConvert($glyphWidth, $parentWidth, $expectedMarginLeft, $expectedMarginRight)
    {
        $glyph = new Container(array('width' => $glyphWidth));
        $glyph->setWidth($glyphWidth);
        $glyph->setMargin(0, 'auto');

        $mock = $this->getMock('\PHPPdf\Glyph\Page', array('getWidth', 'setWidth'));
        $mock->expects($this->atLeastOnce())
             ->method('getWidth')
             ->will($this->returnValue($parentWidth));
             
        if($glyphWidth > $parentWidth)
        {
            $mock->expects($this->once())
                 ->method('setWidth')
                 ->with($glyphWidth);
        }

        $mock->add($glyph);

        $this->formatter->format($glyph, $this->document);

        $this->assertEquals($expectedMarginLeft, $glyph->getMarginLeft());
        $this->assertEquals($expectedMarginRight, $glyph->getMarginRight());
    }
    
    public function autoMarginConvertProvider()
    {
        return array(
            array(100, 200, 50, 50),
            array(200, 100, 0, 0), // if child is wider than parent, margins should be set as "0" and parent width should be set as child width
        );
    }

    /**
     * @test
     */
    public function colorConvert()
    {
        $page = new Page();
        $glyph = new Container(array('color' => '#ffffff'));
        $page->add($glyph);

        $this->formatter->format($glyph, $this->document);

        $this->assertTrue($glyph->getAttribute('color') instanceof PHPPdf\Engine\Color);
    }

    /**
     * @test
     */
    public function fontConvert()
    {
        $fontStub = 'fontStub';

        $documentMock = $this->getMock('PHPPdf\Document', array('getFont'));
        $documentMock->expects($this->once())
                ->method('getFont')
                ->with('font')
                ->will($this->returnValue($fontStub));

        $glyphMock = $this->getMock('PHPPdf\Glyph\Container', array('setFontType', 'getFontType', 'getParent'));
        $glyphMock->expects($this->once())
                  ->method('setFontType')
                  ->with($fontStub);
        $glyphMock->expects($this->once())
                  ->method('getFontType')
                  ->will($this->returnValue('font'));
        $glyphMock->expects($this->any())
                  ->method('getParent')
                  ->will($this->returnValue(new Page()));

        $this->formatter->format($glyphMock, $documentMock);
    }
}