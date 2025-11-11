<?php

class manual_backend {

	function executeHook($strHook, &$mixInput) {
		global $_VARS, $arrConfigData, $objWebDynamics;

		switch($strHook) {
			case "navigation_top":
				
				$mixInput[140]['name'] = 'manual';
				$mixInput[140]['right'] = 'manual';
				$mixInput[140]['title'] = 'Manual';		
				$mixInput[140]['icon'] = 'fa-support';			
				$mixInput[140]['key'] = 'manual';
				$mixInput[140]['extension'] = 	1;

				break;
			case "navigation_left":
				
				if($mixInput['name'] == 'manual') {
					
//					if($arrConfigData && $arrConfigData['install_complete'] && (int)$arrConfigData['install_complete'] == 1)
//					{
						/*
						 * Array with office navigation items
						 */
						
						$oManual = new Ext_Manual_Manual();
						
						$aPages = $oManual->getPages();
						
						$arrOfficeNavigation = array();


						$arrOfficeNavigation[10] = array("Startseite","extensions/manual.html?action=show&page=0", 0, "manual", "/admin/media/pfeil.gif", 'manual.list');

						$i = 20;
						foreach($aPages as $aPage){
							
							$arrOfficeNavigation[$i] = array($aPage['title'],"extensions/manual.html?action=show&page=".$aPage['id'], 0, "manual", "/admin/media/settings_sitemap.png", 'manual.page.'.$aPage['id']);
							
							$i = $i + 10;
						}

						$arrOfficeNavigation[$i] = array("Administration","extensions/manual.html?action=edit", 0, "manual_admin", "/admin/media/pfeil.gif", 'manual.edit');

						$mixInput['childs'] = $arrOfficeNavigation;
//					}
				}
				break;
			default:
				break;
		}
	}

}

\System::wd()->addHook('navigation_left', 'manual');
\System::wd()->addHook('navigation_top', 'manual');
