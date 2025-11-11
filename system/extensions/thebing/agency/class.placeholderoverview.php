<?php
/*
 * Agentur PDF �bersichts Platzhalter
 */
class Ext_Thebing_Agency_Placeholderoverview extends Ext_Thebing_Agency_Placeholder {

	protected $_oAgency;

	public function __construct($iObjectId = null)
	{

		parent::__construct($iObjectId, 'agency');

		if(is_null($iObjectId))
		{
			return;
		}

		$this->_oAgency = Ext_Thebing_Agency::getInstance($iObjectId);

	}

	protected function _helperReplaceVars($sText, $iOptionalId = 0) {

		# overview mit "v" und "w" (war mit "v" eine lange Zeit, also muss das auch noch berücksichtigt werden)
		$sText = preg_replace_callback('@\{start_loop_overviev_staffmembers\}(.*?)\{end_loop_overviev_staffmembers\}@ims', [$this, '_helperReplaceAgencyStaffDialogLoop'], $sText);
		$sText = preg_replace_callback('@\{start_loop_overview_staffmembers\}(.*?)\{end_loop_overview_staffmembers\}@ims', [$this, '_helperReplaceAgencyStaffDialogLoop'], $sText);

		$sText = parent::_helperReplaceVars($sText, $iOptionalId);

		$sText = $this->_helperReplaceVars2($sText, $iOptionalId);

		return $sText;

	}

	/**
	 * ELC-Feature!
	 * TODO: Nach Umstellung auf Dokumentendialog entfernen (kann Nutzer dann selber eintragen)
	 *
	 * @param $aText
	 * @return string
	 */
	protected function _helperReplaceAgencyStaffDialogLoop($aText) {

		$this->addMonitoringEntry('start_loop_overview_staffmembers');

		$sText = '';
		foreach((array)$this->getAdditionalData('overview_contacts') as $iContactId) {
			$oContact = Ext_Thebing_Agency_Contact::getInstance($iContactId);
			$this->_oAgencyStaff = $oContact;

			$oPlaceholder = new Ext_Thebing_Agency_Member_Placeholder($oContact->id);
			$sTextTemp = $oPlaceholder->replace($aText[1]);

			$sText .= $this->_helperReplaceVars($sTextTemp);
		}

		// wieder reseten damit nicht schleifen platzhalter normal ersetzt werden
		$this->_oAgencyStaff = null;

		return $sText;

	}

	/**
	 * Get the list of available placeholders
	 * 
	 * @return array
	 */
	public function getPlaceholders($sType = '') {

		$aPlaceholders = array(
			array(
				'section'		=> L10N::t('Agenturen Übersicht', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
				'placeholders'	=> array(
					'agency_statistic'			=> L10N::t('Agentur Statistik', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_statistic_without_revenues' => L10N::t('Agentur-Statistik (ohne Beträge)', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_statistic_students_at_school' =>  L10N::t('Agentur-Statistik: Schüler in der Schule (absoluter Leistungszeitraum am heutigen Tag)', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_statistic_due_payments' => L10N::t('Agentur-Statistik: Fällige Zahlungen', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'agency_commission_groups'	=> L10N::t('Agentur Provisionsgruppen', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					//'agency_comments'			=> L10N::t('Agentur Kommentare', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'start_loop_overview_staffmembers}.....{end_loop_overview_staffmembers' => L10N::t('Durchläuft alle ausgewählten Agenturansprechpartner', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'overview_date' => L10N::t('Ausgewähltes Datum', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
					'overview_time' => L10N::t('Ausgewählte Zeit', Ext_Thebing_Agency_Gui2::getDescriptionPart()),
				)
			)

		);

		// Get parent placeholders
		$aParentPlaceholders		= parent::getPlaceholders();

		// Add parent placeholders
		$aPlaceholdersAll = array_merge($aParentPlaceholders, $aPlaceholders);

		return $aPlaceholdersAll;

	}


	protected function _getReplaceValue($sField, array $aPlaceholder) {

		$mValue = false;
		$oAgency = $this->_oAgency;

		switch($sField) {
			case 'agency_statistic':
			case 'agency_statistic_without_revenues':
				if(is_object($oAgency)) {
					$bWithRevenues = $sField !== 'agency_statistic_without_revenues';
					$mValue = $oAgency->getPlaceholderOverview(true, $bWithRevenues);
				}
				break;
			case 'agency_provision_groups':
			case 'agency_commission_groups':
				if(is_object($oAgency)) {
					$mValue = $oAgency->getPlaceholderProvisiongroups();
				}
				break;

			case 'agency_statistic_students_at_school':
				$mValue = $oAgency->getPlaceholderStudentsAtSchool($this->sTemplateLanguage);

				break;

			case 'agency_statistic_due_payments':
				$mValue = $oAgency->getPlaceholderDuePayments($this->sTemplateLanguage);

				break;

			// TODO: Entfernen, da es nun den Loop gibt?
			case 'agency_comments':
				if(is_object($oAgency)) {
					$mValue = $oAgency->getPlaceholderComments();
				}
				break;

			// TODO: Nach Umstellung auf Dokumentendialog entfernen (kann Nutzer dann selber eintragen)
			case 'overview_time':
				$mValue = $this->getAdditionalData($sField);
				break;
			case 'overview_date':

				$mValue = [
					'value' => $this->getAdditionalData($sField),
					'format' => 'date'
				];
				break;
			default:
				$mValue = parent::_getReplaceValue($sField, $aPlaceholder);

				$aHookData = [
					'placeholder' => $sField,
					'value' => &$mValue,
					'agency' => $this->_oAgency
				];
				System::wd()->executeHook('ts_agency_placeholder_overview_replace', $aHookData);
		}

		return $mValue;
	}

	public static function getPlaceholderDuePayments(Tc\Service\LanguageAbstract $oLanguage) {

		return [
			'school' => $oLanguage->translate('Schule'),
			'type' => $oLanguage->translate('Typ'),
			'document_number' => $oLanguage->translate('Rechnungsnummer'),
			'customer_number' => $oLanguage->translate('Kundennummer'),
			'customer_name' => $oLanguage->translate('Name'),
			'course_dates' => $oLanguage->translate('Kursdaten'),
			'amount_open' => $oLanguage->translate('Fälliger Betrag')
		];

	}

}
