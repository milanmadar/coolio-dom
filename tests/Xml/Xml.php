<?php

namespace tests\Xml;

use Milanmadar\Dom\Dom;
use PHPUnit\Framework\TestCase;

class Xml extends TestCase
{
    public function testInvalidScheme_Load()
    {
        $src = file_get_contents('tests/Xml/files/invalid_missing_schema.xml');
        $dom = new Dom($src);
        $this->assertTrue(true);
    }

    public function testInvalidScheme_FindElem_Tag_lowercased()
    {
        $src = file_get_contents('tests/Xml/files/invalid_missing_schema.xml');
        $dom = new Dom($src);

        $el = $dom->elem(['obscollection'=>0]);

        $this->assertNotNull($el);
    }

    public function testInvalidScheme_FindElem_Tag_uppercased()
    {
        $src = file_get_contents('tests/Xml/files/invalid_missing_schema.xml');
        $dom = new Dom($src);

        $el = $dom->elem(['ObsCollection'=>0]);

        $this->assertNotNull($el);
    }

    public function testInvalidScheme_FindElem_TagAttrib()
    {
        $src = file_get_contents('tests/Xml/files/invalid_missing_schema.xml');
        $dom = new Dom($src);

        $el = $dom->elem(['JustForOurTest[attr1]'=>0]);

        $this->assertNotNull($el);
        $this->assertEquals("Im Here", $el->text());
        $this->assertEquals("hello", $el->attr('attr1'));
    }

    public function testInvalidScheme_FindElem_TagAttribAsteriskRight()
    {
        $src = file_get_contents('tests/Xml/files/invalid_missing_schema.xml');
        $dom = new Dom($src);

        $el = $dom->elem(['JustForOurTest[attr*]'=>0]);

        $this->assertNotNull($el);
        $this->assertEquals("Im Here", $el->text());
        $this->assertEquals("hello", $el->attr('attr1'));
    }

    public function testInvalidScheme_FindElem_TagAttribAsteriskLeft()
    {
        $src = file_get_contents('tests/Xml/files/invalid_missing_schema.xml');
        $dom = new Dom($src);

        $el = $dom->elem(['justforourtest[*attr1]'=>0]);

        $this->assertNotNull($el);
        $this->assertEquals("Im Here", $el->text());
        $this->assertEquals("hello", $el->attr('attr1'));
    }

    public function testInvalidScheme_FindElem_TagAttribAsteriskMiddle()
    {
        $src = file_get_contents('tests/Xml/files/invalid_missing_schema.xml');
        $dom = new Dom($src);

        $el = $dom->elem(['JustForOurTest[at*r1]'=>0]);

        $this->assertNotNull($el);
        $this->assertEquals("Im Here", $el->text());
        $this->assertEquals("hello", $el->attr('attr1'));
    }

    public function testInvalidScheme_FindElem_TagAttribValue()
    {
        $src = file_get_contents('tests/Xml/files/invalid_missing_schema.xml');
        $dom = new Dom($src);

        $el = $dom->elem(['JustForOurTest[attr1=bye]'=>0]);

        $this->assertNotNull($el);
        $this->assertEquals("Im Away", $el->text());
        $this->assertEquals("bye", $el->attr('attr1'));
    }

    public function testInvalidScheme_FindElem_TagAttribAsteriskRightValue()
    {
        $src = file_get_contents('tests/Xml/files/invalid_missing_schema.xml');
        $dom = new Dom($src);

        $el = $dom->elem(['JustForOurTest[attr*=bye]'=>0]);

        $this->assertNotNull($el);
        $this->assertEquals("Im Away", $el->text());
        $this->assertEquals("bye", $el->attr('attr1'));
    }

    public function testInvalidScheme_FindElem_TagAttribAsteriskLeftValue()
    {
        $src = file_get_contents('tests/Xml/files/invalid_missing_schema.xml');
        $dom = new Dom($src);

        $el = $dom->elem(['JustForOurTest[*ttr1=bye]'=>0]);

        $this->assertNotNull($el);
        $this->assertEquals("Im Away", $el->text());
        $this->assertEquals("bye", $el->attr('attr1'));
    }

    public function testInvalidScheme_FindElem_TagAttribAsteriskMiddleValue()
    {
        $src = file_get_contents('tests/Xml/files/invalid_missing_schema.xml');
        $dom = new Dom($src);

        $el = $dom->elem(['JustForOurTest[*ttr1=bye]'=>0]);

        $this->assertNotNull($el);
        $this->assertEquals("Im Away", $el->text());
        $this->assertEquals("bye", $el->attr('attr1'));
    }

    public function testInvalidScheme_FindElem_AttribColon()
    {
        $src = file_get_contents('tests/Xml/files/invalid_missing_schema.xml');
        $dom = new Dom($src);

        $el = $dom->elem(['Bulletin[gml:id]'=>0]);

        $this->assertNotNull($el);
        $this->assertEquals("YES", $el->attr('gml:id'));
    }

    public function testInvalidScheme_FindElem_AttribColonValue()
    {
        $src = file_get_contents('tests/Xml/files/invalid_missing_schema.xml');
        $dom = new Dom($src);

        $el = $dom->elem(['Bulletin[gml:id=YES]'=>0]);

        $this->assertNotNull($el);
        $this->assertEquals("YES", $el->attr('gml:id'));
    }

    public function testInvalidScheme_FindElem_Selfclosing_AttribColonValueHyphen()
    {
        $src = file_get_contents('tests/Xml/files/invalid_missing_schema.xml');
        $dom = new Dom($src);

        $el = $dom->elem(['locRef[xlink:href=THIS-and-that]'=>0]);

        $this->assertNotNull($el);
        $this->assertEquals("THIS-and-that", $el->attr('xlink:href'));
    }

    public function testInvalidScheme_FindElem_Selfclosing_AttribColonValueColon()
    {
        $src = file_get_contents('tests/Xml/files/invalid_missing_schema.xml');
        $dom = new Dom($src);

        $el = $dom->elem(['locRef[xlink:href=12:30]'=>0]);

        $this->assertNotNull($el);
        $this->assertEquals("12:30", $el->attr('xlink:href'));
    }

    public function testInvalidScheme_FindElem_Specialchars()
    {
        $src = file_get_contents('tests/Xml/files/invalid_missing_schema.xml');
        $dom = new Dom($src);

        $el = $dom->elem(['specialStuff[what=letters]'=>0]);

        $this->assertNotNull($el);
        $this->assertEquals("öüóőúéáű ÖÜÓŐÚÉÁŰ äÄß", $el->text());
    }

    public function testInvalidScheme_FindElem_Specialchars2()
    {
        $src = file_get_contents('tests/Xml/files/invalid_missing_schema.xml');
        $dom = new Dom($src);

        $el = $dom->elem(['specialStuff[what=chars]'=>0]);

        $this->assertNotNull($el);
        $this->assertEquals("AND & QUOTE \" APOST ' LESS < MORE > UMLAUT ä", $el->text());
    }

}