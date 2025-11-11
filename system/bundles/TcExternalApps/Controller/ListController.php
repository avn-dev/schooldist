<?php

namespace TcExternalApps\Controller;

use Illuminate\Support\Collection;
use TcExternalApps\Interfaces\ExternalApp;
use TcExternalApps\Service\AppService;
use Core\Handler\SessionHandler as Session;
use Core\Helper\Routing;
use Illuminate\Http\Response;

class ListController extends \MVC_Abstract_Controller {
	
	/**
	 * Controller hat kein CMS-Recht
	 * @var null
	 */
	protected $_sInterface = 'backend';
	
	protected $_sAccessRight = 'core_external_apps';

	/**
	 * Übersichtsliste für die externen Apps
	 * 
	 * @return Response
	 */
	public function indexAction() {

		$appModules = AppService::getLicenseAppModules();

		$oAllApps = AppService::getAll();
		$oCategories = AppService::getCategories()->prepend(\L10N::t('Alle'), 'all');

		$oInstalledApps = $oAllApps->filter(function (ExternalApp $oApp) {
			return $oApp->isInstalled();
		});
		$oNotInstalled = $oAllApps->diffKeys($oInstalledApps);

		$toArray = function ($oAppCollection) use($appModules) {
		
			return $oAppCollection->map(function (ExternalApp $oApp) use($appModules) {

				if(isset($appModules[$oApp->getAppKey()])) {
					$oApp->setLicenseModule($appModules[$oApp->getAppKey()]);
				}

				return $oApp->toArray();
			})
			->values()
			->toArray();
			
		};

		$aTemplateData = [
			'aCategories' => $oCategories
				->map(function($sCategory, $sKey) {
					return [
						'key' => $sKey,
						'title' => $sCategory
					];
				})
				->values()
				->toArray(),
			'aRoutes' => [
				'loading' => Routing::generateUrl('TcExternalApps.loading'),
				'delete' => Routing::generateUrl('TcExternalApps.delete'),
				'install' => Routing::generateUrl('TcExternalApps.install'),
			],
			'aL10n' => [
				'install_confirm' => \L10N::t('Möchten Sie die App wirklich hinzufügen?'),
				'install_confirm_with_price' => \L10N::t('Möchten Sie die App wirklich kostenpflichtig hinzufügen? Es entstehen Mehrkosten für Sie.'),
				'delete_confirm' => \L10N::t('Möchten Sie die App wirklich löschen?'),
				'action' => [
					'install' => \L10N::t('Installieren'),
					'delete' => \L10N::t('App deinstallieren'),
					'edit' => \L10N::t('Einstellungen'),
					'close' => \L10N::t('Schließen'),
				]
			],
			'aMyApps' => $toArray($oInstalledApps),
			'aApps' => $toArray($oNotInstalled),
		];

		return response()
					->view('list', $aTemplateData);
	}
	
	/**
	 * Liefert alle Apps die noch nicht installiert wurden
	 * - Filter nach Kategorie und nach Titel der Apps
	 * 
	 * @param \MVC_Request $oRequest
	 * @return Response
	 */
	public function loadAction(\MVC_Request $oRequest) {

		$appModules = AppService::getLicenseAppModules();

		$sSearch = $oRequest->get('search', '');
		$sCategory = $oRequest->get('category', 'all');

		[$oInstalledCollection, $oAppCollection] = AppService::getAll()
			->filter(function (ExternalApp $oApp) use ($sCategory, $sSearch) {
				// Filter nach Kategorie
				if ($sCategory !== 'all' && $oApp->getCategory() !== $sCategory) {
					return false;
				}
				// Filter nach Titel
				if (!empty($sSearch) && strpos(strtolower($oApp->getTitle()), strtolower($sSearch)) === false) {
					return false;
				}

				return true;
			})
			->partition(fn (ExternalApp $oApp) => $oApp->isInstalled());


		$buildAppArray = function (Collection $collection)use ($appModules) {
			return $collection->map(function (ExternalApp $oApp) use ($appModules) {

				if(isset($appModules[$oApp->getAppKey()])) {
					$oApp->setLicenseModule($appModules[$oApp->getAppKey()]);
				}

				return $oApp->toArray();
			})
			->values();
		};

		return response()->json([
			'my_apps' => $buildAppArray($oInstalledCollection)->toArray(),
			'apps' => $buildAppArray($oAppCollection)->toArray()
		]);
	}
	
