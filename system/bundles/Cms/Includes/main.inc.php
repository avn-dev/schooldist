<?php

////////////////////////////////////////////////////////////////////////////////
//CMS                                // Mark Koopmann                         //
//PHP-Datei: main.inc	             // copyright by plan-i GmbH		      //
////////////////////////////////////////////////////////////////////////////////
//Erstellungsdatum:          	     // Systemdatei - steuert alle aufrufe    //
//letzte Änderung:                   //                                       //
//durch: Mark Koopmann               //                                       //
////////////////////////////////////////////////////////////////////////////////

$oPageAccess = new Cms\Helper\Access\Page($oPage);

if($session_data['ob'] === 1) {
	$objPageProcessor->start();
}

if(!$template_data['charset']) {
	$template_data['charset'] = "utf-8";
}

// checken, ob der user hier editieren darf
// prüfen auf rechte und ob ein anderer user gerade editiert
if($oPage->getMode() === Cms\Entity\Page::MODE_EDIT) {

	$bRight = $oPageAccess->checkRightInPath('edit', $user_data['id']);
	if(!$bRight) {
		$sMessage = "Sie haben keine Rechte, diese Seite zu editieren.";
		$this->showMessage($oPage, $sMessage);
	}

	if(
		$page_data['locked'] !== null &&
		$page_data['locked_by'] != $this->_oAccess->id &&
		$this->_oRequest->get('skip_locked', false) === false
	) {
		
		try {

			$dLocked = new DateTime($page_data['locked']);
			$dCompare = new DateTime('30 minutes ago');

			if($dLocked >= $dCompare) {
				$this->showLockingHint($oPage, $page_data);
			}
			
		} catch(\Exception $e) {
			
		}

	}

}

$page_id = (int)$page_data['id'];

if (!array_key_exists('access',$page_data)){
	$page_data['access'] = array();
}
if (!array_key_exists('access', $user_data)){
	$user_data['access'] = array();
}

if($user_data['cms']) {

	// Alle bisherigen Seiten entsperren und aktuelle Seite sperren
	\DB::executeQuery("UPDATE cms_pages SET locked = null, locked_by = null WHERE locked_by = ".(int)$user_data['id']."");
	if($oPage->getMode() === Cms\Entity\Page::MODE_EDIT) {
		\DB::executeQuery("UPDATE cms_pages SET locked = NOW(), locked_by = ".(int)$user_data['id']." WHERE id = ".(int)$page_id."");
	}

	$bCheckViewRight = $oPageAccess->checkRightInPath('edit_view_pages', $page_data['id']);
	if(!$bCheckViewRight) {
		die(L10N::t('Sie haben keine Berechtigung, diese Seite zu betreten!'));
	}

}

$session_data['access'] = array_intersect_key((array)$page_data['access'],(array)$user_data['access']);

