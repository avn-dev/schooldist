<?php

class Ext_Thebing_Agency_Manual_Creditnote_Placeholder extends Ext_Thebing_Agency_Placeholder {

	/**
	 * @var Ext_Thebing_Agency_Manual_Creditnote
	 */
	protected $_oCreditnote;

	/**
	 * @param null|int $iCN
	 */
	public function  __construct($iCN = null) {

		if($iCN instanceof Ext_Thebing_Agency_Manual_Creditnote) {
			$this->_oCreditnote = $iCN;
		} else {
			$this->_oCreditnote = Ext_Thebing_Agency_Manual_Creditnote::getInstance((int)$iCN);
		}

		parent::__construct((int)$this->_oCreditnote->agency_id, 'agency');

		$aFlexFields = (array)Ext_TC_Flexibility::getSectionFieldData(array($this->_sSection));

		$this->_aFlexFields = array();
		foreach($aFlexFields as $aField) {
			if(!empty($aField['placeholder'])) {
				$this->_aFlexFields[$aField['placeholder']] = $aField['id'];
			}
		}

	}

	/**
	 * Get the list of available placeholders
	 *
	 * @param string $sType
	 * @return array
	 */
	public function getPlaceholders($sType = '') {

		$aPlaceholders = $this->getOwnPlaceholders();

		// Get parent placeholders
		$aParentPlaceholders = parent::getPlaceholders();

		// Add parent placeholders
		$aPlaceholdersAll = array_merge($aParentPlaceholders, $aPlaceholders);

		return $aPlaceholdersAll;
	}

	/**
	 * @return array
	 */
	public function getOwnPlaceholders() {

		$aPlaceholders = [
			[
				'section' => L10N::t('Manuelle Creditnotes', Ext_Thebing_Agency_Manual_Creditnote_Gui2::getDescriptionPart()),
				'placeholders' => array(
					//'creditnote_type' => L10N::t('Art der Creditnote', Ext_Thebing_Agency_Manual_Creditnote_Gui2::getDescriptionPart()),
					'manual_credit_note_amount'	=> L10N::t('Betrag der Creditnote', Ext_Thebing_Agency_Manual_Creditnote_Gui2::getDescriptionPart()),
					'manual_credit_note_currency' => L10N::t('Währung der Creditnote', Ext_Thebing_Agency_Manual_Creditnote_Gui2::getDescriptionPart()),
					'manual_credit_note_note' => L10N::t('Kommentar der Creditnote', Ext_Thebing_Agency_Manual_Creditnote_Gui2::getDescriptionPart()),
					'manual_credit_note_subject' => L10N::t('Grund der Creditnote', Ext_Thebing_Agency_Manual_Creditnote_Gui2::getDescriptionPart()),
					'manual_credit_note_number'	=> L10N::t('CN Nr.', Ext_Thebing_Agency_Manual_Creditnote_Gui2::getDescriptionPart())
				)
			]
		];

		return $aPlaceholders;

	}

	protected function _getReplaceValue($sPlaceholder, array $aPlaceholder) {

		$oCN = $this->_oCreditnote;

		switch($sPlaceholder) {
//			case 'creditnote_type':
//				$sValue = L10N::t('Einmalig', Ext_Thebing_Agency_Manual_Creditnote_Gui2::getDescriptionPart());
//				break;
			case 'manual_credit_note_amount':
			case 'creditnote_amount':
				$sValue = Ext_Thebing_Format::Number($oCN->amount);
				break;
			case 'manual_credit_note_currency':
			case 'creditnote_currency':
				$oCurrency = Ext_Thebing_Currency::getInstance($oCN->currency_id);
				$sValue = $oCurrency->getSign();
				break;
			case 'creditnote_comment':
			case 'manual_credit_note_note':
				$sValue = $oCN->comment;
				break;
			case 'manual_credit_note_subject':
			case 'creditnote_reason':
				$aReasons	= Ext_Thebing_Client::getFirstClient()->getReasons();
				$aReasons[0] = '';
				$sValue = $aReasons[$oCN->reason_id];
				break;
			case 'manual_credit_note_number':
			case 'creditnote_documentnumber':
				$sValue = $oCN->document_number;
				break;
			default:
				$sValue = parent::_getReplaceValue($sPlaceholder, $aPlaceholder);
				break;
		}

		return $sValue;
	}

}