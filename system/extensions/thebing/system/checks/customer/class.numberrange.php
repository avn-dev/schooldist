<?php

// Achtung: Dieser Check wird in Ext_Thebing_System_Checks_Combination_InboxAndNumberRange aufgerufen!
class Ext_Thebing_System_Checks_Customer_Numberrange extends GlobalChecks
{
	protected $_oClient;

	protected $_aSchoolIds = array();


	public function getDescription()
	{
		return 'Update customer numberrange interface';
	}
	
	public function getTitle()
	{
		return 'Update customer numberrange interface';
	}
	
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
        
        //INDO DB Fremdschlüssel löschen da wir sonst nicht backupen können!
        
        try {
            $sSql = "ALTER TABLE `tc_number_ranges_allocations_sets_applications` DROP FOREIGN KEY `tc_number_ranges_allocations_sets_applications_ibfk_1` ;";
            DB::executeQuery($sSql);
        } catch (Exception $exc) {
            __pout($exc);
        }

        try {
            $sSql = "ALTER TABLE `tc_number_ranges_allocations_objects` DROP FOREIGN KEY `tc_number_ranges_allocations_objects_ibfk_1` ;";
            DB::executeQuery($sSql);
        } catch (Exception $exc) {
            __pout($exc);
        }


        Ext_Thebing_Util::backupTable('tc_contacts_numbers');
		Ext_Thebing_Util::backupTable('tc_number_ranges');
		Ext_Thebing_Util::backupTable('tc_number_ranges_allocations');
		Ext_Thebing_Util::backupTable('tc_number_ranges_allocations_sets');
		Ext_Thebing_Util::backupTable('tc_number_ranges_allocations_sets_applications');
		Ext_Thebing_Util::backupTable('tc_number_ranges_allocations_objects');
		
        DB::begin('Ext_Thebing_System_Checks_Customer_Numberrange');
        
		$this->_oClient		= Ext_Thebing_System::getClient();
		
		$this->_aSchoolIds	= Ext_Thebing_Client::getSchoolList(true);
		
		$iFirstSchoolId		= (int)key($this->_aSchoolIds);
        
		// Überprüfen ob Check schonmal durchgelaufen & Standardnumberrange schonmal angelegt wurde...
		$oNumberrange	= Ext_TC_Numberrange_Contact::getByApplicationAndObject('customer', $iFirstSchoolId);
		
		$iNumberrangeId = (int)$oNumberrange->id;
        
        __pout('gefundener Nummerkreis: '.$iNumberrangeId);
        
		if($iNumberrangeId <= 0){
            
            foreach($this->_aSchoolIds as $iSchool => $sSchool){
                // Check läuft das erste mal durch, Standard Numberrange erstellen
                $iNumberrangeId = (int)$this->_createNumberrangeId($iSchool);
                
                $sSql = "
                        UPDATE 
                            `tc_contacts_numbers` SET 
                                `numberrange_id` = :numberrange_id 
                        WHERE
                            `contact_id` IN (
                                SELECT 
                                    `t1`.`id`
                                FROM
                                    `tc_contacts` `t1` INNER JOIN
                                    `ts_inquiries_to_contacts` `t2` ON
                                        `t2`.`contact_id` = `t1`.`id` INNER JOIN
                                    `ts_inquiries` `t3` ON
                                        `t3`.`id` = `t2`.`inquiry_id` INNER JOIN
                                    `ts_inquiries_journeys` `t4` ON
                                        `t4`.`inquiry_id` = `t3`.`id`
                                WHERE
                                    `t4`.`school_id` = :school_id AND
                                    `t3`.`active` = 1 AND
                                    `t1`.`active` = 1
                            )
                ";
                
                $aSql = array(
                    'numberrange_id' => (int)$iNumberrangeId,
                    'school_id' => (int)$iSchool
                );
                
                DB::executePreparedQuery($sSql, $aSql);
            }
            
            
            
		}
		
