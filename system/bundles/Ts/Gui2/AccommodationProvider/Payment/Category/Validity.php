<?php

namespace Ts\Gui2\AccommodationProvider\Payment\Category;

class Validity extends \Ext_TC_Validity_Gui2 {

	public function __construct() {

		$sHash = md5('ts_accommodation_payment_category');
		$sDataClass = '\Ts\Gui2\AccommodationProvider\Payment\Category\ValidityData';

		parent::__construct($sHash, $sDataClass);

		$oPaymentCategories = new \Ts\Entity\AccommodationProvider\Payment\Category();
		$aPaymentCategories = $oPaymentCategories->getArrayList(true);
		$oSelection = new \Ext_Thebing_Gui2_Selection_School_PaymentCategory();

		$this->setWDBasic('\Ts\Entity\AccommodationProvider\Payment\Category\Validity');
		$this->setValiditySelectSettings($aPaymentCategories, $oSelection);
		$this->setTableData('limit', 30);

	}

}
