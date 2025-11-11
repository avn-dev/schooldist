<?php

class Ext_Thebing_Basic extends Ext_TC_Basic {

	/**
	 * @var string
	 */
	protected $_sEditorIdColumn = 'user_id';

	/**
	 * @var string|null
	 */
	protected $_sSchoolIdField = null;

	/**
	 * @var string|null
	 */
	protected $_sClientIdField = null;

	/**
	 * @return bool
	 */
	protected function _helperCheckData() {

		if (
			count((array)$this->_aData) > 0
		) {
			return true;
		}

		return false;
	}
	
	public function getField($sField) {
		if (array_key_exists($sField, (array)$this->_aData)) {
			return $this->_aData[$sField];
		}
		return false;
	}

	/**
	 * @param bool $bLog
	 * @return $this|Ext_TC_Basic
	 * @throws Exception
	 */
	public function save($bLog = true) {

		if(
			!array_key_exists('creator_id', self::$_aTable[$this->_sTable]) &&
			array_key_exists('active', self::$_aTable[$this->_sTable])
		) {

			//creator_id spalte erstellen
			$this->_oDb->field($this->_sTable, 'creator_id', "INT NOT NULL DEFAULT '0'", 'active', 'INDEX');

			#self::$_aTable[$this->_sTable]['creator_id'] = array();

			//cache leeren um die neue spalte zu cachen
			$sCacheKey = 'wdbasic_table_description_'.$this->_sTable;
			WDCache::delete($sCacheKey);

			//cache erneut bilden mit der neuen spalte
			$this->_getTableFields();

			$this->_aData['creator_id'] = 0;

		}

		parent::save($bLog);

		$aSchoolDataArrayList = $this->_getArrayListSchoolData(true);
		if($aSchoolDataArrayList) {
			WDCache::delete($aSchoolDataArrayList['cache_key']);
		}
		$aSchoolDataArrayList = $this->_getArrayListSchoolData(false);
		if($aSchoolDataArrayList) {
			WDCache::delete($aSchoolDataArrayList['cache_key']);
		}

		return $this;

	}

	/**
	 * @return string
	 */
	public static function getCommunicationTemplateKey($aItem = null, $sApplication = '') {
		$sKey = 'default';
		return $sKey;
	}

	/**
	 * Füllt Felder mit einem Zufallsstring, die als UNIQUE gekennzeichnet sind
	 *
	 * TODO: Gibt es dafür nicht irgendwo bereits eine Implementierung?
	 */
	protected function _fillUniqueFields() {

		foreach((array)$this->_aFormat as $sKey=>$aValue) {

			$aValue['validate'] = (array)$aValue['validate'];

			if(
				$aValue['required'] === true &&
				in_array('UNIQUE', $aValue['validate'])
			) {
				$mValue = $this->$sKey;
				if(empty($mValue)) {
					if(in_array('MAIL', $aValue['validate'])) {
						$this->$sKey = Ext_TC_Util::generateRandomString(16).'@noemail.thebing.com';
					} else {
						$this->$sKey = Ext_TC_Util::generateRandomString(16);
					}
				}

			}

		}

	}

	/**
	 * @TODO Diese Methode sollte am besten nicht existieren
	 * @deprecated
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getLanguage() {

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		if(!$oSchool->exist()) {
			return 'en';
		}

		return $oSchool->getLanguage();
	}

	/**
	 * Löscht einen Datensatz unwiederruflich
	 */
	public function remove() {
		
		$sSql = "
			DELETE FROM
				#table
			WHERE
				`id` = :id  AND
				`active` = 1 ";

		$aSql = array();
		$aSql['table'] = $this->_sTable;
		$aSql['id']	= (int)$this->id;

		DB::executePreparedQuery($sSql, $aSql);
			
	}
		
