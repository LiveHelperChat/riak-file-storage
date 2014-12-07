<?php

class erLhcoreClassRiak extends Basho\Riak\Riak{

	private static $instance = null;
	
	public function __construct($host, $port) {		
		parent::__construct($host, $port);	
	}
	
    public static function instance($host, $port) {
    	if (is_null(self::$instance)) {
    		self::$instance = new self($host, $port);
    	}
    	return self::$instance;
    }
    
    public function storeBinary($fileName, $filePath, $bucket = 'images', $mimeType = 'image/jpeg')
    {
    	$bucket = $this->bucket($bucket);    	
    	$notification = $bucket->newBinary($fileName,file_get_contents($filePath),$mimeType);
    	$notification->store();
    }
    
    public function getBinary($fileName, $bucket = 'images')
    {
    	$bucket = $this->bucket($bucket);    	
    	return $bucket->getBinary($fileName);    	    	
    }
    
    public function deleteBinary($fileName, $bucket = 'images')
    {
    	$bucket = $this->bucket($bucket);   
    	$item = $bucket->get($fileName);
    	$item->delete();    	   	    	
    }
    
    public function storeTempBinary($fileName, $dir, $bucket = 'images'){
    	$bucket = $this->bucket($bucket);
    	$item = $bucket->getBinary($fileName);
    	
    	if ($item->exists == 1) {
    		file_put_contents($dir . $fileName, $item->data);
    		return $dir . $fileName;
    	} else {
    		return false;
    	}
    }
}


?>