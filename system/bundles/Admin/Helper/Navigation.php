<?php

namespace Admin\Helper;

use Illuminate\Support\Str;

class Navigation {
	
	const CACHE_GROUP_KEY = 'ADMIN_HELPER_NAVIGATION_CACHE_GROUP';

	/**
	 * @var \Access_Backend|null
	 */
	protected ?\Access_Backend $oAccessBackend;

	public function __construct(\Access_Backend $oAccessBackend = null) {
		
		$this->oAccessBackend = $oAccessBackend;
	}
	
	public function getCacheKey() {
		
		$sInterfaceLanguage = \System::getInterfaceLanguage();

		$sCacheKey = 'Backend_Navigation_'.$sInterfaceLanguage;

		if (!app()->runningInConsole() && $this->oAccessBackend) {
			$sCacheKey .= '_'.$this->oAccessBackend->id;
		}
		
		return $sCacheKey;
	}

	public function get() {
		global $_VARS;

		$sCacheKey = $this->getCacheKey();

		$aNavigationItems = \WDCache::get($sCacheKey);

		// $elem[0] = (string) link text
		// $elem[1] = (string) link target
		// $elem[2] = (bool) the element is a sub-element (default = 0)
		// $elem[3] = (string) permission that is required to view the element
		// $elem[4] = (string) fontawesone-Class
		// $elem[5] = (string) Key
		// $elem[6] = (string) Type
		if(
			\System::d('debugmode') === 2 ||
			$aNavigationItems === null
		) {
			// create list of basic navigation elements
			$aNavigationItems = array();

			$aNavigationItems[10]['name'] = 'welcome';
			$aNavigationItems[10]['right'] = 'control';
			$aNavigationItems[10]['icon'] = 'fa-dashboard';
			$aNavigationItems[10]['title'] = \L10N::t('Start', 'Framework');
			$aNavigationItems[10]['key'] = 'admin.start';
			$aNavigationItems[10]['childs'] = array(
				array(\L10N::t('Dashboard', 'Framework'), \Admin\Components\DashboardComponent::class, false, null, null, 'admin.dashboard', 'component'),
				array(sprintf(\L10N::t('Über %s', 'Framework'), \System::d('software_name')), "/admin/credits", false, null, null, 'admin.credits', 'view'),
				array(\L10N::t('Notfallnachricht', 'Framework'), 'https://emergency.fidelo.com/', false, null, null, 'admin.emergency', 'url')
			);

			$aNavigationItems[30]['name'] = 'media';
			$aNavigationItems[30]['right'] = 'media_admin';
			$aNavigationItems[30]['icon'] = 'fa-image';
			$aNavigationItems[30]['title'] = \L10N::t('Medienverwaltung', 'Framework');
			$aNavigationItems[30]['key'] = 'media';
			$aNavigationItems[30]['url'] = '/tinymce/resource/filemanager/dialog.php?lang='.\System::getInterfaceLanguage();
			$aNavigationItems[30]['childs'] = [
				array(\L10N::t('Medienverwaltung','Framework'), '/tinymce/resource/filemanager/dialog.php?lang='.\System::getInterfaceLanguage(), 0, "media-admin", null, 'media.admin'),
				array(\L10N::t('Dateiverwaltung','Framework'), '/admin/storage/manager', 0, "storage_admin", null, 'media.files'),
			];

			$aNavigationItems[40]['name'] = 'settings';
			$aNavigationItems[40]['right'] = 'settings';
			$aNavigationItems[40]['icon'] = 'fa-cogs';
			$aNavigationItems[40]['title'] = \L10N::t('Einstellungen und Funktionen', 'Framework');
			$aNavigationItems[40]['key'] = 'settings';
			$aNavigationItems[40]['childs'] = array(
				array(\L10N::t('Gestaltung','CMS'),				"/admin/sitemap.html",0,"imgbuilder", '/admin/media/settings_imagebuilder2.png', 'settings.sitemap'),
				array(\L10N::t('Grafikgenerierung','Framework'),	"/admin/imgbuilder.html",1,"imgbuilder", '/admin/media/settings_imagebuilder.png', 'settings.imagebuilder'),
				array(\L10N::t('System','Framework'),		"/admin/global.html",0,"settings", '/admin/media/settings_system.png', 'settings.system'),
				array(\L10N::t('Systemeinstellungen','Framework'),	"/admin/global.html",1,"system_admin", '/admin/media/settings_system.png', 'settings.system.config'),
				array(\L10N::t('Frontend-Übersetzungen','CMS'),			"/admin/translations.html?view=frontend",1,"languages", '/admin/media/settings_languages.png', 'settings.translations.frontend'),
				array(\L10N::t('Backend-Übersetzungen','Framework'),			"/admin/backend_translations.html",1,"backend_languages", '/admin/media/settings_languages.png', 'settings.translations.backend'),
				array(\L10N::t('Datenbank','Framework'),				"/admin/database.html",1,"database", '/admin/media/settings_languages.png', 'settings.database'),
				array(\L10N::t('Verwaltung','Framework'),"/admin/cache.html",0,"settings", '/admin/media/settings_update.png', 'settings.system.cache'),
				array(\L10N::t('Tools', 'Framework'),			"/admin/tools.html",1,"admin", '/admin/media/settings_cache.png', 'settings.system.tools'),
				array(\L10N::t('Systemupdate','Framework'),			"/admin/update.html",1,"update", '/admin/media/settings_update.png', 'admin.update'),
				array(\L10N::t('Backup','Framework'),				"/admin/backup.html",1,"backup", '/admin/media/settings_backup.png', 'settings.system.backup'),
				array(\L10N::t('HTACCESS','Framework'),							"/admin/htaccess.html", 1, "htaccess", '/admin/media/settings_system.png', 'settings.system.htaccess')
			);

			$aNavigationItems[50]['name'] = 'modules';
			$aNavigationItems[50]['right'] = 'modules';
			$aNavigationItems[50]['icon'] = 'fa-cubes';
			$aNavigationItems[50]['title'] = \L10N::t('Erweiterungen', 'Framework');
			$aNavigationItems[50]['url'] = '/admin/modules';
			$aNavigationItems[50]['key'] = 'admin.modules';
			$aNavigationItems[50]['childs'] = [
				[\L10N::t('Übersicht', 'Framework'), "modules", 'url', 'modules_admin', null, 'modules_admin']
			];
			$aExtensions = \DB::getQueryRows("SELECT * FROM system_elements WHERE element = 'modul' AND administrable = 1 AND `category` != 'shop' AND active = 1 ORDER BY title");
			foreach($aExtensions as $aExtension) {
				$aRights = \DB::getQueryRows("SELECT * FROM system_rights WHERE `right` = :file LIMIT 1", array('file'=>$aExtension['file']));
				if(!empty($aRights)) {
					$sRight = $aExtension['file'];
				} else {
					$sRight = "modules_admin";
				}
				if(is_file(\Util::getDocumentRoot()."system/legacy/admin/extensions/".$aExtension['file'].".html")) {
					$aNavigationItems[50]['childs'][] = array($aExtension['title'], "/admin/extensions/".$aExtension['file'].".html", 'url', $sRight, '', 'module_'.Str::slug($aExtension['file']));
				}
			}

			$aNavigationItems[80]['name'] = 'user';
			$aNavigationItems[80]['right'] = 'mydata';
			$aNavigationItems[80]['icon'] = 'fa-users';
			$aNavigationItems[80]['title'] = \L10N::t('Benutzerverwaltung', 'Framework');
			$aNavigationItems[80]['key'] = 'admin.users';
			$aNavigationItems[80]['childs'] = array(
				array(\L10N::t('Benutzer', 'Framework'), "/admin/user.html",0,"user_admin", null, 'admin.users.list'),
				array(\L10N::t('Rollen', 'Framework'), "/admin/roles.html",0,"group_admin", null, 'admin.users.roles'),
				array(\L10N::t('Rechte', 'Framework'), "/admin/rights.html",0,"rights", null, 'admin.users.rights')
			);

			$aNavigationItems[90]['name'] = 'stats';
			$aNavigationItems[90]['right'] = 'view_stats';
			$aNavigationItems[90]['icon'] = 'fa-bar-chart';
			$aNavigationItems[90]['title'] = \L10N::t('Auswertungen und Protokolle', 'Framework');
			$aNavigationItems[90]['key'] = 'admin.stats';
			$aNavigationItems[90]['childs'] = array(
				array(\L10N::t('Protokoll','Framework'),		"/admin/logs.html", 0, "view_logs", null, 'admin.stats.logs'),
				array(\L10N::t('E-Mail-Protokoll','Framework'),	"/admin/maillog.html", 0, "maillog", null, 'admin.stats.maillog'),
				array(\L10N::t('Analyse','Framework'),			"/admin/analysis.html", 0, "analysis", null, 'admin.stats.analysis')
			);

			$aNavigationItems[100]['name'] = 'trashcan';
			$aNavigationItems[100]['right'] = 'trashcan';
			$aNavigationItems[100]['icon'] = 'fa-trash';
			$aNavigationItems[100]['title'] = \L10N::t('Papierkorb', 'Framework');
			$aNavigationItems[100]['url'] = '/admin/trashcan.html';
			$aNavigationItems[100]['key'] = 'admin.trash';
			$aNavigationItems[100]['childs'] = [];

			$aNavigationItems[110]['name'] = 'help';
			$aNavigationItems[110]['right'] = 'help';
			$aNavigationItems[110]['icon'] = 'fa-support';
			$aNavigationItems[110]['title'] = \L10N::t('Unterstützung', 'Framework');
			$aNavigationItems[110]['key'] = 'admin.support';
			$aNavigationItems[110]['childs'] = array(
				array(\L10N::t("FAQ", 'Framework'), "/admin/faq.html", 0, 'support_faq', null, 'support_faq'),
				array(\L10N::t('Supportformular', 'Framework'), "/admin/support.html", 0, "support_form", null, 'support_form')
			);

			// execute hooks to allow extensions to modify the navigation elements
			\System::wd()->executeHook('navigation_top', $aNavigationItems);

			foreach($aNavigationItems as $iNavigationItem=>&$aNavigationItem) {

				if(
					empty($aNavigationItem['name']) ||
					!$this->hasRight($aNavigationItem['right'])
				) {
					unset($aNavigationItems[$iNavigationItem]);
				} else {

					if(!isset($aNavigationItem['childs'])) {
						$aNavigationItem['childs'] = array();
					}

					$_VARS['task'] = $aNavigationItem['name'];
					\System::wd()->executeHook('navigation_left', $aNavigationItem);

					$iLatestChild = null;
					foreach($aNavigationItem['childs'] as $iChild=>&$aChild) {

						// Array hier schon deklarieren, damit man nachträglich sehen kann, ob das Item Kindelemente haben sollte
						if(
							isset($aChild[2]) &&
							$aChild[2] == 1 && 
							isset($aNavigationItem['childs'][$iLatestChild]) &&
							!isset($aNavigationItem['childs'][$iLatestChild]['childs'])
						) {
							$aNavigationItem['childs'][$iLatestChild]['childs'] = [];
						}

						if(
							!empty($aChild[3]) &&
							!$this->hasRight($aChild[3])
						) {

							unset($aNavigationItem['childs'][$iChild]);
							
							if($aChild[2] != 1) {
								// Hier muss $iLastChild gesetzt werden, ansonsten werden die Childs mit iSubPoint=1
								// falsch gesetzt. 
								$iLatestChild = $iChild;							
							}
							
						} else {
							
							if(
								isset($aChild[2]) &&
								$aChild[2] == 1
							) {
								// Prüfen ob ein Item zu $iLatestChild existiert. Wenn man nämlich
								// nicht das Recht für den Oberpunkt hat darf man die Unterpunkte
								// auch nicht sehen
								if(isset($aNavigationItem['childs'][$iLatestChild])) {
									$aNavigationItem['childs'][$iLatestChild]['childs'][] = $aChild;
								}

								unset($aNavigationItem['childs'][$iChild]);
								
							} else {
								$iLatestChild = $iChild;
							}
							
						}
					}
					
					// Leere "childs" Items entfernen
					foreach($aNavigationItem['childs'] as $iChild=>&$aChild) {
						
						if(
							!isset($aChild['childs'])
						) {
							$aChild['childs'] = [];
							#unset($aNavigationItem['childs'][$iChild]);
						}

					}
					
				}

			}

			// sort the items based on their keys
			ksort($aNavigationItems, SORT_NUMERIC);

			\System::wd()->executeHook('admin_navigation', $aNavigationItems);
			
			\WDCache::set($sCacheKey, (60*60*24), $aNavigationItems, false, self::CACHE_GROUP_KEY);

		}

		return $aNavigationItems;
	}

	static function clearCache() {
		\WDCache::deleteGroup(\Admin\Helper\Navigation::CACHE_GROUP_KEY);
	}
	
	private function hasRight(mixed $right): bool
	{
		if (!app()->runningInConsole() && $this->oAccessBackend) {
			return $this->oAccessBackend->hasRight($right);
		}

		return true;
	}

}