	/**
	 * cache key schulbasierend
	 * @param bool $bCheckValid
	 * @return false|string 
	 */
	protected function _getArrayListSchoolData($bCheckValid) {

		//Standard Key holen, in der Schulsoftware wird hier noch die client_id mitgebunden falls vorhanden
		$sCacheKey = $this->_getArrayListCacheKey($bCheckValid);
		
		//überprüfen ob objekt schulbasierend ist
		$sSchoolIdField = $this->_checkSchoolIdField();

		if($sSchoolIdField) {

			if(empty($this->_aData[$sSchoolIdField])) {
				$oSchool	= Ext_Thebing_School::getSchoolFromSession();
				$iSchoolId	= (int)$oSchool->id;	
			} else {
				//Falls man eine Arrayliste für eine bestimmte Schule haben will
				//und nicht die von der session, dann ins objekt reinsetzen
				$iSchoolId	= (int)$this->_aData[$sSchoolIdField];
			}
			
			if($iSchoolId > 0) {
				//cache key die schule anhängen
				$sCacheKey .= '_school_'.$iSchoolId;
			} else {
				//wenn keine school_id generiert werden konnte (z.B. in der all_schools Ansicht),
				//gib den nicht schulbasierenden array zurück
				return false;
			}

		} else {
			//wenn objekt nicht schulbasierend, gib den nicht schulbasierenden array zurück
			return false;
		}
		
		$aData = array(
			'cache_key'			=> $sCacheKey,
			'school_id'			=> $iSchoolId,
			'school_id_field'	=> $sSchoolIdField,
		);
		
		return $aData;
	}
	
	public function getArrayListJoinedSchool($bForSelect=true, $sNameField=null, Ext_Thebing_School $oSchool=null) {
		
		if(!isset($this->_aJoinTables['schools'])) {
			throw new RuntimeException(get_class($this).' has no jointable "schools"!');
		}

		if($oSchool === null) {
			$oSchool = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		}

		$iSchoolId = $oSchool->id;

		$sCacheKey = get_class($this).'::getArrayListJoinedSchool_'.$iSchoolId.'_'.$sNameField;

		if(!isset(self::$_aArrayListCache[$sCacheKey])) {

			$aArrayList = WDCache::get($sCacheKey);

			if(
				//nicht auf empty überprüfen, sonst würde man bei einem leeren
				//ergebnis immer wieder versuchen den cache zu bilden
				!is_array($aArrayList)
			) {

				$sSql = "
					SELECT 
						* 
					FROM
						#table `t` JOIN
						#school_table `s` ON
							`t`.`id` = `s`.#entity_id_field
					WHERE
						`active` = 1 AND
						`s`.#school_id_field = :school_id
				";
			
				if(array_key_exists('position', $this->_aData)) {
					$sSql .= " ORDER BY 
						`t`.`position` ASC";
				}

				$aSql = [
					'table' => $this->_sTable,
					'school_table' => $this->_aJoinTables['schools']['table'],
					'entity_id_field' => $this->_aJoinTables['schools']['primary_key_field'],
					'school_id_field' => $this->_aJoinTables['schools']['foreign_key_field'],
					'school_id' => (int)$iSchoolId
				];

				$aCacheInsert = DB::getQueryRows($sSql, $aSql);

				//objekt cache
				self::$_aArrayListCache[$sCacheKey] = $aCacheInsert;

				//memcache oder dbcache einfügen
				WDCache::set($sCacheKey, 86400, $aCacheInsert);

			} else {
				//objekt cache
				self::$_aArrayListCache[$sCacheKey] = $aArrayList;
			}

		}

		//falls für select etc. nocheinmal das array vorbereiten
		$aBack = $this->_prepareArrayListByOptions($sCacheKey, $bForSelect, $sNameField);

		return $aBack;
	}
	
