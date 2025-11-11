<?php
namespace Ts\Gui2\Data;

class TeacherCategories extends \Ext_Thebing_Gui2_Data
{
	
	static public function getOrderby()
	{
		return ['kckt.name'=>'ASC'];
	}

	static public function getWhere()
	{
		$iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
		return ['kckt.school_id'=>(int)$iSessionSchoolId];
	}
	
	static public function getDialog(\Ext_Thebing_Gui2 $oGui)
	{
		$iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');

		$oDialog = $oGui->createDialog($oGui->t('Kostenkategorie bearbeiten'), 
									   $oGui->t('Kostenkategorie anlegen'));
		$oDialog->width       = 900;
		$oDialog->height      = 450;

		$oDialog->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
						'db_column'=> 'name',
						'db_alias'=> 'kckt',
						'required'=>true
			)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Gruppierung'), 'select', array(
			'db_column' => 'grouping',
			'select_options' => [
				'week' => $oGui->t('Blockwoche'),
				'month' => $oGui->t('Monat')
			],
			'required' => true
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Wochenenden als Feiertage abrechnen'), 'checkbox', array(
			'db_column' => 'account_as_holiday'
		)));

		$oDialog->save_as_new_button = true;
		$oDialog->save_bar_options = true;
		
		return $oDialog;
	}
	
}