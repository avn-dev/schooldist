<?php

// Sebastian Kaiser: wieder reingenommen, konnte auf die schnelle nicht nachvollziehen, warum es rausgenommen wurde
if(!$template_data['charset']) {
	$template_data['charset'] = "utf-8";
}

//We need this query rather often, so why repeat it?
//$result_meta = db_query($db_data['system'],"SELECT * FROM cms_meta WHERE page_id = '".$page_data['id']."' OR page_id = '0' ORDER BY page_id DESC LIMIT 1");
//$my_meta=get_data($result_meta);
$my_meta[]="";
$my_meta_lokal = DB::getQueryRow("SELECT * FROM cms_meta WHERE page_id = ".(int)$page_data['id']."");
$my_meta_global = DB::getQueryRow("SELECT * FROM cms_meta WHERE page_id = 0");
$my_meta_testcase = 0;

if ($my_meta_global['meta_handling_localallow']) {
	// Es darf lokal modifiziert werden
	if (!$my_meta_lokal['meta_handling']) {
		// Ist aber leer...
		$my_meta_testcase = $my_meta_global['meta_handling'];
	} else {
		// Enthält eine Einstellung
		$my_meta_testcase = $my_meta_lokal['meta_handling'];
	}
} else {
	// Also direkt nach der globalen Einstellung schauen
	$my_meta_testcase = $my_meta_global['meta_handling'];
}

switch ($my_meta_testcase) {
	case 1: // Global ergänzt Lokal (Empfohlen)
		$my_meta = $my_meta_lokal;
		foreach ($my_meta_global as $my_meta_global_key => $my_meta_global_value) {
			if (!$my_meta_lokal[$my_meta_global_key]) {
				$my_meta[$my_meta_global_key] = $my_meta_global_value;
			}
		}
		break;
	case 2: // Lokal ignorieren
		$my_meta = $my_meta_global;
		break;
	case 3: // Global ignorieren
		$my_meta = $my_meta_lokal;
		break;
	default: // Wenn keine Einstellung vorgenommen wurde, verfahren wie bisher
		$my_meta = $my_meta_global;
		if ($my_meta_lokal) {
			$my_meta = $my_meta_lokal;
		}
}

//Autoren bestimmen
{
	$arrUser = array();
	$resData = DB::getQueryRows("SELECT id,firstname,lastname FROM system_user WHERE 1 ORDER BY lastname");
	$arrUser[0] = $my_meta['dc_author_text'];
	foreach($resData as $arrData) {
		$arrUser[$arrData['id']] = $arrData['firstname']." ".$arrData['lastname'];
	}

	$my_author = $my_meta['dc_author'];
	if (isset($my_author)) {
		$my_author = $arrUser[$my_author];
	}
	if (!$my_author) {
		$my_author = $arrUser[$page_data['author']];
	}
	
	$my_meta['author'] = $my_author;
}

$my_meta['generator'] = 'Fidelo Framework by Fidelo Software GmbH';
$my_meta['revisit_after'] = '14 days';

// manipulate meta data
\System::wd()->executeHook('meta_data', $my_meta, $page_data);

// Automagic switching for doc- and mime-types
$template_data['automatic_mode'] = false;
$template_data['automatic_xhtml'] = false;
$template_data['automatic_mimetype'] = "text/html";
$template_data['automatic_doctype'] = "";
$template_data['automatic_language'] = "de";

// We need the language
if($page_data['id']) {
	if ($my_meta['Content_Language']) {
		$template_data['automatic_language'] = $my_meta['Content_Language'];
	}
}

