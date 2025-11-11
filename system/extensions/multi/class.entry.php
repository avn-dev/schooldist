<?php

class Ext_Multi_Entry extends WDBasic {

	protected $_sTable			= 'multi_entry';

	protected $_aAccess			= array();

	protected $_aFields			= array();

	public function getListQueryData($oGui = null) {

		$aQueryData = parent::getListQueryData($oGui);

		$aQueryData['sql'] = str_replace(' WHERE `multi_entry`.`active` = 1 ', '', $aQueryData['sql']);

		return $aQueryData;
	}

	public function __get($sName) {

		if(strstr($sName, 'field_') !== false) {

			$iFieldID = str_replace('field_', '', $sName);

			foreach($this->_aFields as $aField) {

				if($aField['field_id'] == $iFieldID) {

					if(
						$aField['type'] == 'multiselect' && 
						!is_array($aField['value'])
					) {
						$aField['value'] = explode('|', $aField['value']);
					}

					return $aField['value'];

					break;
				}
			}
		}
		else if($sName === 'access' || $sName === 'noaccess')
		{
			$iAccess = 1;

			if($sName === 'noaccess')
			{
				$iAccess = 0;
			}

			return (array)$this->_aAccess[$iAccess];
		}
		else
		{
			return parent::__get($sName);
		}
	}

	public function __set($sName, $mValue)
	{
		if(strstr($sName, 'field_') !== false)
		{
			$iFieldID = str_replace('field_', '', $sName);

			foreach($this->_aFields as $iKey => $aField)
			{
				if($aField['field_id'] == $iFieldID)
				{
					$this->_aFields[$iKey]['value'] = $mValue;

					if($aField['type'] == 'multiselect' && is_array($mValue))
					{
						$this->_aFields[$iKey]['value'] = implode('|', $mValue);
					}

					$iFieldID = true;

					break;
				}
			}

			if($iFieldID !== true)
			{
				$this->_aFields[] = array(
					'field_id'	=> $iFieldID,
					'value'		=> $mValue
				);
			}
		}
		else if($sName === 'access' || $sName === 'noaccess')
		{
			$iAccess = 1;

			if($sName === 'noaccess')
			{
				$iAccess = 0;
			}

			$this->_aAccess[$iAccess] = $mValue;
		}
		else
		{
			parent::__set($sName, $mValue);
		}
	}

	public function save()
	{
		$aAccessCopy = $this->_aAccess;
		$aFieldsCopy = $this->_aFields;

		Ext_Multi_Access::saveAccess($this->id, $this->_aAccess[1], 1);
		Ext_Multi_Access::saveAccess($this->id, $this->_aAccess[0], 0);

		parent::save();

		$this->_loadFields();

		foreach($this->_aFields as $iKey => $aField)
		{
			foreach($aFieldsCopy as $iTempKey => $aTemp)
			{
				if($aField['field_id'] == $aTemp['field_id'])
				{
					$this->_aFields[$iKey]['value'] = $aTemp['value'];

					if($aField['type'] == 'multiselect' && is_array($aTemp['value']))
					{
						$this->_aFields[$iKey]['value'] = implode('|', $aTemp['value']);
					}

					$sSQL = "
						SELECT
							`id`
						FROM
							`multi_data`
						WHERE
							`field_id` = :field_id AND
							`multi_id` = :multi_id AND
							`entry_id` = :entry_id
						LIMIT
							1
					";
					$aSQL = array(
						'multi_id'	=> $this->multi_id,
						'entry_id'	=> $this->id,
						'field_id'	=> $this->_aFields[$iKey]['field_id'],
						'value'		=> (string)$this->_aFields[$iKey]['value']
					);
					$iCheck = (int)DB::getQueryOne($sSQL, $aSQL);

					if($iCheck)
					{
						DB::updateData('multi_data', $aSQL, "`id` = " . $iCheck);
					}
					else
					{
						DB::insertData('multi_data', $aSQL);
					}

					break;
				}
			}
		}

		$this->_loadData($this->id);

		return $this;
	}

	protected function _loadData($iDataID)
	{
		parent::_loadData($iDataID);

		if($this->id > 0) // Load access data
		{
			$aAccess = Ext_Multi_Access::getAccess($this->id);

			foreach((array)$aAccess as $iAccess => $aAccessData)
			{
				foreach((array)$aAccessData as $iDB_ID => $aCutomerGroups)
				{
					foreach((array)$aCutomerGroups as $iGroupID => $sValue)
					{
						$this->_aAccess[$iAccess][] = $iDB_ID . '_' . $iGroupID;
					}
				}
			}

			$this->_loadFields();
		}
	}

	protected function _loadFields()
	{
		$sSQL = "
			SELECT
				`mf`.`field_id`,
				`md`.`value`,
				`mf`.`type`
			FROM
				`multi_fields` AS `mf` LEFT JOIN
				`multi_data` AS `md` ON
					`md`.`field_id` = `mf`.`field_id` AND
					`md`.`multi_id` = `mf`.`multi_id` AND
					`md`.`entry_id` = :iEntryID
			WHERE
				`mf`.`multi_id` = :iMultiID
		";
		$aSQL = array(
			'iMultiID' => $this->multi_id,
			'iEntryID' => $this->id
		);
		$this->_aFields = (array)DB::getPreparedQueryData($sSQL, $aSQL);
	}
}