<?php

use TsAgencyLogin\Combination\Agency as AgencyLogin;
use TsFrontend\Generator\PaymentFormGenerator;
use TsRegistrationForm\Generator\CombinationGenerator as RegistrationCombination;
use TsContactLogin\Combination\Contact as ContactLogin;
use Smarty\Smarty;

class Ext_TS_Frontend_Combination extends Ext_TC_Frontend_Combination {

	/**
	 * @var array
	 */
	protected $_aAttributes = [
		'payment_providers' => ['type' => 'array']
	];

	/**
	 * @var array
	 */
	protected $_aScalarItems = array(
		'language',
		'password_security',
		'school',
		'form',
		'use_iframe',
		'use_css_bundle',
		'domains',
		'template',
		'template_submit_success',
		'course_slug',
		'course_category',
		'accommodation_category',
		'url'
	);

	/**
	 * @see \Ext_TS_Frontend_Template_Gui2_Data::getUsageOptions()
	 *
	 * @param Smarty|null $oSmarty
	 *
	 * @return Ext_TC_Frontend_Combination_Abstract|Ext_TS_Frontend_Combination_Enquiry|Ext_TS_Frontend_Combination_Inquiry|Ext_TS_Frontend_Combination_Pricelist|AgencyLogin|RegistrationCombination
	 */
	protected function _getObjectForUsage(Smarty $oSmarty = null) {

		switch($this->usage) {
			case Ext_Thebing_Form::TYPE_CONTACT_PORTAL:
				return new ContactLogin($this, $oSmarty);
			case Ext_Thebing_Form::TYPE_REGISTRATION_V3:
				return new RegistrationCombination($this, $oSmarty);
			case Ext_Thebing_Form::TYPE_REGISTRATION_NEW:
				return new Ext_TS_Frontend_Combination_Inquiry($this, $oSmarty);
			case Ext_Thebing_Form::TYPE_ENQUIRY:
				return new Ext_TS_Frontend_Combination_Enquiry($this, $oSmarty);
			case 'pricelist':
				return new Ext_TS_Frontend_Combination_Pricelist($this, $oSmarty);
			case 'agency_login':
				return new AgencyLogin($this, $oSmarty);
			case 'payment_form':
				return new PaymentFormGenerator($this, $oSmarty);
			case 'placementtest':
				return new TsFrontend\Generator\PlacementTestGenerator($this, $oSmarty);
			case 'course_details':
				return new TsFrontend\Generator\CourseDetailsGenerator($this, $oSmarty);
			case 'course_list':
				return new TsFrontend\Generator\CourseListGenerator($this, $oSmarty);
			case 'accommodation_categories':
				return new TsFrontend\Generator\AccommodationCategoriesGenerator($this, $oSmarty);
			case 'course_categories':
				return new TsFrontend\Generator\CourseCategoriesGenerator($this, $oSmarty);
			case 'accommodation_category':
				return new TsFrontend\Generator\AccommodationCategoryGenerator($this, $oSmarty);
			case 'course_category':
				return new TsFrontend\Generator\CourseCategoryGenerator($this, $oSmarty);
		}

		return parent::_getObjectForUsage($oSmarty);
	}

	public function save($bLog = true) {

		// Sind wir mal so nett und entfernen den potentiellen Benutzerfehler
		if (!empty($this->items_domains)) {
			$this->items_domains = str_replace('http://', '', $this->items_domains);
			$this->items_domains = str_replace('https://', '', $this->items_domains);
		}

		return parent::save($bLog);

	}

}
