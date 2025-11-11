<?php

namespace Office\Gui2\Dialog;

include_once(\Util::getDocumentRoot()."system/extensions/office/office.inc.php");
include_once(\Util::getDocumentRoot()."system/extensions/office/office.dao.inc.php");

class ProductDialog extends \Ext_Gui2_Data {

	/**
	 * @var classExtensionDao_Office 
	 */
	protected $oOfficeDao;
	
	/**
	 * @var array
	 */
	protected $aConfigData;
	
	public function __construct(&$oGui) {

		parent::__construct($oGui);
		
	}
	
	static protected function getProductgroups($oOfficeDao) {

		$aProductgroups = $oOfficeDao->getArticleGroups(true);

		return $aProductgroups;
	}
	
	static protected function getUnits($aConfigData) {
		
		$aUnits = $aConfigData['units'];

		return $aUnits;
	}

	static protected function getCurrencies() {

		// get curencys
		$aCurrencys = \Data::getCurrencys();
		
		$aJSCurrencys = array();
		foreach((array)$aCurrencys as $iKey => $aCurrency) {
			$aJSCurrencys[$aCurrency['iso4217']] = $aCurrency['sign'] . ' - ' . $aCurrency['name'];
		}

		$aJSCurrencys = \Util::addEmptyItem($aJSCurrencys);

		return $aJSCurrencys;
	}
	
	static protected function getRevenueAccounts() {

		$oRevenueAccounts = \Office\Entity\RevenueAccounts::getInstance();
		$aRevenueAccounts = (array)$oRevenueAccounts->getArrayList(true);	
		
		return $aRevenueAccounts;
	}
	
	public static function getDialog(\Ext_Gui2 $oGui) {
		
		$oOffice = new \classExtension_Office;
		$aConfigData = $oOffice->getConfigData();
		$oOfficeDao = new \classExtensionDao_Office($aConfigData);

		$oDialog = $oGui->createDialog('Produkt "{product}" bearbeiten', 'Neues Produkt anlegen');
 
		$oDialog->width = 900;

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Name'), 
				'input', 
				array(
					'db_column' => 'product',
					'db_alias' => 'op',
				)
			)
		);
		
		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Artikelgruppe'), 
				'select', 
				array(
					'db_column' => 'productgroup',
					'db_alias' => 'op',
					'select_options' => self::getProductgroups($oOfficeDao),
					'style' => 'width: auto;'
				)
			)
		);
		
		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Erlöskonto'), 
				'select', 
				array(
					'db_column' => 'revenue_account',
					'db_alias' => 'op',
					'select_options' => self::getRevenueAccounts(),
					'style' => 'width: auto;'
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Artikelnummer'), 
				'input', 
				array(
					'db_column' => 'number',
					'db_alias' => 'op',
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Einheit'), 
				'select', 
				array(
					'db_column' => 'unit',
					'db_alias' => 'op',
					'select_options' => self::getUnits($aConfigData),
					'style' => 'width: auto;'
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Währung'), 
				'select', 
				array(
					'db_column' => 'currency',
					'db_alias' => 'op',
					'select_options' => self::getCurrencies(),
					'style' => 'width: auto;'
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Einkaufspreis'), 
				'input', 
				array(
					'db_column' => 'cost',
					'db_alias' => 'op',
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Preis'), 
				'input', 
				array(
					'db_column' => 'price',
					'db_alias' => 'op',
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('USt. %'), 
				'input', 
				array(
					'db_column' => 'vat',
					'db_alias' => 'op',
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Monate'), 
				'input', 
				array(
					'db_column' => 'month',
					'db_alias' => 'op',
				)
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Beschreibung'), 
				'textarea', 
				array(
					'db_column' => 'description',
					'db_alias' => 'op',
					'rows'=>5
				)
			)
		);

		return $oDialog;
	}

    public static function getOrderBy(){
        return array('product' => 'ASC');
    }	

	public static function getWhere() {
		return array();
	}
	
}
