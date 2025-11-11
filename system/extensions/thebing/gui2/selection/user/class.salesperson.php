<?php

/**
 * Class Ext_Thebing_Gui2_Selection_User_SalesPerson
 */
class Ext_Thebing_Gui2_Selection_User_SalesPerson extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * @param array   $aSelectedIds
	 * @param array   $aSaveField
	 * @param WDBasic $oWDBasic
	 *
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aOptions = [];
		$aUsers = self::getUsers();
		foreach($aUsers as $oUser) {
			$aOptions[$oUser->getId()] = $oUser->getName();
		}
		$aOptions = Util::addEmptyItem($aOptions);

		return $aOptions;

	}

	/**
	 * Sucht eine Verbindung zwischen einem Sales Person und einer Buchung (default value SR)
	 *
	 * @param WDBasic $oWDBasic
	 *
	 * @return array
	 */
	public function getMatchingSalesPerson($oWDBasic) {

		$oSchool = $oWDBasic->getSchool();

		/** @var Ext_TS_Inquiry_Contact_Traveller[] $aContacts */
		$aContacts = $oWDBasic->getJoinTableObjects('bookers');

		if(empty($aContacts)) {
			$aContacts = $oWDBasic->getJoinTableObjects('travellers');
		}

		$oContact = reset($aContacts);

		/* @var $oSalesPersonRepo Ext_Thebing_Salesperson_SettingRepository */
		$oSalesPersonRepo = Ext_Thebing_Salesperson_Setting::getRepository();
		
		$oSalesPersonSetting = null;
		
		if($oWDBasic->hasAgency()) {
			$oAgency = $oWDBasic->getAgency();
			$oSalesPersonSetting = $oSalesPersonRepo->getSettingBySchoolAndAgency($oSchool, $oAgency);
		}

		if(
			(
				$oSalesPersonSetting === null &&
				$oContact instanceof Ext_TS_Contact &&
				!empty($oContact->nationality)
			) ||
			(
				$oSalesPersonSetting === null &&
				$oContact === false &&
				!empty($oWDBasic->nationality)
			)

		) {
			// Workaround, da die Enquiry eine Eigenschaft mit Nationalität hat und dort der Wert enthalten ist
			if ($oWDBasic instanceof Ext_TS_Enquiry) {
				$sNationality = $oWDBasic->nationality;
			} else {
				$sNationality = $oContact->nationality;
			}

			$oSalesPersonSetting = $oSalesPersonRepo->getSettingBySchoolAndNationality($oSchool, $sNationality);
		}
		
		if($oSalesPersonSetting instanceof Ext_Thebing_Salesperson_Setting) {
			$oUser = $oSalesPersonSetting->getUser();
			return $oUser;
		}
		
	}

	/**
	 * Holt alle Benutzer die eine Sales Person sind.
	 *
	 * @return Ext_Thebing_User[]
	 */
	public static function getUsers() {
		return Ext_Thebing_User::getRepository()->getSalesPersons();
	}
}