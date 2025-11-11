<?php

namespace Ts\Gui2\Data;

class PointOfSale extends \Ext_Thebing_Gui2_Data
{
	
	public static function getOrderby()
	{
		return ['name'=>'ASC'];
	}
	
	public static function getDialog(\Ext_Gui2 $oGui)
	{

		$aGenders = \Ext_TC_Util::getPersonTitles();

		$oDialog = $oGui->createDialog($oGui->t('Verkaufsstelle "{name}" editieren'), $oGui->t('
		Neue Verkaufsstelle anlegen'));

		$oDialog->aOptions['section'] = 'admin_pos';

		$oDialog->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
			'db_column' => 'name',
			'db_alias' => 'ts_p',
			'required' => 1
		)));
		
		return $oDialog;
	}

	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $mAction = 'edit', $bPrepareOpenDialog = true)
	{
		
		$aReturn = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $mAction, $bPrepareOpenDialog);
		
		\WDCache::deleteGroup(\Admin\Helper\Navigation::CACHE_GROUP_KEY);
		
		return $aReturn;
	}
	
}
