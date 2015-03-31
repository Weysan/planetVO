<?php
/* Use drupal functions for unit tests */
define('DRUPAL_ROOT', '/home/raphael/dev-foundry/dallard/www');
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Bootstrap Drupal.
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

use Import\Importer;
use Import\FtpAccess\FtpAccess;
use Import\PlanetVoElements\PlanetVoElements;
/**
 * Description of ImporterTests
 *
 * @author Raphael GONCALVES <contact@raphael-goncalves.fr>
 */
class ImporterTests extends PHPUnit_Framework_TestCase
{
    
    private $host = '';
    
    private $login = '';
    
    private $pwd = '';
    
    /**
     * Delete all files after all unit tests
     * Delete all entities
     */
    public function tearDown()
    {
        $files = glob(__DIR__.'/../tmp-import/photos/*'); // get all file names
        foreach($files as $file){ // iterate files
          if(is_file($file))
            unlink($file); // delete file
        }
        
        rmdir(__DIR__.'/../tmp-import/photos/');
        
        $files = glob(__DIR__.'/../tmp-import/*'); // get all file names
        foreach($files as $file){ // iterate files
          if(is_file($file))
            unlink($file); // delete file
        }
        
        
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
    
    public function testImportDatas()
    {
        $ftpAccess = new FtpAccess($this->host, $this->login, $this->pwd);
        $ftpAccess->connect();
        
        $importer = new Importer();
        $importer->setTransport($ftpAccess, 'tt4');
        $importer->importElements();
        
        /* check file */
        $this->assertTrue(file_exists(__DIR__ . '/../tmp-import/photos_'.$importer->date.'.txt.zip'));
        $this->assertTrue(file_exists(__DIR__ . '/../tmp-import/tt4_'.$importer->date.'.xml'));
        
        $importer->saveElements(1);
        
        
        $query = new EntityFieldQuery();
        $query
          ->entityCondition('entity_type', 'node', '=')
          ->propertyCondition('type', 'voiture', '=');
        
        $result = $query->execute();

        $nbInsertCars = count($result['node']);
        
        $this->assertEquals(1, $nbInsertCars);
        
    }
 
}