// The actual switching mechanism
if ($template_data['doctype'] == 'automatic switch XHTML1.1 / HTML 4.01') {
	$template_data['automatic_mode'] = true;
	if(stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml")) {
    	if(preg_match("/application\/xhtml\+xml;q=([01]|0\.\d{1,3}|1\.0)/i",$_SERVER["HTTP_ACCEPT"],$matches)) {
    		$xhtml_q = $matches[1];
    		if(preg_match("/text\/html;q=q=([01]|0\.\d{1,3}|1\.0)/i",$_SERVER["HTTP_ACCEPT"],$matches)) {
    			$html_q = $matches[1];
    			if((float)$xhtml_q >= (float)$html_q) {
    				$template_data['automatic_mimetype'] = "application/xhtml+xml";
				}
    		}
    	} else {
    		$template_data['automatic_mimetype'] = "application/xhtml+xml";
    	}
	}
	if($template_data['automatic_mimetype'] == "application/xhtml+xml") {
		$template_data['automatic_xhtml'] = true;
		$template_data['automatic_doctype'] = "<?xml version=\"1.0\" encoding=\"".$template_data['charset']."\" ?".">\n<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"".$template_data['automatic_language']."\">\n";
	} else {
		$template_data['automatic_doctype'] = "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">\n<html lang=\"".$template_data['automatic_language']."\">\n";
	}
	header("Content-Type: ".$template_data['automatic_mimetype'].";charset=".$template_data['charset']);
	header("Vary: Accept");
	if (strlen($template_data['automatic_doctype']) > 0) {
		echo $template_data['automatic_doctype']."\n";
	}
} else {

	$sHeaderLanguage = $page_data['language'];

	System::wd()->executeHook('header_language', $sHeaderLanguage);

	switch($template_data['doctype']) {
		case 'xhtml_strict':
			
			echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
			echo "\n";
			echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$sHeaderLanguage.'" lang="'.$sHeaderLanguage.'">';
			echo "\n";
			break;
		case 'html5':
			echo '<!DOCTYPE html>';
			echo "\n";
			echo '<html lang="'.$sHeaderLanguage.'">';
			echo "\n";
			break;
		default:
			if(strlen($template_data['doctype']) > 0) {
				echo $template_data['doctype']."\n";
			}
			echo "<html>\n";
	}

}

echo "<head";
if ($my_meta['dublin_core']) {
	echo " profile=\"http://dublincore.org/documents/dcq-html/\"";
}
echo ">\n";
?>

<meta http-equiv="pragma" content="no-cache" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="Content-Type" content="<?=$template_data['automatic_mimetype']?>; charset=<?=$template_data['charset']?>" />

<meta name="cms-page-id" content="<?=$page_data['id']?>" />

<?
if(trim($my_meta['generator']) != '')
{
	echo '<meta name="GENERATOR" content="' . $my_meta['generator'] . '" />';
}
?>
<meta name="revisit-after" content="<?=$my_meta['revisit_after']?>" />
<?
if(trim($my_meta['author']) != '')
{
	echo '<meta name="author" content="' . $my_meta['author'] . '" />';
}
?>

<?=$my_meta['additional_meta_code']?>