	/**
	 * Übersicht um die Daten für die App zu bearbeiten
	 * 
	 * @param \MVC_Request $oRequest
	 * @param string $sAppKey
	 * @return Response
	 * @throws \InvalidArgumentException
	 */
	public function editAction(\MVC_Request $oRequest, $sAppKey) {
				
		$oApp = AppService::getApp($sAppKey);
		$oApp->setRequest($oRequest);
		
		if (!$oApp || !$oApp->isInstalled()) {
			throw new \InvalidArgumentException('No valid app key');
		}
		
		$aTemplateData = [
			'oApp' => $oApp,
			'oSession' => Session::getInstance()
		];
		
		return response()
			->view('edit', $aTemplateData);
	}

	/**
	 * @param \MVC_Request $oRequest
	 * @param $sAppKey
	 */
	public function saveAction(\MVC_Request $oRequest, $sAppKey) {
		
		$oApp = AppService::getApp($sAppKey);
		
		if (!$oApp || !$oApp->isInstalled()) {
			throw new \InvalidArgumentException('No valid app key');
		}
		
		$oApp->saveSettings(Session::getInstance(), $oRequest);
		
		$this->redirectUrl(Routing::generateUrl('TcExternalApps.edit', ['sAppKey' => $sAppKey]));
	}
	
	/**
	 * Aktiviert eine App
	 * - wenn die App schon mal installiert war wir active wieder auf 1 gesetzt
	 * 
	 * @param \MVC_Request $oRequest
	 * @return Response
	 */
	public function installAction(\MVC_Request $oRequest) {
		
		$sAppKey = $oRequest->get('app');
		
		$aResponse = [];
		$aResponse['success'] = false;
		$aResponse['app_key'] = $sAppKey;
		
		if (!empty($sAppKey)) {

			if (null !== $oApp = AppService::getAll()->get($sAppKey)) {
				
				\DB::begin(__METHOD__);

				try {
					$success = AppService::installApp($oApp);

					$aResponse['success'] = $success;
					$aResponse['app'] = $oApp->toArray();
					$aResponse['app']['route'] = Routing::generateUrl('TcExternalApps.edit', ['sAppKey' => $sAppKey]);

					\DB::commit(__METHOD__);

					$oApp = AppService::getApp($sAppKey);

					$oApp->install();

				} catch (\Throwable $e) {

					\DB::rollback(__METHOD__);
					$aResponse['message'] = sprintf(\L10N::t('Die App "%s" konnte nicht installiert werden, bitte wenden Sie sich an den Support.'), $oApp->getTitle());

					AppService::getLogger()->error('App installation failed', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
				}

			}
		}
		
		return response()->json($aResponse);
	}
	
	/**
	 * Deaktiviert eine App
	 * 
	 * @param \MVC_Request $oRequest
	 * @return Response
	 */
	public function deleteAction(\MVC_Request $oRequest) {
		
		$sAppKey = $oRequest->get('app');

		$aResponse = [];
		$aResponse['success'] = false;		
		$aResponse['app'] = $sAppKey;

		if (null !== $oApp = AppService::getAll()->get($sAppKey)) {

			\DB::begin(__METHOD__);

			try {
				$success = AppService::uninstallApp($oApp);

				$aResponse['success'] = $success;

				\DB::commit(__METHOD__);

			} catch (\Throwable $e) {
				\DB::rollback(__METHOD__);
				$aResponse['message'] = sprintf(\L10N::t('Die App konnte nicht deinstalliert werden, bitte wenden Sie sich an den Support.'), $oApp->getTitle());

				AppService::getLogger()->error('App uninstall failed', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
			}

		}
		
		return response()->json($aResponse);
	}

}
