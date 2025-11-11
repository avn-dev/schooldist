<?php

class Ext_Thebing_Document_Address {
	
	/**
	 * @var WDBasic
	 */
	private $oInquiry;
	
	static private $addressData;

	/**
	 * @param Ext_TS_Inquiry $oInquiry
	 */
	public function __construct(WDBasic $oInquiry) {
		$this->oInquiry = $oInquiry;
	}

	public function getLabel(string $sType, $iTypeId = null): string {

		return match ($sType) {
			'address', 'billing' => $this->oInquiry->getFirstTraveller()->getName(),
			'group' => Ext_Thebing_Inquiry_Group::getInstance($iTypeId)->getName(),
			'accommodation' => Ext_Thebing_Accommodation::getInstance($iTypeId)->getName(),
			'transfer_provider' => Ext_Thebing_Pickup_Company::getInstance($iTypeId)->name,
			'transfer_accommodation' => Ext_Thebing_Accommodation::getInstance($iTypeId)->name,
			'agency' => Ext_Thebing_Agency::getInstance($iTypeId)->getName(true)??'',
			'subagency' => Ext_Thebing_Agency::getInstance($iTypeId)->getName(true)??'',
			'sponsor' => TsSponsoring\Entity\Sponsor::getInstance($iTypeId)->getName(),
		};

	}

	public static function getTypeLabel($sType): string {

		return match ($sType) {
			'address' => L10N::t('Adresse des Kundens', Ext_Thebing_Document::$sL10NDescription),
			'group' => L10N::t('Adresse der Gruppe', Ext_Thebing_Document::$sL10NDescription),
			'accommodation' => L10N::t('Adresse der Familie', Ext_Thebing_Document::$sL10NDescription),
			'transfer_provider' => L10N::t('Adresse des Transferanbieters', Ext_Thebing_Document::$sL10NDescription),
			'transfer_accommodation' => L10N::t('Adresse des Unterkunftsanbieters', Ext_Thebing_Document::$sL10NDescription),
			'agency' => L10N::t('Adresse der Agentur', Ext_Thebing_Document::$sL10NDescription),
			'subagency' => L10N::t('Adresse der Unteragentur', Ext_Thebing_Document::$sL10NDescription),
			'sponsor' => L10N::t('Adresse des Sponsors', Ext_Thebing_Document::$sL10NDescription),
			'billing' => L10N::t('Rechnungsadresse', Ext_Thebing_Document::$sL10NDescription),
		};

	}

	public function buildLabelWithType(string $sType, $iTypeId = null): string {

		return sprintf('%s: %s', $this->getLabel($sType, $iTypeId), $this->getTypeLabel($sType));

	}

	/**
	 * Filter-Options für Adressant-Filter (document.yml)
	 *
	 * @return array
	 */
	public static function getLabels() {

		return [
			'address' => L10N::t('Adresse des Kundens', Ext_Thebing_Document::$sL10NDescription),
			'group' => L10N::t('Adresse der Gruppe', Ext_Thebing_Document::$sL10NDescription),
			'agency' => L10N::t('Adresse der Agentur', Ext_Thebing_Document::$sL10NDescription),
			'subagency' => L10N::t('Adresse der Unteragentur', Ext_Thebing_Document::$sL10NDescription),
			'sponsor' => L10N::t('Adresse der Sponsors', Ext_Thebing_Document::$sL10NDescription),
			'billing' => L10N::t('Rechnungsadresse', Ext_Thebing_Document::$sL10NDescription)
		];

	}

