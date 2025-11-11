<?php

class Ext_TS_Enquiry_Offer_Gui2_Format_Groupdetails extends Ext_Gui2_View_Format_Abstract {

	protected $_sType;
	
	public static $aMemberCache = array();
	
	public function __construct($sType = 'members') {
		$this->_sType = $sType;
	}
	
	public function format($mValue, &$oColumn = null, &$aResultData = null){
		
		$iBack = 0;
		$iId = (int)$aResultData['id'];
		
		if(empty(self::$aMemberCache[$iId])) {		
		
			$oOffer = Ext_TS_Enquiry_Offer::getInstance($iId);
			$oEnquiry = Ext_TS_Enquiry::getInstance($oOffer->enquiry_id);
			$oGroup = $oEnquiry->getGroup();
			$aAllocatedContacts = $oOffer->getAllocatedContacts();
			
			$aDetails = array(
				'guides' => array(),
				'members' => array()
			);		
			foreach($aAllocatedContacts as $oContact) {

				$oEnquiry->setTraveller($oContact);

				if($oEnquiry->isGuide()) {
					$aDetails['guides'][] = $oContact;
				}

				$aDetails['members'][] = $oContact;

			}
			
			self::$aMemberCache[$iId] = $aDetails;
			
		}
			
		if(isset(self::$aMemberCache[$iId][$this->_sType])) {
			$iBack = count(self::$aMemberCache[$iId][$this->_sType]);
		}		
		
		return $iBack;
		
	}
	
	public function align(&$oColumn = null){
		return 'right';
	}	
	
}
