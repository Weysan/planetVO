<?php
namespace Import\SavePlanetVOElements;

use Import\DrupalNode\DrupalNode;
use Import\Modele\Modele;
/**
 * Save a planetVO element in a drupal content type
 *
 * @author Raphael GONCALVES <contact@raphael-goncalves.fr>
 */
class SavePlanetVOElements
{
    private $element;
    
    private $node;
    
    private $photo_files;
    
    private $ftp;
    
    private $ftp_current_dir;
    
    public function __construct(\SimpleXMLElement $element, $photo_files,  \Import\FtpAccess\FtpAccess $ftp = null, $existing_node = null)
    {
        $this->element = $element;
        
        if(!file_exists($photo_files))
            throw new \Exception('You have to specify an existing photo file.');
        
        $this->photo_files = $photo_files;
        
        //var_dump($this->element);
        $drupalnode = new DrupalNode();
        
        if(is_null($existing_node))
            $this->node = $drupalnode->createNode('voiture');
        else 
            $this->node = $existing_node;
        
        $this->ftp = $ftp;
    }
    
    /**
     * Insert or update a node with XML datas
     */
    public function AddNodeElements()
    {
        $this->node->title = $this->element->Marque . ' - ' . $this->element->Modele;
        var_dump($this->node->title);
        foreach($this->element->children() as $xmlLibelle => $xmlValue){
            $field = $this->convertXmlNode($xmlLibelle, $xmlValue);
            $test = (string)$xmlValue;
            if(!$field) continue;
            if($field == 'field_couleur' OR $field == 'field_famille'){
              // Trim + mise en minuscule sur champ 
              $this->node->{$field}[LANGUAGE_NONE][0]['value'] = trim(strtolower((string)$xmlValue));
            }
            else {
              $this->node->{$field}[LANGUAGE_NONE][0]['value'] = (string)$xmlValue;
            }
            
        }
    }
    
    /**
     * Save the node
     */
    public function save()
    {
        error_log('Save planetVO node : '.$this->node->title);
        
        $drupalnode = new DrupalNode();
        
        drupal_alter('voiture', $this->node); //permet à drupal de modifier du contenu custom

        $nid = $drupalnode->save($this->node);
        $node = node_load($nid);
        drupal_alter('voiture_after_save', $node); //permet à drupal de modifier du contenu custom
        return $nid;
    }
    
    /**
     * Import pictures from FTP
     * 
     * @param string $photos
     */
    private function importPhotos($photos, \Import\FtpAccess\FtpAccess $ftp = null)
    {
        error_log('Import voiture photo');
        $field = "field_photos";
        
        $aPhotos = explode('|', $photos[0]);
        //var_dump($this->photo_files); die();
        /* file photo */
        $f = fopen($this->photo_files, 'r');
        
        $this->node->field_photos[LANGUAGE_NONE] = array();
        var_dump($this->node->field_photos[LANGUAGE_NONE]);
        var_dump('----');
        while(($line = fgets ($f, 4096))!== false){
            
            
            $cols = explode("\t", $line);
            
            if(in_array($cols[0], $aPhotos)){
                $this->upload_drupal_file($line, $ftp);
            }
            
        }
        var_dump($this->node->field_photos[LANGUAGE_NONE]);
        fclose($f);
    }
    