		DB::commit('Ext_Thebing_System_Checks_Customer_Numberrange');
        
		return true;
	}
	
	protected function _createNumberrangeId($iSchool)
	{
		// Nummernkreis für manuelle Creditnotes anlegen
		
		$oClient				= $this->_oClient;
		
        if($oClient->customernumber_for_schools == 0){
            $iSchool            = 0;
            $iLastNumberrange   = $this->_iLastNumberrangeId;
            if($iLastNumberrange > 0){
                return $iLastNumberrange;
            }
            $aOldNumberrangeData['offset_abs']  = $oClient->customernumber_start;
            $aOldNumberrangeData['offset_rel']  = $oClient->customernumber_offset;
            $aOldNumberrangeData['digits']      = $oClient->customernumber_digits;
            $aOldNumberrangeData['format']      = $oClient->customernumber_format;
        } else {
            $aOldNumberrangeData	= Ext_Thebing_School_NumberRange::getValues($oClient->id, $iSchool, array('customer'));
            $aOldNumberrangeData	= $aOldNumberrangeData['customer'];
        }
        
		
        $sSchool = (string)$this->_aSchoolIds[$iSchool];
		
		// Nummernkreis
		$aInsert = array(
			'active'		=> '1',
			'category'		=> 'other',
			'name'			=> 'Default Customernumber '.$sSchool,
			'offset_abs'	=> $aOldNumberrangeData['offset_abs'],
			'offset_rel'	=> $aOldNumberrangeData['offset_rel'],
			'digits'		=> $aOldNumberrangeData['digits'],
			'format'		=> $aOldNumberrangeData['format'],
		);

		$iNumberrangeId		= (int)DB::insertData('tc_number_ranges', $aInsert);
		$this->_iLastNumberrangeId = $iNumberrangeId;
        
		// Zuweisung
		$aInsertAllocation = array(
			'active'		=> '1',
			'category'		=> 'other',
			'name'			=> 'Default Customernumber '.$sSchool,
		);
		
		$iAllocationId		= (int)DB::insertData('tc_number_ranges_allocations', $aInsertAllocation);
		
		if($iNumberrangeId > 0 && $iAllocationId > 0)
		{			
			// Set
			$aInsertSet = array(
				'active'			=> '1',
				'allocation_id'		=> $iAllocationId,
				'numberrange_id'	=> $iNumberrangeId,
			);
			
			$iSetId = (int)DB::insertData('tc_number_ranges_allocations_sets', $aInsertSet);
			
			if($iSetId > 0)
			{
				// Application

				$aInsertApplication = array(
					'set_id'		=> $iSetId,
					'application'	=> 'customer',
				);
				DB::insertData('tc_number_ranges_allocations_sets_applications', $aInsertApplication);
                
                $aInsertApplication = array(
					'set_id'		=> $iSetId,
					'application'	=> 'customer_agency',
				);
				DB::insertData('tc_number_ranges_allocations_sets_applications', $aInsertApplication);

				// Allocation Objects (in jede Schule zuweisen, da die Einstellung eine Mandanteneinstellung ist)
				if($iSchool <= 0){
                    $aSchoolIds  = $this->_aSchoolIds;
                } else {
                    $aSchoolIds = array($iSchool => $this->_aSchoolIds[$iSchool]);
                }

				foreach($aSchoolIds as $iSchoolId => $sSchool)
				{
					$aInsertAllocationObjects = array(
						'allocation_id' => $iAllocationId,
						'object_id'		=> $iSchoolId,
					);
					DB::insertData('tc_number_ranges_allocations_objects', $aInsertAllocationObjects);
				}	
			}
			else
			{
				__pout('Couldnt create Set for Numberrange!');
				
				return false;
			}
		}
		else
		{
			__pout('Couldnt create Numberrange or Numberrange allocation!');
			
			return false;
		}
		
		return $iNumberrangeId;
	}
	
}