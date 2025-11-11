<?php

// TODO 16002 Entfernen
class Ext_TS_Enquiry_Offer_Placeholder extends Ext_Thebing_Inquiry_Placeholder
{
	
	protected $_oEnquiry = null;	

	public function __construct(Ext_Thebing_Inquiry_Document $oDocument=null, $iSchoolForFormat = 0)
	{		
		$this->_oDocument			= $oDocument;

		$this->_iSchoolForFormat =	 (int)$iSchoolForFormat;

		if($oDocument){
			
			$oEnquiry = $oDocument->getEnquiry();
			
			$this->_oEnquiry = $oEnquiry;
			$this->_oInquiry = $oEnquiry;
		
			if(
				is_object($this->_oEnquiry) && 
				$this->_oEnquiry instanceof Ext_TS_Enquiry
			)
			{
				$this->_oCustomer = $oEnquiry->getFirstTraveller();

				$this->_oAgency = $oEnquiry->getAgency();
			}
			
		}
		
		if($this->_oInquiry)
		{
			$this->_aInquiryCourses			= $this->_oInquiry->getCourses();
			$this->_aJourneyAccomondations	= $this->_oInquiry->getAccommodations();

			// Transferobjecte
			$this->_oTransferArrival		= $this->_oInquiry->getTransfers('arrival', true);
			$this->_oTransferDeparture		= $this->_oInquiry->getTransfers('departure', true);

			// Wenn kein individueller Platzhalter durch die Schleife gesetzt wird, wird der 1. besste
			// gesetzt, damit die Platzhalter auch ohne Schleife funktionieren 
			$this->_setAdditionalTransfer();

			$this->_oSchool					= $this->_oInquiry->getSchool();

			if(
				is_object($this->_oSchool) &&
				$this->_oSchool instanceof Ext_Thebing_School
			){
					$this->_aLanguages	= $this->_oSchool->getLanguageList();
				}else{
					$this->_oSchool = Ext_Thebing_School::getSchoolFromSession();
			}	
		}
		
	}
    
    protected function _setAdditionalTransfer($oTransfer = null) {
		
		if($this->_oInquiry instanceof Ext_TS_Inquiry_Abstract){
			if($oTransfer instanceof Ext_TS_Service_Interface_Transfer){
				$this->_oTransferAdditional = $oTransfer;
			} else {
				$aAdditionalTransfers = $this->_oInquiry->getTransfers('additional', false);
				if(!empty($aAdditionalTransfers)) {
					$this->_oTransferAdditional = reset($aAdditionalTransfers);
				}
			}
		}
	}
	
	/**
	 * Musste ich jetzt leider so überschreiben, das ganze Service Zeug muss irgendwo anders hin, wohin genau müssen wir uns noch überlegen :)
	 * @todo für das oben beschriebene irgendwas überlegen
	 * 
	 * @return string
	 */
	public function displayPlaceholderTable($iCount = 1, $aFilter = array(), $sType = '')
	{

		$aPlaceholders = $this->getPlaceholders($sType);

		$aFlexPlaceholders = array();
		foreach((array)$this->_aFlexFieldLabels as $sPlaceholder=>$sLabel) {

			$aFlexPlaceholders[$sPlaceholder] = $sLabel;

		}

		if(!empty($aFlexPlaceholders)) {
			$aPlaceholders[] = array(
				'section'=>L10N::t('Individuelle Felder', 'Thebing » Placeholder'),
				'placeholders'=>$aFlexPlaceholders
			);
		}

		$sHtml = self::printPlaceholderList($aPlaceholders);

		return $sHtml;
	}
	
	public function getPlaceholders($sType = '')
	{		
		$this->buildPlaceholderTableData();
		
		$aPlaceholdersGeneral				= $this->_getPlaceholders('general');
		$aPlaceholderCustomer				= $this->_getPlaceholders('customer');
		$aPlaceholderAgency					= $this->_getPlaceholders('agency');
		$aPlaceholderPdf					= $this->_getPlaceholders('pdf');
		$aPlaceholderNumbers				= $this->_getPlaceholders('numbers');
		$aPlaceholderGroup					= $this->_getPlaceholders('group');
		$aPlaceholderCourse					= $this->_getPlaceholders('course');
		$aPlaceholderAccommodation			= $this->_getPlaceholders('accommodation');
		$aPlaceholderTransfer				= $this->_getPlaceholders('transfer');
		$aPlaceholderAdditionalTransfer		= $this->_getPlaceholders('additional_transfer');
		$aPlaceholderAgencyBank				= $this->_getPlaceholders('agency_bank');
		$aPlaceholderInsurance				= $this->_getPlaceholders('insurance');
		$aPlaceholderDocument				= $this->_getPlaceholders('document');
		
		$aPlaceholders = array(
			$aPlaceholdersGeneral,
			$aPlaceholderPdf,
			$aPlaceholderNumbers,
			$aPlaceholderCustomer,
			$aPlaceholderGroup,
			$aPlaceholderCourse,
			$aPlaceholderAccommodation,
			$aPlaceholderTransfer,
			$aPlaceholderAdditionalTransfer,
			$aPlaceholderAgency,
			$aPlaceholderAgencyBank,
			$aPlaceholderInsurance,
            $aPlaceholderDocument
		);

		return $aPlaceholders;
	}
	
	/**
	 *
	 * @return Ext_Thebing_Agency
	 */
	public function getAgency()
	{
		return $this->_oAgency;
	}
	
	/**
	 *
	 * @return Ext_Thebing_School 
	 */
	public function getSchool()
	{
		$oSchool = $this->_oEnquiry->getSchool();

		return $oSchool; 
	}
	
	/**
	 * @return Ext_TS_Enquiry
	 */
	public function getMainObject()
	{
		return $this->_oEnquiry;
	}
	
	public function searchPlaceholderValue($sField, $iOptionalParentId, $aPlaceholder=array()) {
		
		$mValue = '';
		
		$oEnquiry				= $this->_oEnquiry;
		$oDateFormat			= new Ext_Thebing_Gui2_Format_Date();
		
		switch($sField) {
			case 'date_entry':
				$sDate = $oEnquiry->created;
				$mValue = $oDateFormat->format($sDate);
			break;
			default:
				$mValue = parent::searchPlaceholderValue($sField, $iOptionalParentId, $aPlaceholder);
		}
		
		return $mValue;
	}

    public function getAgencyMasterContact() {
        $oAgency = $this->getAgency();
        $oMaster = $oAgency->getMasterContact();
        return $oMaster;
    }

    public function getCustomer() {
        return $this->_oEnquiry->getFirstTraveller();
    }	
	
}