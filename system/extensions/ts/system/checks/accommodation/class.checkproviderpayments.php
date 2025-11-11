<?php

use Ts\Entity\AccommodationProvider\Payment\Category;

class Ext_TS_System_Checks_Accommodation_CheckProviderPayments extends GlobalChecks {
	
	public function getTitle() {
		return 'Update accommodation provider payment data';
	}
	
	public function getDescription() {
		return 'Transfer to new structure';
	}
	
	/**
	 * - Eine Standard-Abrechnungskategorie anlegen, falls keine da ist
	 * - Die Abrechnungskategorie allen Anbietern zuweisen ab 01.06.2014
	 * 
	 * @return boolean
	 */
	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '1G');

		$aBackupTables = array(
			'ts_accommodation_providers_payment_categories',
			'ts_accommodation_providers_payment_categories_validity'
		);
		
		foreach($aBackupTables as $sBackupTable) {
			$bBackup = Util::backupTable($sBackupTable);
			if(!$bBackup) {
				return false;
			}
		}

		$oCategoryRepository = Category::getRepository();
		$oCategory = $oCategoryRepository->findOneBy(array());

		if(empty($oCategory)) {
			$oCategory = Category::getInstance();
			$oCategory->active = 1;
			$oCategory->name = 'Default';
			$oCategory->save();
		}

		$oAccommodationRepo = Ext_Thebing_Accommodation::getRepository();
		$aAccommodations = $oAccommodationRepo->findAll();

		/* @var $oAccommodation Ext_Thebing_Accommodation */
		foreach($aAccommodations as $oAccommodation) {

			$oCheckCategory = $oCategoryRepository->findByProvider($oAccommodation);
			if(empty($oCheckCategory)) {
				$oValidity = Category\Validity::getInstance();
				$oValidity->active = 1;
				$oValidity->provider_id = $oAccommodation->id;
				$oValidity->category_id = $oCategory->id;
				$oValidity->valid_from = '2014-06-01';
				$oValidity->save();
			}

		}

		// Unbenötigte Datei löschen
		if(is_file(Util::getDocumentRoot().'system/config/gui2/ts_accommodation_provider_payments.yml')) {
			unlink(Util::getDocumentRoot().'system/config/gui2/ts_accommodation_provider_payments.yml');
		}

		return true;		
	}
	
}