    /**
     * Upload a picture in drupal
     * And update the node
     * 
     * @param string $photo_file_line
     * @param \Import\FtpAccess\FtpAccess $ftp
     * @return mixed
     * @throws \Exception
     */
    private function upload_drupal_file($photo_file_line, \Import\FtpAccess\FtpAccess $ftp = null)
    {
        error_log('Upload a file');
        
        $cols = explode("\t", $photo_file_line);
        
        //this image already saved and uploaded?
        $md5_file = $cols[2];
        $file_name = $cols[0];
        $file_name_dir = $cols[1];
        $drupal_node = new DrupalNode();
        
        $results = $drupal_node->findBy(array('field_md5_import' => $md5_file), 'photo_voiture', 1);

        if(!$results){
            //copy the file from FTP server to tmp directory
            if(!is_null($ftp)){
                $tmp_dir = __DIR__ . '/../../tmp-import/';
                //get the distant file
                $dir_ftp = str_replace(basename($file_name_dir), '', $file_name_dir);
                
                if($this->ftp_current_dir != $dir_ftp){
                    $this->ftp_current_dir = $dir_ftp;
                    $racine = $ftp->goDir('/');

                    $aDirectory = explode('/', $dir_ftp);
                    foreach($aDirectory as $dir){
                        if(empty($dir)) continue;
                        $changedir = $ftp->goDir($dir);
                    }
                }
                $file_dl = $ftp->get(basename($file_name_dir), $tmp_dir, null, FTP_BINARY);

                //upload the file
                $uploaded_file = $drupal_node->uploadFile(realpath($tmp_dir . '/'.  basename($file_name_dir)), 'dallard/planetVO/');
                var_dump($uploaded_file);
                $id_pic = $this->savePhotoVoiture($uploaded_file, $cols);

                //insert in node
                $this->node->field_photos[LANGUAGE_NONE][]['target_id'] = $id_pic;
                
            } else {
                throw new \Exception('You have to specify a FTP access');
            }
        } else {
            error_log('image already downloaded');
            $result = current($results);
            $this->node->field_photos[LANGUAGE_NONE][]['target_id'] = $result->nid;
            
            return $result->nid;
        }
    }
    
    /**
     * create the picture associated
     * 
     * @param array $file_info
     * @param array $cols
     * @return integer
     */
    private function savePhotoVoiture($file_info, $cols = array())
    {
        error_log('Save photo voiture');
        
        $drupalnode = new DrupalNode();
        $node_photo = $drupalnode->createNode('photo_voiture');
        
        $node_photo->field_md5_import[LANGUAGE_NONE][0]['value'] = $cols[2];
        $node_photo->field_photo[LANGUAGE_NONE][0] = array(
                                                        'fid' => $file_info['fid'],
                                                        'display' => 1,
                                                        'description' => '',
                                                      );
        $node_photo->title = $cols[0];
        
        
        drupal_alter('photo_voiture', $node_photo); //permet à drupal de modifier du contenu custom
        
        return $drupalnode->save($node_photo);
    }
    
