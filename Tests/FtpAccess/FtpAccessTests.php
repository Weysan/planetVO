<?php
use Import\FtpAccess\FtpAccess;
/**
 * Unit test for FtpAccess class
 *
 * @author Raphael GONCALVES <contact@raphael-goncalves.fr>
 */
class FtpAccessTests extends PHPUnit_Framework_TestCase
{
    private $host;
    
    private $login;
    
    private $pwd;
    
    /**
     * Initiate the ftp credentials to use
     */
    public function setUp()
    {
        $this->host = '';
        
        $this->login = '';
        
        $this->pwd = '';
    }
    
    /**
     * Delete all files after all unit tests
     */
    public function tearDown()
    {
        $files = glob(__DIR__.'/../../tmp-import/*'); // get all file names
        foreach($files as $file){ // iterate files
          if(is_file($file))
            unlink($file); // delete file
        }
    }
    
    public function testFtpConnection()
    {
        $ftp = new FtpAccess($this->host, $this->login, $this->pwd);
        
        $this->assertTrue($ftp->connect());
    }
    
    /**
     * @expectedException              Exception
     * @expectedExceptionMessageRegExp #an host to your FTP access#
     */
    public function testExceptionFtpConnection()
    {
        $ftp = new FtpAccess(null);
    }
    
    public function testGiveAFileFromFtp()
    {
        $file_to_give = 'tt4.xml';
        
        /* the folder is empty */
        $this->assertFalse(file_exists(__DIR__ . '/../../tmp-import/' .$file_to_give));
        $this->assertFalse(file_exists(__DIR__ . '/../../tmp-import/test2.txt'));
        
        /* connection */
        $ftp = new FtpAccess($this->host, $this->login, $this->pwd);
        $ftp->connect();
        
        $ftp->goDir('datas');
        
        $this->assertTrue($ftp->get($file_to_give, __DIR__ . '/../../tmp-import/'));
        $this->assertTrue(file_exists( __DIR__ . '/../../tmp-import/'.$file_to_give));
        
        $file_content = file_get_contents(__DIR__ . '/../../tmp-import/'.$file_to_give);
        
        
        $this->assertRegExp('/voiture/i', $file_content);
        
        /* another name file */
        $this->assertTrue($ftp->get($file_to_give, __DIR__ . '/../../tmp-import/', 'test2.txt'));
        $this->assertTrue(file_exists( __DIR__ . '/../../tmp-import/test2.txt'));
        
        $file_content_file_2 = file_get_contents(__DIR__ . '/../../tmp-import/test2.txt');
        
        
        $this->assertRegExp('/voiture/i', $file_content_file_2);
    }
}
