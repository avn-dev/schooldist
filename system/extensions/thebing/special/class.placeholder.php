<?php

class Ext_Thebing_Special_Placeholder extends Ext_Thebing_Placeholder {

	/**
	 * @var Ext_Thebing_School_Special
	 */
	protected $oSpecial;

	/**
	 * @param Ext_Thebing_School_Special $oSpecial
	 */
	public function  __construct(Ext_Thebing_School_Special $oSpecial = null) {
		parent::__construct();
		$this->oSpecial = $oSpecial;
	}

	/**
	 * @param string $sType
	 * @return array
	 */
	public function getPlaceholders($sType = '') {

		$aPlaceholders = [
			[
				'section' => L10N::t('Sonderangebote', Ext_Thebing_Agency_Manual_Creditnote_Gui2::getDescriptionPart()),
				'placeholders' => [
					'special_title' => Ext_Thebing_Special_Gui2::specialT('Bezeichnung'),
					'special_validation' => Ext_Thebing_Special_Gui2::specialT('Gültigkeit'),
					'special_based_on' => Ext_Thebing_Special_Gui2::specialT('Ausgehend von'),
					'special_valid_from' => Ext_Thebing_Special_Gui2::specialT('Gültig ab'),
					'special_valid_until' => Ext_Thebing_Special_Gui2::specialT('Deaktiviert ab'),
					'special_availability_type' => Ext_Thebing_Special_Gui2::specialT('Art der Verfügbarkeit'),
					'special_availability_quantity' => Ext_Thebing_Special_Gui2::specialT('Anzahl der Verfügbarkeit'),
					'special_dependency_status' => Ext_Thebing_Special_Gui2::specialT('Abhängig von Schülerstatus'),
					'special_discount_type' => Ext_Thebing_Special_Gui2::specialT('Art des Rabatts')
				]
			]
		];

		return $aPlaceholders;

	}

	/**
	 * @param string $sPlaceholder
	 * @param array $aPlaceholder
	 * @return string
	 */
	protected function _getReplaceValue($sPlaceholder, array $aPlaceholder) {

		$sValue = '';

		if(empty($this->oSpecial)) {
			return $sValue;
		}

		switch($sPlaceholder) {
			case 'special_title':
				$sValue = $this->oSpecial->getName();
				break;
			case 'special_validation':
				$sValue = $this->oSpecial->visible;
				break;
			case 'special_based_on':
				
				$basedOn = [];
				if(
					!empty($this->oSpecial->created_from) &&
					!empty($this->oSpecial->created_until)
				) {
					$basedOn[] = Ext_Thebing_Special_Gui2::specialT('Erstellungszeitraum');
				}
				if(
					!empty($this->oSpecial->service_from) &&
					!empty($this->oSpecial->service_until)
				) {
					$basedOn[] = Ext_Thebing_Special_Gui2::specialT('Leistungszeitraum');
				}
				
				$sValue = implode(', ', $basedOn);
				
				break;
			case 'special_valid_from':
			case 'special_created_from':
				$sValue = (new Ext_Thebing_Gui2_Format_Date())->format($this->oSpecial->created_from);
				break;
			case 'special_valid_until':
			case 'special_created_until':
				$sValue = (new Ext_Thebing_Gui2_Format_Date())->format($this->oSpecial->created_until);
				break;
			case 'special_availability_type':
				$aLimitTypes = Ext_Thebing_School_Special::getLimitTypes();
				if(isset($aLimitTypes[$this->oSpecial->limit_type])) {
					$sValue = $aLimitTypes[$this->oSpecial->limit_type];
				}
				break;
			case 'special_availability_quantity':
				$sValue = $this->oSpecial->limit;
				break;
			case 'special_dependency_status':
				$sValue = $this->oSpecial->use_student_status;
				break;
			case 'special_discount_type':
				$aAmountTypes = Ext_Thebing_School_Special::getAmountTypes();
				if(isset($aAmountTypes[$this->oSpecial->amount_type])) {
					$sValue = $aAmountTypes[$this->oSpecial->amount_type];
				}
				break;
			default:
				$sValue = parent::_getReplaceValue($sPlaceholder, $aPlaceholder);
		}

		return $sValue;

	}

}