    /**
     * Convert simplexml node into fields name
     *  
     * @param string $xmlLibelle
     * @return boolean|string
     */
    private function convertXmlNode($xmlLibelle, $xmlValue = null)
    {
        
        //error_log('Convert XML to node field : ' . $xmlLibelle);
        
        switch($xmlLibelle){
            case "CodePvo":
                return 'field_code_pvo';
                break;
            case "SocieteNom":
                return 'field_nom';
                break;
            case "SocieteMarque":
                return "field_marque_societe";
                break;
            case "SocieteAdresse":
            case "SocieteAdresseSuite":
            case "SocieteCodePostal":
            case "SocieteVille":
                
                $this->node->field_adresse[LANGUAGE_NONE][0]['value'] .= " ".(string)$xmlValue;
                
                return false;
                break;
            case "SocieteContact":
                return 'field_nom_contact';
                break;
            case "SocieteTelephone":
            case "SocieteTelephone2":
                $this->node->field_telephone_contact[LANGUAGE_NONE][]['value'] = (string)$xmlValue;
                return false;
                break;
            case "SocieteEmail":
                return 'field_mail';
                break;
            case "IdentifiantVehicule":
                return 'field_id_vehicule_voplanet';
                break;
            case "ReferenceVehicule":
                return 'field_reference';
                break;
            case "NumeroPolice":
                return 'field_numero_de_police';
                break;
            case "StatutStock":
                return 'field_statut_stock_select';
                break;
            case "Annee":
                return 'field_annee';
                break;
            case "Date1Mec":
                return 'field_date1mec';
                break;
            case "GenreCode":
                return 'field_genre_libelle';
                break;
            case "Marque":
                return 'field_marque_voiture';
                break;
            case "Famille":
                return 'field_famille';
                break;
            case "Version":
                return 'field_version';
                break;
            case "Modele":
                return 'field_modele';
                break;
            case "TypeMine":
                return 'field_type_mine';
                break;
            case "EnergieCode":
                return 'field_energie_libelle_select';
                break;
            case "PuissanceFiscale":
                return 'field_puissance_fiscale';
                break;
            case "PuissanceReelle":
                return 'field_puissance_reelle';
                break;
            case "Cylindree":
                return 'field_cylindree';
                break;
            case "NbPlaces":
                return 'field_nombre_de_place';
                break;
            case "NbPortes":
                return 'field_nombre_de_porte';
                break;
            case "Kilometrage":
                return 'field_kilometrage';
                break;
            case "KmGarantie":
                return 'field_km_garanti';
                break;
            case "Couleur":
                return 'field_couleur';
                break;
            case "BoiteCode":
                return 'field_boite';
                break;
            case "NbRapports":
                return 'field_nombre_de_rapport';
                break;
            case "PrixVenteTTC":
                return 'field_prix_de_vente_ttc';
                break;
            case "PremiereMain":
                $testval = (string)$xmlValue;
                if($testval == 'VRAI')
                    $this->node->field_premiere_main[LANGUAGE_NONE][0]['value'] = 1;
                return false;
                break;
            case "GarantieLibelle":
                return 'field_garantie_libelle';
                break;
            case "DestinationLibelle":
                return 'field_destination_libelle';;
                break;
            case "CategorieLibelle":
                return 'field_categorie_libelle';
                break;
            case "EquipementsSerieEtOption":
                $values = explode('|', $xmlValue);
                $this->node->field_equipements_et_options[LANGUAGE_NONE] = array();
                foreach($values as $k => $value){
                    if(empty($value)) continue;

                    $equipement_id = $this->saveOptionAndEquipement((string)$value);

                    if(is_array($equipement_id))
                        $equipement_id = current($equipement_id);
                    
                    if(!is_int($equipement_id) && !is_string($equipement_id))
                        $equipement_id = $equipement_id->nid;
                    
                    $this->node->field_equipements_et_options[LANGUAGE_NONE][]['target_id'] = $equipement_id;
                }
                return false;
                break;
            case "SiteLibelle":
                return 'field_site_libelle';
                break;
            case "LieuLibelle":
                return 'field_lieu_libelle';
                break;
            case "Photos":
                $testval = (string)$xmlValue;
                if(!empty($testval)){
                    $this->importPhotos($xmlValue, $this->ftp);
                }
                else {
                  
                }
                return false;
                break;
            case "Co2":
                return 'field_emission_de_co2';
                break;
            case "Poids":
                return 'field_poids';
                break;
            case "PTAC":
                return 'field_ptac';
                break;
            case "PTRA":
                return 'field_ptra';
                break;
            case "ChargeUtile":
                return 'field_charge_utile';
                break;
            case "Longueur":
                return 'field_longueur';
                break;
            case "Largeur":
                return 'field_largeur';
                break;
            case "Empattement":
                return 'field_empattement';
                break;
            case "Hauteur":
                return 'field_hauteur';
                break;
            case "Volume":
                return 'field_volume';
                break;
            case "Silhouette":
                return 'field_silhouette';
                break;
            case "DerniereVisiteTechnique":
                return 'field_derniere_visite_technique';
                break;
            case "DateControlographe":
                return 'field_date_controlographe';
                break;
            default:
                return false;
                break;
        }
    }
    
    /**
     * Save an option
     * 
     * @param string $name
     * @return integer
     */
    private function saveOptionAndEquipement($name)
    {
        //error_log('save Option and Equipment :' . $name);
        
        $node = new DrupalNode();

        $id_equipment = $this->findEquipementIdByName($name);
        //var_dump($id_equipment);
        if(!$id_equipment){
            $node_equipment = $node->createNode('equipements_et_options');

            $node_equipment->title = $name;


            drupal_alter('equipement_voiture', $node_equipment); //permet à drupal de modifier du contenu custom

            $saved = $node->save($node_equipment);

            return $saved;
        } else {
            return $id_equipment;
        }
    }
    
    /**
     * Find an equipment with its name and return its node's id
     * 
     * @param string $name
     * @return boolean|integer
     */
    private function findEquipementIdByName($name)
    {
        $node = new DrupalNode();
        $equipment_id = $node->findBy(array('title' => $name), 'equipements_et_options');

        if($equipment_id)
            return $equipment_id;
        else
            return false;
    }
}