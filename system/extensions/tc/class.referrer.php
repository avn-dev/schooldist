<?php

/**
 * @property int $id
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property int $active
 * @property string $valid_until (DATE)
 * @property int $creator_id
 * @property int $editor_id
 * @property int $position
 * @property int $textfields
 * @param array $tc_r_i18n
 * @param array $objects
 */
abstract class Ext_TC_Referrer extends Ext_TC_Basic {

	protected $_sTable = 'tc_referrers';

	protected $_sTableAlias = 'tc_r';

	protected $_aFormat = array(
		'valid_until' => array(
			'format' => 'DATE'
		)
	);

	protected $_aJoinTables = array(
		'tc_r_i18n' => array(
			'table' => 'tc_referrers_i18n',
	 		'foreign_key_field' => array('language_iso', 'name'),
	 		'primary_key_field' => 'referrer_id',
		)
	);

	protected $_aJoinedObjects = array(
		'fields'=>array(
			'class'=>'Ext_TC_Referrer_Field',
			'key'=>'referrer_id',
			'type'=>'child',
			'on_delete' => 'cascade'
		)
	);	

	/**
	 * Prüfen, ob Objekt noch benutzt wird
	 *
	 * @return bool
	 */
	public abstract function checkUse();

	public function validate($bThrowExceptions = false) {

		if(
			$this->active == 0 &&
			$this->checkUse()
		) {
			return ['tc_r.id' => ['REFERRER_IN_USE']];
		}

		return parent::validate($bThrowExceptions);

	}

    public function manipulateSqlParts(&$aSqlParts, $sView = null) {

   		parent::manipulateSqlParts($aSqlParts, $sView);

   		$sForeignKeyField = $this->_aJoinTables['objects']['foreign_key_field'];

   		$aSqlParts['select'] .= ",
   			GROUP_CONCAT(DISTINCT `objects`.`{$sForeignKeyField}`) AS `subobjects`
   		";

   	}

	/**
	 * Abgeleitete save()-Methode in der die Daten geprüft werden, die abgespeichert 
	 * werden sollen (i18n-Felder abhängig von Büro-Auswahl; JoinedObjectChilds abhängig von Checkbox)
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
	
		$aTempJoin = $this->tc_r_i18n;
		
		// übrig gebliebene Sprachen rauswerfen		
		foreach($aAgencyLanguages as $sIso => $aTemp){
			
			foreach($aTempJoin as $iKey => $aData){
				
				if($aData['language_iso'] == $sIso){
					unset($aTempJoin[$iKey]);
				}
				
			}
			
		}
		
		$this->tc_r_i18n = $aTempJoin;

		// wenn es sich nicht um ein Textfeld handelt die Daten aus JoinedObject
		// rauswerfen
		
		if($this->textfields == '0'){
			$this->_aJoinedObjectChilds['fields'] = array();
			$this->cleanJoinedObjectChilds('fields');
		}

		return parent::save($bLog);
	}

	public function getName($sLanguage = '') {
		return $this->getI18NName('tc_r_i18n', 'name', $sLanguage);
	}

	/**
	 * baut ein Array mit den Sprachen der Agentur und den Büros, die diese Sprache
	 * ausgewählt haben, zusammen, damit sie mit js ausgeblendet werden können
	 * @return array
	 */		
	public static function getSubObjectsLanguages($sAlias){
		
		$aReturn = array();
		
		$aSubObjects = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getSubObjects', array(true));
		$aLanguages	= Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getLanguages', array(true));
		$aTempLanguages = $aLanguages;
		
		$aTemp = array();
		
		// alle SubObjects durchlaufen und deren Sprache über getLanguages() holen
		foreach($aSubObjects as $iId => $sValue){
			
			$oObject = Ext_TC_Factory::executeStatic('Ext_TC_SubObject', 'getInstance', array($iId));
			$aObjectLanguages = $oObject->getLanguages();
			
			//Sprachen die benutzt werden rauswerfen, um nicht ausgewählte Sprachen 
			//zu filtern, da diese mit berücksichtigt werden müssen
			foreach($aObjectLanguages as $sIso){
				$aTemp[$sIso][] = $iId;
				unset($aTempLanguages[$sIso]);				
			}
			
		}
		
		//nicht benutzte Sprachen mit einem leeren Eintrag anhängen, damit sie
		//über js ausgeblendet werden
		if(!empty($aTempLanguages)){
			foreach($aTempLanguages as $sIso => $sTempValue){
				$aTemp[$sIso] = array('dummy');
			}
		}
		
		//Array mit der id des Divs ($aTempReturn['id']) und den ids der Büros, die 
		//diese Sprache ausgewählt haben ($aTempReturn['on_values'])
		foreach($aTemp as $sIso => $aObjects){
			$aTempReturn = array();

			if(isset($aLanguages[$sIso])){
				$aTempReturn['id'] = 'i18n_container_name_'.$sAlias.'_'.$sIso;
				$aTempReturn['on_values'] = $aObjects;
			
				$aReturn[] = $aTempReturn;
			}
		}
		
		
		return $aReturn;
		
	}

    /**
  	 * Array mit verfügbaren Feldern für Select
  	 *
  	 * @param bool $bForSelect
  	 * @return array
  	 */
  	public static function getFieldList($bForSelect = false) {
  		return [];
  	}
	
}
