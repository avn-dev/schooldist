<?php

namespace Form\Gui2\Data;

class Pages extends \Ext_Gui2_Data {

	static public $iFormId;
	
	public static function getDialog(\Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog('Seite "{name}" bearbeiten', 'Neue Seite anlegen');

		$oDialog->height = 500;
		$oDialog->width = 900;

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Name'), 
				'input', 
				array(
					'db_column' => 'name',
					'db_alias' => 'cms_dr',
					'required' => true
				)
			)
		);

		return $oDialog;
	}
	
    public static function getOrderBy(){
        return array('name' => 'ASC');
    }	

	static public function getWhere(\Ext_Gui2 $oGui) {

		$aWhere = [
			'form_id' => self::$iFormId
		];
		
		return $aWhere;
	}
	
}
