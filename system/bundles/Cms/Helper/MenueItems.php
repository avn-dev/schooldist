<?php

namespace Cms\Helper;

/** Start Navigations-Klasse **/
class MenueItems {
	
	var $items;      // Array, erste Dimension ist das level, 2.Dimension ist der Pfad, die dritte dann die reihenfolge
	//var $max_level;

	static $aCheckedItems = [];
	
	// Holt die Daten aus der DB in eine Interne Struktur
	// Die Parameter sind optional zum Schonen der Resourcen
	function __construct($max_level = 10, $max_items = 500, $strStart="") {

		global $system_data, $db_module, $db_data, $language, $page_data;

		global $default_group;
		
		//to avoid warnings:
		global $min_level;
		global $skip_path;
		global $old_path;
		global $session_data;

		// Query für die "Normalen" Seiteneinträge aus pages absetzen:
		$sQuery = "
				SELECT 
					*,
					IF(`file` = 'index', `level`-1, `level`) `l`
				FROM
					`cms_pages`
				WHERE
					`site_id` = ".intval($system_data['site_id'])." AND
					`path` LIKE '".$strStart."%' AND
					(
						(
							`file` !='index' AND 
							`level` >= ".intval($min_level)." AND 
							`level` <= ".intval($max_level)."
						) OR 
						(
							`file` = 'index' AND 
							`level` >= ".(intval($min_level)+1)." AND 
							`level` <= ".(intval($max_level)+1)."
						)
					) AND 
					`active` = 1 AND 
					`element` != 'template' AND 
					`element` != 'xml' AND 
					(
						`language` = '".\DB::escapeQueryString($page_data['language'])."' OR
						`language` = ''
					)
				ORDER BY  
					`path`,
					(`file` !='index'),  
					`position`,  
					`id`,  
					`level` ASC
				LIMIT 0, ".(int)$max_items."
				";

		$i=0;
		$res_menue = (array)\DB::getQueryRows($sQuery);

		foreach($res_menue as $my_menue) {

			//Skippen, wenn Datei in verstecktem Ordner
			if(
				$skip_path !== null && 
				strpos($my_menue['path'], $skip_path) !== false
			) {
				continue;
			}

			$skip_path ="###";
			if(
				$my_menue['path'] != '' && 
				$my_menue['path'] != $old_path && 
				$my_menue['file'] != 'index'
			) {
				$skip_path = $my_menue['path'];
				continue;
			}
			
			// Daten in Element schreiben
			$this->items[$i]['data']             = $my_menue;
			$this->items[$i]['parent']           = 0;
			$this->items[$i]['children']         = array();
			$this->items[$i]['pages']            = array();
			$this->items[$i]['folders']          = array();

			### Eigene Beziehungen eintragen
			// gucken ob "Bruder" des Vorgängers
			if($my_menue['path'] == $old_path && $this->items[$i-1]['data']['file'] != 'index') {

				if($this->items[$i-1]['parent']) {
					$this->items[$i]['parent'] = $this->items[$i-1]['parent'];
				}
			
			// gucken ob "Sohn" des Vorgängers
			} elseif($my_menue['path'] == $old_path) {
				$this->items[$i]['parent'] = $i-1;
			
			// gucken, wo man das neue Verzeichnis(da index) einhängen muss, da es weder direkter Bruder, noch direkter Sohn ist
			} elseif($my_menue['file'] == 'index' && $old_path != '') {
				// gucken ob im root
				// gucken, ob "Sohn"
				if($my_menue['file']!='index' && $this->items[$i-1]['data']['file']=='index') {
					$this->items[$i]['parent'] = $i-1;
				} else {
					// weder "Sohn" noch "Bruder" -> Vater suchen
					$parent_id = $i-1;
					while(
			        	$this->items[$parent_id]['data']['path'] != "" &&
			        	!(strpos($my_menue['path'],$this->items[$parent_id]['data']['path'])===0)
			        ) {
			        	$parent_id = $this->items[$parent_id]['parent'];
			     	}
					if($this->items[$parent_id]['data']['file'] != 'index') {
						$parent_id = $this->items[$parent_id]['parent'];
					}
			     	// einfügen:
			     	$this->items[$i]['parent'] = (int)$parent_id;
				}
			}

			### Beim Vater-Element eintragen
			//(aber nicht bei sich selber!)
			if($this->items[$i]['parent'] != $i) {

				// Beim Vater als Child eintragen,         und zwar an der richtigen "position" wegen der Sortierung (mögliche doppelte vergabe von Positionsnummern sind berücksichtigt, daher die While-Schleife
				while($this->items[$this->items[$i]['parent']]['children'] [$this->items[$i]['data']['position']]>0) {
					$this->items[$i]['data']['position']++;
				}
				$this->items[$this->items[$i]['parent']]['children'][$this->items[$i]['data']['position']]=$i;
	
				// Beim Vater in das "folders"-array eintragen, wenn es ein Verzeichnis ist
				if($my_menue['file'] == 'index') { //als folder eintragen
					while($this->items[$this->items[$i]['parent']]['folders'] [$this->items[$i]['data']['position']]>0) {
	           			$this->items[$i]['data']['position']++;
					}
	         		$this->items[$this->items[$i]['parent']]['folders'] [$this->items[$i]['data']['position']]=$i;
				// Beim Vater in das "pages"-array eintragen, wenn es kein Verzeichnis ist
				} else { // als page eintragen
	         		while($this->items[$this->items[$i]['parent']]['pages'][$this->items[$i]['data']['position']] > 0) {
	           			$this->items[$i]['data']['position'] ++;
	         		}
	         		$this->items[$this->items[$i]['parent']]['pages'] [$this->items[$i]['data']['position']]=$i;
				}
			}
			$old_path = $my_menue['path'];
	
			$i++;
	
		}

		$session_data['navigation']['items'] = $this->items;

		for($j=0; $j<$i; $j++) {
			usort($this->items[$j]['children'], array('\Cms\Helper\MenueItems', "sortByPosition"));
			usort($this->items[$j]['pages'], array('\Cms\Helper\MenueItems', "sortByPosition"));
			usort($this->items[$j]['folders'], array('\Cms\Helper\MenueItems', "sortByPosition"));
		}

		unset($session_data['navigation']['items']);

	}

