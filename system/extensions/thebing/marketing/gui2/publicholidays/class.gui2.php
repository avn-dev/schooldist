<?php

class Ext_Thebing_Marketing_Gui2_PublicHolidays_Gui2 extends Ext_Thebing_Gui2_Data
{

	static public function getDialog(\Ext_Gui2 $oGui)
    {
		
		$aSchools = Ext_Thebing_Client::getSchoolList(true, Ext_Thebing_Client::getClientId());
		
		$oDialog = $oGui->createDialog(
            L10N::t('Feiertag "{name}" editieren', $oGui->gui_description),
            L10N::t('Neuen Feiertag anlegen', $oGui->gui_description)
        );

        $oDialog->width = 900;
		$oDialog->height = 650;

		$oDialog->save_as_new_button = true;
		$oDialog->save_bar_options = true;
		$oDialog->save_bar_default_option = 'new';

		$oDialog->setElement($oDialog->createRow(
            L10N::t('Aktiv', $oGui->gui_description), 'checkbox',
            array('db_alias'=>'', 'db_column'=>'status'))
        );
		$oDialog->setElement($oDialog->createRow(
            L10N::t('Bezeichnung', $oGui->gui_description), 'input',
            array('db_alias'=>'', 'db_column'=>'name', 'required'=>true))
        );
		$oDialog->setElement($oDialog->createRow(
            L10N::t('Datum', $oGui->gui_description), 'calendar',
            array('db_alias'=>'', 'db_column'=>'date', 'format'=>new Ext_Thebing_Gui2_Format_Date(), 'required'=>true))
        );
		$oDialog->setElement($oDialog->createRow(
            L10N::t('Jährlich', $oGui->gui_description), 'checkbox',
            array('db_alias'=>'', 'db_column'=>'annual'))
        );
		$oDialog->setElement(
			$oDialog->createRow(
				L10N::t('Schulen', $oGui->gui_description),
				'select',
				array(
					'db_alias' => '',
					'db_column'=>'join_school',
					'select_options' => $aSchools,
					'multiple'=>6,
					'jquery_multiple'=>1,
					'searchable'=>1
					)
				)
			);
		$oDialog->setElement($oDialog->createRow(
            L10N::t('Kommentar', $oGui->gui_description), 'textarea',
            array('db_alias'=>'', 'db_column'=>'comment'))
        );
		
		return $oDialog;
	}
	
	static public function getOrderby()
    {

		return ['created' => 'DESC'];
	}
	
	static public function getWhere()
    {
		
		return ['client_id' => Ext_Thebing_Client::getClientId()];
	}

	static public function getYears()
	{
		return Ext_TC_Util::getYears(2,3);
	}

	static public function getYear()
	{
		$yearArray = Ext_TC_Util::getYears();

		return head($yearArray);
	}

	static public function getFilterQueryYearSelectFilter()
	{

		$years = self::getYears();

		$filterQuery = [];

		foreach($years as $year) {
			$filterQuery += [$year => 'YEAR(date) = '.$year.' OR annual = 1'];
		}

		return $filterQuery;
	}
}