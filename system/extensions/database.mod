<?php
/**
 * Created on 12.06.2006
 * @copyright Copyright &copy; 2006, plan-i GmbH, Bastian Haustein
 * @author Bastian Haustein 
 * @version 0.1
 */

/* 
include_once dirname(__FILE__)."/../includes/sajax.inc.php";
//include_once __FILE__."modDb/modDb.inc.php";
// Zuk�nftiges Include: 
*/

// Funktionen und Konstantennur einmal ausf�hren
if(!function_exists('loadCdbStructure'))
{
	/* Sinnvolle SQL-Befehle
	 * 
	 * Daten zu Paragraph hinzuf�gen:
	 * INSERT INTO `db_form_par_cols`  (`id_form_par`,`id_column_def`,`position`)
	 * SELECT '24', id, id*10 FROM `db_columns`  WHERE active=1 AND id_table='1'
	 * 
	 * 
	 * Backup-Tabellen modifizieren:
	 * ALTER TABLE `customer_db_8_backup` ADD `original_id` INT UNSIGNED NOT NULL AFTER `id` ,
	 * ADD `backup_date` TIMESTAMP NOT NULL AFTER `original_id` ;
	 * ALTER TABLE `db_table_7_backup` ADD INDEX ( `original_id` , `backup_date` ) ;
	 * 
	 * 
	 * INSERT INTO `db_form_par_cols` (`id_column_def`,`position`,`num_row`, `id_form_par`)
	 * SELECT cd.id,cd.id * 10, 1, 11
	 * FROM `db_column_definitions` cd, db_columns c
	 * WHERE c.id = cd.id_col
	 * AND c.id_table =1
	 * 
	 */

	include_once dirname(__FILE__)."/database/database2.inc.php";	
	
 
}

if($modDb_admin)
	$_SESSION['datenbank']['modDb_admin']=true;

$cConfig = new \Cms\Helper\ExtensionConfig($page_id, $element_data["number"]);
if(strpos($_SERVER['PHP_SELF'],'/german/')!==false || $_SESSION['datenbank']['modDb_admin'])
	include_once dirname(__FILE__)."/database/template.inc.php";

else	
	include_once dirname(__FILE__)."/database/template_eng.inc.php";

//$sTemplate = $my_element['content'];

//Konstanten:
global $mod_db_debug;
$mod_db_debug =false; 
$iMyCustomerDb =	$cConfig->iUserCdb;
$idForm =			$cConfig->iIdForm;
$idDb =				1;// NICE: aus Form holen!
$cModDB = 			new cModDB($idForm, $iMyCustomerDb, $iUserId);




















// Wenn in der admin eingebunden, dann gelten andere spielregeln:
global $modDb_admin;

// debug:
if($_VARS['task']=='resetsession')
{
	if($_SESSION['datenbank']['modDb_admin'])
	{
		$modDb_admin = true;
		$_GET['id'] = $_SESSION['datenbank']['id_dataset'];
	}
	unset($_SESSION['datenbank']);
}




global $file_data;
$sLanguage = "";
$aLanguages = array("german", "english");
if(in_array($file_data["dir"][0], $aLanguages))
{
	$sLanguage = $file_data["dir"][0];
}




if(($modDb_admin || $_SESSION['datenbank']['modDb_admin']) && function_exists('accesschecker')) {

	Access_Backend::checkAccess('voice_compass');

	$cConfig->iUserCdb =	8;
	if(strpos($_SERVER['PHP_SELF'],'/german/')!==false  || $_SESSION['datenbank']['modDb_admin'])
		$cConfig->iIdForm =		2;
	else
		$cConfig->iIdForm =		3;
	
	if($_GET['id'])
	{
		//laden! 
		unset($_SESSION['datenbank']);
		$_SESSION['datenbank']['id_dataset'] = $_GET['id'];
		if($sLanguage)
		{
			$aEmptyForm = $cModDB->createEmptyForm($sLanguage);
		}
		else
		{
			$aEmptyForm = $cModDB->createEmptyForm();
		}
		$aDataset = $cModDB->loadDataset($_SESSION['datenbank']['id_dataset']);
		$cModDB->insertDataIntoEmptyForm($aDataset, $aEmptyForm);
		$_SESSION['datenbank']['freigabe']=$aDataset['data'][1][1]['freigabe'];
		$_SESSION['datenbank']['pages'] = $aEmptyForm;
		$_SESSION['datenbank']['loaded']=1;
		$_SESSION['datenbank']['modDb_admin']=true;
		if($_GET['num_row'])
		{
			$_SESSION['datenbank']['page']=2+intval($_GET['num_row']);
		}
	}	
}