	/**
	 * Baut das Array für die Adressauswahl zusammen
	 *
	 * @param bool $bInvoice
	 * @return array
	 */
	public function getAddressSelectOptions($bInvoice = true) {

		$oInquiry = $this->oInquiry;
		$aOptions = array();

		if ($oInquiry instanceof \Ext_Thebing_Teacher) {
			return $aOptions;
		}

		$oDocumentAddress = new Ext_Thebing_Document_Address($oInquiry);

		$this->addCustomerAdressesByRef($aOptions);

		$this->addAgencyAdressesByRef($aOptions);

		// Gruppe
		$oGroup = $oInquiry->getGroup();
		if($oGroup) {
			$aOptions['group_'.$oGroup->id] = $oDocumentAddress->buildLabelWithType('group', $oGroup->id);
		}

		if(!$bInvoice) {

			// Familien
			$aAllocations = $oInquiry->getAllocations();
			foreach($aAllocations as $oAllocation) {
				$oRoom = $oAllocation->getRoom();
				if($oRoom) {
					$oAccommodation = $oRoom->getProvider();
					$aOptions['accommodation_'.$oAccommodation->id] = $oDocumentAddress->buildLabelWithType('accommodation', $oAccommodation->id);
				}
			}

			// Transferprovider
			$aProviders = $oInquiry->getAllocatedTransfers();
			foreach($aProviders as $oProvider) {
				if($oProvider instanceof Ext_Thebing_Pickup_Company) {
					$sKey = 'transfer_provider';
				} elseif($oProvider instanceof Ext_Thebing_Accommodation) {
					$sKey = 'transfer_accommodation';
				} else {
					throw new RuntimeException('Transfer provider type "'.get_class($oProvider).'" unknown!');
				}
				$aOptions[$sKey.'_'.$oProvider->id] = $oDocumentAddress->buildLabelWithType($sKey, $oProvider->id);
			}

		}

		return $aOptions;
	}

	/**
	 * @param $aOptions
	 */
	protected function addCustomerAdressesByRef(&$aOptions) {

		$oTraveller = $this->oInquiry->getFirstTraveller();
		$oAddressBilling = $this->oInquiry->getBooker()?->getAddress('billing');

		// Kundenadresse
		$aOptions['address_0'] = $this->buildLabelWithType('address');

		// Kunden-Rechnungsadresse
		if(
			$oAddressBilling &&
			!$oAddressBilling->isEmpty()
		) {
			$aOptions['billing_0'] = $this->buildLabelWithType('billing');
		}

	}

	/**
	 * @param $aOptions
	 */
	protected function addAgencyAdressesByRef(&$aOptions) {

		// Agentur
		if($this->oInquiry->hasAgency()) {
			$oAgency = $this->oInquiry->getAgency();
			$aOptions['agency_'.$oAgency->id] = $this->buildLabelWithType('agency', $oAgency->id);
		}

		if($this->oInquiry->hasSubAgency()) {
			$subAgency = Ext_Thebing_Agency::getInstance($this->oInquiry->subagency_id);
			$aOptions['subagency_'.$subAgency->id] = $this->buildLabelWithType('subagency', $subAgency->id);
		}

		if(
			$this->oInquiry instanceof Ext_TS_Inquiry &&
			$this->oInquiry->isSponsored() &&
			$this->oInquiry->sponsor_id != 0
		) {
			$aOptions['sponsor_'.$this->oInquiry->sponsor_id] = $this->buildLabelWithType('sponsor', $this->oInquiry->sponsor_id);
		}

	}

