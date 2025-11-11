<?php

/**
 * Check um aktive Unterkunftszuweisungen von gelöschten Unterkunftsbuchungen zu löschen
 */
class Ext_TS_System_Checks_Accommodation_Allocation_DeleteUnusedBookingAllocations extends GlobalChecks
{
	
	protected $_aIDs = array();


	public function getTitle()
	{
		return 'Accommodation Allocation';
	}
	
	public function getDescription()
	{
		return 'Deactivate allocations of deleted accommodation bookings';
	}
	
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$bBackup = Ext_Thebing_Util::backupTable('kolumbus_accommodations_allocations');
		
		if(!$bBackup) {
			__pout('backup error!');
			return false;
		}
		
		$sSql = "
			SELECT
				`kaa`.*
			FROM 
				`kolumbus_accommodations_allocations` `kaa` INNER JOIN
				`ts_inquiries_journeys_accommodations` `ts_ija` ON
					`ts_ija`.`id` = `kaa`.`inquiry_accommodation_id` AND
					`ts_ija`.`active` = 0
			WHERE
				`kaa`.`active` = 1
		";
		
		$aResult = (array)DB::getQueryData($sSql);
						
		if(!empty($aResult)) {
		
			DB::begin('Ext_TS_System_Checks_Accommodation_Allocation_DeleteUnusedBookingAllocations');
			
			try {
			
				foreach($aResult as $aAllocationData) {
					$iInquiryAccommodation = (int) $aAllocationData['inquiry_accommodation_id'];
					$oInquiryAccommodation = Ext_TS_Inquiry_Journey_Accommodation::getInstance($iInquiryAccommodation);

					// nochmal eine Sicherheitsabfrage, damit auch wirklich nichts falsches gelöscht wird
					if(
						$oInquiryAccommodation->id > 0 &&
						$oInquiryAccommodation->active == 0
					) {

						$iId = (int) $aAllocationData['id'];

						if($iId > 0) {
							$sSql = "
								UPDATE
									`kolumbus_accommodations_allocations` 
								SET
									`active` = 0
								WHERE
									`id` = :id						
							";

							$aSql = array(
								'id' => $iId
							);

							$this->_aIDs[] = $iId;
							
							DB::executePreparedQuery($sSql, $aSql);
						}
					}
				}
				
				DB::commit('Ext_TS_System_Checks_Accommodation_Allocation_DeleteUnusedBookingAllocations');
				
			} catch(Exception $e) {
				__pout($e);
				DB::rollback('Ext_TS_System_Checks_Accommodation_Allocation_DeleteUnusedBookingAllocations');
				return false;
			}
		}

		return true;
	}
	
	public function getChangedAllocations() {
		$sReturn = implode(', ', $this->_aIDs);
		return $sReturn;
	}
	
}