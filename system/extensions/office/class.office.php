<?php

global $oZendDB;

/**
 * The abstract office class
 */
abstract class Ext_Office_Office
{
	/**
	 * The global Zend_Db object
	 */
	protected $_oZendDB;


	/**
	 * The office configuration
	 */
	protected $_aConfig = array();


	/**
	 * The name of the table in the DB
	 */
	protected $_sTable = '';


	/**
	 * The required fields
	 */
	protected $_aRequiredFields = array();


	/**
	 * The DB table definition
	 */
	protected $_aTableDB = array();


	/**
	 * The constructor
	 * 
	 * @param string : The name of table
	 * @param int : The element ID
	 */
	public function __construct($sTable = null, $iElementID = null)
	{
		global $oZendDB;

		$this->_oZendDB = $oZendDB;

		if(!isset($this->_aData))
		{
			throw new Exception('Please define the element data array!');
		}

		// Load configuration data
		$this->_aConfig = Ext_Office_Config::getInstance();

		if(is_null($sTable))
		{
			throw new Exception('Please define the table name!');
		}

		// Set the table name
		$this->_sTable = $sTable;

		// Get the table definition
		$this->_aTableDB = DB::describeTable($this->_sTable);

		if(is_numeric($iElementID) && $iElementID > 0)
		{
			// Set the ID and load the element data from DB
			$this->_aData['id'] = (int)$iElementID;
			$this->_loadData();
		}
		else
		{
			// The new element
			$this->_aData['id'] = 0;
		}
	}

	public function getData() {
		return $this->_aData;
	}

	/**
	 * Returns the value of a element data
	 * 
	 * @param string : The key of data array
	 * @return mixed : The value of a key
	 */
	public function __get($sName)
	{
		if(array_key_exists($sName, $this->_aData))
		{
			return $this->_aData[$sName];
		}
		else if($sName == 'config')
		{
			return $this->_aConfig;
		}
		else
		{
			throw new Exception('"'.$sName.'" does not exists!');
		}
	}


	/**
	 * Sets the value of a element data
	 * 
	 * @param string : The key of data array
	 * @param mixed : The value of data array
	 */
	public function __set($sName, $mValue)
	{
		if(array_key_exists($sName, $this->_aData))
		{
			if($sName == 'id')
			{
				throw new Exception('ID of this element cannot be changed!');
			}

			$this->_aData[$sName] = $mValue;
		}
		else
		{
			throw new Exception('"'.$sName.'" does not exists!');
		}
	}


	/**
	 * Deletes an entry from DB by ID
	 * 
	 * @return mixed : null || $this
	 */
	public function remove($bDelete = false)
	{
		if($bDelete === true)
		{
			$sSQL = "DELETE FROM `" . $this->_sTable . "` WHERE `id` = " . intval($this->_aData['id']);
			DB::executeQuery($sSQL);
			return null;
		}
		else
		{
			if(array_key_exists('active', $this->_aTableDB))
			{
				DB::updateData($this->_sTable, array('active' => 0), '`id` = '.$this->_aData['id']);
				return $this;
			}
			else
			{
				throw new Exception('The entry cannot be deactivated!');
			}
		}
	}


	/**
	 * Saves the element data into the DB
	 * 
	 * @return object $this
	 */
	public function save()
	{
		// Check the requiered fields
		foreach((array)$this->_aRequiredFields as $iKey => $aValue)
		{
			switch($aValue['type'])
			{
				case 'INT':
				case 'TIMESTAMP':
				{
					if(!is_numeric($this->_aData[$aValue['field']]))
					{
						throw new Exception('Please set right value into "'.$aValue['field'].'"');
					}
					break;
				}
				case 'TEXT':
				{
					if(strlen(trim($this->_aData[$aValue['field']])) < 1)
					{
						throw new Exception('Please set right value into "'.$aValue['field'].'"');
					}
					break;
				}
				case 'ID':
					if(!is_numeric($this->_aData[$aValue['field']]) || $this->_aData[$aValue['field']] <= 0)
					{
						throw new Exception('Please set right value into "'.$aValue['field'].'"');
					}
					break;
				default: break;
			}
		}

		// Convert the timestamps
		$this->_convertTimestamps('from_unix_ts');

		// Create an new entry into the DB
		if(intval($this->_aData['id']) <= 0)
		{
			unset($this->_aData['id']);

			DB::insertData($this->_sTable, $this->_aData);
			$this->_aData['id'] = DB::fetchInsertID();
		}
		// Update an entry
		if(intval($this->_aData['id']) > 0)
		{
			DB::updateData($this->_sTable, $this->_aData, '`id` = '.intval($this->_aData['id']));
		}

		$this->_loadData();
	}


	/**
	 * Loads the element data from the DB
	 */
	protected function _loadData()
	{
		// Convert the timestamps
		$sTimestamps = $this->_convertTimestamps('into_unix_ts');

		$sSQL = "
			SELECT
				*
				".$sTimestamps."
			FROM
				`".$this->_sTable."`
			WHERE
				`id` = :iID
			LIMIT
				1
		";
		$this->_aData = DB::getQueryRow($sSQL, array('iID' => $this->_aData['id']));
	}


	/**
	 * Converts the timestamps for DB
	 * 
	 * @param string : The convert modus
	 * @return string : Converted timestamps || void
	 */
	protected function _convertTimestamps($sModus)
	{
		$sTimestamps = "";

		switch($sModus)
		{
			// eg. '2008-09-10 17:00:21' >>> '1221058821'
			case 'into_unix_ts':
			{	
				foreach((array)$this->_aData as $sKey => $mValue)
				{
					if(array_key_exists($sKey, $this->_aTableDB))
					{
						if($this->_aTableDB[$sKey]['DATA_TYPE'] == 'timestamp' || $this->_aTableDB[$sKey]['DATA_TYPE'] == 'datetime')
						{
							$sTimestamps .= ", UNIX_TIMESTAMP(`".$sKey."`) AS `".$sKey."`";
						}
					}
					else
					{
						throw new Exception('"'.$sKey.'" does not exists!');
					}
				}
				return $sTimestamps;
				break;
			}
			// eg. '1221058821' >>> '2008-09-10 17:00:21'
			case 'from_unix_ts':
			{
				foreach((array)$this->_aData as $sKey => $mValue)
				{
					if(array_key_exists($sKey, $this->_aTableDB))
					{
						if($this->_aTableDB[$sKey]['DATA_TYPE'] == 'timestamp' || $this->_aTableDB[$sKey]['DATA_TYPE'] == 'datetime')
						{
							if
							(
								($this->_aData[$sKey] == null || $this->_aData[$sKey] == 0)
									&&
								$sKey != 'created'
									&&
								$this->_aTableDB[$sKey]['DEFAULT'] != 'CURRENT_TIMESTAMP'
							)
							{
								$this->_aData[$sKey] = $this->_aTableDB[$sKey]['DEFAULT'];
							}
							else if($sKey == 'created' && intval($this->_aData['id']) == 0)
							{
								$this->_aData[$sKey] = date('YmdHis');
							}
							else if($this->_aTableDB[$sKey]['DEFAULT'] == 'CURRENT_TIMESTAMP')
							{
								unset($this->_aData[$sKey]);
							}
							else
							{
								if(is_numeric($this->_aData[$sKey])) {
									$this->_aData[$sKey] = date('YmdHis', $this->_aData[$sKey]);
								}
							}
						}
					}
				}
				break;
			}
			default:
			{
				throw new Exception('Please define the modus!');
			}
		}
	}
}
