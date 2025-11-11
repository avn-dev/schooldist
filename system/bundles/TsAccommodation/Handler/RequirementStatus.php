<?php

namespace TsAccommodation\Handler;

use Core\Handler\ParallelProcessing\TypeHandler;

class RequirementStatus extends TypeHandler {

	/**
	 * @inheritdoc
	 * @todo Ids in $aData übergeben und berücksichtigen, da es jetzt alles gespeichert wird.
	 */
	public function execute(array $aData, $bDebug = false) {

		/*
		 * In Abhängigkeit von $aData (category_id oder requirement_id) nur die relevanten Unterkünfte ermitteln
		 * -> und dann für diese Unterkünfte jeweils einen Eintrag ins PP schreiben mit einem weiteren Handler (neuer Eintrag in config.yml)
		 */

		if(isset($aData['category_id'])) {

			$sSql = "
				SELECT 
					`ts_actap`.`accommodation_provider_id` 
				FROM 
					 `customer_db_4` `cdb4` JOIN 
					`ts_accommodation_categories_to_accommodation_providers` `ts_actap` ON 
						`cdb4`.`id` = `ts_actap`.`accommodation_provider_id` 
				WHERE 
				    `cdb4`.`active` = 1 AND
					`ts_actap`.`accommodation_category_id` = :category_id
					";
			$aSql = [
				'category_id' => (int)$aData['category_id']
			];

		} elseif(isset($aData['requirement_id'])) {

			$sSql = "
				SELECT 
					`ts_actap`.`accommodation_provider_id` 
				FROM 
				    `customer_db_4` `cdb4` JOIN 
					`ts_accommodation_categories_to_accommodation_providers` `ts_actap` ON 
						`cdb4`.`id` = `ts_actap`.`accommodation_provider_id` JOIN
					`ts_accommodation_categories_to_requirements` `ts_actr` ON 
						`ts_actr`.`accommodation_category_id` = `ts_actap`.`accommodation_category_id` 
				WHERE 
				    `cdb4`.`active` = 1 AND
					`ts_actr`.`requirement_id` = :requirement_id
					";
			$aSql = [
				'requirement_id' => (int)$aData['requirement_id']
			];

		}

		$aAccommodationIds = \DB::getQueryCol($sSql, $aSql);

		$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();

		foreach($aAccommodationIds as $iAccommodationId) {

			$oStackRepository->writeToStack('ts-accommodation/requirements-status-updater', ['accommodation_id' => $iAccommodationId], 1);

		}

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function getLabel() {
		return \L10N::t('Unterkunfts-Voraussetzungen');
	}

}
