<?php

namespace Admin\Http\Controller;

use Admin\Http\Controller\DB;

include(\Util::getDocumentRoot().'system/includes/admin.inc.php');

class ModulesController extends \MVC_Abstract_Controller {

	protected $_sAccessRight = 'modules_admin';
	
	protected $_sViewClass = '\MVC_View_Smarty';

	public function overview() {
		global $aUpdate,$iLastModul,$sLastName,$aModules;

		$_VARS = $this->_oRequest->getAll();

		ob_start();

		$aUpdate = array();
		$sLastName = false;
		$iLastModul = false;

		$oSession = \Core\Handler\SessionHandler::getInstance();

		if(!$oSession->has('framework_update_server')) {
			$oSession->set('framework_update_server', \System::d('update_server'));
		}

		if(!empty($_VARS['update_server'])) {

			$oUpdate = new \Update($_VARS['update_server']);
			$aModules = $oUpdate->getModules();

			if(!empty($aModules)) {
				$oSession->set('framework_update_server', $_VARS['update_server']);
			}

		}

		$oUpdate = new \Update($oSession->get('framework_update_server'));

		// Einstellungen
		$sInstallServer = $oSession->get('framework_update_server');

		$aUpdateExtensions = $oUpdate->getExtensions();
		
?>

<?
if($_VARS['task'] == "add") {
	if($_VARS['id']>0) {
		$my_module = \DB::getQueryRow("SELECT * FROM system_elements WHERE id = ".(int)$_VARS['id']."");
	} else {
		$my_module['title'] = \L10N::t('Neue Erweiterung', 'Framework');
	}
?>
			<h2><?=$my_module['title']?></h2>

			<form method="POST" action="modules">
			<input type="hidden" name="task" value="save">
			<input type="hidden" name="id" value="<?=$_VARS['id']?>">
			<?=printTableStart()?>
			<?=printFormText(\L10N::t('Titel', 'Framework'), "title", $my_module['title'])?>
			<?=printFormText(\L10N::t('Name', 'Framework'), "file", $my_module['file'],(($_VARS['id']>0)?"readonly style=\"background-color:#dddddd;\"":""))?>
			<?=printFormText(\L10N::t('Kategorie', 'Framework'), "category", $my_module['category'])?>
			<?=printFormCheckbox(\L10N::t('Administration', 'Framework'), "administrable", 1, $my_module['administrable'])?>
			<?=printFormCheckbox(\L10N::t('Backend Einbindung aktivieren', 'Framework'), "include_backend", 1, $my_module['include_backend'])?>
			<?=printFormCheckbox(\L10N::t('Frontend Einbindung aktivieren', 'Framework'), "include_frontend", 1, $my_module['include_frontend'])?>
			<?=printFormCheckbox(\L10N::t('Frontend Einbindung immer aktivieren?', 'Framework'), "include_mode", 1, $my_module['include_mode'])?>
			<?=printFormSelect(\L10N::t('Typ', 'Framework'), "element", array("modul"=>"Modul","template"=>"Vorlage"), $my_module['element'])?>
			<?=printFormTextarea(\L10N::t('Code', 'Framework'), "template", ($my_module['template']), "10")?>
			<?=printFormTextarea(\L10N::t('Beschreibung', 'Framework'), "description", ($my_module['description']))?>
			<?=printTableEnd()?>
			<?=printSubmit(\L10N::t('Erweiterung speichern', 'Framework'))?>
			</form>
<?
} elseif($_VARS['task'] == "buy") {
	
	$aModules = $oUpdate->getModules();
	
?>
		<h2><?=\L10N::t('Verfügbare Erweiterungen', 'Framework')?></h2>
<?

?>
<script type="text/javascript">

function get_require(sRequire,sElement,sTitle){
	var aExtension=sRequire.split(":");
	aRequire = new Array();
	for(var i=1;i<=aExtension.length;i++){
		
		var sTemp = aExtension[i-1];
		if(sTemp != ""){
			if(document.getElementById(sElement).checked == false){
				document.getElementById(sTemp).checked = false;
				document.getElementById(sTemp).disabled = "";
				document.getElementById('tr_'+sTemp).style.backgroundColor="";
				document.getElementById(sTemp+'_warning').style.display="none";
			} else {
				document.getElementById(sTemp).checked = true;
				document.getElementById(sTemp).disabled = "disabled";
				document.getElementById('tr_'+sTemp).style.backgroundColor="#FE9E32";
				document.getElementById(sTemp+'_warning').style.display="inline";
				document.getElementById(sTemp+'_pic').title='Wird für die Installation von '+sTitle+' benötigt';
			}
		}

	}
	
}

</script>
<link href="http://<?=\System::d('update_server')?>/install.css" rel="stylesheet" type="text/css" />
	<br><br>
	<form method="POST" action="modules">
	<input type="hidden" name="task" value="auto_install">
		<table class="table table-condensed table-striped table-hover">
			<tr>
				<th width="45%"><?=\L10N::t('Erweiterung', 'Framework')?></th>
				<th width="30%"><?=\L10N::t('Kategorie', 'Framework')?></th>
				<th width="20%"><?=\L10N::t('Preis', 'Framework')?></th>
				<th width="5%">&nbsp;</th>
			</tr>
			<?

			foreach($aModules as $key=>$val) { 
	                   	    if($val['require']['extension']){
		                    	$sRequire = implode(':',$val['require']['extension']);
		                    } else {
		                    	$sRequire = "";
		                    }
			?>
			<tr id="tr_<?=$key?>"  style="background-color:<?=(($val['license']==1)?"":"#FFE0DD")?>;">
				<td id='<?=$key?>_warning'><?=$val['title']?> <img id='<?=$key?>_pic' src='http://<?=\System::d('update_server')?>/messagebox_warning.png' style="display:none"/></td>
				<td><?=$val['category']?></td>
				<td align="right"><?=(($val['license']==1)?\L10N::t('Lizenz vorhanden', 'Framework'):sprintf(\L10N::t('%s € netto', 'Framework'), $val['value']))?></td>
				<td align="center" ><input name="<?=$key?>" id="<?=$key?>" type="checkbox" value="1" onclick="get_require('<?=$sRequire?>','<?=$key?>','<?=$val['title']?>')" /></td>
			</tr>
			<?
			}
			?>
		</table>
		<div style="float:left;">
			<p>
				<button class="btn" onClick="document.location.href='modules';return false;"><?=\L10N::t('Abbrechen', 'Framework')?></button>
			</p>
		</div>
		<div style="float: right;">
			<?=printSubmit('Install')?>
		</div>
		<div style="clear:both;"></div>
		

	</form>
<?
} elseif($_VARS['task'] === 'update') {

	if($this->_oAccess->hasRight('update')) {
		header('Location: /admin/update.html?extension='.$this->_oRequest->get('module'));
	}
	
} elseif($_VARS['task'] == "install") {

	// Modul Informationen holen

	$aModule = $oUpdate->getModule($_VARS['module']);

	$oModule = new \Core\Service\Module($oUpdate, $aModule);
		
	DB::executeQuery("INSERT INTO system_elements SET title = '".$aModul['TITLE']."', element = 'modul', category = '".$aModul['CATEGORY']."', file = '".$_VARS['module']."', administrable = 1, active = 1");

?>
			<h2><?=$aModul['TITLE']?></h2>
<?

	$aUpdatedQueries = $oModule->executeQueries();
	
	$aUpdatedFiles = $oModule->getFiles();

	$oModule->executeConfigSql();
	
	echo "<p>".\L10N::t('Die Installation wurde erfolgreich durchgeführt.', 'Framework')."</p>";
	\Log::enterLog(0,"Erweiterung '".$aModul['TITLE']."' wurde installiert.");

?>
			<p><a href="modules"><?=\L10N::t('zurück', 'Framework')?></a></p>
<?
} elseif($_VARS['task'] == "auto_install") {

		$aModules = $oUpdate->getModules();
		$i = 0;
		foreach($aModules as $sModuleKey=>$aModule) {
			if($_VARS[$sModuleKey] == "1"){
			 	
				$oModule = new \Core\Service\Module($oUpdate, $aModule);
				$oModule->install();
		
			 	echo "\"".$aModule['title']."\" wurde erfolgreich installiert.<br />";

			 	$i = 1;
			 }	
		}

		if ($i == 0){
			echo "Keine Erweiterung ausgewählt.";
		} 

?>		
		<p>
			<button class="btn" onClick="document.location.href='modules?task=buy';"><?=\L10N::t('zurück', 'Framework')?></button>
		</p>
<?

} elseif($_VARS['task'] == "save") {

	if($_VARS['id']>0) {
		$query = "UPDATE system_elements SET " .
					"title = '".\DB::escapeQueryString($_VARS['title'])."', " .
					"file = '".\DB::escapeQueryString($_VARS['file'])."', " .
					"category = '".\DB::escapeQueryString($_VARS['category'])."', " .
					"element = '".\DB::escapeQueryString($_VARS['element'])."', " .
					"template = '".\DB::escapeQueryString($_VARS['template'])."', " .
					"description = '".\DB::escapeQueryString($_VARS['description'])."', " .
					"version = (version+0.01), " .
					"administrable = '".$_VARS['administrable']."',  " .
					"include_backend = '".$_VARS['include_backend']."', " .
					"include_frontend = '".$_VARS['include_frontend']."', " .
					"include_mode = '".$_VARS['include_mode']."' " .
				"WHERE id = '".$_VARS['id']."'";
		\Log::enterLog(0,"Erweiterung '".$_VARS['title']."' wurde geändert.");
	} else {
		$query = "INSERT INTO system_elements SET " .
					"title = '".\DB::escapeQueryString($_VARS['title'])."', " .
					"file = '".\DB::escapeQueryString($_VARS['file'])."', " .
					"category = '".\DB::escapeQueryString($_VARS['category'])."', " .
					"element = '".\DB::escapeQueryString($_VARS['element'])."', " .
					"template = '".\DB::escapeQueryString($_VARS['template'])."', " .
					"description = '".\DB::escapeQueryString($_VARS['description'])."', " .
					"version = 0.01, " .
					"administrable = '".$_VARS['administrable']."', " .
					"include_backend = '".$_VARS['include_backend']."', " .
					"include_frontend = '".$_VARS['include_frontend']."', " .
					"include_mode = '".$_VARS['include_mode']."', " .
					"active = 1";
		\Log::enterLog(0,"Erweiterung '".$_VARS['title']."' wurde angelegt.");
	}
	\DB::executeQuery($query);
?>
			<h2><?=\L10N::t('Erweiterung gespeichert', 'Framework')?></h2>
			<h3><?=\L10N::t('Das Element wurde erfolgreich gespeichert.', 'Framework')?></h3>
			<p>
				<button onclick="document.location.href = 'modules';" class="btn"><?=\L10N::t('zurück zur Übersicht', 'Framework')?></button>&nbsp;
				<button onclick="document.location.href = 'modules?task=add&id=<?=$_VARS['id']?>';" class="btn"><?=\L10N::t('zurück zur Erweiterung', 'Framework')?></button>
			</p>
<?
} elseif($_VARS['task'] == "delete") {
	
//	$modul = $_VARS['module'];
//	
//	$query = "DELETE FROM
//					`system_elements`
//				WHERE
//					`file` = '".$modul."'";
//					
//	db_query($db_data['system'],$query);
//	
//	$selectetmodule = "SELECT
//							*
//						FROM
//							`cms_blockdata`
//						WHERE
//							`content` = '".$modul."'";
//	
//	$rDelMod = db_query($db_data['system'],$selectetmodule);
//	$aDelMod = mysql_fetch_array($rDelMod);
//	
//	foreach ($aDelMod as $aDel){
//	
//	$query = "DELETE FROM
//					`cms_blockdata`
//				WHERE
//					`content_id` = '".$aDel['content_id']."'";
//					
//	db_query($db_data['system'],$query);
//	}
//	
//	echo "Erweiterung erfolgreich gelöscht.";
?>
	<p>
		<button class="btn" onClick="document.location.href='modules';"><?=\L10N::t('zurück', 'Framework')?></button>
	</p>
<?
} else {
?>
			
	<form method="post" action="modules">
		<?=printTableStart()?>
		<?=printFormText('Repository-URL', 'update_server', $oSession->get('framework_update_server'))?>
		<?=printTableEnd()?>
		<?=printSubmit('Repository-URL übernehmen')?>
	</form>

			<table class="table table-condensed table-striped table-hover">
				<tr>
					<th style="width:auto;"><?=\L10N::t('Erweiterung', 'Framework')?></th>
					<th style="width:180px;"><?=\L10N::t('Kategorie', 'Framework')?></th>
					<th style="width:80px;"><?=\L10N::t('Version', 'Framework')?></th>
					<th style="width:80px;"><?=\L10N::t('Aktionen', 'Framework')?></th>
				</tr>
<?

	$aModules = $oUpdate->getModules();

	$aItems = \DB::getQueryRows("SELECT * FROM system_elements WHERE (element = 'modul' || element = 'template') AND active = 1 ORDER BY category, title");
	foreach($aItems as $my_modules) {

		$bHasUpdates = false;

		if(
			isset($aUpdateExtensions[$my_modules['file']]) &&
			$aUpdateExtensions[$my_modules['file']] > $my_modules['version']
		) {
			$bHasUpdates = true;
		}

		$bolRight = count((array)\DB::getQueryRows("SELECT * FROM system_rights WHERE `right` = '".$my_modules['file']."' LIMIT 1"));
		if($bolRight) {
			$strRight = $my_modules['file'];
		} else {
			$strRight = "modules_admin";
		}

		if($this->_oAccess->hasRight($strRight)) {
?>
				<tr id="tr_<?=$my_modules['id']?>">
					<? if($my_modules['administrable'] == 1 && is_file(\Util::getDocumentRoot()."system/legacy/admin/extensions/".$my_modules['file'].".html")) {?>
					<td><a href="extensions/<?=$my_modules['file']?>.html"><?=$my_modules['title']?></a>&nbsp;</td>
					<? } else { ?>
					<td><?=$my_modules['title']?>&nbsp;</td>
					<? } ?>
					<td><?=$my_modules['category']?>&nbsp;</td>
					<td align="right"><?=$my_modules['version']?>&nbsp;</td>
					<td align="center">
						<a href="modules?task=add&id=<?=$my_modules['id']?>"><img src="media/edit.png" border="0" title="<?=\L10N::t('Erweiterung editieren', 'Framework')?>"></a>&nbsp;
<?
					if(
						$bHasUpdates && 
						$this->_oAccess->hasRight('update')
					) {
?>
						<a href="modules?task=update&module=<?=$my_modules['file']?>"><img src="media/modules_update.png" border="0" title="<?=\L10N::t('Erweiterung aktualisieren', 'Framework')?>"></a>&nbsp;
<?
					}
?>
						<!-- Deinstallations Funktion
							<a href="modules?task=delete&module=<?=$my_modules['file']?>" onclick="if (confirm('Erweiterung wirklich löschen?\nAlle Blöcke mit dieser Erweiterung werden geleert!'))return true; else return false;"><img src="/admin/media/edit_delete.gif" border="0" title="Erweiterung deinstallieren"></a>
						-->
					</td>
				</tr>
<?
		}
	}
?>
			</table>
			<div class="pull-right">
				<button class="btn" onClick="document.location.href='modules?task=add';"><?=\L10N::t('Eigenes Element hinzufügen', 'Framework')?></button>
				<button class="btn" onClick="document.location.href='modules?task=buy';"><?=\L10N::t('Standard-Erweiterung installieren', 'Framework')?></button>
			</div>
<?
}

		$sContent = ob_get_clean();

		$this->set('sContent', $sContent);
		
	}
	
}
