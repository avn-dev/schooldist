<?php

class Ext_TC_Gui2_Format_WDBasic extends Ext_TC_Gui2_Format {

	/**
	 *
	 * @var WDBasic
	 */
	protected $_oWDBasic;
	protected $_aSetterFields;

	public function __construct($sClassName, $aSetterFields=array())
	{
		$this->_oWDBasic		= new $sClassName();
		$this->_aSetterFields	= (array)$aSetterFields;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		$oWDBasicFormat = $this->_oWDBasic;
		$aSetterFields	= $this->_aSetterFields;
		$sDbAlias		= $oColumn->db_alias;
		$sDbColumn		= $oColumn->db_column;

		foreach($aSetterFields as $sSetterAlias => $mSetterField)
		{
			if(!is_string($sSetterAlias))
			{
				$sSetterAlias = '';
			}
			//@todo: recursion
			$mSetterField = (array)$mSetterField;
			foreach($mSetterField as $sSetterField)
			{
				$oWDBasicFormat->getJoinedObject($sSetterAlias)->$sSetterField = $aResultData[$sSetterField];
			}
		}

		$mValue = $oWDBasicFormat->getJoinedObject($sDbAlias)->$sDbColumn;

		return $mValue;

	}

}