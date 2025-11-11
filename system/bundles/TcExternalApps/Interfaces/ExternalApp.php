<?php

namespace TcExternalApps\Interfaces;

abstract class ExternalApp {

	const CATEGORY_DEFAULT = 'default';

	const CATEGORY_ACCOUNTING = 'accounting';

	const CATEGORY_AUTHENTICATION = 'authentication';

	const L10N_PATH = 'External Apps';
	
	protected $sAppKey;

	protected $oAppEntity;
	
	protected $licenseModule;

	public $request;

	public function setAppKey(string $sAppKey) {
		$this->sAppKey = $sAppKey;
		return $this;
	}

	public function getAppKey() {
		return $this->sAppKey;
	}

	public function setAppEntity(\TcExternalApps\Entity\App $oAppEntity) {
		$this->oAppEntity = $oAppEntity;
		return $this;
	}

	public function unsetAppEntity() {
		$this->oAppEntity = null;
		return $this;
	}

	public function getAppEntity() {
		return $this->oAppEntity;
	}

	public function isInstalled() : bool {
		return $this->oAppEntity !== null;
	}

	public function canBeInstalled() : bool {
		return true;
	}

	public function canBeUninstalled() : bool {
		return true;
	}
	
	public function getCategory() : string {
		return self::CATEGORY_DEFAULT;
	}
	
	public function getIcon() {
		return 'fa fa-circle-o';
	}

	public function getPrice() {

		// Preisinfo vom Lizenzserver abrufen
		if($this->licenseModule === null) {
			$appModules = \TcExternalApps\Service\AppService::getLicenseAppModules();
			$this->licenseModule = $appModules[$this->getAppKey()];
		}
		
		if(
			empty($this->licenseModule['prices']) || 
			!is_array($this->licenseModule['prices'])
		) {
			return null;
		}
		
		$amountFormat = \Factory::getObject('Ext_Gui2_View_Format_Int');
		
		$priceInfo = [];
		
		foreach($this->licenseModule['prices'] as $price) {
			switch($price['type']) {
				case 'one_time':
					$priceInfo[] = $this->t('Einmalig').': '.$amountFormat->formatByValue($price['amount']).' €';
					break;
				case 'fixed':
					$priceInfo[] = $this->t('Monatlich').': '.$amountFormat->formatByValue($price['amount']).' €';
					break;
				case 'per_user':
					$priceInfo[] = $this->t('Monatlich pro Benutzer').': '.$amountFormat->formatByValue($price['amount']).' €';
					break;
			}
		}
		
		return implode('; ', $priceInfo);
	}

	abstract public function getTitle() : string;

	abstract public function getDescription() : string;

	/**
	 * Weglassen wenn keine Einstellungen
	 * @return string|null
	 */
	public function getContent() : ?string {
		return null;
	}

	/**
	 * Weglassen wenn keine Einstellungen
	 * @param \Core\Handler\SessionHandler $oSession
	 * @param \MVC_Request $oRequest
	 */
	public function saveSettings(\Core\Handler\SessionHandler $oSession, \MVC_Request $oRequest) {
		// do nothing
	}

	/**
	 * Übersetzungen (Labels usw.) nur im Backend ausführen
	 *
	 * @param string $sTranslation
	 * @return string
	 */
	public function t(string $sTranslation): string {

		if (\System::wd()->getInterface() === 'backend') {
			return \L10N::t($sTranslation, self::L10N_PATH);
		}

		return '';

	}
	
	/**
	 * Führt Befehle für die Installation aus
	 */
	public function install() {
		
	}
	
	/**
	 * Führt Befehle für die Deinstallation aus
	 */
	public function uninstall() {
		
	}

	public function setLicenseModule(array $module) {
		$this->licenseModule = $module;
	}
	
	public function toArray(): array {
		$sDescription = $this->getDescription();
		$sDescriptionClean = strip_tags($sDescription);

		$aApp = [];
		$aApp['key'] = $this->sAppKey;
		$aApp['id'] = ($this->oAppEntity) ? $this->oAppEntity->id : null;
		$aApp['title'] = $this->getTitle();
		$aApp['description'] = $sDescription;
		$aApp['description_short'] = substr($sDescriptionClean, 0, 80);
		$aApp['category'] = $this->getCategory();
		$aApp['icon'] = $this->getIcon();
		$aApp['price'] = $this->getPrice();

		$oRoutingService = new \Core\Service\RoutingService();
		$aApp['route'] = $oRoutingService->generateUrl('TcExternalApps.edit', ['sAppKey' => $this->sAppKey]);

		return $aApp;
	}

	public function setRequest($request) {
		$this->request = $request;
	}
}

