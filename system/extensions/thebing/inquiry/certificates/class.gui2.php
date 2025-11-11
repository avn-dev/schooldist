<?php

class Ext_Thebing_Inquiry_Certificates_Gui2 extends Ext_Thebing_Document_Gui2
{
    public function executeGuiCreatedHook()
    {
        $this->_oGui->name = 'ts_tuition_certificate';
        $this->_oGui->set = ''; // Darf nicht null sein (Legacy-HTML war leerer String)

        $oInquiryAdditionalDocuments = new Ext_Thebing_Inquiry_Document_Additional();
        $oInquiryAdditionalDocuments->use_template_type = 'document_certificates';
        $oInquiryAdditionalDocuments->icons_at_first_pos = true;
        $oInquiryAdditionalDocuments->access_document_edit = 'thebing_tuition_certificates_documents';
        $oInquiryAdditionalDocuments->access_document_open = 'thebing_tuition_certificates_display_documents';
        $this->_oGui->addAdditionalDocumentsOptions($oInquiryAdditionalDocuments);

        $this->_oGui->multiple_pdf_class = new Ext_Thebing_Gui2_Pdf_Cards('document_certificates');
    }

	protected function  _prepareTableQueryData(&$aSql, &$sSql)
	{
		$oSchool				= Ext_Thebing_School::getSchoolFromSession();
		$sInterfaceLanguage		= $oSchool->getInterfaceLanguage();
		$sNameField				= 'name_'.substr($sInterfaceLanguage, 0, 2);

		$aSql['name_field']		= $sNameField;
	}

	public static function getOrderby()
	{
		return ['cdb1.lastname' => 'ASC'];
	}

	public static function getFromDate()
	{
		return Ext_Thebing_Format::LocalDate(\Carbon\Carbon::today());
	}

	public static function getUntilDate()
	{
		return Ext_Thebing_Format::LocalDate(\Carbon\Carbon::today()->addWeek());
	}

	public static function getCourses()
	{
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aCourses = $oSchool->getCourseList();

		return $aCourses;
	}

	public static function getVisumStatusList()
	{
		$aVisumStatusList = Ext_Thebing_Visum::getVisumStatusList(Ext_Thebing_School::getSchoolFromSession()->id);
//		$aVisumStatusList = Ext_Gui2_Util::addLabelItem($aVisumStatusList, Ext_Thebing_L10N::getEmptySelectLabel('visum'));

		return $aVisumStatusList;
	}

	public static function getYesNo(Ext_Thebing_Gui2 $oGui)
	{
		$aYesNo = [
			'1' => $oGui->t('Ja'),
			'2' => $oGui->t('Nein')
		];

		return $aYesNo;
	}

	public static function getCategories()
	{
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
//		$aCategories = array_combine($aCategories, $aCategories); // Komischer Sonderfall
		return $oSchool->getCourseCategoriesList('select');
	}

	public static function getInboxes()
	{
		$oClient = Ext_Thebing_System::getClient();
		return $oClient->getInboxList(true, true);
	}

	public static function getCriticalOptions(Ext_Thebing_Gui2 $oGui)
	{
		$aCritical= [
			'critical' => $oGui->t('kritisch'),
			'non_critical' =>$oGui->t('nicht kritisch'),
		];
		return $aCritical;
	}

	public static function getCriticalOptionsFilterQuery()
	{
		$iCriticalPoint = Ext_Thebing_School::getSchoolFromSession()->critical_attendance;
		return [
			'critical' => "`attendance_all` <= $iCriticalPoint",
			'non_critical' => "`attendance_all` > $iCriticalPoint OR `attendance_all` IS NULL",
		];

	}
}
