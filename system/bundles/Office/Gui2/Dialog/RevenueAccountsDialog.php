<?php

namespace Office\Gui2\Dialog;

class RevenueAccountsDialog extends \Ext_Gui2_Data {

	public static function getDialog(\Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog('Erlöskonto "{name}" bearbeiten', 'Neues Erlöskonto anlegen');

		$oDialog->height = 500;
		$oDialog->width = 900;

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Name'), 
				'input', 
				array(
					'db_column' => 'name',
					'db_alias' => 'ora',
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Number'), 
				'input', 
				array(
					'db_column' => 'number',
					'db_alias' => 'ora',
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Von Auswertungen ausschließen'), 
				'checkbox', 
				array(
					'db_column' => 'exclude_from_reports',
					'db_alias' => 'ora',
				)
			)
		);

		return $oDialog;
	}

    public static function getOrderBy(){
        return array('name' => 'ASC');
    }	

	public static function getWhere() {
		return array();
	}
	
}
