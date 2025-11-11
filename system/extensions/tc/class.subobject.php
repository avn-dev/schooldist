<?php

class Ext_TC_SubObject extends Ext_TC_Basic {
	
	/**
	 * Liefert den Absender für SMS
	 * 
	 * Solange SMSKAUFEN verwendet wird, MUSS das Format folgendes sein:
	 *	[0-9]{1,16} ODER
	 *  [A-Za-z0-9]{1,11}
	 * 
	 * …oder als RegEx direkt:
	 *	#^([a-z0-9]{1,11}|[0-9]{1,16})$#i
	 * 
	 * @see Ext_TC_Communication_SMS_Gateway::checkSender()
	 * @return string
	 */
	public function getSmsSenderName()
	{
		return 'Fidelo';
	}
	
	private static $aInstance = null;
	
	static protected $sClassName = 'Ext_TC_SubObject';
	
	protected $_sTable = 'test_subobject';

	protected $_aJoinTables = array(
		'communication_emailsignatures' => array(
			'table' => 'tc_objects_emailsignatures',
			'primary_key_field' => 'object_id',
		)
	);

	/**
	 * Returns the instance of an object by data ID
	 *
	 * @param int $iDataId
	 * @return <object>
	 */
	public static function getInstance($iDataId = 0) {

		//$sClass = get_called_class();
		$sClass = self::$sClassName;

		// Wenn neuer Eintrag, immer direkt neues Objekt zurückgeben
		if($iDataId == 0) {
			return new $sClass($iDataId);
		}

		if(!isset(self::$aInstance[$sClass][$iDataId])) {
			#try {
				self::$aInstance[$sClass][$iDataId] = new $sClass($iDataId);
			#} catch(Exception $e) {
				error(print_r($e, 1));
			#}
		}

		return self::$aInstance[$sClass][$iDataId];

	}

	public function getCorrespondenceLanguages() {

		$aLanguages = Ext_TC_Language::getSelectOptions();

		// Auswahl für Core begrenzen! ~dg
		$aAllow = array('de', 'en');
		foreach($aLanguages as $sIso => $sLang) {
			if(!in_array($sIso, $aAllow)) {
				unset($aLanguages[$sIso]);
			}
		}

		return $aLanguages;
	}

	public function getLanguages(){
		return false;
	}
	
	public function getEmailAccount() {
		return 0;
	}
	
}