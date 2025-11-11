<?php

class Ext_Thebing_Accommodation_Fee {

	use \Ts\Traits\PriceCalculationColumns;
	
	protected $_iAccommodationCategorieId = 0;
	protected $_iCostId = 0;
	protected $_iSaisonId = 0;
	protected $_iCurrency = 0;

	/**
     * @var Ext_Thebing_School 
     */
	protected $_oSchool;

	public function __construct(
		$iAccommodationCategorieId,
		$iCostId,
		$iSaisonId,
		$iCurrencyId,
		string $nationality = null,
		Ext_Thebing_Agency_Category $agencyCategory = null,
		?array $countryGroupIds = null
	) {
		
        $oCost = Ext_Thebing_School_Additionalcost::getInstance($iCostId);
        $this->_oSchool = $oCost->getSchool();

		$this->_iAccommodationCategorieId = (int)$iAccommodationCategorieId;
		$this->_iCostId = (int)$iCostId;
		$this->_iSaisonId = (int)$iSaisonId;
		$this->_iCurrency = (int)$iCurrencyId;

		$this->agencyCategory = $agencyCategory;
		$this->nationality = $nationality;
		$this->countryGroupIds = $countryGroupIds;

	}

	/**
	 * Speichern für Zusätzliche Kosten
	 * @param ?float $fAmount
	 * @throws Exception
	 */
	public function saveFeeAmount(?float $fAmount): void {

		$agencyCategory = $this->agencyCategory;
		$nationality = $this->nationality;
		// Save or update only allows one country group id or null
		$countryGroupIds = isset($this->countryGroupIds[0]) ? array_slice($this->countryGroupIds, 0, 1) : null;

		// Achtung! Man kann nur Nationalität ODER Agenturkategorie verwenden
		$this->manipulateNationalityAndAgencyCategory($nationality, $agencyCategory, $countryGroupIds);

		$aSql = array(
			'amount' => Ext_Thebing_Format::convertFloat($fAmount),
			'categorie_id' => (int)$this->_iAccommodationCategorieId,
			'school_id' => (int)$this->_oSchool->getId(),
			'cost_id' => (int)$this->_iCostId,
			'saison_id' => (int)$this->_iSaisonId,
			'currency_id' => (int)$this->_iCurrency,
			'nationality' => $nationality,
			'agency_category_id' => $agencyCategory?->id,
			'country_group_id' => $countryGroupIds[0] ?? null,
		);

		$sSql = "
			DELETE FROM
				`kolumbus_accommodation_fee` 
			WHERE 
				`categorie_id` = :categorie_id AND
				`school_id` = :school_id AND
				`cost_id` = :cost_id AND
				`saison_id` = :saison_id AND
				`currency_id` = :currency_id
		";

		$sSql .= " AND ".$this->addAgencyCategoryWherePart($agencyCategory)." AND ".$this->addNationalityAndCountryGroupWherePart($nationality, $countryGroupIds);

		DB::executePreparedQuery($sSql, $aSql);

		if($fAmount !== null) {

			$sSql = "
				INSERT INTO 
					`kolumbus_accommodation_fee` 
				SET 
					`amount` = :amount, 
					`categorie_id` = :categorie_id,
					`school_id` = :school_id, 
					`cost_id` = :cost_id,
					`saison_id` = :saison_id,
					`currency_id` = :currency_id,
					`nationality` = :nationality,
					`agency_category_id` = :agency_category_id,
					`country_group_id` = :country_group_id
			";

			DB::executePreparedQuery($sSql, $aSql);

		}
		
	}

	/**
	 * Auslesen für Zusätzliche Kosten
	 * @param bool $fallbackNationalityToCountryGroups
	 * @return ?float
	 * @throws Exception
	 */
	public function getFeeAmount(bool $fallbackNationalityToCountryGroups = true): ?float {

		$agencyCategory = $this->agencyCategory;
		$nationality = $this->nationality;
		$countryGroupIds = $this->countryGroupIds;

		// Achtung! Man kann nur Nationalität ODER Agenturkategorie verwenden
		$this->manipulateNationalityAndAgencyCategory($nationality, $agencyCategory, $countryGroupIds, $fallbackNationalityToCountryGroups);

		$sSql = "
			SELECT 
				`amount` 
			FROM
				`kolumbus_accommodation_fee` 
			WHERE
				`categorie_id` = :categorie_id AND
				`school_id` = :school_id AND
				`cost_id` = :cost_id AND
				`saison_id` = :saison_id AND
				`currency_id` = :currency_id AND
				".$this->addAgencyCategoryWherePart($agencyCategory)." AND
				".$this->addNationalityAndCountryGroupWherePart($nationality, $countryGroupIds, $fallbackNationalityToCountryGroups)."
			ORDER BY 
				`nationality` DESC,
				`country_group_id`
			LIMIT 
				1
		";

		$aSql = array(
			'categorie_id' => (int)$this->_iAccommodationCategorieId,
			'school_id' => (int)$this->_oSchool->getId(),
			'cost_id' => (int)$this->_iCostId,
			'saison_id' => (int)$this->_iSaisonId,
			'currency_id' => (int)$this->_iCurrency,
			'nationality' => $nationality,
			'agency_category_id' => $agencyCategory?->id
		);
		$amount = DB::getQueryOne($sSql, $aSql);

		if($amount !== null) {
			return (float)$amount;
		}
		
		return null;
	}

}
