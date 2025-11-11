<?php

class Ext_TC_User_Signature extends Ext_TC_Basic
{

	protected $_sTable = 'tc_system_user_signatures';

	protected $_sTableAlias = 'tc_sus';
	
	protected $_sPlaceholderClass = Ext_TC_Placeholder_Signature::class;
	
	protected $_aFormat = array(
		'email' => array(
			'validate' => 'MAIL'
		),
		/*'phone' => array(
			'validate' => 'PHONE'
		),
		'fax' => array(
			'validate' => 'PHONE'
		)*/ #1243
	);

	protected $_aJoinTables = array(
		'titles_i18n' => array(
			'table' => 'tc_system_user_signatures_titles',
			'primary_key_field' => 'user_id',
			'foreign_key_field' => array('language_iso', 'title'),
			'i18n' => true
		)
	);
	
	/**
	 * gibt einen Teil des Namens zurück (z.B. $sKey = 'lastname')
	 * @param string $sKey
	 * @return string 
	 */
	
	public function getUserInformation($sKey){

		$oUser = $this->getUser();

		$sReturn = '';
		switch($sKey){
			case 'firstname':
				if (empty($sReturn = $oUser->signature_firstname)) {
					$sReturn = $oUser->firstname;
				}
				break;
			case 'lastname':
				if (empty($sReturn = $oUser->signature_lastname)) {
					$sReturn = $oUser->lastname;
				}
				break;
		}
		
		return $sReturn;
		
	}

	/**
	 * Gibt den Titel des Benutzers in der Sprache zurück
	 * @param $sIso
	 * @return mixed
	 */
	public function getTitle($sIso)
	{
		return $this->getI18NName('titles_i18n', 'title', $sIso);
	}
	
	/**
	 * liefert die url der Signatur
	 * @return string 
	 */
	public function getSignatureUrl() {
		
		$oUser = $this->getUser();
		
		$sSignatur = $oUser->signature_img;

		if(!empty($sSignatur)) {
		
			$sSignatureDirectory = Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getSignatureDirectory');
			$sRootDirectory = Ext_TC_Util::getDocumentRoot(false);

			$sSignatur = $sSignatureDirectory.$sSignatur;

			if(is_file($sRootDirectory.$sSignatur)) {
				return $sSignatur;
			}

		}
	}
	
	public static function getSignatureDirectory() {
		
	}
	
	/**
	 * liefert die Signatur als Bild zurück
	 * @return string 
	 */
	public function getSignatureImg(){
		
		$mReplace = '';
		
		$sSignatur = $this->getSignatureUrl();
		if(!empty($sSignatur)) {
			$mReplace = '<img src="'.$sSignatur.'" border="0" />';
		}

		return $mReplace;
	}
	
	/**
	 * liefert die Signatur als Bild zurück
	 * @return string 
	 */
	public function getSignatureText() {
		return $this->getSignatureImg();
	}
	
	/**
	 * liefert das User Objekt
	 * @return object
	 */
	public function getUser() {
		$oUser = Ext_TC_Factory::getInstance('Ext_TC_User', $this->user_id);
		return $oUser;
	}
}
