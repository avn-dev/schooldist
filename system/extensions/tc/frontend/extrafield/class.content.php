<?php

/**
 * @param string $type
 * @param array $tc_fcc_i18n
 */
class Ext_TC_Frontend_Extrafield_Content extends Ext_TC_Basic {

	/** 
	 * The DB table name
	 * 
	 * @var string 
	 */
	protected $_sTable = 'tc_frontend_checkboxes_contents';

	/**
	 * Current class name
	 * 
	 * @var string 
	 */
	static protected $sClassName = 'Ext_TC_Frontend_Extrafield_Content'; 
	
	/**
	 * The DB table alias
	 * 
	 * @var string 
	 */
	protected $_sTableAlias = 'tc_fcc';

	/**
	 * Joined tables
	 * @var array 
	 */
	protected $_aJoinTables = array(
		'tc_fcc_i18n' => array(
			'table' => 'tc_frontend_checkboxes_contents_i18n',
	 		'foreign_key_field' => array('language_iso', 'content'),
	 		'primary_key_field' => 'content_id',
			'i18n' => true
		)
	);
	
	/**
	 * Abgeleitete save()-Methode in der die Daten geprüft werden, die abgespeichert 
	 * werden sollen (i18n-Felder abhängig von Büro-Auswahl)
	 * @param boolean $bLog 
	 */
	public function save($bLog = true) {
		
		/**
		 * Abhängig von der Auswahl des Büros nur die Sprachen speichern, die
		 * für die ausgewählten Büros gelten
		 */
		
		// alle Sprachen der Agentur
		$aAgencyLanguages = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getLanguages');
		
		// Alle ausgewählten Büros durchlaufen und deren Sprachen holen
		foreach($this->objects as $iId){
			$oObject = Ext_TC_Factory::executeStatic('Ext_TC_SubObject', 'getInstance', array($iId));
			$aLanguages = $oObject->getLanguages();
			
			// benutzte Sprachen der Büros rauswerfen
			foreach ($aLanguages as $sIso){
				unset($aAgencyLanguages[$sIso]);
			}
			
		}
		
		
		// Daten für JoinTables neue setzen
		
		$aTempJoin = $this->tc_fcc_i18n;
	
		// übrig gebliebene Sprachen rauswerfen		
		foreach($aAgencyLanguages as $sIso => $aTemp){
			
			foreach($aTempJoin as $iKey => $aData){
				
				if($aData['language_iso'] == $sIso){
					unset($aTempJoin[$iKey]);
				}
				
			}
			
		}
		
		$this->tc_fcc_i18n = $aTempJoin;

		parent::save($bLog);
	}	
	
}
?>
