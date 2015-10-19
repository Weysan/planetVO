<?php
namespace Import\Modele;

use Import\DrupalNode\DrupalNode;
/**
 * Va lier un node drupal à un modèle.
 * Si le modele n'est pas encore créé, on le créé.
 *
 * @author Raphael
 */
class Modele
{
    
    private $modele;
    
    public function __construct()
    {
        
    }
    
    public function link($id_node)
    {
        $oDrupalNode = new DrupalNode();
        
        $node = node_load($id_node);
        if(!$node) throw new \Exception('can\'t find the node.');
        
        $this->getModele($node->field_famille[LANGUAGE_NONE][0]['value']);
        
        
        $this->modele->title = $node->field_famille[LANGUAGE_NONE][0]['value'];
        
        
        foreach($this->modele->field_blocs_voiture_et_dossiers[LANGUAGE_NONE] as $target){
            if( $target['target_id'] == $id_node )
                return true;
        }
        
        $this->modele->field_blocs_voiture_et_dossiers[LANGUAGE_NONE][]['target_id'] = $id_node;
        
        $oDrupalNode->save($this->modele);
        return true;
        
    }
    
    private function getModele($name_modele)
    {
        $query = new \EntityFieldQuery();
         
        $entities = $query->entityCondition('entity_type', 'node');
        
        $entities->entityCondition('bundle', 'modeles_de_voiture');
        
        $entities->propertyCondition('title', $name_modele);
        
        $entities->range(0,1);
        
        $result = $entities->execute();
        
        if (isset($result['node'])) {
            $items_nids = array_keys($result['node']);
            $items = entity_load('node', $items_nids);

            return $this->modele = current($items);
        } else {
            $oDrupalNode = new DrupalNode();
            $node = $oDrupalNode->createNode('modeles_de_voiture');
            
            return $this->modele = $node;
        }
    }
    
}
