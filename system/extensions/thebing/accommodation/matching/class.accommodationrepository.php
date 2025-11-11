<?php

/**
 * 
 */
class Ext_Thebing_Accommodation_Matching_AccommodationRepository {
	
	/**
	 * @var DB
	 */
	private $oDB;

	/**
	 * @var int
	 */
	private $iCategoryId;

	/**
	 * @var int
	 */
	private $iSchoolId;

	/**
	 * @var array
	 */
	private $aWhereParts = array();

	/**
	 * @var array
	 */
	private $aSql = array();

	/**
	 * @var string
	 */
	private $sJoin;

	/**
	 * @var string
	 */
	private $sActiveFamiliesWherePart;

	/**
	 * @param DB $oDB
	 * @param int $iSchoolId
	 */
	public function __construct(DB $oDB, $iSchoolId) {
		$this->oDB = $oDB;
		$this->iSchoolId = (int)$iSchoolId;
	}

	/**
	 * @param DateTime $oFrom
     * @param DateTime|null $oUntil
	 */
	public function setValidUntil(DateTime $oFrom, DateTime $oUntil = null) {

        /* Beim generellen Matching ($oUntil is set) darf das Anbieter-Deaktivierungs-Datum
         * nicht in den Datumsbereich fallen.
         * Bei der Übersicht's Liste ($oUntil not set) soll ein Eintrag erscheinen, sobald
         * ein Datumsbereich matched. */

        if($oUntil) {

            $this->aSql['to'] = $oUntil->format('Y-m-d H:i:s');
            $this->aWhereParts[] = "(
                `acc`.`valid_until` > :to OR
                `acc`.`valid_until` = '0000-00-00'
            )";

        } else {

            $this->aSql['from'] = $oFrom->format('Y-m-d H:i:s');
            $this->aWhereParts[] = "(
                `acc`.`valid_until` >= :from OR
                `acc`.`valid_until` = '0000-00-00'
            )";

        }

	}

	/**
	 * @param int $iRoomId
	 */
	public function setRoomId($iRoomId) {
		$this->aSql['room_id'] = (int)$iRoomId;
		$this->sJoin = " INNER JOIN
			`kolumbus_rooms` as `kr` ON
				`kr`.`accommodation_id` = `acc`.`id`";
		$this->aWhereParts[] = "`kr`.`id` = :room_id";	
	}

	/**
	 * @param int $iCategoryId
	 */
	public function setCategoryId($iCategoryId) {
		$this->iCategoryId = (int)$iCategoryId;
		$this->aWhereParts[] = "`kac`.`id` = :category_id";
	}
	
	public function enableAdults() {
		$this->aWhereParts[] = "`acc`.`ext_35` = 1";
	}
	
	public function enableMinors() {
		$this->aWhereParts[] = "`acc`.`ext_36` = 1";
	}
	
	public function enableMale() {
		$this->aWhereParts[] = "`acc`.`ext_37` = 1";
	}
	
	public function enableFemale() {
		$this->aWhereParts[] = "`acc`.`ext_38` = 1";
	}

	public function enableDiverse() {
		$this->aWhereParts[] = "`acc`.`diverse` = 1";
	}
	
	public function enableSmoker() {
		$this->aWhereParts[] = "`acc`.`ext_39` = 1";
	}
	
	public function enableVegetarian() {
		$this->aWhereParts[] = "`acc`.`ext_40` = 1";
	}
	
	public function enableMuslimDiet() {
		$this->aWhereParts[] = "`acc`.`ext_41` = 1";
	}

	public function enableTypeHostfamily() {
		$this->aWhereParts[] = "`kac`.`type_id` = ".\Ext_Thebing_Accommodation_Category::TYPE_HOSTFAMILY;;
	}

	public function enableTypeOther() {
		$this->aWhereParts[] = "`kac`.`type_id` = ".\Ext_Thebing_Accommodation_Category::TYPE_OTHERS;;
	}

	public function enableTypeParking() {
        $this->aWhereParts[] = "`kac`.`type_id` = ".\Ext_Thebing_Accommodation_Category::TYPE_PARKING;
    }

	/**
	 * Bereits vorhandene Familien setzen, damit diese auch nach möglicher Kriterien-Änderung noch angezeigt werden
	 *
	 * @param array $aActiveFamilies
	 */
	public function setActiveFamilies(array $aActiveFamilies) {
		$this->aSql['active_families'] = $aActiveFamilies;
		$this->sActiveFamiliesWherePart = " OR
						`acc`.`id` IN (:active_families)";
		
	}

	/**
	 * @return Collection
	 */
	public function getCollection() {

		$sSql = "
			SELECT 
				`acc`.`id`,
				`acc`.`email`,
				`acc`.`ext_33`,
				`acc`.`ext_34`,
				(
					SELECT
						GROUP_CONCAT(`meal_id`)
					FROM
						`ts_accommodation_providers_to_accommodation_meals` as `ts_aptam`
					WHERE
						`ts_aptam`.`accommodation_provider_id` = `acc`.`id`
				) as `meals`,
				`acc`.`ext_67`,
				`acc`.`ext_76`,
				`acc`.`ext_77`,
				`acc`.`ext_78`,
				`acc`.`ext_42`,
				`acc`.`ext_43`,
				`acc`.`ext_44`,
				`acc`.`ext_45`,
				`acc`.`ext_46`,
				`acc`.`ext_47`,
				`acc`.`ext_48`,
				`acc`.`ext_49`,
				`acc`.`ext_50`,
				`acc`.`ext_51`,
				`acc`.`ext_53`,
				`acc`.`ext_54`,
				`acc`.`ext_55`,
				`acc`.`ext_56`,
				`acc`.`ext_57`,
				`acc`.`ext_63`, -- Adresse
				`acc`.`ext_64`, -- PLZ
				`acc`.`ext_65`, -- Stadt
				`acc`.`ext_66`, -- Land,
				`acc`.`ext_103`, -- Vorname,
				`acc`.`ext_104`, -- Nachname,
				`acc`.`requirement_missing`,
				`acc`.`requirement_expired`
			FROM
				`customer_db_4` as `acc` INNER JOIN
				`ts_accommodation_categories_to_accommodation_providers` `kac_t_cdb4` ON
					`kac_t_cdb4`.`accommodation_provider_id` = `acc`.`id` INNER JOIN
				`kolumbus_accommodations_categories` as `kac` ON
					`kac`.`id` = `kac_t_cdb4`.`accommodation_category_id` AND
					`kac`.`active` = 1 INNER JOIN
				`ts_accommodation_providers_schools` `ts_aps` ON
					`ts_aps`.`accommodation_provider_id` = `acc`.`id` AND
					`ts_aps`.`school_id` = :school_id
			".$this->sJoin."
			WHERE
				`acc`.`active` = 1 AND
				(
					(".implode(' AND ', $this->aWhereParts).")
					".$this->sActiveFamiliesWherePart." 
				)
			GROUP BY
				`acc`.`id`
			ORDER BY 
				`acc`.`ext_33` 
		";

		$this->aSql['school_id'] = $this->iSchoolId;
		$this->aSql['category_id'] = $this->iCategoryId;

		$oCollection = $this->oDB->getCollection($sSql, $this->aSql);

		return $oCollection;

	}

}