$aBlocks = array();
$sTemplate = 						str_replace('<#PHP_SELF#>',$_SERVER['PHP_SELF'], $sTemplate);
$aBlocks['page'] = 					\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_page');
$aBlocks['columns'] =				\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_columns');
$aBlocks['headline'] =				\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_headline');
$aBlocks['text'] =					\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_text');
	
$aBlocks['col_url'] =				\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_col_url');
$aBlocks['col_text'] =				\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_col_text');
$aBlocks['col_textarea'] =			\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_col_textarea');
$aBlocks['col_radio_h'] =			\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_col_radio_h');
$aBlocks['col_radio_v'] =			\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_col_radio_v');
$aBlocks['col_select'] =			\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_col_select');
$aBlocks['col_select_ver'] =		\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_col_select_ver');
$aBlocks['col_upload'] =			\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_col_upload');
$aBlocks['col_select_or_text'] =	\Cms\Service\PageParser::checkForBlock($sTemplate, 'col_select_or_text');
$aBlocks['col_multiselect'] = 		\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_col_multiselect');
$aBlocks['col_tristate'] = 			\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_col_tristate');
$aBlocks['col_image'] = 			\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_col_image');
$aBlocks['col_hidden'] = 			\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_col_hidden');

$aBlocks['col_subheading'] = 		\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_col_subheading');
$aBlocks['col_info'] = 				\Cms\Service\PageParser::checkForBlock($sTemplate, 'template_col_info');						


//vom Benutzer:
global $user_data;
$iUserId =	 		$user_data['id'];
$iUserCustomerDb =	$user_data['idTable'];







// actions nr 1

if($_VARS['modDB_task']=='duplicate')
{
	$iPageKey = $_VARS['modDB_page'];
	//erst mal ans ende der gleichartigen seiten gehen...
	#echo "dubliziere:".$_SESSION['datenbank']['pages'][$iPageKey]['name'].
	#	"==".$_SESSION['datenbank']['pages'][$iPageKey+1]['name'].
	#	("(#)".$_SESSION['datenbank']['pages'][$iPageKey+1]['name']!='');
	while($_SESSION['datenbank']['pages'][$iPageKey]['id']==$_SESSION['datenbank']['pages'][$iPageKey+1]['id'] 
		&& $_SESSION['datenbank']['pages'][$iPageKey+1]['name']!='')
	{
		#echo "<br>++skip:".$_SESSION['datenbank']['pages'][$iPageKey]['name'].
		#"==".$_SESSION['datenbank']['pages'][$iPageKey+1]['name'].
		#'&&'.("(#)".$_SESSION['datenbank']['pages'][$iPageKey+1]['name']!='').':'.$iPageKey;
		++$iPageKey;
		$_SESSION['datenbank']['page']++;
	}
	$aPage = &$_SESSION['datenbank']['pages'][$iPageKey];
//	echo "<br><b>Dupliziere Seite $iPageKey $aPage[name] </b><br>";
	if($iPageKey<100
		&& $aPage['num_row']<$aPage['repeat_max'] 
		)	
	{				
		#echo "<br><b>Dupliziere Seite $iPageKey $aPage[name] ($aPage[num_row]/$aPage[repeat_max])</b><br>";
		for($i=count($_SESSION['datenbank']['pages'])-1; $i>=$iPageKey; $i--)
		{
			$_SESSION['datenbank']['pages'][$i+1] = $_SESSION['datenbank']['pages'][$i];
		}
		// spalten leeren und row-count erh�hen
		$iPageKey++;
		$aPage = &$_SESSION['datenbank']['pages'][$iPageKey];
		$aPage['num_row']++;
		if(is_array($aPage['paragraphs']))
		{
		//	echo "<br>$aPage[name] bzw. $iPageKey wird nach cols durchsucht.";
			foreach($aPage['paragraphs'] as $iParKey=>$aPar)
			{
				// Spalten durchgehen und Inhalte leeren
				if(is_array($aPar['columns']))
				{
			//		echo "<br>columns found.";
					foreach($aPar['columns'] as $iColKey=>$aCol)
					{
						$_SESSION['datenbank']['pages'][$iPageKey]['paragraphs'][$iParKey]['columns'][$iColKey]['num_row'] ++;
						$_SESSION['datenbank']['pages'][$iPageKey]['paragraphs'][$iParKey]['columns'][$iColKey]['value']='';
					}
				}
			}
		} 
	$_SESSION['datenbank']['page']++;	
	}
}
elseif($_VARS['modDB_task']=='remove')
{
	$iPageKey = $_VARS['modDB_page'];
	$aPage = $_SESSION['datenbank']['pages'][$iPageKey];
//	echo "<br><b>L�sche Seite $iPageKey $aPage[name] </b><br>";
	if($iPageKey<100
		&& $aPage['num_row']>1 
		)	
	{				
		#echo "<br><b>L�sche Seite $iPageKey $aPage[name] ($aPage[num_row]/$aPage[repeat_max])</b><br>";
		for($i=$iPageKey; $i<count($_SESSION['datenbank']['pages'])-1; $i++)
		{
			$_SESSION['datenbank']['pages'][$i] = $_SESSION['datenbank']['pages'][$i+1];
			if($aPage['id']==$_SESSION['datenbank']['pages'][$i]['id'])$_SESSION['datenbank']['pages'][$i]['num_row']--;
		}
		unset($_SESSION['datenbank']['pages'][count($_SESSION['datenbank']['pages'])-1]);
		echo "Sie m�ssen noch speichern, um das L�schen ab zu schlie�en!<br/>";
		$_SESSION['datenbank']['page']--;	
	}
}

