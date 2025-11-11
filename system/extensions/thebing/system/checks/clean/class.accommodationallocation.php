<?php

/**
 * Der Check führt eine Bereinigung anhand der gegebenen Struktur durch 
 */
class Ext_Thebing_System_Checks_Clean_AccommodationAllocation extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Check inactive accommodation allocations';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'No longer existing entries are set to inactive.';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$aTableStructure = array(
			// Mandanten
			'kolumbus_clients'=>array(
				'primary_key'=>'id',
				'childs'=>array(
					// Schule
					'customer_db_2'=>array(
						'primary_key'=>'id',
						'foreign_key'=>'idClient',
						'childs'=>array(
							// Reiseabschnitte
							'ts_inquiries_journeys' => array(
								'primary_key'=>'id',
								'foreign_key'=>'school_id',
								'childs'=>array(
									// Unterkunftsbuchungen
									'ts_inquiries_journeys_accommodations' => array(
										'primary_key'=>'id',
										'foreign_key'=>'journey_id',
										'childs'=>array(
											// Unterkunftszuordnungen
											'kolumbus_accommodations_allocations' => array(
												'primary_key'=>'id',
												'foreign_key'=>'inquiry_accommodation_id'
											)
										)
									)
								)
							)
						)
					)
				)
			)
		);
									
		$oClean = new Ext_Thebing_Db_Clean();

		$oClean->execute($aTableStructure);

		return true;

	}

}
