<?php
namespace Import\PlanetVoElements;

/**
 * Will parse the XML datas from PlanetVO
 *
 * @author Raphael GONCALVES <contact@raphael-goncalves.fr>
 */
class PlanetVoElements
{
    private $file;
    
    private $xml;
    
    private $parsed;
    
    public function __construct($file)
    {
        if(!file_exists($file))
            throw new \Exception('The file for parsing doesn\'t exist.');
        
        $this->file = $file;
        
        $this->parseXml($this->getContent());
    }
    
    /**
     * Get the file XML content
     * 
     * @return string
     */
    private function getContent()
    {
        return $this->xml = file_get_contents($this->file);
    }
    
    /**
     * Parse the XML
     * 
     * @param string $content
     * @return \SimpleXMLElement
     */
    private function parseXml($content)
    {
        return $this->parsed = new \SimpleXMLElement($content);
    }
    
    /**
     * Get XML content

     * @return string
     */
    public function getXml()
    {
        return $this->xml;
    }
    
    /**
     * Get SimpleXMLElement
     * 
     * @return \SimpleXMLElement
     */
    public function getParsedElements()
    {
        return $this->parsed;
    }
}
