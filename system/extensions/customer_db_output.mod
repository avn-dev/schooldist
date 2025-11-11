<?

////////////////////////////////////////////////////
/////////////// Modul Eingabefeld //////////////////
////////////////////////////////////////////////////
// Dieses Modul erzeugt ein generisches Ausgabefeld
////////////////////////////////////////////////////

global $parent_config;

$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

$aResult = getCustomerFieldData($config->field_name,$parent_config->idDatabase);

if($aResult["type"]=="Select Field" OR $aResult["type"]=="Radio-Button") {
    $aResult["type"]="select";
}

///////////////////////////////////
$sType  		= trim($aResult["type"]);
$sValue 		= trim($_SESSION['customer_db_'.$config->field_name]);
$iDefinition	= intval($aResult["id"]);
$aAdditional	= unserialize($aResult["additional"]);
///////////////////////////////////

switch($sType) {
	case "image":
		$sValue = getFieldOutput($sType, $sValue, $iDefinition,  $aAdditional,$config);
		if(is_file(\Util::getDocumentRoot().$sValue)) {
		?>
			<img src="<?=$sValue?>" border="0" <?=$config->default_class?>>
		<?
		} else {
			echo $config->no_file;
		}
		break;

	case "Datei":
		$sValue = getFieldOutput($sType, $sValue, $iDefinition, $aAdditional,$config);
		if(is_file(\Util::getDocumentRoot().$sValue)) {
		?>
			<a href="<?=$sValue?>" <?=$config->default_class?>><?=$config->link_name?></a>
		<?
		} else {
			echo $config->no_file;
		}
		break;

	case "ProtectedDatei":
		$sValue = getFieldOutput($sType, $sValue, $iDefinition, $aAdditional,$config);
		if(is_file(\Util::getDocumentRoot().$sValue)) {
		
			/*<a href="<?=$sValue?>" <?=$config->default_class?>><?=$config->link_name?></a>*/
			 
		?>
		<a href="../../system/applications/download_ProtectedFile.php?file=<?=$sValue?>" <?=$config->default_class?> target="_blank""><?=$config->link_name?></a>
		<?
		} else {

			echo $config->no_file;#."|".$sValue."|";
		}
		break;

	default:
		echo getFieldOutput($sType, $sValue, $iDefinition, $aAdditional,$config);
		break;
}

?>