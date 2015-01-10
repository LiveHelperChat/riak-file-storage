<?php 

/**
 * Plugins which overrides almoust all callbacks related to file storage and stores files directly on Riak
 * This plugin can be also used as basic start for other cloud storage support. Like Amazon S3 etc.
 * It's compatible with Automated hosting plugin and was intentionaly done for future.
 * 
 * */

class erLhcoreClassExtensionRiakfilestorage {

	public function __construct() {
		
	}
	
	private $riakHost = '';
	private $riakPort = '';
	
	private $imagesBucket = 'images';
	private $filesBucket = 'fileschat';
	private $formsBucket = 'filesform';
			
	public function run() {		
		
		$settings = include 'extension/riakfilestorage/settings/settings.ini.php';
				
		$this->registerAutoload();
		
		$dispatcher = erLhcoreClassChatEventDispatcher::getInstance();
		
		$this->riakHost = $settings['host'];
		$this->riakPort = $settings['port'];
		
		$this->imagesBucket = $settings['images_bucket'];
		$this->filesBucket = $settings['files_bucket'];
		$this->formsBucket = $settings['form_bucket'];
		
		erLhcoreClassSystem::instance()->WWWDirImages = $settings['riak_images_host'];

		/**
		 * User events
		 * */
		$dispatcher->listen('user.edit.photo_store',array($this,'storeUserPhoto'));	
		$dispatcher->listen('user.edit.photo_resize_150',array($this,'userPhotoResize'));	
		$dispatcher->listen('user.remove_photo',array($this,'userRemovePhoto'));
		
		/**
		 * Files events
		 * */
		$dispatcher->listen('file.uploadfile.file_path',array($this,'filePath'));
		$dispatcher->listen('file.uploadfileadmin.file_path',array($this,'filePath'));
		$dispatcher->listen('file.new.file_path',array($this,'filePath'));
		$dispatcher->listen('file.uploadfile.file_store',array($this,'fileStore'));
		$dispatcher->listen('file.uploadfileadmin.file_store',array($this,'fileStore'));
		$dispatcher->listen('file.file_new_admin.file_store',array($this,'fileStore'));
		$dispatcher->listen('file.remove_file',array($this,'fileRemove'));		
		$dispatcher->listen('file.download',array($this,'fileDownload'));	

		/**
		 * Store screenshot functionality
		 * */
		$dispatcher->listen('file.storescreenshot.screenshot_path',array($this,'screenshotPath'));	
		$dispatcher->listen('file.storescreenshot.store',array($this,'fileStore'));	

		/**
		 * Theme listeners
		 * */
		// Themes listeners
		$dispatcher->listen('theme.edit.logo_image_path',array($this,'themeStoragePath'));
		$dispatcher->listen('theme.edit.need_help_image_path',array($this,'themeStoragePath'));
		$dispatcher->listen('theme.edit.offline_image_path',array($this,'themeStoragePath'));
		$dispatcher->listen('theme.edit.online_image_path',array($this,'themeStoragePath'));	
		$dispatcher->listen('theme.edit.operator_image_path',array($this,'themeStoragePath'));	
		$dispatcher->listen('theme.edit.copyright_image_path',array($this,'themeStoragePath'));	
		$dispatcher->listen('theme.edit.popup_image_path',array($this,'themeStoragePath'));
		$dispatcher->listen('theme.edit.close_image_path',array($this,'themeStoragePath'));
		$dispatcher->listen('theme.edit.restore_image_path',array($this,'themeStoragePath'));
		$dispatcher->listen('theme.edit.minimize_image_path',array($this,'themeStoragePath'));
		
		$dispatcher->listen('theme.temppath',array($this,'themeStoragePath'));
		
		// Theme storage listeners
		$dispatcher->listen('theme.edit.store_logo_image',array($this,'themeStoreFile'));
		$dispatcher->listen('theme.edit.store_need_help_image',array($this,'themeStoreFile'));
		$dispatcher->listen('theme.edit.store_offline_image',array($this,'themeStoreFile'));
		$dispatcher->listen('theme.edit.store_online_image',array($this,'themeStoreFile'));		
		$dispatcher->listen('theme.edit.store_copyright_image',array($this,'themeStoreFile'));		
		$dispatcher->listen('theme.edit.store_operator_image',array($this,'themeStoreFile'));
		$dispatcher->listen('theme.edit.store_popup_image',array($this,'themeStoreFile'));
		$dispatcher->listen('theme.edit.store_close_image',array($this,'themeStoreFile'));
		$dispatcher->listen('theme.edit.store_restore_image',array($this,'themeStoreFile'));
		$dispatcher->listen('theme.edit.store_minimize_image',array($this,'themeStoreFile'));
				
		// Themes files removement
		$dispatcher->listen('theme.edit.remove_logo_image',array($this,'themeFileRemove'));
		$dispatcher->listen('theme.edit.remove_need_help_image',array($this,'themeFileRemove'));
		$dispatcher->listen('theme.edit.remove_offline_image',array($this,'themeFileRemove'));
		$dispatcher->listen('theme.edit.remove_online_image',array($this,'themeFileRemove'));		
		$dispatcher->listen('theme.edit.remove_operator_image',array($this,'themeFileRemove'));		
		$dispatcher->listen('theme.edit.remove_copyright_image',array($this,'themeFileRemove'));		
		$dispatcher->listen('theme.edit.remove_popup_image',array($this,'themeFileRemove'));
		$dispatcher->listen('theme.edit.remove_close_image',array($this,'themeFileRemove'));
		$dispatcher->listen('theme.edit.remove_restore_image',array($this,'themeFileRemove'));
		$dispatcher->listen('theme.edit.remove_minimize_image',array($this,'themeFileRemove'));
		
		// Download events				
		$dispatcher->listen('theme.download_image.logo_image',array($this,'themeFileDownload'));
		$dispatcher->listen('theme.download_image.need_help_image',array($this,'themeFileDownload'));
		$dispatcher->listen('theme.download_image.offline_image',array($this,'themeFileDownload'));
		$dispatcher->listen('theme.download_image.online_image',array($this,'themeFileDownload'));
		$dispatcher->listen('theme.download_image.operator_image',array($this,'themeFileDownload'));
		$dispatcher->listen('theme.download_image.copyright_image',array($this,'themeFileDownload'));
		$dispatcher->listen('theme.download_image.popup_image',array($this,'themeFileDownload'));
		$dispatcher->listen('theme.download_image.close_image',array($this,'themeFileDownload'));
		$dispatcher->listen('theme.download_image.restore_image',array($this,'themeFileDownload'));
		$dispatcher->listen('theme.download_image.minimize_image',array($this,'themeFileDownload'));
				
		// Forms module listener
		$dispatcher->listen('form.fill.file_path',array($this,'formFillPath'));
		$dispatcher->listen('form.fill.store_file',array($this,'formStoreFile'));		
		$dispatcher->listen('form.file.download',array($this,'formFileDownload'));		
		$dispatcher->listen('form.remove_file',array($this,'formFileRemove'));	
	}
	
