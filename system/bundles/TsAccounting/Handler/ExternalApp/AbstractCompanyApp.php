<?php

namespace TsAccounting\Handler\ExternalApp;

abstract class AbstractCompanyApp extends \TcExternalApps\Interfaces\ExternalApp {
	
	const APPS = [
		\TsAccounting\Service\eInvoice\Italy\ExternalApp\XmlIt::class,
		\TsAccounting\Service\eInvoice\Spain\ExternalApp\Verifactu::class
	];
	
	public function getIcon(): string {
		return 'fa fa-send';
	}
	
	public function getContent(): ?string {

		$oSmarty = new \SmartyWrapper();
		
		$oSmarty->assign('sAppKey', $this->oAppEntity->app_key);
		$oSmarty->assign('oApp', $this);
		$oSmarty->assign('aCompanies', $this->getCompanys());

		if ($this->getAppKey() === \TsAccounting\Service\eInvoice\Spain\ExternalApp\Verifactu::APP_NAME) {
			$oSmarty->assign('configElements', $this->getConfigKeys());
		}
		
		$aCompany = static::getCompanyAttributes();
				
		$oSmarty->assign('aAttributes', $aCompany);
		
		return $oSmarty->fetch('@TsAccounting/external_apps/config.tpl');
		
	}

	/**
	 * @todo required einbauen
	 * 
	 * @param \Core\Handler\SessionHandler $oSession
	 * @param \MVC_Request $oRequest
	 */
	public function saveSettings(\Core\Handler\SessionHandler $oSession, \MVC_Request $oRequest) {
		
		$aConfig = $oRequest->input('config', []);
		$aCompanies = $this->getCompanys();
		
		$aCompanyAttibutes = static::getCompanyAttributes();
		
		foreach($aCompanies as $oCompany) {
			if(isset($aConfig[$oCompany->getId()])) {
				$aCompanyConfig = $aConfig[$oCompany->getId()];
				
				foreach($aCompanyAttibutes as $sAttribute => $aAttribute) {
					if(isset($aCompanyConfig[$sAttribute])) {
						$oCompany->__set($sAttribute, $aCompanyConfig[$sAttribute]);
					}
				}
				
				$oCompany->save();
			}
		}
		
		$oSession->getFlashBag()->add('success', \L10N::t('Ihre Einstellungen wurden gespeichert'));
		
	}
	
	/**
	 * @return \TsAccounting\Entity\Company[]
	 */
	protected function getCompanys() : array {
		return \TsAccounting\Entity\Company::getRepository()->findAll();
	}

	/**
	 * @return array
	 */
	public static function getCompanyAttributes() : array {
		return [
			'address' => [
				'label' => \L10N::t('Adresse'),
				'type' => 'input'
			],
			'address_zip' => [
				'label' => \L10N::t('PLZ'),
				'type' => 'input'
			],
			'address_city' => [
				'label' => \L10N::t('Stadt'),
				'type' => 'input'
			],
			'address_state' => [
				'label' => \L10N::t('Bundesland'),
				'type' => 'input'
			],
			'address_country' => [
				'label' => \L10N::t('Land'),
				'type' => 'select',
				'options' => \Ext_Thebing_Data::getCountryList(true, true)
			],
			'phone_number' => [
				'label' => \L10N::t('Telefon'),
				'type' => 'input'
			],
			'transmission_tax_number' => [
				'label' => \L10N::t('Steuernummer'),
				'type' => 'input'
			],
			'transmission_bank_name' => [
				'label' => \L10N::t('Name des Bankinstituts'),
				'type' => 'input'
			],
			'transmission_iban' => [
				'label' => \L10N::t('IBAN'),
				'type' => 'input'
			],
			'transmission_designation' => [
				'label' => \L10N::t('Bürobezeichnung'),
				'type' => 'input'
			],
			'transmission_tax_system' => [
				'label' => \L10N::t('Steuersystem'),
				'type' => 'input'
			],
			'transmission_register_office' => [
				'label' => \L10N::t('Initialen der Provinz des Handelsregisteramtes'),
				'type' => 'input'
			],
			'transmission_register_number' => [
				'label' => \L10N::t('Handelsregisternummer'),
				'type' => 'input'
			],
			'transmission_capital' => [
				'label' => \L10N::t('Stammkapital'),
				'type' => 'input'
			],
			'transmission_partner' => [
				'label' => \L10N::t('Gesellschafter'),
				'type' => 'select',
				'options' => [
					'' => '',
					'SU' => 'Alleingesellschafter',
					'SM' => 'Mehrere Aktionäre',
				]
			],
			'transmission_liquidation' => [
				'label' => \L10N::t('Liquidation'),
				'type' => 'select',
				'options' => [
					'' => '',
					'LS' => 'In Liquidation',
					'LN' => 'Nicht in Liquidation',
				]
			],
			'default_natura' => [
				'label' => \L10N::t('Natura'),
				'type' => 'select',
				'options' => [
					'N5' => 'N5',
					'N4' => 'N4',
				]
			]
		];
	}
}

