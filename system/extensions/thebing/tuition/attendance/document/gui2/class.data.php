<?php

class Ext_Thebing_Tuition_Attendance_Document_Gui2_Data extends Ext_TS_Inquiry_Document_Gui2_Data {

	/**
	 * {@inheritdoc}
	 */
	public function getDocument($_VARS, Ext_TS_Inquiry_Abstract $oInquiryAbstract, $sType) {

		$aParentIds = $this->_oGui->getParentGuiIds();

		$aDecodedParentIds = $this->_oGui->getParent()->decodeId($aParentIds);
		$aDecodedParentId = reset($aDecodedParentIds);
		
		$iInquiryCourseAllocationId = $aDecodedParentId['id'];

		$oInquiryDocument = Ext_Thebing_Tuition_Attendance_Document::getInstance();
		$oInquiryDocument->entity = Ext_TS_Inquiry::class;
		$oInquiryDocument->entity_id = $oInquiryAbstract->getId();
		$oInquiryDocument->active = 1;
		$oInquiryDocument->type = $sType;

		if(!empty($iInquiryCourseAllocationId)) {
			$oInquiryDocument->inquiry_course_allocation_id = $iInquiryCourseAllocationId;
		}

		if(!empty($_VARS['filter']['week_from_filter'])) {
			$oInquiryDocument->week_from_filter = $_VARS['filter']['week_from_filter'];
		}

		if(!empty($_VARS['filter']['week_until_filter'])) {
			$oInquiryDocument->week_until_filter = $_VARS['filter']['week_until_filter'];
		}

		return $oInquiryDocument;
	}

}