<?
if($page_data['id']) {

	if ($my_meta['publisher']) { echo "<meta name=\"publisher\" content=\"".$my_meta['publisher']."\" />\n"; }
	if ($my_meta['Publisher_Email']) { echo "<meta name=\"Publisher_Email\" content=\"".$my_meta['Publisher_Email']."\" />\n"; }
	if ($my_meta['Identifier_URL']) { echo "<meta name=\"Identifier_URL\" content=\"".$my_meta['Identifier_URL']."\" />\n"; }
	if ($my_meta['DESCRIPTION']) { echo "<meta name=\"description\" content=\"".$my_meta['DESCRIPTION']."\" />\n"; }
	if ($my_meta['keywords']) { echo "<meta name=\"keywords\" content=\"".$my_meta['keywords']."\" />\n"; }
	if ($my_meta['page_topic']) { echo "<meta name=\"page_topic\" content=\"".$my_meta['page_topic']."\" />\n"; }
	if ($my_meta['audience']) { echo "<meta name=\"audience\" content=\"".$my_meta['audience']."\" />\n"; }
	if ($my_meta['robots']) { echo "<meta name=\"robots\" content=\"".$my_meta['robots']."\" />\n"; }
	if ($my_meta['Content_Language']) { echo "<meta name=\"Content_Language\" content=\"".$my_meta['Content_Language']."\" />\n"; }
	if ($my_meta['revisit_after']) { echo "<meta name=\"revisit_after\" content=\"".$my_meta['revisit_after']."\" />\n"; }
	if ($my_meta['expires']) { echo "<meta name=\"expires\" content=\"".$my_meta['expires']."\" />\n\n"; }

	// Dublin Core
	if ($my_meta['dublin_core']) { //(meta_dublincore)
		echo "<link rel=\"schema.DC\" href=\"http://purl.org/dc/elements/1.1/\" />\n";
		echo "<link rel=\"schema.DCTERMS\" href=\"http://purl.org/dc/terms/\" />\n";
		if ($page_data['htmltitle']) { echo "<meta name=\"DC.title\" content=\"".$page_data['htmltitle']."\" />\n"; }
		if ($page_data['author']) { echo "<meta name=\"DC.creator\" content=\"".$my_author."\" />\n"; }
		if ($my_meta['page_topic']) { echo "<meta name=\"DC.subject\" content=\"".$my_meta['page_topic']."\" />\n"; }
		if ($my_meta['DESCRIPTION']) { echo "<meta name=\"DC.description\" content=\"".$my_meta['DESCRIPTION']."\" />\n"; }
		if ($my_meta['publisher']) { echo "<meta name=\"DC.publisher\" content=\"".$my_meta['publisher']."\" />\n"; }
		if ($my_meta['dc_contributors']) {
			$contributors = explode("\n",$my_meta['dc_contributors']);
			foreach ($contributors as $contributor) {
				echo "<meta name=\"DC.contributor\" content=\"".trim($contributor)."\" />\n";
			}
		}
		if ($my_meta['dc_date']) { echo "<meta name=\"DC.date\" content=\"".$my_meta['dc_date']."\""; if ($my_meta['dc_date_isScheme']) { echo " scheme=\"DCTERMS.W3CDTF\""; } echo " />\n"; }
		if ($my_meta['dc_type']) { echo "<meta name=\"DC.type\" content=\"".$my_meta['dc_type']."\" scheme=\"DCTERMS.DCMIType\" />\n"; }
		if ($template_data['automatic_mimetype']) { echo "<meta name=\"DC.format\" content=\"".$template_data['automatic_mimetype']."\" scheme=\"DCTERMS.IMT\" />\n"; }
		if ($my_meta['Identifier_URL']) { echo "<meta name=\"DC.identifier\" content=\"".$my_meta['Identifier_URL']."\" scheme=\"DCTERMS.URI\" />\n"; }
		if ($my_meta['dc_source']) { echo "<meta name=\"DC.source\" content=\"".$my_meta['dc_source']."\""; if ($my_meta['dc_source_isScheme']) { echo " scheme=\"DCTERMS.URI\""; } echo " />\n"; }
		if ($my_meta['Content_Language']) { echo "<meta name=\"DC.language\" content=\"".$my_meta['Content_Language']."\" />\n"; }
		if ($my_meta['dc_relation']) { echo "<meta name=\"DC.relation\" content=\"".$my_meta['dc_relation']."\" />\n"; }
		if ($my_meta['dc_coverage']) { echo "<meta name=\"DC.coverage\" content=\"".$my_meta['dc_coverage']."\""; if ($my_meta['dc_coverage_isScheme']) { echo " scheme=\"DCTERMS.TGN\""; } echo " />\n"; }
		if ($my_meta['dc_rights']) { echo "<meta name=\"DC.rights\" content=\"".$my_meta['dc_rights']."\""; if ($my_meta['dc_rights_isScheme']) { echo " scheme=\"DCTERMS.URI\""; } echo " />\n"; }
	}

	if($oPage->search != 1) {
		echo "<meta name=\"robots\" content=\"noindex\">\n";
	}
	
	if($page_data['original_language'] === '') {
		$aSiteLanguages = $oSite->getLanguages(1);
		foreach($aSiteLanguages as $sSiteLanguage) {
			if($sSiteLanguage !== $page_data['language']) {
				echo "\t\t";
				echo '<link rel="alternate" href="'.$oPage->getLink($sSiteLanguage).'" hreflang="'.$sSiteLanguage.'" />';
				echo "\n";
			}
		}
	}

	// Relational Links (W3C)
	if ($my_meta['relational_links']) {
		if ($my_meta['link_alternates']) {
			$alternates = explode("\n", $my_meta['link_alternates']);
			foreach ($alternates as $alternate) {
				$alt_parts = explode(",", $alternate);
				$alt_url = $alt_parts[0];
				$alt_lang = $alt_parts[1];
				echo "<link rel=\"alternate\" href=\"".trim($alt_url)."\"";
				if ($alt_lang) {
					echo " ";
					if ($template_data['automatic_mode'] && $template_data['automatic_xhtml']) {
						echo "xml:";
					}
					echo "lang=\"".trim($alt_lang)."\"";
				}
				echo " />\n";
			}
		}
		if ($my_meta['link_start']) { echo "<link rel=\"start\" href=\"".$my_meta['link_start']."\" />\n"; }
		if ($my_meta['link_next']) { echo "<link rel=\"next\" href=\"".$my_meta['link_next']."\" />\n"; }
		if ($my_meta['link_prev']) { echo "<link rel=\"prev\" href=\"".$my_meta['link_prev']."\" />\n"; }
		if ($my_meta['link_contents']) { echo "<link rel=\"contents\" href=\"".$my_meta['link_contents']."\" />\n"; }
		if ($my_meta['link_index']) { echo "<link rel=\"index\" href=\"".$my_meta['link_index']."\" />\n"; }
		if ($my_meta['link_glossary']) { echo "<link rel=\"glossary\" href=\"".$my_meta['link_glossary']."\" />\n"; }
		if ($my_meta['link_copyright']) { echo "<link rel=\"copyright\" href=\"".$my_meta['link_copyright']."\" />\n"; }
		if ($my_meta['link_chapter']) { echo "<link rel=\"chapter\" href=\"".$my_meta['link_chapter']."\" />\n"; }
		if ($my_meta['link_section']) { echo "<link rel=\"section\" href=\"".$my_meta['link_section']."\" />\n"; }
		if ($my_meta['link_subsection']) { echo "<link rel=\"subsection\" href=\"".$my_meta['link_subsection']."\" />\n"; }
		if ($my_meta['link_appendix']) { echo "<link rel=\"appendix\" href=\"".$my_meta['link_appendix']."\" />\n"; }
		if ($my_meta['link_help']) { echo "<link rel=\"help\" href=\"".$my_meta['link_help']."\" />\n"; }
		if ($my_meta['link_bookmarks']) {
			$bookmarks = explode("\n", $my_meta['link_bookmarks']);
			foreach ($bookmarks as $bookmark) {
				$bm_parts = explode(",", $bookmark);
				$bm_url = $bm_parts[0];
				$bm_title = $bm_parts[1];
				echo "<link rel=\"bookmark\" href=\"".trim($bm_url)."\"";
				if ($bm_title) {
					echo " title=\"".trim($bm_title)."\"";
				}
				echo " />\n";
			}
		}
	}

	if(!empty($template_data['css'])) {
	?>
	<link type="text/css" rel="stylesheet" href="/css/<?=$template_data['css']?>" />
	<?
	}
	if($oSite->favicon_active == 1) { 
	?>
	<link rel="shortcut icon" href="/favicon.ico" />
	<? 
	}

	\System::wd()->executeHook('html_header', $template_data);

	echo stripslashes($template_data['header']);

	echo stripslashes($page_data['header']);

}

