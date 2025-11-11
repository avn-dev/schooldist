<?php

/**
 * @see \Ext_Thebing_Document::getDialog()
 */
class Ext_Thebing_Document_Gui2_List extends Ext_Thebing_Document_Gui2 {

	public $aListCache	= array();

	/**
	 * @TODO Diese Methode kann eigentlich gar nicht mehr aufgerufen werden, da es nur noch die Elasticsearch-GUI gibt
	 */
	public function getTableQueryData($aFilter = array(), $aOrderBy = array(), $aSelectedIds = array(), $bSkipLimit = false) {

		throw new \RuntimeException('\Ext_Thebing_Document_Gui2_List::getTableQueryData called');

		$aResult = parent::getTableQueryData($aFilter, $aOrderBy, $aSelectedIds, $bSkipLimit);

		// Cache darf nur gebildet werden, wenn kein Eintrag selektiert ist
		if(empty($aSelectedIds)) {

			/**
			 * Documente in Cache schreiben für die Icon-Klasse
			 * @author MK
			 */
			$aInvoiceTypes = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');		

			$this->aListCache = array();
			$aInvoices = array();
			$aActiveMainInvoices = array();
			$this->aListCache['latest_invoice'] = 0;
			foreach((array)$aResult['data'] as $aRow) {
				$this->aListCache['data'][$aRow['id']] = array(
					'type'=>$aRow['type'],
					'is_credit'=>$aRow['is_credit']
				);

				if(
					$aRow['type'] == 'brutto' ||
					$aRow['type'] == 'netto'
				) {
					if(!isset($aActiveMainInvoices[$aRow['type']])) {
						$aActiveMainInvoices[$aRow['type']] = 0;
					}
					if($aRow['is_credit'] == 0) {
						$aActiveMainInvoices[$aRow['type']]++;
					} else {
						$aActiveMainInvoices[$aRow['type']]--;
					}
				}elseif(
					$aRow['type'] == 'credit_brutto' ||
					$aRow['type'] == 'credit_netto'
				){
					//Abwärtskompabilität, siehe [#1217]
					$NewType = substr($aRow['type'], 7, strlen($aRow['type']) - 7);
				
					if(!isset($aActiveMainInvoices[$NewType])){
						$aActiveMainInvoices[$NewType] = 0;
					}
					
					$aActiveMainInvoices[$NewType]--;
				}

				if(in_array($aRow['type'], $aInvoiceTypes)) {
					$aInvoices[] = $aRow['id'];
					if($this->aListCache['latest_invoice'] === 0) {
						$this->aListCache['latest_invoice'] = $aRow['id'];
					}
				}

			}

			$this->aListCache['new_invoice'] = true;
			foreach($aActiveMainInvoices as $sType=>$iCount) {
				if($iCount > 0) {
					$this->aListCache['new_invoice'] = false;
					break;
				}
			}

			
			if(empty($aInvoices)) {
				$this->aListCache['invoices'] = false;			
			} else {
				$this->aListCache['invoices'] = true;
			}

			$this->_oGui->save();
		}

		return $aResult;

	}

	/**
	 * @TODO Diese Methode kann eigentlich gar nicht mehr aufgerufen werden, da es nur noch die Elasticsearch-GUI gibt
	 */
	protected function _prepareTableQueryData(&$aSql, &$sSql) {
		global $_VARS;

		throw new \RuntimeException('\Ext_Thebing_Document_Gui2_List::_prepareTableQueryData');

		parent::_prepareTableQueryData($aSql, $sSql);
		
		$aParentIds		= (array)$_VARS['parent_gui_id'];
		$sTemplateType	= $this->_oGui->getOption('template_type');		
			
		$oParentGui		= $this->_getParentGui();
		if(
			$this->_oGui->decode_parent_primary_key && 
			$oParentGui
		){
			$aParentIds = $oParentGui->decodeId($aParentIds, $this->_oGui->parent_primary_key);
		}
		
		$iParentId		= (int)reset($aParentIds);

		if($sTemplateType == 'document_student_requests')
		{
			$oEnquiry	= Ext_TS_Enquiry::getInstance($iParentId);
			$iEnquiryId	= (int)$oEnquiry->id;
			$oInquiry	= $oEnquiry->getFirstInquiry(); 
			if($oInquiry){
				$iInquiryId	= (int)$oInquiry->id;
			}else{
				$iInquiryId = 0;
			}
		}
		else
		{
			$oInquiry	= Ext_TS_Inquiry::getInstance($iParentId);
			$iInquiryId	= (int)$oInquiry->id;
			$oEnquiry	= $oInquiry->getFirstEnquiry();
			if($oEnquiry){
				$iEnquiryId = (int)$oEnquiry->id;
			}else{
				$iEnquiryId = 0;
			}
		}

		$aSql['inquiry_id'] = $iInquiryId;
		$aSql['enquiry_id'] = $iEnquiryId;
	}
	
}