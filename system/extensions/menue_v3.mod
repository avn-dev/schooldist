<?php

global $config;
$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

$objWebDynamicsDAO = new Cms\Helper\Data;

if(!isset($config->with_language)) {
	$config->with_language = true;
}

// Vorbereitungen:
################################################################################
// Globals
global $user_data;    	// System, Array
global $site_data;      // System, Array
global $P;
global $template;
global $menue_template;
$menue_template= array();

$GLOBALS['arrCheckShowItemCache'] = array();

// Template holen
$template = $element_data["content"];

$template = stripslashes($template);

if(!function_exists('generate_menue')){
// funktionen zum generieren definieren

function recursion_check($add = 0){
	// die folgenden Zeilen fangen fehler ab, die zu unendlicher recursion f�hren w�rden
	// $add enth�lt +1 am anfang einer recursion, -1 am ende einer recursion
	global $anz_rec;
	global $max_rec_error_mail_send;
	global $domain;
	$anz_rec += $add;
	if($anz_rec >=100) {
		if($max_rec_error_mail_send != 1) {
			\Util::handleErrorMessage("Das Menü auf '$domain' erzeugt beim rekursiven Durchlauf einen Fehler");
			$max_rec_error_mail_send = 1;
		}
		return true;
	}
	if($anz_rec<0) die("anz_rec_error!!!!");
	return false;
}

function kill_block(&$template, $block_name){

	$iCheck=0;
	
	while(
		(!(strpos($template,"<#".$block_name."#>") === false))
		&&(!(strpos($template,"<#/".$block_name."#>") === false))
	) {
		$len  = strlen($block_name) + 4;
		$pos  = strpos($template,"<#".$block_name."#>");
		$end  = strpos($template,"<#/".$block_name."#>",$pos);
	    $template = substr($template, 0, $pos)  .  substr($template, $end+$len+1);

	    if($iCheck > 20) {
	    	break;
	    }
	    $iCheck++;

	}

	return $template;

}

function kill_tags(&$code, $block){
	$new_code = str_replace("<#$block#>", '', $code);
	$new_code = str_replace("<#/$block#>", '', $new_code);
	return $new_code;
}

function kill_block_or_tags($condition,  &$code, $block, $not_block=''){
	if($condition) {
		$new_code =   kill_tags ($code,     $block);
		if($not_block!='')
			$new_code = kill_block($new_code, $not_block);
	} else {
		$new_code =   kill_block($code,     $block);
		if($not_block!='')
			$new_code = kill_tags ($new_code, $not_block);
	}
	return $new_code;
}


function generate_menue(&$items, $actual = 0, $brothers_cnt = 0, $level = 0, $temp_level = 0) {
	global $intCurrentDirectoryId;
	global $menue_template;
	global $template;
	global $interaction;
	global $file_ext;
	global $language;
	global $shop_actual_inv_group_higlight;
	global $shop_actual_inv_group;
	global $SHOP_PATH;
	global $SHOP_LIST_PAGE;
	global $page_data;
	global $_VARS;
	global $user_data;
	global $config;

	if (recursion_check(1)) {
		return;
	}

	$item = $items[$actual];
	$item['data']['path'] = '/'.$item['data']['path'];
	$strPagePath = '/'.$page_data['path'];
	if (!$item) {
		return;
	}

	$oPage = \Cms\Entity\Page::getInstance($item['data']['id']);
	
	$bolShowItem = \Cms\Helper\MenueItems::checkShowItem($item);

	// next_sibling suchen:
	$brother = false;
	if ($level > 0) {
		$parent = $items[$item['parent']];
		reset($parent['children']);
		while (current($parent['children']) != $actual) {
			if (next($parent['children']) === false) {
				die("<br>brother-serch-error!");
			}
		}
		// search for brother with menue = 1
		do {
			$brother = next($parent['children']);
		} while($brother && !\Cms\Helper\MenueItems::checkShowItem($items[$brother]));
	} #TEST# else $html_output = "#NOPARENT:$actual#";

	$deeper = -1; // zeigt an, ob tiefere Ebenen bearbeitet werden sollen

	// checken, ob f�r diese ebene �berhaupt ein template existiert...
	// zwei varianten: f�r active und inactive
	if (!is_array($menue_template)) {
		$menue_template = array();
	}

	if ($menue_template[$temp_level]['menue'] !== false) {

		if (isset($menue_template[$temp_level])) {
			$menue_template[$temp_level] = array();
		}
		if ($menue_template[$temp_level]['menue'] == "") {

			$menue_template[$temp_level]['menue'] = \Cms\Service\PageParser::checkForBlock($template, "menue_$temp_level");
			
			if(strpos($menue_template[$temp_level]['menue'], '<#data:') !== false) {
				 preg_match_all("/<#data:(.*?)#>/i", $menue_template[$temp_level]['menue'], $aMatch);
				 $menue_template[$temp_level]['placeholders'] = $aMatch[1];
			}
			
			if (!$menue_template[$temp_level]['menue']) {
				$menue_template[$temp_level]['menue'] = false;
			} else {
				$menue_template[$temp_level]['exact_page']    = \Cms\Service\PageParser::checkForBlock($menue_template[$temp_level]['menue'], "if:exact");
				$menue_template[$temp_level]['inexact_page']  = \Cms\Service\PageParser::checkForBlock($menue_template[$temp_level]['menue'], "ifnot:exact");
				$menue_template[$temp_level]['active_page']   = \Cms\Service\PageParser::checkForBlock($menue_template[$temp_level]['menue'], "if:active");
				$menue_template[$temp_level]['inactive_page'] = \Cms\Service\PageParser::checkForBlock($menue_template[$temp_level]['menue'], "ifnot:active");
			}

		}
		// ende men�template laden

		// Notitz: in dem Template-Schnipsel (active oder inactive) gibt es eine Markierung (<#insert:$temp_level#>).
		// An dieser Stelle wird der Code tieferer Ebenen eingef�gt => Alles vorher wird also in $menue_html_output eingef�gt,
		// und der rest in $post zwischen gespeichert. Wenn es existiert, wird das men�template hier beearbeitet.

		// template ist geladen, wenn es existiert:
		if (!($menue_template[$temp_level]['menue'] === false)) {

			// richtiges Men�template ausw�hlen (active oder exact-tags)

			// option 1) Der Pfad stimmt einfach
			// option 2) Die Get-Parameter weisen diese Seite als virtuelle shop-seite aus

			$bLocalDebug = false;

			if (isset($item['data']['status'])) {
				$active 	= "";
				$inactive 	= $menue_template[$temp_level]['inactive_page'];
				$exact   	= "";
				$inexact 	= $menue_template[$temp_level]['inexact_page'];
				if ($item['data']['status'] == 1 || $item['data']['status'] == 2) {
					$active 	= $menue_template[$temp_level]['active_page'];
					$inactive 	= "";
				}
				if ($item['data']['status'] == 2) {
					$exact   	= $menue_template[$temp_level]['exact_page'];
					$inexact 	= "";
				}
			} else {
				if ($bLocalDebug) {
					echo '>> '.$item['data']['title'].'<br />';
					echo '** '.$item['data']['id'].'<br />';
					echo '** '.$page_data['id'].'<br />';
				}
				if (

					// option 1)

					(

						// option 1.1)
						(
							$page_data['id'] == $item['data']['id'] || // item ist aktuelle datei
							(
								$item['data']['file'] == "index" &&
								strpos($strPagePath, $item['data']['path']) === 0 // item ist oberverzeichnis der aktuellen Datei
							)
						) ||

						// option 1.2)
						(
							strpos($strPagePath, $item['data']['path'].$item['data']['file'].'.'.$file_ext) === 0 ||  // item ist aktuelle datei
							(
								$item['data']['file'] == "index" &&
								strpos($strPagePath, $item['data']['path']) === 0 // item ist oberverzeichnis der aktuellen Datei
							)
						)
	        		) && (
	        			strpos($item['data']['get_param'], 'shop_') === false
	        		)

	        		||

					// option 2)
					(
						is_array($shop_actual_inv_group_higlight) &&
						in_array($item['data']['get_param'], $shop_actual_inv_group_higlight, true) !== false
					)
				) {
					if ($bLocalDebug) {
						echo '++ aktiv<br />';
					}
					// Dann aktiv
					$active = $menue_template[$temp_level]['active_page'];
					$inactive = "";
				} else  {
					if ($bLocalDebug) {
						echo '++ inaktiv<br />';
					}
					// Sonst inaktiv
					$active = "";
					$inactive = $menue_template[$temp_level]['inactive_page'];
				}


	    // nochmal:
	    // option 1) Der Pfad stimmt einfach
	    // option 2) Die Get-Parameter weisen diese Seite als virtuelle shop-seite aus

	    if(
	      	// option 1.1
	      	$page_data['id'] == $item['data']['id'] // item ist aktuelle datei
	
			||

			$intCurrentDirectoryId == $item['data']['id'] // verzeichnis ist aktuelle datei

	       	||

	      	// option 1.2
	      	(
	      		(strpos($strPagePath, $page_data['language'].$item['data']['path'].$item['data']['file'].'.html') === 0) // item ist aktuelle datei
	      		&& (!$item['data']['get_param']) 
	      	)
	       
	       	||
	
	      	// option 2
			(	
				(!$shop_actual_inv_group_higlight&&!$shop_actual_inv_group_higlight=='0') 
				||
				($item['data']['get_param'] == 'shop_actual_inv_group='.$shop_actual_inv_group)
	        )
	      )
		{
			if ($bLocalDebug) {
				echo '++ exact<br />';
			}
			$exact   = $menue_template[$temp_level]['exact_page'];
			$inexact = "";
		}
		else
		{
			if ($bLocalDebug) {
				echo '++ inexcat<br />';
			}
			$exact   = "";
			$inexact = $menue_template[$temp_level]['inexact_page'];
		}
	    // Ende richtiges Template ausw�hlen
	}
	   
    // variablen ersetzen, den zweiten teil in $post speichern, und den ersten teil schon "ausgeben"
    $new_code = \Cms\Service\PageParser::replaceBlock($menue_template[$temp_level]['menue'],"if:active", $active);
    $new_code = \Cms\Service\PageParser::replaceBlock($new_code,"ifnot:active", $inactive);
    $new_code = \Cms\Service\PageParser::replaceBlock($new_code,"if:exact", $exact);
    $new_code = \Cms\Service\PageParser::replaceBlock($new_code,"ifnot:exact", $inexact);

    $sUrl = $oPage->getLink();

    $new_code = str_replace('<#url#>', $sUrl, $new_code);//"/$language".$item[data][path].$item[data][file].'.'.$file_ext, $new_code);
    
    $item['data']['dir'] = explode("/",$item['data']['path']);

    // ersetzungen durchf�hren
	
	$new_code = str_replace('<#title#>',$item['data']['title'], $new_code);
	$new_code = str_replace('<#description#>',$item['data']['description'], $new_code);
	$new_code = str_replace('<#parameter#>',$item['data']['parameter'], $new_code);
    $new_code = str_replace('<#message#>',$item['data']['message'], $new_code);
    $new_code = str_replace('<#color1#>',$item['data']['color1'], $new_code);
    $new_code = str_replace('<#color2#>',$item['data']['color2'], $new_code);

    foreach((array)$menue_template[$temp_level]['placeholders'] as $sPlaceholder) {
    	$new_code = str_replace('<#data:'.$sPlaceholder.'#>',$item['data'][$sPlaceholder], $new_code);
    }

    $new_code = str_replace('<#path:1#>',$item['data']['dir'][1], $new_code);
	$new_code = str_replace('<#path:2#>',$item['data']['dir'][2], $new_code);
	$new_code = str_replace('<#path:3#>',$item['data']['dir'][3], $new_code);
    $new_code = str_replace('<#level#>',$level, $new_code);
    $new_code = str_replace('<#nr#>',$brothers_cnt+1, $new_code);
    $new_code = str_replace('<#page_id#>',$item['data']['id'], $new_code);
    $new_code = str_replace('<#page_file#>',$item['data']['file'], $new_code);
    $new_code = str_replace('<#id#>',$actual, $new_code);
    $new_code = str_replace('<#parent_id#>',$item['parent'], $new_code);

    // if:color1 bedingt ausschneiden
    $new_code = kill_block_or_tags((strlen($item['data']['color1'])>0), $new_code, 'if:color1','ifnot:color1');

    // if:color2 bedingt ausschneiden
    $new_code = kill_block_or_tags((strlen($item['data']['color2'])>0), $new_code, 'if:color2','ifnot:color2');

	$bolPrev = 0;
	$bolPost = 0;
	if($bolShowItem) {
		if($brothers_cnt == 0) {
			$bolPrev = 1;
		}
		if(!$brother) {
			$bolPost = 1;
		}
	}

    //pre-block nur beim ersten element
    $new_code = kill_block_or_tags($bolPrev, $new_code, 'prev');

    //post-Block nur beim letzten element
    //Separator-Block nur zwischen elemnten
    $new_code = kill_block_or_tags($bolPost, $new_code, 'post', 'separator');
	    
    //herausfinden, ob und in welcher Men�-tiefe die n�chst ebene eingebaut werden soll
    $ipos = strpos($new_code, '<#insert:');
    if(!($ipos === false)){
      $deeper=(int)substr($new_code, $ipos+9,3);
      $insert='<#insert:'.$deeper.'#>';
    };

    // herausfinden, ob und in welcher Men�-tiefe eine die n�chste ebene per post-insert ingebaut werden soll
    $post_deeper = -1;          #postinsert:4#>
    $ipos = strpos($new_code, '<#postinsert:');
    if(!($ipos === false)){
      $post_deeper=(int)substr($new_code, $ipos+13,3);
      // Das postinsert-tag auf alle f�lle l�schen
      $new_code = str_replace('<#postinsert:'.$post_deeper.'#>', '', $new_code);
    };

	if($bolShowItem) {

	    // untermen�-sektion ausschneiden, wenn kein untermen� definiert
	    $bolSub = 0;
	    if(
	    	$item['data']['file'] == 'index' &&
	    	count($item['children']) > 0
	    ) {
	    	$bolSub = 1;
	    }
	    $new_code = kill_block_or_tags($bolSub, $new_code, 'if:sub', 'ifnot:sub');
	
	    // indexseiten ausschneiden
	    $new_code = kill_block_or_tags((($item['data']['file']=='index')), $new_code, 'if:index', 'ifnot:index');

		$intCountNextBrother = $brothers_cnt + 1;

	} else {

		$new_code = '';

		$intCountNextBrother = $brothers_cnt;

	}

    //untermen� einf�gen, wenn aktiv, vorhanden und insert vorgesehen
    //if($item['data']['menue'] > 0 && count($item['children'])>0 && $deeper != -1){
	if(count((array)$item['children']) > 0 && $deeper != -1){
		$new_code = explode($insert,$new_code);
		$html_output .= $new_code[0];
		$html_output .= generate_menue($items, reset($item['children']), 0, $level+1, $deeper);
		$html_output .= $new_code[1];
	} else {
		if($insert) {
			$new_code = str_replace($insert, '', $new_code);
		}
		$html_output .= $new_code;
    }

    // n�chsten bruder aufrufen
    if($brother) {
    	$html_output .= generate_menue($items, $brother, $intCountNextBrother, $level, $temp_level);
    }

    //# post-insert-child aufrufen

    if(count((array)$item['children'])>0 && $post_deeper != -1){
      // Child aufrufen
      $html_output .= generate_menue($items, reset($item['children']), 0, $level+1, $post_deeper);
    }

  } // ende men�template bearbeiten
  }

  //} else { // Seite
  //}
  recursion_check(-1);
  return $html_output;
}


} // ende if function exists