	/**
	 * ArrayListe Schul-basierend
	 *
	 * @deprecated
	 *
	 * @param bool $bForSelect
	 * @param string $sNameField
	 * @param boolean $bCheckValid
	 * @return array
	 */
	public function getArrayListSchool($bForSelect = false, $sNameField = 'name', $bCheckValid=true) {

		$aSchoolData = $this->_getArrayListSchoolData($bCheckValid);
		
		if(!$aSchoolData) {
			return $this->getArrayList($bForSelect, $sNameField, $bCheckValid);
		}
		
		$sCacheKey = $aSchoolData['cache_key'];
		$sSchoolIdField = $aSchoolData['school_id_field'];
		$iSchoolId = $aSchoolData['school_id'];

		if(!isset(self::$_aArrayListCache[$sCacheKey])) {

			$aArrayList = WDCache::get($sCacheKey);

			if(
				//nicht auf empty überprüfen, sonst würde man bei einem leeren
				//ergebnis immer wieder versuchen den cache zu bilden
				!is_array($aArrayList)
			) {

				$aArrayListSchool = array();
				
				//nicht schulbasierende arraylist aufrufen & danach filtern
				$aArrayList = $this->getArrayList(false, $sNameField, $bCheckValid);

				//filtern der arraylist nach schule
				foreach($aArrayList as $aRowData) {

					if(
						isset($aRowData[$sSchoolIdField]) &&
						$aRowData[$sSchoolIdField] == $iSchoolId
					) {
						$aArrayListSchool[] = $aRowData;
					}

				}
				
				//vorbereiten falls nur bestimmte felder gecached werden sollen
				$aCacheInsert = $this->_prepareArrayListResult($aArrayListSchool);
				
				//objekt cache
				self::$_aArrayListCache[$sCacheKey] = $aCacheInsert;
				
				//memcache oder dbcache einfügen
				WDCache::set($sCacheKey, 86400, $aCacheInsert);

			} else {
				//objekt cache
				self::$_aArrayListCache[$sCacheKey] = $aArrayList;
			}

		}
		
		//falls für select etc. nocheinmal das array vorbereiten
		$aBack = $this->_prepareArrayListByOptions($sCacheKey, $bForSelect, $sNameField);
		
		return $aBack;
	}

	/**
	 * @return bool|null|string
	 */
	protected function _checkSchoolIdField() {
		
		if(
			$this->_sSchoolIdField !== null &&
			isset($this->_aData[$this->_sSchoolIdField])
		) {
			return $this->_sSchoolIdField;
		} elseif(
			isset($this->_aData['school_id'])
		) {
			return 'school_id';
		} elseif (
			isset($this->_aData['idSchool'])	
		) {
			return 'idSchool';
		} else {
			return false;
		}
		
	}

	/**
	 * @param array $aData
	 */
	public function setData(array $aData){
		$this->_aData = $aData;
	}

	/**
	 * @deprecated
	 * @internal
	 * @return bool|mixed
	 */
	public function getSchoolId() {
		
		$sSchoolIdField = $this->_checkSchoolIdField();
		
		if(
			$sSchoolIdField &&
			isset($this->_aData[$sSchoolIdField])
		) {
			return $this->$sSchoolIdField;
		} else {
			return false;
		}
		
	}

	/**
	 * @deprecated
	 * @internal
	 * @return null|Ext_Thebing_School
	 */
    public function getSchool() {

        $oSchool = null;
        $iSchool = $this->getSchoolId();

        if($iSchool) {
            $oSchool = Ext_Thebing_School::getInstance($iSchool);
        }

        return $oSchool;
    }

	/**
	 * @deprecated
	 * @internal
	 * @param int $iSchoolId
	 */
	public function setSchoolId($iSchoolId) {
		
		$sSchoolIdField = $this->_checkSchoolIdField();

		if(
			$sSchoolIdField &&
			isset($this->_aData[$sSchoolIdField])
		) {
			$this->_aData[$sSchoolIdField] = (int)$iSchoolId;
		}

	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getIndexData() {

		$aData = $this->getData();
		$oSchool = $this->getSchool();
		
		if($oSchool instanceof Ext_Thebing_School) {
			$aData['school_id'] = (int)$oSchool->id;
		}
		
		return $aData;
	}
	
	public function getOldPlaceholderObject(SmartyWrapper $oSmarty=null) {
		
		if($this->_sOldPlaceholderClass !== null) {

			$sClass = $this->_sOldPlaceholderClass;
			$oReturn = new $sClass($this, $oSmarty);

			return $oReturn;
			
		}
		
	}
	
	/**
	 * Gibt den Namen der Platzhalterklasse zurück
	 *
	 * @return string
	 */
	public function getOldPlaceholderClass() {
		
		if($this->_sOldPlaceholderClass !== null) {
			return $this->_sOldPlaceholderClass;
		}
		
	}

}
