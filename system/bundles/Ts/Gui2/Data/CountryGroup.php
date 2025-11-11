<?php

namespace Ts\Gui2\Data;

class CountryGroup extends \Ext_Thebing_Gui2_Data
{

	/**
	 * Schulversion, Ableitung in der Util, weil es die Ländergruppen in der Agentursoftware schon gab, aber die Liste in der
	 * Schulsoftware eine andere sein muss (in der Liste für die Agentursoftware war glaub ich noch ein Schulselect und noch
	 * etwas)
	 *
	 * @param \Ext_Thebing_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog
	 * @throws \Exception
	 */
	static public function getDialog(\Ext_Thebing_Gui2 $oGui)
	{

		$aCountries = \Ext_TC_Country::getSelectOptions();

		$oDialog = $oGui->createDialog($oGui->t('Ländergruppe "{name}" editieren'), $oGui->t('Neue Ländergruppe anlegen'));

		$oDialog->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
			'db_alias' => 'tc_cg',
			'db_column' => 'name',
			'required' => 1
		)));

		$oJoinContainer = $oDialog->createJoinedObjectContainer('SubObjects', array('min'=>1, 'max'=>1));

		$oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Länder'), 'select', array(
			'db_alias' => 'tc_cg_o',
			'db_column' => 'countries',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'select_options' => $aCountries,
			'searchable' => 1,
			'required' => 1
		)));

		$oDialog->setElement($oJoinContainer);

		$oDialog->access = array('core_admin_countrygroups', 'edit');

		return $oDialog;
	}

}