	/**
	 * @param Ext_Thebing_Inquiry_Document_Version $oVersion
	 * @param Ext_TS_Inquiry_Abstract $oInquiry Muss übergeben werden, da bei neuen Dokumenten Version keine Inquiry hat (keine objektrelationen Beziehnungen)
	 * @param string $sView
	 * @return string
	 */
	public function getSelectedAdressSelect(Ext_Thebing_Inquiry_Document_Version $oVersion, $sView, $sType=null) {

		$aHookData = array(
			'inquiry' => $this->oInquiry,
			'view' => $sView,
			'selected' => 'address_0'
		);

		if ($this->oInquiry instanceof \Ext_TS_Inquiry_Abstract) {

			$oAddressBilling = $this->oInquiry->getBooker();

			if($sView === 'net') {
				if(
					$sType=== 'creditnote_subagency' &&
					$this->oInquiry->hasSubAgency()
				) {
					$aHookData['selected'] = 'subagency_'.$this->oInquiry->subagency_id;
				} else {
					$aHookData['selected'] = 'agency_'.$this->oInquiry->agency_id;
				}
			} elseif(
				$sView === 'gross' &&
				$oAddressBilling !== null
			) {
				$aHookData['selected'] = 'billing_0';
			} elseif(
				$this->oInquiry instanceof Ext_TS_Inquiry &&
				$this->oInquiry->isSponsored() &&
				$this->oInquiry->checkValidSponsorFinancialGurantee(new DateTime())
			) {
				// Netto immer an Agentur, brutto (Bruttodiff) an Sponsor, solange Finanzgarantie gültig
				$aHookData['selected'] = 'sponsor_'.$this->oInquiry->sponsor_id;
			}

			// bCalculateProvisionNew === true steht für das Erstellen eines neuen Dokuents
			if(
				$oVersion->bCalculateProvisionNew === false &&
				!empty($oVersion->addresses)
			) {
				$aAddress = reset($oVersion->addresses);
				$aHookData['selected'] = $aAddress['type'].'_'.$aAddress['type_id'];
			}

			// Für DiD implementiert.
			System::wd()->executeHook('ts_document_address_select_default_value', $aHookData);

		}

		return $aHookData['selected'];
	}

