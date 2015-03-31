<?php
namespace Import;

use Import\DrupalNode\DrupalNode;
use Import\SavePlanetVOElements\SavePlanetVOElements;
use Import\PlanetVoElements\PlanetVoElements;
/**
 * Import data
 *
 * @author Raphael GONCALVES <contact@raphael-goncalves.fr>
 */
class Importer
{
    private $ftp;
    
    private $elements;
    
    private $DrupalNode;
    
    public $date;
    
    private $code_PVO;
    
    public function __construct()
    {
        $this->DrupalNode = new DrupalNode();
        
        $this->date = date('Y-m-d-h-i');
    }
    
    /**
     * Set FTP access to the planet VO files
     * 
     * @param \Import\FtpAccess\FtpAccess $ftp
     */
    public function setTransport(FtpAccess\FtpAccess $ftp, $code_PVO)
    {
        $this->ftp = $ftp;
        $this->code_PVO = $code_PVO;
    }
    
    /**
     * Set parsed XML elements
     * 
     * @param \Import\PlanetVoElements\PlanetVoElements $planetVoElements
     */
    public function importElements(PlanetVoElements\PlanetVoElements $planetVoElements = null)
    {
        if(is_null($planetVoElements)){
            $this->importFiles();

            $file_datas = __DIR__ . '/../tmp-import/' . $this->code_PVO.'_'.$this->date.'.xml';

            $planetVoElements = new PlanetVoElements($file_datas);
        }

        $this->elements = $planetVoElements->getParsedElements();
    }
    
    /**
     * Save the parsed Elements
     */
    public function saveElements($nb_element_to_save = null)
    {        
        if($this->unzipFile()){
            $photo_files = __DIR__ . '/../tmp-import/photos/photos.txt';
        }

        $nb_element = 0;

        foreach($this->elements->children() as $element){

            if(!is_null($nb_element_to_save) && $nb_element >= $nb_element_to_save) continue;
            
            $node = $this->searchExisting((string)$element->IdentifiantVehicule);
            
            $saver = new SavePlanetVOElements($element, $photo_files, $this->ftp, $node);

            $saver->AddNodeElements();
            $idNode = $saver->save();

            $nb_element++;
        }
        return true;
    }
    
    /**
     * Import the needed files.
     */
    private function importFiles()
    {
        if(is_null($this->ftp))
            throw new \Exception('You need a valid FTP access.');
        
        $changeDir = $this->ftp->goDir('datas');
        
        $this->ftp->get($this->code_PVO.'.xml', __DIR__ . '/../tmp-import/', $this->code_PVO.'_'.$this->date.'.xml');
        $get = $this->ftp->get('photos.txt.zip', __DIR__ . '/../tmp-import/', 'photos_'.$this->date.'.txt.zip');
        //var_dump($get);
        if(!file_exists(__DIR__ . '/../tmp-import/photos_'.$this->date.'.txt.zip') || !file_exists(__DIR__ . '/../tmp-import/' . $this->code_PVO.'_'.$this->date.'.xml')){
            throw new \Exception('Error during the file\'s import.');
        }
        
        return true;
    }
    
    /**
     * Unzip the zipped photo file
     * 
     * @return boolean
     * @throws \Exception
     */
    private function unzipFile()
    {
        $zip = new \ZipArchive;
        $res = $zip->open(__DIR__ . '/../tmp-import/photos_'.$this->date.'.txt.zip');
        if ($res === TRUE) {
          $zip->extractTo(__DIR__ . '/../tmp-import/photos/');
          $zip->close();
          return true;
        } else {
          throw new \Exception('Can\'t unzip the photo file.');
        }
    }
    
    /**
     * Search an existing node by planetVO's ID
     * 
     * @param int $car_id
     * @return object
     */
    private function searchExisting($car_id)
    {
        $cars = $this->DrupalNode->findBy(array('field_id_vehicule_voplanet' => $car_id), 'voiture');
        
        if($cars){
            $car = current($cars);
            return $this->DrupalNode->findByID($car->nid);
        }
        
        return $this->DrupalNode->createNode('voiture');
    }
    
}
