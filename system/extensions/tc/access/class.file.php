<?php

class Ext_TC_Access_File { 
	
	static protected $oFile = NULL;
	protected $sLicence = '';
	
 	/**
 	 * Holt eine Instance
 	 * und läd die daten in den cache
 	 * @return Object
 	 */
	static public function getInstance($sLicence){
		
		$sClass = get_called_class();
		
		if (self::$oFile === NULL)  {
			self::$oFile = new $sClass($sLicence);
		}

		return self::$oFile;
		
	}
	
	/**
	 * @param string $sLicence
	 */
	public function __construct($sLicence) {		
		$this->sLicence = $sLicence;		
	}

	/**
	 * @return string
	 */
	protected function getAccessFilePath() {
		return Util::getDocumentRoot().'system/extensions/tc/access/class.data.php';
	}
	
	/**
	 * Get Access File Content from Access Server
	 * @return String
	 */
	public function getFileContent() {

		$sAccessServer = Ext_TC_Util::getAccessServer().'/system/extensions/thebing/access/file.php';

	    $sContent = '';
	    
		try {
			
			$sCMSLicence = $this->sLicence;
			$sHost = \Util::getHost();
			
			$sParam = 'task=getContent';
			$sParam .= '&cms_licence='.rawurlencode($sCMSLicence);
			$sParam .= '&host='.rawurlencode($sHost);
			$sParam .= '&php_version='.rawurlencode(Ext_TC_Util::getPHPVersion());

			$s = curl_init();
			
	        curl_setopt($s, CURLOPT_URL, $sAccessServer);
	        curl_setopt($s, CURLOPT_HTTPHEADER, array('Expect:'));
	        curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($s, CURLOPT_POST, true);
			// Ist sicherheitsrelevant, daher muss geprüft werden
			curl_setopt($s, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($s, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($s, CURLOPT_POSTFIELDS, $sParam);
			curl_setopt($s, CURLOPT_CONNECTTIMEOUT, 2);

	        $sContent = curl_exec($s);
	        $sStatus = curl_getinfo($s, CURLINFO_HTTP_CODE);

	        curl_close($s);
			
		} catch (Exception $e) {
			__pout($e);
			Ext_TC_Error::log('Error while reading remote Access File!', $e);
		}

		if(strpos($sContent, 'Error: Domain')){
			Ext_TC_Error::log('Error. Domain Check Failed!', $sContent);
			return '';
		}

		return $sContent;

	}
     
	/**
	 * Create the Access File
	 * @return unknown_type
	 */
	public function createFile(){

		$sContent = $this->getFileContent();

		if($sContent != "") {

			$sFilename = $this->getAccessFilePath();

			$rHandle = fopen($sFilename,'w+');

			if (fwrite($rHandle, $sContent) === false) {
				Ext_TC_Error::log('Unable to write the accessfile');
				return false;
			} 

		} else {
			Ext_TC_Error::log('Error Access File Content Lenght is zero!');
			// Muss ein Fehler auf dem Lizenzserver sein, daher return true
			return true;
		}

		return true;
	}     
     
	/**
	 * Create the Access File
	 * @return unknown_type
	 */
	public static function create($sCMSLicence){

	   $oFile = self::getInstance($sCMSLicence);
	   return $oFile->createFile();
	}
	
}