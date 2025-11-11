<?php

require_once(Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");
require_once($_SERVER['DOCUMENT_ROOT']."/system/extensions/customdata/customdata.php");

function gui_ajax_where_replace_recursive($arrWhere, $strValue) {

	foreach((array)$arrWhere as $intKey=>$arrItem) {
		if(is_array($arrItem) && !isset($arrItem['field'])) {
			$arrWhere[$intKey] = gui_ajax_where_replace_recursive($arrItem, $strValue);
		} else {
			if($arrItem['cond_arg'] == 'strSearch') {
				$arrWhere[$intKey]['cond_arg'] = "%".$strValue."%";
			}
		}
	}
	return $arrWhere;
}

$arrOptions = $_SESSION['gui_autocomplete'][$_VARS['key']];

if(!isset($arrOptions['limit'])) {
	$arrOptions['limit'] = 20;
}

if($arrOptions['mode'] == 'customdata') {

	$arrOptions['customdata']['where'] = gui_ajax_where_replace_recursive($arrOptions['customdata']['where'], $_VARS['q']);

	$arrData = CustomData_Dao::fetchTableData($arrOptions['table'], $arrOptions['customdata']);

} else {

	$strSelect = "*";

	if(isset($arrOptions['fields'])) {
		$strSelect = '`'.implode('`, `', $arrOptions['fields']).'`';
	}

	$strSql = "
				SELECT
					".$strSelect."
				FROM
					#strTable
				WHERE
					".$arrOptions['where']."
				ORDER BY
					".db_addslashes($arrOptions['orderby'])."
					".db_addslashes($arrOptions['orderdir'])."
				LIMIT :intLimit
				";
	$arrSql = array();
	$arrSql['strTable']		= $arrOptions['table'];
	$arrSql['strSearch']	= "%".$_VARS['q']."%";
	$arrSql['intLimit']		= $arrOptions['limit'];
	$arrData = DB::getPreparedQueryData($strSql, $arrSql);

}

foreach((array)$arrData as $arrItem) {
	echo $arrItem[$arrOptions['display']];
	foreach((array)$arrItem as $mixKey=>$mixItem) {
		if(!is_numeric($mixKey)) {
			echo "|".$mixItem;
		}
	}	
	echo "\n";
}

/*

Array

(

    [table] => newsletter2_recipients

    [key] => id

    [where] => `name` LIKE :strSearch, `firstname` LIKE :strSearch, `email` LIKE :strSearch

    [orderby] => email

    [orderdir] => ASC

)

*/

?>
