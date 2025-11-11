<?php
namespace Ts\Gui2\Data;

class MarketingCostWeeks extends \Ext_Thebing_Gui2_Data
{
	
	static public function getOrderby()
	{
		return ['title' => 'ASC'];
	}

	static public function getDialog(\Ext_Thebing_Gui2 $oGui)
	{

		$aSchools = \Ext_Thebing_Client::getSchoolList(true);
		
		$oDialog = $oGui->createDialog(
			$oGui->t('Woche editieren').' - {title}',
			$oGui->t('Neue Woche anlegen')
		);

		$oDialog->width = 900;
		$oDialog->height = 650;
		$oDialog->save_as_new_button = true;
		$oDialog->save_bar_options = true;
		$oDialog->save_bar_default_option = 'new';

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Bezeichnung'),
				'input',
				[
					'db_alias' => 'kacw',
					'db_column' => 'title',
					'required' => 1,
				]
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Schulen'),
				'select',
				[
					'db_alias' => '',
					'db_column' => 'schools',
					'multiple' => 5,
					'select_options' => $aSchools,
					'jquery_multiple' => 1,
					'searchable' => 1,
					'required' => 1,
				]
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Startwoche'),
				'input',
				[
					'db_alias' => 'kacw',
					'db_column' => 'start_week',
					'required' => 1,
				]
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Wochenanzahl'),
				'input',
				[
					'db_alias' => 'kacw',
					'db_column' => 'week_count',
				]
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Extrawoche'),
				'checkbox',
				[
					'db_alias' => 'kacw',
					'db_column' => 'extra',
				]
			)
		);

		return $oDialog;
	}

}
