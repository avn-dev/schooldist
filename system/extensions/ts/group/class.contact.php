<?php

/**
 * Wird NUR bei Anfragen verwendet
 */
class Ext_TS_Group_Contact extends Ext_TS_Contact {

	const FLAGS = [
		'guide',
		'free_all',
		'free_course',
		'free_accommodation',
		'free_course_fee',
		'free_accommodation_fee',
		'free_transfer',
	];

//	/**
//	 * @var Ext_TS_Group
//	 */
//	protected $_oGroup = null;
	
	public function __construct($iDataID = 0, $sTable = null) {

		parent::__construct($iDataID, $sTable);

//		$this->_aJoinTables['guide_flags'] = array(
//			'table'				=> 'ts_groups_contacts_flags',
//			'foreign_key_field'	=> '',
//	 		'primary_key_field'	=> 'contact_id',
//			'autoload'			=> true
//		);

		$this->_aJoinTables['group'] = array(
			'table'				=> 'ts_groups_to_contacts',
			'foreign_key_field'	=> 'group_id',
	 		'primary_key_field'	=> 'contact_id',
			'class'				=> Ext_TS_Enquiry_Group::class,
			'readonly'			=> true,
			'static_key_fields' => ['type' => 'inquiry'],
		);

		// Die alte Tabelle hatte nur FKs und flag, aber kein value-Feld, also musste bei 0 alles gelöscht werden.
		// Dass dieses Speziel-Konstrukt irgendwann kaputtgehen würde, war eigentlich klar.
		foreach (self::FLAGS as $flag) {
			$this->aDetailPropertyWhitelist[] = $flag;
		}

	}
	
	/*public function __get($sField){
		
		Ext_Gui2_Index_Registry::set($this);
		
		$mValue = '';
		switch($sField){
			case 'guide':
			case 'free_all':
			case 'free_course':
			case 'free_accommodation':
			case 'free_course_fee':
			case 'free_accommodation_fee':
			case 'free_transfer':
				// Guide Flags auslesen
				$oGroup	= $this->getGroup();
				$mValue = $this->_getGroupFlag($sField, $oGroup);
				break;
			default:
				$mValue = parent::__get($sField);
		}
		
		return $mValue;
	}
	
	public function __set($sField, $mValue){
		
		switch($sField){
			case 'guide':
			case 'free_all':
			case 'free_course':
			case 'free_accommodation':
			case 'free_course_fee':
			case 'free_accommodation_fee':
			case 'free_transfer':	
				// Guide Flags speichern
				$oGroup			= $this->getGroup();
				$this->_setGroupFlag($sField, $mValue, $oGroup);
				break;
			default:
				parent::__set($sField, $mValue);
		}
		
		
	}

	protected function _getGroupFlag($sField, $oGroup){
		
		$aFlags			= $this->guide_flags;
		foreach($aFlags as $iKey => $aData){

			// Flag suchen
			// wenn keine Gruppe aktuell vorhanden ist es ein neuer Eintrag
			// generell dürfen keine Flags von anderen Gruppen verändert werden
			if(
				$aData['flag']			== $sField &&
				$aData['group_id']		== (int)$oGroup->id // entweder beides 0 oder jeweils eine ID
			){
				return 1;
			}
		}
		
		return 0;
	}

	public function getGroupFlags(){
		$aFlags = $this->guide_flags;
		
		// Formatieren
		$aBack = array();
		foreach($aFlags as $aFlag){
			$aBack[$aFlag['flag']] = 1;
		}
		
		return $aBack;
	}

	protected function _setGroupFlag($sField, $mValue, $oGroup){
		
		$aFlags			= $this->guide_flags;
		$bFound			= false;
		$bCheckFlags	= false;
			
		foreach($aFlags as $iKey => $aData){

			// Flag suchen
			// wenn keine Gruppe aktuell vorhanden ist es ein neuer Eintrag
			// generell dürfen keine Flags von anderen Gruppen verändert werden
			if(
				$aData['contact_id']	== $this->id &&
				$aData['flag']			== $sField &&
				$aData['group_id']		== (int)$oGroup->id // entweder beides 0 oder jeweils eine ID
			){
				$bFound = true;
			}

			// Flag löschen
			if(
				$bFound &&
				$mValue == 0
			){
				// Flag löschen
				unset($aFlags[$iKey]);
				break;
			}

		}
		
		// Flag einfügen
		if(
			!$bFound &&
			$mValue == 1
		){
			$aFlags[] = array(
				'group_id'		=> (int)$oGroup->id, 
				'flag'			=> $sField
			);
			
			$bCheckFlags = true;
		}

		$this->guide_flags = $aFlags;
		
	}
	
	public function setGroup(Ext_TS_Group_Interface $oGroup){
		$this->_oGroup = $oGroup;
	}

	public function checkGroupFlags($oGroup){
	
		// Wenn der Free-all Flag gesetzt ist, dann dürfen sonst keine Positions-flags gespeichert werden
		if($this->free_all == 1){
			$this->_setGroupFlag('free_course', 0, $oGroup);
			$this->_setGroupFlag('free_accommodation', 0, $oGroup);
			$this->_setGroupFlag('free_course_fee', 0, $oGroup);
			$this->_setGroupFlag('free_accommodation_fee', 0, $oGroup);
			$this->_setGroupFlag('free_transfer', 0, $oGroup);
		}
		
		// Gruppen ID setzten
		$aFlags = $this->guide_flags;
		foreach($aFlags as $iKey => $aFlag){
			if($aFlag['group_id'] <= 0){
				$aFlags[$iKey]['group_id'] = (int)$oGroup->id;
			}
		}
		$this->guide_flags = $aFlags;
		
	}

	public function getGroup(){
		
		if($this->_oGroup){
			return $this->_oGroup;
		}
		
		// Das geht leider nicht da es hier sonst eine endlosschleift gibt
		#$aGroups = $this->getJoinTableObjects('group');
		
		$aGroups = $this->group;
		
		$oGroup = null;
		// Es darf nur eine Gruppe geben
		if(!empty($aGroups)){
			$oGroup = Ext_TS_Group::getInstance((int)reset($aGroups));
		} 
		return $oGroup;
	}
	
	protected function _getType()
	{
		return 'traveller';
	}

	protected function _generateLastnameByGroup($oGroup){
		$sGroupNameShort = '';
		if($oGroup){
			$sGroupNameShort = $oGroup->getShortName();	
		}	
		return $sGroupNameShort;
	}

	protected function _generateFirstnameByGroup($oGroup){	 
		static $iCounter = 0;	
		
		$iCounter++;
		
		$sFirstname = $iCounter;

		return $sFirstname;
	}
	
	public function checkData($oGroup){
	
		if((int)$this->id <= 0){
			if($this->lastname == ''){
				$this->lastname = $this->_generateLastnameByGroup($oGroup);
			}
			
			if($this->firstname == ''){
				$this->firstname = $this->_generateFirstnameByGroup($oGroup);
			}

			if($this->birthday == ''){
//				$this->birthday = '1970-01-01';
			}

			if($this->gender <= 0){
				$this->gender = 1;
			}
		}
	}
	
	public function save($bLog = true){
		$oGroup = $this->getGroup();
		$this->checkData($oGroup);
		$this->checkGroupFlags($oGroup);
		return parent::save($bLog);
	}*/
	
}