	public function registerAutoload()
	{
		spl_autoload_register(array($this, 'autoload'), true, false);
	}
	
	/**
	 * Extension autoload
	 * */
	public function autoload($className)
    {
        if (0 === strpos($className, 'Basho\Riak\\')) {
            $parts = explode('\\', substr($className, strlen('Basho\Riak\\')));
            
            $dirBase = array(
            		'extension',
            		'riakfilestorage',
            		'vendor',
            		'basho',
            		'riak',
            		'src',
            		'Basho',
            		'Riak',
            );
            
            $filepath = implode(DIRECTORY_SEPARATOR, $dirBase).DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $parts).'.php';
                        
            if (is_file($filepath)) {
                require($filepath);
            }
            
        } elseif ($className == 'erLhcoreClassRiak') {
        	include_once 'extension/riakfilestorage/classes/lhriak.php';
        }
    }
	
	/**
	 * Helper function
	 * */
	function get_mime($file) {
		if (function_exists("finfo_file")) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
			$mime = finfo_file($finfo, $file);
			finfo_close($finfo);
			return $mime;
		} else if (function_exists("mime_content_type")) {
			return mime_content_type($file);
		} else if (!stristr(ini_get("disable_functions"), "shell_exec")) {
			// http://stackoverflow.com/a/134930/1593459
			$file = escapeshellarg($file);
			$mime = shell_exec("file -bi " . $file);
			return $mime;
		} else {
			return false;
		}
	}

	/**
	 * Downloads theme attribute as binnary, in most cases it's image content
	 * */
	public function themeFileDownload($params)
	{	
		$client = erLhcoreClassRiak::instance($this->riakHost,$this->riakPort);
		$bucket = $client->bucket($this->imagesBucket);
		$item = $bucket->getBinary($params['theme']->$params['attr']);
	
		if ($item->exists == 1) {
			return array('status' => erLhcoreClassChatEventDispatcher::STOP_WORKFLOW, 'filedata' => $item->data);
		}
	
		return false;
	}
	
	/**
	 * curl -i http://localhost:8098/buckets/images/keys?keys=true
	 * */
	public function themeStoreFile($params)
	{
		$theme = $params['theme'];
	
		if (file_exists($params['file_path']) ) {
			$client = erLhcoreClassRiak::instance($this->riakHost,$this->riakPort);
				
			$client->storeBinary($params['name'], $params['file_path'], $this->imagesBucket, $this->get_mime($params['file_path']));
				
			unlink($params['file_path']); // We do not need anymore original file
	
			$theme->$params['path_attr'] = '';
		}
	}
	
	/**
	 * Store original file in temporary folder also
	 * */
	public function themeStoragePath($params) {
		$params['dir'] = 'var/tmpfiles/';
		return array('status' => erLhcoreClassChatEventDispatcher::STOP_WORKFLOW);
	}
	
	/**
	 *  curl -i http://localhost:8098/buckets/themesfiles/keys?keys=true
	 * */
	public function themeFileRemove($params)
	{
		$client = erLhcoreClassRiak::instance($this->riakHost,$this->riakPort);
		$client->deleteBinary($params['name'],$this->imagesBucket);
	}
	
	/**
	 * Downloads filled form attribute
	 * */
	public function formFileDownload($params)
	{
		$client = erLhcoreClassRiak::instance($this->riakHost,$this->riakPort);
		$bucket = $client->bucket($this->formsBucket);
		$item = $bucket->getBinary($params['filename']);
	
		if ($item->exists == 1) {
			return array('status' => erLhcoreClassChatEventDispatcher::STOP_WORKFLOW, 'filedata' => $item->data);
		}
	
		return false;
	}

	/**
	 * Removes filled form attribute
	 * */
	public function formFileRemove($params)
	{
		$client = erLhcoreClassRiak::instance($this->riakHost,$this->riakPort);
		$client->deleteBinary($params['filename'],$this->formsBucket);
	}
	
	/**
	 * Forms module file storage override
	 * 
	 * curl -i http://localhost:8098/buckets/filesform/keys?keys=true
	 * */
	public function formStoreFile($params)
	{
		if ( file_exists($params['file_params']['filepath'] . $params['file_params']['filename']) ) {
			$client = erLhcoreClassRiak::instance($this->riakHost,$this->riakPort);
				
			$client->storeBinary($params['file_params']['filename'], $params['file_params']['filepath'] . $params['file_params']['filename'], $this->formsBucket, $this->get_mime($params['file_params']['filepath'] . $params['file_params']['filename']));
				
			unlink($params['file_params']['filepath'] . $params['file_params']['filename']); // We do not need anymore original file
		
			$params['file_params']['filepath'] = '';
		}
	}
	
	/**
	 * Store form files in temporary folder
	 * */
	public function formFillPath($params) {
		$params['dir'] = 'var/tmpfiles/';
		return array('status' => erLhcoreClassChatEventDispatcher::STOP_WORKFLOW);
	}
	
	/**
	 * Store screenshot at temporary folder
	 * */
	public function screenshotPath($params) {
		$params['path'] = 'var/tmpfiles/';
		return array('status' => erLhcoreClassChatEventDispatcher::STOP_WORKFLOW);
	}

	/**
	 * Download chat file
	 * */
	public function fileDownload($params)
	{
		$file = $params['chat_file'];
		
		$client = erLhcoreClassRiak::instance($this->riakHost,$this->riakPort);		
		$bucket = $client->bucket($this->filesBucket);
		$item = $bucket->getBinary($file->name);
		
		if ($item->exists == 1) {			
			return array('status' => erLhcoreClassChatEventDispatcher::STOP_WORKFLOW, 'filedata' => $item->data);
		}
		
		return false;
	}
	
	/**
	 * 
	 * Store chat file
	 * curl -i http://localhost:8098/buckets/fileschat/keys?keys=true
	 * */
	public function fileStore($params)
	{
		$file = $params['chat_file'];
		
		if (file_exists($file->file_path_server)) {
			$client = erLhcoreClassRiak::instance($this->riakHost,$this->riakPort);			
			$client->storeBinary($file->name, $file->file_path_server, $this->filesBucket, $file->type);
			unlink($file->file_path_server); // We do not need anymore original file
			
			$file->file_path = '';
			$file->saveThis();
		}		
	}
	
	/**
	 * Delete from bucket on file removement
	 * */
	public function fileRemove($params)
	{
		$client = erLhcoreClassRiak::instance($this->riakHost,$this->riakPort);
		$client->deleteBinary($params['chat_file']->name,$this->filesBucket);
	}
	
	/**
	 * store all files in tmpfolder
	 * */
	public function filePath($params) {
		$params['path'] = 'var/tmpfiles/';
	}
	
	/**
	 * Resizes user profile photo and stores in riak
	 * 
	 * curl -O http://localhost:8098/buckets/images/keys/135ab4e6236793a19c67e7f651c0903d.png
	 * curl -i http://localhost:8098/buckets/images/keys?keys=true
	 * */
	public function userPhotoResize($params){
		$response = array('status' => erLhcoreClassChatEventDispatcher::STOP_WORKFLOW);
		
		$client = erLhcoreClassRiak::instance($this->riakHost,$this->riakPort);
		
		$tmpPath = $client->storeTempBinary($params['user']->filename, 'var/tmpfiles/');		
		erLhcoreClassImageConverter::getInstance()->converter->transform( 'photow_150', $tmpPath, $tmpPath );

		// Overwrite existing
		$client->storeBinary($params['user']->filename, $tmpPath, $this->imagesBucket, $params['mime_type']);
		
		// Delete file
		unlink($tmpPath);
		
		return $response;
	}
	
	/**
	 * Removes user photo from Riak
	 * */
	public function userRemovePhoto($params)
	{
		$client = erLhcoreClassRiak::instance($this->riakHost,$this->riakPort);
		$client->deleteBinary($params['user']->filename, $this->imagesBucket);
	}
	
	/**
	 * Stores user photo
	 * */
	public function storeUserPhoto($params) {
		$response = array('status' => erLhcoreClassChatEventDispatcher::STOP_WORKFLOW);
		
		$file = qqFileUploader::upload($_FILES,'UserPhoto','var/tmpfiles/');
		if ( !empty($file["errors"]) ) {
			$response['errors'] = $file["errors"];
			return $response;
		}
		
		$client = erLhcoreClassRiak::instance($this->riakHost,$this->riakPort);
		$client->storeBinary($file['data']['filename'], $file['data']['dir'] . $file['data']['filename'], $this->imagesBucket, $file['data']['mime_type']);

		// We do not store anything there, because we will use RIAK path from configuration
		$file['data']['dir'] = '';
		
		// delete file
		unlink($file['data']['dir'] . $file['data']['filename']);
		
		return array('status' => erLhcoreClassChatEventDispatcher::STOP_WORKFLOW, 'data' => $file);
	}
	
	
}