	/**
	 * @param array $aAddressData
	 * @param \Tc\Service\LanguageAbstract $oLanguageObject
	 * @return array
	 * @throws Exception
	 */
	public function getAddressData(array $aAddressData, \Tc\Service\LanguageAbstract $oLanguageObject) {
		
		$cacheKey = __METHOD__.'_'.$this->oInquiry->id.'_'.implode('_', $aAddressData).'_'.$oLanguageObject->getLanguage();

		if(!isset(self::$addressData[$cacheKey])) {

			$aData = [];
			$aData['document_address_type'] = $aAddressData['type'];

			// Prüfen, ob die ID verfügbar ist, wenn sie benötigt wird
			if(
				(
					$aAddressData['type'] === 'group' ||
					$aAddressData['type'] === 'accommodation' ||
					$aAddressData['type'] === 'transfer'
				) &&
				empty($aAddressData['type_id'])
			) {
				throw new Exception('Adress type ID is missing!');
			}

			// Bei Kundenadressen
			if(
				$aAddressData['type'] === 'address' ||
				$aAddressData['type'] === 'billing'
			) {

				$oTraveller = $this->oInquiry->getFirstTraveller();

				$documentContact = null;
				if($aAddressData['type'] === 'billing') {
					$documentContact = $this->oInquiry->getBooker();
					if($documentContact) {
						$oAddress = $documentContact->getAddress('billing');
						$aData['document_name'] = $oAddress->company;
					}
				} 

				// Standardfall, Schüler oder Fallback wenn Rechnungsadresse verwendet, aber nachträglich aus Buchung entfernt
				if($documentContact === null) {
					$documentContact = $oTraveller;
					$oAddress = $documentContact->getAddress();
				}

				$sSalutation = Ext_TS_Contact::getSalutationForFrontend($documentContact->gender, $oLanguageObject);

				$aData['document_firstname'] = $documentContact->firstname;
				$aData['document_surname'] = $documentContact->lastname;
				$aData['document_lastname'] = $documentContact->lastname;
				$aData['document_salutation'] = $sSalutation;
				$aData['document_address'] = $oAddress->address??'';
				$aData['document_address_addon'] = $oAddress->address_addon??'';
				$aData['document_zip'] = $oAddress->zip??'';
				$aData['document_city'] = $oAddress->city??'';
				$aData['document_state'] = $oAddress->state??'';
				$aData['document_country'] = $oAddress?->getCountry($oLanguageObject->getLanguage())??'';
				$aData['document_country_iso'] = $oAddress->country_iso??'';
				$aData['document_company'] = $oAddress->company??'';
				$aData['document_tax_code'] = $documentContact->getDetail('tax_code')??'';
				$aData['document_vat_number'] = $documentContact->getDetail('vat_number')??'';
				$aData['document_recipient_code'] = $documentContact->getDetail('recipient_code')??'';
				$aData['document_number'] = $documentContact->getCustomerNumber();
				$aData['document_email'] = $documentContact->getFirstEmailAddress(false)?->email;

			} elseif(
				$aAddressData['type'] === 'agency' ||
				$aAddressData['type'] === 'subagency'
			) {

				// Agentur der Buchung

				// Agentur direkt holen (auch wegen dem möglichen Ansprechpartner)
				if(!empty($aAddressData['type_id'])) {
					$oAgency = Ext_Thebing_Agency::getInstance($aAddressData['type_id']);
				} else {
					$oAgency = $this->oInquiry->getAgency();
				}
				
				if(
					$oAgency instanceof Ext_Thebing_Agency &&
					$oAgency->exist()
				) {
							
					$sCountry = Ext_TC_Address::getCountryStatic($oAgency->ext_6, $oLanguageObject->getLanguage());

					// Wenn es einen Ansprechpartner gibt, dann diesen nehmen; ansonsten den MasterContact der Agentur
					/** @var $oAgencyContact Ext_Thebing_Agency_Contact */
					$oAgencyContact = null;
					if($this->oInquiry) {
						$oAgencyContact = $this->oInquiry->getAgencyContact();
					}
					if(
						$oAgencyContact === null || 
						!$oAgencyContact->exist()
					) {
						$oAgencyContact = $oAgency->getMasterContact();
					}

					$sSalutation = Ext_TS_Contact::getSalutationForFrontend($oAgencyContact->gender, $oLanguageObject);

					$aData['document_name'] = $oAgency->getName(true);
					$aData['document_firstname'] = $oAgencyContact->firstname;
					$aData['document_surname'] = $oAgencyContact->lastname;
					$aData['document_salutation'] = $sSalutation;
					$aData['document_address'] = $oAgency->ext_3;
					$aData['document_address_addon'] = $oAgency->ext_35;
					$aData['document_zip'] = $oAgency->ext_4;
					$aData['document_city'] = $oAgency->ext_5;
					$aData['document_state'] = $oAgency->state;
					$aData['document_country'] = $sCountry;
					$aData['document_country_iso'] = $oAgency->ext_6;
					$aData['document_tax_code'] = $oAgency->ext_24;
					$aData['document_vat_number'] = $oAgency->vat_number;
					$aData['document_recipient_code'] = $oAgency->recipient_code;
					$aData['document_company'] = $oAgency->getName(true);
					$aData['document_number'] = $oAgency->getNumber();
					$aData['document_email'] = $oAgency->getMasterContact()?->email;

				}
				
			} elseif($aAddressData['type'] === 'sponsor') {

				if($this->oInquiry instanceof Ext_TS_Inquiry) {

					$oSponsor = $this->oInquiry->getSponsor();
					$oAddress = $oSponsor->getAddress();

					$sCountry = Ext_TC_Address::getCountryStatic($oAddress->country_iso, $oLanguageObject->getLanguage());

					$aData['document_address'] = $oAddress->address;
					$aData['document_address_addon'] = $oAddress->address_addon;
					$aData['document_zip'] = $oAddress->zip;
					$aData['document_city'] = $oAddress->city;
					$aData['document_state'] = $oAddress->state;
					$aData['document_country'] = $sCountry;
					$aData['document_country_iso'] = $oAddress->country_iso;
					$aData['document_name'] = $oSponsor->getName();
					$aData['document_company'] = $oSponsor->getName();
					$aData['document_number'] = $oSponsor->getNumber();

					if($this->oInquiry->sponsor_contact_id > 0) {
						$oContact = Ext_TS_Contact::getInstance($this->oInquiry->sponsor_contact_id);
						$sSalutation = Ext_TS_Contact::getSalutationForFrontend($oContact->gender, $oLanguageObject);
						$aData['document_firstname'] = $oContact->firstname;
						$aData['document_surname'] = $oContact->lastname;
						$aData['document_salutation'] = $sSalutation;
					}

				}

			} elseif($aAddressData['type'] === 'group') {
				// Gruppe der Buchung

				$oGroup = Ext_Thebing_Inquiry_Group::getInstance($aAddressData['type_id']);
				$sCountry = Ext_TC_Address::getCountryStatic($oGroup->country, $oLanguageObject->getLanguage());
				$oGroupContact = $oGroup->getContactPerson();
				$sSalutation = Ext_TS_Contact::getSalutationForFrontend($oGroupContact->gender, $oLanguageObject);

				$aData['document_name'] = $oGroup->getName();
				$aData['document_firstname'] = $oGroupContact->firstname;
				$aData['document_surname'] = $oGroupContact->lastname;
				$aData['document_salutation'] = $sSalutation;
				$aData['document_address'] = $oGroup->address;
				$aData['document_address_addon'] = $oGroup->address_addon;
				$aData['document_zip'] = $oGroup->plz;
				$aData['document_city'] = $oGroup->city;
				$aData['document_state'] = $oGroup->state;
				$aData['document_country'] = $sCountry;
				$aData['document_country_iso'] = $oGroup->country;
				$aData['document_company'] = '';
				$aData['document_number'] = $oGroup->getNumber();

			} elseif($aAddressData['type'] === 'accommodation') {
				// Unterkunftsanbieter

				$oAccommodation = Ext_Thebing_Accommodation::getInstance($aAddressData['type_id']);
				$sSalutation = Ext_TS_Contact::getSalutationForFrontend($oAccommodation->gender, $oLanguageObject);

				$aData['document_name'] = $oAccommodation->name;
				$aData['document_firstname'] = $oAccommodation->firstname;
				$aData['document_surname'] = $oAccommodation->lastname;
				$aData['document_salutation'] = $sSalutation;
				$aData['document_address'] = $oAccommodation->street;
				$aData['document_address_addon'] = '';
				$aData['document_zip'] = $oAccommodation->zip;
				$aData['document_city'] = $oAccommodation->city;
				$aData['document_state'] = $oAccommodation->state;
				$aData['document_country'] = $oAccommodation->country; // Textfeld!
				$aData['document_company'] = $oAccommodation->getName();
				$aData['document_number'] = $oAccommodation->getNumber();

			} elseif($aAddressData['type'] === 'transfer') {
				// Transferprovider

				$oProvider = Ext_Thebing_Pickup_Company::getInstance($aAddressData['type_id']);
				$sSalutation = Ext_TS_Contact::getSalutationForFrontend($oProvider->title, $oLanguageObject);
				$sCountry = Ext_TC_Address::getCountryStatic($oProvider->country_iso, $oLanguageObject->getLanguage());

				$aData['document_name'] = $oProvider->name;
				$aData['document_firstname'] = $oProvider->firstname;
				$aData['document_surname'] = $oProvider->lastname;
				$aData['document_salutation'] = $sSalutation;
				$aData['document_address'] = $oProvider->street;
				$aData['document_address_addon'] = '';
				$aData['document_zip'] = $oProvider->plz;
				$aData['document_city'] = $oProvider->city;
				$aData['document_state'] = $oProvider->state;
				$aData['document_country'] = $sCountry;
				$aData['document_company'] = $oProvider->getName();

			} else {
				throw new Exception('Address type "'.$aAddressData['type'].'" unknown!');
			}
			
			self::$addressData[$cacheKey] = $aData;
			
		}
	
		return self::$addressData[$cacheKey];
	}
	
	/**
	 * Ausgewählte Adresse aus $_VARS extrahieren
	 *
	 * @param string $sValue
	 * @return array
	 */
	public static function getValueOfAddressSelect($sValue) {

		$aSelectedAddress = [];
		if(!empty($sValue)) {
			// Feld enthält kodierte Daten - Typ_Typ-ID
			$aTmp = explode('_', $sValue);
			$typeId = end($aTmp); // z.b. bei transfer_provider_2 reicht explode und [0],[1] nicht

			$aSelectedAddress = [
				[
					'type' => $aTmp[0],
					'type_id' => $typeId
				]
			];
		}

		return $aSelectedAddress;

	}

}