	static function sortByPosition($mixA, $mixB) {
		global $session_data;
		
		if ($session_data['navigation']['items'][$mixA]['data']['position'] == $session_data['navigation']['items'][$mixB]['data']['position']) {
			$intResult = 0;
		}
		if($session_data['navigation']['items'][$mixA]['data']['position'] < $session_data['navigation']['items'][$mixB]['data']['position']) {
			$intResult = -1;
		} else {
			$intResult =  1;
		}

		return $intResult;

	}

   // gibt nur die Hauptmenüpunkte zurück
   function get_items_by_folder($folder) {
      //folder suchen
      $folders = explode('/',$folder);
      $aktuell = $this->items[0];
      $folder  = '';
      for($i=0; $i<count($folders); $i++) {
         $j=0;
         if($folders[$i]=='') continue;
         else $folder .= $folders[0].'/';
         while(
            $this->items[$aktuell['folders'][$j]]['data']['path'] != $folder &&
            $j < count($aktuell['folders'][$j])
            ) $j++;
         $aktuell = $this->items[$aktuell['folders'][$j]];
      }
      //elemente auflisten

      return $aktuell;
   }

   function get_items() {
      return $this->items;
   }
   
	static public function checkShowItem($arrItem) {
		global $user_data, $config;

		if(isset(self::$aCheckedItems[$arrItem['data']['id']][$config->show_hidden])) {

			$bolShowItem = self::$aCheckedItems[$arrItem['data']['id']][$config->show_hidden];

		} else {

			if($config->show_hidden) {
				$arrItem['data']['menue'] = 1;
			}

			$bolShowItem = true;
			// Wenn Punkt nicht ins Men� soll
			if(!$arrItem['data']['menue']) {
				$bolShowItem = false;
			// Wenn Seite nur angezeigt werden soll, wenn auch die Rechte da sind
			} elseif($arrItem['data']['menue'] == 2) {
				$arrItem['data']['access'] = \Util::decodeSerializeOrJson($arrItem['data']['access']);
				if(!\Cms\Helper\Access\Page::checkPageAccess($arrItem['data'])) {
					$bolShowItem = false;
				}
			} elseif($arrItem['data']['menue'] == 5) {
				$arrItem['data']['access'] = \Util::decodeSerializeOrJson($arrItem['data']['access']);
				if(\Cms\Helper\Access\Page::checkPageAccess($arrItem['data'])) {
					$bolShowItem = false;
				}
			} elseif($arrItem['data']['menue'] == 3) {
				if($user_data['login'] == 1) {
					$bolShowItem = false;
				}
			} elseif($arrItem['data']['menue'] == 4) {
				if($user_data['login'] != 1) {
					$bolShowItem = false;
				}
			} // END if menue = 1

			self::$aCheckedItems[$arrItem['data']['id']][$config->show_hidden] = $bolShowItem;

		}

		return $bolShowItem;
	}
   
}
// END CLASS menue_items
