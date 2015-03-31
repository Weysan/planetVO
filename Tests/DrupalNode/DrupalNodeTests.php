<?php
/* Use drupal functions for unit tests */
define('DRUPAL_ROOT', '/home/raphael/dev-foundry/dallard/www');
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Bootstrap Drupal.
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);


use Import\DrupalNode\DrupalNode;
/**
 * Description of DrupalNodeTests
 *
 * @author Raphael GONCALVES <contact@raphael-goncalves.fr>
 */
class DrupalNodeTests extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $file2 = __DIR__ . '/Jellyfish.jpg';
        /* delete all file */
        $uri = 'public://tests/'.basename($file2);
        $files = file_load_multiple(array(), array('uri' => $uri));
        if(current($files)){
            drupal_unlink($uri);
            file_delete(current($files), true);
        }
    }
    
    /* delete all content type elements from data base after a test */
    public function tearDown()
    {
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
    }
    
    public function testCreateNode()
    {
        $drupal_node = new DrupalNode();
        
        $node = $drupal_node->createNode('voiture');
        $node->title = 'Test voiture';
        $node->field_id_vehicule_voplanet[LANGUAGE_NONE][0]['value'] = 'testId';
        $node->field_nom[LANGUAGE_NONE][0]['value'] = 'Test nom';
        
        
        $nid = $drupal_node->save($node);
        
        $this->assertTrue($nid > 0);
        
        /* the node is found */
        $node_test = node_load($nid);
        
        $this->assertTrue($node_test->title == $node->title);
        
        /* find a node by other */
        $return = $drupal_node->findBy(array('field_id_vehicule_voplanet' => 'testId'), 'voiture', 1);
        
        $return = current($return);
        
        $this->assertTrue($return->title == $node->title);
        
        /* delete the node */
        $drupal_node->delete($nid);
        
        $this->assertFalse(node_load($nid));
        
    }
    
    public function testModifyNode()
    {
        $drupal_node = new DrupalNode();
        
        $node = $drupal_node->createNode('voiture');
        $node->title = 'Test voiture';
        $node->field_id_vehicule_voplanet[LANGUAGE_NONE][0]['value'] = 'testId';
        $node->field_nom[LANGUAGE_NONE][0]['value'] = 'Test nom';
        
        
        $nid = $drupal_node->save($node);
        
        $this->assertTrue($nid > 0);
        
        /* the node is found */
        $node_test = node_load($nid);
        
        $this->assertTrue($node_test->title == $node->title);
        
        /* update the node */
        $node_test->title = 'Test voiture 2 modif';
        
        $nid2 = $drupal_node->save($node_test);
        
        $node_test_2 = node_load($nid);
        
        $this->assertTrue($node_test_2->title == $node_test->title);
        
        $this->assertFalse($node_test_2->title == $node->title);
    }
    
    public function testUploadFile()
    {
        $drupal_node = new DrupalNode();
        
        $file2 = __DIR__ . '/Jellyfish.jpg';
        
        $this->assertTrue(file_exists($file2));
        
        $file = $drupal_node->uploadFile($file2, 'tests/');

        $this->assertTrue(isset($file['fid']));
        
        $this->assertTrue(!empty($file));
    }
}
