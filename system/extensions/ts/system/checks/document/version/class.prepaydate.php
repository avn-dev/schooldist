<?php

class Ext_TS_System_Checks_Document_Version_PrepayDate extends GlobalChecks {
	
	protected $_aDebug = array();
	
	public function getTitle() {
		return 'Group invoices';
	}
	
	public function getDescription() {
		return 'Prepares the prepay amount of group invoices';
	}
	
	public function executeCheck() {

		// Der Check DARF NICHT noch einmal ausgeführt werden, da dieser ansonsten die Anrechnungsbeträge weiter stückelt!
		throw new RuntimeException();

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		$bBackUp = Ext_TC_Util::backupTable('kolumbus_inquiries_documents_versions');
		
		if(!$bBackUp) {
			__pout('Backup error!');
			return false;
		}
		
		DB::begin('Ext_TS_System_Checks_Document_Version_PrepayDate');
		
		try {
			
			$aDocuments = $this->_getDocuments();
			
			foreach($aDocuments as $iDocument) {
				
				$oDocument = Ext_Thebing_Inquiry_Document::getInstance($iDocument);

				$this->_calculatePrepayAmount($oDocument);		
				
				WDBasic::clearAllInstances();
				
			}
						
		} catch (Exception $e) {
			__pout($this->debug());
			__pout($e);
			DB::rollback('Ext_TS_System_Checks_Document_Version_PrepayDate');
			return false;
		}
		
		DB::commit('Ext_TS_System_Checks_Document_Version_PrepayDate');
		return true;
		
	}
	
	public function debug() {
		return $this->_aDebug;
	}
	
	protected function _calculatePrepayAmount(Ext_Thebing_Inquiry_Document $oDocument){		
		$oVersion = $oDocument->getLastVersion();
		
		if(
			$oVersion &&
			$oVersion->amount_prepay > 0
		) {
			
			$fGroupPrepayAmount = $oVersion->amount_prepay;
			$bCalculate = $oVersion->calculatePrepayAmount($fGroupPrepayAmount);	
			
			$oInquiry = $oDocument->getInquiry();
			
			$aGroupInquiries = array();
			if($oInquiry) {
				$oGroup = $oInquiry->getGroup();
				if($oGroup) {
					$aGroupInquiries = $oGroup->getInquiries();
				}			
			}
			if($bCalculate) {
				$this->_aDebug[] = array(
					'document_id' => $oDocument->id,
					'document_type' => $oDocument->type,
					'group_inquiries' => count($aGroupInquiries),
					'group_amount' => number_format($oVersion->getGroupAmount(), 5, ',' , '.'),
					'student_amount' => $oVersion->getAmount(),
					'inquiry_id' => $oInquiry->id,
					'document_number' => $oDocument->document_number,
					'whole_prepay_amount' => $fGroupPrepayAmount,
					'new_prepay_amount_per_student' => $oVersion->amount_prepay
				);
			}
		}
	}
	
	protected function _getDocuments() {
		
		$sSql = "
			SELECT
				`kd`.`id`
			FROM
				`kolumbus_inquiries_documents` `kd` LEFT JOIN	
				`ts_documents_release` `dr` ON
					 `kd`.`id` = `dr`.`document_id`
			WHERE
				`kd`.`active` = 1 AND
				`dr`.`document_id` IS NULL
		";
		
		$aDocuments = (array) DB::getQueryCol($sSql);
		
		return $aDocuments;
		
	}
	
}
