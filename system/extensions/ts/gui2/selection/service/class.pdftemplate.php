<?php

class Ext_TS_Gui2_Selection_Service_PdfTemplate extends \Ext_Gui2_View_Selection_Abstract {

	protected $sServiceType;

	public function __construct($sServiceType) {
		$this->sServiceType = $sServiceType;
	}

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		if(
			Ext_Thebing_System::isAllSchools() &&
			in_array($this->sServiceType, ['course', 'transfer'])
		) {
			throw new RuntimeException('Invalid use of class '.__CLASS__);
		}

		$aTypes = [
			'document_loa',
			'document_studentrecord_additional_pdf'
		];

		switch($this->sServiceType) {
			case 'course':
				$mSchool = [Ext_Thebing_School::getSchoolIdFromSession()];
				break;
			case 'accommodation':
				$aTypes[] = 'document_accommodation_communication';
				$mSchool = $oWDBasic->schools;
				break;
			case 'transfer':
				$aTypes[] = 'document_transfer_additional_pdf';
				$mSchool = [Ext_Thebing_School::getSchoolIdFromSession()];
				break;
			case 'insurance':
				$aTypes[] = 'document_insurances';
				$mSchool = false;
				break;
			case 'activity':
				$mSchool = false;
				break;
			default:
				throw new InvalidArgumentException('Invalid service type');
		}

		$aTemplates = Ext_Thebing_Pdf_Template_Search::s($aTypes, false, $mSchool, null, true);
		return $aTemplates;

	}

}