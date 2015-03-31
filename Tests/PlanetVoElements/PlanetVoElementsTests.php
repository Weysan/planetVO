<?php
use Import\PlanetVoElements\PlanetVoElementsTests;
/**
 * Description of PlanetVoElements
 *
 * @author Raphael GONCALVES <contact@raphael-goncalves.fr>
 */
class PlanetVoElementsTests extends PHPUnit_Framework_TestCase
{
    private $file;
    
    public function setUp()
    {
        $this->file = __DIR__ . '/example_planet_vo.xml';
    }
    
    /**
     * @expectedException \Exception
     * @expectedExceptionMessageRegExp #The file for parsing#
     */
    public function testParseElementsException()
    {
        $parser = new PlanetVoElements(__DIR__.'/machin.xml');
    }
    
    public function testParseElements()
    {
        $parser = new PlanetVoElements($this->file);
        
        $content = $parser->getXml();
        
        $this->assertNotFalse($content);
        $this->assertRegExp('/<Stock>/i', $content);
        $this->assertRegExp('/<vehicule>/i', $content);
        
        $parsed = $parser->getParsedElements();
        
        $this->assertNotFalse($parsed);
        $this->containsOnlyInstancesOf('\SimpleXMLElement', $parsed);
    }
}
