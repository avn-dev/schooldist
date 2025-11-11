<?php

class cms_backend {

	function executeHook($strHook, &$mixInput) {
		global $_VARS;

		switch($strHook) {
			case "navigation_top":
				
				$mixInput[] = [
					'name' => 'cms',
					'right' => 'edit',
					'title' => 'CMS',
					'icon' => 'fa-edit',				
					'extension' => 1,	
					'type' => 'iframe',
					'url' => '/admin/extensions/cms/edit_index.html',
					'key' => 'cms'
				];

				break;
			case "navigation_left":

				if($mixInput['name'] == 'stats') {

					$mixInput['childs'][] = array(
						L10N::t('Zugriffsstatistik', 'CMS'),				
						"/admin/extensions/cms/stats.html",
						0,
						"view_stats", 
						'fa-bar-chart',
						'cms.stats'
					);

				} elseif($mixInput['name'] == 'settings') {

					$mixInput['childs'][] = array(
						L10N::t('CMS', 'CMS'),				
						"/admin/cms/structure/edit",
						0,
						"page_admin", 
						'/admin/media/settings_sitemap.png',
						'cms.structure'
					);

					$mixInput['childs'][] = array(
						L10N::t('Einstellungen', 'CMS'),
						"/admin/extensions/cms/settings.html",
						1,
						"page_admin", 
						'/admin/media/settings_sitemap.png',
						'cms.settings'
					);
		
					#$mixInput['childs'][] = array(L10N::t('Seitenverwaltung','CMS'),		"/admin/cms/structure/edit",1,"page_admin", '/admin/media/settings_sitemap.png');
					$mixInput['childs'][] = array(L10N::t('Seitenübersicht','CMS'),		"/admin/extensions/cms/sitemap.html?task=overview",1,"page_admin", '/admin/media/settings_overview.png', 'cms.overview');
					#$mixInput['childs'][] = array(L10N::t('Stilverwaltung','CMS'),			"/admin/extensions/cms/styles.html",1,"edit_css", '/admin/media/settings_styles.png');
					$mixInput['childs'][] = array(L10N::t('Blockverwaltung','CMS'),		"/admin/extensions/cms/block.html",1,"block_admin", '/admin/media/settings_block_admin.png', 'cms.blocks');
					$mixInput['childs'][] = array(L10N::t('Seitenvorlagen','CMS'),			"/admin/extensions/cms/templates.html",1,"pagetemplates", '/admin/media/settings_templates.png', 'cms.templates');
					$mixInput['childs'][] = array(L10N::t('Internetauftritte','CMS'),	"/admin/extensions/cms/sites.html",1,"admin_sites", '/admin/media/settings_sites.png', 'cms.websites');
					$mixInput['childs'][] = array(L10N::t('Linkprüfung','CMS'),			"/admin/extensions/cms/links.html",1,"link_checker", '/admin/media/settings_linkchecker.png', 'cms.linkchecker');
					$mixInput['childs'][] = array(L10N::t('Filterfunktion','CMS'),		"/admin/extensions/cms/filter.html",1,"filter_admin", '', 'cms.filters');
					$mixInput['childs'][] = array(L10N::t('Globale Metadaten','CMS'),	"/admin/extensions/cms/preferences.html?topic=meta&select=global",1,"page_admin", '/admin/media/settings_global.png', 'cms.meta');

					$mixInput['childs'][] = array(
						L10N::t('Weiterleitungen', 'CMS'),
						'/admin/extensions/cms/redirections.html',
						'1',
						'page_admin',
						'/admin/media/settings_overview.png',
						'cms.redirects'
					);

					$mixInput['childs'][] = array(
						L10N::t('Dynamische Routen', 'CMS'),
						'/wdmvc/gui2/page/cms_dynamic_routing',
						'1',
						'page_admin',
						'/admin/media/settings_overview.png',
						'cms.dynamic_routes'
					);
					$mixInput['childs'][] = array(
						L10N::t('Schnipsel', 'CMS'),
						'/wdmvc/gui2/page/cms_snippet',
						'1',
						'page_admin',
						'/admin/media/settings_overview.png',
						'cms.snippets'
					);
					
				}
				
				break;
			case 'mvc_controller_no_route_found':
				
				$oCmsController = new \Cms\Controller\PageController('cms', 'page', 'output');
				$oCmsController->setRequest($mixInput['request']);
				
				$oCmsController->outputPage($mixInput['url']);

				break;
			default:
				break;
		}

	}

}

\System::wd()->addHook('navigation_left', 'cms');
\System::wd()->addHook('navigation_top', 'cms');
\System::wd()->addHook('mvc_controller_no_route_found', 'cms');