if(
	isset($oDebugBarRenderer) &&
	$oDebugBarRenderer instanceof \DebugBar\JavascriptRenderer
) {
	echo $oDebugBarRenderer->renderHead();
}

?>

	<title><?= System::d('title_template')?></title>

	<link rel="canonical" href="<?=$this->_oRequest->getUri()?>">
	
</head>

<?
if($page_data['element'] != "frameset") {

	$sBodyTagParameter = "";

	// im Zuge der Kompatibilität onload und onbeforeunload nur einbauen, wenn es sinn macht:
	if(isset($printview) && $printview) // hier weitere prüfungen ggf. mit or ergänzen
	{
		$sBodyTagParameter .= " onload=\"";

		if($printview) {
			$sBodyTagParameter .= "window.print();";
			$bg_tag = "";
		}

		$sBodyTagParameter .= "\"";
	}

	if($user_data['cms'] == true) {
		$sBodyTagParameter .= " onbeforeunload=\"if(parent && parent.Page) { parent.Page.resetPublish(); }\"";
	}

	$sBodyTag = "<body";
	if (isset($bg_tag) && strlen($bg_tag) > 0) {
		$sBodyTag .= " ".$bg_tag;
	}
	if (strlen($sBodyTagParameter) > 0) {
		$sBodyTag .= " ".$sBodyTagParameter;
	}
	if ($template_data['automatic_mode'] && $template_data['automatic_xhtml']) {
		$sBodyTag .= " id=\"top\"";
	}
	$sBodyTag .= ">";

	\System::wd()->executeHook('body_tag', $sBodyTag);

	echo $sBodyTag;

}

