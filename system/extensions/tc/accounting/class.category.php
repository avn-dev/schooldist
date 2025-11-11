<?php

/**
 * WDBasic-Klasse der Kategorien für Konten
 */

class Ext_TC_Accounting_Category extends Ext_TC_Basic {

	protected $_sTable = 'tc_accounting_accounts_categories';

	protected $_sTableAlias = 'tc_aca';	
	
	public function getClassName() {
		return get_class($this);
	}

	protected $_aFormat = array(
		'number' => array(
			'validate'	=> 'INT_NOTNEGATIVE'
		)
	);	
	
	protected $_aJoinedObjects = array(
		'accounts'=>array(
			'class'=>'Ext_TC_Accounting_Account',
			'key'=>'category_id',
			'type'=>'child',
			'check_active'=>true
		)
	);
	
	/**
	 * @return Ext_TC_Accounting_Account <array> 
	 */
	public function getAccounts(){
		$aAccounts = (array)$this->getJoinedObjectChilds('accounts');
		return $aAccounts;
	}
	
	/**
	 * Gibt ein Array mit allen Kategorien zurück.
	 * @return type 
	 */
	
	public static function getSelectOptions($bForSelect = false)
	{

		$oTemp = new self();
		$mList = $oTemp->getArrayList(true);

		if($bForSelect){
			$mList = Ext_TC_Util::addEmptyItem($mList);
		}

		return $mList;
	}

	/**
	 *
	 * @return Ext_TC_Accounting_Accountscode 
	 */
	
	
	public function getAccountsCode(){
		$iId = (int) $this->accountscode_id;
		$oAccountsCode = Ext_TC_Accounting_Accountscode::getInstance($iId);
		
		return $oAccountsCode;
	}
	
}