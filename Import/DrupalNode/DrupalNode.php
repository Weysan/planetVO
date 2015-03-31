<?php
namespace Import\DrupalNode;

/**
 * Access and modify/create a node
 *
 * @author Raphael GONCALVES <contact@raphael-goncalves.fr>
 */
class DrupalNode
{
    
    /**
     * create an empty node
     * 
     * @param string $node_type
     * @return object
     */
    public function createNode($node_type)
    {
        $node = new \stdClass();
        $node->type = $node_type;
        \node_object_prepare($node);
        
        return $node;
    }
    
    /**
     * Find a node by a node ID
     * 
     * @param int $node_id
     * @return object|false
     */
    public function findByID($node_id)
    {
        return \node_load($node_id);
    }
    
    /**
     * Find nodes with criterias
     * 
     * @param array $criteria
     * @param int $count
     * @return type
     */
    public function findBy(array $criteria, $node_type, $count = null)
    {
        return $this->createQuery($criteria, $count, $node_type);
    }
    
    /**
     * save a node
     * 
     * @param object $node
     * @return int
     */
    public function save($node)
    {
        if($node = \node_submit($node)){
            \node_save($node);
        
            $nid = $node->nid;

            return $nid;
        }
        
        return false;
    }
    
    /**
     * Delete a node
     * 
     * @param integer $nid
     */
    public function delete($nid)
    {
        \node_delete($nid);
    }
    
    /**
     * create a custom query
     * 
     * @param int $count
     * @param string $content_type
     * @param array $params
     * @return array|false
     */
    private function createQuery(array $params = array(), $count = null, $content_type = 'page')
    {
        $query = new \EntityFieldQuery();
         
        $entities = $query->entityCondition('entity_type', 'node');
        
        $entities->entityCondition('bundle', $content_type);
        
        foreach($params as $field => $value){
            $entities->fieldCondition($field, 'value', $value, '=');
        }
        
        if($count)
            $entities->range(0,$count);
        
        $result = $entities->execute();
        
        if (isset($result['node'])) {
            $items_nids = array_keys($result['node']);
            $items = entity_load('node', $items_nids);
            
            return $items;
        }
        
        return false;
    }
    
    /**
     * Upload a file
     * and return datas
     */
    public function uploadFile($file_path_upload, $public_directory = null)
    {
        $file_temp = file_get_contents($file_path_upload);
        
        /* cli debug */
        chdir(DRUPAL_ROOT);
        $file_temp = file_save_data($file_temp, 'public://' . $public_directory . basename($file_path_upload), FILE_EXISTS_REPLACE);
        
        return (array) $file_temp;
    }
}
