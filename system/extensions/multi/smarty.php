<?php

$objSmarty = new \Cms\Service\Smarty();

$strView = "";
if(isset($_VARS['mu_action']) && $_VARS['mu_action'] == 'detail')
{
	$strView = 'detail';
} else {
	$strView = 'list';
}

$strSelect = '';
$aFieldData = array();
$strSql = "
			SELECT 
				multi_init.id as multi_id, 
				multi_fields.field_id as field_id,  
				multi_fields.type as type,  
				multi_fields.options as options  
			FROM  
				multi_init JOIN
				multi_fields ON
					multi_init.id = multi_fields.multi_id
			WHERE 
				multi_init.id = ".(int)$config->multi_id." AND  
				multi_fields.display LIKE '%".$strView."%'  
			ORDER BY  
				multi_fields.position";
$rFields = DB::getQueryRows($strSql);
foreach($rFields as $aFields) {
	$aFieldData[$aFields['field_id']] = $aFields;
	switch($aFields['type']) {
		case "date":
			$strSelect .= 'UNIX_TIMESTAMP(e.field_'.$aFields['field_id'].') field_'.$aFields['field_id'].', ';
			break;
		default:
			$strSelect .= 'e.field_'.$aFields['field_id'].', ';
			break;
	}
}

// Search >>>

$i=0;
$sParameter = $where = "";
foreach($_VARS as $key=>$val) {
	if($par = strstr($key, "search")) {
		if(!is_array($val)) {
			$aVal = array();
			$aVal[0] = $val;
			$sAdd = "";
		} else {
			$aVal = $val;
			$sAdd = "[]";
		}
		foreach($aVal as $k=>$v) {
			$sParameter .= $key.$sAdd."=".$v."&";
			if($par = strstr($key,"search_")) {

				if(is_numeric(substr($key,7)))
				{
					$sCol = "field_"  .substr($key, 7);
				}
				else
				{
					$sCol = substr($key, 7);
				}

				switch($_VARS['searchmode_'.substr($key,7)][$k]) {
					case "1":
						$where .= " (`".$sCol."` LIKE '%".$v."%') OR ";
						break;
					case "2":
						$where .= " (`".$sCol."` LIKE '".$v."%') OR ";
						break;
					case "3":
						$where .= " (`".$sCol."` LIKE '%".$v."') OR ";
						break;
					case "4":
						$where .= " (`".$sCol."` LIKE '".$v."') OR ";
						break;
					case "5":
						$where .= " (`".$sCol."` = '".$v."') OR ";
						break;
					case "6":
						$where .= " (`".$sCol."` >= ".intval($v).") OR ";
						break;
					case "7":
						$where .= " (`".$sCol."` < ".intval($v).") OR ";
						break;
					case "8":
						$where .= " (`".$sCol."` != '') OR ";
						break;
					case "9":
						if($v=='0') $v = 0;
						$where .= " (`".$sCol."` = '".$v."' OR '".$v."' = 0) OR ";
						break;
					default:
						$where .= " (`".$sCol."` LIKE '%".$v."%') OR ";
						break;
				}
				$i++;
			}
		}
	}
}
$where = substr($where,0,-3);

if(empty($where) || !$where)
{
	$where = "1";
}

// Query Zusatz für Volltextsuche
$sWhereFulltext = false;

if($_VARS['mu_keyword']) {
	$sWhereFulltext = "(";
	$mu_i=0;
	foreach($aFieldData as $aFields) {
		$sWhereFulltext .= "(field_".$aFields['field_id']." LIKE '%".$_VARS['mu_keyword']."%') OR ";
	}
	$sWhereFulltext .= " 0) ";
}

if(!$sWhereFulltext) $sWhereFulltext = "1";

$arr = array();
$sOrderBy = "";

if ($config->random == 1) {
	$sOrderBy .= "RAND()";
} else {
	if($config->sortbycat > 0) {
		$sOrderBy .= "c.position, c.name, ";
	}
	foreach((array)$config->sortby_field as $k=>$v) {
		if(empty($v)) {
			continue;
		}
		if(is_numeric($v)) {
			$sOrderBy .= "e.field_".$v." ".$config->sortby_dir[$k].",";
		} else {
			$sOrderBy .= "e.`".$v."` ".$config->sortby_dir[$k].",";
		}
	}
	$sOrderBy .= "e.position";
}

$sWhere = "";
if($config->showcat > 0) {
	$sWhere .= " AND e.category_id = '".intval($config->showcat)."' ";
}

if(!$_VARS['mu_start'] || $_VARS['mu_start'] < 0) {
	$_VARS['mu_start'] = 0;
}

