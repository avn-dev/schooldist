<?php

namespace TcExternalApps\Service;

use Illuminate\Support\Collection;
use Licence\Exception\ApiException;
use Licence\Service\Office\Api;
use TcExternalApps\Api\Operations;
use TcExternalApps\Entity\App;
use TcExternalApps\Interfaces\ExternalApp;
use Core\Facade\Cache;

class AppService {
	
	const CACHE_GROUP = 'tc_external_apps';

	private static $installedAppKeys;

	/**
	 * Prüft, ob eine App installiert ist
	 * 
	 * @param string $sAppKey
	 * @return bool
	 */
	public static function hasApp(string $sAppKey) : bool {
		if (self::$installedAppKeys === null) {
			self::$installedAppKeys = App::query()->pluck('app_key');
		}
		return self::$installedAppKeys->contains($sAppKey);
	}
	
	/**
	 * Liefert das Objekt einer App
	 * 
	 * @param string $sAppKey
	 * @return ExternalApp
	 */
	public static function getApp(string $sAppKey): ?ExternalApp {
		return self::getAll()->get($sAppKey);
	}

	/**
	 * @param string $sAppKey
	 * @return string
	 */
	public static function getAppStoragePath(string $sAppKey, bool $bWithDocumentRoot = true) {

		if (null === self::getApp($sAppKey)) {
			throw new \RuntimeException(sprintf('Unknown app key "%s"', $sAppKey));
		}

		$sDirectory = '/storage/app_store/'.\Util::getCleanFilename($sAppKey);

		if ($bWithDocumentRoot === true) {
			$sDirectory = \Ext_TC_Util::getDocumentRoot(false).$sDirectory;
			\Util::checkDir($sDirectory);
		}

		return $sDirectory;
	}

	/**
	 * Liefert alle Kategorien der externen Apps. Diese können über einen Hook erweitert werden
	 * 
	 * @return Collection
	 */
	public static function getCategories() {
		
//		$sCacheKey = 'tc_external_apps_categories_'.\System::getInterfaceLanguage();
//
//		$aCategories = Cache::get($sCacheKey);
//		$aCategories = null;
//
//		if($aCategories === null) {
			$aCategories = [];
			$aCategories[ExternalApp::CATEGORY_DEFAULT] = \L10N::t('Sonstiges');
			$aCategories[ExternalApp::CATEGORY_ACCOUNTING] = \L10N::t('Buchhaltung');
			$aCategories[ExternalApp::CATEGORY_AUTHENTICATION] = \L10N::t('Authentifizierung');

			\System::wd()->executeHook('tc_external_apps_categories', $aCategories);
			
//			Cache::put($sCacheKey, 60*60*24*28, $aCategories, self::CACHE_GROUP);
//		}
				
		return collect($aCategories)->sort();
	}
		
	/**
	 * Liefert alle externen Apps des Systems
	 * 
	 * @return Collection
	 */
	public static function getAll() {

		$aAppClasses = self::getAppClasses();

		$oAppEntities = App::query()->get()
			->mapWithKeys(fn (App $oApp) => [$oApp->app_key => $oApp]);

		$aApps = [];
		foreach ($aAppClasses as $sAppKey => $sAppClass) {

			$oApp = app()->make($sAppClass)->setAppKey($sAppKey);

			if (null !== $oInstalledEntity = $oAppEntities->get($sAppKey)) {
				$oApp->setAppEntity($oInstalledEntity);
			}

			$aApps[$sAppKey] = $oApp;
		}

		return collect($aApps);
	}

	protected static function getAppClasses(): array {

		$sCacheKey = 'tc_external_apps';

		$aApps = Cache::get($sCacheKey);

		if ($aApps === null || \System::d('debugmode') == 2) {

			$aFiles = (new \Core\Helper\Config\FileCollector())->collectAllFileParts();
			$aApps = [];

			foreach ($aFiles as $oFile) {

				$aExternalApps = (array)$oFile->get('external_apps');

				foreach ($aExternalApps as $sKey => $aAppConfig) {
					if (!isset($aAppConfig['class'])) {
						throw new \RuntimeException(sprintf('Missing class configuration for app "%s" in bundle "%s"', $sKey, $oFile->getBundle()));
					}

					$aApps[$sKey] = $aAppConfig['class'];
				}

			}

			Cache::put($sCacheKey, 60*60*24*28, $aApps, self::CACHE_GROUP);
		}

		return $aApps;
	}

	public static function installApp(ExternalApp $oApp) {

		$appModules = self::getLicenseAppModules();
		
		if(empty($appModules)) {
			throw new ApiException('App modules could not be retrieved.');
		}
		
		$oUser = \Access_Backend::getInstance()->getUser();

		if (!$oApp->canBeInstalled()) {
			throw new \RuntimeException(sprintf('Install requirements are not fulfilled for App %s', $oApp->getAppKey()));
		}

		if(isset($appModules[$oApp->getAppKey()])) {
			$oResponse = (new Api())->request(new Operations\InstallApp($oApp, $oUser));

			if (!$oResponse->isSuccessful()) {
				throw (new ApiException('Api request failed'))->setResponse($oResponse);
			}
		}

		// App in die Datenbank eintragen/ Eintrag wieder auf active=1 setzen
		$oAppEntity = App::query()->withTrashed()->firstOrNew(['app_key' => $oApp->getAppKey()]);
		$oAppEntity->active = 1;

		$oApp->setAppEntity($oAppEntity);

		$oApp->install();

		$oAppEntity->save();

		self::getLogger()->info('App installed', ['app' => $oApp->getAppKey(), 'user' => $oUser->id]);

		return true;
	}

	public static function uninstallApp(ExternalApp $oApp) {

		$oUser = \Access_Backend::getInstance()->getUser();

		if (!$oApp->canBeUninstalled()) {
			throw new \RuntimeException(sprintf('Uninstall requirements are not fulfilled for App %s', $oApp->getAppKey()));
		}

		if (null !== $mPrice = $oApp->getPrice()) {
			$oResponse = (new Api())->request(new Operations\UninstallApp($oApp, $oUser));

			if (!$oResponse->isSuccessful()) {
				throw (new ApiException('Api request failed'))->setResponse($oResponse);
			}
		}

		$oApp->uninstall();

		$oApp->getAppEntity()->delete();
		$oApp->unsetAppEntity();

		self::getLogger()->info('App uninstalled', ['app' => $oApp->getAppKey(), 'user' => $oUser->id]);

		return true;
	}

	public static function getLogger() {
		return \Log::getLogger('tc_external_apps');
	}

	static public function getLicenseAppModules() {
		
		$cacheKey = 'tc_external_apps_license_modules';
		
		$licenseModules = Cache::get($cacheKey);

		if($licenseModules === null) {
			
			$api = new \Licence\Service\Office\Api();
			
			$object = new \TcExternalApps\Service\Api\LicenseModules();

			$response = $api->request($object);

			if($response->isSuccessful()) {
				
				$licenseModules = $response->get('data', []);
				Cache::put($cacheKey, 15*60, $licenseModules);
				
			}
			
		}
		
		return $licenseModules;
	}
	
}

