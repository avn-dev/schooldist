<?php

/**
 * Feldtypen pro Verfügbarkeiten manipulieren
 */
abstract class Ext_TC_Gui2_Selection_Flexibility_Section_Filter extends Ext_TC_Gui2_Selection_Flexibility_FieldType
{
	public final function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
	{
		$iSectionId		= (int)$oWDBasic->section_id;
		$oSection		= Ext_TC_Flexible_Section::getInstance($iSectionId);
		
		$aTypeOptions	= Ext_TC_Flexibility::getFlexFieldTypes();
		asort($aTypeOptions);

		$aSelectOptions	= parent::getOptions($aSelectedIds, $aSaveField,$oWDBasic);

		// Filtern

		$aFilterData	= $this->_getFilterData();

		foreach($aFilterData as $sFilterKey => $aAllowedTypes)
		{
			if($oSection->type == $sFilterKey)
			{
				foreach($aSelectOptions as $mKey => $mOption)
				{
					if(!in_array($mKey, $aAllowedTypes))
					{
						unset($aSelectOptions[$mKey]);
					}
				}
			}	
		}

		return $aSelectOptions;
	}

	abstract protected function _getFilterData();
}
