<?php

namespace Office\Gui2\Dialog;

class PriceQuantityScaleParts extends \Ext_Gui2_Data {

	public static function getDialog(\Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog('Eintrag "{from} bis {to}" bearbeiten', 'Neuen Eintrag anlegen');

		$oDialog->save_as_new_button = true;
		$oDialog->save_bar_options = true;
		$oDialog->save_bar_default_option = 'new';

		$oDialog->height = 500;
		$oDialog->width = 900;

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Von'), 
				'input', 
				array(
					'db_column' => 'from',
					'db_alias' => 'opqsp',
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Bis'), 
				'input', 
				array(
					'db_column' => 'to',
					'db_alias' => 'opqsp',
				)
			)
		);

		return $oDialog;
	}

    public static function getOrderBy(){
        return array('from' => 'ASC');
    }	

	public static function getWhere() {
		return array();
	}
	
}