if($system_data['community_active']) {

	// Community Funktionen
	if(
		$browser_data['agent'] != "Bot" && 
		$session_data['cookie'] && 
		!$session_data['error'] && 
		$system_data['imagepage'] == false &&
		$system_data['csspage'] == false &&
		!$user_data['cms'] && 
		$system_data['whoisonline_active']
	) {
		insertWhoisonline();
	}
	
	if(isset($user_data['idTable'])) {
	
		// Favorit hinzufügen
		if($_VARS['addbookmark'] == 1) {
			insertBookmark($page_data['id']);
		}
		// User in Buddyliste eintragen
		if($_VARS['addBuddy'] == 1) {
			insertBuddy($_VARS['idBuddy'], $_VARS['tblBuddy']);
		}
		// User in Blacklist eintragen
		if($_VARS['addBlacklist'] == 1) {
			insertBlacklist($_VARS['idBlacklist'], $_VARS['tblBlacklist']);
		}
	
		// Eigene Blacklisteinträge holen
		$res = (array)DB::getQueryRows("SELECT idBlacklist, tblBlacklist FROM community_blacklist WHERE tblUser = ".(int)$user_data['idTable']." AND idUser = ".(int)$user_data['id']."");
		foreach($res as $my) {
			$user_data['blacklist']['check'][$my['tblBlacklist']."|".$my['idBlacklist']] = array($my['idBlacklist'], $my['tblBlacklist']);
			$user_data['blacklist']['db'][$my['tblBlacklist']][] 	= $my['idBlacklist'];
		}
		// Fremde Blacklisteinträge holen
		$res = (array)DB::getQueryRows("SELECT idUser, tblUser FROM community_blacklist WHERE tblBlacklist = ".(int)$user_data['idTable']." AND idBlacklist = ".(int)$user_data['id']."");
		foreach($res as $my) {
			$user_data['blacklist']['recheck'][$my['tblUser']."|".$my['idUser']] = array($my['idUser'], $my['tblUser']);
			$user_data['blacklist']['redb'][$my['tblUser']][] 	= $my['idUser'];
		}
		
	}

	if($system_data['whoisonline_active']) {
		$session_data['onlineuser'] = count(DB::getQueryRows("SELECT id FROM community_whoisonline"));
	}

}
