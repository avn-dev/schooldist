<?php

namespace Office\Gui2\Dialog;

class ProductAreaDialog extends \Ext_Gui2_Data {

	public static function getDialog(\Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog('Produktbereich "{name}" bearbeiten', 'Neuen Produktbereich anlegen');

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

		return $oDialog;
	}

    public static function getOrderBy(){
        return array('name' => 'ASC');
    }	

	public static function getWhere() {
		return array();
	}
	
}
