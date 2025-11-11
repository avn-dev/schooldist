<?php

class Ext_TS_System_Checks_Templates_Pdf_Inboxes extends GlobalChecks {
	
	protected $_aIgnoredDocumentTypes = array(
		'document_offer_agency', 
		'document_offer_customer', 
		'document_student_requests', 
		'company_payment',
		'cheque',
		'document_transfer_payment',
		'document_accommodation_payment',
		'agency_overview',
		'manual_creditnotes',
		'document_teacher_contract_basic',
		'document_teacher_contract_additional',
		'document_accommodation_contract_basic',
		'document_accommodation_contract_additional',
		'document_teacher_payment'
	);
		
	public function getTitle() {
		return 'Allocate inboxes to pdf-templates';
	}
	
	public function getDescription() {
		return '...';
	}
	
	public function executeCheck() {
		
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		DB::begin('Ext_TS_System_Checks_Templates_Pdf_Inboxes');
		
		try {
			$aInboxes = Ext_Thebing_System::getInboxList('use_id', true);
			$aTemplates = $this->_getTemplates();

			foreach($aTemplates as $iTemplate) {
				foreach($aInboxes as $iInbox => $sInbox) {
					$aEntry = array('template_id' => $iTemplate, 'inbox_id' => $iInbox);
					#DB::insertData('kolumbus_pdf_templates_inboxes', $aEntry);

					$sSql = "
						REPLACE INTO
							`kolumbus_pdf_templates_inboxes`
						SET
							`template_id` = :template_id,
							`inbox_id` = :inbox_id
					";

					DB::executePreparedQuery($sSql, $aEntry);
				}
			}
		
		} catch(Exception $e) {
			__pout($e);
			DB::rollback('Ext_TS_System_Checks_Templates_Pdf_Inboxes');
			return false;
		}
		
		DB::commit('Ext_TS_System_Checks_Templates_Pdf_Inboxes');
		
		return true;
	}
	
	protected function _getTemplates() {
		
		$sSql = "
			SELECT	
				`id`
			FROM
				`kolumbus_pdf_templates` 
			WHERE
				`type` NOT IN (:ignored_document_types)
		";
		
		$aSql = array(
			'ignored_document_types' => $this->_aIgnoredDocumentTypes
		);
		
		$aData = (array) DB::getQueryCol($sSql, $aSql);
		
		return $aData;
	}
		
}