// Hauptfunktion: Men� generieren

global $shop_actual_inv_group, $intCurrentDirectoryId;

$arrCurrentPage = $objWebDynamicsDAO->getPageData($page_data['id']);

$intCurrentDirectoryId = 0;

if($arrCurrentPage['indexpage'] == 1) {

	$strSql = "
			SELECT 
				* 
			FROM 
				cms_pages 
			WHERE 
				`site_id` = :intSiteId AND
				`path` = :strFilePath AND
				`file` = 'index' AND
				`active` = 1
			LIMIT 1
			";
	$arrTransfer = array();
	$arrTransfer['intSiteId'] 	= $arrCurrentPage['site_id'];
	$arrTransfer['strFilePath'] = $arrCurrentPage['path'];
	$arrTemp = DB::getPreparedQueryData($strSql, $arrTransfer);	

	$intCurrentDirectoryId = $arrTemp[0]['id'];

}

// nur eine Instanz erzeugen und weiter verwenden!
global $menue_template;
$menue_template = array();
global $__menue;

if(is_numeric($config->start_page)) {
	$arrStartPage = $objWebDynamicsDAO->getPageData($config->start_page);
	$__menue = new \Cms\Helper\MenueItems(10, 500, $arrStartPage['path']);
} elseif($config->start_page != "") {
	$config->start_page = @substr($config->start_page,0,strrpos($config->start_page,"/")+1);
	$config->start_page = @substr($config->start_page,strpos($config->start_page,"/",2)+1);
	$__menue = new \Cms\Helper\MenueItems(10, 500, $config->start_page);
} else {
	if(!$__menue) $__menue = new \Cms\Helper\MenueItems();	
}
$items = $__menue->get_items();
/*
 * set hook if not in edit mode
 */
if($session_data['public']) {
	\System::wd()->executeHook('menue_v3_'.$element_data['content_id'], $items);
}

$html = generate_menue($items);

echo $html;