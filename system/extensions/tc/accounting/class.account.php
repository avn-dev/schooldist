<?php

/**
 * WDBasic-Klasse der Konten
 * @param int $accountscode_id
 * @param string $type
 * @param string $number
 * @param string $name
 * @param int $category_id
 * @param string $usage
 * @param string $currency_iso
 * @param int $position
 */
class Ext_TC_Accounting_Account extends Ext_TC_Basic {

	/**
	 * @var string
	 */
	protected $_sTable = 'tc_accounting_accounts';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'tc_aa';

	/**
	 * @var array
	 */
	protected $_aFormat = array(
		'number' => array(
			'validate'	=> 'INT_NOTNEGATIVE'
		)
	);
	
	/**
	 * Gibt ein Array mit allen Konten zurück.
	 *
	 * @return array
	 */	
	public static function getSelectOptions($bForSelect = false) {

		$oTemp = new self();
		$mList = $oTemp->getArrayList(true);

		if($bForSelect){
			$mList = Ext_TC_Util::addEmptyItem($mList);
		}
		
		return $mList;
	}	
	
	/**
	 * liefert anhand der Art des Kontos (Ertrag,...) die passenden 
	 * Zwischenkategorien
	 *
	 * @param bool $bObjectList
	 * @return Ext_TC_Accounting_Category[]
	 */
	public function getCategoriesByType($bObjectList = false) {
		
		$aTempData = array();

		$oAccountsCode = $this->getAccountsCode();
		$aAccountsCodeCategories = $oAccountsCode->getCategories();

		foreach($aAccountsCodeCategories as $oCategory) {
			
			if($oCategory->type == $this->type) {
				if($bObjectList) {
					$aTempData[$oCategory->id] = $oCategory;
				} else {
					$aTempData[$oCategory->id] = $oCategory->getName();
				}
			}
			
		}

		return $aTempData;
	}
		
	/**
	 * Ext_TC_Accounting_Category
	 * @return Ext_TC_Accounting_Category
	 */

	public function getCategory() {
		
		$iId = (int) $this->category_id;
		$oCategory = Ext_TC_Accounting_Category::getInstance($iId);
		
		return $oCategory;
	}
	
	/**
	 *
	 * @return Ext_TC_Accounting_Accountscode 
	 */
	
	public function getAccountsCode() {

		$iId = (int) $this->accountscode_id;
		$oAccountsCode = Ext_TC_Accounting_Accountscode::getInstance($iId);
		
		return $oAccountsCode;
	}
	
}