// Wenn kein Formular geladen,dann Datensatz ermitteln und formular laden
if(intval($_SESSION['datenbank']['id_dataset'])<1)
{
	// ist derjenige eingeloggt?
	if($iUserCustomerDb == $cModDB->iCustomerDb && $iUserId > 0)
	{
		// Gibt es bereits einen Datenbankeintrag, der diesem Benutzer zugeordnet ist?
		$sSql =	"SELECT id_dataset FROM customer_db_$cModDB->iCustomerDb WHERE id='$iUserId' LIMIT 0,1";
		$aMy =	db_get_datarow($sSql);
		//DEBUG:
		#echo"<br>Datensatz f�r user laden:<br>$sSql<br>".mysql_error()."<br>ID:".$aMy['id_dataset'];
		
		
		if($sLanguage)
		{
			$aEmptyForm = $cModDB->createEmptyForm($sLanguage);
		}
		else
		{
			$aEmptyForm = $cModDB->createEmptyForm();
		}
		$_SESSION['datenbank']['id_dataset']=$aMy['id_dataset'];	
		if($_SESSION['datenbank']['id_dataset'] > 0 )
		{
			#echo"<br>Laden:<br>".$_SESSION['datenbank']['id_dataset'];
		
			$aDataset = $cModDB->loadDataset($_SESSION['datenbank']['id_dataset']);
			$cModDB->insertDataIntoEmptyForm($aDataset, $aEmptyForm);#
			$_SESSION['datenbank']['freigabe']=$aDataset['data'][1][1]['freigabe'];
		}
		else
		{	
			// Leeren Datensatz anlegen und ID merken
			$sSql =	"INSERT INTO `db_datasets` SET id_database = '$idDb'";
			db_query($sSql);
			#echo mysql_error();
			$_SESSION['datenbank']['id_dataset']=get_insert_id();
			$sSql =	"UPDATE `customer_db_$cModDB->iCustomerDb` SET `num_row`='1', `id_dataset`='".$_SESSION['datenbank']['id_dataset']."' WHERE id='$iUserId'";
			db_query($sSql);
			#echo mysql_error();
			#echo"<br>Neu angelegt:<br>".$_SESSION['datenbank']['id_dataset'];
			$aDataset = $cModDB->loadDataset($_SESSION['datenbank']['id_dataset']);
			$cModDB->insertDataIntoEmptyForm($aDataset, $aEmptyForm);
			$_SESSION['datenbank']['freigabe']=$aDataset['data'][1][1]['freigabe'];
		}
		$_SESSION['datenbank']['pages'] = $aEmptyForm;
		$_SESSION['datenbank']['loaded']=1;
	
	}
	else
	{
		echo "Bitte loggen Sie sich ein, um Ihre Daten bearbeiten zu k�nnen.<br>Please log in to edit your Data.";
	}
	
}
else
{
	// immer speichern, was gerade so l�uft.
	// aktuelle Daten in Session-Array integrieren:
	if($_VARS['modDB_task']!='remove') // beim l�schen einer Seite d�rfen die daten der seite nicht auf die neue Seite mit der aktuellen Nummer eingetragen werden!
	foreach($_VARS as $sKey=>$sVal)
	{
		if(substr($sKey,0,7)=='mod_db_')
		{
			if(is_array($sVal))
			{
				if(is_file($sVal['tmp_name']))
				{
					$aTemp = explode('_', substr($sKey,7));
					
					$sColName = $_SESSION['datenbank']['pages'][$aTemp[0]]['paragraphs'][$aTemp[1]]['columns'][$aTemp[2]]['col_id'] .
								'_'.$_SESSION['datenbank']['pages'][$aTemp[0]]['paragraphs'][$aTemp[1]]['columns'][$aTemp[2]]['col_name'];
					
					if(!is_dir(\Util::getDocumentRoot().'media/mod_database'))
						mkdir(\Util::getDocumentRoot().'media/mod_database' ,$system_data['chmod_mode_dir']);
					
					if(!is_dir(\Util::getDocumentRoot().'media/mod_database/'.$sColName.'/'))
						mkdir(\Util::getDocumentRoot().'media/mod_database/'.$sColName ,$system_data['chmod_mode_dir']);
					$sFileName = $sColName . '/'. intval($_SESSION['datenbank']['id_dataset']) . '_' . $sVal['name'];
					move_uploaded_file($sVal['tmp_name'], \Util::getDocumentRoot().'media/mod_database/'.$sFileName);
					chmod(\Util::getDocumentRoot().'media/mod_database/'.$sFileName ,$system_data['chmod_mode_file']);
					$_SESSION['datenbank']['pages'][$aTemp[0]]['paragraphs'][$aTemp[1]]['columns'][$aTemp[2]]['value']=$sFileName;
				}
			}
			else
			{
				$aTemp = explode('_', substr($sKey,7));
				$_SESSION['datenbank']['pages'][$aTemp[0]][paragraphs][$aTemp[1]][columns][$aTemp[2]][value]=$sVal;
			}
		}
		
	}
	
	
	
	if($_VARS['modDB_task']=='save')
	{
		$cModDB->saveDataset($_SESSION['datenbank'], $_SESSION['datenbank']['id_dataset'], true);
		$_SESSION['datenbank']['page']=1;
		if($_SESSION['datenbank']['freigabe']>0)
		{
			?>
<script language="JavaScript" type="text/javascript">
  alert('Sie haben bereits eine Freigabe erteilt! Um Speichern zu k�nnen muss diese wiederrufen werden. Bitte wenden Sie sich an den Support.\nYou have already confirmed your profile! You have to recall that to save your profile again. Please contact the support.');
</script>
  			
			<?
		}
	}
	else
	{
	
		// nur auto-speichern, 	wenn nicht administration
		if(!$_SESSION['datenbank']['modDb_admin'])
			$cModDB->saveDataset($_SESSION['datenbank'], $_SESSION['datenbank']['id_dataset'], true);
	}	
}


