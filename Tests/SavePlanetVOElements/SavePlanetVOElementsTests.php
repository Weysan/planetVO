<?php
/* Use drupal functions for unit tests */
define('DRUPAL_ROOT', '/home/raphael/dev-foundry/dallard/www');
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Bootstrap Drupal.
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

use Import\SavePlanetVOElements\SavePlanetVOElements;
/**
 * Description of SavePlanetVOElements
 *
 * @author Raphael
 */
class SavePlanetVOElementsTests extends PHPUnit_Framework_TestCase
{
    
    private $xml_file;
    
    private $parsedElements;
    
    public function tearDown()
    {
        
        
        /* delete all car entities */
        $query = new EntityFieldQuery();
        $query
          ->entityCondition('entity_type', 'node', '=')
          ->propertyCondition('type', 'voiture', '=');


        $result = $query->execute();
        
        foreach($result as $entities){
            foreach($entities as $entity){
                node_delete($entity->nid);
            }
        }
        
        /* delete all car photo entities */
        $query = new EntityFieldQuery();
        $query
          ->entityCondition('entity_type', 'node', '=')
          ->propertyCondition('type', 'photo_voiture', '=');


        $result = $query->execute();
        
        foreach($result as $entities){
            foreach($entities as $entity){
                $photo_car = node_load($entity->nid);
                $uri = $photo_car->field_photo['und'][0]['uri'];

                /* delete all files attached */
                $files = file_load_multiple(array(), array('uri' => $uri));
                if(current($files)){
                    drupal_unlink($uri);
                    file_delete(current($files), true);
                }
                
                
                node_delete($entity->nid);
            }
        }
    }
    
    public function setUp()
    {
        $this->xml_file = __DIR__ . "/example_planet_vo.xml";
        
        $this->parsedElements = new SimpleXMLElement(file_get_contents($this->xml_file));
    }
    
    public function testImportDatas()
    {
        $ftp = new \Import\FtpAccess\FtpAccess('', '', '');
        $ftp->connect();
        
        $iteration = 0;
        foreach($this->parsedElements as $k => $voitureElement){
            echo $iteration;
            $iteration++;
            
            $saved_elements = new SavePlanetVOElements($voitureElement, __DIR__ . '/photos.txt', $ftp);
            
            $saved_elements->AddNodeElements();
            $idNode = $saved_elements->save();
            
            $node_test = node_load($idNode);            

            $this->assertEquals((string)$voitureElement->Modele, $node_test->field_modele[LANGUAGE_NONE][0]['value']);
            $this->assertEquals((string)$voitureElement->NumeroPolice, $node_test->field_numero_de_police[LANGUAGE_NONE][0]['value']);
            $this->assertEquals((string)$voitureElement->IdentifiantVehicule, $node_test->field_id_vehicule_voplanet[LANGUAGE_NONE][0]['value']);
            $this->assertEquals((string)$voitureElement->ReferenceVehicule, $node_test->field_reference[LANGUAGE_NONE][0]['value']);
        }

        $nbElementXml = count($this->parsedElements);
        
        $query = new EntityFieldQuery();
        $query
          ->entityCondition('entity_type', 'node', '=')
          ->propertyCondition('type', 'voiture', '=');


        $result = $query->execute();

        $nbInsertCars = count($result['node']);
        
        $this->assertEquals($nbElementXml, $nbInsertCars);
        
    }
}