// Wenn Seite nicht aktiv
if($page_data['active'] != 1 && !$user_data['cms']) {
	include(\Util::getDocumentRoot()."system/bundles/Cms/Includes/header.inc.php");
	\Util::handleErrorMessage("Diese Seite ist momentan nicht aktiv.");
}
// zugang kontrollieren
elseif (($page_data['access'] == "" || count($session_data['access'])>0) || $user_data['cms']) {

	// wenn typ interne Verknüpfung
	if ($page_data['element'] == "intern") {

		if(!$user_data['cms']) {

			$iInternalLink = (int)$page_data['internal_link'];
			if(
				empty($iInternalLink) &&
				!empty($page_data['parameter'])
			) {
				$iInternalLink = (int)$page_data['parameter'];
			}

			$sTemplate = $page_data['template'];
			$page_link = DB::getQueryRow("SELECT * FROM cms_pages WHERE id = ".(int)$iInternalLink."");
			$page_link['access'] = $page_data['access'];
			$page_link['cms'] = $page_data['cms'];
			$page_link['htmltitle'] = $page_data['htmltitle'];
			$page_link['language'] = $page_data['language'];
			$page_data = $page_link;
			$page_data['template'] = $sTemplate;
		} else {
			header("Location: /admin/preferences.html?page_id=".$page_data['id']);
			die();
		}
	}

	// wenn typ link
	if ($page_data['element'] == "link") {
		$objPageParser->insertStats();
		if(!$user_data['cms']) {
			if(count($_VARS) > 0) {
				$sVars = "?";
			}
			foreach($_VARS as $k=>$v) {
				$sVars .= $k."=".$v."&";
			}
			// Wenn ID einer Seite angegeben wurde, dann ID in URL umwandeln
			if(is_numeric($page_data['parameter'])) {
				$oLinkedPage = \Cms\Entity\Page::getInstance($page_data['parameter']);
				$page_data['parameter'] = $oLinkedPage->getLink();
			}
			header("Location: ".\Cms\Service\ReplaceVars::execute($page_data['parameter']).$sVars);
		} else {
			header("Location: /admin/preferences.html?page_id=".$page_data['id']);
		}
		die();
	// wenn typ content
	} elseif ($page_data['element'] == "content") {
		include(\Util::getDocumentRoot()."system/bundles/Cms/Includes/header.inc.php");
		getcontent();
		// seitenfuss einbauen
		include(\Util::getDocumentRoot()."system/bundles/Cms/Includes/footer.inc.php");
	// Wenn Typ XML, nur Ausgabe vornehmen
	} elseif ($page_data['element'] == "xml") {
		// Seitenvorlage wird ermittelt
		$templatecode = "<#content001#>";
		$template_id  = 0;
		$template_data['id']  = 0;
		$template_data['header'] = "";
		$template_data['charset'] = $page_data['parameter'];
		/*
		 * Anmerkung von Bastian (Haustein)
		 * NICE: Doc-Type für XML hinzufügen
		 * Minimale Änderungen
		 *  - in der preferences.html (Andere Doc-Types) und
		 *  - im Header-Teil von XML-Dateien
		 * wären notwendig
		 */
		//$template_data['doctype'] = ($page_data['doctype']);

		$template_data['css'] = "styles.css";
		if($user_data['cms']) {
			include(\Util::getDocumentRoot()."system/bundles/Cms/Includes/header.inc.php");
		} else {
			header('Content-Type: application/xml; charset='.$page_data['parameter']);
			echo '<?xml version="1.0" encoding="'.$page_data['parameter'].'" standalone="yes"?>';
		}
		$objPageParser->checkCode($templatecode);
		if($user_data['cms']) {
			include(\Util::getDocumentRoot()."system/bundles/Cms/Includes/footer.inc.php");
		}
	// wenn typ template dann template aus db holen und parsen
	} elseif ($page_data['element'] == "page") {

		\System::wd()->executeHook('page_data', $page_data, $this->_oRequest);

		if(isset($_VARS['printview']) && $_VARS['printview']) {
			$page_data['template'] = "print";
		}
		$name = $page_data['template'];
		// Für Manipulation der Seitenvorlage
		if($system_data['template_manipulation'] && $_VARS[$system_data['template_manipulation']]) {
			$name = $_VARS[$system_data['template_manipulation']];
		}
		// Seitenvorlage wird ermittelt
		$my_template = DB::getQueryRow("SELECT * FROM cms_pages WHERE file = '".$name."' AND element = 'template'");

		// Bei Vorlage mit Vorlage
		while($my_template['path']) {
			$my_parenttemplate = DB::getQueryRow("SELECT * FROM cms_pages WHERE file = '".$my_template['path']."' AND element = 'template'");
			$my_parenttemplate['template'] = str_replace("<#content9","<#content".str_pad($my_parenttemplate['id'], 9, "0", STR_PAD_LEFT).":9",$my_parenttemplate['template']);
			$my_template['header'] = $my_parenttemplate['header']."\n".$my_template['header'];
			$my_template['template'] = str_replace("<#content001#>",$my_template['template'],$my_parenttemplate['template']);
			$my_template['path'] = $my_parenttemplate['path'];
		}

		$templatecode = ($my_template['template']);
		$template_id  = $my_template['id'];
		$template_data['id']  = $my_template['id'];
		$template_data['header'] = ($my_template['header']);
		$template_data['doctype'] = ($my_template['doctype']);
		$template_data['css'] = ($my_template['css']);
		header('Content-Type: text/html; charset='.$template_data['charset']);

		if($session_data['ob']) {
			$objPageProcessor->preprocess();
		}

		$bOnlyContent = false;
		if(
			isset($_VARS['only_content']) &&
			$_VARS['only_content'] == 1
		) {
			$bOnlyContent = true;
		}

		// template parsen
		if($bOnlyContent === false) {
			include(__DIR__."/header.inc.php");
		}
		$objPageParser->checkCode($templatecode);

		$sFooter = '';

		\System::wd()->executeHook('page_footer', $sFooter);

		echo $sFooter;

		// seitenfuss einbauen
		if($bOnlyContent === false) {			
			include(__DIR__."/footer.inc.php");
		}

	// wenn typ code dann code ausgeben
	} elseif ($page_data['element'] == "code") {
		if($user_data['cms']) {
			header("Location: /admin/preferences.html?page_id=".$page_data['id']);
			die();
		}
		if($system_data['eval_php'] == "12012001") {
			eval(' ?>' . $page_data['template'] . '<?php ');
		} else {
			echo $page_data['template'];
		}
	} elseif ($page_data['element'] == "frameset") {
		include(\Util::getDocumentRoot()."system/bundles/Cms/Includes/header.inc.php");
		echo ($my_template['template']);
		// seitenfuss einbauen
		include(\Util::getDocumentRoot()."system/bundles/Cms/Includes/footer.inc.php");
	// wenn typ individuell dann datei includen
	} else {
		if(!@include(\Util::getDocumentRoot()."system/templates/".$my_template['template'])) {
			\Util::handleErrorMessage("Seite wurde nicht im Verzeichniss gefunden.");
		}
	}

// wenn keine rechte dann hinweis
} else {

	if(is_numeric($page_data['login'])) {
		$oLoginPage = Cms\Entity\Page::getInstance($page_data['login']);
		$strLoginPageLink = $oLoginPage->getLink();
	} else {
		$strLoginPageLink = $page_data['login'];
	}

	$_SERVER["REQUEST_URI"] = str_replace("logout=ok", "", $_SERVER["REQUEST_URI"]);
	$_SERVER["QUERY_STRING"] = str_replace("logout=ok", "", $_SERVER["QUERY_STRING"]);

	$aErrors = (array)\Core\Handler\SessionHandler::getInstance()->getFlashBag()->get('error');
	$aErrors = array_unique($aErrors);

	header("Location: ".$strLoginPageLink."?target_id=".$page_data['id']."&target_query=".$_SERVER['QUERY_STRING']."&logintarget=".$_SERVER["REQUEST_URI"]."&loginfailed=".implode(', ', $aErrors));
	die();
}

if($session_data['ob']) {

	$objPageProcessor->content = ob_get_clean();

	if(
		$system_data['wd_vars'] && 
		(
			$user_data['edit'] == false || 
			$page_data['layout'] == "block"
		)
	) {
		
		$objPageProcessor->replacevars();
	}
	
	$objPageProcessor->postprocess();
	
}