$dOffset = $config->show != '' ? $config->show : 10;

$sValidQuery = Ext_Multi::getValidQueryPart($config->select_mode);

$sQuery = "
		SELECT SQL_CALC_FOUND_ROWS 
			".$strSelect."
			e.id,
			e.created,
			e.title,
			e.validfrom,
			e.validuntil,
			e.position,
			c.id category_id,
			c.name category_name,
			c.multi_id multi_id,
			GROUP_CONCAT(CONCAT(`ma_0`.`customer_db_id`, '_', `ma_0`.`customer_group_id`) SEPARATOR '|') AS `access_0`,
			GROUP_CONCAT(CONCAT(`ma_1`.`customer_db_id`, '_', `ma_1`.`customer_group_id`) SEPARATOR '|') AS `access_1`
		FROM 
			`multi_table_" . $config->multi_id . "` AS `e`	LEFT OUTER JOIN 
			`multi_categories` AS `c`							ON
				`e`.`category_id` = `c`.`id`				LEFT OUTER JOIN
			`multi_access` AS `ma_0`							ON
				`e`.`id` = `ma_0`.`multi_entry_id` AND
				`ma_0`.`access` = 0							LEFT OUTER JOIN
			`multi_access` AS `ma_1`							ON
				`e`.`id` = `ma_1`.`multi_entry_id` AND
				`ma_1`.`access` = 1
		WHERE 
			(".$where.") AND 
			(".$sWhereFulltext.") AND 
			(".$sValidQuery.") AND
			(e.language_code = '' OR e.language_code = '".\DB::escapeQueryString($page_data['language'])."')  
			".$sWhere." 
		GROUP BY
			`e`.`id`
		ORDER BY 
			".$sOrderBy."
		";

if($strView == 'detail') {
	$sQuery .= "";
} else {
	$sQuery .= "LIMIT ".(int)$_VARS['mu_start'].", ".$dOffset."";
}

$aList = DB::getQueryData($sQuery);

$aCount = DB::getQueryData('SELECT FOUND_ROWS() AS `count`');

if($strView == 'detail')
{
	foreach((array)$aList as $iKey => $aValue)
	{
		if($aValue['id'] == $_VARS['mu_id'])
		{
			if(!Ext_Multi_Access::checkAccess($aValue['access_0'], $aValue['access_1']))
			{
				continue;
			}

			$aPrev = $aList[$iKey-1];
			$aResult = $aValue;
			$aNext = $aList[$iKey+1];
			break;
		}
	}
	$objSmarty->assign('aPrev', $aPrev);
	$objSmarty->assign('aResult', $aResult);
	$objSmarty->assign('aNext', $aNext);
}
else
{

	foreach((array)$aList as $iKey => $aValue) {

		if(!Ext_Multi_Access::checkAccess($aValue['access_0'], $aValue['access_1']))
		{
			unset($aList[$iKey]);
			continue;
		}

		foreach($aFieldData as $idField=>$aField) {

			if($aField['type'] == "download") {

				$sKey = 'field_'.$idField;
				$sValue = $aValue[$sKey];
				$sFile = "/".$sValue;
				$sName 	= substr($sFile,(strrpos($sFile,"/")+1));
				$sLink = "/system/applications/download.php?multi_data_id=".$my['multi_data_id']."&targetname=".urlencode($sName)."&filepath=".urlencode($sValue);
				$sExt 	= substr($sValue,(strrpos($sValue,".")+1));
				$sPath = Util::getDocumentRoot()."media/".$sValue;
				$sSize 	= Util::formatFilesize(filesize($sPath));

				$aList[$iKey]['ext']	= $sExt;
				$aList[$iKey]['size'] 	= $sSize;
				$aList[$iKey]['link'] 	= $sLink;

			}

		}

	}

	// Wenn keine Ergebnisse
	if(count($aList) < 1) {
		$objSmarty->assign('aList', false);
	// Ergebnisse
	} else {
		$objSmarty->assign('aList', $aList);
	}

	$objSmarty->assign('iStart', (int)$_VARS['mu_start']);
	$objSmarty->assign('iIteration', $dOffset);
	$objSmarty->assign('iTotal', $aCount[0]['count']);

}

$objSmarty->assign('sVariables', $sParameter);
$objSmarty->assign('sKeyWord', $_VARS['mu_keyword']);

if(!empty($_SESSION['history'])) {
	$aLatest = end($_SESSION['history']);

	if(!empty($aLatest)) {
		$objSmarty->assign('sBackLink', $aLatest['url'].'?'.$aLatest['query']);
	}
}

$objSmarty->displayExtension($element_data);
