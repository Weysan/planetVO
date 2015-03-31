<?php
namespace Import\FtpAccess;

/**
 * Description of FtpAccess
 *
 * @author Raphael GONCALVES <contact@raphael-goncalves.fr>
 */
class FtpAccess
{
    private $host;
    
    private $port;
    
    private $login;
    
    private $pwd;
    
    private $ftpstream;
    
    /**
     * check variables and create a connexion
     * 
     * @param string $host
     * @param string $login can be null
     * @param string $pwd can be null
     * @param int $port Default 21
     */
    public function __construct($host, $login = null, $pwd = null, $port = 21)
    {
        if(empty($host) || is_null($host)){
            throw new \Exception('You have to specify an host to your FTP access.');
        }
        
        $this->host = $host;
        
        $this->login = $login;
        
        $this->pwd = $pwd;
        
        $this->port = $port;
        
        //ftp connection strem
        $this ->ftpstream = ftp_connect($host, $port);
        
        if(!$this ->ftpstream){
            throw new \Exception('The server cannot be called by your FTP credentials.');
        }
    }
    
    /**
     * Get a distant file
     * 
     * @param string $filename
     * @param string $localfilename
     * @param int $mode
     * @throws \Exception
     * @return boolean
     */
    public function get($filename, $dirtodownload, $localfilename = null, $mode = null)
    {
        if(empty($filename) || is_null($filename))
            throw new \Exception('You have to specify the distant file to get.');
        
        if(is_null($localfilename)) $localfilename = $dirtodownload . $filename;
        else $localfilename = $dirtodownload . $localfilename;
        
        if(is_null($mode))
            $mode = FTP_ASCII;
        
        return ftp_get($this->ftpstream, $localfilename, $filename, $mode);
    }
    
    /**
     * Identification to an FTP access
     * 
     * @return boolean
     */
    public function connect()
    {
        return ftp_login($this->ftpstream, $this->login, $this->pwd);
    }
    
    /**
     * Change current directory
     * 
     * @param string $directory
     * @return type
     */
    public function goDir($directory)
    {
        return ftp_chdir($this->ftpstream, $directory);
    }
    
    /**
     * Close the FTP connection.
     */
    public function __destruct()
    {
        ftp_close($this->ftpstream);
    }
}
