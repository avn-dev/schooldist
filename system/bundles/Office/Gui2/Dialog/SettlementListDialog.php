<?php

namespace Office\Gui2\Dialog;

class SettlementListDialog extends \Ext_Gui2_Data {

	public static function getDialog(\Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog('Eintrag "{product}" bearbeiten', 'Neuen Eintrag anlegen');

		$oDialog->height = 500;
		$oDialog->width = 900;

		$aUnits = \Ext_Office_Config::get('units');
		$aVatOptions = \Ext_Office_Config::get('vat');
		
		/*
			product
			description
			amount
			unit
			price
			discount_item
			vat
			cleared
			customer_id
			customer_contact_id
		*/

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Titel'), 
				'input', 
				array(
					'db_column' => 'product',
					'db_alias' => 'osli',
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Beschreibung'), 
				'textarea', 
				array(
					'db_column' => 'description',
					'db_alias' => 'osli',
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createMultiRow(
				$oGui->t('Menge'),
				array(
					'db_alias' => 'osli',
					'items' => array(
						array(
							'input' => 'input',
							'db_column' => 'amount',
							'format' => new \Ext_Gui2_View_Format_Float(',', '.'),
							'style' => 'text-align: right; width: 100px;'
						),						
						array(
							'input' => 'select',
							'db_column' => 'unit',
							'select_options' => $aUnits,
							'style' => 'width: auto;'
						)
					)
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Preis'), 
				'input', 
				array(
					'db_column' => 'price',
					'db_alias' => 'osli',
					'format' => new \Ext_Gui2_View_Format_Float(',', '.'),
					'style' => 'text-align: right;width: 100px;'
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Rabatt (in Prozent)'), 
				'input', 
				array(
					'db_column' => 'discount_item',
					'db_alias' => 'osli',
					'format' => new \Ext_Gui2_View_Format_Float(',', '.'),
					'style' => 'text-align: right;width: 100px'
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Umsatzsteuer'), 
				'select', 
				array(
					'db_column' => 'vat',
					'db_alias' => 'osli',
					'select_options' => $aVatOptions,
					'style' => 'width: auto;'
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Kunde'), 
				'select', 
				array(
					'db_column' => 'customer_id',
					'db_alias' => 'osli',
					'selection' => new \Office\Gui2\Selection\CustomerSelection(),
					'style' => 'width: auto;'
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Ansprechpartner'), 
				'select', 
				array(
					'db_column' => 'customer_contact_id',
					'db_alias' => 'osli',
					'selection' => new \Office\Gui2\Selection\CustomerContactSelection(),
					'style' => 'width: auto;',
					'dependency' => array(
						array(
							'db_column' => 'customer_id',
							'db_alias' => 'osli'
						)
					)
				)
			)
		);

		return $oDialog;
	}

    public static function getOrderBy(){
        return array('customer' => 'ASC', 'contact' => 'ASC');
    }	

	public static function getWhere() {
		return array('cleared' => '0000-00-00 00:00:00');
	}
	
}
