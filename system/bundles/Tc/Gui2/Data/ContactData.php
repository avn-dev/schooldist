<?php

namespace Tc\Gui2\Data;

class ContactData extends \Ext_TC_Gui2_Data {

	public static function getDialog(\Ext_Gui2 $oGui2) {
		$oDialog = $oGui2->createDialog($oGui2->t('Kontakt "{lastname}, {firstname}" bearbeiten'), $oGui2->t('Neuer Kontakt'));
		return $oDialog;
	}

	public static function getOrderby(){
		return [
			'tc_c.lastname' => 'ASC',
		];
	}

	protected function _buildWherePart($aWhere) {

		// Fester Systemtyp für neue Einträge
		$sFixSystemType = $this->_oGui->getOption('system_type', null);
		// Liste der Systemtypen für die Liste
		$aListSystemTypes = $this->_oGui->getOption('list_system_types', []);

		if (!empty($aListSystemTypes)) {
			$aWhere['tc_stmtst.type'] = ['IN', $aListSystemTypes];
		} else if ($sFixSystemType !== null) {
			$aWhere['tc_stmtst.type'] = $sFixSystemType;
		}

		return parent::_buildWherePart($aWhere);
	}

	/**
	 * {@inheitdoc}
	 */
	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {

		/* @var \Ext_TC_Contact $oContact */
		$oContact = $this->getWDBasicObject($aSelectedIds);

		$oDialogData->aElements = [];

		$sFixSystemType = $this->_oGui->getOption('system_type', null);

		if (
			$sFixSystemType !== null &&
			$oContact->exist() &&
			!$oContact->hasSystemType($sFixSystemType)
		) {
			// Wenn ein fester Systemtyp vorgegeben ist und der ausgewählte Kontakt diesen nicht hat den Dialog auf
			// readonly schalten
			$oDialogData->bReadOnly = true;
		}

		//if ($oDialogData->bReadOnly) {
		//	$this->buildReadonlyDialogContent($oDialogData, $oContact);
		//} else {
		$this->buildDialogContent($oDialogData, $oContact);
		//}

		return parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);

	}

	/**
	 * TODO kopiert aus Ext_TC_Contact_Gui2. Noch benötigt?
	 *
	 * @param \Ext_Gui2_Dialog $oDialog
	 * @param \Ext_TC_Contact $oContact
	 * @throws \Exception
	 */
	private function buildReadonlyDialogContent(\Ext_Gui2_Dialog $oDialog, \Ext_TC_Contact $oContact) {

		$oTab = $oDialog->createTab($this->t('Zusammenfassung'));
		$oTab->aOptions['task'] = 'overview';

		$aSalutations	= \Ext_TC_Util::getSalutations();
		$sSalutation	= $aSalutations[$oContact->salutation];

		$aDetailTypes	= \Ext_TC_Contact_Detail::getTypes();

		$aCountries		= \Ext_TC_Country::getSelectOptions();
		$aStates		= \Ext_TC_State::getSelectOptions();

		$oFormatDate	= \Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date');

		$aAddresses		= $oContact->getAddresses();
		$aContactDetails= $oContact->getDetails();
		$aContactEmails = $oContact->getEmailAddresses();

		$oDiv = new \Ext_Gui2_Html_Div();

		##########
		# INFOS
		##########

		// Anrede
		$oHtml = new \Ext_Gui2_Html_Text();
		if($oContact->salutation != ""){
			$oHtml->setElement($sSalutation.' ');
		}
		if($oContact->title != ""){
			$oHtml->setElement($oContact->title.' ');
		}
		$oHtml->setElement($oContact->lastname.', '.$oContact->firstname);
		$oRow = $oDialog->createRow($this->_oGui->t('Name'), $oHtml);
		$oDiv->setElement($oRow);

		// Geb. Datum
		$oHtml = new \Ext_Gui2_Html_Text();
		$oHtml->setElement($oFormatDate->formatByValue($oContact->birthday));
		$oRow = $oDialog->createRow($this->_oGui->t('Geburtstag'), $oHtml);
		$oDiv->setElement($oRow);

		##########
		# ADRESSEN
		##########

		$oH3 = new \Ext_Gui2_Html_H4();
		$oH3->setElement($this->_oGui->t('Adressen'));
		$oH3->style = 'clear:both';
		$oDiv->setElement($oH3);

		foreach($aAddresses as $oAddress){
			$oFieldSet = new \Ext_Gui2_Html_Div();
			$oFieldSet->style = 'width:450px; margin-right: 5px;float:left; border: 1px solid #ccc; margin-bottom:10px;';

			$oHtml = new \Ext_Gui2_Html_Text();
			$oHtml->setElement($oAddress->address);
			$oRow = $oDialog->createRow($this->_oGui->t('Adresse'), $oHtml);
			$oFieldSet->setElement($oRow);

			$oHtml = new \Ext_Gui2_Html_Text();
			$oHtml->setElement($oAddress->address_addon);
			$oRow = $oDialog->createRow($this->_oGui->t('Adresszusatz'), $oHtml);
			$oFieldSet->setElement($oRow);

			$oHtml = new \Ext_Gui2_Html_Text();
			$oHtml->setElement((string)$aStates[$oAddress->state_id]);
			$oRow = $oDialog->createRow($this->_oGui->t('Staat'), $oHtml);
			$oFieldSet->setElement($oRow);

			$oHtml = new \Ext_Gui2_Html_Text();
			$oHtml->setElement((string)$aCountries[$oAddress->country_iso]);
			$oRow = $oDialog->createRow($this->_oGui->t('Land'), $oHtml);
			$oFieldSet->setElement($oRow);

			$oDiv->setElement($oFieldSet);
		}

		##########
		# Details
		##########

		$oH3 = new \Ext_Gui2_Html_H4();
		$oH3->setElement($this->_oGui->t('Kontaktdaten'));
		$oH3->style = 'clear:both';
		$oDiv->setElement($oH3);

		$oFieldSet = new \Ext_Gui2_Html_Div();
		$oFieldSet->style = 'width:450px; margin-right: 5px;float:left; border: 1px solid #ccc;';

		foreach((array)$aContactDetails as $oDetail){
			$oHtml = new \Ext_Gui2_Html_Text();
			$oHtml->setElement($oDetail->value);
			$oRow = $oDialog->createRow((string)$aDetailTypes[$oDetail->type], $oHtml);
			$oFieldSet->setElement($oRow);
		}

		$oDiv->setElement($oFieldSet);


		##########
		# E-MAILS
		##########

		$oFieldSet = new \Ext_Gui2_Html_Div();
		$oFieldSet->style = 'width:450px; margin-right: 5px;float:left; border: 1px solid #ccc;';

		foreach((array)$aContactEmails as $oEmailAddress){
			$oHtml = new \Ext_Gui2_Html_Text();
			$oHtml->setElement($oEmailAddress->email);
			$oRow = $oDialog->createRow($this->_oGui->t('E-Mail'), $oHtml);
			$oFieldSet->setElement($oRow);
		}

		$oDiv->setElement($oFieldSet);

		$oTab->setElement($oDiv);

		$oDialog->setElement($oTab);
	}

	/**
	 * Dialog
	 *
	 * @param \Ext_Gui2_Dialog $oDialog
	 * @param \Ext_TC_Contact $oContact
	 * @return \Ext_Gui2_Dialog
	 * @throws \Exception
	 */
	private function buildDialogContent(\Ext_Gui2_Dialog $oDialog, \Ext_TC_Contact $oContact) {

		$aRequiredFields = $this->_oGui->getOption('required_fields', ['firstname', 'lastname']);
		$sFixSystemType = $this->_oGui->getOption('system_type', null);

		$oTab = $oDialog->createTab($this->t('Informationen'));
		$oTab->aOptions['task'] = 'infos';

		$oTab->setElement($oDialog->createRow($this->t('Anrede'), 'select', array(
			'db_alias' => 'tc_c',
			'db_column' => 'salutation',
			'select_options' => \Ext_TC_Util::getSalutations(),
			'required' => in_array('salutation', $aRequiredFields)
		)));

		$oTab->setElement($oDialog->createRow($this->t('Titel'), 'input', array(
            'db_alias' => 'tc_c',
			'db_column' => 'title',
			'required' => in_array('title', $aRequiredFields)
		)));

		$oTab->setElement($oDialog->createRow($this->t('Vorname'), 'input', array(
            'db_alias' => 'tc_c',
			'db_column' => 'firstname',
			'required' => in_array('firstname', $aRequiredFields)
		)));

		$oTab->setElement($oDialog->createRow($this->t('Nachname'), 'input', array(
            'db_alias' => 'tc_c',
			'db_column' => 'lastname',
			'required' => in_array('lastname', $aRequiredFields)
		)));

		$oTab->setElement($oDialog->createRow($this->t('Geburtstag'), 'calendar', array(
            'db_alias' => 'tc_c',
			'db_column' => 'birthday',
			'format' => \Factory::getObject('Ext_TC_Gui2_Format_Date'),
			'required' => in_array('birthday', $aRequiredFields)
		)));

		$oTab->setElement($oDialog->createRow($this->t('Korrespondenzsprache'), 'select', array(
            'db_alias' => 'tc_c',
			'db_column' => 'corresponding_language',
			'select_options' => \Ext_TC_Util::addEmptyItem(static::getLanguageOptions()),
			'required' => in_array('corresponding_language', $aRequiredFields)
		)));

		$oAllSystemContactTypes = collect(
			\Factory::executeStatic(\Ext_TC_Object::class, 'getContactSystemTypes')
		);

		if($sFixSystemType === null) {

			$oNotGlobal = $oAllSystemContactTypes
				->filter(fn ($aSystemTypeConfig) => (isset($aSystemTypeConfig['global']) && $aSystemTypeConfig['global'] === false))
				->keys();

			$aSystemTypes = $oContact->getSystemTypes();

			if (!empty($aSystemTypes) && $oNotGlobal->intersect($aSystemTypes)->isNotEmpty()) {
				// Wenn der Kontakt bereits einen nicht globalen Systemtyp hat, kann kein anderer Systemtyp hinzugefügt werden
				$oAllSystemContactTypes = $oAllSystemContactTypes->intersectByKeys(array_flip($aSystemTypes));
			} else {
				// Ansonsten stehen nur die globalen Systemtypen zur Verfügung
				$oAllSystemContactTypes = $oAllSystemContactTypes->diffKeys($oNotGlobal->flip());
			}

		} else {
			$oAllSystemContactTypes = $oAllSystemContactTypes->intersectByKeys(array_flip([$sFixSystemType]));
			$oContact->addSystemType($sFixSystemType);
		}

		// Systemtypen rausfiltern, die weitere Einstellungen mitbringen
		$oSystemContactTypesWithTab = $oAllSystemContactTypes
			->filter(function ($aSystemTypeConfig) {
				return isset($aSystemTypeConfig['tab']);
			});

        $aSystemTypes = $oAllSystemContactTypes->keys()
            ->push(null)
            ->toArray();

		$mappings = \Tc\Entity\SystemTypeMapping::getRepository()
			->findBySystemType(\Ext_TC_Contact::MAPPING_TYPE, $aSystemTypes)
			->mapWithKeys(fn ($mapping) => [$mapping->id => $mapping->name]);

		$oTab->setElement($oDialog->createRow($this->t('Kategorien'), 'select', array(
            'db_alias' => 'tc_c',
			'db_column' => 'system_types',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'select_options' => $mappings,
			'style' => 'height: 105px;',
			'row_style' => ($sFixSystemType !== null) ? 'display: none;' : '',
			'required' => true,
			'events' => array(
				array(
					'event' 		=> 'change',
					'function' 		=> 'reloadDialogTab',
					// Da es aktuell keine Möglichkeit gibt Tabs dynamisch hinzuzufügen werden diese standardmäßig eingebaut
					// und müssem alle neu geladen werden
					'parameter'		=> 'aDialogData, ['.implode(', ', range(1, $oSystemContactTypesWithTab->count())).']'
				)
			)
		)));

		$oDialog->setElement($oTab);

		// Systemtypen mit weiteren Einstellungen als Tab einbinden
		foreach ($oSystemContactTypesWithTab as $sSystemType => $aSystemTypeConfig) {

			$oTab = $oDialog->createTab($this->t($aSystemTypeConfig['label']));
			$oTab->aOptions['task'] = $sSystemType;

			if($oContact->hasSystemType($sSystemType)) {
				(new $aSystemTypeConfig['tab']())->build($this->_oGui, $oDialog, $oTab);
			} else {
				// Elemente die nicht ausgewählt sind einbinden damit diese mit reloadDialogTab geladen werden können
				$oTab->aOptions['hidden'] = true;
			}

			$oDialog->setElement($oTab);

		}

		// Details

		$oTab = $oDialog->createTab($this->t('Kontaktdaten'));
		$oDetailGui = new \Ext_TC_Contact_Detail_Gui2(md5('tc_contacts_to_contactdetails'), 'Ext_TC_Gui2_Data', null, $this->_oGui->instance_hash);
		$oDetailGui->access = $this->_oGui->access;
		$oDetailGui->gui_description = $this->_oGui->gui_description;
		$oDetailGui->bReadOnly = $oDialog->bReadOnly;
		//$oDetailGui->gui_title = $this->t('Kontaktdaten');

		$oDetailGui->parent_hash		= $this->_oGui->hash;
		$oDetailGui->foreign_key		= 'contact_id';
		$oDetailGui->parent_primary_key	= 'id';
		$oDetailGui->parent_gui[] = $this->_oGui->hash;

		$oTab->setElement($oDetailGui);
		$oDialog->setElement($oTab);

		// Adressen

		$oTab = $oDialog->createTab($this->t('Adressen'));
		$oTab->aOptions['task'] = 'address';
		$oAddressGui = new \Ext_TC_Address_Gui2(md5('tc_contacts_to_addresses'), 'Ext_TC_Gui2_Data', null, $this->_oGui->instance_hash);
		$oAddressGui->access = $this->_oGui->access;
		$oAddressGui->gui_description = $this->_oGui->gui_description;
		$oAddressGui->bReadOnly = $oDialog->bReadOnly;
		//$oAddressGui->gui_title = $this->t('Adressen');

		$oAddressGui->parent_hash			= $this->_oGui->hash;
		$oAddressGui->foreign_key			= 'contact_id';
		$oAddressGui->foreign_jointable		= 'tc_contacts_to_addresses';
		$oAddressGui->parent_primary_key	= 'address_id';
		$oAddressGui->parent_gui[] = $this->_oGui->hash;

		$oTab->setElement($oAddressGui);
		$oDialog->setElement($oTab);

		// E-Mail-Adressen

		$oTab = $oDialog->createTab($this->t('E-Mail'));
		$oTab->aOptions['task'] = 'email';
		$oEmailGui = new \Ext_TC_Email_Address_Gui2(md5('tc_contacts_to_emails'), 'Ext_TC_Gui2_Data', null, $this->_oGui->instance_hash);
		$oEmailGui->access = $this->_oGui->access;
		$oEmailGui->gui_description = $this->_oGui->gui_description;
		$oEmailGui->bReadOnly = $oDialog->bReadOnly;

		$oEmailGui->parent_hash			= $this->_oGui->hash;
		$oEmailGui->foreign_key			= 'contact_id';
		$oEmailGui->foreign_jointable	= 'tc_contacts_to_emailaddresses';
		$oEmailGui->parent_primary_key	= 'emailaddress_id';
		$oEmailGui->parent_gui[] = $this->_oGui->hash;

		$oTab->setElement($oEmailGui);
		$oDialog->setElement($oTab);

		return $oDialog;
	}

    public static function getLanguageOptions(): array {
        return \Ext_TC_Language::getSelectOptions();
    }

	public static function getContactSystemTypesOptions(): array {

		$oAllSystemContactTypes = collect(
			\Factory::executeStatic(\Ext_TC_Object::class, 'getContactSystemTypes')
		);

		return $oAllSystemContactTypes->mapWithKeys(function($aSystemTypeConfig, $sSystemType) {
				return [$sSystemType => \L10N::t($aSystemTypeConfig['label'])];
			})
			->toArray();
	}
}