// hier nur noch was machen, wenn er geladen hat:
#if(intval($_SESSION['datenbank']['id_dataset'])>0)
{
	#echo "<br>".$_SESSION['datenbank']['page']."###".count($_SESSION['datenbank']['pages']);
	#echo "<br>".$_VARS['modDB_task']."##";
	
	
	$_SESSION['datenbank']['page'] = intval($_SESSION['datenbank']['page']);
	if($_VARS['modDB_task']=='goto')
	{
		$_SESSION['datenbank']['page']=$_VARS['modDB_page'];
	}
	elseif($_VARS['modDB_task']=='next')
	{
		$iOldPage = $_SESSION['datenbank']['page'];
		while($_SESSION['datenbank']['page'] < count($_SESSION['datenbank']['pages']))
		{
			$_SESSION['datenbank']['page']++;
			if(!$_SESSION['datenbank']['pages'][$_SESSION['datenbank']['page']-1]['invisible']) break;
		}
		if($_SESSION['datenbank']['page'] > count($_SESSION['datenbank']['pages']))#>= 
			$_SESSION['datenbank']['page'] = $iOldPage;
	}
	elseif($_VARS['modDB_task']=='prev')
	{
		$iOldPage = $_SESSION['datenbank']['page'];
		while($_SESSION['datenbank']['page'] >= 1)
		{
			$_SESSION['datenbank']['page']--;
			if(!$_SESSION['datenbank']['pages'][$_SESSION['datenbank']['page']-1]['invisible']) break;
		}
		if($_SESSION['datenbank']['page']<1)
		{
			$_SESSION['datenbank']['page'] = $iOldPage;
		}
	}
	
	if(intval($_SESSION['datenbank']['page'])<1)
	{
		$_SESSION['datenbank']['page']=1;
	}
	
	
	
	
	
	echo $cModDB->createPage($_SESSION['datenbank']['pages'][$_SESSION['datenbank']['page']-1], $_SESSION['datenbank']['page']-1, $aBlocks); // die Page-Nr l�uft ab 1, die ID ab 0
	
	// DEBUG:
	if($mod_db_debug)
	{
		echo "<br><b>PageArray:</b>";
		printArr($_SESSION['datenbank']);
		printArr($cModDB->loadDataset(1));
		echo "FREIGABE:".$aDataset['data'][1][1]['freigabe'];
		unset($_SESSION['datenbank']);
	}
	

	if($_SESSION['datenbank']['modDb_admin'])
		echo '<form method=post><center><input type=hidden name=task value=resetsession><input type=submit value="neu laden"></center></form>';
}
