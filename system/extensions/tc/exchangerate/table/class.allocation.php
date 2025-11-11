<?php

class Ext_TC_Exchangerate_Table_Allocation {
	/**
	 * @var Ext_TC_Basic 
	 */
	protected $oObject;
	/**
	 * @var Ext_TC_Productline 
	 */
	protected $oProductline;
	/**
	 * @var Ext_TC_Countrygroup 
	 */
	protected $oCountryGroupFrom;
	/**
	 * @var Ext_TC_Countrygroup 
	 */
	protected $oCountryGroupTo;
	/**
	 * @var string 
	 */
	protected $sApplication;
	/**
	 * @var string 
	 */
	protected $sCurrencyIso;

	/**
	 * @param Ext_TC_Basic $oObject
	 * @param Ext_TC_Productline $oProductline
	 * @param string $sCurrencyIso
	 * @param string $sApplication
	 */
	public function __construct(Ext_TC_Basic $oObject, Ext_TC_Productline $oProductline, $sCurrencyIso, $sApplication) {
		$this->oObject = $oObject;
		$this->oProductline = $oProductline;
		$this->sApplication = $sApplication;
		$this->sCurrencyIso = $sCurrencyIso;
	}
	
	/**
	 * @param Ext_TC_Countrygroup $oCountryGroupFrom
	 * @param Ext_TC_Countrygroup $oCountryGroupTo
	 */
	public function bindCountryGroups(Ext_TC_Countrygroup $oCountryGroupFrom, Ext_TC_Countrygroup $oCountryGroupTo) {
		$this->oCountryGroupFrom = $oCountryGroupFrom;
		$this->oCountryGroupTo = $oCountryGroupTo;
	}
	
	/**
	 * @return Ext_TC_Exchangerate_Table|null
	 */
	public function getTable() {

		$sSql = "
			SELECT
				`table_id`
			FROM
				`tc_exchangerates_tables_allocations` `tc_eta`
			WHERE
				`countrygroup_from_id` = :countrygroup_from_id AND
				`countrygroup_to_id` = :countrygroup_to_id AND
				`productline_id` = :productline_id AND
				`application` = :application AND
				`object_id` = :object_id AND
				`currency_iso` = :currency_iso
		";
		
		$iCountryGroupFromId = $iCountryGroupToId = 0;
		
		if(
			$this->oCountryGroupFrom instanceof Ext_TC_Countrygroup &&
			$this->oCountryGroupTo instanceof Ext_TC_Countrygroup
		) {
			$iCountryGroupFromId = $this->oCountryGroupFrom->getId();
			$iCountryGroupToId = $this->oCountryGroupFrom->getId();
		}
		
		$aSql = array(
			'countrygroup_from_id' => $iCountryGroupFromId,
			'countrygroup_to_id' => $iCountryGroupToId,
			'productline_id' => (int)$this->oProductline->getId(),
			'application' => $this->sApplication,
			'currency_iso' => $this->sCurrencyIso,
			'object_id' => (int)$this->oObject->getId()
		);

		$iTableId = (int)DB::getQueryOne($sSql, $aSql);

		if($iTableId > 0) {
			$oTable = Ext_TC_Exchangerate_Table::getInstance($iTableId);
			return $oTable;
		}

		return null;
	}

}
